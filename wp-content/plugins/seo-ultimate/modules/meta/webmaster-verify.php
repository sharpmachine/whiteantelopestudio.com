<?php
/**
 * Webmaster Verification Assistant Module
 * 
 * @since 4.0
 */

if (class_exists('SU_Module')) {

class SU_WebmasterVerify extends SU_Module {
	
	function get_module_title() { return __('Webmaster Verification Assistant', 'seo-ultimate'); }
	function get_menu_title() { return __('W.M. Verification', 'seo-ultimate'); }
	
	function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'meta'; }
	
	function init() {
		add_action('su_head', array(&$this, 'head_tag_output'));
	}
	
	function head_tag_output() {
		
		//Supported meta tags and their names
		$verify = array(
			  'google' => 'google-site-verification'
			, 'yahoo' => 'y_key'
			, 'microsoft' => 'msvalidate.01'
		);
		
		//Do we have verification tags? If so, output them.
		foreach ($verify as $site => $name) {
			if ($value = $this->get_setting($site.'_verify')) {
				if (sustr::startswith(trim($value), '<meta ') && sustr::endswith(trim($value), '/>'))
					echo "\t".trim($value)."\n";
				else {
					$value = su_esc_attr($value);
					echo "\t<meta name=\"$name\" content=\"$value\" />\n";
				}
			}
		}
	}
	
	function admin_page_contents() {
		$this->child_admin_form_start();
		$this->textboxes(array(
				  'google_verify' => __('Google Webmaster Tools', 'seo-ultimate')
				, 'yahoo_verify' => __('Yahoo! Site Explorer', 'seo-ultimate')
				, 'microsoft_verify' => __('Bing Webmaster Center', 'seo-ultimate')
			));
		$this->child_admin_form_end();
	}

	function add_help_tabs($screen) {
		
		$screen->add_help_tab(array(
			  'id' => 'su-webmaster-verify-overview'
			, 'title' => $this->has_enabled_parent() ? __('Webmaster Verification Assistant', 'seo-ultimate') : __('Overview', 'seo-ultimate')
			, 'content' => __("
<ul>
	<li><strong>What it does:</strong> Webmaster Verification Assistant lets you enter in verification codes for the webmaster portals of the 3 leading search engines.</li>
	<li><strong>Why it helps:</strong> Webmaster Verification Assistant assists you in obtaining access to webmaster portals, which can provide you with valuable SEO tools.</li>
	<li><strong>How to use it:</strong> Use a search engine to locate the webmaster portal you&#8217;re interested in, sign up at the portal, and then obtain a verification code. Once you have the code, you can paste it in here, click Save Changes, then return to the portal to verify that you own the site. Once that&#8217;s done, you'll have access to the portal&#8217;s SEO tools.</li>
</ul>
", 'seo-ultimate')));
	}

}

}
?>