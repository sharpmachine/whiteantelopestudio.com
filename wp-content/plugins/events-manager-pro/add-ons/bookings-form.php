<?php
class EM_Booking_Form {
	static $validate;
	static $user_fields = array(
		'Username Login' => 'user_login',
		'E-mail (required)' => 'user_email',
		'First Name' => 'first_name',
		'Last Name' => 'last_name',
		'Website' => 'user_url',
		'AIM' => 'aim',
		'Yahoo IM' => 'yim',
		'Jabber / Google Talk' => 'jabber',
		'Biographical Info' => 'about',
		'Phone (EM)' => 'dbem_phone'
	);
	function init(){	
		//Menu/admin page
		add_action('em_create_events_submenu',array('EM_Booking_Form', 'admin_menu'),1,1);
		if( get_option('em_booking_form_custom') ){
			//Add options and tables to EM admin pages
			//add_action('em_bookings_ticket_footer', array('EM_Booking_Form', 'ticket_meta_box'),1,1); //specific ticket booking info
			add_action('em_bookings_single_custom', array('EM_Booking_Form', 'em_bookings_single_custom'),1,1); //show booking form and ticket summary
			add_action('em_csv_bookings_loop_after', array('EM_Booking_Form', 'em_csv_bookings_loop_after'),1,3); //show booking form and ticket summary
			add_action('em_csv_bookings_headers', array('EM_Booking_Form', 'em_csv_bookings_headers'),1,1); //show booking form and ticket summary
			// Actions and Filters
			add_filter('em_booking_form_custom', array('EM_Booking_Form','booking_form'),10,2); //handle the 
			add_filter('em_booking_form_js', array('EM_Booking_Form','js'),10,2); //handle the 
			add_filter('em_booking_form_tickets_loop', array('EM_Booking_Form','ticket_form'),10,2); //JS Replacement, so we can handle the ajax return differently
			//Booking interception
			//add_filter('em_booking_add', array('EM_Booking_Form', 'em_booking_add'), 1, 2); //called only when bookin is added
			add_filter('em_booking_get_post_pre', array('EM_Booking_Form', 'em_booking_get_post_pre'), 1, 1); //turns on flag so we know not to double validate
			add_filter('em_booking_get_post', array('EM_Booking_Form', 'em_booking_get_post'), 1, 2); //get post data + validate
			add_filter('em_booking_validate', array('EM_Booking_Form', 'em_booking_validate'), 1, 2); //validate object
			add_filter('em_register_new_user_pre', array('EM_Booking_Form', 'em_register_new_user_pre'), 1, 1); //add extra use reg data
			//Placeholder overriding	
			add_filter('em_booking_output_placeholder',array('EM_Booking_Form','placeholders'),1,3); //for emails
		}
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
				$('#em-booking-form select.em-ticket-select').change( function(e){
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
	 * @param EM_Event $EM_Event
	 */
	function booking_form($EM_Event){
		$booking_form_fields = get_option('em_booking_form_fields');
		echo apply_filters('emp_booking_form_booking_form',self::output($booking_form_fields), $EM_Event);
	}
	
	/**
	 * Prints html fields according to this field structure.
	 * @param array $booking_form_fields
	 */
	function output($booking_form_fields){
		$return = '';
		foreach($booking_form_fields as $field){
			$return .= self::output_field($field);
		}
		return apply_filters('emp_booking_form_output',$return, $booking_form_fields);
	}
	
	function output_field($field, $post=true){
		ob_start();
		$default = ($post && !empty($_REQUEST[$field['booking_form_fieldid']])) ? $_REQUEST[$field['booking_form_fieldid']]:'';
		switch($field['booking_form_type']){
			case 'name':
			case 'email': //depreciated
				if( self::show_reg_fields() ){
					?>
					<p class="input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
						<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label> 
						<input type="text" name="<?php echo $field['booking_form_fieldid'] ?>" id="<?php echo $field['booking_form_fieldid'] ?>" class="input" value="<?php echo $default; ?>"  />
					</p>
					<?php
				}
				break;				
			case 'text':
				?>
				<p class="input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label> 
					<input type="text" name="<?php echo $field['booking_form_fieldid'] ?>" id="<?php echo $field['booking_form_fieldid'] ?>" class="input" value="<?php echo $default; ?>"  />
				</p>
				<?php
				break;	
			case 'textarea':
				?>
				<p class="input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label> 
					<textarea name="<?php echo $field['booking_form_fieldid'] ?>" id="<?php echo $field['booking_form_fieldid'] ?>" class="input"><?php echo $default; ?></textarea>
				</p>
				<?php
				break;
			case 'checkbox':
				?>
				<p class="input-group input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label>
					<span> 
						<input type="checkbox" name="<?php echo $field['booking_form_fieldid'] ?>" id="<?php echo $field['booking_form_fieldid'] ?>" value="1" <?php if($default) echo 'checked="checked"'; ?> />
					</span>
				</p>
				<?php
				break;
			case 'checkboxes':
				if(!is_array($default)) $default = array();
				$values = explode("\r\n",$field['booking_form_options_selection_values']);
				?>
				<p class="input-group input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label>
					<span> 
						<?php foreach($values as $value): $value = trim($value); ?>
						<input type="checkbox" name="<?php echo $field['booking_form_fieldid'] ?>[]" class="<?php echo $field['booking_form_fieldid'] ?>" value="<?php echo esc_attr($value) ?>" <?php if(in_array($value, $default)) echo 'checked="checked"'; ?> /> <?php echo $value ?><br />
						<?php endforeach; ?>
					</span>
				</p>
				<?php
				break;
			case 'radio':
				$values = explode("\r\n",$field['booking_form_options_selection_values']);
				?>
				<p class="input-group input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label>
					<span> 
						<?php foreach($values as $value): $value = trim($value); ?>
						<input type="radio" name="<?php echo $field['booking_form_fieldid'] ?>" class="<?php echo $field['booking_form_fieldid'] ?>" value="<?php echo esc_attr($value) ?>" <?php if($value == $default) echo 'checked="checked"'; ?> /> <?php echo $value ?><br />
						<?php endforeach; ?>
					</span>
				</p>
				<?php
				break;
			case 'select':
			case 'multiselect':
				$values = explode("\r\n",$field['booking_form_options_select_values']);
				$multi = $field['booking_form_type'] == 'multiselect';
				if($multi && !is_array($default)) $default = array();
				?>
				<p class="input-group input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label>
					<span> 
						<select name="<?php echo $field['booking_form_fieldid'] ?><?php echo ($multi) ? '[]':''; ?>" class="<?php echo $field['booking_form_fieldid'] ?>" <?php echo ($multi) ? 'multiple':''; ?>>
						<?php 
							//calculate default value to be checked
							if( !$field['booking_form_options_select_default'] ){
								?>
								<option value=""><?php echo esc_html($field['booking_form_options_select_default_text']); ?></option>
								<?php
							}
							$count = 0;
						?>
						<?php foreach($values as $value): $value = trim($value); $count++; ?>
							<option <?php echo (($count == 1 && $field['booking_form_options_select_default']) || ($multi && in_array($value, $default)) || ($value == $default) )?'selecte="selected"':''; ?>>
								<?php echo esc_html($value) ?>
							</option>
						<?php endforeach; ?>
						</select>
					</span>
				</p>
				<?php
				break;
			case 'captcha':
				if( !function_exists('recaptcha_get_html') ) { include_once(plugin_dir_path(__FILE__).'../includes/lib/recaptchalib.php'); }
				if( function_exists('recaptcha_get_html') && !is_user_logged_in() ){
					?>
					<p class="input-group input-<?php echo $field['booking_form_type']; ?> input-field-<?php echo $field['booking_form_fieldid'] ?>">
					<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label>
					<span> 
						<?php echo recaptcha_get_html($field['booking_form_options_captcha_key_pub'], $field['booking_form_options_captcha_error'], is_ssl()); ?>
					</span>
					</p>
					<script type="text/javascript">
						jQuery(document).ready( function($){
							$(document).bind('em_booking_gateway_add',function(event, response){
								if( !response.result ){
									Recaptcha.reload();
								}
							});						
						});
					</script>
					<?php
				}
				break;
			default:
				if( in_array($field['booking_form_type'], self::$user_fields) && self::show_reg_fields() ){
					//registration fields
					?>
					<p class="input-<?php echo $field['booking_form_type']; ?>">
						<label for='<?php echo $field['booking_form_fieldid'] ?>'><?php echo $field['booking_form_label'] ?></label> 
						<input type="text" name="<?php echo $field['booking_form_fieldid'] ?>" id="<?php echo $field['booking_form_fieldid'] ?>" class="input"  value="<?php echo $default; ?>" />
					</p>
					<?php
				}
				break;
		}	
		return apply_filters('emp_booking_form_output_field', ob_get_clean(), $field);	
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
		self::$validate = true;  //no need for a filter, use the em_booking_get_post_pre filter
	}
	
	function em_booking_get_post($result, $EM_Booking){
		//get, store and validate post data 
		$booking_form_fields = get_option('em_booking_form_fields');
		
		$errors = array();
		
		if( empty($EM_Booking->booking_id ) ){
			foreach($booking_form_fields as $fieldid => $field){
				$value = '';
				if(!empty($_REQUEST[$fieldid]) && !is_array($_REQUEST[$fieldid])){
					$value = wp_kses_data(stripslashes($_REQUEST[$fieldid]));
				}elseif(!empty($_REQUEST[$fieldid]) && is_array($_REQUEST[$fieldid])){
					$value = $_REQUEST[$fieldid];
				}
				if( in_array($fieldid, self::$user_fields) || in_array($fieldid, array('user_email','user_name')) ){
					//registration fields
					$EM_Booking->booking_meta['registration'][$fieldid] = $value;
				}elseif( $fieldid != 'captcha' ){ //ignore captchas, only for verification
					//booking fields
					$EM_Booking->booking_meta['booking'][$fieldid] = $value;
				}
				$errors[] = self::validate_field($field, $value, $EM_Booking);
			}
			self::$validate = false;
		}
		return apply_filters('emp_booking_form_get_post', !in_array(false,$errors) && $result, $EM_Booking);
	}
	
	function em_booking_validate($result, $EM_Booking){
		if( empty($EM_Booking->booking_id)  && self::$validate ){
			//only run if taking post data, because validation could fail elsewhere 
			$booking_form_fields = get_option('em_booking_form_fields');
			$results = array();
			if($result){
				//go through each form field and run checks, we don't do it the opposite way to avoid invalidating old bookings without the meta data
				if( !empty($EM_Booking->booking_meta['booking']) ){
					foreach($EM_Booking->booking_meta['booking'] as $fieldid => $field){
						//validate
						$results[] = self::validate_field($booking_form_fields[$fieldid], $field, $EM_Booking);
					}
				}
				if( !empty($EM_Booking->booking_meta['registration']) ){
					foreach($EM_Booking->booking_meta['registration'] as $fieldid => $field){
						//registration fields too
						$results[] = self::validate_field($booking_form_fields[$fieldid], $field, $EM_Booking);
					}
				}
				return apply_filters('emp_booking_form_validate',!(in_array(false,$results)), $EM_Booking);
			}
		}
		return apply_filters('emp_booking_form_validate', $result, $EM_Booking);
	}
	
	function em_register_new_user_pre($user_data){
		global $EM_Booking;
		if( !empty($EM_Booking->booking_meta['registration']) && is_array($EM_Booking->booking_meta['registration']) ){
			foreach($EM_Booking->booking_meta['registration'] as $fieldid => $field){
				if( trim($field) !== '' ){
					$user_data[$fieldid] = $field;
				}
			}
		}
		return apply_filters('emp_booking_form_register_new_user_pre',$user_data, $EM_Booking);
	}

	/**
	 * Validates a field and adds errors to the object it's referring to (can be any extension of EM_Object)
	 * @param array $field
	 * @param mixed $value
	 * @param EM_Object $EM_Object
	 */
	function validate_field( $field, $value, $EM_Object ){
		$value = (is_array($value)) ? $value:trim($value);
		$result = true;
		$err = sprintf(get_option('em_booking_form_error_required'), $field['booking_form_label']);	
		switch($field['booking_form_type']){
			case 'email': //depreciated
			case 'user_email':
				if( self::show_reg_fields() ){
					// Check the e-mail address
					if ( $value == '' ) {
						$EM_Object->add_error($err);
						$result = false;
					} elseif ( ! is_email( $value ) ) {
						$EM_Object->add_error( __( 'The email address isn&#8217;t correct.', 'dbem') );
						$result = false;
					}
					//regex
					if( !empty($field['booking_form_options_text_regex']) && !@preg_match('/'.$field['booking_form_options_text_regex'].'/',$value) ){
						$this_err = (!empty($field['booking_form_options_text_error'])) ? $field['booking_form_options_text_error']:$err;
						$EM_Object->add_error($this_err);
						$result = false;
					}
				}
				break;
			case 'user_login':
				if( self::show_reg_fields() ){
					$sanitized_user_login = sanitize_user( $value );
					// Check the username
					if ( $sanitized_user_login == '' ) {
						$EM_Object->add_error($err);
						$result = false;
					} elseif ( ! validate_username( $value ) ) {
						$EM_Object->add_error( __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'dbem') );
						$result = false;
					} elseif ( username_exists( $sanitized_user_login ) ) {
						$EM_Object->add_error( __( 'This username is already registered, please choose another one.' ) );
						$result = false;
					}
				}
			case 'name':
				if( self::show_reg_fields() ){
					//regex
					if( !empty($field['booking_form_options_text_regex']) && !@preg_match('/'.$field['booking_form_options_text_regex'].'/',$value) ){
						if( !($value == '' && $field['booking_form_required']) ){
							$this_err = (!empty($field['booking_form_options_text_error'])) ? $field['booking_form_options_text_error']:$err;
							$EM_Object->add_error($this_err);
							$result = false;
						}
					}
					//non-empty match
					if( empty($value) && $field['booking_form_required'] ){
						$EM_Object->add_error($err);
						$result = false;
					}
				}
				break;
			case 'text':
			case 'textarea':
				//regex
				if( !empty($field['booking_form_options_text_regex']) && !@preg_match('/'.$field['booking_form_options_text_regex'].'/',$value) ){
					if( !($value == '' && $field['booking_form_required']) ){
						$this_err = (!empty($field['booking_form_options_text_error'])) ? $field['booking_form_options_text_error']:$err;
						$EM_Object->add_error($this_err);
						$result = false;
					}
				}
				//non-empty match
				if( $result && empty($value) && $field['booking_form_required'] ){
					$EM_Object->add_error($err);
					$result = false;
				}
				break;
			case 'checkbox':
				//non-empty match
				if( empty($value) && $field['booking_form_required'] ){
					$this_err = (!empty($field['booking_form_options_checkbox_error'])) ? $field['booking_form_options_checkbox_error']:$err;
					$EM_Object->add_error($this_err);
					$result = false;
				}
				break;
			case 'checkboxes':
				$values = explode("\r\n",$field['booking_form_options_selection_values']);
				array_walk($values,'trim');
				if( !is_array($value) ) $value = array();
				//in-values
				if( (empty($value) && $field['booking_form_required']) || count(array_diff($value, $values)) > 0 ){
					$this_err = (!empty($field['booking_form_options_selection_error'])) ? $field['booking_form_options_selection_error']:$err;
					$EM_Object->add_error($this_err);
					$result = false;
				}
				break;
			case 'radio':
				$values = explode("\r\n",$field['booking_form_options_selection_values']);
				array_walk($values,'trim');
				//in-values
				if( (!in_array($value, $values) || empty($value)) && $field['booking_form_required'] ){
					$this_err = (!empty($field['booking_form_options_selection_error'])) ? $field['booking_form_options_selection_error']:$err;
					$EM_Object->add_error($this_err);
					$result = false;
				}				
				break;
			case 'multiselect':
				$values = explode("\r\n",$field['booking_form_options_select_values']);
				array_walk($values,'trim');
				if( !is_array($value) ) $value = array();
				//in_values
				if( (empty($value) && $field['booking_form_required']) || count(array_diff($value, $values)) > 0 ){
					$this_err = (!empty($field['booking_form_options_select_error'])) ? $field['booking_form_options_select_error']:$err;
					$EM_Object->add_error($this_err);
					$result = false;
				}				
				break;
			case 'select':
				$values = explode("\r\n",$field['booking_form_options_select_values']);
				array_walk($values,'trim');
				//in-values
				if( (!in_array($value, $values) || empty($value)) && $field['booking_form_required'] ){
					$this_err = (!empty($field['booking_form_options_select_error'])) ? $field['booking_form_options_select_error']:$err;
					$EM_Object->add_error($this_err);
					$result = false;
				}				
				break;
			case 'captcha':
				if( !function_exists('recaptcha_get_html') ) { include_once(plugin_dir_path(__FILE__).'../includes/lib/recaptchalib.php'); }
				if( function_exists('recaptcha_check_answer') && !is_user_logged_in() ){
					$resp = recaptcha_check_answer($field['booking_form_options_captcha_key_priv'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
					$result = $resp->is_valid;
					if(!$result){
						$EM_Object->add_error($field['booking_form_options_captcha_error']);
					}
				}
				break;
			default:
				if( !is_user_logged_in() && in_array($field['booking_form_type'], self::$user_fields) ){
					//regex
					if( !empty($field['booking_form_options_reg_regex']) && !@preg_match('/'.$field['booking_form_options_reg_regex'].'/',$value) ){
						if( !($value == '' && !$field['booking_form_required']) ){
							$this_err = (!empty($field['booking_form_options_reg_error'])) ? $field['booking_form_options_reg_error']:$err;
							$EM_Object->add_error($this_err);
							$result = false;
						}
					}
					//non-empty match
					if( empty($value) && ($field['booking_form_required']) ){
						$EM_Object->add_error($err);
						$result = false;
					}
				}
				break;
		}
		return apply_filters('emp_booking_form_validate_field',$result, $field, $value, $EM_Object);
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
			$booking_form_fields = get_option('em_booking_form_fields');
			if( $full_result == '#_BOOKINGFORMCUSTOMREG{user_name}' || $full_result == '#_BOOKINGFORMCUSTOM{user_name}' ){
				//special user_name case
				if( !is_user_logged_in() ){
					if( !empty($booking_form_fields['first_name']['booking_form_fieldid']) ){
						$replace = $EM_Booking->booking_meta['registration'][$booking_form_fields['first_name']['booking_form_fieldid']];
					}
					if( !empty($booking_form_fields['last_name']['booking_form_fieldid']) ){
						$replace .= " ".$EM_Booking->booking_meta['registration'][$booking_form_fields['last_name']['booking_form_fieldid']];
					}					
				}else{
					$replace = $user->get_name();
				}
			}else{
				foreach($booking_form_fields as $field){
					if( $full_result == '#_BOOKINGFORMCUSTOM{'.$field['booking_form_fieldid'].'}' || $full_result == '#_BOOKINGFORMCUSTOMREG{'.$field['booking_form_fieldid'].'}'){
						$replace = '';
						if( !empty($user->$field['booking_form_fieldid']) ){
							//user profile is freshest, using this
							$replace = $user->$field['booking_form_fieldid'];
						}elseif( !empty($EM_Booking->booking_meta['registration'][$field['booking_form_fieldid']]) ){
							//reg fields only exist as reg fields
							$replace = $EM_Booking->booking_meta['registration'][$field['booking_form_fieldid']];
						}elseif( !empty($EM_Booking->booking_meta['booking'][$field['booking_form_fieldid']]) ){
							//match for custom field value
							if(!is_array($EM_Booking->booking_meta['booking'][$field['booking_form_fieldid']])){
								$replace = $EM_Booking->booking_meta['booking'][$field['booking_form_fieldid']];
							}else{
								$replace = implode(', ', $EM_Booking->booking_meta['booking'][$field['booking_form_fieldid']]);
							}
						}
					}
				}
			}
		}
		return $replace; //no need for a filter, use the em_booking_email_placeholders filter
	}
	
	function em_csv_bookings_headers($headers){
		$booking_form_fields = get_option('em_booking_form_fields');
		foreach($booking_form_fields as $fieldid => $field){
			if( !in_array($fieldid, self::$user_fields) && !in_array($fieldid, array('user_email','user_name')) ){
				$headers[] = $field['booking_form_label']; 
			}
		}
		return $headers; //no filter needed, use the em_csv_bookings_headers filter instead
	}
	
	function em_csv_bookings_loop_after($file, $EM_Ticket_Booking, $EM_Booking){
		$booking_form_fields = get_option('em_booking_form_fields');
		foreach($booking_form_fields as $fieldid => $field){
			if( !in_array($fieldid, self::$user_fields) && !in_array($fieldid, array('user_email','user_name')) && $fieldid != 'booking_comment' ){
				$field_value = (isset($EM_Booking->booking_meta['booking'][$fieldid])) ? $EM_Booking->booking_meta['booking'][$fieldid]:'n/a';
				if(is_array($field_value)){ $field_value = implode(', ', $field_value); }
				if($field['booking_form_type'] == 'checkbox'){ $field_value = ($field_value) ? __('Yes','dbem'):__('No','dbem'); }
				//backward compatibility for old booking forms
				$file .= '"' .  preg_replace("/\n\r|\r\n|\n|\r/", ".     ", $field_value) . '",'; 
			}
		}
		return $file; //no filter needed, use the em_csv_bookings_loop_after filter instead
	}
	
	function admin_menu($plugin_pages){
		$plugin_pages[] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Booking Form Editor'),__('Booking Form Editor'),'activate_plugins','events-manager-booking-form',array('EM_Booking_Form','admin_page'));
		return $plugin_pages; //use wp action/filters to mess with the menus
	}
	
	/**
	 * Gets an array of field keys accepted by the booking form 
	 */
	function get_fields_map(){
		$map = array (
			'booking_form_fieldid','booking_form_label','booking_form_type','booking_form_required',
			'booking_form_options_select_values','booking_form_options_select_default','booking_form_options_select_default_text','booking_form_options_select_error',
			'booking_form_options_selection_values','booking_form_options_selection_default','booking_form_options_selection_error',
			'booking_form_options_checkbox_error','booking_form_options_checkbox_checked',
			'booking_form_options_text_regex','booking_form_options_text_error',
			'booking_form_options_reg_regex', 'booking_form_options_reg_error',
			'booking_form_options_captcha_theme','booking_form_options_captcha_key_priv','booking_form_options_captcha_key_pub', 'booking_form_options_captcha_error');
		return apply_filters('em_booking_form_get_fields_map', $map);
	}
	/**
	 * Gets an array of field keys accepted by the booking form 
	 */
	function get_attendee_fields_map(){
		$map = array (
			'booking_form_attendee_attendee_fieldid','booking_form_attendee_label','booking_form_attendee_type','booking_form_attendee_required',
			'booking_form_attendee_options_select_values','booking_form_attendee_options_select_default','booking_form_attendee_options_select_default_text','booking_form_attendee_options_select_error',
			'booking_form_attendee_options_selection_values','booking_form_attendee_options_selection_default','booking_form_attendee_options_selection_error',
			'booking_form_attendee_options_checkbox_error','booking_form_attendee_options_checkbox_checked',
			'booking_form_attendee_options_text_regex','booking_form_attendee_options_text_error');
		return apply_filters('em_booking_form_get_fields_map', $map);
	}
	
	function get_input_default($key, $field_values, $type='text', $value=""){
		$return = '';
		if(is_array($field_values)){
			switch ($type){
				case 'text':
					$return = (array_key_exists($key,$field_values)) ? 'value="'.esc_attr($field_values[$key]).'"':'value="'.esc_attr($value).'"';
					break;
				case 'textarea':
					$return = (array_key_exists($key,$field_values)) ? esc_html($field_values[$key]):esc_html($value);
					break;
				case 'select':
					$return = ( array_key_exists($key,$field_values) && $value == $field_values[$key] ) ? 'selected="selected"':'';
					break;
				case 'checkbox':
					$return = ( !empty($field_values[$key]) && $field_values[$key] == 1 ) ? 'checked="checked"':'';
					break;
				case 'radio':
					$return = ( $value == $field_values[$key] ) ? 'checked="checked"':'';
					break;
			}
		}
		return apply_filters('emp_booking_form_get_input_default',$return, $key, $field_values, $type, $value);
	}
	function input_default($key, $fields, $type = 'text', $value=""){ echo self::get_input_default($key, $fields, $type, $value); }

	function em_bookings_single_custom( $EM_Booking ){
		//if you want to mess with these values, intercept the em_bookings_single_custom instead
		$booking_form_fields = get_option('em_booking_form_fields');
		foreach($booking_form_fields as $fieldid => $field){
			if( !in_array($fieldid, self::$user_fields) && !in_array($fieldid, array('user_email','user_name')) ){
				$field_value = (isset($EM_Booking->booking_meta['booking'][$fieldid])) ? $EM_Booking->booking_meta['booking'][$fieldid]:'n/a';
				if(is_array($field_value)){ $field_value = implode(', ', $field_value); }
				if($field['booking_form_type'] == 'checkbox'){ $field_value = ($field_value) ? __('Yes','dbem'):__('No','dbem'); }
				//backward compatibility for old booking forms
				if( $field['booking_form_fieldid'] == 'booking_comment' && $field_value == 'n/a' && !empty($EM_Booking->booking_comment) ){ $field_value = $EM_Booking->booking_comment; }
				?>
				<tr><td><strong><?php echo $field['booking_form_label'] ?></strong> </td><td><?php echo $field_value; ?></td></tr>
				<?php
			}
		}
	}
	
	function admin_page() {
		global $EM_Pro;
		//echo "<pre>"; print_r($_POST); echo "</pre>";
		//set up booking form field map and save/retreive previous data
		$booking_form_fields = get_option('em_booking_form_fields');
		$attendee_form_fields = get_option('em_attendee_form_fields');
		if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'booking_fields' && wp_verify_nonce($_REQUEST['_wpnonce'], 'booking_fields') ){
			//Booking form fields
			$fields_map = self::get_fields_map();
			$booking_form_fields = array();
			$types_added = array();
			//extract request info back into item lists, but first assign fieldids to new items
			foreach( $_REQUEST['booking_form_fieldid'] as $fieldid_key => $fieldid ){
				if( $_REQUEST['booking_form_type'][$fieldid_key] == 'name' ){ //name field
					$_REQUEST['booking_form_fieldid'][$fieldid_key] = 'user_name';
				}elseif( in_array($_REQUEST['booking_form_type'][$fieldid_key], self::$user_fields) ){ //other fields
					$_REQUEST['booking_form_fieldid'][$fieldid_key] = $_REQUEST['booking_form_type'][$fieldid_key];
				}elseif( empty($fieldid) ){
					$_REQUEST['booking_form_fieldid'][$fieldid_key] = sanitize_title($_REQUEST['booking_form_label'][$fieldid_key]); //assign unique id
				}
			}
			//get field values
			foreach( $_REQUEST as $key => $value){
				if( is_array($value) && in_array($key,$fields_map) ){
					foreach($value as $item_index => $item_value){
						$item_value = stripslashes(wp_kses_data($item_value));
						$booking_form_fields[$_REQUEST['booking_form_fieldid'][$item_index]][$key] = $item_value;
						if($key == 'booking_form_type'){
							$types_added[] = $item_value;
						}
					}
				}
				//TODO validate and clean up blanks/bad data
			}
			
			//Attendee Form Fields
			$attendee_fields_map = self::get_attendee_fields_map();
			$attendee_form_fields = array();
			/*
			//extract request info back into item lists, but first assign fieldids to new items
			foreach( $_REQUEST['booking_form_attendee_fieldid'] as $fieldid_key => $fieldid ){
				if( empty($fieldid_key) ){
					$_REQUEST['booking_form_attendee_fieldid'][$fieldid_key] = substr(md5(rand()),0,8); //assign unique id
				}
			}
			//get field values
			foreach( $_REQUEST as $key => $value){
				if( in_array($key,$attendee_fields_map) ){
					foreach($value as $item_index => $item_value){
						$attendee_form_fields[$_REQUEST['booking_form_attendee_fieldid'][$item_index]][$key] = $item_value;
					}
				}
				//TODO validate and clean up blanks/bad data
			}
			*/
			
			//Update Values
			if( in_array('user_email',$types_added) ){
				update_option('em_booking_form_fields',$booking_form_fields);
				update_option('em_attendee_form_fields', $attendee_form_fields);
			}else{
				?>
				<div class="error"><p><?php _e('You must include a name and email field type for booking forms to work. These are used to create the user account and aren\'t shown to logged in users.','em-pro'); ?></p></div>
				<?php
				$errors = true;
			}
			
			if(empty($errors)){
				?>
				<div class="updated"><p><?php _e('Changes Saved','em-pro'); ?></p></div>
				<?php
				update_option('em_booking_form_custom',!empty($_REQUEST['em_booking_form_custom']));
				update_option('em_booking_form_attendee_custom',!empty($_REQUEST['em_booking_form_attendee_custom']));
			}
		}
		//echo "<pre>"; var_export($booking_form_fields); echo "</pre>";
		if( count($booking_form_fields) == 0 ){ $booking_form_fields[] = self::get_fields_map(); }
		$booking_form_fields['blank_em_template'] = self::get_fields_map();
		if( count($attendee_form_fields) == 0 ){ $attendee_form_fields[] = self::get_attendee_fields_map(); }
		$attendee_form_fields['blank_em_template'] = self::get_attendee_fields_map();
		//enable dbem_bookings_tickets_single_form if enabled
		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php _e('Booking Form Editor','em-pro'); ?></h2>
			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
			}
			?>
			<p><?php _e('On this page you can override the default booking form and add your own custom fields.', 'em-pro' ); ?></p>
			<style tyle="text/css">
				/* structure */
				.booking-custom > div, .booking-custom > ul {  padding:10px; }
				.booking-custom-item { clear:left; border-top:1px solid #dedede; padding-top:10px; overflow:visible; }
				/* cols/fields */
				.bc-col { float:left; width:140px; text-align:left; margin:0px 30px 0px 0px; }
				.bc-col-required { width:50px; }
				.bc-col-sort { width:25px; height:25px; background:url(<?php echo plugins_url('includes/images/cross.png',__FILE__); ?>) 0px 0px no-repeat; cursor:move; }
				.booking-custom-head .bc-col-sort { background:none; }
				.booking-custom-types { clear:left; }
				.booking-custom-types .bct-options { clear:left; margin-top:50px; }
				.booking-custom-types .bct-field { clear:left; margin-top:10px; }
				/* option structure */
				.bct-field .bct-label { float:left; width:120px; }
				.bct-field .bct-input { margin:0px 0px 10px 130px; }
				.bct-field .bct-input input, .bct-field .bct-input textarea { display:block; }
				/* Sorting */
				.booking-custom { list-style-type: none; margin: 0; padding: 0; }
				.bc-highlight { height:45px; line-height:35px; border:1px solid #cdcdcd; background:#efefef; }
			</style>
			<form method="post" action="">
				<div id="poststuff" class="metabox-holder">
					<!-- END OF SIDEBAR -->
					<div id="post-body">
						<div id="post-body-content">
							<?php do_action('em_booking_form_custom_admin_page_header'); ?>
							<div id="event_end_day" class="stuffbox">
								<h3>
									<?php _e ( 'Booking Form - General Information', 'em-pro' ); ?>
								</h3>
								<div class="inside">
									<p><?php _e ( 'You can customize the fields shown in your booking forms below. ', 'em-pro' )?>.</p>
									<p><?php _e ( 'It is required that you have at least an email and name field so guest users can register. Registration fields are only shown to guest visitors. Not doing so may result in unexpected behaviour.', 'em-pro' )?></p>
									<p><?php _e ( '<strong>Important:</strong> When editing this form, to make sure your old booking information is displayed, make sure new field ids correspond with the old ones.', 'em-pro' )?></p>
									<div id="booking-form-custom-table" class="booking-custom">
										<div class="booking-custom-head">
											<div class='bc-col-sort bc-col'>&nbsp;</div>
											<div class='bc-col-label bc-col'><?php _e('Label','em-pro'); ?></div>
											<div class='bc-col-id bc-col'><?php _e('Field ID','em-pro'); ?><a title="<?php _e('DO NOT change these values if you want to keep your field settings associated with previous booking fields.'); ?>">?</a></div>
											<div class='bc-col-type bc-col'><?php _e('Type','em-pro'); ?></div>
											<div class='bc-col-required bc-col'><?php _e('Required','em-pro'); ?></div>
										</div>
										<ul class="booking-custom-body">
											<?php foreach($booking_form_fields as $field_key => $field_values): ?>
											<li class="booking-custom-item" <?php if( $field_key === 'blank_em_template' ){ echo 'id="booking-custom-item-template"'; }; ?>>
												<div class='bc-col-sort bc-col'>&nbsp;</div>
												<div class='bc-col-label bc-col'><input type="text" name="booking_form_label[]" class="booking-form-custom-label" <?php self::input_default('booking_form_label',$field_values); ?> /></div>
												<div class='bc-col-id bc-col'><input type="text" name="booking_form_fieldid[]" class="booking-form-custom-fieldid" <?php self::input_default('booking_form_fieldid',$field_values); ?> /></div>
												<div class='bc-col-type bc-col'>
													<select name="booking_form_type[]" class="booking-form-custom-type">
														<option value=""><?php echo _e('Select Type','em-pro'); ?></option>
														<optgroup label="<?php _e('Customizable Fields','em-pro'); ?>">
															<option <?php self::input_default('booking_form_type',$field_values,'select','checkbox'); ?>>checkbox</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','text'); ?>>text</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','textarea'); ?>>textarea</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','checkboxes'); ?>>checkboxes</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','radio'); ?>>radio</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','select'); ?>>select</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','multiselect'); ?>>multiselect</option>
															<option <?php self::input_default('booking_form_type',$field_values,'select','captcha'); ?>>captcha</option>
														</optgroup>
														<optgroup label="<?php _e('Registration Fields','em-pro'); ?>">
															<option value="name" <?php self::input_default('booking_form_type',$field_values,'select','name'); ?>>Name</option>
															<?php foreach( self::$user_fields as $field_name => $field ): ?>
															<option value="<?php echo $field; ?>" <?php self::input_default('booking_form_type',$field_values,'select',$field); ?>><?php echo $field_name; ?></option>
															<?php endforeach; ?>
														</optgroup>
													</select>
												</div>
												<div class='bc-col-required bc-col'>
													<input type="checkbox" class="booking-form-custom-required" value="1" <?php self::input_default('booking_form_required',$field_values,'checkbox'); ?> />
													<input type="hidden" name="booking_form_required[]" <?php self::input_default('booking_form_required',$field_values,'text'); ?> />
												</div>
												<div class='bc-col-options bc-col'><a href="#" class="booking-form-custom-field-remove"><?php _e('remove','em-pro'); ?></a> | <a href="#" class="booking-form-custom-field-options"><?php _e('options','em-pro'); ?></a></div>
												<div class='booking-custom-types'>
													<div class="bct-select bct-options" style="display:none;">
														<!-- select,multiselect -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
															<div class="bct-input">
																<textarea name="booking_form_options_select_values[]"><?php self::input_default('booking_form_options_select_values',$field_values,'textarea'); ?></textarea>
																<em><?php _e('Available options, one per line.','em-pro'); ?></em>	
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Use Default?','em-pro'); ?></div>
															<div class="bct-input">
																<input type="checkbox" <?php self::input_default('booking_form_options_select_default',$field_values,'checkbox'); ?>/>
																<input type="hidden" name="booking_form_options_select_default[]" <?php self::input_default('booking_form_options_select_default',$field_values); ?> /> 
																<em><?php _e('If checked, the first value above will be used.','em-pro'); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Default Text','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_select_default_text[]" <?php self::input_default('booking_form_options_select_default_text',$field_values,'text',__('Select ...','em-pro')); ?> />
																<em><?php _e('Shown when a default value isn\'t selected, selected by default.','em-pro'); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_select_error[]" <?php self::input_default('booking_form_options_select_error',$field_values); ?> />
																<em><?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-selection bct-options" style="display:none;">
														<!-- checkboxes,radio -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
															<div class="bct-input">
																<textarea name="booking_form_options_selection_values[]"><?php self::input_default('booking_form_options_selection_values',$field_values,'textarea'); ?></textarea>
																<em><?php _e('Available options, one per line.','em-pro'); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_selection_error[]" <?php self::input_default('booking_form_options_selection_error',$field_values); ?> />
																<em><?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-checkbox bct-options" style="display:none;">
														<!-- checkbox -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Checked by default?','em-pro'); ?></div>
															<div class="bct-input">
																<input type="checkbox" <?php self::input_default('booking_form_options_checkbox_checked',$field_values,'checkbox'); ?>/>
																<input type="hidden" name="booking_form_options_checkbox_checked[]" <?php self::input_default('booking_form_options_checkbox_checked',$field_values); ?> /> 
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_checkbox_error[]" <?php self::input_default('booking_form_options_checkbox_error',$field_values); ?> />
																<em><?php _e('This error will show if this box is not checked.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-text bct-options" style="display:none;">
														<!-- text,textarea,email,name -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_text_regex[]" <?php self::input_default('booking_form_options_text_regex',$field_values); ?> />
																<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_text_error[]" <?php self::input_default('booking_form_options_text_error',$field_values); ?> />
																<em><?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-registration bct-options" style="display:none;">
														<!-- registration -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_reg_error" <?php self::input_default('booking_form_attendee_options_text_regex',$field_values); ?> />
																<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_reg_error" <?php self::input_default('booking_form_attendee_options_text_error',$field_values); ?> />
																<em><?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-captcha bct-options" style="display:none;">
														<!-- captcha -->
														<?php 
															$uri = parse_url(get_option('siteurl')); 
															$recaptcha_url = "https://www.google.com/recaptcha/admin/create?domains={$uri['host']}&amp;app=wordpress"; 
														?>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Private Key','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_captcha_key_priv[]" <?php self::input_default('booking_form_options_captcha_key_priv',$field_values); ?> />
																<em><?php echo sprintf(__('Required, get your keys <a href="%s">here</a>','em-pro'),$recaptcha_url); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Public Key','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_captcha_key_pub[]" <?php self::input_default('booking_form_options_captcha_key_pub',$field_values); ?> />
																<em><?php echo sprintf(__('Required, get your keys <a href="%s">here</a>','em-pro'),$recaptcha_url); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_options_captcha_error[]" <?php self::input_default('booking_form_options_captcha_error',$field_values); ?> />
																<em><?php _e('This error will show if the captcha is not correct.','em-pro'); ?></em>
															</div>
														</div>
													</div>
												</div>
												<br style="clear:both" />
											</li>
											<?php endforeach; ?>
										</ul>
										<p><input type="button" value="<?php _e('Add booking field','em-pro'); ?>" class="booking-form-custom-field-add button-secondary"></p>
									</div>
									<table cellpadding="3" cellspacing="5" class="form-table">
										<tbody>
											<tr>
												<th scope="row">Use this booking form?</th>
												<td>
													<input type="checkbox" name="em_booking_form_custom" <?php if(get_option('em_booking_form_custom')){ echo "checked='checked'"; } ?> />
													<br /><em><?php _e('Disabling this option reverts your booking form with limited functionality as in the free version.','em-pro'); ?></em>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
							<?php 
							/*
							<div id="event_end_day" class="stuffbox">
								<h3>
									<?php _e ( 'Attendee Form - Individual Inforamtion', 'em-pro' ); ?>
								</h3>
								<div class="inside">
									<p><?php _e ( 'You can require attendees to provide individual attendee information, so that you have specific information for every ticket booked.', 'em-pro' )?>.</p>
									<table cellpadding="3" cellspacing="5" class="form-table">
										<tbody>
											<tr>
												<th scope="row">Use this form for attendees?</th>
												<td><input type="checkbox" name="em_booking_form_attendee_custom" <?php if( get_option('em_booking_form_attendee_custom') ){ echo "checked='checked'"; } ?> /></td>
											</tr>
										</tbody>
									</table>
									<div id="booking-form-attendee-custom-table" class="booking-custom">
										<div class="booking-custom-head">
											<div class='bc-col-sort bc-col'>&nbsp;</div>
											<div class='bc-col-label bc-col'>Label</div>
											<div class='bc-col-type bc-col'>Type</div>
											<div class='bc-col-required bc-col'>Required</div>
										</div>
										<ul class="booking-custom-body">
											<?php foreach($attendee_form_fields as $field_key => $field_values): ?>
											<li class="booking-custom-item" <?php if( $field_key === 'blank_em_template' ){ echo 'id="attendee-custom-item-template"'; }; ?>>
												<input type="hidden" name="booking_form_attendee_fieldid[]" class="booking-form-custom-fieldid" <?php self::input_default('booking_form_attendee_fieldid',$field_values); ?> />
												<div class='bc-col-sort bc-col'>&nbsp;</div>
												<div class='bc-col-label bc-col'><input type="text" name="booking_form_attendee_label[]" class="booking-form-custom-label" <?php self::input_default('booking_form_attendee_label',$field_values); ?> /></div>
												<div class='bc-col-type bc-col'>
													<select name="booking_form_attendee_type[]" class="booking-form-custom-type">
														<option value=""><?php echo _e('Select Type','em-pro'); ?></option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','checkbox'); ?>>checkbox</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','text'); ?>>text</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','textarea'); ?>>textarea</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','checkboxes'); ?>>checkboxes</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','radio'); ?>>radio</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','textarea'); ?>>textarea</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','select'); ?>>select</option>
														<option <?php self::input_default('booking_form_attendee_type',$field_values,'select','multiselect'); ?>>multiselect</option>
													</select>
												</div>
												<div class='bc-col-required bc-col'>
													<input type="checkbox" class="booking-form-custom-required" value="1" <?php self::input_default('booking_form_attendee_required',$field_values,'checkbox'); ?> />
													<input type="hidden" name="booking_form_attendee_required[]" <?php self::input_default('booking_form_attendee_required',$field_values,'text'); ?> />
												</div>
												<div class='bc-col-options bc-col'><a href="#" class="booking-form-custom-field-remove"><?php _e('remove','em-pro'); ?></a> | <a href="#" class="booking-form-custom-field-options"><?php _e('options','em-pro'); ?></a></div>
												<div class='booking-custom-types'>
													<div class="bct-select bct-options" style="display:none;">
														<!-- select,multiselect -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
															<div class="bct-input"><textarea name="booking_form_attendee_options_select_values[]"><?php self::input_default('booking_form_attendee_options_select_values',$field_values,'textarea'); ?></textarea></div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Use Default?','em-pro'); ?></div>
															<div class="bct-input">
																<input type="checkbox" <?php self::input_default('booking_form_attendee_options_select_default',$field_values,'checkbox'); ?>/>
																<input type="hidden" name="booking_form_attendee_options_select_default[]" <?php self::input_default('booking_form_attendee_options_select_default',$field_values); ?> /> 
																<em><?php _e('If checked, the first value above will be used.','em-pro'); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Default Text','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_attendee_options_select_default_text[]" <?php self::input_default('booking_form_attendee_options_select_default_text',$field_values,'text',__('Select ...','em-pro')); ?> />
																<em><?php _e('Shown when a default value isn\'t selected, selected by default.','em-pro'); ?></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_attendee_options_select_error[]" <?php self::input_default('booking_form_attendee_options_select_error',$field_values); ?> />
																<em><?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-selection bct-options" style="display:none;">
														<!-- checkboxes,radio -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
															<div class="bct-input"><textarea name="booking_form_attendee_options_selection_values[]"><?php self::input_default('booking_form_attendee_options_selection_values',$field_values,'textarea'); ?></textarea></div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_attendee_options_selection_error[]" <?php self::input_default('booking_form_attendee_options_selection_error',$field_values); ?> />
																<em><?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-checkbox bct-options" style="display:none;">
														<!-- checkbox -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Checked by default?','em-pro'); ?></div>
															<div class="bct-input">
																<input type="checkbox" <?php self::input_default('booking_form_attendee_options_checkbox_checked',$field_values,'checkbox'); ?>/>
																<input type="hidden" name="booking_form_attendee_options_checkbox_checked[]" <?php self::input_default('booking_form_attendee_options_checkbox_checked',$field_values); ?> /> 
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_attendee_options_checkbox_error[]" <?php self::input_default('booking_form_attendee_options_checkbox_error',$field_values); ?> />
																<em><?php _e('This error will show if this box is not checked.','em-pro'); ?></em>
															</div>
														</div>
													</div>
													<div class="bct-text bct-options" style="display:none;">
														<!-- text,textarea,email,name -->
														<div class="bct-field">
															<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_attendee_options_text_regex[]" <?php self::input_default('booking_form_attendee_options_text_regex',$field_values); ?> />
																<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
															</div>
														</div>
														<div class="bct-field">
															<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
															<div class="bct-input">
																<input type="text" name="booking_form_attendee_options_text_error[]" <?php self::input_default('booking_form_attendee_options_text_error',$field_values); ?> />
																<em><?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?></em>
															</div>
														</div>
													</div>
												</div>
												<br style="clear:both" />
											</li>
											<?php endforeach; ?>
										</ul>
										<p><a href="#" class="attendee-form-custom-field-add">add</a></p>
									</div>									
								</div>
							</div>
							*/
							?>
							<?php do_action('em_booking_form_custom_admin_page_footer'); ?>
						</div>
						<p class="submit">				
							<input type="submit" name="events_update" value="<?php _e ( 'Save Forms', 'em-pro' ); ?> &raquo;" <?php if(!empty($js)) echo $js; ?> class="button-primary" />
						</p>
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('booking_fields'); ?>" />
						<input type="hidden" name="action" value="booking_fields" />
					</div>
				</div>
			</form>
		</div> <!-- wrap -->
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.bct-options').hide();
				//Booking Form
				var booking_template = $('#booking-custom-item-template').detach();
				$('.booking-form-custom-field-remove').live('click',function(e){
					e.preventDefault();
					$(this).parents('.booking-custom-item').remove();
				});
				$('.booking-form-custom-field-add').click(function(e){
					e.preventDefault();
					booking_template.clone().appendTo($(this).parents('.booking-custom').children('.booking-custom-body'));
				});	
				//Attendee Form
				var attendee_template = $('#attendee-custom-item-template').detach();
				$('.attendee-form-custom-field-add').click(function(e){
					e.preventDefault();
					attendee_template.clone().appendTo($(this).parents('.booking-custom').children('.booking-custom-body'));
				});	
				$('.booking-form-custom-field-options').live('click',function(e){
					e.preventDefault();
					if( $(this).attr('rel') != '1' ){
						$(this).parents('.booking-custom').find('.booking-form-custom-field-options').text('<?php _e('options','em-pro'); ?>').attr('rel','0')
						$(this).parents('.booking-custom-item').find('.booking-form-custom-type').trigger('change');
					}else{
						$(this).text('<?php _e('options','em-pro'); ?>').parents('.booking-custom-item').find('.bct-options').slideUp();
						$(this).attr('rel','0');
					}
				});
				//specifics
				$('.booking-form-custom-label').live('change',function(e){
					var parent_div =  $(this).parents('.booking-custom-item').first();
					var field_id = parent_div.find('input.booking-form-custom-fieldid').first();
					if( field_id.val() == '' ){
						field_id.val(escape($(this).val()).replace(/%[0-9]+/g,'_').toLowerCase());
					}
				});
				$('.booking-custom input[type=checkbox]').live('change',function(){
					var checkbox = $(this);
					if( checkbox.next().attr('type') == 'hidden' ){
						if( this.checked ){
							checkbox.next().val(1);
						}else{
							checkbox.next().val(0);
						}
					}
				});
				$('.booking-form-custom-type').live('change',function(){
					$('.bct-options').slideUp();
					var type_keys = {
						select : ['select','multiselect'],
						selection : ['checkboxes','radio'],
						checkbox : ['checkbox'],
						text : ['text','textarea','email','name'],
						registration : ['<?php echo implode("', '", self::$user_fields); ?>'],
						captcha : ['captcha']							
					}
					var select_box = $(this);
					var selected_value = select_box.val();
					$.each(type_keys, function(option,types){
						if( $.inArray(selected_value,types) > -1 ){
							//get parent div
							parent_div =  select_box.parents('.booking-custom-item').first();
							//slide the right divs in/out
							parent_div.find('.bct-'+option).slideDown();
							parent_div.find('.booking-form-custom-field-options').text('<?php _e('hide options','em-pro'); ?>').attr('rel','1');
						}
					});
				});
				$('.bc-link-up, .bc-link-down').live('click',function(e){
					e.preventDefault();
					item = $(this).parents('.booking-custom-item').first();
					if( $(this).hasClass('bc-link-up') ){
						if(item.prev().length > 0){
							item.prev().before(item);
						}
					}else{
						if( item.next().length > 0 ){
							item.next().after(item);
						}
					}
				});
				$('.bc-col-sort').live('mousedown',function(){
					parent_div =  $(this).parents('.booking-custom-item').first();
					parent_div.find('.bct-options').hide();
					parent_div.find('.booking-form-custom-field-options').text('<?php _e('options','em-pro'); ?>').attr('rel','0');
				});
				$( ".booking-custom .booking-custom-body" ).sortable({
					placeholder: "bc-highlight",
					handle:'.bc-col-sort'
				});
			});
		</script>		
		<?php
	}
	
	private function show_reg_fields(){
		return !is_user_logged_in() && get_option('dbem_bookings_anonymous'); 
	}
}
EM_Booking_Form::init();

?>