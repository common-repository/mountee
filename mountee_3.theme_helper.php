<?php

class Mountee_3_Theme_Helper
{

	var $theme = NULL;
	var $theme_absolute_path = NULL;
	var $theme_parent_absolute_path = NULL;
	var $theme_folder_name = NULL;

	public function __construct()
	{
		$this->theme_absolute_path = get_stylesheet_directory();
		$this->theme_parent_absolute_path = dirname($this->theme_absolute_path);
		$this->theme_folder_name = basename($this->theme_absolute_path);
	}

	public function get_theme_data()
	{
		$folders[] = $this->_process_folder_and_format($this->theme_absolute_path);

		return $folders;
	}

	/**
	 * Simple/rough test for folder and file accessiblity
	 * Testing readable access on 1st children folders
	 * Testing writable access on 1st children files
	 */
	public function test_theme_files_for_operations()
	{
		if (is_readable($this->theme_absolute_path))
		{
			$folders_files = scandir($this->theme_absolute_path);
			foreach ($folders_files as $folder_file)
			{
				if ($folder_file != '.' && $folder_file != '..')
				{
					$folder_file_abs_path = $this->theme_absolute_path.DIRECTORY_SEPARATOR.$folder_file;
					if (is_dir($folder_file_abs_path) && !is_readable($folder_file_abs_path))
					{
						return FALSE;
					}
					else if (is_file($folder_file_abs_path) && !is_writable($folder_file_abs_path))
					{
						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}

	/**
	 * Test if the theme folder is readable
	 */
	public function test_theme_folder_read()
	{
		return is_readable($this->theme_absolute_path);
	}

	/**
	 * Test if the theme folder is writable
	 */
	public function test_theme_folder_write()
	{
		return is_writable($this->theme_absolute_path);
	}

	/**
	 * Test files in the theme folder for readability (only in theme folder, not subfolders)
	 */
	public function test_theme_inner_files_read()
	{
		$folders_files = scandir($this->theme_absolute_path);
		foreach ($folders_files as $folder_file)
		{
			$folder_file_abs_path = $this->theme_absolute_path.DIRECTORY_SEPARATOR.$folder_file;
			if ($folder_file != '.' 
				&& $folder_file != '..' 
				&& is_file($folder_file_abs_path)
			)
			{
				if (!is_readable($folder_file_abs_path))
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Test files in the theme folder for writability (only in theme folder, not subfolders)
	 */
	public function test_theme_inner_files_write()
	{
		$folders_files = scandir($this->theme_absolute_path);
		foreach ($folders_files as $folder_file)
		{
			$folder_file_abs_path = $this->theme_absolute_path.DIRECTORY_SEPARATOR.$folder_file;
			if ($folder_file != '.' 
				&& $folder_file != '..' 
				&& is_file($folder_file_abs_path)
			)
			{
				if (!is_writable($folder_file_abs_path))
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Test if a folder with that name exists
	*/
	public function theme_folder_exists($folder, $folder_parent = '')
	{
		if ($folder_parent != '')
		{
			$folder_abs_path = $this->_get_folder_absolute_path($folder_parent.DIRECTORY_SEPARATOR.$folder);
		}
		else
		{
			$folder_abs_path = $this->_get_folder_absolute_path($folder);
		}

		return is_dir($folder_abs_path);
	}

	public function theme_folder_is_readable($folder)
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);

		return is_readable($folder_abs_path);
	}

	/**
	 * Test if a file exists in the given folder
	 */
	public function theme_file_exists($file_name, $folder)
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);

		return is_file($folder_abs_path.DIRECTORY_SEPARATOR.$file_name);
	}

	/**
	 * Test if we can write in the file
	 */
	public function theme_file_is_writable($file_name, $folder)
	{

		$folder_absolute_path = $this->_get_folder_absolute_path($folder);
		$file_absolute_path = $folder_absolute_path.DIRECTORY_SEPARATOR.$file_name;

		return is_writable($file_absolute_path);
	}

	/**
	 * Creates a folder at theme root
	 */
	public function create_folder($folder, $folder_parent = '')
	{
		if ($folder_parent == '')
		{
			$folder_abs_path = $this->_get_folder_absolute_path($folder);
		}
		else
		{
			$folder_abs_path = $this->_get_folder_absolute_path($folder_parent.DIRECTORY_SEPARATOR.$folder);
		}
		
		if  (mkdir($folder_abs_path))
		{
			// return $this->_format_folder($folder_name, array());
			return $this->_process_folder_and_format($folder_abs_path);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Rename an existing folder
	 * @param $folder 	string 	relative path to the folder
	 * @param $new_folder_name 	 string 	The new name of the folder
	 */
	public function rename_folder($folder, $new_folder_name)
	{
		$old_folder_abs_path = $this->_get_folder_absolute_path($folder);

		// The $folder can be a relative path, handle that case
		if (strpos($folder, DIRECTORY_SEPARATOR) !== FALSE)
		{
			$parent_folder = dirname($folder);
			$parent_folder_abs_path = $this->_get_folder_absolute_path($parent_folder);
			$new_folder_abs_path = $parent_folder_abs_path.DIRECTORY_SEPARATOR.$new_folder_name;
		}
		else
		{
			$new_folder_abs_path = $this->_get_folder_absolute_path($new_folder_name);
		}

		if (rename($old_folder_abs_path, $new_folder_abs_path))
		{
			return $this->_process_folder_and_format($new_folder_abs_path);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Move a folder
	 * @param $folder 	string 	 relative path of the folder to move
	 * @param $new_parent_folder 	string 	 relative path to the new parent folder to move the folder in
	 */
	public function move_folder($folder, $new_parent_folder)
	{
		$old_folder_abs_path = $this->_get_folder_absolute_path($folder);
		$new_parent_folder_abs_path = $this->_get_folder_absolute_path($new_parent_folder);

		// Get the folder name out of its relative path
		if (strpos($folder, DIRECTORY_SEPARATOR) !== FALSE)
		{
			$folder_name = basename($folder);
			$new_folder_abs_path = $new_parent_folder_abs_path.DIRECTORY_SEPARATOR.$folder_name;
		}
		else
		{
			$new_folder_abs_path = $new_parent_folder_abs_path.DIRECTORY_SEPARATOR.$folder;
		}

		if (rename($old_folder_abs_path, $new_folder_abs_path))
		{
			return $this->_process_folder_and_format($new_folder_abs_path);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Delete a folder with everything that's inside
	 */
	public function delete_folder($folder)
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);

		// Need to delete all files from the folder before deleting it
		$result = $this->_delete_files_from_folder($folder_abs_path);

		return rmdir($folder_abs_path);
	}

	/**
	 * Creates an empty file in the given folder
	 */
	public function create_file($file_name, $folder, $file_data = '')
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);
		$file_absolute_path = $folder_abs_path.DIRECTORY_SEPARATOR.$file_name;

		if (touch($file_absolute_path))
		{
			if ($file_data != '')
			{
				$result = file_put_contents($file_absolute_path, $file_data);
				if ($result === FALSE)
				{
					return FALSE;
				}
			}
			return $this->_format_file($file_name, $file_absolute_path);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Get a single file info and content
	 */
	public function get_file($file_name, $folder)
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);

		$file_absolute_path = $folder_abs_path.DIRECTORY_SEPARATOR.$file_name;

		return $this->_format_file($file_name, $file_absolute_path);
	}

	/**
	 * Rename a file
	 */
	public function rename_file($file_name, $new_file_name, $folder)
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);

		$file_old = $folder_abs_path.DIRECTORY_SEPARATOR.$file_name;
		$file_new = $folder_abs_path.DIRECTORY_SEPARATOR.$new_file_name;

		if (rename($file_old, $file_new))
		{
			return $this->_format_file($new_file_name, $file_new);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Update the content of a file
	 */
	public function update_file($folder, $file_name, $file_data)
	{
		$folder_absolute_path = $this->_get_folder_absolute_path($folder);
		$file_absolute_path = $folder_absolute_path.DIRECTORY_SEPARATOR.$file_name;

		$result = file_put_contents($file_absolute_path, $file_data);
		if ($result === FALSE)
		{
			return FALSE;
		}

		return $this->_format_file($file_name, $file_absolute_path);
	}

	/**
	 * Move a file from a folder to another
	 */
	public function move_file($file_name, $folder_from, $folder_to)
	{
		$folder_from_absolute_path = $this->_get_folder_absolute_path($folder_from);
		$folder_to_absolute_path = $this->_get_folder_absolute_path($folder_to);

		if (rename($folder_from_absolute_path.DIRECTORY_SEPARATOR.$file_name, $folder_to_absolute_path.DIRECTORY_SEPARATOR.$file_name))
		{
			return $this->_format_file($file_name, $folder_to_absolute_path.DIRECTORY_SEPARATOR.$file_name);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Delete a file
	 */
	public function delete_file($file_name, $folder)
	{
		$folder_abs_path = $this->_get_folder_absolute_path($folder);

		$file = $folder_abs_path.DIRECTORY_SEPARATOR.$file_name;

		return unlink($file);
	}

	private function _format_folder($folder, $files, $subfolders = array())
	{
		$folder_data = array(
			'id'            => $folder,
			'name'          => $folder,
			'is_special'    => $folder == $this->theme_folder_name,
			'files'         => array(),
			'subfolders'	=> $subfolders
		);

		foreach ($files as $file_name => $file_path)
		{
			$folder_data['files'][] = $this->_format_file($file_name, $file_path);
		}
		return $folder_data;
	}

	private function _format_file($file_name, $file_absolute_path)
	{
		return array(
			'id'	=> $file_name,
			'name'	=> $file_name,
			// 'type'	=> $this->_get_file_type($file_absolute_path),
			'type'	=> '',
			'data'	=> base64_encode(file_get_contents($file_absolute_path))
		);
	}

	/**
	 * Returns an array correctly formatted containing files, folders and sub-folders
	 */
	private function _process_folder_and_format($folder_absolute_path)
	{
		$folders_files = scandir($folder_absolute_path);
		$folder_name = basename($folder_absolute_path);
		$files = array();
		$subfolders = array();
		foreach ($folders_files as $folder_file)
		{
			if ($folder_file == '.' || $folder_file == '..')
			{
				continue;
			}

			$folder_file_abs_path = $folder_absolute_path.DIRECTORY_SEPARATOR.$folder_file;
			if (is_file($folder_file_abs_path))
			{
				$files[] = $this->_format_file($folder_file, $folder_file_abs_path);
			}
			else if (is_dir($folder_file_abs_path))
			{
				$subfolders[] = $this->_process_folder_and_format($folder_file_abs_path);
			}
		}

		$folder_data = array(
			'id'            => $this->_get_folder_relative_path($folder_absolute_path),
			'name'          => $folder_name,
			'is_special'    => $folder_name == $this->theme_folder_name,
			'files'         => $files,
			'subfolders'	=> $subfolders
		);

		return $folder_data;
	}

	private function _delete_files_from_folder($folder_absolute_path)
	{
		$result = TRUE;
		$folders_files = scandir($folder_absolute_path);
		foreach ($folders_files as $folder_file)
		{
			if ($folder_file == '.' || $folder_file == '..')
			{

			}
			else
			{
				if (is_file($folder_absolute_path.DIRECTORY_SEPARATOR.$folder_file))
				{
					$del_result = unlink($folder_absolute_path.DIRECTORY_SEPARATOR.$folder_file);
					$result = ($result && $del_result);
				}
				else if (is_dir($folder_absolute_path.DIRECTORY_SEPARATOR.$folder_file))
				{
					$del_result = $this->_delete_files_from_folder($folder_absolute_path.DIRECTORY_SEPARATOR.$folder_file);
					$result = ($result && $del_result);
				}
			}
		}
		return $result;
	}

	/**
	 * Get the absolute path of a theme folder, from its relative path
	 * Relative path always begin with the theme folder itself
	 */
	private function _get_folder_absolute_path($folder)
	{
		return $this->theme_parent_absolute_path.DIRECTORY_SEPARATOR.$folder;
	}

	/**
	 * Process an absolute path and return the folder relative path to the theme
	 * Relative path always begin with the theme folder itself
	 */
	private function _get_folder_relative_path($folder_absolute_path)
	{
		return str_replace($this->theme_parent_absolute_path.DIRECTORY_SEPARATOR, "", $folder_absolute_path);
	}
}