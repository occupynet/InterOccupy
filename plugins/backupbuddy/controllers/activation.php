<?php

// Any code in this file will be run upon plugin activation. NOTHING should be echo here or it will break activation.
// TODO: Set up proper data structure migration based on the structure version. This is a temporary approach. Sorry.




// ********** BEGIN 1.x -> 2.x DATA MIGRATION **********

$upgrade_options = get_option( 'ithemes-backupbuddy' ); // 1.x data structure storage location.
if ( $upgrade_options != false ) {
	pb_backupbuddy::$options = $upgrade_options;
	
	pb_backupbuddy::$options['email_notify_error'] = pb_backupbuddy::$options['email'];
	if ( pb_backupbuddy::$options['email_notify_manual'] == 1 ) {
		pb_backupbuddy::$options['email_notify_manual'] = pb_backupbuddy::$options['email'];
	}
	if ( pb_backupbuddy::$options['email_notify_scheduled'] == 1 ) {
		pb_backupbuddy::$options['email_notify_scheduled'] = pb_backupbuddy::$options['email'];
	}
	unset( pb_backupbuddy::$options['email'] );
	
	pb_backupbuddy::$options['archive_limit'] = pb_backupbuddy::$options['zip_limit'];
	unset( pb_backupbuddy::$options['zip_limit'] );
	
	pb_backupbuddy::$options['import_password'] = pb_backupbuddy::$options['password'];
	if ( pb_backupbuddy::$options['import_password'] == '#PASSWORD#' ) {
		pb_backupbuddy::$options['import_password'] = '';
	}
	unset( pb_backupbuddy::$options['password'] );
	
	if ( is_array( pb_backupbuddy::$options['excludes'] ) ) {
		pb_backupbuddy::$options['excludes'] = implode( "\n", pb_backupbuddy::$options['excludes'] );
	}
	
	pb_backupbuddy::$options['last_backup'] = pb_backupbuddy::$options['last_run'];
	unset( pb_backupbuddy::$options['last_run'] );
	
	// FTP.
	if ( !empty( pb_backupbuddy::$options['ftp_server'] ) ) {
		pb_backupbuddy::$options['remote_destinations'][0] = array(
														'title'			=>		'FTP',
														'address'		=>		pb_backupbuddy::$options['ftp_server'],
														'username'		=>		pb_backupbuddy::$options['ftp_user'],
														'password'		=>		pb_backupbuddy::$options['ftp_pass'],
														'path'			=>		pb_backupbuddy::$options['ftp_path'],
														'type'			=>		'ftp',
													);
		if ( pb_backupbuddy::$options['ftp_type'] == 'ftp' ) {
			pb_backupbuddy::$options['remote_destinations'][0]['ftps'] = 0;
		} else {
			pb_backupbuddy::$options['remote_destinations'][0]['ftps'] = 1;
		}
	}
	
	// Amazon S3.
	if ( !empty( pb_backupbuddy::$options['aws_bucket'] ) ) {
		pb_backupbuddy::$options['remote_destinations'][1] = array(
														'title'			=>		'S3',
														'accesskey'		=>		pb_backupbuddy::$options['aws_accesskey'],
														'secretkey'		=>		pb_backupbuddy::$options['aws_secretkey'],
														'bucket'		=>		pb_backupbuddy::$options['aws_bucket'],
														'directory'		=>		pb_backupbuddy::$options['aws_directory'],
														'ssl'			=>		pb_backupbuddy::$options['aws_ssl'],
														'type'			=>		's3',
													);
	}
	
	// Email destination.
	if ( !empty( pb_backupbuddy::$options['email'] ) ) {
		pb_backupbuddy::$options['remote_destinations'][2] = array(
														'title'			=>		'Email',
														'email'			=>		pb_backupbuddy::$options['email'],
													);
	}
	
	// Handle migrating scheduled remote destinations.
	foreach( pb_backupbuddy::$options['schedules'] as $schedule_id => $schedule ) {
		pb_backupbuddy::$options['schedules'][$schedule_id]['title'] = pb_backupbuddy::$options['schedules'][$schedule_id]['name'];
		unset( pb_backupbuddy::$options['schedules'][$schedule_id]['name'] );
		
		pb_backupbuddy::$options['schedules'][$schedule_id]['remote_destinations'] = '';
		if ( $schedule['remote_send'] == 'ftp' ) {
			pb_backupbuddy::$options['schedules'][$schedule_id]['remote_destinations'] .= '0|';
		}
		if ( $schedule['remote_send'] == 'aws' ) {
			pb_backupbuddy::$options['schedules'][$schedule_id]['remote_destinations'] .= '1|';
		}
		if ( $schedule['remote_send'] == 'email' ) {
			pb_backupbuddy::$options['schedules'][$schedule_id]['remote_destinations'] .= '2|';
		}
	}
	
	delete_option( 'ithemes-backupbuddy' );
}

