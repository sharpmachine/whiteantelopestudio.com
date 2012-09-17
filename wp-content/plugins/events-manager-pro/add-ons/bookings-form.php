<?php
class EM_Booking_Form {
	static $validate;
	/**
	 * @var EM_Form
	 */
	static $form;
	static $form_id;
	static $form_name;
	static $form_template;
	
	function init(){	
		//Menu/admin page
		add_action('admin_init',array('EM_Booking_Form', 'admin_page_actions'), 10);
		add_action('emp_forms_admin_page',array('EM_Booking_Form', 'admin_page'),10);
		//add_action('em_bookings_ticket_footer', array('EM_Booking_Form', 'ticket_meta_box'),1,1); //specific ticket booking info
		add_action('em_bookings_single_custom', array('EM_Booking_Form', 'em_bookings_single_custom'),1,1); //show booking form and ticket summary
		add_action('em_csv_bookings_loop_after', array('EM_Booking_Form', 'em_csv_bookings_loop_after'),1,3); //show booking form and ticket summary
		add_action('em_csv_bookings_headers', array('EM_Booking_Form', 'em_csv_bookings_headers'),1,1); //show booking form and ticket summary
		//Booking Tables UI
		add_filter('em_bookings_table_rows_col', array('EM_Booking_Form','em_bookings_table_rows_col'),10,5);
		add_filter('em_bookings_table_cols_template', array('EM_Booking_Form','em_bookings_table_cols_template'),10,2);
		// Actions and Filters
		add_filter('em_booking_form_custom', array('EM_Booking_Form','booking_form'),10,2); //handle the booking form template
		add_filter('em_booking_form_js', array('EM_Booking_Form','js'),10,2); //JS, so we can handle the ajax return differently
		add_filter('em_booking_form_tickets_loop', array('EM_Booking_Form','ticket_form'),10,2); 
		//Booking interception
		//add_filter('em_booking_add', array('EM_Booking_Form', 'em_booking_add'), 1, 2); //called only when bookin is added
		add_filter('em_booking_get_post_pre', array('EM_Booking_Form', 'em_booking_get_post_pre'), 1, 1); //turns on flag so we know not to double validate
		add_filter('em_booking_get_post', array('EM_Booking_Form', 'em_booking_get_post'), 1, 2); //get post data + validate
		add_filter('em_booking_validate', array('EM_Booking_Form', 'em_booking_validate'), 1, 2); //validate object
		add_filter('em_bookings_add', array('EM_Booking_Form', 'em_bookings_add'), 1, 1); //add extra use reg data
		add_filter('em_register_new_user_pre', array('EM_Booking_Form', 'em_register_new_user_pre'), 10, 1); //add extra use reg data
		//Placeholder overriding	
		add_filter('em_booking_output_placeholder',array('EM_Booking_Form','placeholders'),1,3); //for emails
		//custom form chooser in event bookings meta box:
		add_action('em_events_admin_bookings_footer',array('EM_Booking_Form', 'event_bookings_meta_box'),20,1);
		add_filter('em_event_save_meta',array('EM_Booking_Form', 'em_event_save_meta'),10,2);
		self::$form_template = array (
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
		);
	}
	
	/**
	 * @param EM_Booking $EM_Booking
	 */
	function get_form($EM_Event = false){
		if( empty(self::$form) ){
			global $wpdb;
			if(is_numeric($EM_Event)){ $EM_Event = new EM_Event($EM_Event); }
			$custom_form_id = ( !empty($EM_Event->post_id) ) ? get_post_meta($EM_Event->post_id, '_custom_booking_form', true):0;
			$form_id = empty($custom_form_id) ? get_option('em_booking_form_fields') : $custom_form_id;
			$sql = $wpdb->prepare("SELECT meta_id, meta_value FROM ".EM_META_TABLE." WHERE meta_key = 'booking-form' AND meta_id=%d", $form_id);
			$form_data_row = $wpdb->get_row($sql, ARRAY_A);
			if( empty($form_data_row) ){
				$form_data = self::$form_template;
				self::$form_name = __('Default','em-pro');
			}else{
				$form_data = unserialize($form_data_row['meta_value']);
				self::$form_id = $form_data_row['meta_id'];
				self::$form_name = $form_data['name'];
			}
			self::$form = new EM_Form($form_data['form'], 'em_bookings_form');
		}
		return self::$form;
	}
	
