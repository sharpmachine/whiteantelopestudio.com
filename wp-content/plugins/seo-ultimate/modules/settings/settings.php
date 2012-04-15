<?php
/**
 * SEO Ultimate Plugin Settings Module
 * 
 * @since 0.2
 */

if (class_exists('SU_Module')) {

class SU_Settings extends SU_Module {
	
	function get_module_title() { return __('Plugin Settings', 'seo-ultimate'); }
	function get_page_title() { return __('SEO Ultimate Plugin Settings', 'seo-ultimate'); }
	function get_menu_title() { return __('SEO Ultimate', 'seo-ultimate'); }
	function get_menu_parent(){ return 'options-general.php'; }	
	function admin_page_contents() { $this->children_admin_page_tabs(); }
	
	function add_help_tabs($screen) {
		
		$screen->add_help_tab(array(
			  'id' => 'su-settings-overview'
			, 'title' => __('Overview', 'seo-ultimate')
			, 'content' => __("
<p>The Settings module lets you manage settings related to the SEO Ultimate plugin as a whole.</p>
", 'seo-ultimate')));
		
		$screen->add_help_tab(array(
			  'id' => 'su-settings-settings'
			, 'title' => __('Global Settings', 'seo-ultimate')
			, 'content' => __("
<p>Here&#8217;s information on some of the settings:</p>
<ul>
	<li><strong>Enable nofollow&#8217;d attribution link</strong> &mdash; If enabled, the plugin will display an attribution link on your site.</li>
	<li><strong>Notify me about unnecessary active plugins</strong> &mdash; If enabled, SEO Ultimate will add notices to your &#8220;Plugins&#8221; administration page if you have any other plugins installed whose functionality SEO Ultimate replaces.</li>
	<li><strong>Insert comments around HTML code insertions</strong> &mdash; If enabled, SEO Ultimate will use HTML comments to identify all code it inserts into your <code>&lt;head&gt;</code> tag. This is useful if you&#8217;re trying to figure out whether or not SEO Ultimate is inserting a certain piece of header code.</li>
</ul>
", 'seo-ultimate')));
	
		$screen->add_help_tab(array(
			  'id' => 'su-settings-faq'
			, 'title' => __('FAQ', 'seo-ultimate')
			, 'content' => __("
<ul>
	<li>
		<p><strong>Why doesn&#8217;t the settings exporter include all my data in an export?</strong><br />The settings export/import system is designed to facilitate moving settings between sites. It is NOT a replacement for keeping your database backed up. The settings exporter doesn&#8217;t include data that is specific to your site. For example, logged 404 errors are not included because those 404 errors only apply to your site, not another site. Also, post/page titles/meta are not included because the site into which you import the file could have totally different posts/pages located under the same ID numbers.</p>
		<p>If you&#8217;re moving a site to a different server or restoring a crashed site, you should do so with database backup/restore.</p>
	</li>
</ul>
", 'seo-ultimate')));
	}
}

}
?>