<?php
/*
Plugin Name: Admin Help Content
Plugin URI: http://premium.wpmudev.org/project/admin-help-content
Description: Change the 'help content' that slides down all AJAXy
Author: Barry (Incsub), Andrew Billits, Ulrich Sossou (Incsub), Ve Bailovity (Incsub)
Version: 2.0.1
Author URI: http://premium.wpmudev.org/project/
Textdomain: admin_help_content
WDP ID: 170
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/**
 * Escaping for textarea values.
 *
 * @since 3.1
 *
 * Added for compatibility with WordPress 3.0.*
 *
 * @param string $text
 * @return string
 */
if( !function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		$safe_text = htmlspecialchars( $text, ENT_QUOTES );
		return apply_filters( 'esc_textarea', $safe_text, $text );
	}
}


/**
 * Main help panel handler.
 */
class Ahc_AdminHelpContent {

	private $_help;
	private $_default_text = 'You can change the content in this Help drop-down by going to Branding > Help Content.';

	function __construct () {
		if (!class_exists('WpmuDev_ContextualHelp')) require_once('admin-help-content-files/class_wd_contextual_help.php');
		$this->_help = new WpmuDev_ContextualHelp();

		add_action('admin_init', array(&$this, 'register_settings'));

		add_action( 'ultimatebranding_settings_menu_help', array(&$this, 'create_admin_page') );
		add_filter( 'ultimatebranding_settings_menu_help_process', array(&$this, 'process_admin_page'), 10, 1 );

		$this->_initialize_help_content();

	}

	function Ahc_AdminHelpContent() {
		$this->__construct();
	}

	/**
	 * Main handling method.
	 * Pick up stored settings, convert them to proper format
	 * and feed them to abstract help handler.
	 */
	private function _initialize_help_content () {
		$opts = $this->_get_options();

		if ($opts['prevent_network'] && defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) return false;

		$tabs = $opts['tabs'];
		foreach ($tabs as $idx => $tab) {
			$tabs[$idx]['id'] = md5(@$tab['title'] . @$tab['content'] . time());
		}
		$this->_help->add_page('_global_', $tabs, $opts['sidebar'], !@$opts['merge_panels']);
		$this->_help->initialize();
	}

/* ----- Helper  methods ----- */

	/**
	 * Returnds true if we have sidebar available (i.e. WP3.3+).
	 */
	private function _has_sidebar () {
		global $wp_version;
		$version = preg_replace('/-.*$/', '', $wp_version);

		if (version_compare($version, '3.3', '>=')) return true;
		return false;
	}

	/**
	 * Returns true if the plugin is network activated.
	 */
	private function _is_network_activated () {
		if (!is_multisite()) return false;

		$plugin = plugin_basename(__FILE__);
		$plugins = get_site_option( 'active_sitewide_plugins');
		return isset($plugins[$plugin]);
	}

	/**
	 * Gets appropriate options.
	 * If the old options are still around, attempt to convert them to new format.
	 */
	private function _get_options () {

		$opts = ub_get_option('admin_help_content');
		$opts = is_array($opts) ? $opts : array(
			'tabs' => array(
				array(
					'title' => __('Admin Help', 'ub'),
					'content' => ($opts ? $opts : __($this->_default_text, 'ub') ),
				),
			),
			'sidebar' => '',
			'prevent_network' => false,
			'merge_panels' => false,
		);
		return $opts;
	}

	/**
	 * Sets plugin options.
	 */
	private function _set_options ($opts) {
		return ub_update_option('admin_help_content', $opts);
	}

/* ----- Handlers ----- */

