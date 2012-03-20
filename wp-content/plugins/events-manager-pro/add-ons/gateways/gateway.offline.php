<?php

/**
 * This Gateway is slightly special, because as well as providing functions that need to be activated, there are offline payment functions that are always there e.g. adding manual payments.
 * @author marcus
 */
class EM_Gateway_Offline extends EM_Gateway {

	var $gateway = 'offline';
	var $title = 'Offline';
	var $status = 5;
	var $button_enabled = true;

	/**
	 * Sets up gateway and registers actions/filters
	 */
	function __construct() {
		parent::__construct();
		add_action('init',array(&$this, 'actions'),1);
		//Booking Interception
		add_filter('em_booking_set_status',array(&$this,'em_booking_set_status'),1,2);
		add_filter('em_bookings_pending_count', array(&$this, 'em_bookings_pending_count'),1,1);
		add_filter('em_bookings_get_pending_spaces', array(&$this, 'em_bookings_get_pending_spaces'),1,2);
		//Booking UI
		if($this->is_active()) { //only if active
			//Bookings Table
			add_filter('em_bookings_table_booking_actions_5', array(&$this,'bookings_table_actions'),1,2);
			add_filter('em_bookings_table_footer', array(&$this,'bookings_table_footer'),1,2);
		}
		add_action('em_admin_event_booking_options_buttons', array(&$this, 'event_booking_options_buttons'),10);
		add_action('em_admin_event_booking_options', array(&$this, 'event_booking_options'),10);
		add_action('em_bookings_single_metabox_footer', array(&$this, 'add_payment_form'),1,1); //add payment to booking
		add_action('em_bookings_manual_booking', array(&$this, 'add_booking_form'),1,1);
	}
	
