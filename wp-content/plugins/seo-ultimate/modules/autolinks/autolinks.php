<?php
/**
 * Deeplink Juggernaut Module
 * 
 * @since 1.8
 */

if (class_exists('SU_Module')) {

class SU_Autolinks extends SU_Module {
	function get_module_title() { return __('Deeplink Juggernaut', 'seo-ultimate'); }
	
	function admin_page_contents() {
		
		if (function_exists('json_encode')) {
			$this->children_admin_page_tabs_form();
		} else {
			$this->print_message('error', sprintf(__('Deeplink Juggernaut requires PHP 5.2 or above in SEO Ultimate 6.0 and later. (Note that WordPress itself will soon require PHP 5.2 as well, starting with WordPress 3.2.) If you aren&#8217;t sure how to upgrade PHP, please ask your webhost. In the meantime, you can return to an older version of Deeplink Juggernaut that supports your version of PHP by <a href="%s">downgrading</a> to SEO Ultimate 5.9.', 'seo-ultimate'), $this->get_admin_url('settings').'#su-downgrade'));
			return;
		}
		
	}
}

}
?>