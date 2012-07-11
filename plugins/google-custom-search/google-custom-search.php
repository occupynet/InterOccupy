<?php
/*
Plugin Name: Google Custom Search
Plugin URI: http://littlehandytips.com/plugins/google-custom-search/
Description: This plugin uses Google's Search Engine to search your site's contents! It gives you to option to display the Google search results in one of three formats. 1) As a pop-up dialog. 2) Within the widget 3) In an area you specify. Unleash the power of google search on your site!
Version: 1.3.4
Author: Little Handy Tips
Author URI: http://littleHandyTips.com
License: 
  Copyright 2010  Little Handy Tips  (email : plugins@littleHandyTips.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include_once(dirname(__FILE__).'/config.php');
include_once(dirname(__FILE__).'/options.php');
include_once(dirname(__FILE__).'/widget.php');
include_once(dirname(__FILE__).'/search-box.php');

// Call the Installer/Upgrader when plugin is activated
function gsc_activate()
{
	require_once(dirname(__FILE__).'/installer.php');
}

//Adding hooks
register_activation_hook(__FILE__, 'gsc_activate');


add_action( 'wp_enqueue_scripts', 'load_scripts' , 5 );

function load_scripts(){
	global $gsc_plugin_dir_path, $gsc_hide_search_button;

	//Adding javascripts
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui-dialog");
	wp_enqueue_script("jquery-ui-resizable");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-draggable ");
	wp_enqueue_script("jquery-ui-selectable ");
	wp_enqueue_script('gsc_dialog', $gsc_plugin_dir_path.'/js/gsc.js');
	wp_enqueue_script('gsc_jsapi', 'http://www.google.com/jsapi');
	
	//Adding CSS
	wp_enqueue_style('gsc_style', $gsc_plugin_dir_path.'/css/smoothness/jquery-ui-1.7.3.custom.css');
	wp_enqueue_style('gsc_style_search_bar', 'http://www.google.com/cse/style/look/minimalist.css');
	wp_enqueue_style('gsc_style_search_bar_more', $gsc_plugin_dir_path.'/css/gsc.css');
	if(get_option($gsc_hide_search_button) == "yes"){
		wp_enqueue_style('gsc_style_search_bar_even_more', $gsc_plugin_dir_path.'/css/gsc-no-search-button.css');
	}
}
?>
