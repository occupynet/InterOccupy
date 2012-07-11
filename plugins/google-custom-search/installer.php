<?php

/**
 * Activation of plugin
 *
 * Things that run once when the plugin is activated
 *
 * Reference Documentation
 * http://codex.wordpress.org/Function_Reference/wpdb_Class
 * http://codex.wordpress.org/Creating_Tables_with_Plugins
 */
include_once(dirname(__FILE__).'/config.php');

global $gsc_search_engine_id, $gsc_open_results_in_same_window, $gsc_hide_search_button;
add_option($gsc_search_engine_id);
add_option($gsc_open_results_in_same_window);
add_option($gsc_hide_search_button);

?>