	function register_settings () {
		register_setting('admin_help_content', 'ub');
		add_settings_section('admin_help_content_setting_section', __('Help Content', 'ub'), '__return_false', 'admin_help_content');
		add_settings_field('admin_help_content_old', __('Existing Help Items', 'ub'), array(&$this, 'help_content_existing_elements'), 'admin_help_content', 'admin_help_content_setting_section');
		add_settings_field('admin_help_content_new', __('Add New Help Item', 'ub'), array(&$this, 'help_content_new_element'), 'admin_help_content', 'admin_help_content_setting_section');
		if ($this->_has_sidebar()) {
			add_settings_field('admin_help_sidebar', __('Help Sidebar', 'ub'), array(&$this, 'help_sidebar_element'), 'admin_help_content', 'admin_help_content_setting_section');
		}
		add_settings_field('admin_help_settings', __('Help Panel Settings', 'ub'), array(&$this, 'help_settings_element'), 'admin_help_content', 'admin_help_content_setting_section');
	}

	function help_content_existing_elements () {
		$opts = $this->_get_options();
		$tabs = $opts['tabs'];
		$tabs = $tabs ? $tabs : array();

		if (!$tabs) {
			echo '<div class="updated below-h2"><p>' .
				__('There are no existing help items to edit.', 'ub') .
			'</p></div>';
			return false;
		}

		foreach ($tabs as $idx => $tab) {
			$title = esc_attr($tab['title']);
			$body = stripslashes($tab['content']);
			$class = ($idx%2) ? 'even' : 'odd';
			?>
				<div class="ahc-help_item <?php echo $class; ?> ahc_existing_help_item">
					<label for='ahc-tab-<?php echo $idx; ?>-title'><?php _e('Help Item Title', 'ub'); ?></label>
					<input type='text' class='widefat ahc-tab-title' name='admin_help_content[tabs][<?php echo $idx; ?>][title]' id='ahc-tab-<?php echo $idx; ?>-title' value='<?php echo $title; ?>' />
					<?php
					$args = array("textarea_name" => "admin_help_content[tabs][" . $idx . "][content]", "textarea_rows" => 5);
					wp_editor( $body, "admin_help_content[tabs][" . $idx . "][content]", $args );
					?>
					<br/>
					<label for='ahc-tab-<?php echo $idx; ?>-content'><?php _e('Help Item Content', 'ub'); ?></label>
					<a href="#" class="ahc-remove_item"><?php _e('Remove this item', 'ub'); ?></a>
				</div>
				<br/>

			<?php
		}

		echo <<<EoAhcTabsJs
<style type="text/css">
.ahc-help_item {
	padding: 10px;
	border:1px solid #ccc;
	-moz-border-radius:3px;-khtml-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;
 	-moz-box-shadow: 0 3px 3px #ccc;
 	-webkit-box-shadow: 0 3px 3px #ccc;
 	box-shadow: 0 3px 3px #ccc;
}
.ahc-help_item.even {
	background: #eee;
}
</style>
<script type="text/javascript">
jQuery(".ahc-remove_item").click(function () {
	if(jQuery('.ahc_existing_help_item').length <= 1) {
		alert('You must have at least one help item.');
	} else {
		jQuery(this).parent().hide();
		jQuery(this).parent().find('.ahc-tab-title').val('');
	}

	return false;
});

</script>
EoAhcTabsJs;
	}

	function help_content_new_element () {

		?>
		<div class="ahc-help_item">
		<label for='ahc-tab-new-title'><?php _e('New Help Item Title', 'ub'); ?></label>
		<input type='text' class='widefat' name='admin_help_content[new_tab][title]' id='ahc-tab-new-title' value='' />
		<label for='ahc-tab-new-content'><?php _e('New Help Item Content','ub'); ?></label>
		<?php
		$args = array("textarea_name" => "admin_help_content[new_tab][content]", "textarea_rows" => 5);
		wp_editor( '', "admin_help_content[new_tab][content]", $args );
		?>
		<br/>
		<?php _e( 'HTML Allowed.', 'ub' ); ?>
		<p><input type="submit" value="<?php echo esc_attr(__('Add', 'ub')); ?>" /></p>
		</div>
		<?php
	}

	function help_sidebar_element () {
		$opts = $this->_get_options();
		$bar = stripslashes($opts['sidebar']);

		$args = array("textarea_name" => "admin_help_content[sidebar]", "textarea_rows" => 5);
		wp_editor( $bar, "admin_help_content[sidebar]", $args );
		?>
		<br/>
		<?php
		_e( 'HTML Allowed.', 'admin_help_content' );
	}