	/**
	 * Run on init, actions that need taking regarding offline bookings are caught here, e.g. registering manual bookings and adding payments 
	 */
	function actions(){
		global $EM_Notices, $EM_Booking, $EM_Event, $wpdb;
		//Check if manual payment has been added
		if( !empty($_REQUEST['booking_id']) && !empty($_REQUEST['action']) && !empty($_REQUEST['_wpnonce'])){
			$EM_Booking = new EM_Booking($_REQUEST['booking_id']);
			if( $_REQUEST['action'] == 'gateway_add_payment' && is_object($EM_Booking) && wp_verify_nonce($_REQUEST['_wpnonce'], 'gateway_add_payment') ){
				if( !empty($_REQUEST['transaction_total_amount']) && is_numeric($_REQUEST['transaction_total_amount']) ){
					$this->record_transaction($EM_Booking, $_REQUEST['transaction_total_amount'], get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', $_REQUEST['transaction_note']);
					$string = __('Payment has been registered.','em-pro');
					$total = $wpdb->get_var('SELECT SUM(transaction_total_amount) FROM '.EM_TRANSACTIONS_TABLE." WHERE booking_id={$EM_Booking->booking_id}");
					if( $total >= $EM_Booking->get_price() ){
						$EM_Booking->approve();
						$string .= " ". __('Booking is now fully paid and confirmed.','em-pro');
					}
					$EM_Notices->add_confirm($string,true);
					do_action('em_payment_processed', $EM_Booking, $this);
					wp_redirect(wp_get_referer());
					exit();
				}else{
					$EM_Notices->add_error(__('Please enter a valid payment amount. Numbers only, use negative number to credit a booking.','em-pro'));
					unset($_REQUEST['action']);
					unset($_POST['action']);
				}
			}
		}
		//manual bookings
		if( !empty($_REQUEST['event_id']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'manual_booking' && !empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'],'manual_booking')){ //TODO allow manual bookings for any event owner that can manage bookings
			$EM_Booking = new EM_Booking();
			$EM_Event = new EM_Event($_REQUEST['event_id']);
			if( $EM_Event->can_manage('manage_bookings','manage_others_bookings') ){
				if( $EM_Booking->get_post() ){
					//Assign a user to this booking
					$EM_Booking->person = new EM_Person($_REQUEST['person_id']);
					$EM_Booking->booking_status = !empty($_REQUEST['booking_paid']) ? 1 : 5;
					if( $EM_Event->get_bookings()->add($EM_Booking) ){
						$result = true;
						if( !empty($_REQUEST['booking_paid']) ){
							$this->record_transaction($EM_Booking, $EM_Booking->get_price(), get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', '');
						}
						$additional = sprintf(__('Go back to &quot;%s&quot; bookings','em-pro'), '<a href="'.$EM_Event->get_bookings_url().'">'.$EM_Event->name.'</a>');
						$EM_Notices->add_confirm( $EM_Event->get_bookings()->feedback_message .' '.$additional, true );
						$redirect = !empty($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : wp_get_referer();
						wp_redirect( $redirect );
						exit();
					}else{
						ob_start();
						$result = false;
						$EM_Booking->feedback_message = ob_get_clean();
						$EM_Notices->add_error( $EM_Event->get_bookings()->get_errors() );				
					}
				}else{
					$result = false;
					$EM_Notices->add_error( $EM_Booking->get_errors() );
				}
			}	
		}
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */
	
	/**
	 * Intercepts return JSON and adjust feedback messages when booking with this gateway.
	 * @param array $return
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	function booking_form_feedback( $return, $EM_Booking = false ){
		if( !empty($return['result']) && !empty($EM_Booking->booking_meta['gateway']) && !empty($EM_Booking->booking_status) ){ //check emtpies
			if( $EM_Booking->booking_status == 5 && $this->uses_gateway($EM_Booking) ){ //check values
				$return['message'] = get_option('em_'.$this->gateway.'_booking_feedback');	
				return apply_filters('em_gateway_offline_booking_add', $return, $EM_Booking->get_event(), $EM_Booking);
			}
		}
		return $return;
	}
	
	/**
	 * Sets booking status and records a full payment transaction if new status is from pending payment to completed. 
	 * @param int $status
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_set_status($status, $EM_Booking){
		if($status == 1 && $EM_Booking->previous_status == $this->status && $this->uses_gateway($EM_Booking) && (empty($_REQUEST['action']) || $_REQUEST['action'] != 'gateway_add_payment') ){
			$this->record_transaction($EM_Booking, $EM_Booking->get_price(false,false,true), get_option('dbem_bookings_currency'), current_time('mysql'), '', 'Completed', '');								
		}
		return $status;
	}
	
	function em_bookings_pending_count($count){
		return $count + count(EM_Bookings::get(array('status'=>'5'))->bookings);
	}
	
	/**
	 * @param integer $count
	 * @param EM_Bookings $EM_Bookings
	 */
	function em_bookings_get_pending_spaces($count, $EM_Bookings){
		foreach($EM_Bookings->bookings as $EM_Booking){
			if($EM_Booking->booking_status == $this->status && $this->uses_gateway($EM_Booking)){
				$count += $EM_Booking->get_spaces();
			}
		}
		return $count;
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing offline bookings
	 * --------------------------------------------------
	 */

	/**
	 * Outputs extra custom information, e.g. payment details or procedure, which is displayed when this gateway is selected when booking (not when using Quick Pay Buttons)
	 */
	function booking_form(){
		echo get_option('em_'.$this->gateway.'_form');
	}
	
	/**
	 * Adds relevant actions to booking shown in the bookings table
	 * @param EM_Booking $EM_Booking
	 */
	function bookings_table_actions( $actions, $EM_Booking ){
		return array(
			'approve' => '<a class="em-bookings-approve em-bookings-approve-offline" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.__('Approve','dbem').'</a>',
			'reject' => '<a class="em-bookings-reject" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_reject', 'booking_id'=>$EM_Booking->booking_id)).'">'.__('Reject','dbem').'</a>',
			'delete' => '<span class="trash"><a class="em-bookings-delete" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id)).'">'.__('Delete','dbem').'</a></span>',
			'edit' => '<a class="em-bookings-edit" href="'.em_add_get_params($EM_Booking->get_event()->get_bookings_url(), array('booking_id'=>$EM_Booking->booking_id, 'em_ajax'=>null, 'em_obj'=>null)).'">'.__('Edit/View','dbem').'</a>',
		);
	}
	
	/**
	 * JS shown at the bottom of a bookings table to modify offline booking button behaviour
	 * @param EM_Booking $EM_Booking
	 */
	function bookings_table_footer($EM_Booking){
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('.em-bookings-approve-offline').live('click', function(e){
					if( !confirm('<?php _e('Be aware that by approving a booking awaiting payment, a full payment transaction will be registered against this booking, meaning that it will be considered as paid.','dbem'); ?>') ){
						return false; 
					}
				});
			});
		</script>
		<?php
	}
	
	/**
	 * Adds an add manual booking button to admin pages
	 */
	function event_booking_options_buttons(){
		global $EM_Event;
		?><a href="<?php echo EM_ADMIN_URL; ?>&amp;page=events-manager-bookings&amp;action=manual_booking&amp;event_id=<?php echo $EM_Event->event_id ?>" class="button add-new-h2"><?php _e('Add Booking','dbem') ?></a><?php	
	}
	
	/**
	 * Adds a link to add a new manual booking in admin pages
	 */
	function event_booking_options(){
		global $EM_Event;
		?><a href="<?php echo EM_ADMIN_URL; ?>&amp;page=events-manager-bookings&amp;action=manual_booking&amp;event_id=<?php echo $EM_Event->event_id ?>"><?php _e('add booking','dbem') ?></a><?php	
	}
	
	/**
	 * Adds a payment form which can be used to submit full or partial offline payments for a booking. 
	 */
	function add_payment_form() {
		?>
		<div id="em-gateway-payment" class="stuffbox">
			<h3>
				<?php _e('Add Offline Payment', 'dbem'); ?>
			</h3>
			<div class="inside">
				<div>
					<form method="post" action="" style="padding:5px;">
						<table class="form-table">
							<tbody>
							  <tr valign="top">
								  <th scope="row"><?php _e('Amount', 'em-pro') ?></th>
									  <td><input type="text" name="transaction_total_amount" value="<?php if(!empty($_REQUEST['transaction_total_amount'])) echo $_REQUEST['transaction_total_amount']; ?>" />
									  <br />
									  <em><?php _e('Please enter a valid payment amount (e.g. 10.00). Use negative numbers to credit a booking.','em-pro'); ?></em>
								  </td>
							  </tr>
							  <tr valign="top">
								  <th scope="row"><?php _e('Comments', 'em-pro') ?></th>
								  <td>
										<textarea name="transaction_note"><?php if(!empty($_REQUEST['transaction_note'])) echo $_REQUEST['transaction_note']; ?></textarea>
								  </td>
							  </tr>
							</tbody>
						</table>							
						<input type="hidden" name="action" value="gateway_add_payment" />
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('gateway_add_payment'); ?>" />
						<input type="hidden" name="redirect_to" value="<?php echo (!empty($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to']:wp_get_referer(); ?>" />
						<input type="submit" value="<?php _e('Add Offline Payment', 'dbem'); ?>" />
					</form>
				</div>					
			</div>
		</div> 
		<?php
	}

	/**
	 * Generates a booking form where an event admin can add a booking for another user. $EM_Event is assumed to be global at this point.
	 */
	function add_booking_form() {
		/* @var $EM_Event EM_Event */   
		global $EM_Notices, $EM_Event;
		if( !is_object($EM_Event) ) { return; }
		$booked_places_options = array();
		for ( $i = 1; $i <= 10; $i++ ) {
			$booking_spaces = (!empty($_POST['booking_spaces']) && $_POST['booking_spaces'] == $i) ? 'selected="selected"':'';
			array_push($booked_places_options, "<option value='$i' $booking_spaces>$i</option>");
		}
		$EM_Tickets = $EM_Event->get_bookings()->get_tickets();	
		$back_to_button = '<a href="'.$EM_Event->get_bookings_url().'" class="button add-new-h2">'. sprintf(__('Go back to &quot;%s&quot; bookings','em-pro'), $EM_Event->name) .'</a>';
		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php echo sprintf(__('Add Booking For &quot;%s&quot;','em-pro'), $EM_Event->name) .' '. $back_to_button; ?></h2>
			<div id="em-booking">
				<?php if( $EM_Event->start < current_time('timestamp') ): ?>
					<p><?php _e('Bookings are closed for this event.','dbem'); ?></p>
				<?php else: ?>
					<?php echo $EM_Notices; ?>		
					<?php if( count($EM_Tickets->tickets) > 0) : ?>
						<?php //Tickets exist, so we show a booking form. ?>
						<form id='em-booking-form' name='booking-form' method='post' action=''>
							<?php do_action('em_booking_form_before_tickets'); ?>
							<?php if( count($EM_Tickets->tickets) > 1 ): ?>
								<div class='table-wrap'>
								<table class="em-tickets widefat post" cellspacing="0" cellpadding="0">
									<thead>
										<tr>
											<th><?php _e('Ticket Type','dbem') ?></th>
											<?php if( !$EM_Event->is_free() ): ?>
											<th><?php _e('Price','dbem') ?></th>
											<?php endif; ?>
											<th><?php _e('Spaces','dbem') ?></th>
										</tr>
									</thead>
									<tbody>
									<?php foreach( $EM_Tickets->tickets as $EM_Ticket ): ?>
										<?php if( $EM_Ticket->is_available() || get_option('dbem_bookings_tickets_show_unavailable') ): ?>
										<tr>
											<td><?php echo wp_kses_data($EM_Ticket->name); ?></td>
											<?php if( !$EM_Event->is_free() ): ?>
											<td><?php echo $EM_Ticket->get_price(true); ?></td>
											<?php endif; ?>
											<td>
												<?php 
													$spaces_options = $EM_Ticket->get_spaces_options();
													if( $spaces_options ){
														echo $spaces_options;
													}else{
														echo "<strong>".__('N/A','dbem')."</strong>";
													}
												?>
											</td>
										</tr>
										<?php endif; ?>
									<?php endforeach; ?>
									</tbody>
								</table>	
								</div>	
							<?php endif; ?>
							<?php do_action('em_booking_form_after_tickets'); ?>
							<div class='em-booking-form-details'>
							
								<?php $EM_Ticket = $EM_Tickets->get_first(); ?>
								<?php if( is_object($EM_Ticket) && count($EM_Tickets->tickets) == 1 ): ?>
								<p>
									<label for='em_tickets'><?php _e('Spaces', 'dbem') ?></label>
									<?php 
										$spaces_options = $EM_Ticket->get_spaces_options(false);
										if( $spaces_options ){
											echo $spaces_options;
										}else{
											echo "<strong>".__('N/A','dbem')."</strong>";
										}
									?>
								</p>	
								<?php endif; ?>
								
								<?php //Here we have extra information required for the booking. ?>
								<?php do_action('em_booking_form_before_user_details'); ?>
								<p>
									<label for='booking_comment'><?php _e('User', 'dbem') ?></label>
									<?php
									$person_id = (!empty($_REQUEST['person_id'])) ? $_REQUEST['person_id'] : false;
									wp_dropdown_users ( array ('name' => 'person_id', 'show_option_none' => __ ( "Select User", 'dbem' ), 'selected' => $person_id  ) );
									?>
								</p>
								<p>
									<label for='booking_comment'><?php _e('Already Paid?', 'dbem') ?></label>
									<input type="checkbox" name="booking_paid" value="1" style="width:auto;"/>
								</p>
								<?php if( get_option('em_booking_form_custom') ) : ?>
									<?php do_action('em_booking_form_custom'); ?>
								<?php else: //temporary fix, don't depend on this ?>	
									<p>
										<label for='booking_comment'><?php _e('Comment', 'dbem') ?></label>
										<textarea name='booking_comment'><?php echo !empty($_POST['booking_comment']) ? $_POST['booking_comment']:'' ?></textarea>
									</p>
									<?php do_action('em_booking_form_after_user_details'); ?>	
								<?php endif; ?>	
								<?php do_action('em_booking_form_after_user_details'); ?>					
								<div class="em-booking-buttons">
									<input type='submit' value="<?php _e('Submit Booking','em-pro'); ?>" />
								 	<input type='hidden' name='gateway' value='offline'/>
								 	<input type='hidden' name='action' value='manual_booking'/>
								 	<input type='hidden' name='event_id' value='<?php echo $EM_Event->event_id; ?>'/>
								 	<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('manual_booking'); ?>'/>
								</div>
							</div>
						</form>	
					<?php elseif( count($EM_Tickets->tickets) == 0 ): ?>
						<div><?php _e('No more tickets available at this time.','dbem'); ?></div>
					<?php endif; ?>  
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	
	/* 
	 * --------------------------------------------------
	 * Settings pages and functions
	 * --------------------------------------------------
	 */
	
	/**
	 * Outputs custom offline setting fields in the settings page 
	 */
	function mysettings() {

		global $EM_options;
		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
			  <th scope="row"><?php _e('Success Message', 'em-pro') ?></th>
			  <td>
			  	<input type="text" name="offline_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' /><br />
			  	<em><?php _e('The message that is shown to a user when a booking with offline payments is successful.','em-pro'); ?></em>
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
		$gateway_options = array(
			$this->gateway . "_button" => $_REQUEST[ 'offline_button' ],
			$this->gateway . "_form" => $_REQUEST[ 'offline_form' ],
			$this->gateway . "_booking_feedback" => $_REQUEST[ 'offline_booking_feedback' ]
		);
		foreach($gateway_options as $key=>$option){
			update_option('em_'.$key, stripslashes($option));
		}
		//default action is to return true
		return true;

	}
}
EM_Gateways::register_gateway('offline', 'EM_Gateway_Offline');
?>