	function get_forms(){
		global $wpdb;
		$forms_data = $wpdb->get_results("SELECT meta_id, meta_value FROM ".EM_META_TABLE." WHERE meta_key = 'booking-form'");
		foreach($forms_data as $form_data){
			$form = unserialize($form_data->meta_value);
			$forms[$form_data->meta_id] = $form['form'];
		}
		return $forms;
	}
	
	function get_forms_names(){
		global $wpdb;
		$forms_data = $wpdb->get_results("SELECT meta_id, meta_value FROM ".EM_META_TABLE." WHERE meta_key = 'booking-form'");
		foreach($forms_data as $form_data){
			$form = unserialize($form_data->meta_value);
			$forms[$form_data->meta_id] = $form['name'];
		}
		return $forms;
	}
	
	/**
	 * replaces default js to 
	 * @param string $original_js
	 * @param EM_Event $EM_Event
	 * 
	 * @return string
	 */
	function js($original_js, $EM_Event){
		ob_start();
		/* 
		//when em_tickets[<?php echo self::id ?>][spaces] changes, add a row under that with new rows
		?>		
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.em-booking-form select.em-ticket-select').change( function(e){
					e.preventDefault();
					var tr = $(this).parents('tr.em-ticket').first();
					var id = tr.attr('id').substr(10);
				});			
			});
		</script>
		<?php
		*/
		return apply_filters('emp_booking_form_js', $original_js.ob_get_clean(),$original_js, $EM_Event);	//different filter to avoid collisions	
	}
	
	/**
	 * Shows the actual booking form. 
	 * @param EM_Event $event
	 */
	function booking_form($event = false){
		global $EM_Event;
		//emp_booking_form_booking_form depreciated, use em_booking_form filter instead at later priority to override this
		if( !empty($event) ){
			echo self::get_form($event);
		}else{
		    echo self::get_form($EM_Event);
		}
	}
	
	/**
	 * Fore each ticket row in the booking table, add a hidden row with ticket form
	 * @param EM_Tickets $EM_Tickets
	 */
	function ticket_form($EM_Ticket){
		?>
		<tr class="em-ticket-attendees" id="em-ticket-<?php echo $EM_Ticket->ticket_id; ?>-attendees" style="display:none;">
			<td colspan="<?php echo ( !$EM_Ticket->get_event()->is_free() ) ? '3':'2'; ?>">
				
			</td>
		</tr>
		<?php 
	}
	
	function em_booking_get_post_pre($EM_Booking){
		self::$validate = false;  //no need for a filter, use the em_booking_get_post_pre filter
	}
	
	function em_register_new_user_pre($user_array){
		global $EM_Booking;
		if( !empty($EM_Booking->booking_meta['registration']['user_login']) ){
			$user_array['user_login'] = $EM_Booking->booking_meta['registration']['user_login'];
		}
		if( !empty($EM_Booking->temporary_password) ){
			$user_array['user_pass'] = $EM_Booking->temporary_password;
		}
		return $user_array;
	}
	
	/**
	 * @param boolean $result
	 * @param EM_Booking $EM_Booking
	 * @return bool
	 */
	function em_booking_get_post($result, $EM_Booking){
		//get, store and validate post data 
		$EM_Form = self::get_form($EM_Booking->event_id);				
		if( (empty($EM_Booking->booking_id) || (!empty($EM_Booking->booking_id) && $EM_Booking->can_manage())) && $EM_Form->get_post() ){
			foreach($EM_Form->get_values() as $fieldid => $value){
				if($fieldid == 'user_password'){
				    $EM_Booking->temporary_password = $value; //assign a random property so it's never saved
				}else{
					//get results and put them into booking meta
					if( array_key_exists($fieldid, $EM_Form->user_fields) || in_array($fieldid, array('user_email','user_name')) ){
						//registration fields
						$EM_Booking->booking_meta['registration'][$fieldid] = $value;
					}elseif( $fieldid != 'captcha' ){ //ignore captchas, only for verification
						//booking fields
						$EM_Booking->booking_meta['booking'][$fieldid] = $value;
					}
				}
			}
			$result = $result && true;
		}elseif( count($EM_Form->get_errors()) > 0 ){
			$result = false;
			$EM_Booking->add_error($EM_Form->get_errors());
		}
		self::$validate = true;
		return $result;
	}
	
