<?php
/*
Plugin Name: OrangeBox
Plugin URI: http://davidpaulhamilton.net/orangebox
Description: OrangeBox is a Lightweight, Cross-Browser, Automated jQuery Lightbox Script. It can display images, YouTube and Vimeo videos, Flash SWF files, PDFs, iFrames, and any other inline content. It also groups items together (regardless of their content type) and shows easy to use navigation.
Version: 3.0.0
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
                'debug' => "",
                'autoplay' => "",
                'checkAlias' => true,
                'automateImage' => true,
                'automateSWF' => true,
                'automateOnline' => true,
                'addThis' => true,
                'fadeControls' => "",
                'showDots' => "",
                'showNav' => true,
                'showClose' => true,
                'keyboardNavigation' => true,
                'contentRoundedBorder' => true,
                //Integers
                'streamItems' => 10,
                'fadeTime' => 200,
                'preloaderDelay' => 600,
                'slideshowTimer' => 3000,
                'overlayOpacity' => 0.95,
                'contentBorderWidth' => 4,
                //Strings
                'notFound' => "Not Found",
                'logging' => "0",
                'searchTerm' => 'lightbox',
                'addThisServices' => 'twitter,facebook,digg,delicious,more',
                //Max-Min Values
                'contentMinSize' => "[100, 200]",
                'contentMaxSize' => "[0.75, 0.75]",
                'videoAspect' => "[390, 640]",
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
                $replacement = '$1 data-ob="'.$ob_options['searchTerm'].'['.$post->ID.']">';
                $content = preg_replace($pattern, $replacement, $content);
            }
            if($ob_options['automateSWF'] == "true") {
                $pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.swf[\"]?[^\>]*)>/i";
                $replacement = '$1 data-ob="'.$ob_options['searchTerm'].'['.$post->ID.']">';
                $content = preg_replace($pattern, $replacement, $content);
            }
            if($ob_options['automateOnline'] == "true") {
                $pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?(?:(youtube+\.\w{2,3}\/watch\?v=[\w-]{11}(['\"]|[\&]))|(vimeo\.com\/(\w{1,10})(['\"]|[\?])))[^\>]*)>/i";
                $replacement = '$1 data-ob="'.$ob_options['searchTerm'].'['.$post->ID.']">';
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

                if($ob_options['debug'] == "true") {
                    wp_enqueue_script('orangebox', WP_PLUGIN_URL . '/orangebox/js/orangebox.js', array('jquery'), '3.0.0');
                }
                else {
                    wp_enqueue_script('orangebox', WP_PLUGIN_URL . '/orangebox/js/orangebox.min.js', array('jquery'), '3.0.0');
                }

                $ob_setOptions = array(
                    'searchTerm' => $ob_options['searchTerm'],
                    'logging' => $ob_options['logging'],
                    'streamItems' => $ob_options['streamItems'],
                    'autoplay' => $ob_options['autoplay'],
                    'checkAlias' => $ob_options['checkAlias'],
                    'notFound' => $ob_options['notFound'],
                    'addThis' => $ob_options['addThis'],
                    'addThisServices' => $ob_options['addThisServices'],
                    'fadeControls' => $ob_options['fadeControls'],
                    'showDots' => $ob_options['showDots'],
                    'showNav' => $ob_options['showNav'],
                    'showClose' => $ob_options['showClose'],
                    'keyboardNavigation' => $ob_options['keyboardNavigation'],
                    'fadeTime' => $ob_options['fadeTime'],
                    'overlayOpacity' => $ob_options['overlayOpacity'],
                    'contentBorderWidth' => $ob_options['contentBorderWidth'],
                    'contentRoundedBorder' => $ob_options['contentRoundedBorder'],
                    'preloaderDelay' => $ob_options['preloaderDelay'],
                    'slideshowTimer' => $ob_options['slideshowTimer'],
                    'contentMinSize' => $ob_options['contentMinSize'],
                    'contentMaxSize' => $ob_options['contentMaxSize'],
                    'videoAspect' => $ob_options['videoAspect']
                );
                $ob_newOptions = array();
                foreach($ob_defaultOptions as $key => $value) {
                    if($value != $ob_setOptions[$key] && $key != 'debug' && $key != 'automateImage' && $key != 'automateSWF' && $key != 'automateOnline') {
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
                'searchTerm' => __("Search Term:", 'OrangeBox'),
                'logging' => __("Logging:", 'OrangeBox'),
                'autoplay' => __("Autoplay Content", 'OrangeBox'),
                'streamItems' => __("Number of Items to Stream:", 'OrangeBox'),
                'checkAlias' => __("Check Alias", 'OrangeBox'),
                'automateImage' => __("Images", 'OrangeBox'),
                'automateOnline' => __("Vimeo or YouTube URLs", 'OrangeBox'),
                'automateSWF' => __("SWF Files", 'OrangeBox'),
                'debug' => __("Use Non-Minified Code (not recommended for regular use)", 'OrangeBox'),
                'addThis' => __("Enable AddThis (www.addthis.com)", 'OrangeBox'),
                'addThisServices' => __("AddThis Services:", 'OrangeBox'),
                'fadeControls' => __("Allow Controls to Fade", 'OrangeBox'),
                'showDots' => __("Show Navigation Dots", 'OrangeBox'),
                'showNav' => __("Show Navigation Arrows", 'OrangeBox'),
                'showClose' => __("Show Close Button", 'OrangeBox'),
                'keyboardNavigation' => __("Enable Keyboard Navigation", 'OrangeBox'),
                'notFound' => __("Not Found:", 'OrangeBox'),
                'fadeTime' => __("Fade Time:", 'OrangeBox'),
                'overlayOpacity' => __("Overlay Opacity Percentage:", 'OrangeBox'),
                'contentMinSize' => __("Min Content Size:", 'OrangeBox'),
                'contentMaxSize' => __("Max Content Size:", 'OrangeBox'),
                'videoAspect' => __("Video Aspect Ratio:", 'OrangeBox'),
                'contentBorderWidth' => __("Content Border Width:", 'OrangeBox'),
                'contentRoundedBorder' => __("Content Rounded Corners", 'OrangeBox'),
                'preloaderDelay' => __("Preloader Delay Time:", 'OrangeBox'),
                'slideshowTimer' => __("Content Slideshow Timer:", 'OrangeBox')
            );
            if(isset($_POST['update_orangeboxSettings'])) {
                $valid = true;
                $message = "Settings Not Updated!<br />";
                $boolOptionArray = array('debug','autoplay','checkAlias','automateImage','automateSWF','automateOnline','addThis','fadeControls','showDots','showNav','showClose','keyboardNavigation','contentRoundedBorder');
                $numericOptionArray = array('fadeTime','streamItems','preloaderDelay','slideshowTimer','contentBorderWidth');
                $numericValuePairArray = array('contentMinSize','contentMaxSize','videoAspect');
                $percentageOptionArray = array('overlayOpacity');
                $stringOptionArray = array('notFound', 'searchTerm', 'logging', 'addThisServices');
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
                foreach($numericValuePairArray as $optionName) {
                    $post = $_POST[$optionName];
                    $match = preg_match("/^\[[0-9\.]+\,(\s)?[0-9\.]+\]$/", $post);
                    if($match && $post >= 0) { $ob_options[$optionName] = $post; }
                    else {$valid = false;$message = $message."Please enter a valid value pair set for ".$ob_optionLabel[$optionName]."<br />";}
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
                    <?php echo ob_getCheckBox("automateSWF", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("automateOnline", $ob_options, $ob_optionLabel);?>
                </div>
                <h3><?php _e("General Options", 'OrangeBox') ?></h3>
                <div style="margin:0 0 30px 20px;">
                    <?php echo ob_getField("searchTerm", $ob_options, $ob_optionLabel, "");?>
                    <?php echo ob_getField("streamItems", $ob_options, $ob_optionLabel, "");?>
                    <?php echo ob_getCheckBox("checkAlias", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("autoplay", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("fadeControls", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("showDots", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("showNav", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("showClose", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("keyboardNavigation", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getCheckBox("addThis", $ob_options, $ob_optionLabel);?>
                    <?php echo ob_getField("addThisServices", $ob_options, $ob_optionLabel, "");?>
                </div>
                <h3><?php _e("Modal Properties", 'OrangeBox') ?></h3>
                <div style="margin:0 0 30px 20px;">
                    <?php echo ob_getField("fadeTime", $ob_options, $ob_optionLabel, "(ms)");?>
                    <?php echo ob_getField("preloaderDelay", $ob_options, $ob_optionLabel, "(ms)");?>
                    <?php echo ob_getField("slideshowTimer", $ob_options, $ob_optionLabel, "(ms)");?>
                    <?php echo ob_getField("overlayOpacity", $ob_options, $ob_optionLabel, "(0-1)");?>
                    <?php echo ob_getField("contentBorderWidth", $ob_options, $ob_optionLabel, "(px)");?>
                    <?php echo ob_getCheckBox("contentRoundedBorder", $ob_options, $ob_optionLabel); ?>
                </div>
                <h3><?php _e("Modal Size Properties", 'OrangeBox') ?></h3>
                <div style="margin:0 0 30px 20px;">
                    <p><?php echo __('The three options below are set using value pairs. To set a pair use the syntax: "[height, width]". There are three different ways to specify a value:'); ?></p>
                    <ol>
                        <li><?php echo __('No Maximum: <strong>0</strong> (width will be constrained to the browser window)'); ?></li>
                        <li><?php echo __('Percentage of browser window: <strong>Any value between 0 and 1</strong>'); ?></li>
                        <li><?php echo __('Pixel Value: <strong>Any value greater than 1</strong> (width will be constrained to the browser window)'); ?></li>
                    </ol>
                    <?php echo ob_getField("contentMinSize", $ob_options, $ob_optionLabel, "[height, width]");?>
                    <?php echo ob_getField("contentMaxSize", $ob_options, $ob_optionLabel, "[height, width]");?>
                    <?php echo ob_getField("videoAspect", $ob_options, $ob_optionLabel, "[height, width]");?>
                </div>
                <h3><?php _e("Error Messages", 'OrangeBox') ?></h3>
                <div style="margin:0 0 30px 20px;">
                    <?php echo ob_getField("notFound", $ob_options, $ob_optionLabel, "");?>
                </div>
                <h3><?php _e("Debug Mode", 'OrangeBox') ?></h3>
                <div style="margin:0 0 30px 20px;">
                    <?php echo ob_getCheckBox("debug", $ob_options, $ob_optionLabel);?>
                    <label for="logging"><?php echo $ob_optionLabel['logging']; ?> <select name="logging" id="logging">
                        <option <?php if($ob_options['logging'] == "0") echo 'selected="selected"' ?> value="0">Off</option>
                        <option <?php if($ob_options['logging'] == "1") echo 'selected="selected"' ?> value="1">On</option>
                        <option <?php if($ob_options['logging'] == "debug") echo 'selected="selected"' ?> value="debug">Debug</option>
                    </select></label>
                </div>
                <div class="submit">
                    <input type="submit" name="update_orangeboxSettings" value="<?php _e('Update Settings', 'OrangeBox') ?>" />
                </div>
            </form>
        </div>
        <?php
        } // End ob_printAdminPage()
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
}