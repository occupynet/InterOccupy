<?php

/** Google Custom Search Options */
function gsc_add_menu_page() {
	global $gcs_plugin_name;
	add_options_page('Google Custom Search', 'Google Custom Search', 'manage_options', $gcs_plugin_name, 'gsc_plugin_options');

	//call register settings function
	add_action( 'admin_init', 'register_gsc_settings' );
}

function gsc_plugin_options() {
  require_once(dirname(__FILE__).'/admin-page.php');
} 


// create custom plugin settings menu
add_action('admin_menu', 'gsc_add_menu_page');

function register_gsc_settings() {
	global $gsc_search_engine_id;
	register_setting( 'gsc-settings-group', $gsc_search_engine_id );
}

?>