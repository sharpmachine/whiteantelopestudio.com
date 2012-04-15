<?php
/**
 * Install Module
 * 
 * @since 2.5
 */

if (class_exists('SU_Module')) {

define('SU_DOWNGRADE_LIMIT', '5.0');

class SU_Install extends SU_Module {
	
	function get_parent_module() { return 'settings'; }
	function get_child_order() { return 20; }
	function is_independent_module() { return false; }
	
	function get_module_title() { return __('Upgrade/Downgrade/Reinstall', 'seo-ultimate'); }
	function get_menu_title() { return __('Installer', 'seo-ultimate'); }
	
	function get_admin_page_tabs() {
		return array(
			  array('title' => __('Upgrade', 'seo-ultimate'),   'id' => 'su-upgrade',   'callback' => 'upgrade_tab')
			, array('title' => __('Downgrade', 'seo-ultimate'), 'id' => 'su-downgrade', 'callback' => 'downgrade_tab')
			, array('title' => __('Reinstall', 'seo-ultimate'), 'id' => 'su-reinstall', 'callback' => 'reinstall_tab')
		);
	}
	
	function init() {
		if ($this->is_action('update')) {
			add_filter('su_custom_admin_page-settings', array(&$this, 'do_installation'));
		}
	}
	
	function upgrade_tab() {
		
		$radiobuttons = $this->get_version_radiobuttons(SU_VERSION, false);
		if (is_array($radiobuttons)) {
			if (count($radiobuttons) > 1) {
				
				echo "\n<p>";
				_e('From the list below, select the version to which you would like to upgrade. Then click the &#8220;Upgrade&#8221; button at the bottom of the screen.', 'seo-ultimate');
				echo "</p>\n";
				
				echo "<div class='su-xgrade'>\n";
				$this->admin_form_start();
				$this->radiobuttons('version', $radiobuttons);
				$this->admin_form_end(__('Upgrade', 'seo-ultimate'));
				echo "</div>\n";
			} else
				$this->print_message('success', __('You are already running the latest version.', 'seo-ultimate'));
		} else
			$this->print_message('error', __('There was an error retrieving the list of available versions. Please try again later. You can also upgrade to the latest version of SEO Ultimate using the WordPress plugin upgrader.', 'seo-ultimate'));
	}
	
	function downgrade_tab() {
		
		$radiobuttons = $this->get_version_radiobuttons(SU_DOWNGRADE_LIMIT, SU_VERSION);
		if (is_array($radiobuttons)) {
			if (count($radiobuttons) > 1) {
				
				$this->print_message('warning', suwp::add_backup_url(__('Downgrading is provided as a convenience only and is not officially supported. Although unlikely, you may lose data in the downgrading process. It is your responsibility to backup your database before proceeding.', 'seo-ultimate')));
				
				echo "\n<p>";
				_e('From the list below, select the version to which you would like to downgrade. Then click the &#8220;Downgrade&#8221; button at the bottom of the screen.', 'seo-ultimate');
				echo "</p>\n";
				
				echo "<div class='su-xgrade'>\n";
				$this->admin_form_start();
				$this->radiobuttons('version', $radiobuttons);
				$this->admin_form_end(__('Downgrade', 'seo-ultimate'));
				echo "</div>\n";
			} else
				$this->print_message('warning', sprintf(__('Downgrading to versions earlier than %s is not supported because doing so will result in data loss.', 'seo-ultimate'), SU_DOWNGRADE_LIMIT));
		} else
			$this->print_message('error', __('There was an error retrieving the list of available versions. Please try again later.', 'seo-ultimate'));
	}
	
	function reinstall_tab() {
		echo "\n<p>";
		_e('To download and install a fresh copy of the SEO Ultimate version you are currently using, click the &#8220;Reinstall&#8221; button below.', 'seo-ultimate');
		echo "</p>\n";
		
		$this->admin_form_start(false, false);
		echo "<input type='hidden' name='version' id='version' value='".su_esc_attr(SU_VERSION)."' />\n";
		$this->admin_form_end(__('Reinstall', 'seo-ultimate'), false);
	}
	
	function get_version_radiobuttons($min, $max) {
		
		$this->update_setting('version', SU_VERSION);
		
		$versions = $this->plugin->download_changelog();
		
		if (is_array($versions) && count($versions)) {
			
			$radiobuttons = array();
			$first = true;
			foreach ($versions as $title => $changes) {
				if (preg_match('|Version ([0-9.]{3,9}) |', $title, $matches)) {
					$version = $matches[1];
					
					if ($max && version_compare($version, $max, '>')) continue;
					if ($min && version_compare($version, $min, '<')) break;
					
					$changes = wptexturize($changes);
					if ($version == SU_VERSION)
						$message = __('Your Current Version', 'seo-ultimate');
					elseif ($first)
						$message = __('Latest Version', 'seo-ultimate');
					else
						$message = '';
					if ($message) $message = " &mdash; <em>$message</em>";
					
					$radiobuttons[$version] = "<strong>$title</strong>$message</label>\n$changes\n";
					
					$first = false;
				}
			}
			
			return $radiobuttons;
		}
		
		return false; //Error
	}
	
	function do_installation() {
		
		if ( ! current_user_can('update_plugins') )
			wp_die(__('You do not have sufficient permissions to upgrade/downgrade plugins for this blog.', 'seo-ultimate'));		
		
		$nv = sustr::preg_filter('0-9a-zA-Z .', $_POST['version']);
		if (!strlen($nv)) return false;
		
		//Don't allow downgrading to anything below the minimum limit
		if (version_compare(SU_DOWNGRADE_LIMIT, $nv, '>')) return;
		
		switch (version_compare($nv, SU_VERSION)) {
			case -1: //Downgrade
				$title = __('Downgrade to SEO Ultimate %s', 'seo-ultimate');
				break;
			case 0: //Reinstall
				$title = __('Reinstall SEO Ultimate %s', 'seo-ultimate');
				break;
			case 1: //Upgrade
				$title = __('Upgrade to SEO Ultimate %s', 'seo-ultimate');
				break;
			default:
				return;
		}
		
		$title = sprintf($title, $nv);
		$nonce = 'su-install-plugin';
		$plugin = 'seo-ultimate/seo-ultimate.php';
		$url = 'update.php?action=upgrade-plugin&plugin='.$plugin;
		
		include_once $this->plugin->plugin_dir_path.'plugin/class.su-installer.php';
		
		$upgrader = new SU_Installer( new SU_Installer_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
		$upgrader->upgrade($plugin, SU_VERSION, $nv);
		
		return true;
	}
}

}
?>