	function help_settings_element () {
		$opts = $this->_get_options();

		$network = @$opts['prevent_network'] ? 'checked="checked"' : '';
		?>

		<?php
		echo '' .
			'<input type="hidden" name="admin_help_content[prevent_network]" value="" />' .
			"<input type='checkbox' id='ahc-prevent_network' name='admin_help_content[prevent_network]' value='1' {$network} />" .
			'&nbsp;' .
			'<label for="ahc-prevent_network">' . __('Do not show new help panels in Network Admin area', 'ub') . '</label>' .
		'<br />';

		$merge = @$opts['merge_panels'] ? 'checked="checked"' : '';
		echo '' .
			'<input type="hidden" name="admin_help_content[merge_panels]" value="" />' .
			"<input type='checkbox' id='ahc-merge_panels' name='admin_help_content[merge_panels]' value='1' {$merge} />" .
			'&nbsp;' .
			'<label for="ahc-merge_panels">' . __('Keep default help items (if any) and merge the new ones with them.', 'ub') . '</label>' .
		'<br />';

	}

	function process_admin_page( $status ) {

		if (isset($_POST['admin_help_content'])) {
			$tabs = $_POST['admin_help_content']['tabs'];
			$tabs = is_array($tabs) ? $tabs : array();
			if (trim(@$_POST['admin_help_content']['new_tab']['title']) && trim(@$_POST['admin_help_content']['new_tab']['content'])) {
				$tabs[] = $_POST['admin_help_content']['new_tab'];
				unset($_POST['admin_help_content']['new_tab']);
			}
			foreach ($tabs as $key=>$tab) {
				if(!empty($tab['title'])) {
					$tabs[$key]['title'] = strip_tags(stripslashes($tab['title']));
					$tabs[$key]['content'] = stripslashes($tab['content']);
				} else {
					unset($tabs[$key]);
				}
			}
			$_POST['admin_help_content']['tabs'] = $tabs;
			$_POST['admin_help_content']['sidebar'] = stripslashes($_POST['admin_help_content']['sidebar']);
			$this->_set_options($_POST['admin_help_content']);
		}

		if($status === false) {
			return $status;
		} else {
			return true;
		}
	}

	function create_admin_menu_entry () {
		if (@$_POST && isset($_POST['option_page']) && 'admin_help_content' == @$_POST['option_page']) {
			if (isset($_POST['admin_help_content'])) {
				$tabs = $_POST['admin_help_content']['tabs'];
				$tabs = is_array($tabs) ? $tabs : array();
				if (trim(@$_POST['admin_help_content']['new_tab']['title']) && trim(@$_POST['admin_help_content']['new_tab']['content'])) {
					$tabs[] = $_POST['admin_help_content']['new_tab'];
					unset($_POST['admin_help_content']['new_tab']);
				}
				foreach ($tabs as $key=>$tab) {
					$tabs[$key]['title'] = strip_tags(stripslashes($tab['title']));
					$tabs[$key]['content'] = stripslashes($tab['content']);
				}
				$_POST['admin_help_content']['tabs'] = $tabs;
				$_POST['admin_help_content']['sidebar'] = stripslashes($_POST['admin_help_content']['sidebar']);
				$this->_set_options($_POST['admin_help_content']);
			}
			$goback = add_query_arg('settings-updated', 'true',  wp_get_referer());
			wp_redirect($goback);
			die;
		}

	}

	function create_admin_page () {
		?>
			<div class="postbox">
				<h3 class="hndle" style='cursor:auto;'><span><?php _e('Admin Panel Help Settings','ub'); ?></span></h3>
				<div class="inside">
						<?php //settings_fields('admin_help_content'); ?>
						<?php do_settings_sections('admin_help_content'); ?>
				</div>
			</div>
		<?php

	}
}

$ub_Ahc_AdminHelpContent = new Ahc_AdminHelpContent();