	/**
	 * @param boolean $result
	 * @param EM_Booking $EM_Booking
	 * @return boolean
	 */
	function em_booking_validate($result, $EM_Booking){
		if( empty($EM_Booking->booking_id) && self::$validate ){
			//only run if taking post data, because validation could fail elsewhere
			$EM_Form = self::get_form($EM_Booking->event_id);		
			if( !$EM_Form->get_post() ){
			    $EM_Booking->add_error($EM_Form->get_errors());
				return false;
			}
		}
		return $result;
	}
	
	function em_bookings_add($result){
		global $EM_Booking;
		$EM_Form = self::get_form($EM_Booking->event_id);
		if( !empty($EM_Booking->booking_meta['registration']) && is_array($EM_Booking->booking_meta['registration']) &&  !get_option('dbem_bookings_registration_disable') ){
			$user_data = array();
			foreach($EM_Booking->booking_meta['registration'] as $fieldid => $field){
				if( trim($field) !== '' && array_key_exists($fieldid, $EM_Form->form_fields) ){
					$user_data[$fieldid] = $field;
				}
			}
			foreach($user_data as $userkey => $uservalue){
				update_user_meta($EM_Booking->person_id, $userkey, $uservalue);
			}
		}
		return $result;
	}

	/**
	 * @param string $replace
	 * @param EM_Booking $EM_Booking
	 * @param string $full_result
	 * @return string
	 */
	function placeholders($replace, $EM_Booking, $full_result){
		if( empty($replace) || $replace == $full_result ){
			$user = $EM_Booking->get_person();
			$EM_Form = self::get_form($EM_Booking->event_id);
			if( $full_result == '#_BOOKINGFORMCUSTOMREG{user_name}' || $full_result == '#_BOOKINGFORMCUSTOM{user_name}' ){
				//special user_name case
				if( !is_user_logged_in() ){
					$replace = $EM_Booking->get_person()->get_name();					
				}else{
					$replace = $user->get_name();
				}
			}elseif( $full_result == '#_BOOKINGFORMCUSTOMFIELDS' ){
				$replace = '';
				foreach($EM_Form->form_fields as $field){
					$replace .= "\r\n". $field['label'] .': ';
					if( !empty($user->$field['fieldid']) ){
						//user profile is freshest, using this
						$replace .= $user->$field['fieldid'];
					}elseif( !empty($EM_Booking->booking_meta['registration'][$field['fieldid']]) ){
						//reg fields only exist as reg fields
						if(!is_array($EM_Booking->booking_meta['registration'][$field['fieldid']])){
							$replace .= $EM_Booking->booking_meta['registration'][$field['fieldid']];
						}else{
							$replace .= implode(', ', $EM_Booking->booking_meta['registration'][$field['fieldid']]);
						}
					}elseif( !empty($EM_Booking->booking_meta['booking'][$field['fieldid']]) ){
						//match for custom field value
						if(!is_array($EM_Booking->booking_meta['booking'][$field['fieldid']])){
							$replace .= $EM_Booking->booking_meta['booking'][$field['fieldid']];
						}else{
							$replace .= implode(', ', $EM_Booking->booking_meta['booking'][$field['fieldid']]);
						}
					}
				}
			}else{
				foreach($EM_Form->form_fields as $field){
					if( $full_result == '#_BOOKINGFORMCUSTOM{'.$field['fieldid'].'}' || $full_result == '#_BOOKINGFORMCUSTOMREG{'.$field['fieldid'].'}'){
						$replace = '';
						if( !empty($user->$field['fieldid']) ){
							//user profile is freshest, using this
							$replace = $user->$field['fieldid'];
						}elseif( !empty($EM_Booking->booking_meta['registration'][$field['fieldid']]) ){
							//reg fields only exist as reg fields
							if(!is_array($EM_Booking->booking_meta['registration'][$field['fieldid']])){
								$replace = $EM_Booking->booking_meta['registration'][$field['fieldid']];
							}else{
								$replace = implode(', ', $EM_Booking->booking_meta['registration'][$field['fieldid']]);
							}
						}elseif( !empty($EM_Booking->booking_meta['booking'][$field['fieldid']]) ){
							//match for custom field value
							if(!is_array($EM_Booking->booking_meta['booking'][$field['fieldid']])){
								$replace = $EM_Booking->booking_meta['booking'][$field['fieldid']];
							}else{
								$replace = implode(', ', $EM_Booking->booking_meta['booking'][$field['fieldid']]);
							}
						}
					}
				}
			}
		}
		return $replace; //no need for a filter, use the em_booking_email_placeholders filter
	}
	
