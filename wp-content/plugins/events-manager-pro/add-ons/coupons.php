<?php
include('coupon.php');
class EM_Coupons extends EM_Object {
	function init(){
		//add coupon to admin menu
		//coupon admin select page
		//coupon admin add/edit page
		add_action('em_create_events_submenu',array('EM_Coupons', 'admin_menu'),10,1);
		//meta box hook for adding coupons to booking info
		add_action('em_events_admin_bookings_footer',array('EM_Coupons', 'admin_meta_box'),10,1);
		add_action('em_event',array('EM_Coupons', 'em_event'),10,1);
		add_filter('em_event_get_post',array('EM_Coupons', 'em_event_get_post'),10,2);
		add_filter('em_event_save_meta',array('EM_Coupons', 'em_event_save_meta'),10,2);
		//add field to booking form and ajax
		add_action('em_booking_form_footer', array('EM_Coupons', 'em_booking_form_footer'),1,2);
		//hook into booking submission to add discount and coupon info
		add_filter('em_booking_get_post', array('EM_Coupons', 'em_booking_get_post'), 10, 2);
		add_filter('em_booking_validate', array('EM_Coupons', 'em_booking_validate'), 10, 2);
		add_filter('em_booking_save', array('EM_Coupons', 'em_booking_save'), 10, 2);
		//hook into paypal gateway
		add_filter('em_gateway_paypal_get_paypal_vars', array('EM_Coupons', 'paypal_vars'), 10, 2);
		//hook into price calculator
		add_filter('em_booking_get_price', array('EM_Coupons', 'em_booking_get_price'), 10, 2);
		//add coupon code info to individual booking
		add_action('em_bookings_admin_ticket_totals_header', array('EM_Coupons', 'em_bookings_admin_ticket_totals_header'), 10, 2);
		//add coupon info to CSV
		add_action('em_csv_bookings_loop_after', array('EM_Coupons', 'em_csv_bookings_loop_after'),1,3); //show booking form and ticket summary
		add_action('em_csv_bookings_headers', array('EM_Coupons', 'em_csv_bookings_headers'),1,1); //show booking form and ticket summary
		//add ajax response for coupon code queries
		add_action('wp_ajax_coupon_check',array('EM_Coupons', 'coupon_check'));
		add_action('wp_ajax_nopriv_coupon_check',array('EM_Coupons', 'coupon_check'));
		//add css for coupon field
		add_action('wp_head',array('EM_Coupons', 'wp_head'));
	}
	
	function em_bookings_admin_ticket_totals_header(){
		global $EM_Booking;
		//since a code COULD have been deleted, let's make up the info this way
		if( !empty($EM_Booking->booking_meta['coupon']) ){
			$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']); //don't use the db, just give the array to create a coupon
			?>
			<tr>
				<th><?php _e('Original Total Price','dbem'); ?></th>
				<th>&nbsp;</th>
				<th><?php echo em_get_currency_formatted($EM_Booking->booking_meta['original_price']); ?></th>
			</tr>
			<tr>
				<th><?php _e('Coupon Discount','dbem'); ?></th>
				<th><?php echo $EM_Coupon->get_discount_text(); ?></th>
				<th>- <?php echo em_get_currency_formatted($EM_Booking->booking_meta['original_price'] - $EM_Booking->get_price()); ?></th>
			</tr>
			<?php
		}
	}
	
	function em_booking_get_price( $price, $EM_Booking ){
		if( !empty($EM_Booking->booking_meta['coupon']) ){
			//get coupon and calculate price
			$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
			if( $EM_Booking->booking_price == $EM_Booking->booking_meta['original_price'] ){
				return $EM_Coupon->apply_discount($price);
			}
		}
		return $price;
	}
	
	/*
	 * MODIFYING BOOKING SUBMISSION Functions 
	 */	
	/**
	 * @param int $code
	 * @param EM_Event $EM_Event
	 * @return EM_Coupon|boolean
	 */
	function verify_code($code, $EM_Event){
		foreach($EM_Event->coupons as $EM_Coupon){
			if($EM_Coupon->coupon_code == $code){
				return $EM_Coupon;
			}
		}
		return false;
	}
	
