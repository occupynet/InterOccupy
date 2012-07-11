<?php
// If we are on a campus install then we should be hiding some of the modules
if(!defined('UB_ON_CAMPUS')) define('UB_ON_CAMPUS', false);
// Allows the branding admin menus to be hidden on a single site install
if(!defined('UB_HIDE_ADMIN_MENU')) define('UB_HIDE_ADMIN_MENU', false);
// Allows the main blog to be changed from the default with an id of 1
if(!defined('UB_MAIN_BLOG_ID')) define('UB_MAIN_BLOG_ID', 1);

?>