	/*
	 * ----------------------------------------------------------
	 * Booking Table and CSV Export
	 * ----------------------------------------------------------
	 */
	
	function em_bookings_table_rows_col($value, $col, $EM_Booking, $EM_Bookings_Table, $csv){
		global $EM_Event;
		$event_id = (!empty($EM_Booking->get_event()->event_id) && !empty($EM_Event->event_id) && $EM_Event->event_id == $EM_Booking->get_event()->event_id ) ? $EM_Event->event_id:false;
		$EM_Form = self::get_form($event_id);
		if( array_key_exists($col, $EM_Form->form_fields) ){
			$field = $EM_Form->form_fields[$col];
			$value = get_user_meta($EM_Booking->get_person()->ID, $col, true);
			if( empty($value) && !empty($EM_Booking->booking_meta['booking'][$col]) ){
				$value = is_array($EM_Booking->booking_meta['booking'][$col]) ? implode(', ', $EM_Booking->booking_meta['booking'][$col]): $EM_Booking->booking_meta['booking'][$col];
			}elseif( empty($value) ){
				$value = "";			 
			}
		}
		return $value;
	}
	
	function em_bookings_table_cols_template($template, $EM_Bookings_Table){
		global $EM_Event;
		$event_id = (!empty($EM_Event->event_id)) ? $EM_Event->event_id:false;
		$EM_Form = self::get_form($event_id);
		foreach($EM_Form->form_fields as $field_id => $field ){
			$template[$field_id] = $field['label'];
		}
		return $template;
	}
	
	/*
	 * ----------------------------------------------------------
	 * Event Admin Functions
	 * ----------------------------------------------------------
	 */
	
	/**
	 * Depreciated, see self::em_bookings_table_cols_template()
	 * @param array $headers
	 * @return array
	 */
	function em_csv_bookings_headers($headers){
		$EM_Form = self::get_form($EM_Booking->event_id);
		foreach($EM_Form->form_fields as $fieldid => $field){
			if( !array_key_exists($fieldid, $EM_Form->user_fields) && !in_array($fieldid, array('user_email','user_name')) && $fieldid != 'booking_comment' ){
				$headers[] = $field['label']; 
			}
		}
		return $headers; //no filter needed, use the em_csv_bookings_headers filter instead
	}
	
	/**
	 * Depreciated, see self::em_bookings_table_rows_col()
	 * @param array $headers
	 * @return array
	 */	
	function em_csv_bookings_loop_after($file, $EM_Ticket_Booking, $EM_Booking){
		$EM_Form = self::get_form($EM_Booking->event_id);
		foreach($EM_Form->form_fields as $fieldid => $field){
			if( !array_key_exists($fieldid, $EM_Form->user_fields) && !in_array($fieldid, array('user_email','user_name')) && $fieldid != 'booking_comment' ){
				$field_value = (isset($EM_Booking->booking_meta['booking'][$fieldid])) ? $EM_Booking->booking_meta['booking'][$fieldid]:'n/a';
				if(is_array($field_value)){ $field_value = implode(', ', $field_value); }
				if($field['type'] == 'checkbox'){ $field_value = ($field_value) ? __('Yes','dbem'):__('No','dbem'); }
				//backward compatibility for old booking forms
				$file .= '"' .  preg_replace("/\n\r|\r\n|\n|\r/", ".     ", $field_value) . '",'; 
			}
		}
		return $file; //no filter needed, use the em_csv_bookings_loop_after filter instead
	}

