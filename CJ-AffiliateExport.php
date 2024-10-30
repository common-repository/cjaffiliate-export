<?php
/**
 * Plugin Name: CJAffiliate Export plugin 
 * Plugin URI: https://wordpress.org/plugins/cjaffiliate-export/
 * Description:  Export product in CJAffiliate system
 * Version: 1.0
 * Author: Cimpleo
 * Author URI: http://cimpleo.com
 * Requires at least: 4.4
 * Tested up to: 4.7
 *
 *
 * @package cj_affiliate_plugin
 * @author Cimpleo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
final class cjaffiliate_class_plugin {

	/**
	 * Plugin version
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * Plugin __construct 
	 */
	public function __construct() {
		$this->init_hooks();
		$this->includes();
		$this->define_constants();
	}

	/**
	 * Hook into actions and filters.
	 *
	 */
	private function init_hooks() {
	// Plugin actions
		register_activation_hook( __FILE__, array( $this, 'install_plugin' ) );
		register_deactivation_hook( __FILE__,  array( $this, 'deactivate_plugin' ) );	
	// Actions
		add_action( 'admin_menu', array( $this, 'add_plugin_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_scripts' ) );

	}

	/**
	 * Include required core files used in admin.
	 */
	public function includes() {
	// Class
		include_once( $this->plugin_path().'/include/class_productExport.php' );

	// Settings options
		include_once( $this->plugin_path().'/include/Cj_SettingFields.php' );
	}

	/**
	 * [installPlugin description]
	 * 
	 */
	public static function install_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	// Create Folder
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/CJ-Affilate/';
		wp_mkdir_p( $upload_dir );
	// Add default settings
		$exportOptions = array( 'cid' => '', 'subid' => '', 'processtype' => 'update', 'aid' => '', 'cron_activate' => 0 );
		$status = update_option( 'CJAffiliate_plugin_export', $exportOptions );

		$exportTransferOptions = array( 'ftp_host' => '', 'ftp_port' => '', 'ftp_login' => '', 'ftp_pwd' => '', 'ftp_port'	=> '' );
		update_option( 'CJAffiliate_plugin_exportTransfer', $exportTransferOptions );

	// WP-Cron. Clear event
		wp_clear_scheduled_hook( 'cron_cj_export' );
	}

	/**
	 * [deactivatePlugin description]
	 * 
	 */
	public static function deactivate_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		delete_option( 'CJAffiliate_plugin_export' );
		delete_option( 'CJAffiliate_plugin_exportTransfer' );

	// Cron clear event
		wp_clear_scheduled_hook( 'cron_cj_export' );
	}

	/**
	 * [define_constants description]
	 * 
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();

		$this->define( 'CJAFFILIATE_VERSION', $this->version );
		$this->define( 'CJAFFILIATE_UPLOADS', $upload_dir['basedir'] . '/CJ-Affilate/' );
		$this->define( 'CJAFFILIATE_UPLOADS_URL', $upload_dir['baseurl'] . '/CJ-Affilate' );
	}


	/**
	 * [admin_styles_scripts description]
	 * 
	 */
	public function admin_styles_scripts() {
	// Custom style
		wp_enqueue_style( 'CJ-style.css', $this->plugin_url('/assets/main.css') );
	// Script
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery.form', $this->plugin_url('/assets/jquery.form.min.js'), array( 'jquery' ) );
		wp_enqueue_script( 'CJ-main.js', $this->plugin_url('/assets/main.js'), array( 'jquery.form' ) );
	}

	/**
	 * [add_plugin_menu_page description]
	 */
	public function add_plugin_menu_page() {
		add_submenu_page( 'tools.php', 'СJ Export Product', 'CJ export', 'manage_options', 'cjexport',  array( $this, 'render_page_export' ) );
	}

	/**
	 * [render_page_settings_export description]
	 * @return [type] [description]
	 */
	public function render_page_export() {
		include_once( $this->plugin_path().'/include/page-export.php' );
	}


	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url( $path ) {
		return untrailingslashit( plugins_url( $path, __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
	

}
new cjaffiliate_class_plugin();	