	function em_booking_get_post($result, $EM_Booking){
		if( !empty($_REQUEST['coupon_code']) ){
			$EM_Coupon = EM_Coupons::verify_code($_REQUEST['coupon_code'], $EM_Booking->get_event());
			if( $EM_Coupon !== false ){
				$EM_Booking->booking_meta['coupon'] = $EM_Coupon->to_array(); //we add an clean a coupon array here for the first time
			}else{	
				$EM_Booking->add_error(__('Invalid coupon code provided','em-pro'));
			}
		}
		$result = self::em_booking_validate($result, $EM_Booking); //validate here as well
		return $result;
	}
	
	function em_booking_validate($result, $EM_Booking){
		if( !empty($EM_Booking->booking_meta['coupon']) ){
			$EM_Coupon = self::verify_code($EM_Booking->booking_meta['coupon']['coupon_code'], $EM_Booking->get_event());
			if( $EM_Coupon === false || !$EM_Coupon->is_valid() ){
				$EM_Booking->add_error(__('Invalid coupon code provided','em-pro'));
				return false;
			}elseif( $EM_Coupon !== false && empty($EM_Booking->booking_meta['original_price']) ){
				$EM_Booking->booking_meta['original_price'] = $EM_Booking->booking_price; 
				$EM_Booking->booking_price = $EM_Coupon->apply_discount($EM_Booking->booking_price); //double check price
			}
		}
		return $result;
	}
	
	function em_booking_save($result, $EM_Booking){
		if( $result && !empty($EM_Booking->booking_meta['coupon']) ){
			global $wpdb;
			$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
			$count = $EM_Coupon->get_count();
			if( $count ){
				//add to coupon count
				$wpdb->update(EM_META_TABLE, array('meta_value'=>$count+1), array('object_id'=>$EM_Coupon->coupon_id, 'meta_key'=>'coupon-count'));
			}else{
				//start coupon count
				$wpdb->insert(EM_META_TABLE, array('meta_value'=>1, 'object_id'=>$EM_Coupon->coupon_id, 'meta_key'=>'coupon-count'));
			}			
		}
		return $result;
	}
	
	function paypal_vars($vars, $EM_Booking){
		if(!empty($EM_Booking->booking_meta['coupon'])){
			$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
			$discount = $EM_Booking->booking_meta['original_price'] - $EM_Booking->get_price();
			$vars['discount_amount_cart'] = $discount;
		}
		return $vars;
	}
	
	/*
	 * ADMIN AREA Functions
	 */
	
	function admin_menu($plugin_pages){
		$plugin_pages[] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Coupons','em-pro'),__('Coupons Manager','em-pro'),'manage_others_bookings','events-manager-coupons',array('EM_Coupons','admin_page'));
		return $plugin_pages; //use wp action/filters to mess with the menus
	}

	function em_event($EM_Event){
		//Get coupons for this event
		global $wpdb;
		$EM_Event->coupons = array();
		if( !empty($EM_Event->event_id) ){
			 //get coupons that are event and sitewide
			 $coupon_ids = $wpdb->get_col("SELECT coupon_id FROM ".EM_COUPONS_TABLE." WHERE (coupon_eventwide=1 AND coupon_owner='{$EM_Event->event_owner}') OR coupon_sitewide=1");
			 //get coupons associated with this event
			 $coupon_ids = array_merge($coupon_ids, $wpdb->get_col("SELECT meta_value FROM ".EM_META_TABLE." WHERE object_id='{$EM_Event->event_id}' AND meta_key='event-coupon'"));
			 //get coupon objects
			 if( is_array($coupon_ids) && count($coupon_ids) > 0 ){
			 	$EM_Event->coupons = EM_Coupons::get($coupon_ids);
			 }
		}
	}
	
	function em_event_get_post($result, $EM_Event){
		$EM_Event->coupons = array();
		if(!empty($_REQUEST['em_coupons']) && is_array($_REQUEST['em_coupons'])){
		 	$EM_Event->coupons = EM_Coupons::get($_REQUEST['em_coupons']);
		}
		return $result;
	}
	
	function em_event_save_meta($result, $EM_Event){
		global $wpdb;
		if($result){
			$wpdb->query("DELETE FROM ".EM_META_TABLE." WHERE meta_key='event-coupon' AND object_id=".$EM_Event->event_id);
			foreach($EM_Event->coupons as $EM_Coupon){
				//save record of coupons
				$wpdb->insert(EM_META_TABLE, array('object_id'=>$EM_Event->event_id, 'meta_key'=>'event-coupon', 'meta_value'=>$EM_Coupon->coupon_id));
			}
		}
		return $result;
	}
	
