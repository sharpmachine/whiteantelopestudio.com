<?php
/*
Plugin Name: Events Manager Pro
Plugin URI: http://wp-events-plugin.com
Description: Supercharge the Events Manager free plugin with extra feature to make your events even more successful!
Author: NetWebLogic
Author URI: http://wp-events-plugin.com/
Version: 2.2.1

Copyright (C) 2011 NetWebLogic LLC
*/
define('EMP_VERSION', 2.21);
define('EM_MIN_VERSION', 5.189);
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
	}

	/**
	 * Actions to take upon initial action hook
	 */
	function init(){
		global $wpdb;
		//Define some tables
		if( EM_MS_GLOBAL ){
			$prefix = $wpdb->base_prefix;
		}else{
			$prefix = $wpdb->prefix;
		}
		define('EM_TRANSACTIONS_TABLE', $prefix.'em_transactions'); //TABLE NAME
		define('EM_EMAIL_QUEUE_TABLE', $prefix.'em_email_queue'); //TABLE NAME
		define('EM_COUPONS_TABLE', $prefix.'em_coupons'); //TABLE NAME
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
	    if( is_admin() && current_user_can('activate_plugins') ){
			add_action('init', array($this, 'install'),2);
	    }
		//Add extra Styling/JS
		if( !get_option('dbem_disable_css') ){
			add_action('wp_head', array(&$this,'wp_head'));
			add_action('admin_head', array(&$this,'admin_head'));
		}
		add_action('init', array(&$this,'enqueue_script'), 1); //so it gets added before EM, and events handlers override
		//includes
		include('emp-forms.php'); //form editor
		//add-ons
		include('add-ons/gateways.php');
		include('add-ons/bookings-form.php');
		include('add-ons/coupons.php');
		include('add-ons/emails.php');
		include('add-ons/user-fields.php');
		//MS Specific stuff
		if( is_multisite() ){
			add_filter('em_ms_globals',array(&$this,'em_ms_globals'));
		}
	}
	
	function install(){
	    //Upgrade/Install Routine
    	$old_version = get_option('em_pro_version');
    	if( EMP_VERSION > $old_version || $old_version == '' ){
    		require_once(WP_PLUGIN_DIR.'/events-manager-pro/emp-install.php');
    		emp_install();
    	}
	}

	function em_ms_globals($globals){
		$globals[] = 'dbem_pro_api_key';
		return $globals;
	}

	function enqueue_script(){
		wp_enqueue_script('events-manager-pro', plugins_url('includes/js/events-manager-pro.js',__FILE__), array('jquery', 'jquery-ui-core','jquery-ui-widget','jquery-ui-position')); //jQuery will load as dependency
	}

	/**
	 * For now we'll just add style and js here, since it's so minimal
	 */
	function wp_head(){
		?>
		<style type="text/css">
		.em-booking-form span.form-tip { text-decoration:none; border-bottom:1px dotted #aaa; padding-bottom:2px; }
		.input-group .em-date-range input { width:100px; }
		.input-group .em-time-range input { width:80px; }
		 div.em-gateway-buttons { height:50px; width: 100%; }
		 div.em-gateway-buttons .first { padding-left:0px; margin-left:0px; border-left:none; }
		 div.em-gateway-button { float:left; padding-left:20px; margin-left:20px; border-left:1px solid #777; }
		</style>
		<?php
	}

	function admin_head(){
		?>
		<style type="text/css">
			#em-booking-form-editor form { display:inline; }
			 /* Custom Form Editor CSS */
				/* structure */
				.em-form-custom > div { max-width:810px; border:1px solid #ccc; padding:10px 0px 0px; }
				.em-form-custom .booking-custom-head { font-weight:bold; }
				.em-form-custom .booking-custom > div, .booking-custom > ul {  padding:10px; }
				.em-form-custom .booking-custom-item { clear:left; border-top:1px solid #dedede; padding-top:10px; overflow:visible; }
				/* cols/fields */
				.em-form-custom .bc-col { float:left; width:140px; text-align:left; margin:0px 20px 0px 0px; }
				.em-form-custom .bc-col-required { width:50px; text-align:center; }
				.em-form-custom .bc-col-sort { margin-left:10px; width:25px; height:25px; background:url(<?php echo plugins_url('includes/images/cross.png',__FILE__); ?>) 0px 0px no-repeat; cursor:move; }
				.em-form-custom .booking-custom-head .bc-col-sort { background:none; }
				.em-form-custom .booking-custom-types { clear:left; }
				.em-form-custom .booking-custom-types .bct-options { clear:left; margin-top:50px; }
				.em-form-custom .booking-custom-types .bct-field { clear:left; margin-top:10px; }
				/* option structure */
				.em-form-custom .bct-options { padding:0px 20px; }
				.em-form-custom .bct-field .bct-label { float:left; width:120px; }
				.em-form-custom .bct-field .bct-input { margin:0px 0px 10px 130px; }
				.em-form-custom .bct-field .bct-input input, .bct-field .bct-input textarea { display:block; }
				/* Sorting */
				.em-form-custom .booking-custom { list-style-type: none; margin: 0; padding: 0; }
				.em-form-custom .bc-highlight { height:45px; line-height:35px; border:1px solid #cdcdcd; background:#efefef; }
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
// Start plugin
global $EM_Pro;
$EM_Pro = new EM_Pro();

/* Creating the wp_events table to store event data*/
function emp_activate() {
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
}
register_activation_hook( __FILE__,'emp_activate');

/**
 * Handle MS blog deletions
 * @param int $blog_id
 */
function emp_delete_blog( $blog_id ){
	global $wpdb;
	$prefix = $wpdb->get_blog_prefix($blog_id);
	$wpdb->query('DROP TABLE '.$prefix.'em_transactions');
	$wpdb->query('DROP TABLE '.$prefix.'em_coupons');
	$wpdb->query('DROP TABLE '.$prefix.'em_email_queue');
}
add_action('delete_blog','emp_delete_blog');

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