pb_backupbuddy::save();

$old_log_file = WP_CONTENT_DIR . '/uploads/backupbuddy.txt';
if ( file_exists( $old_log_file ) ) {
	@unlink( $old_log_file );
}

// ********** END 1.x -> 2.x DATA MIGRATION **********






// ********** BEGIN 2.x -> 3.x DATA MIGRATION **********

// Attempt to get 2.x options.
$options = get_site_option( 'pluginbuddy_backupbuddy' );
//Try to read site-specific settings in
if ( is_multisite() ) {
	$multisite_option = get_option( 'pluginbuddy_backupbuddy' );
	if ( $multisite_option ) {
		$options = $multisite_option;
	}
	unset( $multisite_option );
}

// If options is not false then we need to upgrade.
if ( $options !== false ) {
	pb_backupbuddy::$options = array_merge( (array)pb_backupbuddy::settings( 'default_options' ), (array)$options ); // Merge defaults.
	unset( $options );
	
	if ( isset( pb_backupbuddy::$options['temporary_options']['experimental_zip'] ) ) {
		pb_backupbuddy::$options['alternative_zip'] = pb_backupbuddy::$options['temporary_options']['experimental_zip'];
	}
	
	if ( isset( pb_backupbuddy::$options['import_password'] ) ) { // Migrate import password to just hash.
		pb_backupbuddy::$options['importbuddy_pass_length'] = strlen( pb_backupbuddy::$options['import_password'] );
		pb_backupbuddy::$options['importbuddy_pass_hash'] = md5( pb_backupbuddy::$options['import_password'] );
		unset( pb_backupbuddy::$options['import_password'] );
	}
	
	if ( isset( pb_backupbuddy::$options['repairbuddy_password'] ) ) { // Migrate repair password to just hash.
		pb_backupbuddy::$options['repairbuddy_pass_length'] = strlen( pb_backupbuddy::$options['repairbuddy_password'] );
		pb_backupbuddy::$options['repairbuddy_pass_hash'] = md5( pb_backupbuddy::$options['repairbuddy_password'] );
		unset( pb_backupbuddy::$options['repairbuddy_password'] );
	}
	
	// Migrate email_notify_scheduled -> email_notify_scheduled_complete
	pb_backupbuddy::$options['email_notify_scheduled_complete'] = pb_backupbuddy::$options['email_notify_scheduled'];
	
	// Migrate log file.
	$old_log_file = ABSPATH . '/wp-content/uploads/pluginbuddy_backupbuddy' . '-' . pb_backupbuddy::$options['log_serial'] . '.txt';
	if ( @file_exists ( $old_log_file ) ) {
		$new_log_file = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/log-' . pb_backupbuddy::$options['log_serial'] . '.txt';
		@copy( $old_log_file, $new_log_file );
		if ( file_exists( $new_log_file ) ) { // If new log exists then we can delete the old.
			@unlink( $old_log_file );
		}
	}
	
	delete_option( 'pluginbuddy_backupbuddy' ); // Remove 2.x options.
	delete_site_option( 'pluginbuddy_backupbuddy' ); // Remove 2.x options.
	
	pb_backupbuddy::$options['data_version'] = '3'; // Update data structure version to 3.
	pb_backupbuddy::save(); // Save 3.0 options.
}

unset( $options );

// ********** END 2.x -> 3.x DATA MIGRATION **********






// MISC SETUP:

// Set up default error email notification email address if none is set.
if ( pb_backupbuddy::$options['email_notify_error'] == '' ) {
	pb_backupbuddy::$options['email_notify_error'] = get_option( 'admin_email' );
	pb_backupbuddy::save();
}

?>