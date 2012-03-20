<?php

class EM_Gateway_Paypal extends EM_Gateway {
	//change these properties below if creating a new gateway, not advised to change this for PayPal
	var $gateway = 'paypal';
	var $title = 'PayPal';
	var $status = 4;
	var $status_txt = 'Awaiting PayPal Payment';
	var $button_enabled = true;
	var $payment_return = true;

	/**
	 * Sets up gateaway and adds relevant actions/filters 
	 */
	function __construct() {
		parent::__construct();
		$this->status_txt = __('Awaiting PayPal Payment','em-pro');
		if($this->is_active()) {
			//Booking Interception
			if ( absint(get_option('em_'.$this->gateway.'_booking_timeout')) > 0 ){
				//Modify spaces calculations only if bookings are set to time out, in case pending spaces are set to be reserved.
				add_filter('em_bookings_get_pending_spaces', array(&$this, 'em_bookings_get_pending_spaces'),1,2);
			}
			add_action('em_gateway_js', array(&$this,'em_gateway_js'));
			//Gateway-Specific
			add_action('em_template_my_bookings_header',array(&$this,'say_thanks')); //say thanks on my_bookings page
			//set up cron
			$timestamp = wp_next_scheduled('emp_cron_hook');
			if( absint(get_option('em_paypal_booking_timeout')) > 0 && !$timestamp ){
				$result = wp_schedule_event(time(),'em_minute','emp_cron_hook');
			}elseif( !$timestamp ){
				wp_unschedule_event($timestamp, 'emp_cron_hook');
			}
		}else{
			//unschedule the cron
			$timestamp = wp_next_scheduled('emp_cron_hook');
			wp_unschedule_event($timestamp, 'emp_cron_hook');			
		}
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */
	
	/**
	 * Modifies pending spaces calculations to include paypal bookings, but only if PayPal bookings are set to time-out (i.e. they'll get deleted after x minutes), therefore can be considered as 'pending' and can be reserved temporarily.
	 * @param integer $count
	 * @param EM_Bookings $EM_Bookings
	 * @return integer
	 */
	function em_bookings_get_pending_spaces($count, $EM_Bookings){
		foreach($EM_Bookings->bookings as $EM_Booking){
			if($EM_Booking->booking_status == $this->status && $this->uses_gateway($EM_Booking)){
				$count += $EM_Booking->get_spaces();
			}
		}
		return $count;
	}
	
	/**
	 * Intercepts return data after a booking has been made and adds paypal vars, modifies feedback message.
	 * @param array $return
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	function booking_form_feedback( $return, $EM_Booking = false ){
		//Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
		if( is_object($EM_Booking) && $this->uses_gateway($EM_Booking) ){
			if( !empty($return['result']) && $EM_Booking->get_price() > 0 && $EM_Booking->booking_status == $this->status ){
				$return['message'] = get_option('em_paypal_booking_feedback');	
				$paypal_url = $this->get_paypal_url();	
				$paypal_vars = $this->get_paypal_vars($EM_Booking);					
				$paypal_return = array('paypal_url'=>$paypal_url, 'paypal_vars'=>$paypal_vars);
				$return = array_merge($return, $paypal_return);
			}else{
				//returning a free message
				$return['message'] = get_option('em_paypal_booking_feedback_free');
			}
		}
		return $return;
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing paypal bookings
	 * --------------------------------------------------
	 */
	
	/**
	 * Instead of a simple status string, a resume payment button is added to the status message so user can resume booking from their my-bookings page.
	 * @param string $message
	 * @param EM_Booking $EM_Booking
	 * @return string
	 */
	function em_my_bookings_booked_message( $message, $EM_Booking){
		$message = parent::em_my_bookings_booked_message($message, $EM_Booking);
		if($this->uses_gateway($EM_Booking) && $EM_Booking->booking_status == $this->status){
			//user owes money!
			$paypal_vars = $this->get_paypal_vars($EM_Booking);
			$form = '<form action="'.$this->get_paypal_url().'" method="post">';
			foreach($paypal_vars as $key=>$value){
				$form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			$form .= '<input type="submit" value="'.__('Resume Payment','em-pro').'">';
			$form .= '</form>';
			$message .= " ". $form;
		}
		return $message;		
	}

	/**
	 * Outputs extra custom content e.g. the PayPal logo by default. 
	 */
	function booking_form(){
		echo get_option('em_'.$this->gateway.'_form');
	}
	
	/**
	 * Outputs some JavaScript during the em_gateway_js action, which is run inside a script html tag, located in gateways/gateway.paypal.js
	 */
	function em_gateway_js(){
		include(dirname(__FILE__).'/gateway.paypal.js');		
	}
	
	/*
	 * --------------------------------------------------
	 * PayPal Functions - functions specific to paypal payments
	 * --------------------------------------------------
	 */
	
	/**
	 * Retreive the paypal vars needed to send to the gatway to proceed with payment
	 * @param EM_Booking $EM_Booking
	 */
	function get_paypal_vars($EM_Booking){
		global $wp_rewrite, $EM_Notices;
		$notify_url = $this->get_payment_return_url();
		$paypal_vars = array(
			'business' => get_option('em_'. $this->gateway . "_email" ), 
			'cmd' => '_cart',
			'upload' => 1,
			'currency_code' => get_option('dbem_bookings_currency', 'USD'),
			'notify_url' =>$notify_url,
			'custom' => $EM_Booking->booking_id.':'.$EM_Booking->event_id,
			'charset' => 'UTF-8'
		);
		if( !get_option('dbem_bookings_tax_auto_add') && is_numeric(get_option('dbem_bookings_tax')) && get_option('dbem_bookings_tax') > 0 ){
			//tax only added if auto_add is disabled, since it would be added to individual ticket prices
			$paypal_vars['tax_cart'] = $EM_Booking->get_price(false,false,false) * (get_option('dbem_bookings_tax')/100);;
		}
		if( get_option('em_'. $this->gateway . "_return" ) != "" ){
			$paypal_vars['return'] = get_option('em_'. $this->gateway . "_return" );
		}
		if( get_option('em_'. $this->gateway . "_cancel_return" ) != "" ){
			$paypal_vars['cancel_return'] = get_option('em_'. $this->gateway . "_cancel_return" );
		}
		if( get_option('em_'. $this->gateway . "_format_logo" ) !== false ){
			$paypal_vars['cpp_logo_image'] = get_option('em_'. $this->gateway . "_format_logo" );
		}
		if( get_option('em_'. $this->gateway . "_border_color" ) !== false ){
			$paypal_vars['cpp_cart_border_color'] = get_option('em_'. $this->gateway . "_format_border" );
		}
		$count = 1;
		foreach( $EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking ){
			$price = $EM_Ticket_Booking->get_ticket()->get_price();
			if( $price > 0 ){
				$paypal_vars['item_name_'.$count] = wp_kses_data($EM_Ticket_Booking->get_ticket()->name);
				$paypal_vars['quantity_'.$count] = $EM_Ticket_Booking->get_spaces();
				$paypal_vars['amount_'.$count] = $price;
				$count++;
			}
		}
		return apply_filters('em_gateway_paypal_get_paypal_vars', $paypal_vars, $EM_Booking, $this);
	}
	
	/**
	 * gets paypal gateway url (sandbox or live mode)
	 * @returns string 
	 */
	function get_paypal_url(){
		return ( get_option('em_'. $this->gateway . "_status" ) == 'test') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr':'https://www.paypal.com/cgi-bin/webscr';
	}
	
	function say_thanks(){
		if( $_REQUEST['thanks'] == 1 ){
			echo "<div class='em-booking-message em-booking-message-success'>".get_option('em_'.$this->gateway.'_booking_feedback_thanks').'</div>';
		}
	}

	/**
	 * Runs when PayPal sends IPNs to the return URL provided during bookings and EM setup. Bookings are updated and transactions are recorded accordingly. 
	 */
	function handle_payment_return() {
		// PayPal IPN handling code
		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {
			
			if (get_option( $this->gateway . "_status" ) == 'live') {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			$req = 'cmd=_notify-validate';
			if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc()) $v = stripslashes($v);
				$req .= '&' . $k . '=' . $v;
			}

			$header = 'POST /cgi-bin/webscr HTTP/1.0' . "\r\n"
					. 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
					. 'Content-Length: ' . strlen($req) . "\r\n"
					. "\r\n";

			@set_time_limit(60);
			if (false && $conn = @fsockopen($domain, 80, $errno, $errstr, 30)) {
				fputs($conn, $header . $req);
				socket_set_timeout($conn, 30);

				$response = '';
				$close_connection = false;
				while (true) {
					if (feof($conn) || $close_connection) {
						fclose($conn);
						break;
					}

					$st = @fgets($conn, 4096);
					if ($st === false) {
						$close_connection = true;
						continue;
					}

					$response .= $st;
				}

				$error = '';
				$lines = explode("\n", str_replace("\r\n", "\n", $response));
				// looking for: HTTP/1.1 200 OK
				if (count($lines) == 0) $error = 'Response Error: Header not found';
				else if (substr($lines[0], -7) != ' 200 OK') $error = 'Response Error: Unexpected HTTP response';
				else {
					// remove HTTP header
					while (count($lines) > 0 && trim($lines[0]) != '') array_shift($lines);

					// first line will be empty, second line will have the result
					if (count($lines) < 2) $error = 'Response Error: No content found in transaction response';
					else if (strtoupper(trim($lines[1])) != 'VERIFIED') $error = 'Response Error: Unexpected transaction response';
				}

				if ($error != '') {
					echo $error;
					//fwrite($log,"\n".date('[Y-m-d H:s:i]').' Exiting, PP not verified.');
					//fclose($log);
					exit;
				}
			}
			
			// handle cases that the system must ignore
			$new_status = false;
			//Common variables
			$amount = $_POST['mc_gross'];
			$currency = $_POST['mc_currency'];
			$timestamp = date('Y-m-d H:i:s', strtotime($_POST['payment_date']));
			$custom_values = explode(':',$_POST['custom']);
			$booking_id = $custom_values[0];
			$event_id = !empty($custom_values[1]) ? $custom_values[1]:0;
			$EM_Booking = new EM_Booking($booking_id);
			if( !empty($EM_Booking->booking_id) ){
				//booking exists
				$EM_Booking->manage_override = true; //since we're overriding the booking ourselves.
				$user_id = $EM_Booking->person_id;
				
				// process PayPal response
				switch ($_POST['payment_status']) {
					case 'Partially-Refunded':
						break;
	
					case 'In-Progress':
						break;
	
					case 'Completed':
					case 'Processed':
						// case: successful payment
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], '');
				
						//get booking metadata
						$user_data = array();
						if( !empty($EM_Booking->booking_meta['registration']) && is_array($EM_Booking->booking_meta['registration']) ){
							foreach($EM_Booking->booking_meta['registration'] as $fieldid => $field){
								if( trim($field) !== '' ){
									$user_data[$fieldid] = $field;
								}
							}
						}
						if( $_POST['mc_gross'] >= $EM_Booking->get_price(false, false, true) && (!get_option('em_'.$this->gateway.'_manual_approval', false) || !get_option('dbem_bookings_approval')) ){
							$EM_Booking->approve();
						}else{
							//TODO do something if pp payment not enough
							$EM_Booking->set_status(0); //Set back to normal "pending"
						}
						do_action('em_payment_processed', $EM_Booking, $this);
						break;
	
					case 'Reversed':
						// case: charge back
						$note = 'Last transaction has been reversed. Reason: Payment has been reversed (charge back)';
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						//We need to cancel their booking.
						$EM_Booking->cancel();
						do_action('em_payment_reversed', $EM_Booking, $this);
						
						break;
	
					case 'Refunded':
						// case: refund
						$note = 'Last transaction has been reversed. Reason: Payment has been refunded';
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						$EM_Booking->cancel();
						do_action('em_payment_refunded', $EM_Booking, $this);
						break;
	
					case 'Denied':
						// case: denied
						$note = 'Last transaction has been reversed. Reason: Payment Denied';
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						$EM_Booking->cancel();
						do_action('em_payment_denied', $EM_Booking, $this);
						break;
	
					case 'Pending':
						// case: payment is pending
						$pending_str = array(
							'address' => 'Customer did not include a confirmed shipping address',
							'authorization' => 'Funds not captured yet',
							'echeck' => 'eCheck that has not cleared yet',
							'intl' => 'Payment waiting for aproval by service provider',
							'multi-currency' => 'Payment waiting for service provider to handle multi-currency process',
							'unilateral' => 'Customer did not register or confirm his/her email yet',
							'upgrade' => 'Waiting for service provider to upgrade the PayPal account',
							'verify' => 'Waiting for service provider to verify his/her PayPal account',
							'paymentreview' => 'Paypal is currently reviewing the payment and will approve or reject within 24 hours',
							'*' => ''
							);
						$reason = @$_POST['pending_reason'];
						$note = 'Last transaction is pending. Reason: ' . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);
	
						$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
	
						do_action('em_payment_pending', $EM_Booking, $this);
						break;
	
					default:
						// case: various error cases		
				}
			}else{
				if( $_POST['payment_status'] == 'Completed' || $_POST['payment_status'] == 'Processed' ){
					$message = apply_filters('em_gateway_paypal_bad_booking_email',"
A Payment has been received by PayPal for a non-existent booking. 

Event Details : %event%

It may be that this user's booking has timed out yet they proceeded with payment at a later stage.

To refund this transaction, you must go to your PayPal account and search for this transaction:

Transaction ID : %transaction_id%
Email : %payer_email%

When viewing the transaction details, you should see an option to issue a refund.

If there is still space available, the user must book again.

Sincerely,
Events Manager
					", $booking_id, $event_id);
					if( !empty($event_id) ){
						$EM_Event = new EM_Event($event_id);
						$event_details = $EM_Event->name . " - " . date_i18n(get_option('date_format'), $EM_Event->start);
					}else{ $event_details = __('Unknown','em-pro'); }
					$message  = str_replace(array('%transaction_id%','%payer_email%', '%event%'), array($_POST['txn_id'], $_POST['payer_email'], $event_details), $message);
					wp_mail(get_option('em_'. $this->gateway . "_email" ), __('Unprocessed payment needs refund'), $message);
				}else{
					//header('Status: 404 Not Found');
					echo 'Error: Bad IPN request, custom ID does not correspond with any pending booking.';
					//echo "<pre>"; print_r($_POST); echo "</pre>";
					exit;
				}
			}
			//fclose($log);
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			//header('Status: 404 Not Found');
			echo 'Error: Missing POST variables. Identification is not possible.';
			exit;
		}
	}
	
	/*
	 * --------------------------------------------------
	 * Gateway Settings Functions
	 * --------------------------------------------------
	 */
	
	/**
	 * Outputs custom PayPal setting fields in the settings page 
	 */
	function mysettings() {
		global $EM_options;
		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
			  <th scope="row"><?php _e('Success Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('The message that is shown to a user when a booking is successful whilst being redirected to PayPal for payment.','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Success Free Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_feedback_free" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback_free" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be redirected to PayPal.','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Thank You Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_feedback_thanks" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback_thanks" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('If you choose to return users to the default Events Manager thank you page after a user has paid on PayPal, you can customize the thank you message here.','em-pro'); ?></em>
			  </td>
		  </tr>
		</tbody>
		</table>
		
		<h3><?php echo sprintf(__('%s Options','em-pro'),'PayPal'); ?></h3>
		<p><?php echo __('<strong>Important:</strong>In order to connect PayPal with your site, you need to enable IPN on your account.'); echo " ". sprintf(__('Your return url is %s','em-pro'),'<code>'.$this->get_payment_return_url().'</code>'); ?></p> 
		<p><?php echo sprintf(__('Please visit the <a href="%s">documentation</a> for further instructions.','em-pro'), 'http://wp-events-plugin.com/documentation/'); ?></p>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Email', 'em-pro') ?></th>
				  <td><input type="text" name="paypal_email" value="<?php esc_attr_e( get_option('em_'. $this->gateway . "_email" )); ?>" />
				  <br />
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Site', 'em-pro') ?></th>
			  <td>
				  <select name="paypal_site">
				  <?php
				      $paypal_site = get_option('em_'. $this->gateway . "_site" );
				      $sel_locale = empty($paypal_site) ? 'US' : $paypal_site;
				      $locales = array('AU'	=> 'Australia', 'AT'	=> 'Austria', 'BE'	=> 'Belgium', 'CA'	=> 'Canada', 'CN'	=> 'China', 'FR'	=> 'France', 'DE'	=> 'Germany', 'HK'	=> 'Hong Kong', 'IT'	=> 'Italy', 'MX'	=> 'Mexico', 'NL'	=> 'Netherlands', 'NZ'	=>	'New Zealand', 'PL'	=> 'Poland', 'SG'	=> 'Singapore', 'ES'	=> 'Spain', 'SE'	=> 'Sweden', 'CH'	=> 'Switzerland', 'GB'	=> 'United Kingdom', 'US'	=> 'United States');
		
				      foreach ($locales as $key => $value) {
							echo '<option value="' . esc_attr($key) . '"';
				 			if($key == $sel_locale) echo 'selected="selected"';
				 			echo '>' . esc_html($value) . '</option>' . "\n";
				      }
				  ?>
				  </select>
				  <br />
				  <?php //_e('Format: 00.00 - Ex: 1.25', 'supporter') ?>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Paypal Currency', 'em-pro') ?></th>
			  <td><?php echo esc_html(get_option('dbem_bookings_currency','USD')); ?><br /><i><?php echo sprintf(__('Set your currency in the <a href="%s">settings</a> page.','dbem'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?></i></td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Mode', 'em-pro') ?></th>
			  <td>
				  <select name="paypal_status">
					  <option value="live" <?php if (get_option('em_'. $this->gateway . "_status" ) == 'live') echo 'selected="selected"'; ?>><?php _e('Live Site', 'em-pro') ?></option>
					  <option value="test" <?php if (get_option('em_'. $this->gateway . "_status" ) == 'test') echo 'selected="selected"'; ?>><?php _e('Test Mode (Sandbox)', 'em-pro') ?></option>
				  </select>
				  <br />
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Return URL', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_return" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_return" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Once a payment is completed, users will be offered a link to this URL which confirms to the user that a payment is made. If you would to customize the thank you page, create a new page and add the link here. For automatic redirect, you need to turn auto-return on in your PayPal settings.', 'em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Cancel URL', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_cancel_return" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_cancel_return" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Whilst paying on PayPal, if a user cancels, they will be redirected to this page.', 'em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('PayPal Page Logo', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_format_logo" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_format_logo" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Add your logo to the PayPal payment page. It\'s highly recommended you link to a https:// address.', 'em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Border Color', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_format_border" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_format_border" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('Provide a hex value color to change the color from the default blue to another color (e.g. #CCAAAA).','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Delete Bookings Pending Payment', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="paypal_booking_timeout" style="width:50px;" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_timeout" )); ?>" style='width: 40em;' /> <?php _e('minutes','em-pro'); ?><br />
			  	<em><?php _e('Once a booking is started and the user is taken to PayPal, Events Manager stores a booking record in the database to identify the incoming payment. These spaces may be considered reserved if you enable <em>Reserved unconfirmed spaces?</em> in your Events &gt; Settings page. If you would like these bookings to expire after x minutes, please enter a value above (note that bookings will be deleted, and any late payments will need to be refunded manually via PayPal).','em-pro'); ?></em>
			  </td>
		  </tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Manually approve completed transactions?', 'em-pro') ?></th>
			  <td>
			  	<input type="checkbox" name="paypal_manual_approval" value="1" <?php echo (get_option('em_'. $this->gateway . "_manual_approval" )) ? 'checked="checked"':''; ?> /><br />
			  	<em><?php _e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em><br />
			  	<em><?php echo sprintf(__('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-pro'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?></em>
			  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}

	/* 
	 * Run when saving PayPal settings, saves the settings available in EM_Gateway_Paypal::mysettings()
	 */
	function update() {
		parent::update();
		if( !empty($_REQUEST[$this->gateway.'_email']) ) {
			$gateway_options = array(
				$this->gateway . "_email" => $_REQUEST[ $this->gateway.'_email' ],
				$this->gateway . "_site" => $_REQUEST[ $this->gateway.'_site' ],
				$this->gateway . "_currency" => $_REQUEST[ 'currency' ],
				$this->gateway . "_status" => $_REQUEST[ $this->gateway.'_status' ],
				$this->gateway . "_tax" => $_REQUEST[ $this->gateway.'_button' ],
				$this->gateway . "_format_logo" => $_REQUEST[ $this->gateway.'_format_logo' ],
				$this->gateway . "_format_border" => $_REQUEST[ $this->gateway.'_format_border' ],
				$this->gateway . "_manual_approval" => $_REQUEST[ $this->gateway.'_manual_approval' ],
				$this->gateway . "_booking_feedback" => wp_kses_data($_REQUEST[ $this->gateway.'_booking_feedback' ]),
				$this->gateway . "_booking_feedback_free" => wp_kses_data($_REQUEST[ $this->gateway.'_booking_feedback_free' ]),
				$this->gateway . "_booking_feedback_thanks" => wp_kses_data($_REQUEST[ $this->gateway.'_booking_feedback_thanks' ]),
				$this->gateway . "_booking_timeout" => $_REQUEST[ $this->gateway.'_booking_timeout' ],
				$this->gateway . "_return" => $_REQUEST[ $this->gateway.'_return' ],
				$this->gateway . "_cancel_return" => $_REQUEST[ $this->gateway.'_cancel_return' ],
				$this->gateway . "_form" => $_REQUEST[ $this->gateway.'_form' ]
			);
			foreach($gateway_options as $key=>$option){
				update_option('em_'.$key, stripslashes($option));
			}
		}
		//default action is to return true
		return true;

	}
}
EM_Gateways::register_gateway('paypal', 'EM_Gateway_Paypal');

/**
 * Deletes bookings pending payment that are more than x minutes old, defined by paypal options. 
 */
function em_gateway_paypal_booking_timeout(){
	global $wpdb;
	//Get a time from when to delete
	$minutes_to_subtract = absint(get_option('em_paypal_booking_timeout'));
	if( $minutes_to_subtract > 0 ){
		//Run the SQL query
		//first delete ticket_bookings with expired bookings
		$sql = "DELETE FROM ".EM_TICKETS_BOOKINGS_TABLE." WHERE booking_id IN (SELECT booking_id FROM ".EM_BOOKINGS_TABLE." WHERE booking_date < TIMESTAMPADD(MINUTE, -{$minutes_to_subtract}, NOW()) AND booking_status=4);";
		$wpdb->query($sql);
		//then delete the bookings themselves
		$sql = "DELETE FROM ".EM_BOOKINGS_TABLE." WHERE booking_date < TIMESTAMPADD(MINUTE, -{$minutes_to_subtract}, NOW()) AND booking_status=4;";
		$wpdb->query($sql);
		update_option('emp_result_try',$sql);
	}
}
add_action('emp_cron_hook', 'em_gateway_paypal_booking_timeout');
?>