<?php

class acf_Image extends acf_Field
{
	
	/*--------------------------------------------------------------------------------------
	*
	*	Constructor
	*
	*	@author Elliot Condon
	*	@since 1.0.0
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($parent)
	{
    	parent::__construct($parent);
    	
    	$this->name = 'image';
		$this->title = __('Image','acf');
		
		add_action('admin_head-media-upload-popup', array($this, 'popup_head'));
		add_filter('get_media_item_args', array($this, 'allow_img_insertion'));
   	}
   	
   	
   	/*--------------------------------------------------------------------------------------
	*
	*	admin_print_scripts / admin_print_styles
	*
	*	@author Elliot Condon
	*	@since 3.0.1
	* 
	*-------------------------------------------------------------------------------------*/
	
	function allow_img_insertion($vars)
	{
	    $vars['send'] = true;
	    return($vars);
	}
	
   	
   	/*--------------------------------------------------------------------------------------
	*
	*	admin_print_scripts / admin_print_styles
	*
	*	@author Elliot Condon
	*	@since 3.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function admin_print_scripts()
	{
		wp_enqueue_script(array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-tabs',

			'thickbox',
			'media-upload',			
		));
	}
	
	function admin_print_styles()
	{
  		wp_enqueue_style(array(
			'thickbox',		
		));
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	create_field
	*
	*	@author Elliot Condon
	*	@since 2.0.5
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_field($field)
	{
		// vars
		$class = "";
		$file_src = "";
		$preview_size = isset($field['preview_size']) ? $field['preview_size'] : 'medium';
		
		// get image url
		if($field['value'] != '' && is_numeric($field['value']))
		{
			$file_src = wp_get_attachment_image_src($field['value'], $preview_size);
			$file_src = $file_src[0];
			
			if($file_src) $class = "active";
		}
		
		// html
		echo '<div class="acf_image_uploader ' . $class . '" data-preview_size="' . $preview_size . '">';
			echo '<a href="#" class="remove_image"></a>';
			echo '<img src="' . $file_src . '" alt=""/>';	
			echo '<input class="value" type="hidden" name="' . $field['name'] . '" value="' . $field['value'] . '" />';
			echo '<p>'.__('No image selected','acf').'. <input type="button" class="button" value="'.__('Add Image','acf').'" /></p>';
		echo '</div>';
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	create_options
	*
	*	@author Elliot Condon
	*	@since 2.0.6
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_options($key, $field)
	{	
		// vars
		$field['save_format'] = isset($field['save_format']) ? $field['save_format'] : 'url';
		$field['preview_size'] = isset($field['preview_size']) ? $field['preview_size'] : 'thumbnail';
		
		?>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Return Value",'acf'); ?></label>
			</td>
			<td>
				<?php 
				$this->parent->create_field(array(
					'type'	=>	'radio',
					'name'	=>	'fields['.$key.'][save_format]',
					'value'	=>	$field['save_format'],
					'layout'	=>	'horizontal',
					'choices' => array(
						'url'	=>	'Image URL',
						'id'	=>	'Attachment ID'
					)
				));
				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Preview Size",'acf'); ?></label>
			</td>
			<td>
				<?php 
				$this->parent->create_field(array(
					'type'	=>	'radio',
					'name'	=>	'fields['.$key.'][preview_size]',
					'value'	=>	$field['preview_size'],
					'layout'	=>	'horizontal',
					'choices' => array(
						'thumbnail'	=>	'Thumbnail',
						'medium'	=>	'Medium',
						'large'		=>	'Large',
						'full'		=>	'Full'
					)
				));
				?>
			</td>
		</tr>
		<?php
	}


	 
	/*---------------------------------------------------------------------------------------------
	 * popup_head - STYLES MEDIA THICKBOX
	 *
	 * @author Elliot Condon
	 * @since 1.1.4
	 * 
	 ---------------------------------------------------------------------------------------------*/
	function popup_head()
	{	
		if(isset($_GET["acf_type"]) && $_GET['acf_type'] == 'image')
		{
			$tab = isset($_GET['tab']) ? $_GET['tab'] : "type"; // "type" is the upload tab
			$preview_size = isset($_GET['acf_preview_size']) ? $_GET['acf_preview_size'] : 'medium';
			
?><style type="text/css">
	#media-upload-header #sidemenu li#tab-type_url,
	#media-upload-header #sidemenu li#tab-gallery, 
	#media-items .media-item table.slidetoggle,
	#media-items .media-item a.toggle {
		display: none !important;
	}
	
	#media-items .media-item {
		min-height: 68px;
	}
	
	#media-items .media-item .acf-checkbox {
		float: left;
		margin: 28px 10px 0;
	}
	
	#media-items .media-item .pinkynail {
		max-width: 64px;
		max-height: 64px;
		display: block !important;
	}
	
	#media-items .media-item .filename.new {
		min-height: 0;
		padding: 25px 10px 10px;
		line-height: 14px;
		
	}
	
	#media-items .media-item .title {
		line-height: 14px;
	}
	
	#media-items .media-item .button {
		float: right;
		margin: -2px 0 0 10px;
	}
	
	#media-upload .ml-submit {
		display: none !important;
	}

	#media-upload .acf-submit {
		margin: 1em 0;
		padding: 1em 0;
		position: relative;
		overflow: hidden;
		display: none; /* default is hidden */
	}
	
	#media-upload .acf-submit a {
		float: left;
		margin: 0 10px 0 0;
	}

