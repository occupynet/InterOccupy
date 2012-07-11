<?php

global $gsc_version, $gcs_plugin_name, $gsc_plugin_dir_path, $gsc_search_engine_id, $gsc_open_results_in_same_window, $gsc_hide_search_button;

$gcs_plugin_name = "google-custom-search";

$gsc_plugin_dir_path = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));

$gsc_version = "1.3.4";

$gsc_search_engine_id = "gsc_search_engine_id";

$gsc_open_results_in_same_window = "gsc_open_results_in_same_window";

$gsc_hide_search_button = "gsc_hide_search_button";

$number_of_widgets_using_pop_up_displays = 0;

//Search Results display constants
define('DISPLAY_RESULTS_AS_POP_UP', 0);
define('DISPLAY_RESULTS_IN_UNDER_SEARCH_BOX', 1);
define('DISPLAY_RESULTS_CUSTOM', 2);

?>