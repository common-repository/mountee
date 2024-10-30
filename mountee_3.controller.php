<?php

class Mountee_3_Controller
{

	var $theme_helper = NULL;
	var $mountee_lib = NULL;

	public function __construct()
	{
		$this->theme_helper = new Mountee_3_Theme_Helper();
		$this->mountee_lib = new Mountee_lib();
	}

	/**
	 * Display the admin page of Mountee
	 */
	public function get_mountee_admin_page()
	{
		$current_user = wp_get_current_user();
		$site_url = get_site_url();
		$site_name = get_bloginfo('name');
		$mountee_url = $site_url.'/wp-json/mountee_3';

		if (!$this->theme_helper->test_theme_folder_read())
		{
			$theme_error_message = 'Your theme folder <em>'.$this->theme_helper->theme_folder_name.'</em> is not readable.';
		}
		else if (!$this->theme_helper->test_theme_folder_write())
		{
			$theme_error_message = 'Your theme folder <em>'.$this->theme_helper->theme_folder_name.'</em> is not writable.';
		}
		else if (!$this->theme_helper->test_theme_inner_files_read())
		{
			$theme_error_message = 'One or several of your theme files in <em>'.$this->theme_helper->theme_folder_name.'</em> are not readable.';
		}
		else if (!$this->theme_helper->test_theme_inner_files_write())
		{
			$theme_error_message = 'One or several of your theme files in <em>'.$this->theme_helper->theme_folder_name.'</em> are not writable.';
		}

		include plugin_dir_path( __FILE__ ).'views/index.php';
	}

	/**
	 *  Return a simple JSON response to know if Mountee is working ok
	*/
	public function get_status()
	{
		$status = 'ok';

		if (!$this->theme_helper->test_theme_folder_read())
		{
			$status = 'disabled';
			$message = 'Your theme folder '.$this->theme_helper->theme_folder_name.' is not readable.';
		}
		else if (!$this->theme_helper->test_theme_folder_write())
		{
			$status = 'disabled';
			$message = 'Your theme folder '.$this->theme_helper->theme_folder_name.' is not writable.';
		}
		else if (!$this->theme_helper->test_theme_inner_files_read())
		{
			$status = 'disabled';
			$message = 'One or several of your theme files in '.$this->theme_helper->theme_folder_name.' are not readable.';
		}
		else if (!$this->theme_helper->test_theme_inner_files_write())
		{
			$status = 'disabled';
			$message = 'One or several of your theme files in '.$this->theme_helper->theme_folder_name.' are not writable.';
		}

		// Return the major version number (avoid leaking wp version number)
		$wp_version = get_bloginfo('version');
		$expl = explode('.', $wp_version);

		if ($this->mountee_lib->check_license())
		{
			$license_ok = TRUE;
		}
		else
		{
			$license_ok = FALSE;
		}

		$response_data = array(
			'cms'			=> 'Wordpress',
			'cms_version'	=> $expl[0],
			'addon_version'	=> MOUNTEE_3_VERSION,
			'status'		=> $status,
			'theme'			=> get_stylesheet(),
			'license'		=> $license_ok
		);

		if (isset($message))
		{
			$response_data['message'] = $message;
		}

		return $response_data;
	}

