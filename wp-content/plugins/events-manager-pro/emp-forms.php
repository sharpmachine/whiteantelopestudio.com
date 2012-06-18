<?php
class EM_Forms {
	function init(){
		if( is_admin() ){
			add_action('em_create_events_submenu',array('EM_Forms', 'admin_menu'),1,1);
		}
	}
	
	function admin_menu($plugin_pages){
		$plugin_pages[] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Forms Editor','em-pro'),__('Forms Editor','em-pro'),'activate_plugins','events-manager-forms-editor',array('EM_Forms','admin_page'));
		return $plugin_pages; //use wp action/filters to mess with the menus
	}
	
	function admin_page(){
		global $EM_Notices;
		?>
		<div class='wrap'>
			<div class="icon32" id="icon-plugins"><br></div>
			<h2><?php _e('Forms Editor','em-pro'); ?></h2>
			<?php echo $EM_Notices; ?>
			<p><?php _e('On this page you can create/edit various forms used within Events Manager Pro.', 'em-pro' ); ?></p>
			<?php do_action('emp_forms_admin_page'); ?>
		</div> <!-- wrap -->
		<?php
	}
}
EM_Forms::init();


class EM_Form extends EM_Object {
	
	public $form_fields = array();
	public $form_name = 'Default';
	private $field_values = array();
	public $user_fields = array();
	protected $core_user_fields = array(
		'user_login' => 'Username Login',
		'user_email' => 'E-mail (required)',
		'first_name' => 'First Name',
		'last_name' => 'Last Name',
		'user_url' => 'Website',
		'aim' => 'AIM',
		'yim' => 'Yahoo IM',
		'jabber' => 'Jabber / Google Talk',
		'about' => 'Biographical Info'
	);
	protected $custom_user_fields = array();
	static $validate;
	
	function __construct( $form_data, $form_name=false, $user_fields = true ){
		if( is_array($form_data) ){
			//load form data from array
			$this->form_fields = $form_data;
		}else{
			//assume the text is the form name
			$this->form_fields = get_option($form_data);
			$this->form_name = $form_data;
		}
		if( !empty($form_name) ){
			$this->form_name = $form_name;
		}
		if( $user_fields ){
			$this->user_fields = apply_filters('emp_form_user_fields',$this->core_user_fields, $this);
			$this->custom_user_fields = array_diff($this->user_fields, $this->core_user_fields);
		}
	}
	
	function get_post( $validate = true ){
		foreach($this->form_fields as $fieldid => $field){
			$value = '';
			if(!empty($_REQUEST[$fieldid]) && !is_array($_REQUEST[$fieldid])){
				$this->field_values[$fieldid] = wp_kses_data(stripslashes($_REQUEST[$fieldid]));
			}elseif(!empty($_REQUEST[$fieldid]) && is_array($_REQUEST[$fieldid])){
				$this->field_values[$fieldid] = $_REQUEST[$fieldid];
			}
		}
		if( $validate ){
			return $this->validate();
		}
		return true;
	}
	
	function get_values(){
		return $this->field_values;
	}
	
	/**
	 * Prints html fields according to this field structure.
	 * @param array $booking_form_fields
	 */
	function __toString(){
		$return = '';
		foreach($this->form_fields as $field){
			$return .= self::output_field($field);
		}
		return apply_filters('emp_form_output',$return, $this);
	}
	
