<?php
/*
Plugin Name: Shockingly Big IE6 Warning
Plugin URI: http://www.incerteza.org/blog/projetos/shockingly-big-ie6-warning/
Description: A warning message about the dangers of using <a href="http://en.wikipedia.org/wiki/Internet_explorer_6" target="_blank">Internet Explorer 6</a>, configure your warning at the plugin <a href="options-general.php?page=shockingly-big-ie6-warning/shockingly-big-ie6-warning.php">settings</a> page.
Author: matias s
Version: 1.6.3
Author URI: http://www.incerteza.org/blog/
*/

/*
Copyright 2008  Matias Schertel  (email : matias@incerteza.org)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// GLOBAL VARIABLES
$ie6w_domain = 'shockingly-big-ie6-warning';
$ie6w_url = WP_PLUGIN_URL . '/shockingly-big-ie6-warning';

// DEFAULT OPTIONS
function ie6w_defaults() {
	$setup = array(
		'name' => 'Shockingly Big IE6 Warning',
		'version' => '1.6.3',
		'site' => 'http://www.incerteza.org/blog/projetos/shockingly-big-ie6-warning/',
		'type' => 'top',
		'test' => 'false',
		'phptest' => 'false',
		'crashmode' => '1',
		'headcomm' => 'false',
		'jstest' => 'false',
		'texts' => array(
			't1' => 'WARNING',
			't2' => 'You are using Internet Explorer version 6.0 or lower. Due to security issues and lack of support for Web Standards it is highly recommended that you upgrade to a modern browser.',
			't3' => 'After the update you can acess this site normally.'
		),
		'browsers' => array(
			'firefox' => 'true',
			'opera' => 'true',
			'chrome' => 'true',
			'safari' => 'true',
			'ie' => 'true',
		),
		'browsersu' => array(
			'firefox' => 'http://www.getfirefox.net/',
			'opera' => 'http://www.opera.com/',
			'chrome' => 'http://www.google.com/chrome/',
			'safari' => 'http://www.apple.com/safari/',
			'ie' => 'http://www.microsoft.com/windows/ie/',
		)
	);
	return $setup;
}

// INITIALIZATION - locales @ /lang/
if ( is_admin() ) {
	add_action('init', 'ie6w_init');
}
function ie6w_init() {
	global $ie6w_domain;
	load_plugin_textdomain($ie6w_domain, '/wp-content/plugins/shockingly-big-ie6-warning/lang/');
}

// ACTIVATION - when plugin is activated
register_activation_hook(__FILE__, 'ie6w_activate');
function ie6w_activate() {
	$opt = get_option('ie6w_options');
	if (!is_array($opt)) {
		delete_option('ie6w_setup');	// OLD NORMAL OPTIONS
		delete_option('ie6w_type');		// OLD NORMAL OPTIONS
		delete_option('ie6w_jq');		// OLD NORMAL OPTIONS
		delete_option('ie6w_t1');		// OLD NORMAL OPTIONS
		delete_option('ie6w_t2');		// OLD NORMAL OPTIONS
		delete_option('ie6w_t3');		// OLD NORMAL OPTIONS
		delete_option('ie6w_b_ff');		// OLD NORMAL OPTIONS
		delete_option('ie6w_b_opera');	// OLD NORMAL OPTIONS
		delete_option('ie6w_b_chrome');	// OLD NORMAL OPTIONS
		delete_option('ie6w_b_safari');	// OLD NORMAL OPTIONS
		delete_option('ie6w_b_ie7');	// OLD NORMAL OPTIONS
		$options = ie6w_defaults();
		add_option('ie6w_options', $options);
	} else {
		$options = ie6w_defaults();
		if ( $opt['version'] != $options['version'] ) {
			$options['texts']['t1'] = $opt['texts']['t1'];
			$options['texts']['t2'] = $opt['texts']['t2'];
			$options['texts']['t3'] = $opt['texts']['t3'];
			update_option("ie6w_options", $options);
		}
	}
}

// DEACTIVATION - when plugin is deactivated
register_deactivation_hook(__FILE__, 'ie6w_deactivate');
function ie6w_deactivate() {
	//delete_option('ie6w_options');
}

// HEADERS - blog header
add_action('template_redirect', 'ie6w_head_init'); // js head
function ie6w_head_init() {
	$opt = get_option('ie6w_options');
	if ( $opt['type'] == 'top' ) {
		ie6w_head_top();
	} else if ( $opt['type'] == 'center' ) {
		ie6w_head_center();
	}
}
add_action('wp_head', 'ie6w_head'); // normal head
function ie6w_head() {
	$opt = get_option('ie6w_options');
	if ( $opt['headcomm'] == 'true' ) { echo '<!-- IE6WDebug Type:'.$opt['type'].' CrashMethod:'.$opt['crashmode'].' Test:'.$opt['test'].' PHPTest:'.$opt['phptest'].' JSTest:'.$opt['jstest'].' -->'; }
	if ( $opt['type'] == 'crash' ) {
		ie6w_head_crash();
	}
}

// HEADER: TOP
function ie6w_head_top() {
	global $ie6w_url;
	$opt = get_option('ie6w_options');
	if ( $opt['phptest'] == 'true' ) { // PHP Test [ON]
		$a_browser_data = browser_detection('full');
		if ( ($a_browser_data[0] == 'ie' && $a_browser_data[1] <= 6) || ($opt['test'] == 'true') ) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('ie6w_head_top', $ie6w_url . '/js/ie6w_top.js', array('jquery'));
			wp_localize_script('ie6w_head_top', 'ie6w', array(
				'url' => $ie6w_url,
				'test' => $opt['test'],
				'jstest' => $opt['jstest'],
				't1' => $opt['texts']['t1'],
				't2' => $opt['texts']['t2'],
				'firefox' => $opt['browsers']['firefox'],
				'opera' => $opt['browsers']['opera'],
				'chrome' => $opt['browsers']['chrome'],
				'safari' => $opt['browsers']['safari'],
				'ie' => $opt['browsers']['ie'],
				'firefoxu' => $opt['browsersu']['firefox'],
				'operau' => $opt['browsersu']['opera'],
				'chromeu' => $opt['browsersu']['chrome'],
				'safariu' => $opt['browsersu']['safari'],
				'ieu' => $opt['browsersu']['ie']
			));
		}
	} else { // PHP Test [OFF]
		wp_enqueue_script('jquery');
		wp_enqueue_script('ie6w_head_top', $ie6w_url . '/js/ie6w_top.js', array('jquery'));
		wp_localize_script('ie6w_head_top', 'ie6w', array(
			'url' => $ie6w_url,
			'test' => $opt['test'],
			'jstest' => $opt['jstest'],
			't1' => $opt['texts']['t1'],
			't2' => $opt['texts']['t2'],
			'firefox' => $opt['browsers']['firefox'],
			'opera' => $opt['browsers']['opera'],
			'chrome' => $opt['browsers']['chrome'],
			'safari' => $opt['browsers']['safari'],
			'ie' => $opt['browsers']['ie'],
			'firefoxu' => $opt['browsersu']['firefox'],
			'operau' => $opt['browsersu']['opera'],
			'chromeu' => $opt['browsersu']['chrome'],
			'safariu' => $opt['browsersu']['safari'],
			'ieu' => $opt['browsersu']['ie']
		));
	}
}

// HEADER: CENTER
function ie6w_head_center() {
	global $ie6w_url;
	$opt = get_option('ie6w_options');
	if ( $opt['phptest'] == 'true' ) { // PHP Test [ON]
		$a_browser_data = browser_detection('full');
		if ( ($a_browser_data[0] == 'ie' && $a_browser_data[1] <= 6) || ($opt['test'] == 'true') ) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('ie6w_head_center', $ie6w_url . '/js/ie6w_center.js', array('jquery'));
			wp_localize_script('ie6w_head_center', 'ie6w', array(
				'url' => $ie6w_url,
				'test' => $opt['test'],
				'jstest' => $opt['jstest'],
				't1' => $opt['texts']['t1'],
				't2' => $opt['texts']['t2'],
				't3' => $opt['texts']['t3'],
				'firefox' => $opt['browsers']['firefox'],
				'opera' => $opt['browsers']['opera'],
				'chrome' => $opt['browsers']['chrome'],
				'safari' => $opt['browsers']['safari'],
				'ie' => $opt['browsers']['ie'],
				'firefoxu' => $opt['browsersu']['firefox'],
				'operau' => $opt['browsersu']['opera'],
				'chromeu' => $opt['browsersu']['chrome'],
				'safariu' => $opt['browsersu']['safari'],
				'ieu' => $opt['browsersu']['ie']
			));
		}
	} else { // PHP Test [OFF]
		wp_enqueue_script('jquery');
		wp_enqueue_script('ie6w_head_center', $ie6w_url . '/js/ie6w_center.js', array('jquery'));
		wp_localize_script('ie6w_head_center', 'ie6w', array(
			'url' => $ie6w_url,
			'test' => $opt['test'],
			'jstest' => $opt['jstest'],
			't1' => $opt['texts']['t1'],
			't2' => $opt['texts']['t2'],
			't3' => $opt['texts']['t3'],
			'firefox' => $opt['browsers']['firefox'],
			'opera' => $opt['browsers']['opera'],
			'chrome' => $opt['browsers']['chrome'],
			'safari' => $opt['browsers']['safari'],
			'ie' => $opt['browsers']['ie'],
			'firefoxu' => $opt['browsersu']['firefox'],
			'operau' => $opt['browsersu']['opera'],
			'chromeu' => $opt['browsersu']['chrome'],
			'safariu' => $opt['browsersu']['safari'],
			'ieu' => $opt['browsersu']['ie']
		));
	}
}

// HEADER: CRASH
function ie6w_head_crash() {
	$opt = get_option('ie6w_options');
	if ( $opt['phptest'] == 'true' ) { // PHP Test [ON]
		$a_browser_data = browser_detection('full');
		if ( $a_browser_data[0] == 'ie' && $a_browser_data[1] <= 6 ) {
			if ( $opt['crashmode'] == '1' ) { echo '<!--[if lte IE 6]><style>*{position:relative}</style><table><input></table><![endif]-->'; }
			else if ( $opt['crashmode'] == '2' ) { echo '<!--[if lte IE 6]><STYLE>@;/*<![endif]-->'; }
		}
	} else { // PHP Test [OFF]
		if ( $opt['crashmode'] == '1' ) { echo '<!--[if lte IE 6]><style>*{position:relative}</style><table><input></table><![endif]-->'; }
		else if ( $opt['crashmode'] == '2' ) { echo '<!--[if lte IE 6]><STYLE>@;/*<![endif]-->'; }
	}
}

// FUNCTION: browser_detection - taken from here: http://techpatterns.com/downloads/php_browser_detection.php
function browser_detection($which_test) {
	$browser_name = '';
	$browser_number = '';
	$browser_user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
	$a_browser_types[] = array('opera', true, 'op' );
	$a_browser_types[] = array('msie', true, 'ie' );
	$a_browser_types[] = array('konqueror', true, 'konq' );
	$a_browser_types[] = array('safari', true, 'saf' );
	$a_browser_types[] = array('gecko', true, 'moz' );
	$a_browser_types[] = array('mozilla/4', false, 'ns4' );
	$a_browser_types[] = array('other', false, 'other' );
	$i_count = count($a_browser_types);
	for ($i = 0; $i < $i_count; $i++) {
		$s_browser = $a_browser_types[$i][0];
		$b_dom = $a_browser_types[$i][1];
		$browser_name = $a_browser_types[$i][2];
		if (stristr($browser_user_agent, $s_browser)) {
			if ( $browser_name == 'moz' ) {
				$s_browser = 'rv';
			}
			$browser_number = browser_version( $browser_user_agent, $s_browser );
			break;
		}
	}
	if ( $which_test == 'browser' ) {
		return $browser_name;
	}
	elseif ( $which_test == 'number' ) {
		return $browser_number;
	}
	elseif ( $which_test == 'full' ) {
		$a_browser_info = array( $browser_name, $browser_number );
		return $a_browser_info;
	}
}
function browser_version( $browser_user_agent, $search_string ) {
	$string_length = 8;
	$browser_number = '';
	$start_pos = strpos( $browser_user_agent, $search_string );
	$start_pos += strlen( $search_string ) + 1;
	for ( $i = $string_length; $i > 0 ; $i-- ) {
		if ( is_numeric( substr( $browser_user_agent, $start_pos, $i ) ) ) {
			$browser_number = substr( $browser_user_agent, $start_pos, $i );
			break;
		}
	}
	return $browser_number;
}

// OPTIONS PAGE
if ( is_admin() ) {
	add_action('admin_menu', 'ie6w_options');
}
function ie6w_options() { // options menu
	$page = add_options_page(__('Shockingly Big IE6 Warning Options', $ie6w_domain), __('S. Big IE6 Warning', $ie6w_domain), 8, __FILE__, 'ie6w_options_page');
	add_action("admin_print_scripts-$page", 'ie6w_admin_js');
	add_action("admin_print_styles-$page", 'ie6w_admin_css');
}
function ie6w_admin_js() { // options js
	global $ie6w_url;
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('ie6w_tabs_js', $ie6w_url . '/js/ie6w_opt.js', array('jquery-ui-tabs'));
}
function ie6w_admin_css() { // options css
	global $ie6w_url;
	wp_enqueue_style('ie6w_tabs_css', $ie6w_url . '/css/ie6w_opt.css');
}
function ie6w_options_page() { // options page
global $ie6w_domain, $ie6w_url;
$opt = get_option('ie6w_options');
	if ( isset($_POST['update_options']) ) { // save options
	// tab 1
		$opt['type'] = $_POST['ie6w_type'];
		$opt['test'] = $_POST['ie6w_test'];
		$opt['browsers']['firefox'] = $_POST['ie6w_firefox'];
		$opt['browsers']['opera'] = $_POST['ie6w_opera'];
		$opt['browsers']['chrome'] = $_POST['ie6w_chrome'];
		$opt['browsers']['safari'] = $_POST['ie6w_safari'];
		$opt['browsers']['ie'] = $_POST['ie6w_ie'];
		if ( $_POST['ie6w_firefoxu'] != "" ) { $opt['browsersu']['firefox'] = $_POST['ie6w_firefoxu']; }
		if ( $_POST['ie6w_operau'] != "" ) { $opt['browsersu']['opera'] = $_POST['ie6w_operau']; }
		if ( $_POST['ie6w_chromeu'] != "" ) { $opt['browsersu']['chrome'] = $_POST['ie6w_chromeu']; }
		if ( $_POST['ie6w_safariu'] != "" ) { $opt['browsersu']['safari'] = $_POST['ie6w_safariu']; }
		if ( $_POST['ie6w_ieu'] != "" ) { $opt['browsersu']['ie'] = $_POST['ie6w_ieu']; }
	// tab 2
		if ( $_POST['ie6w_t1'] != "" ) { $opt['texts']['t1'] = $_POST['ie6w_t1']; }
		if ( $_POST['ie6w_t2'] != "" ) { $opt['texts']['t2'] = $_POST['ie6w_t2']; }
		if ( $_POST['ie6w_t3'] != "" ) { $opt['texts']['t3'] = $_POST['ie6w_t3']; }
	// tab 3
		$opt['phptest'] = $_POST['ie6w_phptest'];
		$opt['crashmode'] = $_POST['ie6w_crashmode'];
		$opt['headcomm'] = $_POST['ie6w_headcomm'];
		$opt['jstest'] = $_POST['ie6w_jstest'];
		update_option('ie6w_options', $opt);
		$opt = get_option('ie6w_options');
		echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.', $ie6w_domain) . '</strong></p></div>';
    } 
	if ( isset($_POST['reset_options']) ) { // reset options
		$opt = ie6w_defaults();
		update_option('ie6w_options', $opt);
		echo '<div id="message" class="updated fade"><p><strong>' . __('Default options loaded.', $ie6w_domain) . '</strong></p></div>';
		$opt = get_option('ie6w_options');
	} 
	if ( isset($_POST['delete_options']) ) { // delete options
		delete_option('ie6w_options');
		echo '<div id="message" class="updated fade"><p><strong>' . __('Options deleted.', $ie6w_domain) . '</strong></p></div>';
		$opt = get_option('ie6w_options');
	}
	if ( $opt['jstest'] == 'true' && $opt['test'] == 'false' ) {
		echo '<div id="message" class="updated fade"><p><strong>' . __('Attention: JavaScript Test is On, but Test Mode is Off, JavaScript Test will not work.', $ie6w_domain) . '</strong></p></div>';
	}
    ?>
<div class="wrap">
  <div class="icon32" id="icon-options-ie6w"><br/>
  </div>
  <h2><?php echo __('Shockingly Big IE6 Warning Settings', $ie6w_domain); ?></h2>
  <div id="tabs">
  <ul>
    <li><a href="#tabs-1"><?php echo __('Options', $ie6w_domain); ?></a> |</li>
    <li><a href="#tabs-2"><?php echo __('Message', $ie6w_domain); ?></a> |</li>
    <li><a href="#tabs-3"><?php echo __('Advanced', $ie6w_domain); ?></a></li>
    <li>| <a href="#tabs-4"><?php echo __('Registry', $ie6w_domain); ?></a></li>
  </ul>
  <form method="post" name="options" target="_self">
    <div id="tabs-1"><br/>
      <table width="100%" cellspacing="0" id="inactive-plugins-table" class="widefat">
        <thead>
          <tr>
            <th width="125"><?php echo __('Settings', $ie6w_domain); ?></th>
            <th width="125">&nbsp;</th>
            <th><?php echo __('Description', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125"><?php echo __('Warning Type', $ie6w_domain); ?></td>
          <td width="125"><select name="ie6w_type">
              <option value="off" <?php if ( $opt['type'] == 'off' ) echo 'selected="selected"'; ?> />
              <?php echo __('Off', $ie6w_domain); ?>
              </option>
              <option value="top" <?php if ( $opt['type'] == 'top' ) echo 'selected="selected"'; ?> />
              <?php echo __('Top', $ie6w_domain); ?>
              </option>
              <option value="center" <?php if ( $opt['type'] == 'center' ) echo 'selected="selected"'; ?> />
              <?php echo __('Center', $ie6w_domain); ?>
              </option>
              <option value="crash" <?php if ( $opt['type'] == 'crash' ) echo 'selected="selected"'; ?> />
              <?php echo __('Crash', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><?php echo __('The warnings: <strong>Top</strong>, the discreet top bar. <strong>Center</strong>, the full screen one. <strong>Crash</strong>, the mean option.', $ie6w_domain); ?></td>
        </tr>
        <tr>
          <td width="125"><?php echo __('Test Mode', $ie6w_domain); ?></td>
          <td width="125"><select name="ie6w_test">
              <option value="false" <?php if ( $opt['test'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Off', $ie6w_domain); ?>
              </option>
              <option value="true" <?php if ( $opt['test'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('On', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><?php echo __('Turn this <strong>On</strong> if you want to test the Warnings in any browser.', $ie6w_domain); ?></td>
        </tr>
        <thead>
          <tr>
            <th width="125"><?php echo __('Browsers', $ie6w_domain); ?></th>
            <th width="125">&nbsp;</th>
            <th><?php echo __('URL', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125">Mozilla Firefox</td>
          <td width="125"><select name="ie6w_firefox">
              <option value="true" <?php if ( $opt['browsers']['firefox'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('Show', $ie6w_domain); ?>
              </option>
              <option value="false" <?php if ( $opt['browsers']['firefox'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Hide', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><input type="text" name="ie6w_firefoxu" class="widefat firefox" value="<?php echo $opt['browsersu']['firefox']; ?>" /></td>
        </tr>
        <tr>
          <td width="125">Opera</td>
          <td width="125"><select name="ie6w_opera">
              <option value="true" <?php if ( $opt['browsers']['opera'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('Show', $ie6w_domain); ?>
              </option>
              <option value="false" <?php if ( $opt['browsers']['opera'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Hide', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><input type="text" name="ie6w_operau" class="widefat opera" value="<?php echo $opt['browsersu']['opera']; ?>" /></td>
        </tr>
        <tr>
          <td width="125">Google Chrome</td>
          <td width="125"><select name="ie6w_chrome">
              <option value="true" <?php if ( $opt['browsers']['chrome'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('Show', $ie6w_domain); ?>
              </option>
              <option value="false" <?php if ( $opt['browsers']['chrome'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Hide', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><input type="text" name="ie6w_chromeu" class="widefat chrome" value="<?php echo $opt['browsersu']['chrome']; ?>" /></td>
        </tr>
        <tr>
          <td>Apple Safari</td>
          <td><select name="ie6w_safari">
              <option value="true" <?php if ( $opt['browsers']['safari'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('Show', $ie6w_domain); ?>
              </option>
              <option value="false" <?php if ( $opt['browsers']['safari'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Hide', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><input type="text" name="ie6w_safariu" class="widefat safari" value="<?php echo $opt['browsersu']['safari']; ?>" /></td>
        </tr>
        <tr>
          <td width="125">Internet Explorer</td>
          <td width="125"><select name="ie6w_ie">
              <option value="true" <?php if ( $opt['browsers']['ie'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('Show', $ie6w_domain); ?>
              </option>
              <option value="false" <?php if ( $opt['browsers']['ie'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Hide', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><input type="text" name="ie6w_ieu" class="widefat ie" value="<?php echo $opt['browsersu']['ie']; ?>" /></td>
        </tr>
      </table>
    </div>
    <div id="tabs-2"><br/>
      <table width="100%" cellspacing="0" id="inactive-plugins-table" class="widefat">
        <thead>
          <tr>
            <th width="125"><?php echo __('Field', $ie6w_domain); ?></th>
            <th><?php echo __('Text', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125"><?php echo __('Title', $ie6w_domain); ?></td>
          <td><input type="text" name="ie6w_t1" class="widefat" value="<?php echo stripslashes(htmlspecialchars($opt['texts']['t1'])); ?>" /></td>
        </tr>
        <tr>
          <td width="125"><?php echo __('Text', $ie6w_domain); ?></td>
          <td><textarea name="ie6w_t2" rows="5" class="widefat"><?php echo stripslashes(htmlspecialchars($opt['texts']['t2'])); ?></textarea></td>
        </tr>
        <tr>
          <td width="125"><?php echo __('Observation', $ie6w_domain); ?></td>
          <td><input type="text" name="ie6w_t3" class="widefat" value="<?php echo stripslashes(htmlspecialchars($opt['texts']['t3'])); ?>" /></td>
        </tr>
      </table>
    </div>
    <div id="tabs-3">
      <h3><?php echo __('PHP Detection', $ie6w_domain); ?></h3>
      <table width="100%" cellspacing="0" id="inactive-plugins-table" class="widefat">
        <thead>
          <tr>
            <th width="125"><?php echo __('Settings', $ie6w_domain); ?></th>
            <th width="125">&nbsp;</th>
            <th><?php echo __('Description', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125"><?php echo __('PHP Detection', $ie6w_domain); ?></td>
          <td width="125"><select name="ie6w_phptest">
              <option value="false" <?php if ( $opt['phptest'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Off', $ie6w_domain); ?>
              </option>
              <option value="true" <?php if ( $opt['phptest'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('On', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><?php echo __('Turn this On <strong>only</strong> if you are having some kind of trouble, like layout errors, when this plugin is On. A PHP function will render the code <strong>only</strong> if you are using <strong>IE6</strong>. Can cause false negatives.', $ie6w_domain); ?></td>
        </tr>
      </table>
      <h3><?php echo __('IE6 Crash Methods', $ie6w_domain); ?></h3>
      <table width="100%" cellspacing="0" id="inactive-plugins-table" class="widefat">
        <thead>
          <tr>
            <th width="125"><?php echo __('Settings', $ie6w_domain); ?></th>
            <th width="125">&nbsp;</th>
            <th><?php echo __('Description', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125"><?php echo __('Crash Methods', $ie6w_domain); ?></td>
          <td width="125"><select name="ie6w_crashmode">
              <option value="1" <?php if ( $opt['crashmode'] == '1' ) echo 'selected="selected"'; ?> />
              <?php echo __('1', $ie6w_domain); ?>
              </option>
              <option value="2" <?php if ( $opt['crashmode'] == '2' ) echo 'selected="selected"'; ?> />
              <?php echo __('2', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><?php echo __('Use the following code to crash IE6', $ie6w_domain); ?>: <span id="ie6w_crashmode_txt"></span></td>
        </tr>
      </table>
      <h3><?php echo __('Debug Mode', $ie6w_domain); ?></h3>
      <table width="100%" cellspacing="0" id="inactive-plugins-table" class="widefat">
        <thead>
          <tr>
            <th width="125"><?php echo __('Settings', $ie6w_domain); ?></th>
            <th width="125">&nbsp;</th>
            <th><?php echo __('Description', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125"><?php echo __('Head Comment', $ie6w_domain); ?></td>
          <td width="125"><select name="ie6w_headcomm">
              <option value="false" <?php if ( $opt['headcomm'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Off', $ie6w_domain); ?>
              </option>
              <option value="true" <?php if ( $opt['headcomm'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('On', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><?php echo __('This mode will put a comment in the <code>head</code> of your blog with info about the setting of the plugin.', $ie6w_domain); ?></td>
        </tr>
        <tr>
          <td width="125"><?php echo __('JavaScript Test', $ie6w_domain); ?></td>
          <td width="125"><select name="ie6w_jstest">
              <option value="false" <?php if ( $opt['jstest'] == 'false' ) echo 'selected="selected"'; ?> />
              <?php echo __('Off', $ie6w_domain); ?>
              </option>
              <option value="true" <?php if ( $opt['jstest'] == 'true' ) echo 'selected="selected"'; ?> />
              <?php echo __('On', $ie6w_domain); ?>
              </option>
            </select></td>
          <td><?php echo __('Make the warning JavaScript pop up two alerts, one in the begin and other in the end of the script, this way you can know if the script is correctly loaded. For security this function only work with <strong>Test Mode</strong> activated.', $ie6w_domain); ?></td>
        </tr>
      </table>
      <h3><?php echo __('Cleanup Registry', $ie6w_domain); ?></h3>
      <p>
        <input type="submit" name="delete_options" style="margin-left:12px;" class="button-secondary" value="<?php echo __('Delete Options', $ie6w_domain); ?>" />
      </p>
      <p><?php echo __('This option will remove any <strong>Shockingly Big IE6 Warning</strong> from the Wordpress database, use it for clean uninstall. If you want to use it againt deactivate and activate it or press the Reset Options button.', $ie6w_domain); ?></p>
    </div>
    </div>
    <div id="tabs-4"><br />
      <table width="100%" cellspacing="0" id="inactive-plugins-table" class="widefat">
        <thead>
          <tr>
            <th width="125"><?php echo __('Field', $ie6w_domain); ?></th>
            <th><?php echo __('Value', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125">name</td>
          <td><?php echo $opt['name'] ?></td>
        </tr>
        <tr>
        <tr>
          <td width="125">version</td>
          <td><?php echo $opt['version'] ?></td>
        </tr>
        <tr>
          <td width="125">site</td>
          <td><?php echo $opt['site'] ?></td>
        </tr>
        <tr>
        <tr>
          <td width="125">type</td>
          <td><?php echo $opt['type'] ?></td>
        </tr>
        <tr>
          <td width="125">test</td>
          <td><?php echo $opt['test'] ?></td>
        </tr>
        <tr>
          <td width="125">phptest</td>
          <td><?php echo $opt['phptest'] ?></td>
        </tr>
        <tr>
          <td width="125">crashmode</td>
          <td><?php echo $opt['crashmode'] ?></td>
        </tr>
        <tr>
          <td width="125">headcomm</td>
          <td><?php echo $opt['headcomm'] ?></td>
        </tr>
        <tr>
          <td width="125">jstest</td>
          <td><?php echo $opt['jstest'] ?></td>
        </tr>
        <thead>
          <tr>
            <th width="125">texts</th>
            <th><?php echo __('Value', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125">t1</td>
          <td><?php echo $opt['texts']['t1'] ?></td>
        </tr>
        <tr>
          <td width="125">t2</td>
          <td><?php echo $opt['texts']['t2'] ?></td>
        </tr>
        <tr>
          <td width="125">t3</td>
          <td><?php echo $opt['texts']['t3'] ?></td>
        </tr>
        <thead>
          <tr>
            <th width="125">browsers</th>
            <th><?php echo __('Value', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125">firefox</td>
          <td><?php echo $opt['browsers']['firefox'] ?></td>
        </tr>
        <tr>
          <td width="125">opera</td>
          <td><?php echo $opt['browsers']['opera'] ?></td>
        </tr>
        <tr>
          <td width="125">chrome</td>
          <td><?php echo $opt['browsers']['chrome'] ?></td>
        </tr>
        <tr>
          <td width="125">safari</td>
          <td><?php echo $opt['browsers']['safari'] ?></td>
        </tr>
        <tr>
          <td width="125">ie</td>
          <td><?php echo $opt['browsers']['ie'] ?></td>
        </tr>
        <thead>
          <tr>
            <th width="125">browsersu</th>
            <th><?php echo __('Value', $ie6w_domain); ?></th>
          </tr>
        </thead>
        <tr>
          <td width="125">firefox</td>
          <td><?php echo $opt['browsersu']['firefox'] ?></td>
        </tr>
        <tr>
          <td width="125">opera</td>
          <td><?php echo $opt['browsersu']['opera'] ?></td>
        </tr>
        <tr>
          <td width="125">chrome</td>
          <td><?php echo $opt['browsersu']['chrome'] ?></td>
        </tr>
        <tr>
          <td width="125">safari</td>
          <td><?php echo $opt['browsersu']['safari'] ?></td>
        </tr>
        <tr>
          <td width="125">ie</td>
          <td><?php echo $opt['browsersu']['ie'] ?></td>
        </tr>
      </table>
    </div>
    <p class="submit">
      <input type="submit" name="update_options" class="button-primary" value="<?php echo __('Save Changes', $ie6w_domain); ?>" />
      <input type="submit" name="reset_options" value="<?php echo __('Reset Options', $ie6w_domain); ?>" />
    </p>
  </form>
  <hr />
  <p><?php echo __('<strong>Note</strong>: i\'m learning PHP & Wordpress coding and using this plugin to study, so if you have any idea or any kind of suggestion please contact me.', $ie6w_domain); ?></p>
  <p><?php echo '<a href="' . $opt['site'] . '">' . $opt['name'] . ' v' . $opt['version'] . '</a> ' . __('by', $ie6w_domain) . ' <a href="mailto:matias@incerteza.org">matias s.</a> ' . __('at', $ie6w_domain) . ' <a href="http://www.incerteza.org/blog/" target="_blank">incerteza.org</a>'; ?></p>
</div>
<?php }
?>