</style>
<script type="text/javascript">
(function($){
	
	/*
	*  Vars
	*/
	
	// generate the preview size (150x150)
	var preview_size = "<?php echo get_option($preview_size . '_size_w'); ?>x<?php echo get_option($preview_size . '_size_h'); ?>";
		
		
	/*
	*  Select Image
	*
	*  @created : 28/03/2012
	*/
	
	$('#media-items .media-item .filename a.acf-select').live('click', function(){
		
		var id = $(this).attr('href');
		var src = $(this).closest('.media-item').find('img').attr('src');
		
		
		// image will be thumbnail size, find last 150x150.png and replace with 150x150.png
		src = src.replace(/-([0-9]+)x([0-9]+)\.([a-zA-Z]{2,4})/, "-" + preview_size + ".$3");
		
		
		// updae acf_div
		self.parent.acf_div.find('input.value').val( id );
		self.parent.acf_div.find('img').attr('src', src );
		self.parent.acf_div.addClass('active');


		// validation
		self.parent.acf_div.closest('.field').removeClass('error');
		
		
		// reset acf_div and return false
		self.parent.acf_div = null;
		self.parent.tb_remove();
		
		return false;
	});
	
	
	$('#acf-add-selected').live('click', function(){ 
		 
		// check total 
		var total = $('#media-items .media-item .acf-checkbox:checked').length;
		if(total == 0) 
		{ 
			alert("<?php _e("No images selected",'acf'); ?>"); 
			return false; 
		} 
		
		
		// loop through and add rows / images
		$('#media-items .media-item .acf-checkbox:checked').each(function(i){
			
			var id = $(this).val();
			var src = $(this).closest('.media-item').find('img').attr('src');
			
			
			// image will be thumbnail size, find last 150x150.png and replace with 150x150.png
			src = src.replace(/-([0-9]+)x([0-9]+)\.([a-zA-Z]{2,4})/, "-" + preview_size + ".$3");
			
			
			// update acf_div
			self.parent.acf_div.find('input.value').val( id ); 
 			self.parent.acf_div.find('img').attr('src', src ); 
 			self.parent.acf_div.addClass('active'); 
 	 
 	 
 			// validation 
 			self.parent.acf_div.closest('.field').removeClass('error'); 

 			 
 			if((i+1) < total) 
 			{ 
 				// add row 
 				self.parent.acf_div.closest('.repeater').find('.table_footer #r_add_row').trigger('click'); 
 			 
 				// set acf_div to new row image 
 				self.parent.acf_div = self.parent.acf_div.closest('.repeater').find('> table > tbody > tr.row:last-child .acf_image_uploader'); 
 			} 
 			else 
 			{ 
 				// reset acf_div and return false 
				self.parent.acf_div = null; 
				self.parent.tb_remove(); 
 			} 
 			
		}); 
		
		return false;
		 
	}); 
	
	
	// set a interval function to add buttons to media items
	function acf_add_buttons()
	{
		// vars
		var is_sub_field = (self.parent.acf_div && self.parent.acf_div.closest('.repeater').length > 0) ? true : false;
		
		
		// add submit after media items (on for sub fields)
		if($('.acf-submit').length == 0 && is_sub_field)
		{
			$('#media-items').after('<div class="acf-submit"><a id="acf-add-selected" class="button"><?php _e("Add selected Images",'acf'); ?></a></div>');
		}
		
		
		// add buttons to media items
		$('#media-items .media-item:not(.acf-active)').each(function(){
			
			// show the add all button
			$('.acf-submit').show();
			
			// needs attachment ID
			if($(this).children('input[id*="type-of-"]').length == 0){ return false; }
			
			// only once!
			$(this).addClass('acf-active');
			
			// find id
			var id = $(this).children('input[id*="type-of-"]').attr('id').replace('type-of-', '');
			
			// if inside repeater, add checkbox
			if(is_sub_field)
			{
				$(this).prepend('<input type="checkbox" class="acf-checkbox" value="' + id + '" <?php if($tab == "type"){echo 'checked="checked"';} ?> />');
			}
			
			// change text of insert button, and add new button
			$(this).find('.filename.new').append('<a href="' + id + '" class="button acf-select"><?php _e("Select Image",'acf'); ?></a>');
			
		});
	}
	<?php
	
	// run the acf_add_buttons ever 500ms when on the image upload tab
	if($tab == 'type'): ?>
	var acf_t = setInterval(function(){
		acf_add_buttons();
	}, 500);
	<?php endif; ?>
	
	
	// add acf input filters to allow for tab navigation
	$(document).ready(function(){
		
		setTimeout(function(){
			acf_add_buttons();
		}, 1);
		
		
		$('form#filter, form#image-form').each(function(){
			
			$(this).append('<input type="hidden" name="acf_preview_size" value="<?php echo $preview_size; ?>" />');
			$(this).append('<input type="hidden" name="acf_type" value="image" />');
			
		});
	});
				
})(jQuery);
</script><?php

		}
	}
	

	/*--------------------------------------------------------------------------------------
	*
	*	get_value_for_api
	*
	*	@author Elliot Condon
	*	@since 3.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function get_value_for_api($post_id, $field)
	{
		// vars
		$format = isset($field['save_format']) ? $field['save_format'] : 'url';
		
		$value = parent::get_value($post_id, $field);
		
		if($format == 'url')
		{
			$value = wp_get_attachment_url($value);
		}
		
		return $value;
	}
	
	
		
}

?>