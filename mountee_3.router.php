<?php

class Mountee_3_Router
{

	protected $namespace;
	protected $controller;

	public function __construct($controller, $namespace = NULL)
	{
		if ($namespace == NULL)
		{
			$this->namespace = 'mountee_3';
		}
		else
		{
			$this->namespace = $namespace;
		}

		$this->controller = $controller;
	}

	/**
	 *	Register all REST routes (API endpoints) needed for Mountee
	 */
	public function register_routes()
	{
		$controller = $this->controller;
		$namespace = $this->namespace;

		// Register routes only when the rest api is ready
		add_action( 'rest_api_init', function () use ($namespace, $controller) {
			register_rest_route($namespace, '/status', array(
				'methods'	=> 'GET',
				'callback'	=> array($controller, 'get_status')
			));

			register_rest_route($namespace, '/login', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'login')
			));

			register_rest_route($namespace, '/logout', array(
				'methods'	=> 'GET',
				'callback'	=> array($controller, 'logout')
			));

			register_rest_route($namespace, '/list_all', array(
				'methods'	=> 'GET',
				'callback'	=> array($controller, 'list_all')
			));

			register_rest_route($namespace, '/create_template_group', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'create_folder')
			));

			register_rest_route($namespace, '/rename_template_group', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'rename_folder')
			));

			register_rest_route($namespace, '/move_template_group', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'move_folder')
			));

			register_rest_route($namespace, '/delete_template_group', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'delete_folder')
			));

			register_rest_route($namespace, '/create_template', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'create_file')
			));

			register_rest_route($namespace, '/get_template', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'get_file')
			));

			register_rest_route($namespace, '/rename_template', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'rename_file')
			));

			register_rest_route($namespace, '/move_template', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'move_file')
			));

			register_rest_route($namespace, '/update_template_data', array(
				'methods'   => 'POST',
				'callback'  => array($controller, 'update_file')
			));

			register_rest_route($namespace, '/delete_template', array(
				'methods'	=> 'POST',
				'callback'	=> array($controller, 'delete_file')
			));

			// TODO: add more
		});
		
	}
}