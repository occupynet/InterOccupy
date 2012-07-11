<?php
/**
 * Handles all Admin access functionality.
 */
class Wdcab_AdminPages {

	function Wdcab_AdminPages () {
		$this->__construct();
	}

	function __construct () {
		add_action('admin_init', array($this, 'register_settings'));

		add_action( 'ultimatebranding_settings_menu_adminbar', array(&$this, 'create_admin_page') );
		add_filter( 'ultimatebranding_settings_menu_adminbar_process', array(&$this, 'update_admin_page'), 10, 1 );

		add_action('ultimatebranding_admin_header_adminbar', array(&$this, 'js_print_scripts'));
		add_action('ultimatebranding_admin_header_adminbar', array(&$this, 'css_print_styles'));
	}

	function create_admin_menu_entry () {
		if (@$_POST && isset($_POST['option_page'])) {
			$changed = false;
			if ('wdcab_options' == @$_POST['option_page']) {
				if (isset($_POST['wdcab']['links']['_last_'])) {
					$last = $_POST['wdcab']['links']['_last_'];
					unset($_POST['wdcab']['links']['_last_']);
					if (@$last['url'] && @$last['title']) $_POST['wdcab']['links'][] = $last;
				}
				if (isset($_POST['wdcab']['links'])) {
					$_POST['wdcab']['links'] = array_filter($_POST['wdcab']['links']);
				}
				ub_update_option('wdcab', $_POST['wdcab']);
				$changed = true;
			}

			if ($changed) {
				$goback = add_query_arg('settings-updated', 'true',  wp_get_referer());
				wp_redirect($goback);
				die;
			}
		}
		$page = is_multisite() ? 'settings.php' : 'options-general.php';
		$perms = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_submenu_page($page, __('Custom Admin Bar', 'ub'), __('Custom Admin Bar', 'ub'), $perms, 'wdcab', array($this, 'create_admin_page'));
	}

	function register_settings () {
		global $wp_version;
		$version = preg_replace('/-.*$/', '', $wp_version);
		$form = new Wdcab_AdminFormRenderer;

		register_setting('wdcab', 'wdcab');
		add_settings_section('wdcab_settings', __('Settings', 'ub'), create_function('', ''), 'wdcab_options');
		add_settings_field('wdcab_enable', __('Enable Custom entry', 'ub'), array($form, 'create_enabled_box'), 'wdcab_options', 'wdcab_settings');
		add_settings_field('wdcab_title', __('Entry title <br /><small>(text or image)</small>', 'ub'), array($form, 'create_title_box'), 'wdcab_options', 'wdcab_settings');
		add_settings_field('wdcab_title_link', __('Title link leads to', 'ub'), array($form, 'create_title_link_box'), 'wdcab_options', 'wdcab_settings');
		add_settings_field('wdcab_add_step', __('Add new link', 'ub'), array($form, 'create_add_link_box'), 'wdcab_options', 'wdcab_settings');
		add_settings_field('wdcab_links', __('Configure Links', 'ub'), array($form, 'create_links_box'), 'wdcab_options', 'wdcab_settings');
		if (version_compare($version, '3.3', '>=')) {
			add_settings_field('wdcab_disable', __('Disable WordPress menu items', 'ub'), array($form, 'create_disable_box'), 'wdcab_options', 'wdcab_settings');
		}
	}

	function update_admin_page( $status ) {

		if (isset($_POST['wdcab']['links']['_last_'])) {
			$last = $_POST['wdcab']['links']['_last_'];
			unset($_POST['wdcab']['links']['_last_']);
			if (@$last['url'] && @$last['title']) $_POST['wdcab']['links'][] = $last;
		}
		if (isset($_POST['wdcab']['links'])) {
			$_POST['wdcab']['links'] = array_filter($_POST['wdcab']['links']);
		}
		ub_update_option('wdcab', $_POST['wdcab']);

		if($status === false) {
			return $status;
		} else {
			return true;
		}
	}

	function create_admin_page () {
		include_once( ub_files_dir('modules/custom-admin-bar-files/lib/forms/plugin_settings.php') );
	}

	function js_print_scripts () {
		wp_enqueue_script( array("jquery", "jquery-ui-core", "jquery-ui-sortable", 'jquery-ui-dialog') );
	}

	function css_print_styles () {
		wp_enqueue_style('jquery-ui-dialog', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
	}

}