	function em_booking_form_footer($EM_Event){
		if( count($EM_Event->coupons) > 0 ){
			?>
			<p>
				<label><?php _e('Coupon Code','em-pro'); ?></label>
				<input type="text" name="coupon_code" id="em-coupon-code" />
			</p>
			<?php
			add_action('wp_footer', array('EM_Coupons', 'wp_footer') );
		}
	}
	
	function wp_head(){
		//override this with CSS in your own theme
		?>
		<style type="text/css">
			#em-coupon-code { width:150px; }
			#em-coupon-loading { display:inline-block; width:16px; height: 16px; margin-left:4px; background:url(<?php echo plugins_url('events-manager-pro/includes/images/spinner.gif','events-manager-pro'); ?>)}
			.em-coupon-message { display:inline-block; margin:5px 0px 0px 105px; text-indent:22px; }
			.em-coupon-success { color:green; background:url(<?php echo plugins_url('events-manager-pro/includes/images/success.png','events-manager-pro'); ?>) 0px 0px no-repeat }
			.em-coupon-error { color:red; background:url(<?php echo plugins_url('events-manager-pro/includes/images/error.png','events-manager-pro'); ?>) 0px 0px no-repeat }
		</style>
		<?php
	}
	
	function wp_footer(){
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('#em-coupon-code').change(function(){
					var formdata = $('#em-booking-form').serialize().replace('action=booking_add','action=coupon_check'); //simple way to change action of form
					$.ajax({
						url: EM.ajaxurl,
						data: formdata,
						dataType: 'jsonp',
						type:'post',
						beforeSend: function(formData, jqForm, options) {
							$('.em-coupon-message').remove();
							if($('#em-coupon-code').val() == ''){ return false; }
							$('#em-coupon-code').after('<span id="em-coupon-loading"></span>');
						},
						success : function(response, statusText, xhr, $form) {
							if(response.result){
								$('#em-coupon-code').after('<span class="em-coupon-message em-coupon-success">'+response.message+'</span>');
							}else{
								$('#em-coupon-code').after('<span class="em-coupon-message em-coupon-error">'+response.message+'</span>');
							}
						},
						complete : function() {
							$('#em-coupon-loading').remove();
						}
					});
				});
			});
		</script>
		<?php
	}
	
	function coupon_check(){
		if( !empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'booking_add') && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'coupon_check' ){ //only run this when booking add form submitted and action is modified
			$result = array('result'=>false, 'message'=> __('Coupon Not Found', 'em-pro'));
			if(!empty($_REQUEST['event_id'])){
				$EM_Event = new EM_Event($_REQUEST['event_id']);
				foreach($EM_Event->coupons as $EM_Coupon){
					if( $EM_Coupon->coupon_code == $_REQUEST['coupon_code'] ){
						if( $EM_Coupon->is_valid() ){
							$result['result'] = true;
							$result['message'] = $EM_Coupon->get_discount_text();
						}else{
							$result['message'] = __('Coupon Invalid','em-pro');
						}
						break;
					}
				}
			}
			echo EM_Object::json_encode($result);
			exit();
		}
	}
	
	/**
	 * @param EM_Event $EM_Event
	 */
	function admin_meta_box($EM_Event){
		//Get available coupons for user
		global $wpdb;
		if( $EM_Event->event_owner == get_current_user_id() ){
			$coupons = EM_Coupons::get(array('owner'=>get_current_user_id()));
		}elseif( current_user_can('manage_others_bookings') ){
			//get owner's coupons
			$coupons = EM_Coupons::get(array('owner'=>$EM_Event->event_owner));			
		}
		$global_coupons = array();
		?>
		<p><strong><?php _e('Coupons','em-pro'); ?></strong></p>
		<p><em><?php _e('Coupons selected here will be applied to bookings made for this event.','em-pro'); ?></em></p>
		<div>	
		<?php if(count($coupons) > 0): foreach($coupons as $EM_Coupon): /* @var $EM_Coupon EM_Coupon */ ?> 
			<?php if( !$EM_Coupon->coupon_eventwide && !$EM_Coupon->coupon_sitewide ): ?>  
				<label>
					<input type="checkbox" name="em_coupons[]" value="<?php echo $EM_Coupon->coupon_id; ?>" <?php if(array_key_exists($EM_Coupon->coupon_id, $EM_Event->coupons)) echo 'checked="checked"'; ?>/>
					<strong><?php echo $EM_Coupon->coupon_code; ?></strong> (<em><?php echo esc_html($EM_Coupon->coupon_name .' - '. $EM_Coupon->coupon_description); ?></em>) - <?php echo $EM_Coupon->get_discount_text(); ?>
				</label><br />
			<?php else: $global_coupons[] = $EM_Coupon; endif; ?>
		<?php endforeach; else: ?>
			<?php _e('No coupons created yet.','em-pro'); ?>
		<?php endif; ?>
		<?php if(count($global_coupons) > 0): ?>
			<p><em><?php _e('The following codes will be automatically available to this event as well')?></em></p> 
			<?php foreach($global_coupons as $EM_Coupon): /* @var $EM_Coupon EM_Coupon */ ?>
				<p style="margin:0px 0px 5px 0px">
					<?php echo '<strong>'.esc_html($EM_Coupon->coupon_code).'</strong> - '. esc_html($EM_Coupon->get_discount_text()); ?><br />
					<em><?php echo esc_html($EM_Coupon->coupon_name .' - '. $EM_Coupon->coupon_description); ?></em>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
		<br style="clear" />
		<?php
	}
	
	/**
	 * Returns an array of EM_Coupon objects
	 * @param boolean $eventful
	 * @param boolean $return_objects
	 * @return array
	 */
	function get( $args = array(), $count=false ){
		global $wpdb;
		$coupons_table = EM_COUPONS_TABLE;
		$coupons = array();
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "SELECT * FROM $coupons_table WHERE coupon_id IN (".implode(",", $args).")";
			$results = $wpdb->get_results($sql,ARRAY_A);
			foreach($results as $result){
				$coupons[$result['coupon_id']] = new EM_Coupon($result);
			}
			return apply_filters('em_coupons_get', $coupons, $args); //We return all the events matched as an EM_Event array. 
		}	

		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get the default conditions
		$conditions = self::build_sql_conditions($args);
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Get ordering instructions
		$orderby = array('coupon_name','coupon_code');
		//Now, build orderby sql
		$orderby_sql = ( count($orderby) > 0 ) ? 'ORDER BY '. implode(', ', $orderby) : '';
		
		$selectors = ( $count ) ? 'COUNT(*)':'*';
		//Create the SQL statement and execute
		$sql = "
			SELECT $selectors FROM $coupons_table
			$where
			$orderby_sql
			$limit $offset
		";
			
		//If we're only counting results, return the number of results
		if( $count ){
			return apply_filters('em_coupons_get_array', $wpdb->get_var($sql), $args);	
		}
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return apply_filters('em_coupons_get_array', $results, $args);
		}
		
		foreach ( $results as $coupon ){
			$coupons[$coupon['coupon_id']] = new EM_Coupon($coupon);
		}
		return apply_filters('em_coupons_get', $coupons, $args);
	}
	
	function count($args = array() ){
		return self::get($args, true);
	}
	
	function admin_page($args = array()){
		global $EM_Coupon, $EM_Notices;
		//load coupon if necessary
		$EM_Coupon = !empty($_REQUEST['coupon_id']) ? new EM_Coupon($_REQUEST['coupon_id']) : new EM_Coupon();
		//save coupon if necessary
		if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'coupon_save' && wp_verify_nonce($_REQUEST['_wpnonce'], 'coupon_save') ){
			if ( $EM_Coupon->get_post() && $EM_Coupon->save() ) {
				//Success notice
				$EM_Notices->add_confirm( $EM_Coupon->feedback_message );
			}else{
				$EM_Notices->add_error( $EM_Coupon->get_errors() );
			}
		}
		//Delete if necessary
		if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'coupon_delete' && wp_verify_nonce($_REQUEST['_wpnonce'], 'coupon_delete_'.$EM_Coupon->coupon_id) ){
			if ( $EM_Coupon->delete() ) { 
				$EM_Notices->add_confirm( $EM_Coupon->feedback_message );
			}else{
				$EM_Notices->add_error( $EM_Coupon->get_errors() );
			}
		}
		if( !empty($_GET['action']) && $_GET['action']=='edit' ){
			if( empty($_REQUEST['redirect_to']) ){
				$_REQUEST['redirect_to'] = em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>null, 'coupon_id'=>null));
			}
			self::edit_form();
		}else{
			self::select_page();
		}
	}

	function select_page() {
		global $wpdb, $EM_Pro, $EM_Notices;
		$url = empty($url) ? $_SERVER['REQUEST_URI']:$url; //url to this page
		$limit = ( !empty($_REQUEST['limit']) && is_numeric($_REQUEST[ 'limit']) ) ? $_REQUEST['limit'] : 20;//Default limit
		$page = ( !empty($_REQUEST['pno']) ) ? $_REQUEST['pno']:1;
		$offset = ( $page > 1 ) ? ($page-1)*$limit : 0;
		$args = array('limit'=>$limit, 'offset'=>$offset);
		if( !empty($_REQUEST['view']) && $_REQUEST['view'] == 'others' && current_user_can('manage_others_bookings') ){
			$coupons = self::get($args);
		}else{
			$coupons = self::get( array_merge($args, array('owner'=>get_current_user_id())) );
		}
		$coupons_count = count($coupons);
		$coupons_mine_count = self::count( array('owner'=>get_current_user_id()) );
		$coupons_all_count = current_user_can('manage_others_bookings') ? self::count():0;
		?>
		<div class='wrap'>
			<div class="icon32" id="icon-bookings"><br></div>
			<h2><?php _e('Edit Coupons','em-pro'); ?>
				<a href="<?php echo add_query_arg(array('action'=>'edit')); ?>" class="add-new-h2"><?php _e('Add New','dbem'); ?></a>
			</h2>
			<?php echo $EM_Notices; ?>
			<form id='coupons-filter' method='post' action=''>
				<input type='hidden' name='pno' value='<?php echo $page ?>' />
				<div class="subsubsub">
					<a href='<?php echo em_add_get_params($_SERVER['REQUEST_URI'], array('view'=>null, 'pno'=>null)); ?>' <?php echo ( empty($_REQUEST['view']) ) ? 'class="current"':''; ?>><?php echo sprintf( __( 'My %s', 'dbem' ), __('Coupons','em-pro')); ?> <span class="count">(<?php echo $coupons_mine_count; ?>)</span></a>
					<?php if( current_user_can('manage_others_bookings') ): ?>
					&nbsp;|&nbsp;
					<a href='<?php echo em_add_get_params($_SERVER['REQUEST_URI'], array('view'=>'others', 'pno'=>null)); ?>' <?php echo ( !empty($_REQUEST['view']) && $_REQUEST['view'] == 'others' ) ? 'class="current"':''; ?>><?php echo sprintf( __( 'All %s', 'dbem' ), __('Coupons','em-pro')); ?> <span class="count">(<?php echo $coupons_all_count; ?>)</span></a>
					<?php endif; ?>
				</div>		
				<?php
				if ( $coupons_count >= $limit ) {
					$coupons_nav = em_admin_paginate( $coupons_count, $limit, $page );
					echo $coupons_nav;
				}
				?>				
				<?php if ( $coupons_count > 0 ) : ?>
				<table class='widefat'>
					<thead>
						<tr>
							<th><?php _e('Name', 'em-pro') ?></th>
							<th><?php _e('Code', 'em-pro') ?></th>
							<th><?php _e('Created By', 'em-pro') ?></th>
							<th><?php _e('Description', 'em-pro') ?></th>  
							<th><?php _e('Discount', 'em-pro') ?></th>                
						</tr> 
					</thead>
					<tfoot>
						<tr>
							<th><?php _e('Name', 'em-pro') ?></th>
							<th><?php _e('Code', 'em-pro') ?></th>
							<th><?php _e('Created By', 'em-pro') ?></th>
							<th><?php _e('Description', 'em-pro') ?></th>  
							<th><?php _e('Discount', 'em-pro') ?></th>      
						</tr>             
					</tfoot>
					<tbody>
						<?php $i = 1; ?>
						<?php foreach ($coupons as $EM_Coupon) : ?>	
							<?php if( $i >= $offset && $i <= $offset+$limit ): ?>
								<tr>
									<td>
										<a href='<?php echo admin_url('edit.php?post_type='.EM_POST_TYPE_EVENT.'&amp;page=events-manager-coupons&amp;action=edit&amp;coupon_id='.$EM_Coupon->coupon_id); ?>'><?php echo $EM_Coupon->coupon_name ?></a>
										<div class="row-actions">
											<span class="trash"><a class="submitdelete" href="<?php echo add_query_arg(array('coupon_id'=>$EM_Coupon->coupon_id,'action'=>'coupon_delete','_wpnonce'=>wp_create_nonce('coupon_delete_'.$EM_Coupon->coupon_id))) ?>"><?php _e('Delete','em-pro')?></a></span>
										</div>
									</td>
									<td><?php echo esc_html($EM_Coupon->coupon_code); ?></td>
									<td><a href="<?php echo admin_url('user-edit.php?user_id='.$EM_Coupon->get_person()->ID); ?>"><?php echo $EM_Coupon->get_person()->get_name(); ?></a></td>
									<td><?php echo esc_html($EM_Coupon->coupon_description); ?></td>  
									<td><?php echo esc_html($EM_Coupon->coupon_discount); ?></td>                             
								</tr>
							<?php endif; ?>
							<?php $i++; ?> 
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else: ?>
				<br class="clear" />
				<p><?php _e('No coupons have been inserted yet!', 'dbem') ?></p>
				<?php endif; ?>
				
				<?php if ( !empty($coupons_nav) ) echo $coupons_nav; ?>
			</form>

		</div> <!-- wrap -->
		<?php
	}
	
	function edit_form(){
		global $EM_Notices, $EM_Coupon;
		$EM_Coupon = ( is_object($EM_Coupon) && get_class($EM_Coupon) == 'EM_Coupon') ? $EM_Coupon : new EM_Coupon();
		//check that user can access this page
		if( is_object($EM_Coupon) && !$EM_Coupon->can_manage('edit_locations','edit_others_locations') ){
			?>
			<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php echo sprintf(__('You do not have the rights to manage this %s.','dbem'),__('coupon','dbem')); ?></p></div>
			<?php
			return false;
		}elseif( !is_object($EM_Coupon) ){
			$EM_Coupon = new EM_Coupon();
		}
		$required = "<i>(".__('required','dbem').")</i>";
		?>
		<div class='wrap nosubsub'>
			<div class="icon32" id="icon-bookings"><br></div>
			<h2><?php _e('Edit Coupon','em-pro'); ?></h2>
			<?php echo $EM_Notices; ?>
			<form id='coupon-form' method='post' action=''>
				<input type='hidden' name='action' value='coupon_save' />
				<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('coupon_save'); ?>' />
				<input type='hidden' name='coupon_id' value='<?php echo $EM_Coupon->coupon_id ?>'/>
				<table class="form-table">
					<tbody>
					<tr valign="top">
						<th scope="row"><?php _e('Event-Wide Coupon?', 'em-pro') ?></th>
							<td><input type="checkbox" name="coupon_eventwide" value="1" <?php if($EM_Coupon->coupon_eventwide) echo 'checked="checked"'; ?> />
							<br />
							<em><?php _e('If checked, all events belonging to you will accept this coupon.','em-pro'); ?></em>
						</td>
					</tr>
					<?php if( current_user_can('manage_others_bookings') || is_super_admin() ): ?>
					<tr valign="top">
						<th scope="row"><?php _e('Site-Wide Coupon?', 'em-pro') ?></th>
							<td><input type="checkbox" name="coupon_sitewide" value="1" <?php if($EM_Coupon->coupon_sitewide) echo 'checked="checked"'; ?> />
							<br />
							<em><?php _e('All events on this site will accept this coupon.','em-pro'); ?></em>
						</td>
					</tr>
					<?php endif; ?>
					<tr valign="top">
						<th scope="row"><?php _e('Registerd Users Only?', 'em-pro') ?></th>
							<td><input type="checkbox" name="coupon_private" value="1" <?php if($EM_Coupon->coupon_private) echo 'checked="checked"'; ?> />
							<br />
							<em><?php _e('If checked, only logged in users will be able to use this coupon.','em-pro'); ?></em>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Coupon Code', 'em-pro') ?></th>
							<td><input type="text" name="coupon_code" value="<?php echo esc_attr($EM_Coupon->coupon_code); ?>" />
							<br />
							<em><?php _e('This is the code you give to users for them to use when booking.','em-pro'); ?></em>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Name', 'em-pro') ?></th>
							<td><input type="text" name="coupon_name" value="<?php echo esc_attr($EM_Coupon->coupon_name); ?>" />
							<br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Description', 'em-pro') ?></th>
							<td><input type="text" name="coupon_description" value="<?php echo esc_attr($EM_Coupon->coupon_description); ?>" />
							<br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Total Coupons', 'em-pro') ?></th>
							<td><input type="text" name="coupon_max" value="<?php echo esc_attr($EM_Coupon->coupon_max); ?>" />
							<br />
							<em><?php _e('If set, this coupon will only be valid that many times.','em-pro'); ?></em>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Start Date', 'em-pro') ?></th>
						<td>
							<input type="hidden"  id="em-date-start" name="coupon_start" value="<?php echo esc_attr($EM_Coupon->coupon_start); ?>" />
							<input type="text" id="em-date-start-loc" />
							<br />
							<em><?php _e('Coupons will only be valid from this date onwards.','em-pro'); ?></em>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('End Date', 'em-pro') ?></th>
						<td>
							<input type="hidden" id="em-date-end" name="coupon_end" value="<?php echo esc_attr($EM_Coupon->coupon_end); ?>" />
							<input type="text" id="em-date-end-loc" />
							<br />
							<em><?php _e('Coupons not be valid after this date.','em-pro'); ?></em>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Discount Type', 'em-pro') ?></th>
						<td>
							<select name="coupon_type">
								<option value="%" <?php echo ($EM_Coupon->coupon_type == '%')?'selected="selected"':''; ?>><?php _e('Percentage'); ?></option>
								<option value="#" <?php echo ($EM_Coupon->coupon_type == '#')?'selected="selected"':''; ?>><?php _e('Fixed Amount'); ?></option>
							</select>
							<br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Discount Amount', 'em-pro') ?></th>
							<td><input type="text" name="coupon_discount" value="<?php echo esc_attr($EM_Coupon->coupon_discount); ?>" />
							<br />
							<em><?php _e('Enter a number here only, decimals accepted.','em-pro'); ?></em>
						</td>
					</tr>
					</tbody>
				</table>				
				<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>
		</div> <!-- wrap -->
		<?php
	}
	
	/*
	 * CSV Functions
	 */
	
	function em_csv_bookings_headers($headers){
		$headers[] = __('Applicable Coupon');
		return $headers; //no filter needed, use the em_csv_bookings_headers filter instead
	}
	
	function em_csv_bookings_loop_after($file, $EM_Ticket_Booking, $EM_Booking){
		if( !empty($EM_Booking->booking_meta['coupon']) ){
			$EM_Coupon = self::verify_code($EM_Booking->booking_meta['coupon']['coupon_code'], $EM_Booking->get_event());
			$file .= '"' .  preg_replace("/\n\r|\r\n|\n|\r/", ".     ", $EM_Coupon->coupon_name) . '",'; 
		}
		return $file; //no filter needed, use the em_csv_bookings_loop_after filter instead
	}

	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_conditions()
	 */
	function build_sql_conditions( $args = array() ){
		$conditions = array();
		//blog ownership
		if( is_multisite() && array_key_exists('blog',$args) && is_numeric($args['blog']) ){
			if( is_main_site($args['blog']) ){
				$conditions['blog'] = "(".EM_COUPONS_TABLE.".blog_id={$args['blog']} OR ".EM_COUPONS_TABLE.".blog_id IS NULL)";
			}else{
				$conditions['blog'] = "(".EM_COUPONS_TABLE.".blog_id={$args['blog']})";
			}
		}
		//owner lookup
		if( !empty($args['owner']) && is_numeric($args['owner'])){
			$conditions['owner'] = "coupon_owner=".$args['owner'];
		}
		//site/event-wide lookups
		if( !empty($args['sitewide']) ){
			$conditions['sitewide'] = "coupon_sitewide=1";
			if( !empty($args['eventwide']) ) $conditions['eventwide'] = "coupon_eventwide=1";
		}elseif( !empty($args['eventwide']) ){
			$conditions['eventwide'] = "coupon_eventwide=1";
		}
		return apply_filters( 'em_coupons_build_sql_conditions', $conditions, $args );
	}
	
	/* 
	 * Adds custom Events search defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	function get_default_search( $array = array() ){
		$defaults = array(
			'sitewide' => 0,
			'eventwide' => 0,
		);
		return apply_filters('em_events_get_default_search', parent::get_default_search($defaults,$array), $array, $defaults);
	}
}
EM_Coupons::init();