	public function login()
	{
		$user = wp_signon(array('user_login' => sanitize_text_field($_POST['username']), 'user_password' => sanitize_text_field($_POST['password'])));

		if (is_wp_error($user))
		{
			$response = new WP_REST_Response( array(
				'logged_in'	=> FALSE,
				'message'	=> 'Wrong login or password'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		wp_set_current_user( $user->ID );

		$response = new WP_REST_Response( array(
			'logged_in'		=> TRUE,
			'permissions'	=> current_user_can('edit_themes'),
		) );
		return $response;
	}

	public function logout()
	{
		// That should send the client that we need to clear the auth cookies
		wp_logout();
		wp_set_current_user(0);

		$response = new WP_REST_Response( array(
			"logged_in"	=> is_user_logged_in()
		) );
		return $response;
	}

	/**
	 * List folders/files of the current theme
	 */
	public function list_all()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		$folders = $this->theme_helper->get_theme_data();

		if (!$this->mountee_lib->check_license())
		{
			$folders[0]['files'][] = Mountee_lib::get_license_dummy_file();
			// return array('folders' => $folders, 'files' => array(Mountee_lib::get_license_dummy_file()));
		}

		return array('folders' => $folders);
	}

	public function create_folder()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_name', $_POST))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group name received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_name = sanitize_text_field($_POST['template_group_name']);

		// Always have a template group id, minimum is the theme folder itself
		if (!array_key_exists('template_group_id', $_POST))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if ($this->theme_helper->theme_folder_exists($template_group_name, $template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'A folder with that name already exists'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}
		else
		{
			$result = $this->theme_helper->create_folder($template_group_name, $template_group_id);
			if ($result != FALSE)
			{
				return array(
					'success'			=> TRUE,
					'message'			=> 'The folder has been created',
					'template_group'	=> $result
				);
			}
			else
			{
				// Stop here
				$response = new WP_REST_Response( array(
					'success'   => FALSE,
					'message'   => 'Tried to create the new folder but it failed.'
				) );
				$response->set_status( 500 ); // Internal Server Error
				return $response;
			}
		}

		// It shouldn't be possible to get to here...
	}

	/**
	 * Rename a folder
	 */
	public function rename_folder()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('template_group_name', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group name received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_name = sanitize_text_field($_POST['template_group_name']);

		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$result = $this->theme_helper->rename_folder($template_group_id, $template_group_name);
		if ($result != FALSE)
		{
			$response = new WP_REST_Response( array(
				'success'			=> TRUE,
				'message'			=> 'The folder has been renamed',
				'template_group'	=> $result
			));
		}
		else
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Tried to rename the folder but it failed.'
			) );
			$response->set_status( 500 ); // Internal Server Error
		}
		return $response;
	}

	/**
	 * Move a folder
	 */
	public function move_folder()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}
		// Current folder to move
		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('to_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No to group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}
		// Parent folder the current folder will be moved in
		$to_group_id = sanitize_text_field($_POST['to_group_id']);

		// Check that the current folder exists
		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		// Check that the destination folder to move the folder into exists
		if (!$this->theme_helper->theme_folder_exists($to_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find the destination folder'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		// Check that a folder with that name doesn't exist in the new parent folder
		$folder_name = basename($template_group_id);
		if ($this->theme_helper->theme_folder_exists($folder_name, $to_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'A folder with that name already exists in the destination folder'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$result = $this->theme_helper->move_folder($template_group_id, $to_group_id);
		if ($result != FALSE)
		{
			$response = new WP_REST_Response( array(
				'success'			=> TRUE,
				'message'			=> 'The folder has been moved',
				'template_group'	=> $result
			));
		}
		else
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Tried to rename the folder but it failed.'
			) );
			$response->set_status( 500 ); // Internal Server Error
		}
		return $response;
	}

	/**
	 * Delete a folder
	 */
	public function delete_folder()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if ($this->theme_helper->delete_folder($template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => TRUE,
				'message'   => 'Folder has been deleted'
			) );
		}
		else
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Something went wrong when deleting the folder'
			) );
			$response->set_status( 500 ); // Bad Request Error
		}
		return $response;
	}

	public function create_file()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('template_name', $_POST))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template name received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_name = sanitize_text_field($_POST['template_name']);

		// template_group_id is actually the folder name/path
		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if ($this->theme_helper->theme_file_exists($template_name, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'A file with that name already exists'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_data = '';
		if (array_key_exists('template_data', $_POST))
		{
			$template_data = base64_decode($_POST['template_data']);
		}

		$result = $this->theme_helper->create_file($template_name, $template_group_id, $template_data);
		if ($result != FALSE)
		{
			return array(
				'success'	=> TRUE,
				'message'	=> 'The file has been created',
				'template'	=> $result
			);
		}
		else
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Tried to create the new file but it failed.'
			) );
			$response->set_status( 500 ); // Internal Server Error
			return $response;
		}
	}

	public function get_file()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('template_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_id = sanitize_text_field($_POST['template_id']);

		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_file_exists($template_id, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a file with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$result = $this->theme_helper->get_file($template_id, $template_group_id);

		$response = new WP_REST_Response( array(
			'template'	=> $result
		));
		return $response;
	}

	public function rename_file()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('template_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_id = sanitize_text_field($_POST['template_id']);

		if (!array_key_exists('template_name', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template name received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_name = sanitize_text_field($_POST['template_name']);

		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_file_exists($template_id, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a file with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$result = $this->theme_helper->rename_file($template_id, $template_name, $template_group_id);
		if ($result != FALSE)
		{
			$response = new WP_REST_Response( array(
				'success'	=> TRUE,
				'message'	=> 'The file has been renamed',
				'template'	=> $result
			));
		}
		else
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Tried to rename the file but it failed.'
			) );
			$response->set_status( 500 ); // Internal Server Error
		}
		return $response;
	}

	public function move_file()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('from_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No from group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id_from = sanitize_text_field($_POST['from_group_id']);

		if (!array_key_exists('to_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No to group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id_to = sanitize_text_field($_POST['to_group_id']);

		if (!array_key_exists('template_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_id = sanitize_text_field($_POST['template_id']);

		if ($template_group_id_from == $template_group_id_to)
		{
			// This is a rename and not a move
			$_POST['template_group_id'] = $template_group_id_from;
			return $this->rename_file();
		}

		if (!$this->theme_helper->theme_folder_exists($template_group_id_from))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find the from folder'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_folder_exists($template_group_id_to))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find the to folder'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_file_exists($template_id, $template_group_id_from))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a file with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if ($this->theme_helper->theme_file_exists($template_id, $template_group_id_to))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'A file with that name already exists in that folder'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$result = $this->theme_helper->move_file($template_id, $template_group_id_from, $template_group_id_to);
		if ($result)
		{
			$response = new WP_REST_Response( array(
				'success'   => TRUE,
				'message'   => 'The file has been moved',
				'template'	=> $result
			) );
		}
		else
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Tried to move the file but it failed'
			) );
			$response->set_status( 500 ); // Bad Request Error
		}
		return $response;
	}

	public function update_file()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('template_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template id received'
			) );
			$response->set_status( 400 ); // Internal Server Error
			return $response;
		}

		$template_id = sanitize_text_field($_POST['template_id']);

		if (!array_key_exists('template_data', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template data received'
			) );
			$response->set_status( 400 ); // Internal Server Error
			return $response;
		}

		$template_data = base64_decode($_POST['template_data']);

		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_file_exists($template_id, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a file with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_file_is_writable($template_id, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'The file isn\'t writable'
			) );
			$response->set_status( 500 ); // Bad Request Error
			return $response;
		}

		$result = $this->theme_helper->update_file($template_group_id, $template_id, $template_data);
		if ($result == FALSE)
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Tried to write in the file but if failed'
			) );
			$response->set_status( 400 ); // Bad Request Error
		}
		else
		{
			$response = new WP_REST_Response(array(
				'success'	=> TRUE,
				'message'	=> 'The file has been saved',
				'template'	=> $result
			));
		}

		return $response;
	}

	public function delete_file()
	{
		$auth_result = $this->_init_check();
		if ( !is_bool($auth_result) && get_class($auth_result) == 'WP_REST_Response')
		{
			return $auth_result;
		}

		if (!array_key_exists('template_group_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template group id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_group_id = sanitize_text_field($_POST['template_group_id']);

		if (!array_key_exists('template_id', $_POST))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'No template id received'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		$template_id = sanitize_text_field($_POST['template_id']);

		if (!$this->theme_helper->theme_folder_exists($template_group_id))
		{
			// Stop here
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a folder with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if (!$this->theme_helper->theme_file_exists($template_id, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Can\'t find a file with that name'
			) );
			$response->set_status( 400 ); // Bad Request Error
			return $response;
		}

		if ($this->theme_helper->delete_file($template_id, $template_group_id))
		{
			$response = new WP_REST_Response( array(
				'success'   => TRUE,
				'message'   => 'File has been deleted'
			) );
		}
		else
		{
			$response = new WP_REST_Response( array(
				'success'   => FALSE,
				'message'   => 'Something went wrong when deleting the file'
			) );
			$response->set_status( 500 ); // Bad Request Error
		}
		return $response;
	}

	/**
	 * Check if current user is properly logged in and has the right rights
	 */
	private function _init_check()
	{
		// Check the auth cookie and loggin the user properly if needed
		if (($userId = wp_validate_logged_in_cookie(FALSE)) !== FALSE)
		{
			wp_set_current_user( $userId );
		}

		if (!is_user_logged_in())
		{
			$response = new WP_REST_Response( array(
				'logged_in' => FALSE,
				'message' => 'You need to be logged in',
			) );
			$response->set_status( 401 ); // Unauthorized
			return $response;
		}

		if ( !current_user_can('edit_themes') )
		{
			$response = new WP_REST_Response( array(
				'logged_in' => FALSE,
				'permissions' => FALSE,
				'message' => 'You don\'t have sufficient permissions',
			) );
			$response->set_status( 401 ); // Unauthorized
			return $response;
		}

		if (array_key_exists('template_id', $_POST) && $_POST['template_id'] === Mountee_lib::$dum_file_id)
		{
			// Do not do anything with this file
			$response = new WP_REST_Response( array(
				'success' => false
			) );
			return $response;
		}

		return TRUE;
	}
}