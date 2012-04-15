<?php
/**
 * Uninstaller Module
 * 
 * @since 2.1
 */

if (class_exists('SU_Module')) {

class SU_Uninstall extends SU_Module {

	function get_parent_module() { return 'settings'; }
	function get_child_order() { return 40; }
	function is_independent_module() { return false; }
	function get_settings_key() { return $this->get_module_key(); }
	
	function get_module_title() { return __('Uninstaller', 'seo-ultimate'); }
	function get_module_subtitle() { return __('Uninstall', 'seo-ultimate'); }
	
	function init() {
		if ($this->is_action('su-uninstall'))
			add_filter('su_custom_admin_page-settings', array(&$this, 'do_uninstall'));
	}
	
	function admin_page_contents() {
		echo "\n<p>";
		_e('Uninstalling SEO Ultimate will delete your settings and the plugin&#8217;s files.', 'seo-ultimate');
		echo "</p>\n";
		$url = $this->get_nonce_url('su-uninstall');
		$confirm = __('Are you sure you want to uninstall SEO Ultimate? This will permanently erase your SEO Ultimate settings and cannot be undone.', 'seo-ultimate');
		echo "<p><a href='$url' class='button-primary' onclick=\"javascript:return confirm('$confirm')\">".__('Uninstall Now', 'seo-ultimate')."</a></p>";
	}
	
	function enable_post_uninstall_page() {
		add_submenu_page('su-hidden-modules', __('Uninstall SEO Ultimate', 'seo-ultimate'), 'Uninstall',
			'manage_options', 'seo-ultimate', array(&$this->parent_module, 'admin_page_contents'));
	}
	
	function do_uninstall() {
		echo "<script type='text/javascript'>jQuery('#adminmenu .current').hide(); jQuery('#toplevel_page_seo').hide();</script>";
		echo "<div class=\"wrap\">\n";
		echo "\n<h2>".__('Uninstall SEO Ultimate', 'seo-ultimate')."</h2>\n";
		
		//Delete settings and do miscellaneous clean up
		$this->plugin->uninstall();
		$this->print_mini_message('success', __('Deleted settings.', 'seo-ultimate'));
		
		//Deactivate the plugin
		deactivate_plugins(array($this->plugin->plugin_basename), true);
		
		//Attempt to delete the plugin's files and output result
		if (is_wp_error($error = delete_plugins(array($this->plugin->plugin_basename))))
			$this->print_mini_message('error', __('An error occurred while deleting files.', 'seo-ultimate').'<br />'.$error->get_error_message());
		else {
			$this->print_mini_message('success', __('Deleted files.', 'seo-ultimate'));
			$this->print_mini_message('success', __('Uninstallation complete. Thanks for trying SEO Ultimate.', 'seo-ultimate'));
		}
		
		echo "\n</div>\n";
		
		return true;
	}
}

}
?>