	function em_bookings_single_custom( $EM_Booking ){
		//if you want to mess with these values, intercept the em_bookings_single_custom instead
		$EM_Form = self::get_form($EM_Booking->event_id);
		foreach($EM_Form->form_fields as $fieldid => $field){
			if( !array_key_exists($fieldid, $EM_Form->user_fields) && !in_array($fieldid, array('user_email','user_name')) ){
				$input_value = $field_value = (isset($EM_Booking->booking_meta['booking'][$fieldid])) ? $EM_Booking->booking_meta['booking'][$fieldid]:'n/a';
				if(is_array($field_value)){ $field_value = implode(', ', $field_value); }
				if($field['type'] == 'checkbox'){ $field_value = ($field_value) ? __('Yes','dbem'):__('No','dbem'); }
				if($field['type'] == 'date'){ $field_value = str_replace(',',' '.$field['options_date_range_seperator'].' ', $field_value); }
				//backward compatibility for old booking forms
				if( $field['fieldid'] == 'booking_comment' && $field_value == 'n/a' && !empty($EM_Booking->booking_comment) ){ $field_value = $EM_Booking->booking_comment; }
				?>
				<tr>
					<th><?php echo $field['label'] ?></th>
					<td>
						<span class="em-booking-single-info"><?php echo $field_value; ?></span>
						<div class="em-booking-single-edit"><?php echo $EM_Form->output_field_input($field, $input_value)?></div>
					</td>
				</tr>
				<?php
			}
		}
	}
	
	function em_event_save_meta($result, $EM_Event){
		global $wpdb;
		if( $result ){
			if( !empty($_REQUEST['custom_booking_form']) && is_numeric($_REQUEST['custom_booking_form']) ){
				//Make sure form id exists
				$id = $wpdb->get_var('SELECT meta_id FROM '.EM_META_TABLE." WHERE meta_id='{$_REQUEST['custom_booking_form']}'");
				if( $id == $_REQUEST['custom_booking_form'] ){
					//add or modify custom booking form id in post data
					update_post_meta($EM_Event->post_id, '_custom_booking_form', $id);
				}
			}else{
				update_post_meta($EM_Event->post_id, '_custom_booking_form', 0);
			}
		}
		return $result;
	}
	
	function event_bookings_meta_box(){
		//Get available coupons for user
		global $wpdb, $EM_Event;
		self::get_form($EM_Event);
		$default_form_id = get_option('em_booking_form_fields');
		?>
		<br style="clear" />
		<p><strong><?php _e('Booking Form','dbem'); ?> </strong></p>
		<p><em><?php _e('You can choose to use a custom booking form, or leave as is to use the default booking form.','em-pro'); ?></em></p>
		<div>	
			<?php _e('Selected Booking Form','dbem'); ?> :
			<select name="custom_booking_form" onchange="this.parentNode.submit()">
				<option value="0">[ <?php _e('Default','em-pro'); ?> ]</option>
				<?php foreach( self::get_forms_names() as $form_key => $form_name_option ): ?>
					<?php if( $form_key != $default_form_id): ?>
					<option value="<?php echo $form_key; ?>" <?php if($form_key == self::$form_id) echo 'selected="selected"'; ?>><?php echo $form_name_option; ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}
	
	/*
	 * ----------------------------------------------------------
	 * ADMIN Functions
	 * ----------------------------------------------------------
	 */
	
