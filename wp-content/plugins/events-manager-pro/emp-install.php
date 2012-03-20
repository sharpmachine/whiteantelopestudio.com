<?php

function emp_install() {
	$old_version = get_option('em_pro_version');
	if( EMP_VERSION > $old_version || $old_version == '' ){
	 	// Creates the tables + options if necessary
	 	emp_create_transactions_table();
		emp_create_coupons_table(); 
		emp_add_options();
		
		//Upate Version	
	  	update_option('em_pro_version', EMP_VERSION);
	}
}

/**
 * Magic function that takes a table name and cleans all non-unique keys not present in the $clean_keys array. if no array is supplied, all but the primary key is removed.
 * @param string $table_name
 * @param array $clean_keys
 */
function emp_sort_out_table_nu_keys($table_name, $clean_keys = array()){
	global $wpdb;
	//sort out the keys
	$new_keys = $clean_keys;
	$table_key_changes = array();
	$table_keys = $wpdb->get_results("SHOW KEYS FROM $table_name WHERE Key_name != 'PRIMARY'", ARRAY_A);
	foreach($table_keys as $table_key_row){
		if( !in_array($table_key_row['Key_name'], $clean_keys) ){
			$table_key_changes[] = "ALTER TABLE $table_name DROP INDEX ".$table_key_row['Key_name'];
		}elseif( in_array($table_key_row['Key_name'], $clean_keys) ){
			foreach($clean_keys as $key => $clean_key){
				if($table_key_row['Key_name'] == $clean_key){
					unset($new_keys[$key]);
				}
			}
		}
	}
	//delete duplicates
	foreach($table_key_changes as $sql){
		$wpdb->query($sql);
	}
	//add new keys
	foreach($new_keys as $key){
		$wpdb->query("ALTER TABLE $table_name ADD INDEX ($key)");
	}
}

function emp_create_transactions_table() {
	global  $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
	if( get_site_option('dbem_ms_global_table') ){
		$prefix = $wpdb->base_prefix;
	}else{
		$prefix = $wpdb->prefix;
	}
	$table_name = $prefix.'em_transactions'; 
	$sql = "CREATE TABLE ".$table_name." (
		  transaction_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  booking_id bigint(20) unsigned NOT NULL DEFAULT '0',
		  transaction_gateway_id varchar(30) DEFAULT NULL,
		  transaction_payment_type varchar(20) DEFAULT NULL,
		  transaction_timestamp datetime NOT NULL,
		  transaction_total_amount decimal(8,2) DEFAULT NULL,
		  transaction_currency varchar(35) DEFAULT NULL,
		  transaction_status varchar(35) DEFAULT NULL,
		  transaction_duedate date DEFAULT NULL,
		  transaction_gateway varchar(50) DEFAULT NULL,
		  transaction_note text,
		  transaction_expires datetime DEFAULT NULL,
		  PRIMARY KEY  (transaction_id)
		) DEFAULT CHARSET=utf8 ;";
	
	dbDelta($sql);
	emp_sort_out_table_nu_keys($table_name,array('transaction_gateway','booking_id'));
}

function emp_create_coupons_table() {
	global  $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
	if( get_site_option('dbem_ms_global_table') ){
		$prefix = $wpdb->base_prefix;
	}else{
		$prefix = $wpdb->prefix;
	}
	$table_name = $prefix.'em_coupons'; 
	$sql = "CREATE TABLE ".$table_name." (
		  coupon_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  coupon_owner bigint(20) unsigned NOT NULL,
		  blog_id bigint(20) unsigned DEFAULT NULL,
		  coupon_code varchar(20) NOT NULL,
		  coupon_name text NOT NULL,
		  coupon_description text NULL,
		  coupon_max int(10) NULL,
		  coupon_start datetime DEFAULT NULL,
		  coupon_end datetime DEFAULT NULL,
		  coupon_type varchar(20) DEFAULT NULL,
		  coupon_discount decimal(8,2) NOT NULL,
		  coupon_eventwide bool NOT NULL DEFAULT 0,
		  coupon_sitewide bool NOT NULL DEFAULT 0,
		  coupon_private bool NOT NULL DEFAULT 0,
		  PRIMARY KEY  (coupon_id)
		) DEFAULT CHARSET=utf8 ;";
	dbDelta($sql);
	$array = array('coupon_owner','coupon_code');
	if( is_multisite() ) $array[] = 'blog_id'; //only add index if needed
	emp_sort_out_table_nu_keys($table_name,$array);
}