	function output_field($field, $post=true){
		ob_start();
		$default = '';
		if($post === true && !empty($_REQUEST[$field['fieldid']])) {
			$default = $_REQUEST[$field['fieldid']];
		}elseif( !empty($post) ){
			$default = $post;
		}
		switch($field['type']){
			case 'name':
			case 'email': //depreciated
				if( self::show_reg_fields() ){
					?>
					<p class="input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?> input-user-field">
						<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label> 
						<?php echo $this->output_field_input($field, $post); ?>
					</p>
					<?php
				}
				break;				
			case 'text':
				?>
				<p class="input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label> 
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;	
			case 'textarea':
				?>
				<p class="input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label> 
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;
			case 'checkbox':
				?>
				<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label>
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;
			case 'checkboxes':
				if(!is_array($default)) $default = array();
				$values = explode("\r\n",$field['options_selection_values']);
				?>
				<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label>
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;
			case 'radio':
				$values = explode("\r\n",$field['options_selection_values']);
				?>
				<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label>
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;
			case 'select':
			case 'multiselect':
				$values = explode("\r\n",$field['options_select_values']);
				$multi = $field['type'] == 'multiselect';
				if($multi && !is_array($default)) $default = array();
				?>
				<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label>
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;
			case 'captcha':
				if( !function_exists('recaptcha_get_html') ) { include_once(trailingslashit(plugin_dir_path(__FILE__)).'includes/lib/recaptchalib.php'); }
				if( function_exists('recaptcha_get_html') && !is_user_logged_in() ){
					?>
					<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label>
					<?php
					echo $this->output_field_input($field, $post);
				}
				break;
			default:
				if( array_key_exists($field['type'], $this->user_fields) && self::show_reg_fields() ){
					if( array_key_exists($field['type'], $this->core_user_fields) ){
						//registration fields
						?>
						<p class="input-<?php echo $field['type']; ?> input-user-field">
							<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label> 
							<?php echo $this->output_field_input($field, $post); ?>
						</p>
						<?php
					}elseif( array_key_exists($field['type'], $this->custom_user_fields) ) {
						?>
						<p class="input-<?php echo $field['type']; ?> input-user-field">
							<label for='<?php echo $field['fieldid'] ?>'><?php echo $field['label'] ?></label> 
							<?php do_action('em_form_output_field_custom_'.$field['type'], $field, $post); ?>
						</p>
						<?php
					}
				}
				break;
		}	
		return apply_filters('emp_forms_output_field', ob_get_clean(), $this);	
	}
	