	function admin_page_actions(){
		global $EM_Pro, $EM_Notices, $wpdb;
		if( !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-forms-editor' ){
			//Load the right form
			if( !empty($_REQUEST['form_id']) ){
				$sql = $wpdb->prepare("SELECT meta_value FROM ".EM_META_TABLE." WHERE meta_key = 'booking-form' AND meta_id=%d", $_REQUEST['form_id']);
				$form_data = unserialize($wpdb->get_var($sql));
				$EM_Form = self::$form =  new EM_Form($form_data['form'], 'em_bookings_form');
				self::$form_name = $form_data['name'];
				self::$form_id = $_REQUEST['form_id'];
			}else{
				$EM_Form = self::get_form();
			}
			if( !empty($_REQUEST['form_name']) && $EM_Form->form_name == $_REQUEST['form_name'] && empty($_REQUEST['bookings_form_action']) ){
				//set up booking form field map and save/retreive previous data
				if( $EM_Form->editor_get_post() ){
					foreach($EM_Form->form_fields as $form_field){
						if( $form_field['fieldid'] == 'user_email' ){
							$user_email_in_form = true;
						}
					}
					//Save into DB rather than as an option
					$booking_form_data = array( 'name'=> self::$form_name, 'form'=> $EM_Form->form_fields );
					$saved = false;
					if( empty($user_email_in_form) ){
						$EM_Notices->add_error(__('You must include an E-mail field type for booking forms to work. These are used to create the user account and aren\'t shown to logged in users.','em-pro'));	
					}else{
						$saved = $wpdb->update(EM_META_TABLE, array('meta_value'=>serialize($booking_form_data)), array('meta_id' => self::$form_id));
					}
					//Update Values
					if( $saved !== false ){
						$EM_Notices->add_confirm(__('Changes Saved','em-pro'));
					}elseif( count($EM_Form->get_errors()) > 0 ){
						$EM_Notices->add_error($EM_Form->get_errors());
					}
				}
			}elseif( !empty($_REQUEST['bookings_form_action']) ){
				if( $_REQUEST['bookings_form_action'] == 'default' && wp_verify_nonce($_REQUEST['_wpnonce'], 'bookings_form_default') ){
					//make this booking form the default
					update_option('em_booking_form_fields', $_REQUEST['form_id']);
					$EM_Notices->add_confirm(sprintf(__('The form <em>%s</em> is now the default booking form. All events without a pre-defined booking form will start using this form from now on.','em-pro'), self::$form_name));
				}elseif( $_REQUEST['bookings_form_action'] == 'delete' && wp_verify_nonce($_REQUEST['_wpnonce'], 'bookings_form_delete') ){
					//load and save booking form object with new name
					$saved = $wpdb->query($wpdb->prepare("DELETE FROM ".EM_META_TABLE." WHERE meta_id='%s'", $_REQUEST['form_id']));
					if( $saved ){
						self::$form = false;
						$EM_Notices->add_confirm(sprintf(__('%s Deleted','dbem'), __('Booking Form','em-pro')), 1);
						
					}
				}elseif( $_REQUEST['bookings_form_action'] == 'rename' && wp_verify_nonce($_REQUEST['_wpnonce'], 'bookings_form_rename') ){
					//load and save booking form object with new name
					$booking_form_data = array( 'name'=> wp_kses_data($_REQUEST['form_name']), 'form'=>$EM_Form->form_fields );
					self::$form_name = $booking_form_data['name'];
					$saved = $wpdb->update(EM_META_TABLE, array('meta_value'=>serialize($booking_form_data)), array('meta_id' => self::$form_id));
					$EM_Notices->add_confirm( sprintf(__('Form renamed to <em>%s</em>.', 'em-pro'), self::$form_name));
				}elseif( $_REQUEST['bookings_form_action'] == 'add' && wp_verify_nonce($_REQUEST['_wpnonce'], 'bookings_form_add') ){
					//create new form with this name and save first off
					$EM_Form = new EM_Form(self::$form_template, 'em_bookings_form');
					$booking_form_data = array( 'name'=> wp_kses_data($_REQUEST['form_name']), 'form'=> $EM_Form->form_fields );
					self::$form = $EM_Form;
					self::$form_name = $booking_form_data['name'];
					$saved = $wpdb->insert(EM_META_TABLE, array('meta_value'=>serialize($booking_form_data), 'meta_key'=>'booking-form','object_id'=>0));
					self::$form_id = $wpdb->insert_id;
					$EM_Notices->add_confirm(__('New form created. You are now editing your new form.', 'em-pro'), true);
					wp_redirect( add_query_arg(array('form_id'=>self::$form_id), wp_get_referer()) );
					exit();
				}
			}
		}	
	}
	
