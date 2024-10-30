<?php

/*
Plugin Name: Mountee 3
Plugin URI: https://hellomountee.com/
Description: Mount your template files as a virtual drive in OS X
Version: 1.2.2
Author: Hop Studios
Author URI: http://www.hopstudios.com
License: commercial

*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if (!defined('MOUNTEE_3_VERSION')) {
	define('MOUNTEE_3_VERSION', '1.2.2');
}

class Mountee_3
{

	protected $router;
	protected $controller;

	public function __construct()
	{
		include_once plugin_dir_path( __FILE__ ).'/mountee_3.router.php';
		include_once plugin_dir_path( __FILE__ ).'/mountee_3.controller.php';
		include_once plugin_dir_path( __FILE__ ).'/mountee_3.theme_helper.php';
		include_once plugin_dir_path( __FILE__ ).'/lib/MLC_Api.php';
		include_once plugin_dir_path( __FILE__ ).'/lib/Mountee_lib.php';

		$this->controller = new Mountee_3_Controller();

		$this->router = new Mountee_3_Router($this->controller);
		$this->router->register_routes();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_css'));

		if (is_admin())
		{
			add_action( 'admin_init', array( $this, 'register_settings') );
		}

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link') );
	}

	public function add_admin_menu()
	{
		// Page title, Menu title, Capability, Menu slug, function
		add_options_page( 'Mountee', 'Mountee', 'edit_themes', 'mountee-main-page', array($this, 'admin_page'));
	}

	public function add_settings_link($links)
	{
		$current_user = wp_get_current_user();
		$site_url = get_site_url();
		$site_name = get_bloginfo('name');
		$mountee_url = $site_url.'/wp-json/mountee_3';
		
		$mylinks = array(
			'<a href="' . admin_url( 'options-general.php?page=mountee-main-page' ) . '">Settings</a>',
			'<a href="mountee://add_site?title='.urlencode($site_name).'&url='.urlencode($mountee_url).'&username='.urlencode($current_user->user_login).'">Quick Start</a>'
		);
		return array_merge( $links, $mylinks );
	}

	public function load_css()
	{
		wp_register_style( 'mountee-style', plugins_url('css/style.css',__FILE__) );
		wp_enqueue_style( 'mountee-style' );
	}

	public function admin_page()
	{
		return $this->controller->get_mountee_admin_page();
	}

	public function register_settings()
	{
		register_setting( 'mountee_options', 'mountee_license' );
		add_settings_section('mountee_main', 'Settings', array($this, 'plugin_section_text'), 'mountee-main-page');
		add_settings_field('mountee_license_field', 'License/Purchase Code', array($this, 'plugin_setting_string'), 'mountee-main-page', 'mountee_main');
	}

	public function plugin_section_text() {
		echo '<p>Enter the purchase code from Envato.</p>';
	}

	function plugin_setting_string() {
		$mountee_license = get_option('mountee_license');
		echo "<input id='mountee_license_field' name='mountee_license' size='40' type='text' value='{$mountee_license}' />";
	}


}

new Mountee_3();