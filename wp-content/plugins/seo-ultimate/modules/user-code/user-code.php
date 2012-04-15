<?php
/**
 * Code Inserter Module
 * 
 * @since 2.7
 */

if (class_exists('SU_Module')) {

class SU_UserCode extends SU_Module {
	
	function get_module_title() { return __('Code Inserter', 'seo-ultimate'); }
	
	function get_default_settings() {
		return array(
			  'global_wp_head' => $this->flush_setting('custom_html', '', 'meta')
		);
	}
	
	function init() {
		$hooks = array('su_head', 'the_content', 'wp_footer');
		foreach ($hooks as $hook) add_filter($hook, array(&$this, "{$hook}_code"));
	}
	
	function get_admin_page_tabs() {
		return array(
			  array('title' => __('Everywhere', 'seo-ultimate'), 'id' => 'su-everywhere', 'callback' => array('usercode_admin_tab', 'global'))
		);
	}
	
	function usercode_admin_tab($section) {
		
		$textareas = array(
			  'wp_head' => __('&lt;head&gt; Tag', 'seo-ultimate')
			, 'the_content_before' => __('Before Item Content', 'seo-ultimate')
			, 'the_content_after' => __('After Item Content', 'seo-ultimate')
			, 'wp_footer' => __('Footer', 'seo-ultimate')
		);
		$textareas = suarr::aprintf("{$section}_%s", false, $textareas);
		
		$this->admin_form_table_start();
		$this->textareas($textareas);
		$this->admin_form_table_end();
	}
	
	function get_usercode($field) {
		
		$code = $this->get_setting("global_$field", '');
		if (is_front_page()) $code .= $this->get_setting("frontpage_$field", '');
		
		return $this->plugin->mark_code($code, __('Code Inserter module', 'seo-ultimate'), $field == 'wp_head');
	}
	
	function su_head_code() {
		echo $this->get_usercode('wp_head');
	}
	
	function wp_footer_code() {
		echo $this->get_usercode('wp_footer');
	}
	
	function the_content_code($content) {
		return $this->get_usercode('the_content_before') . $content . $this->get_usercode('the_content_after');
	}
	
	function add_help_tabs($screen) {
		
		$screen->add_help_tab(array(
			  'id' => 'su-user-code-overview'
			, 'title' => __('Overview', 'seo-ultimate')
			, 'content' => __("
<ul>
	<li><strong>What it does:</strong> Code Inserter can add custom HTML code to various parts of your site.</li>
	<li>
		<p><strong>Why it helps:</strong> Code Inserter is useful for inserting third-party code that can improve the SEO or user experience of your site. For example, you can use Code Inserter to add Google Analytics code to your footer, Feedburner FeedFlares or social media widgets after your posts, or Google AdSense section targeting code before/after your content.</p>
		<p>Using Code Inserter is easier than editing your theme manually because your custom code is stored in one convenient location and will be added to your site even if you change your site&#8217;s theme.</p>
	</li>
	<li><strong>How to use it:</strong> Just paste the desired HTML code into the appropriate fields and then click Save Changes.</li>
</ul>
", 'seo-ultimate')));

		$screen->add_help_tab(array(
			  'id' => 'su-user-code-troubleshooting'
			, 'title' => __('Troubleshooting', 'seo-ultimate')
			, 'content' => __("
<ul>
	<li><strong>Why doesn't my code appear on my site?</strong><br />It&#8217;s possible that your theme doesn't have the proper &#8220;hooks,&#8221; which are pieces of code that let WordPress plugins insert custom HTML into your theme. <a href='http://johnlamansky.com/wordpress/theme-plugin-hooks/' target='_blank'>Click here</a> for information on how to check your theme and add the hooks if needed.</li>
</ul>
", 'seo-ultimate')));
		
	}
}

}

?>