	function admin_page() {
		$EM_Form = self::get_form();
		?>
		<a name="booking-form"></a>
		<div id="poststuff" class="metabox-holder">
			<!-- END OF SIDEBAR -->
			<div id="post-body">
				<div id="post-body-content">
					<?php do_action('em_booking_form_custom_admin_page_header'); ?>
					<div id="em-booking-form-editor" class="stuffbox">
						<h3>
							<?php _e ( 'Booking Form - General Information', 'em-pro' ); ?>
						</h3>
						<div class="inside">
							<p><?php _e ( 'You can customize the fields shown in your booking forms below. ', 'em-pro' )?> <?php _e ( 'It is required that you have at least an email and name field so guest users can register. Registration fields are only shown to guest visitors. Not doing so may result in unexpected behaviour.', 'em-pro' )?></p>
							<p><?php _e ( '<strong>Important:</strong> When editing this form, to make sure your old booking information is displayed, make sure new field ids correspond with the old ones.', 'em-pro' )?></p>
							<div>
								<form method="get" action="#booking-form"> 
										<?php _e('Selected Booking Form','dbem'); ?> :
										<select name="form_id" onchange="this.parentNode.submit()">
											<?php foreach( self::get_forms_names() as $form_key => $form_name_option ): ?>
											<option value="<?php echo $form_key; ?>" <?php if($form_key == self::$form_id) echo 'selected="selected"'; ?>><?php echo $form_name_option; ?></option>
											<?php endforeach; ?>
										</select>
										<input type="hidden" name="post_type" value="<?php echo EM_POST_TYPE_EVENT; ?>" />
										<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
								</form>
								<?php if( self::$form_id != get_option('em_booking_form_fields') ): ?>
								<form method="post" action="<?php echo add_query_arg(array('form_id'=>null)); ?>#booking-form"> 
									<input type="hidden" name="form_id" value="<?php echo $_REQUEST['form_id']; ?>" />
									<input type="hidden" name="bookings_form_action" value="default" />
									<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('bookings_form_default'); ?>" />
									<input type="submit" value="<?php _e ( 'Make Default', 'em-pro' ); ?> &raquo;" class="button-secondary" onclick="return confirm('<?php _e('You are about to make this your default booking form. All events without an existing specifically chosen booking form will use this new default form from now on.\n\n Are you sure you want to do this?') ?>');" />
								</form>
								<?php endif; ?> | 
								<form method="post" action="<?php echo add_query_arg(array('form_id'=>null)); ?>#booking-form" id="bookings-form-add">
									<input type="text" name="form_name" />
									<input type="hidden" name="bookings_form_action" value="add" />
									<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('bookings_form_add'); ?>" />
									<input type="submit"  value="<?php _e ( 'Add New', 'em-pro' ); ?> &raquo;" class="button-secondary" />
								</form>
								<?php if( self::$form_id == get_option('em_booking_form_fields') ): ?>
								<br /><em><?php _e('This is the default bookings form and will be used for any event where you have not chosen a speficic form to use.','em-pro'); ?></em>
								<?php endif; ?>
							</div>
							<br /><br />
							<form method="post" action="<?php echo add_query_arg(array('form_id'=>null)); ?>#booking-form" id="bookings-form-rename">
								<span style="font-weight:bold;"><?php echo sprintf(__("You are now editing ",'em-pro'),self::$form_name); ?></span>
								<input type="text" name="form_name" value="<?php echo self::$form_name;?>" />
								<input type="hidden" name="form_id" value="<?php echo self::$form_id; ?>" />
								<input type="hidden" name="bookings_form_action" value="rename" />
								<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('bookings_form_rename'); ?>" />
								<input type="submit" value="<?php _e ( 'Rename', 'em-pro' ); ?> &raquo;" class="button-secondary" />
							</form>
							<?php if( self::$form_id != get_option('em_booking_form_fields') ): ?>
							<form method="post" action="<?php echo add_query_arg(array('form_id'=>null)); ?>#booking-form" id="bookings-form-rename">
								<input type="hidden" name="form_id" value="<?php echo $_REQUEST['form_id']; ?>" />
								<input type="hidden" name="bookings_form_action" value="delete" />
								<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('bookings_form_delete'); ?>" />
								<input type="submit" value="<?php _e ( 'Delete', 'em-pro' ); ?> &raquo;" class="button-secondary" onclick="return confirm('<?php _e('Are you sure you want to delete this form?\n\n All events using this form will start using the default form automatically.'); ?>');" />
							</form>
							<?php endif; ?>
							<br /><br />
							<?php echo $EM_Form->editor(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
EM_Booking_Form::init();

?>