	function output_field_input($field, $post=true){
		ob_start();
		$default = '';
		if($post === true && !empty($_REQUEST[$field['fieldid']])) {
			$default = $_REQUEST[$field['fieldid']];
		}elseif( $post !== true && !empty($post) ){
			$default = $post;
		}
		switch($field['type']){
			case 'name':
			case 'email': //depreciated
				if( self::show_reg_fields() ){
					?>
					<input type="text" name="<?php echo $field['fieldid'] ?>" id="<?php echo $field['fieldid'] ?>" class="input" value="<?php echo $default; ?>"  />
					<?php
				}
				break;				
			case 'text':
				?>
				<input type="text" name="<?php echo $field['fieldid'] ?>" id="<?php echo $field['fieldid'] ?>" class="input" value="<?php echo $default; ?>"  />
				<?php
				break;	
			case 'textarea':
				?>
				<textarea name="<?php echo $field['fieldid'] ?>" id="<?php echo $field['fieldid'] ?>" class="input"><?php echo $default; ?></textarea>
				<?php
				break;
			case 'checkbox':
				?>
				<input type="checkbox" name="<?php echo $field['fieldid'] ?>" id="<?php echo $field['fieldid'] ?>" value="1" <?php if($default) echo 'checked="checked"'; ?> />
				<?php
				break;
			case 'checkboxes':
				echo "<span class=\"input-group\">";
				if(!is_array($default)) $default = array();
				$values = explode("\r\n",$field['options_selection_values']);
				foreach($values as $value){ 
					$value = trim($value); 
					?><input type="checkbox" name="<?php echo $field['fieldid'] ?>[]" class="<?php echo $field['fieldid'] ?>" value="<?php echo esc_attr($value) ?>" <?php if(in_array($value, $default)) echo 'checked="checked"'; ?> /> <?php echo $value ?><br /><?php 
				}
				echo "</span>";
				break;
			case 'radio':
				echo "<span class=\"input-group\">";
				$values = explode("\r\n",$field['options_selection_values']);
				foreach($values as $value){
					$value = trim($value); 
					?><input type="radio" name="<?php echo $field['fieldid'] ?>" class="<?php echo $field['fieldid'] ?>" value="<?php echo esc_attr($value) ?>" <?php if($value == $default) echo 'checked="checked"'; ?> /> <?php echo $value ?><br /><?php
				}
				break;
				echo "</span>";
			case 'select':
			case 'multiselect':
				$values = explode("\r\n",$field['options_select_values']);
				$multi = $field['type'] == 'multiselect';
				if($multi && !is_array($default)) $default = (empty($default)) ? array():array($default);
				?>
				<select name="<?php echo $field['fieldid'] ?><?php echo ($multi) ? '[]':''; ?>" class="<?php echo $field['fieldid'] ?>" <?php echo ($multi) ? 'multiple':''; ?>>
				<?php 
					//calculate default value to be checked
					if( !$field['options_select_default'] ){
						?>
						<option value=""><?php echo esc_html($field['options_select_default_text']); ?></option>
						<?php
					}
					$count = 0;
				?>
				<?php foreach($values as $value): $value = trim($value); $count++; ?>
					<option <?php echo (($count == 1 && $field['options_select_default']) || ($multi && in_array($value, $default)) || ($value == $default) )?'selected="selected"':''; ?>>
						<?php echo esc_html($value) ?>
					</option>
				<?php endforeach; ?>
				</select>
				<?php
				break;
			case 'captcha':
				if( !function_exists('recaptcha_get_html') ) { include_once(trailingslashit(plugin_dir_path(__FILE__)).'includes/lib/recaptchalib.php'); }
				if( function_exists('recaptcha_get_html') && !is_user_logged_in() ){
					?>
					<span> 
						<?php echo recaptcha_get_html($field['options_captcha_key_pub'], $field['options_captcha_error'], is_ssl()); ?>
					</span>
					<?php
				}
				break;
			default:
				if( array_key_exists($field['type'], $this->user_fields) && self::show_reg_fields() ){
					if( array_key_exists($field['type'], $this->core_user_fields) ){
						//registration fields
						?>
						<input type="text" name="<?php echo $field['fieldid'] ?>" id="<?php echo $field['fieldid'] ?>" class="input"  value="<?php echo $default; ?>" />
						<?php
					}
				}
				break;
		}	
		return apply_filters('emp_forms_output_field_input', ob_get_clean(), $this);	
	}
	
	/**
	 * Validates all fields, if false, an array of objects is returned.
	 * @return array|string
	 */
	function validate(){
		foreach( array_keys($this->form_fields) as $field_id ){
			$value = ( array_key_exists($field_id, $this->field_values) ) ? $this->field_values[$field_id] : '';
			$this->validate_field($field_id, $value);
		}
		if( count($this->get_errors()) > 0 ){
			return false;
		}
		return true;
	}
	
