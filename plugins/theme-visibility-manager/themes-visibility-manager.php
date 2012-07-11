<?php
/*
Plugin Name: Themes Visibility Manager
Version: 2.1
Description: Allows super-admins to control which themes are visible to admins of a given blog.
Author: Jon Gaulding, Ioannis Yessios
Author URI: http://itg.yale.edu
Plugin URI: http://itg.yale.edu
Site Wide Only: true
Network: true
*/

// register plugin settings, only if admin

if ( is_admin() ){
	add_action( 'admin_init', 'register_themesvisibilitymanagersettings' );
}

function themesvisibilitymanager_scripts () {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'themeVisibilityScript' );
}

function register_themesvisibilitymanagersettings() {
	register_setting( 'themesvisibilitymanager-option-group', 'visible_themes_array' );	
	wp_register_script('themeVisibilityScript', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/visibilityCheckbox.js');
}

// create settings page and add this to settings menu
add_action('admin_menu', 'addThemesVisMenu');

function addThemesVisMenu() {
	$page = add_submenu_page('wpmu-admin.php', 'Themes Visibility Manager', 'Themes Visibility Manager', 10, 'themesvisibilitymanager-settings-handle', 'themesvisibilitymanager_settings_page');
	add_action('admin_print_scripts-'.$page, 'themesvisibilitymanager_scripts');
}

function themesvisibilitymanager_settings_page() {
	$optionspath = dirname(__FILE__) . "/" . "options.php";
	include($optionspath);
}

/*** BEGIN: add action to admin_init to update what's hidden and not ***/

add_action('admin_init', 'update_visible_themes');

function update_visible_themes() {
	if( current_user_can( 'manage_network_options' ) ) {
		$visible_themes_array = get_option('visible_themes_array');
		
		// if setting is not an array, no plugins have been added yet
		if(!is_array($visible_themes_array)) {
			$visible_themes_array = array();
			update_option('visible_themes_array', $visible_themes_array);
		}
		
		$visible_themes_array_UNIQUE = array_unique($visible_themes_array);
		
		if($visible_themes_array_UNIQUE != $visible_themes_array) {
			update_option('visible_themes_array', $visible_themes_array_UNIQUE);
		}
			
		// to show a theme
		if( isset($_GET['show_theme_submitted']) && isset($_GET['show_theme_path']) ) {
			$visible_themes_array = $visible_themes_array_UNIQUE;
			
			if( is_array($visible_themes_array) ) {	
				$visible_themes_array[] = $_GET['show_theme_path'];
				update_option('visible_themes_array', $visible_themes_array);
			} else {
				$visible_themes_array = array($_GET['show_theme_path']);
				update_option('visible_themes_array', $visible_themes_array);
			}
		}
		
		// to hide a theme
		if( isset($_GET['hide_theme_submitted']) && isset($_GET['hide_theme_path']) ) {		
			$hide_theme_path = $_GET['hide_theme_path'];
			$visible_themes_array = $visible_themes_array_UNIQUE;
			$visible_themes_array_MODIFIED = $visible_themes_array;
			
			if( in_array($hide_theme_path, $visible_themes_array) ) {
				foreach($visible_themes_array as $key => $item) {
					if( $item == $hide_theme_path ) {
						unset($visible_themes_array_MODIFIED[$key]);
					}
				}
				update_option('visible_themes_array', $visible_themes_array_MODIFIED);
			}
		}
	}
}//

/*** FINISH: add action to admin_init to update what's hidden and not ***/




/*** BEGIN: if user is not a site admin, filter out hidden plugins from the plugins list ***/

add_filter( 'allowed_themes', 'remove_hidden_themes', 10, 2 );

function remove_hidden_themes($allowed_themes_array) {
	
	if( !current_user_can( 'manage_network_options' ) ) {	
		$visible_themes_array = get_option('visible_themes_array');
		$new_allowed_themes_array = array();
		
		foreach($visible_themes_array as $theme_path) {
			if( isset($allowed_themes_array[$theme_path]) ) {
				$new_allowed_themes_array[$theme_path] = $allowed_themes_array[$theme_path];
			}
		}
		
		return $new_allowed_themes_array;	
	} else {
		return $allowed_themes_array;
	}
}

/*** FINISH: if user is not a site admin, filter out hidden plugins from the plugins list ***/




/*** if user is a site admin, add "make visible" or "make hidden" link to each set of plugin action links ***/

add_filter( 'theme_action_links', 'add_themes_visibility_links', 10, 2 );

function add_themes_visibility_links($links, $theme) {

	if( current_user_can( 'manage_network_options' ) ) {
		// ../wp-content/plugins/plugins-visibility-manager/processor.php

		$visible_themes_array = get_option('visible_themes_array');
	
		//print_r(get_option('hidden_themes_array'));	
		//print_r( get_site_allowed_themes() );
		
		if( in_array($theme['Stylesheet'], $visible_themes_array) ) {
			$settings_link = '<span style="font-weight:bold;color:green">is VISIBLE</span> (<a href="themes.php?hide_theme_submitted=yes&hide_theme_path=' . $theme['Stylesheet'] . '">' . __('make hidden') . '</a>)';
			$links = array_merge( array($settings_link), $links); // before other links
		} else {
			$settings_link = '<span style="font-weight:bold;color:red">is HIDDEN</span> (<a href="themes.php?show_theme_submitted=yes&show_theme_path=' . $theme['Stylesheet'] . '">' . __('make visible') . '</a>)';
			$links = array_merge( array($settings_link), $links); // before other links
		}
	}
	
	// return $links, changed or not
	return $links;
}
?>