<?php
/*
Plugin Name: OrangeBox
Plugin URI: http://orangebox.davidpaulhamilton.net
Description: OrangeBox is a Lightweight, Cross-Browser, Automated jQuery Lightbox Script. It can display images, quicktime videos, YouTube and Vimeo videos, Flash SWF files, iFrames, and inline content (along with links in the inline content to open another lightbox). It also groups items together (regardless of their content type) and shows easy to use navigation.
Version: 1.0.0
Author: David Hamilton
Author URI: http://davidpaulhamilton.net
License: http://creativecommons.org/licenses/by/3.0/
*/

// Check to see if the class exists, if not, create it
if (!class_exists("OrangeBox")) {
	class OrangeBox {
		var $ob_adminOptionSet = "OrangeBoxAdminOptions";
		function getDefaultOptions() {
			// Set Default Options
			$ob_defaultOptions = array(
			//Bool
				'debug' => true,
				'automateImage' => true,
				'automateVideo' => true,
				'automateSWF' => true,
				'automateOnline' => true,
				'addThis' => true,
				'autoplay' => "",
				'fadeCaption' => true,
				'fadeControls' => "",
				'orangeControls' => "",
				'showDots' => "",
				'showNav' => true,
				'showClose' => true,
				'keyboardNavigation' => true,
			//Timers
				'fadeTime' => 200,
				'preloaderDelay' => 600,
				'slideshowTimer' => 3000,
			//Messages
				'notFound' => "Not Found",
			//CSS
				'overlayOpacity' => 0.95,
				'contentBorderWidth' => 4,
			//Max-Min Values
				'contentMinHeight' => 100,
				'contentMinWidth' => 200,
				'iframeHeight' => .75,
				'iframeWidth' => .75,
				'inlineHeight' => 0,
				'inlineWidth' => .5,
				'maxImageHeight' => .75,
				'maxImageWidth' => .75,
				'maxVideoHeight' => 390,
				'maxVideoWidth' => 640
			);
			return $ob_defaultOptions;
		}
		
		function getAdminOptions() {
			//Get Current Options or Initialize Defaults
			$ob_adminOptions = $this->getDefaultOptions();
			$ob_options = get_option($this->ob_adminOptionSet);
			if(!empty($ob_options)) {
				foreach($ob_options as $key => $option)
					$ob_adminOptions[$key] = $option;
			}
			update_option($this->ob_adminOptionSet, $ob_adminOptions);
			return $ob_adminOptions;
		}

		function OrangeBox() {
			// Constructor
		}
		
		function init() {
			// Set Options on Activation
			$this->getAdminOptions();
		}
		
		function ob_automate($content) {
			// Add rel="lightbox[$postname]" to all links on a page that do not have a rel set
			$ob_options = $this->getAdminOptions();
			global $post;
			if($ob_options['automateImage'] == "true") {
				$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.(?:bmp|gif|jpg|jpeg|png)['\"][^\>]*)>/i";
				$replacement = '$1 rel="lightbox['.$post->ID.']">';
				$content = preg_replace($pattern, $replacement, $content);
			}
			if($ob_options['automateVideo'] == "true") {
				$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.(?:mov|m4v|mp4)[\"]?[^\>]*)>/i";
				$replacement = '$1 rel="lightbox['.$post->ID.']">';
				$content = preg_replace($pattern, $replacement, $content);
			}
			if($ob_options['automateSWF'] == "true") {
				$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.swf[\"]?[^\>]*)>/i";
				$replacement = '$1 rel="lightbox['.$post->ID.']">';
				$content = preg_replace($pattern, $replacement, $content);
			}
			if($ob_options['automateOnline'] == "true") {
				$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?(?:(youtube+\.\w{2,3}\/watch\?v=[\w-]{11}(['\"]|[\&]))|(vimeo\.com\/(\w{1,10})(['\"]|[\?])))[^\>]*)>/i";
				$replacement = '$1 rel="lightbox['.$post->ID.']">';
				$content = preg_replace($pattern, $replacement, $content);
			}
			return $content;
		}
		
		function ob_addHeader() {
			// Add Style Sheet and Script
			$ob_options = $this->getAdminOptions();
			$ob_defaultOptions = $this->getDefaultOptions();
			if (!is_admin()) {
				echo '<link type="text/css" rel="stylesheet" href="' . WP_PLUGIN_URL . '/orangebox/css/orangebox.css" />' . "\n";
				// De-register jQuery to make sure we're using 1.6
				wp_deregister_script( 'jquery' );
				wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js', null, '1.6');
				wp_enqueue_script( 'jquery' );
			
				if($ob_options['debug'] == "true") {
					wp_enqueue_script('orangebox', WP_PLUGIN_URL . '/orangebox/js/orangebox.js', array('jquery'), '2.0.3');
				}
				else {
					wp_enqueue_script('orangebox', WP_PLUGIN_URL . '/orangebox/js/orangebox.min.js', array('jquery'), '2.0.3');
				}
				$ob_setOptions = array(
					'notFound' => $ob_options['notFound'],
					'addThis' => $ob_options['addThis'],
					'autoplay' => $ob_options['autoplay'],
					'fadeCaption' => $ob_options['fadeCaption'],
					'fadeControls' => $ob_options['fadeControls'],
					'orangeControls' => $ob_options['orangeControls'],
					'showDots' => $ob_options['showDots'],
					'showNav' => $ob_options['showNav'],
					'showClose' => $ob_options['showClose'],
					'keyboardNavigation' => $ob_options['keyboardNavigation'],
					'fadeTime' => $ob_options['fadeTime'],
					'overlayOpacity' => $ob_options['overlayOpacity'],
					'contentBorderWidth' => $ob_options['contentBorderWidth'],
					'preloaderDelay' => $ob_options['preloaderDelay'],
					'slideshowTimer' => $ob_options['slideshowTimer'],
					'maxVideoHeight' => $ob_options['maxVideoHeight'],
					'maxVideoWidth' => $ob_options['maxVideoWidth'],
					'inlineWidth' => $ob_options['inlineWidth'],
					'inlineHeight' => $ob_options['inlineHeight'],
					'iframeWidth' => $ob_options['iframeWidth'],
					'iframeHeight' => $ob_options['iframeHeight'],
					'maxImageHeight' => $ob_options['maxImageHeight'],
					'maxImageWidth' => $ob_options['maxImageWidth'],
					'contentMinHeight' => $ob_options['contentMinHeight'],
					'contentMinWidth' => $ob_options['contentMinWidth']
				);
				$ob_newOptions = array();
				foreach($ob_defaultOptions as $key => $value) {
					if($value != $ob_setOptions[$key] && $key != 'debug' && $key != 'automateImage' && $key != 'automateVideo' && $key != 'automateSWF' && $key != 'automateOnline') {
						$ob_newOptions[$key] = $ob_setOptions[$key];
					}
				};
				// Use WordPress Localization to set Javascript variables if they are different than the defaults
				wp_localize_script( 'orangebox', 'orangebox_vars', $ob_newOptions);
			}
		}
		
   		function ob_printAdminPage() {
			// Build and print the Admin Page
			$ob_options = $this->getAdminOptions();
			// Set Form Labels
			$ob_optionLabel = array(
				'automateImage' => __("Images", 'OrangeBox'),
				'automateVideo' => __("QuickTime Videos", 'OrangeBox'),
				'automateOnline' => __("Vimeo or YouTube URLs", 'OrangeBox'),
				'automateSWF' => __("SWF Files", 'OrangeBox'),
				'debug' => __("This uses non-minified code (not recommended for regular use)", 'OrangeBox'),
				'addThis' => __("This enables the AddThis (www.addthis.com) widget", 'OrangeBox'),
				'autoplay' => __("Autoplays slideshow for image sets", 'OrangeBox'),
				'fadeCaption' => __("Alows caption to fade", 'OrangeBox'),
				'fadeControls' => __("Allows controls to fade", 'OrangeBox'),
				'orangeControls' => __("Use OrangeControls (if plugin is loaded)", 'OrangeBox'),
				'showDots' => __("Show Navigation Dots", 'OrangeBox'),
				'showNav' => __("Show Navigation Arrows", 'OrangeBox'),
				'showClose' => __("Show Close Button", 'OrangeBox'),
				'keyboardNavigation' => __("Enable Keyboard Navigation", 'OrangeBox'),
				'notFound' => __("Not Found:", 'OrangeBox'),
				'fadeTime' => __("Fade Time:", 'OrangeBox'),
				'overlayOpacity' => __("Overlay Opacity Percentage:", 'OrangeBox'),
				'maxVideoHeight' => __("Max Video Height:", 'OrangeBox'),
				'maxVideoWidth' => __("Max Video Width:", 'OrangeBox'),
				'inlineWidth' => __("Inline Width:", 'OrangeBox'),
				'inlineHeight' => __("Inline Height:", 'OrangeBox'),
				'iframeWidth' => __("iFrame Width:", 'OrangeBox'),
				'iframeHeight' => __("iFrame Height:", 'OrangeBox'),
				'contentMinHeight' => __("Min Content Height:", 'OrangeBox'),
				'contentMinWidth' => __("Min Content Width:", 'OrangeBox'),
				'maxImageHeight' => __("Max Image Height:", 'OrangeBox'),
				'maxImageWidth' => __("Max Image Width:", 'OrangeBox'),
				'contentBorderWidth' => __("Content Border Width:", 'OrangeBox'),
				'preloaderDelay' => __("Preloader Delay Time:", 'OrangeBox'),
				'slideshowTimer' => __("Image slideshow timer:", 'OrangeBox')
			);
			if(isset($_POST['update_orangeboxSettings'])) {
				$valid = true;
				$message = "Settings Not Updated!<br />";
				$boolOptionArray = array('debug','automateImage','automateVideo','automateSWF','automateOnline','addThis','autoplay','fadeCaption','fadeControls','orangeControls','showDots','showNav','showClose','keyboardNavigation');
				$numericOptionArray = array('fadeTime','preloaderDelay','slideshowTimer','maxVideoHeight','maxVideoWidth','inlineWidth','inlineHeight','iframeWidth','iframeHeight','maxImageHeight','maxImageWidth','contentMinHeight','contentMinWidth','contentBorderWidth');
				$percentageOptionArray = array('overlayOpacity');		
				$stringOptionArray = array('notFound');
				//Error Checking when updating options
				foreach($boolOptionArray as $optionName) {
					$value = false;
					if($_POST[$optionName] == "true") { $value = true; }
					$ob_options[$optionName] = $value;
				}
				foreach($numericOptionArray as $optionName) {
					$post = $_POST[$optionName];
					$match = preg_match("/[^0-9\.]/", $post);
					$isNumber = is_numeric($post);
					if(!$match && $isNumber && $post >= 0) { $ob_options[$optionName] = $post; }
					else {$valid = false;$message = $message."Please enter a valid number for ".$ob_optionLabel[$optionName]."<br />";}
				}
				foreach($percentageOptionArray as $optionName) {
					$post = $_POST[$optionName];
					$match = preg_match("/[^0-9\.]/", $post);
					$isNumber = is_numeric($post);
					if(!$match && $isNumber && $post >= 0 && $post <= 1) { $ob_options[$optionName] = $post; }
					else {$valid = false;$message = $message."Please enter a valid number between 0 and 1 for ".$ob_optionLabel[$optionName]."<br />";}
				}
				foreach($stringOptionArray as $optionName) {
					$post = $_POST[$optionName];
					if($post != "") { $ob_options[$optionName] = apply_filters('content_save_pre', $post); }
					else {$valid = false;$message = $message.$ob_optionLabel[$optionName]." Cannot be blank"."<br />";}
				}
				//Echo error/success messages
				if($valid == true) {
					update_option($this->ob_adminOptionSet, $ob_options); 
					echo '<div class="updated"><p><strong>'.__("Settings Updated", 'OrangeBox').'</strong></p></div>';
				}
				else {
					echo '<div class="updated"><p><strong>'.__($message, 'OrangeBox').'</strong></p></div>';
				}
			}
			
			function ob_getCheckBox($name, $ob_options, $ob_optionLabel) {
				if($ob_options[$name] == true) { $checkOption = 'checked="checked"'; };
				return '<p><label for="'.$name.'"><input type="checkbox" id="'.$name.'" name="'.$name.'" value="true" '.$checkOption.' /> '.$ob_optionLabel[$name].'</label></p>';
			}
			
			function ob_getField($name, $ob_options, $ob_optionLabel, $type) {
				return '<p><label for="'.$name.'">'.$ob_optionLabel[$name].' <input id="'.$name.'" name="'.$name.'" value="'. apply_filters('format_to_edit', $ob_options[$name]) . '" /> '.$type.'</label></p>';
			}
			
			?>
            <div class="wrap">
            	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                	<h2>OrangeBox <?php _e("Settings", 'OrangeBox') ?></h2>
                    <h3><?php _e("Automate", 'OrangeBox') ?> OrangeBox</h3>
                    <div style="margin:0 0 30px 20px;">
                    	<p><?php echo __("This automaticaly adds", 'OrangeBox')." OrangeBox ".__("functionality to existing links in posts and pages", 'OrangeBox') ?></p>
                        <?php echo ob_getCheckBox("automateImage", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("automateVideo", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("automateSWF", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("automateOnline", $ob_options, $ob_optionLabel);?>
                    </div>
                    <h3><?php _e("General Options", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
                        <?php echo ob_getCheckBox("addThis", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("autoplay", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("fadeCaption", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("fadeControls", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("orangeControls", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("showDots", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("showNav", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("showClose", $ob_options, $ob_optionLabel);?>
                        <?php echo ob_getCheckBox("keyboardNavigation", $ob_options, $ob_optionLabel);?>
                    </div>
					<h3><?php _e("Modal Properties", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
						<?php echo ob_getField("fadeTime", $ob_options, $ob_optionLabel, "(ms)");?>
						<?php echo ob_getField("preloaderDelay", $ob_options, $ob_optionLabel, "(ms)");?>
						<?php echo ob_getField("slideshowTimer", $ob_options, $ob_optionLabel, "(ms)");?>
						<?php echo ob_getField("overlayOpacity", $ob_options, $ob_optionLabel, "(0-1)");?>
					</div>
					<h3><?php _e("General Content Properties", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
						<?php echo ob_getField("contentBorderWidth", $ob_options, $ob_optionLabel, "(px)");?>
						<?php echo ob_getField("contentMinHeight", $ob_options, $ob_optionLabel, "(px)");?>
						<?php echo ob_getField("contentMinWidth", $ob_options, $ob_optionLabel, "(px)");?>
					</div>
					<h3><?php _e("Image Properties", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
						<?php echo ob_getField("maxImageHeight", $ob_options, $ob_optionLabel, "");?>
						<?php echo ob_getField("maxImageWidth", $ob_options, $ob_optionLabel, "");?>
						<br /><small>Min/Max: 0 = not set, 0-1 = percent of window, 1+ pixel value</small>
					</div>
					<h3><?php _e("Inline Content Properties", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
						<?php echo ob_getField("inlineHeight", $ob_options, $ob_optionLabel, "");?>
						<?php echo ob_getField("inlineWidth", $ob_options, $ob_optionLabel, "");?>
						<br /><small>Min/Max: 0 = not set, 0-1 = percent of window, 1+ pixel value</small>
					</div>
					<h3><?php _e("iFrame Content Properties", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
						<?php echo ob_getField("iframeHeight", $ob_options, $ob_optionLabel, "");?>
						<?php echo ob_getField("iframeWidth", $ob_options, $ob_optionLabel, "");?>
						<br /><small>Min/Max: 0 = not set, 0-1 = percent of window, 1+ pixel value</small>
					</div>
					<h3><?php _e("Video Properties", 'OrangeBox') ?></h3>
                    <div style="margin:0 0 30px 20px;">
						<?php echo ob_getField("maxVideoHeight", $ob_options, $ob_optionLabel, "");?>
						<?php echo ob_getField("maxVideoWidth", $ob_options, $ob_optionLabel, "");?>
						<br /><small>Min/Max: 0 = not set, 0-1 = percent of window, 1+ pixel value</small>
					</div>
					<h3><?php _e("Error Messages", 'OrangeBox') ?></h3>
					<div class="section">
						<?php echo ob_getField("notFound", $ob_options, $ob_optionLabel, "");?>
					</div>
					<h3><?php _e("Debug Mode", 'OrangeBox') ?></h3>
					<div class="section">
						<?php echo ob_getCheckBox("debug", $ob_options, $ob_optionLabel);?>
					</div>
                    <div class="submit">
                    	<input type="submit" name="update_orangeboxSettings" value="<?php _e('Update Settings', 'OrangeBox') ?>" />
                    </div>
                </form>
            </div>
            <?php
		} // End ob_printAdminPage()
		
		// Add Settings link to plugins - code from GD Star Ratings
		function add_settings_link($links, $file) {
			static $this_plugin;
			if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
			
			if ($file == $this_plugin){
				$settings_link = '<a href="options-general.php?page=orangebox.php">'.__("Settings", "OrangeBox").'</a>';
				array_unshift($links, $settings_link);
			}
			return $links;
		}
	}
}

// Create variable with instance of class
if(class_exists("OrangeBox")) {
	$ob_plugin = new OrangeBox();
}

//Initialize Admin Panel
if(!function_exists('OrangeBox_ap')) {
	function OrangeBox_ap() {
		global $ob_plugin;
		if (!isset($ob_plugin)) {
			return;
		}
		// Register Admin Page
		add_options_page('OrangeBox', 'OrangeBox', 9, basename(__FILE__), array(&$ob_plugin, 'ob_printAdminPage'));
	}
}

// Set Actions and Filters
if(isset($ob_plugin)) {
	//Actions
	add_action('activate_orangebox/orangebox.php', array(&$ob_plugin, 'init'));
	add_action('wp_head', array(&$ob_plugin, 'ob_addHeader'), 1);
	add_action('admin_menu', 'OrangeBox_ap');
	//Filters
	add_filter('the_content', array(&$ob_plugin, 'ob_automate'), 1);
	add_filter('plugin_action_links', array(&$ob_plugin, 'add_settings_link'), 10, 2 );
}