	/**
	 * Validates a field and adds errors to the object it's referring to (can be any extension of EM_Object)
	 * @param array $field
	 * @param mixed $value
	 */
	function validate_field( $field_id, $value ){
		$field = array_key_exists($field_id, $this->form_fields) ? $this->form_fields[$field_id]:false;
		$value = (is_array($value)) ? $value:trim($value);
		$err = sprintf(get_option('em_booking_form_error_required'), $field['label']);
		if( is_array($field) ){
			$result = true; //innocent until proven guilty
			switch($field['type']){
				case 'email': //depreciated
				case 'user_email':
					if( self::show_reg_fields() ){
						// Check the e-mail address
						if ( $value == '' ) {
							$this->add_error($err);
							$result = false;
						} elseif ( ! is_email( $value ) ) {
							$this->add_error( __( 'The email address isn&#8217;t correct.', 'dbem') );
							$result = false;
						}
						//regex
						if( !empty($field['options_reg_regex']) && !@preg_match('/'.$field['options_reg_regex'].'/',$value) ){
							$this_err = (!empty($field['options_reg_error'])) ? $field['options_reg_error']:$err;
							$this->add_error($this_err);
							$result = false;
						}
					}
					break;
				case 'name':
					if( self::show_reg_fields() ){
						//regex
						if( !empty($field['options_text_regex']) && !@preg_match('/'.$field['options_text_regex'].'/',$value) ){
							if( !($value == '' && $field['required']) ){
								$this_err = (!empty($field['options_text_error'])) ? $field['options_text_error']:$err;
								$this->add_error($this_err);
								$result = false;
							}
						}
						//non-empty match
						if( empty($value) && $field['required'] ){
							$this->add_error($err);
							$result = false;
						}
					}
					break;
				case 'text':
				case 'textarea':
					//regex
					if( !empty($field['options_text_regex']) && !@preg_match('/'.$field['options_text_regex'].'/',$value) ){
						if( !($value == '' && $field['required']) ){
							$this_err = (!empty($field['options_text_error'])) ? $field['options_text_error']:$err;
							$this->add_error($this_err);
							$result = false;
						}
					}
					//non-empty match
					if( $result && empty($value) && $field['required'] ){
						$this->add_error($err);
						$result = false;
					}
					break;
				case 'checkbox':
					//non-empty match
					if( empty($value) && $field['required'] ){
						$this_err = (!empty($field['options_checkbox_error'])) ? $field['options_checkbox_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}
					break;
				case 'checkboxes':
					$values = explode("\r\n",$field['options_selection_values']);
					array_walk($values,'trim');
					if( !is_array($value) ) $value = array();
					//in-values
					if( (empty($value) && $field['required']) || count(array_diff($value, $values)) > 0 ){
						$this_err = (!empty($field['options_selection_error'])) ? $field['options_selection_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}
					break;
				case 'radio':
					$values = explode("\r\n",$field['options_selection_values']);
					array_walk($values,'trim');
					//in-values
					if( (!in_array($value, $values) || empty($value)) && $field['required'] ){
						$this_err = (!empty($field['options_selection_error'])) ? $field['options_selection_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;
				case 'multiselect':
					$values = explode("\r\n",$field['options_select_values']);
					array_walk($values,'trim');
					if( !is_array($value) ) $value = array();
					//in_values
					if( (empty($value) && $field['required']) || count(array_diff($value, $values)) > 0 ){
						$this_err = (!empty($field['options_select_error'])) ? $field['options_select_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;
				case 'select':
					$values = explode("\r\n",$field['options_select_values']);
					array_walk($values,'trim');
					//in-values
					if( (!in_array($value, $values) || empty($value)) && $field['required'] ){
						$this_err = (!empty($field['options_select_error'])) ? $field['options_select_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;
				case 'captcha':
					if( !function_exists('recaptcha_get_html') ) { include_once(trailingslashit(plugin_dir_path(__FILE__)).'includes/lib/recaptchalib.php'); }
					if( function_exists('recaptcha_check_answer') && !is_user_logged_in() && !defined('EMP_CHECKED_CAPTCHA') ){
						$resp = recaptcha_check_answer($field['options_captcha_key_priv'], $_SERVER['REMOTE_ADDR'], $_REQUEST['recaptcha_challenge_field'], $_REQUEST['recaptcha_response_field']);
						$result = $resp->is_valid;
						if(!$result){
							$err = !empty($field['options_captcha_error']) ? $field['options_captcha_error']:$err;
							$this->add_error($err);
						}
						define('EMP_CHECKED_CAPTCHA', true); //captchas can only be checked once, and since we only need one captcha per submission....
					}
					break;
				default:
					//Registration and custom fields
					if( (!is_user_logged_in() || defined('EM_FORCE_REGISTRATION')) && array_key_exists($field['type'], $this->user_fields) ){
						//preliminary checks
						//regex
						if( !empty($field['options_reg_regex']) && !@preg_match('/'.$field['options_reg_regex'].'/',$value) ){
							if( !($value == '' && !$field['required']) ){
								$this_err = (!empty($field['options_reg_error'])) ? $field['options_reg_error']:$err;
								$this->add_error($this_err);
								$result = false;
							}
						}
						//non-empty match
						if( empty($value) && ($field['required']) ){
							$this->add_error($err);
							$result = false;
						}
						//custom field chekcs
						if( array_key_exists($field['type'], $this->custom_user_fields)) {
							//custom field, so just apply 
							$result = apply_filters('em_form_validate_field_custom', $result, $field, $value, $this);
						}
					}
					break;
			}
		}else{
			$result = false;
		}
		return apply_filters('emp_form_validate_field',$result, $field, $value, $this);
	}
	
	/**
	 * Gets an array of field keys accepted by the booking form 
	 */
	function get_fields_map(){
		$map = array (
			'fieldid','label','type','required',
			'options_select_values','options_select_default','options_select_default_text','options_select_error',
			'options_selection_values','options_selection_default','options_selection_error',
			'options_checkbox_error','options_checkbox_checked',
			'options_text_regex','options_text_error',
			'options_reg_regex', 'options_reg_error',
			'options_captcha_theme','options_captcha_key_priv','options_captcha_key_pub', 'options_captcha_error');
		return apply_filters('em_form_get_fields_map', $map);
	}
	
	/*
	 * --------------------------------------------------------
	 * Admin-Side Functions
	 * --------------------------------------------------------
	 */
	
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
		return apply_filters('emp_form_get_input_default',$return, $key, $field_values, $type, $value);
	}
	function input_default($key, $fields, $type = 'text', $value=""){ echo self::get_input_default($key, $fields, $type, $value); }

	
	private function show_reg_fields(){
		return (!is_user_logged_in() || defined('EM_FORCE_REGISTRATION')) && get_option('dbem_bookings_anonymous'); 
	}
	
	function editor($user_fields = true, $custom_fields = true, $captcha_fields = true){
		$fields = $this->form_fields;
		if( empty($fields) ){ $fields = array(self::get_fields_map());  }
		$fields['blank_em_template'] = self::get_fields_map();
		$form_name = "em-form-". sanitize_title_with_dashes($this->form_name); 
		?>
		<form method="post" action="" class="em-form-custom" id="<?php echo $form_name; ?>">
			<div>
				<div class="booking-custom-head">
					<div class='bc-col-sort bc-col'>&nbsp;</div>
					<div class='bc-col-label bc-col'><?php _e('Label','em-pro'); ?></div>
					<div class='bc-col-id bc-col'><?php _e('Field ID','em-pro'); ?><a title="<?php _e('DO NOT change these values if you want to keep your field settings associated with previous booking fields.'); ?>">?</a></div>
					<div class='bc-col-type bc-col'><?php _e('Type','em-pro'); ?></div>
					<div class='bc-col-required bc-col'><?php _e('Required','em-pro'); ?></div>
				</div>
				<ul class="booking-custom-body">
					<?php foreach($fields as $field_key => $field_values): ?>
					<li class="booking-custom-item" <?php if( $field_key === 'blank_em_template' ){ echo 'id="booking-custom-item-template"'; }; ?>>
						<div class='bc-col-sort bc-col'>&nbsp;</div>
						<div class='bc-col-label bc-col'><input type="text" name="label[]" class="booking-form-custom-label" <?php self::input_default('label',$field_values); ?> /></div>
						<div class='bc-col-id bc-col'><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" <?php self::input_default('fieldid',$field_values); ?> /></div>
						<div class='bc-col-type bc-col'>
							<select name="type[]" class="booking-form-custom-type">
								<option value=""><?php echo _e('Select Type','em-pro'); ?></option>
								<?php if($custom_fields): ?>
								<optgroup label="<?php _e('Customizable Fields','em-pro'); ?>">
									<option <?php self::input_default('type',$field_values,'select','checkbox'); ?>>checkbox</option>
									<option <?php self::input_default('type',$field_values,'select','text'); ?>>text</option>
									<option <?php self::input_default('type',$field_values,'select','textarea'); ?>>textarea</option>
									<option <?php self::input_default('type',$field_values,'select','checkboxes'); ?>>checkboxes</option>
									<option <?php self::input_default('type',$field_values,'select','radio'); ?>>radio</option>
									<option <?php self::input_default('type',$field_values,'select','select'); ?>>select</option>
									<option <?php self::input_default('type',$field_values,'select','multiselect'); ?>>multiselect</option>
									<?php if($captcha_fields): ?>
									<option <?php self::input_default('type',$field_values,'select','captcha'); ?>>captcha</option>
									<?php endif; ?>
								</optgroup>
								<?php endif; ?>
								<?php if($user_fields): ?>
								<optgroup label="<?php _e('Registration Fields','em-pro'); ?>">
									<option value="name" <?php self::input_default('type',$field_values,'select','name'); ?>>Name</option>
									<?php foreach( $this->core_user_fields as $field_id => $field_name ): ?>
									<option value="<?php echo $field_id; ?>" <?php self::input_default('type',$field_values,'select',$field_id); ?>><?php echo $field_name; ?></option>
									<?php endforeach; ?>
								</optgroup>
								<?php 
									if( count($this->custom_user_fields) > 0 ){
										?>
										<optgroup label="<?php _e('Custom Registration Fields','em-pro'); ?>">
											<?php foreach( $this->custom_user_fields as $field_id => $field_name ): ?>
											<option value="<?php echo $field_id; ?>" <?php self::input_default('type',$field_values,'select',$field_id); ?>><?php echo $field_name; ?></option>
											<?php endforeach; ?>
										</optgroup>
										<?php
									} 
								?>
								<?php endif; ?>
							</select>
						</div>
						<div class='bc-col-required bc-col'>
							<input type="checkbox" class="booking-form-custom-required" value="1" <?php self::input_default('required',$field_values,'checkbox'); ?> />
							<input type="hidden" name="required[]" <?php self::input_default('required',$field_values,'text'); ?> />
						</div>
						<div class='bc-col-options bc-col'><a href="#" class="booking-form-custom-field-remove"><?php _e('remove','em-pro'); ?></a> | <a href="#" class="booking-form-custom-field-options"><?php _e('options','em-pro'); ?></a></div>
						<div class='booking-custom-types'>
							<?php if($custom_fields): ?>
							<div class="bct-select bct-options" style="display:none;">
								<!-- select,multiselect -->
								<div class="bct-field">
									<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
									<div class="bct-input">
										<textarea name="options_select_values[]"><?php self::input_default('options_select_values',$field_values,'textarea'); ?></textarea>
										<em><?php _e('Available options, one per line.','em-pro'); ?></em>	
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Use Default?','em-pro'); ?></div>
									<div class="bct-input">
										<input type="checkbox" <?php self::input_default('options_select_default',$field_values,'checkbox'); ?> value="1" />
										<input type="hidden" name="options_select_default[]" <?php self::input_default('options_select_default',$field_values); ?> /> 
										<em><?php _e('If checked, the first value above will be used.','em-pro'); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Default Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_select_default_text[]" <?php self::input_default('options_select_default_text',$field_values,'text',__('Select ...','em-pro')); ?> />
										<em><?php _e('Shown when a default value isn\'t selected, selected by default.','em-pro'); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_select_error[]" <?php self::input_default('options_select_error',$field_values); ?> />
										<em><?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?></em>
									</div>
								</div>
							</div>
							<div class="bct-selection bct-options" style="display:none;">
								<!-- checkboxes,radio -->
								<div class="bct-field">
									<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
									<div class="bct-input">
										<textarea name="options_selection_values[]"><?php self::input_default('options_selection_values',$field_values,'textarea'); ?></textarea>
										<em><?php _e('Available options, one per line.','em-pro'); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_selection_error[]" <?php self::input_default('options_selection_error',$field_values); ?> />
										<em><?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?></em>
									</div>
								</div>
							</div>
							<div class="bct-checkbox bct-options" style="display:none;">
								<!-- checkbox -->
								<div class="bct-field">
									<div class="bct-label"><?php _e('Checked by default?','em-pro'); ?></div>
									<div class="bct-input">
										<input type="checkbox" <?php self::input_default('options_checkbox_checked',$field_values,'checkbox'); ?> value="1" />
										<input type="hidden" name="options_checkbox_checked[]" <?php self::input_default('options_checkbox_checked',$field_values); ?> /> 
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_checkbox_error[]" <?php self::input_default('options_checkbox_error',$field_values); ?> />
										<em><?php _e('This error will show if this box is not checked.','em-pro'); ?></em>
									</div>
								</div>
							</div>
							<div class="bct-text bct-options" style="display:none;">
								<!-- text,textarea,email,name -->
								<div class="bct-field">
									<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_text_regex[]" <?php self::input_default('options_text_regex',$field_values); ?> />
										<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_text_error[]" <?php self::input_default('options_text_error',$field_values); ?> />
										<em><?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?></em>
									</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if($user_fields): ?>
							<div class="bct-registration bct-options" style="display:none;">
								<!-- registration -->
								<div class="bct-field">
									<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_reg_regex[]" <?php self::input_default('options_reg_regex',$field_values); ?> />
										<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_reg_error[]" <?php self::input_default('options_reg_error',$field_values); ?> />
										<em><?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?></em>
									</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if($captcha_fields): ?>
							<div class="bct-captcha bct-options" style="display:none;">
								<!-- captcha -->
								<?php 
									$uri = parse_url(get_option('siteurl')); 
									$recaptcha_url = "https://www.google.com/recaptcha/admin/create?domains={$uri['host']}&amp;app=wordpress"; 
								?>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Private Key','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_captcha_key_priv[]" <?php self::input_default('options_captcha_key_priv',$field_values); ?> />
										<em><?php echo sprintf(__('Required, get your keys <a href="%s">here</a>','em-pro'),$recaptcha_url); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Public Key','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_captcha_key_pub[]" <?php self::input_default('options_captcha_key_pub',$field_values); ?> />
										<em><?php echo sprintf(__('Required, get your keys <a href="%s">here</a>','em-pro'),$recaptcha_url); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_captcha_error[]" <?php self::input_default('options_captcha_error',$field_values); ?> />
										<em><?php _e('This error will show if the captcha is not correct.','em-pro'); ?></em>
									</div>
								</div>
							</div>
							<?php endif; ?>
						</div>
						<br style="clear:both" />
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<p>
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('form_fields'); ?>" />
				<input type="hidden" name="form_action" value="form_fields" />
				<input type="hidden" name="form_name" value="<?php echo $this->form_name; ?>" />
				<input type="button" value="<?php _e('Add booking field','em-pro'); ?>" class="booking-form-custom-field-add button-secondary">
				<input type="submit" name="events_update" value="<?php _e ( 'Save Form', 'em-pro' ); ?> &raquo;" class="button-primary" />
			</p>
		</form>	
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.bct-options').hide();
				//Booking Form
				var booking_template = $('#<?php echo $form_name; ?> #booking-custom-item-template').detach();
				$('#<?php echo $form_name; ?>').delegate('.booking-form-custom-field-remove', 'click', function(e){
					e.preventDefault();
					$(this).parents('.booking-custom-item').remove();
				});
				$('#<?php echo $form_name; ?> .booking-form-custom-field-add').click(function(e){
					e.preventDefault();
					booking_template.clone().appendTo($(this).parents('.em-form-custom').find('ul.booking-custom-body').first());
				});
				$('#<?php echo $form_name; ?>').delegate('.booking-form-custom-field-options', 'click', function(e){
					e.preventDefault();
					if( $(this).attr('rel') != '1' ){
						$(this).parents('.em-form-custom').find('.booking-form-custom-field-options').text('<?php _e('options','em-pro'); ?>').attr('rel','0')
						$(this).parents('.booking-custom-item').find('.booking-form-custom-type').trigger('change');
					}else{
						$(this).text('<?php _e('options','em-pro'); ?>').parents('.booking-custom-item').find('.bct-options').slideUp();
						$(this).attr('rel','0');
					}
				});
				//specifics
				$('#<?php echo $form_name; ?>').delegate('.booking-form-custom-label', 'change', function(e){
					var parent_div =  $(this).parents('.booking-custom-item').first();
					var field_id = parent_div.find('input.booking-form-custom-fieldid').first();
					if( field_id.val() == '' ){
						field_id.val(escape($(this).val()).replace(/%[0-9]+/g,'_').toLowerCase());
					}
				});
				$('#<?php echo $form_name; ?>').delegate('input[type="checkbox"]', 'change', function(){
					var checkbox = $(this);
					if( checkbox.next().attr('type') == 'hidden' ){
						if( checkbox.is(':checked') ){
							checkbox.next().val(1);
						}else{
							checkbox.next().val(0);
						}
					}
				});
				$('#<?php echo $form_name; ?>').delegate('.booking-form-custom-type', 'change', function(){
					$('.bct-options').slideUp();
					var type_keys = {
						select : ['select','multiselect'],
						selection : ['checkboxes','radio'],
						checkbox : ['checkbox'],
						text : ['text','textarea','email','name'],
						registration : ['<?php echo implode("', '", array_keys($this->user_fields)); ?>'],
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
				$('#<?php echo $form_name; ?>').delegate('.bc-link-up, #<?php echo $form_name; ?> .bc-link-down', 'click', function(e){
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
				$('#<?php echo $form_name; ?>').delegate('.bc-col-sort', 'mousedown', function(){
					parent_div =  $(this).parents('.booking-custom-item').first();
					parent_div.find('.bct-options').hide();
					parent_div.find('.booking-form-custom-field-options').text('<?php _e('options','em-pro'); ?>').attr('rel','0');
				});
				$("#<?php echo $form_name; ?> .booking-custom-body" ).sortable({
					placeholder: "bc-highlight",
					handle:'.bc-col-sort'
				});
			});
		</script>
		<?php
	}
	
	function editor_save(){
		//Update Values
		return update_option('em_booking_form_fields',$this->form_fields);
	}
	
	function editor_get_post(){
		if( !empty($_REQUEST['form_action']) && $_REQUEST['form_action'] == 'form_fields' && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_fields') ){
			//Booking form fields
			$fields_map = self::get_fields_map();
			//extract request info back into item lists, but first assign fieldids to new items
			foreach( $_REQUEST['fieldid'] as $fieldid_key => $fieldid ){
				if( $_REQUEST['type'][$fieldid_key] == 'name' ){ //name field
					$_REQUEST['fieldid'][$fieldid_key] = 'user_name';
				}elseif( array_key_exists($_REQUEST['type'][$fieldid_key], $this->user_fields) ){ //other fields
					$_REQUEST['fieldid'][$fieldid_key] = $_REQUEST['type'][$fieldid_key];
				}elseif( empty($fieldid) ){
					$_REQUEST['fieldid'][$fieldid_key] = sanitize_title($_REQUEST['label'][$fieldid_key]); //assign unique id
				}
			}
			//get field values
			$this->form_fields = array();
			foreach( $_REQUEST as $key => $value){
				global $allowedposttags;
				if( is_array($value) && in_array($key,$fields_map) ){
					foreach($value as $item_index => $item_value){
						$item_value = stripslashes(wp_kses($item_value, $allowedposttags));
						$this->form_fields[$_REQUEST['fieldid'][$item_index]][$key] = $item_value;
					}
				}
			}
			return true;
		}
		return false;
	}

}