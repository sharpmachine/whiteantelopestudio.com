<?php
/*
Plugin Name: Events Manager Pro
Plugin URI: http://wp-events-plugin.com
Description: Supercharge the Events Manager free plugin with extra feature to make your events even more successful!
Author: NetWebLogic
Author URI: http://wp-events-plugin.com/
Version: 2.0.2

Copyright (C) 2011 NetWebLogic LLC
*/
define('EMP_VERSION', 2.0);
define('EM_MIN_VERSION', 5.071);
define('EMP_SLUG', plugin_basename( __FILE__ ));
class EM_Pro {

	/**
	 * em_pro_data option
	 * @var array
	 */
	var $data;

	/**
	 * Class initialization
	 */
	function EM_Pro() {
		global $wpdb;
		//Set when to run the plugin : after EM is loaded.
		add_action( 'plugins_loaded', array(&$this,'init') );
		//Define some tables
		if( defined('EM_MS_GLOBAL') ){
			$prefix = $wpdb->base_prefix;
		}else{
			$prefix = $wpdb->prefix;
		}
		define('EM_TRANSACTIONS_TABLE', $prefix.'em_transactions'); //TABLE NAME
		define('EM_COUPONS_TABLE', $prefix.'em_coupons'); //TABLE NAME
	}

	/**
	 * Actions to take upon initial action hook
	 */
	function init(){
		//check that EM is installed
		if(!defined('EM_VERSION')){
			add_action('admin_notices',array(&$this,'em_install_warning'));
			add_action('network_admin_notices',array(&$this,'em_install_warning'));
			return false; //don't load EMP further
		}elseif( EM_MIN_VERSION > EM_VERSION ){
			//check that EM is up to date
			add_action('admin_notices',array(&$this,'em_version_warning'));
			add_action('network_admin_notices',array(&$this,'em_version_warning'));
		}
		//Upgrade/Install Routine
		if( is_admin() && current_user_can('activate_plugins') ){
			$old_version = get_option('em_pro_version');
			if( EMP_VERSION > $old_version || $old_version == '' ){
				require_once(WP_PLUGIN_DIR.'/events-manager-pro/emp-install.php');
				emp_install();
			}
		}
		//Add extra styling
		if( get_option('dbem_disable_css') ){
			add_filter('init', '');
		}
		//add-ons
		include('add-ons/gateways.php');
		include('add-ons/bookings-form.php');
		include('add-ons/coupons.php');
		add_action('wp_head', array(&$this,'wp_head'));
	}

	/**
	 * For now we'll just add style and js here, since it's so minimal
	 */
	function wp_head(){
		?>
		<style type="text/css">
		 div.em-gateway-buttons { height:50px; width: 100%; }
		 div.em-gateway-buttons .first { padding-left:0px; margin-left:0px; border-left:none; }
		 div.em-gateway-button { float:left; padding-left:20px; margin-left:20px; border-left:1px solid #777; }
		</style>
		<?php
	}

	function em_install_warning(){
		?>
		<div class="error"><p><?php _e('Please make sure you install Events Manager as well. You can search and install this plugin from your plugin installer or download it <a href="http://wordpress.org/extend/plugins/events-manager/">here</a>.','em-pro'); ?> <em><?php _e('Only admins see this message.','em-pro'); ?></em></p></div>
		<?php
	}

	function em_version_warning(){
		?>
		<div class="error"><p><?php _e('Please make sure you have the <a href="http://wordpress.org/extend/plugins/events-manager/">latest version</a> of Events Manager installed, as this may prevent Pro from functioning properly.','em-pro'); ?> <em><?php _e('Only admins see this message.','em-pro'); ?></em></p></div>
		<?php
	}

}
//Add translation
load_plugin_textdomain('em-pro', false, dirname( plugin_basename( __FILE__ ) ).'/langs');

//Include admin file if needed
if(is_admin()){
	//include_once('em-pro-admin.php');
	include_once('emp-updates.php'); //update manager
}

/* Creating the wp_events table to store event data*/
function emp_activate() {
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
}
register_activation_hook( __FILE__,'emp_activate');

// Start plugin
global $EM_Pro;
$EM_Pro = new EM_Pro();

//cron functions - ran here since functions aren't loaded, scheduling done by gateways and other modules
/**
 * Adds a schedule according to EM
 * @param array $shcehules
 * @return array
 */
function emp_cron_schedules($schedules){
	$schedules['em_minute'] = array(
		'interval' => 60,
		'display' => 'Every Minute'
	);
	return $schedules;
}
add_filter('cron_schedules','emp_cron_schedules',10,1);
?>