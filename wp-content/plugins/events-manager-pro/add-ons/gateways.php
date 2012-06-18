<?php
if(!class_exists('EM_Gateways')) {
class EM_Gateways {
	/*
	 * --------------------------------------------------
	 * STATIC Functions - functions that don't need to be overriden
	 * --------------------------------------------------
	 */
	
	function init(){
	    add_filter('em_wp_localize_script', array('EM_Gateways','em_wp_localize_script'),10,1);
		//add to booking interface (menu options, booking statuses)
		add_action('em_bookings_table',array('EM_Gateways','em_bookings_table'),10,1);
		//WP_Query/Rewrite
		add_filter('rewrite_rules_array',array('EM_Gateways','rewrite_rules_array'));
		add_filter('query_vars',array('EM_Gateways','query_vars'));			
		//Menus
		add_action('em_create_events_submenu',array('EM_Gateways', 'admin_menu'),10,1);
		add_action('admin_init', array('EM_Gateways', 'handle_payment_gateways'),10,1);
		add_action('admin_init', array('EM_Gateways', 'handle_gateways_panel_updates'),10,1);
		add_action('em_options_page_footer_bookings', array('EM_Gateways','admin_options'));		
		//Booking interception
		add_filter('em_booking_add', array('EM_Gateways', 'em_booking_add'), 10, 3);
		add_filter('em_action_booking_add', array('EM_Gateways','em_action_booking_add'),1,2); //adds gateway var to feedback
		add_filter('em_booking_delete', array('EM_Gateways', 'em_booking_delete'), 10, 2);
		// Payment return
		add_action('parse_query', array('EM_Gateways', 'handle_payment_gateways'), 10 ); //just in case
		add_action('wp_ajax_em_payment', array('EM_Gateways', 'handle_payment_gateways'), 10 );
		//Booking Form Modifications
			//buttons only way, oudated but still possible, will eventually depreciated this once an API is out, so use the latter pls
			add_filter('em_booking_form_buttons', array('EM_Gateways','booking_form_buttons'),10,2); //Replace button with booking buttons
			//new way, with payment selector
			add_action('em_booking_form_footer', array('EM_Gateways','booking_form_footer'),10,2);
	}
	
	function em_wp_localize_script( $vars ){
	    $vars['booking_delete'] .= ' '.__('All transactional history associated with this booking will also be deleted.','em-pro');
	    $vars['transaction_delete'] = __('Are you sure you want to delete? This may make your transaction history out of sync with your payment gateway provider.', 'em-pro');
	    return $vars;
	}
	
	function em_bookings_table($EM_Bookings_Table){
		$EM_Bookings_Table->statuses['awaiting-online'] = array('label'=>__('Awaiting Online Payment','em-pro'), 'search'=>4);
		$EM_Bookings_Table->statuses['awaiting-payment'] = array('label'=>__('Awaiting Offline Payment','em-pro'), 'search'=>5);
		$EM_Bookings_Table->statuses['needs-attention']['search'] = array(0,4,5);
		if( !get_option('dbem_bookings_approval') ){
			$EM_Bookings_Table->statuses['needs-attention']['search'] = array(5);
		}else{
			$EM_Bookings_Table->statuses['needs-attention']['search'] = array(0,5);
		}
		$EM_Bookings_Table->status = ( !empty($_REQUEST['status']) && array_key_exists($_REQUEST['status'], $EM_Bookings_Table->statuses) ) ? $_REQUEST['status']:get_option('dbem_default_bookings_search','needs-attention');
	}

	function register_gateway($gateway, $class) {
		global $EM_Gateways;
		if(!is_array($EM_Gateways)) {
			$EM_Gateways = array();
		}
		$EM_Gateways[$gateway] = new $class;
	}
		
	/**
	 * Returns an array of active gateway objects
	 * @return array
	 */
	function active_gateways() {
		global $EM_Gateways;
		$gateways = array();
		foreach($EM_Gateways as $EM_Gateway){
			if($EM_Gateway->is_active()){
				$gateways[$EM_Gateway->gateway] = $EM_Gateway->title;
			}
		}
		return $gateways;
	}
	
	/**
	 * Returns an array of all registered gateway objects
	 * @return array
	 */
	function gateways_list() {
		global $EM_Gateways;
		$gateways = array();
		foreach($EM_Gateways as $EM_Gateway){
			$gateways[$EM_Gateway->gateway] = $EM_Gateway->title;
		}
		return $gateways;
	}
	
	/**
	 * Adding a new rule, shouldn't be necessary anymore, but for backwards compatability
	 * @param array $rules
	 * @return array
	 */
	function rewrite_rules_array($rules){
		//get the slug of the event page
		$events_page_id = get_option ( 'dbem_events_page' );
		$events_page = get_post($events_page_id);
		$em_rules = array();
		if( is_object($events_page) ){
			$events_slug = preg_replace('/\/$/', '', str_replace( trailingslashit(home_url()), '', get_permalink($events_page_id)) );
			$events_slug = ( !empty($events_slug) ) ? trailingslashit($events_slug) : $events_slug;		
			$em_rules[$events_slug.'payments/(.+)$'] = 'index.php?pagename='.$events_slug.'&em_payment_gateway=$matches[1]'; //single event booking form with slug
		}else{
			$events_slug = EM_POST_TYPE_EVENT_SLUG;
			$em_rules[$events_slug.'/payments/(.+)$'] = 'index.php?post_type='.EM_POST_TYPE_EVENT.'&em_payment_gateway=$matches[1]'; //single event booking form with slug
		}
		return $em_rules + $rules;
	}
	
	/**
	 * Add the queryvars to WP_Query
	 * @param array $vars
	 * @return array
	 */
	function query_vars($vars){
		array_push($vars, 'em_payment_gateway');
	    return $vars;
	}

	/* 
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */
	
	/**
	 * Intercepted when a booking is about to be added and saved, calls the relevant booking gateway action provided gateway is provided in submitted request variables.
	 * @param EM_Event $EM_Event the event the booking is being added to
	 * @param EM_Booking $EM_Booking the new booking to be added
	 * @param boolean $post_validation
	 */
	function em_booking_add($EM_Event, $EM_Booking, $post_validation = false){
		global $EM_Gateways;
		if( array_key_exists($_REQUEST['gateway'], $EM_Gateways) ){
			//we haven't been told which gateway to use, revert to offline payment, since it's closest to pending
			$EM_Booking->booking_meta['gateway'] = addslashes($_REQUEST['gateway']);
			//Individual gateways will hook into this function
			$EM_Gateways[$_REQUEST['gateway']]->booking_add($EM_Event, $EM_Booking, $post_validation);
		}
	}
	
	/**
	 * Gets called at the bottom of the form before the submit button. 
	 * Outputs a gateway selector and allows gateways to hook in and provide their own payment information to be submitted.
	 * By default each gateway is wrapped with a div with id em-booking-gateway-x where x is the gateway for JS to work.
	 * 
	 * To prevent this from firing, call this function after the init action:
	 * remove_action('em_booking_form_footer', array('EM_Gateways','booking_form_footer'),1,2);
	 * 
	 * You'll have to ensure a gateway value is submitted in your booking form in order for paid bookings to be processed properly.
	 */
	function booking_form_footer($EM_Event){
		global $EM_Gateways;
		//Display gateway input
		if(!$EM_Event->is_free() ){
			add_action('em_gateway_js', array('EM_Gateways','em_gateway_js'));
			//Check if we can user quick pay buttons
			if( get_option('dbem_gateway_use_buttons', 1) ){ //backward compatability
				echo EM_Gateways::booking_form_buttons('',$EM_Event);
				return;
			}
			//Continue with payment gateway selection
			$active_gateways = get_option('em_payment_gateways');
			if( is_array($active_gateways) ){
				//Add gateway selector
				if( count($active_gateways) > 1 ){
				?>
				<p class="em-booking-gateway" id="em-booking-gateway">
					<label><?php echo get_option('dbem_gateway_label'); ?></label>
					<select name="gateway">
					<?php
					foreach($active_gateways as $gateway => $active_val){
						if(array_key_exists($gateway, $EM_Gateways)) {
							$selected = (!empty($selected)) ? $selected:$gateway;
							echo '<option value="'.$gateway.'">'.get_option('em_'.$gateway.'_option_name').'</option>';
						}
					}
					?>
					</select>
				</p>
				<?php
				}elseif( count($active_gateways) == 1 ){
					foreach($active_gateways as $gateway => $val){
						$selected = (!empty($selected)) ? $selected:$gateway;
						echo '<input type="hidden" name="gateway" value="'.$gateway.'" />';
					}
				}
				foreach($active_gateways as $gateway => $active_val){
					echo '<div class="em-booking-gateway-form" id="em-booking-gateway-'.$gateway.'"';
					echo ($selected == $gateway) ? '':' style="display:none;"';
					echo '>';
					$EM_Gateways[$gateway]->booking_form();
					echo "</div>";
				}
			}
		}
		return; //for filter compatibility
	}
	
	/**
	 * Cleans up Pro-added features in the database, such as deleting transactions for this booking.
	 * @param boolean $result
	 * @param EM_Booking $EM_Booking
	 * @return boolean
	 */
	function em_booking_delete($result, $EM_Booking){
		if($result){
			//TODO decouple transaction logic from gateways
			global $wpdb;
			$wpdb->query('DELETE FROM '.EM_TRANSACTIONS_TABLE." WHERE booking_id = '".$EM_Booking->booking_id."'");
		}
		return $result;
	}
	
	function em_action_booking_add($return){
		if( !empty($_REQUEST['gateway']) ){
			$return['gateway'] = $_REQUEST['gateway'];
		}
		return $return;
	}
	
	function em_gateway_js(){
		include(dirname(__FILE__).'/gateways/gateways.js');
	}

	function handle_payment_gateways($wp_query) {
		if( !empty($_REQUEST['em_payment_gateway']) || get_query_var('em_payment_gateway') != '' ) {
			$action = !empty($_REQUEST['em_payment_gateway']) ? $_REQUEST['em_payment_gateway'] : get_query_var('em_payment_gateway');
			do_action( 'em_handle_payment_return_' . $action);
			exit();
		}
	}
	
	function admin_options(){
		if( current_user_can('activate_plugins') ){
		?>
			<a name="pro-api"></a>
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Payment Gateway General Settings', 'em-pro' ); ?> </span></h3>
			<div class="inside">
				<table class='form-table'>
					<?php 
						em_options_radio_binary ( __( 'Enable Quick Pay Buttons?', 'em-pro' ), 'dbem_gateway_use_buttons', sprintf(__( 'Only works with gateways that do not require additional payment information to be submitted (e.g. PayPal and Offline payments). If enabled, the default booking form submit button is not used, and each gateway will have a button (or image, see <a href="%s">individual gateway settings</a>) which if clicked on will submit a booking for that gateway.','em-pro' ),admin_url('edit.php?post_type='.EM_POST_TYPE_EVENT.'&page=events-manager-gateways')) );
						em_options_input_text(__('Gateway Label','em-pro'),'dbem_gateway_label', __('If you are not using quick pay buttons a drop-down menu will be used, with this label.','em-pro'));
					?>
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
		<?php
		}
	}
	
	function admin_menu($plugin_pages){
		$plugin_pages[] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Payment Gateways','em-pro'),__('Payment Gateways','em-pro'),'activate_plugins','events-manager-gateways',array('EM_Gateways','handle_gateways_panel'));
		return $plugin_pages;
	}

	function handle_gateways_panel() {
		global $action, $page, $EM_Gateways, $EM_Pro;
		wp_reset_vars( array('action', 'page') );
		switch(addslashes($action)) {
			case 'edit':	
				if(isset($EM_Gateways[addslashes($_GET['gateway'])])) {
					$EM_Gateways[addslashes($_GET['gateway'])]->settings();
				}
				return; // so we don't show the list below
				break;
			case 'transactions':
				if(isset($EM_Gateways[addslashes($_GET['gateway'])])) {
					global $EM_Gateways_Transactions;
					$EM_Gateways_Transactions->output();
				}
				return; // so we don't show the list below
				break;
		}
		$messages = array();
		$messages[1] = __('Gateway updated.');
		$messages[2] = __('Gateway not updated.');
		$messages[3] = __('Gateway activated.');
		$messages[4] = __('Gateway not activated.');
		$messages[5] = __('Gateway deactivated.');
		$messages[6] = __('Gateway not deactivated.');
		$messages[7] = __('Gateway activation toggled.');
		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php _e('Edit Gateways','em-pro'); ?></h2>
			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}
			?>
			<form method="get" action="" id="posts-filter">
				<input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
				<div class="tablenav">
					<div class="alignleft actions">
						<select name="action">
							<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
							<option value="toggle"><?php _e('Toggle activation'); ?></option>
						</select>
						<input type="submit" class="button-secondary action" id="doaction" name="doaction" value="<?php _e('Apply'); ?>">		
					</div>		
					<div class="alignright actions"></div>		
					<br class="clear">
				</div>	
				<div class="clear"></div>	
				<?php
					wp_original_referer_field(true, 'previous'); wp_nonce_field('bulk-gateways');	
					$columns = array(	
						"name" => __('Gateway Name','em-pro'),
						"active" =>	__('Active','em-pro'),
						"transactions" => __('Transactions','em-pro')
					);
					$columns = apply_filters('em_gateways_columns', $columns);	
					$gateways = EM_Gateways::gateways_list();
					$active = get_option('em_payment_gateways', array());
				?>	
				<table cellspacing="0" class="widefat fixed">
					<thead>
					<tr>
					<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
						<?php
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
						?>
					</tr>
					</thead>	
					<tfoot>
					<tr>
					<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
						<?php
						reset($columns);
						foreach($columns as $key => $col) {
							?>
							<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
							<?php
						}
						?>
					</tr>
					</tfoot>
					<tbody>
						<?php
						if($gateways) {
							foreach($gateways as $key => $gateway) {
								if(!isset($EM_Gateways[$key])) {
									continue;
								}
								?>
								<tr valign="middle" class="alternate">
									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo esc_attr($key); ?>" name="gatewaycheck[]"></th>
									<td class="column-name">
										<strong><a title="Edit <?php echo esc_attr($gateway); ?>" href="<?php echo EM_ADMIN_URL; ?>&amp;page=<?php echo $page; ?>&amp;action=edit&amp;gateway=<?php echo $key; ?>" class="row-title"><?php echo esc_html($gateway); ?></a></strong>
										<?php
											$actions = array();
											$actions['edit'] = "<span class='edit'><a href='".EM_ADMIN_URL."&amp;page=" . $page . "&amp;action=edit&amp;gateway=" . $key . "'>" . __('Settings') . "</a></span>";

											if(array_key_exists($key, $active)) {
												$actions['toggle'] = "<span class='edit activate'><a href='" . wp_nonce_url(EM_ADMIN_URL."&amp;page=" . $page. "&amp;action=deactivate&amp;gateway=" . $key . "", 'toggle-gateway_' . $key) . "'>" . __('Deactivate') . "</a></span>";
											} else {
												$actions['toggle'] = "<span class='edit deactivate'><a href='" . wp_nonce_url(EM_ADMIN_URL."&amp;page=" . $page. "&amp;action=activate&amp;gateway=" . $key . "", 'toggle-gateway_' . $key) . "'>" . __('Activate') . "</a></span>";
											}
										?>
										<br><div class="row-actions"><?php echo implode(" | ", $actions); ?></div>
										</td>
									<td class="column-active">
										<?php
											if(array_key_exists($key, $active)) {
												echo "<strong>" . __('Active', 'em-pro') . "</strong>";
											} else {
												echo __('Inactive', 'em-pro');
											}
										?>
									</td>
									<td class="column-transactions">
										<a href='<?php echo EM_ADMIN_URL; ?>&amp;page=<?php echo $page; ?>&amp;action=transactions&amp;gateway=<?php echo $key; ?>'><?php _e('View transactions','em-pro'); ?></a>
									</td>
							    </tr>
								<?php
							}
						} else {
							$columncount = count($columns) + 1;
							?>
							<tr valign="middle" class="alternate" >
								<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Payment gateways where found for this install.','em-pro'); ?></td>
						    </tr>
							<?php
						}
						?>
					</tbody>
				</table>	
				<div class="tablenav">	
					<div class="alignleft actions">
						<select name="action2">
							<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
							<option value="toggle"><?php _e('Toggle activation'); ?></option>
						</select>
						<input type="submit" class="button-secondary action" id="doaction2" name="doaction2" value="Apply">
					</div>
					<div class="alignright actions"></div>
					<br class="clear">
				</div>
			</form>

		</div> <!-- wrap -->
		<?php
	}
			
	function handle_gateways_panel_updates() {	
		global $action, $page, $EM_Gateways;	
		wp_reset_vars ( array ('action', 'page' ) );
		$request = $_REQUEST;
		if (isset ( $_REQUEST ['doaction'] ) || isset ( $_REQUEST ['doaction2'] )) {
			if ( (!empty($_GET ['action']) && addslashes ( $_GET ['action'] ) == 'toggle') || (!empty( $_GET ['action2']) && addslashes ( $_GET ['action2'] ) == 'toggle') ) {
				$action = 'bulk-toggle';
			}
		}	
		if( !empty($_REQUEST['gateway']) || !empty($_REQUEST['bulk-gateways']) ){
			switch (addslashes ( $action )) {		
				case 'deactivate' :
					$key = addslashes ( $_REQUEST ['gateway'] );
					if (isset ( $EM_Gateways [$key] )) {
						if ($EM_Gateways [$key]->deactivate ()) {
							wp_safe_redirect ( add_query_arg ( 'msg', 5, wp_get_referer () ) );
						} else {
							wp_safe_redirect ( add_query_arg ( 'msg', 6, wp_get_referer () ) );
						}
					}
					break;		
				case 'activate' :
					$key = addslashes ( $_REQUEST ['gateway'] );
					if (isset ( $EM_Gateways[$key] )) {
						if ($EM_Gateways[$key]->activate ()) {
							wp_safe_redirect ( add_query_arg ( 'msg', 3, wp_get_referer () ) );
						} else {
							wp_safe_redirect ( add_query_arg ( 'msg', 4, wp_get_referer () ) );
						}
					}
					break;		
				case 'bulk-toggle' :
					check_admin_referer ( 'bulk-gateways' );
					foreach ( $_REQUEST ['gatewaycheck'] as $key ) {
						if (isset ( $EM_Gateways [$key] )) {					
							$EM_Gateways [$key]->toggleactivation ();				
						}
					}
					wp_safe_redirect ( add_query_arg ( 'msg', 7, wp_get_referer () ) );
					break;		
				case 'updated' :
					$gateway = addslashes ( $_REQUEST ['gateway'] );		
					check_admin_referer ( 'updated-'.$EM_Gateways[$gateway]->gateway );
					if ($EM_Gateways[$gateway]->update ()) {
						wp_safe_redirect ( add_query_arg ( 'msg', 1, EM_ADMIN_URL.'&page=' . $page ) );
					} else {
						wp_safe_redirect ( add_query_arg ( 'msg', 2, EM_ADMIN_URL.'&page=' . $page ) );
					}			
					break;
			}
		}
	}		

	/*
	 * --------------------------------------------------
	 * BUTTONS MODE Functions - i.e. booking doesn't require gateway selection, just button click
	 * --------------------------------------------------
	 */

	/**
	 * This gets called when a booking form created using the old buttons API, and calls subsequent gateways to output their buttons.
	 * @param string $button
	 * @param EM_Event $EM_Event
	 * @return string
	 */
	function booking_form_buttons($button, $EM_Event){
		global $EM_Gateways;
		$gateway_buttons = array();
		$active_gateways = get_option('em_payment_gateways');
		if( is_array($active_gateways) ){
			foreach($active_gateways as $gateway => $active_val){
				if(array_key_exists($gateway, $EM_Gateways) && $EM_Gateways[$gateway]->button_enabled) {
					$gateway_button = $EM_Gateways[$gateway]->booking_form_button();
					if(!empty($gateway_button)){
						$gateway_buttons[$gateway] = $gateway_button;
					}
				}
			}
			$gateway_buttons = apply_filters('em_gateway_buttons', $gateway_buttons, $EM_Event);
			if( count($gateway_buttons) > 0 ){
				$button = '<div class="em-gateway-buttons"><div class="em-gateway-button first">'. implode('</div><div class="em-gateway-button">', $gateway_buttons).'</div></div>';			
			}
		}
		if($button != '') $button .= '<style type="text/css">input.em-booking-submit { display:none; } .em-gateway-button input.em-booking-submit { display:block; }</style>'; //hide normal button if we have buttons
		return apply_filters('em_gateway_booking_form_buttons', $button, $gateway_buttons);
	}
}
EM_Gateways::init();
function emp_register_gateway($gateway, $class) { EM_Gateways::register_gateway($gateway, $class); } //compatibility, use EM_Gateways directly
}

include('gateways/gateway.php');
include('gateways/gateways.transactions.php');
include('gateways/gateway.paypal.php');
include('gateways/gateway.offline.php');
include('gateways/gateway.authorize.aim.php');