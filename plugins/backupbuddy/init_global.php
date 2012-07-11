<?php // This code runs everywhere.

// Make localization happen.
load_plugin_textdomain( 'it-l10n-backupbuddy', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );



/* BEGIN HANDLING DATA STRUCTURE UPGRADE */

// 2.x -> 3.x

/*
if ( pb_backupbuddy::$options['data_version'] < 3 ) {
	pb_backupbuddy::status( 'details', 'Migrating data structure. Currently: `' . pb_backupbuddy::$options['data_version'] . '`.' );
	require_once( pb_backupbuddy::plugin_path() . '/controllers/activation.php' );
}
*/

$options = get_site_option( 'pluginbuddy_backupbuddy' ); // Attempt to get 2.x options.
if ( is_multisite() ) { // Try to read site-specific settings in.
	$multisite_option = get_option( 'pluginbuddy_backupbuddy' );
	if ( $multisite_option ) {
		$options = $multisite_option;
	}
	unset( $multisite_option );
}

if ( $options !== false ) { // If options is not false then we need to upgrade.
	pb_backupbuddy::status( 'details', 'Migrating data structure. 2.x data discovered.' );
	require_once( pb_backupbuddy::plugin_path() . '/controllers/activation.php' );
}
unset( $options );

/* END HANDLING DATA STRUCTURE UPGRADE */



/********** ACTIONS (global) **********/
pb_backupbuddy::add_action( array( 'pb_backupbuddy-cron_scheduled_backup', 'process_scheduled_backup' ), 10, 5 ); // Scheduled backup.



/********** AJAX (global) **********/



/********** CRON (global) **********/
pb_backupbuddy::add_cron( 'process_backup', 10, 1 ); // Normal (manual) backup. Normal backups use cron system for scheduling each step when in modern mode. Classic mode skips this and runs all in one PHP process.
pb_backupbuddy::add_cron( 'final_cleanup', 10, 1 ); // Cleanup after backup.
pb_backupbuddy::add_cron( 'remote_send', 10, 4 ); // Manual remote destination sending.

// Remote destination copying. Eventually combine into one function to pass to individual remote destination classes to process.
pb_backupbuddy::add_cron( 'process_s3_copy', 10, 5 );
pb_backupbuddy::add_cron( 'process_dropbox_copy', 10, 2 );
pb_backupbuddy::add_cron( 'process_rackspace_copy', 10, 5 );
pb_backupbuddy::add_cron( 'process_ftp_copy', 10, 5 );



/********** FILTERS (global) **********/
pb_backupbuddy::add_filter( 'cron_schedules', 10, 0 ); // Add schedule periods such as bimonthly, etc into cron.



/********** WIDGETS (global) **********/

?>