function emp_add_options() {
	add_option('em_pro_data', array());
	
	//Booking form stuff
	add_option('em_booking_form_custom', 1); //we're installing with same values as in the normal booking form
	add_option('em_booking_form_attendee_custom', 0);
	add_option('em_booking_form_error_required', __('Please fill in the field: %s','em-pro'));
	add_option('em_booking_form_fields', array (
	  'name' => array ( 'booking_form_label' => __('Name','dbem'), 'booking_form_type' => 'name', 'booking_form_fieldid'=>'user_name' ),
	  'user_email' => array ( 'booking_form_label' => __('Email','dbem'), 'booking_form_type' => 'user_email', 'booking_form_fieldid'=>'user_email' ),
	  'dbem_phone' => array ( 'booking_form_label' => __('Phone','dbem'), 'booking_form_type' => 'dbem_phone', 'booking_form_fieldid'=>'dbem_phone' ),
	  'textarea' => array ( 'booking_form_label' => __('Comment','dbem'), 'booking_form_type' => 'textarea', 'booking_form_fieldid'=>'booking_comment' ),
	));
	
	//Gateway Stuff
	add_option('dbem_gateway_use_buttons', 0);
	//paypal
	add_option('em_paypal_option_name', __('PayPal', 'em-pro'));
	add_option('em_paypal_form', '<img src="'.plugins_url('events-manager-pro/includes/images/paypal/paypal_info.png','events-manager').'" />');
	add_option('em_paypal_booking_feedback', __('Please wait whilst you are redirected to PayPal to proceed with payment.','em-pro'));
	add_option('em_paypal_booking_feedback_free', __('Booking successful.', 'dbem'));
	add_option('em_paypal_button', 'http://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif');
	add_option('em_paypal_booking_feedback_thanks', __('Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you along with a seperate email containing account details to access your booking information on this site. You may log into your account at www.paypal.com to view details of this transaction.', 'em-pro'));
	//offline
	add_option('em_offline_option_name', __('Pay Offline', 'em-pro'));
	add_option('em_offline_booking_feedback', __('Booking successful.', 'dbem'));
	add_option('em_offline_button', __('Pay Offline', 'em-pro'));
	//authorize.net
	add_option('em_authorize_aim_option_name', __('Credit Card', 'em-pro'));
	add_option('em_authorize_aim_booking_feedback', __('Booking successful.', 'dbem'));
	add_option('em_authorize_aim_booking_feedback_free', __('Booking successful. You have not been charged for this booking.', 'dbem'));
	
	//Version updates
	if( get_option('em_pro_version') ){ //upgrade, so make buttons the default option
		if( get_option('em_pro_version') < 1.6 ){
			update_option('dbem_gateway_use_buttons', 1);
			if( get_option('em_offline_button_text') && !get_option('em_offline_button') ){
				update_option('em_offline_button',get_option('em_offline_button_text')); //merge offline quick pay button option into one
			}
			if( get_option('em_paypal_button_text') && !get_option('em_paypal_button') ){
				update_option('em_paypal_button',get_option('em_paypal_button_text')); //merge offline quick pay button option into one
			}
		}
		if( get_option('em_pro_version') == '1.3' ){ //fix for v1.3
			$booking_form_fields = get_option('em_booking_form_fields');
			foreach($booking_form_fields as $key => $booking_form_field){
				if($booking_form_field['booking_form_type'] == 'email' && $booking_form_field['booking_form_label'] == __('Email','dbem')){
					$booking_form_fields[$key]['booking_form_type'] = 'user_email';
					update_option('em_booking_form_fields',$booking_form_fields);
				}
			}
		}
	}
}     
?>