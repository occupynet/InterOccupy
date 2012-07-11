<?php
/*
Plugin Name: Ultimate Branding
Plugin URI: http://premium.wpmudev.org/project/white-label-branding
Description: A complete white-label and branding solution for multisite. Login images, favicons, remove WordPress links and branding, and much more.
Author: Barry (Incsub), Andrew Billits, Ulrich Sossou, Ve Bailovity (Incsub)
Version: 1.0.3
Author URI: http://premium.wpmudev.org/
Network: true
Text_domain: ub
WDP ID: 9135
*/

// Include the configuration library
require_once('ultimate-branding-files/includes/config.php');
// Include the functions library
require_once('ultimate-branding-files/includes/functions.php');

// Set up my location
set_ub_url(__FILE__);
set_ub_dir(__FILE__);

if(is_admin()) {
	// Add in the contextual help
	require_once('ultimate-branding-files/classes/class.help.php');
	// Include the admin class
	require_once('ultimate-branding-files/classes/ubadmin.php');
	$uba = new UltimateBrandingAdmin();

} else {
	// Include the public class
	require_once('ultimate-branding-files/classes/ubpublic.php');
	$ubp = new UltimateBrandingPublic();
}