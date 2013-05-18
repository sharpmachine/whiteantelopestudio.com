<?php

function emp_install() {
	$old_version = get_option('em_pro_version');
	if( EMP_VERSION > $old_version || $old_version == '' ){
	 	// Creates the tables + options if necessary
	 	emp_create_transactions_table();
		emp_create_coupons_table(); 
		emp_create_reminders_table();
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
	$table_name = $wpdb->prefix.'em_transactions'; 
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
	$table_name = $wpdb->prefix.'em_coupons'; 
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

function emp_create_reminders_table(){
	global  $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
    $table_name = $wpdb->prefix.'em_email_queue';
	$sql = "CREATE TABLE ".$table_name." (
		  queue_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  event_id bigint(20) unsigned DEFAULT NULL,
		  booking_id bigint(20) unsigned DEFAULT NULL,
		  email text NOT NULL,
		  subject text NOT NULL,
		  body text NOT NULL,
		  attachment text NOT NULL,
		  PRIMARY KEY  (queue_id)
		) DEFAULT CHARSET=utf8 ;";
	dbDelta($sql);
	$array = array('coupon_owner','coupon_code');
	emp_sort_out_table_nu_keys($table_name,array('event_id','booking_id'));
}

function emp_add_options() {
	global $wpdb;
	add_option('em_pro_data', array());
	//Form Stuff
	$booking_form_data = array( 'name'=> __('Default','em-pro'), 'form'=> array (
	  'name' => array ( 'label' => __('Name','dbem'), 'type' => 'name', 'fieldid'=>'user_name', 'required'=>1 ),
	  'user_email' => array ( 'label' => __('Email','dbem'), 'type' => 'user_email', 'fieldid'=>'user_email', 'required'=>1 ),
    	'dbem_address' => array ( 'label' => __('Address','dbem'), 'type' => 'dbem_address', 'fieldid'=>'dbem_address', 'required'=>1 ),
    	'dbem_city' => array ( 'label' => __('City','dbem'), 'type' => 'dbem_city', 'fieldid'=>'dbem_city', 'required'=>1 ),
    	'dbem_state' => array ( 'label' => __('State/County','dbem'), 'type' => 'dbem_state', 'fieldid'=>'dbem_state', 'required'=>1 ),
    	'dbem_zip' => array ( 'label' => __('Zip/Post Code','dbem'), 'type' => 'dbem_zip', 'fieldid'=>'dbem_zip', 'required'=>1 ),
    	'dbem_country' => array ( 'label' => __('Country','dbem'), 'type' => 'dbem_country', 'fieldid'=>'dbem_country', 'required'=>1 ),
    	'dbem_phone' => array ( 'label' => __('Phone','dbem'), 'type' => 'dbem_phone', 'fieldid'=>'dbem_phone' ),
    	'dbem_fax' => array ( 'label' => __('Fax','dbem'), 'type' => 'dbem_fax', 'fieldid'=>'dbem_fax' ),
	  	'textarea' => array ( 'label' => __('Comment','dbem'), 'type' => 'textarea', 'fieldid'=>'booking_comment' ),
	));
	add_option('em_booking_form_error_required', __('Please fill in the field: %s','em-pro'));
    $new_fields = array(
    	'dbem_address' => array ( 'label' => __('Address','dbem'), 'type' => 'text', 'fieldid'=>'dbem_address', 'required'=>1 ),
    	'dbem_address_2' => array ( 'label' => __('Address Line 2','dbem'), 'type' => 'text', 'fieldid'=>'dbem_address_2' ),
    	'dbem_city' => array ( 'label' => __('City','dbem'), 'type' => 'text', 'fieldid'=>'dbem_city', 'required'=>1 ),
    	'dbem_state' => array ( 'label' => __('State/County','dbem'), 'type' => 'text', 'fieldid'=>'dbem_state', 'required'=>1 ),
    	'dbem_zip' => array ( 'label' => __('Zip/Post Code','dbem'), 'type' => 'text', 'fieldid'=>'dbem_zip', 'required'=>1 ),
    	'dbem_country' => array ( 'label' => __('Country','dbem'), 'type' => 'country', 'fieldid'=>'dbem_country', 'required'=>1 ),
    	'dbem_phone' => array ( 'label' => __('Phone','dbem'), 'type' => 'text', 'fieldid'=>'dbem_phone' ),
    	'dbem_fax' => array ( 'label' => __('Fax','dbem'), 'type' => 'text', 'fieldid'=>'dbem_fax' ),
    	'dbem_company' => array ( 'label' => __('Company','dbem'), 'type' => 'text', 'fieldid'=>'dbem_company' ),
    );
	add_option('em_user_fields', $new_fields);
	$customer_fields = array('address' => 'dbem_address','address_2' => 'dbem_address_2','city' => 'dbem_city','state' => 'dbem_state','zip' => 'dbem_zip','country' => 'dbem_country','phone' => 'dbem_phone','fax' => 'dbem_fax','company' => 'dbem_company');
    add_option('emp_gateway_customer_fields', $customer_fields);
	//Gateway Stuff
    add_option('dbem_emp_booking_form_reg_input', 1);
    add_option('dbem_emp_booking_form_reg_show', 1);
	add_option('dbem_gateway_use_buttons', 0);
	add_option('dbem_gateway_label', __('Pay With','em-pro'));
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
	//email reminders
	add_option('dbem_cron_emails', 0);
	add_option('dbem_emp_emails_reminder_subject', 'Reminder - #_EVENTNAME');
	$email_footer = __('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$respondent_email_body_localizable = __("Dear #_BOOKINGNAME, <br/>This is a reminder about your #_BOOKINGSPACES space/spaces reserved for #_EVENTNAME.<br/>When : #_EVENTDATES @ #_EVENTTIMES<br/>Where : #_LOCATIONNAME - #_LOCATIONFULLLINE<br/>We look forward to seeing you there!<br/>Yours faithfully,<br/>#_CONTACTNAME",'dbem').$email_footer;
	add_option('dbem_emp_emails_reminder_body', str_replace("<br/>", "\n\r", $respondent_email_body_localizable));
	add_option('dbem_emp_emails_reminder_time', '12:00 AM');
	add_option('dbem_emp_emails_reminder_days', 1);	
	add_option('dbem_emp_emails_reminder_ical', 1);
	
	//Version updates
	if( get_option('em_pro_version') ){ //upgrade, so do any specific version updates
		if( get_option('em_pro_version') < 2.16 ){ //add new customer information fields
		    $user_fields = get_option('em_user_fields', array () );
		    update_option('em_user_fields', array_merge($new_fields, $user_fields));
		}
		if( get_option('em_pro_version') < 2.061 ){ //new booking form data structure
			global $wpdb;
			//backward compatability, check first field to see if indexes start with 'booking_form_...' and change this.
			$form_fields = get_option('em_booking_form_fields', $booking_form_data['form']);
			if( is_array($form_fields) ){
				$booking_form_fields = array();
				foreach( $form_fields as $form_field_id => $form_field_data){
					foreach( $form_field_data as $field_key => $value ){
						$field_key = str_replace('booking_form_', '', $field_key);
						$booking_form_fields[$form_field_id][$field_key] = $value;
					}
				}
				//move booking form to meta table and update wp option with booking form id too
				$booking_form = serialize(array('name'=>__('Default','em-pro'), 'form'=>$booking_form_fields));
				if ($wpdb->insert(EM_META_TABLE, array('meta_key'=>'booking-form','meta_value'=>$booking_form,'object_id'=>0))){
					update_option('em_booking_form_fields',$wpdb->insert_id);
				}
			}
		}
		if( get_option('em_pro_version') < 1.6 ){ //make buttons the default option
			update_option('dbem_gateway_use_buttons', 1);
			if( get_option('em_offline_button_text') && !get_option('em_offline_button') ){
				update_option('em_offline_button',get_option('em_offline_button_text')); //merge offline quick pay button option into one
			}
			if( get_option('em_paypal_button_text') && !get_option('em_paypal_button') ){
				update_option('em_paypal_button',get_option('em_paypal_button_text')); //merge offline quick pay button option into one
			}
		}
	}else{
		//Booking form stuff only run on install
		$insert_result = $wpdb->insert(EM_META_TABLE, array('meta_value'=>serialize($booking_form_data), 'meta_key'=>'booking-form','object_id'=>0));
		add_option('em_booking_form_fields', $wpdb->insert_id);
	}
}     
?>