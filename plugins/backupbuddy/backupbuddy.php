<?php
/**
 *
 *	Plugin Name: BackupBuddy
 *	Plugin URI: http://pluginbuddy.com/backupbuddy/
 *	Description: The most complete WordPress solution for Backup, Restoration, and Migration. Backs up a customizable selection of files, settings, and content for the complete snapshot of your site. Restore and/or migrate your site to a new host or new domain with complete ease-of-mind.
 *	Version: 3.0.38
 *	Author: Dustin Bolton
 *	Author URI: http://dustinbolton.com/
 *	Author URL: http://pluginbuddy.com/
 *	
 *	Installation:
 * 
 *	1. Download and unzip the latest release zip file.
 *	2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 *	3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 *	4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 * 
 *	Usage:
 * 
 *	1. Navigate to the new plugin menu in the Wordpress Administration Panel.
 *
 *	NOTE: DO NOT EDIT THIS OR ANY OTHER PLUGIN FILES. NO USER-CONFIGURABLE OPTIONS WITHIN.
 */



// Plugin defaults:
$pluginbuddy_settings = array(
				'slug'				=>		'backupbuddy',
				'series'			=>		'',
				'default_options'	=>		array(
												'data_version'						=>		'3',				// Data structure version. Added BB 2.0 to ease updating.												
												'importbuddy_pass_hash'				=>		'',					// ImportBuddy password hash.
												'importbuddy_pass_length'			=>		0,					// Length of the ImportBuddy password before it was hashed.
												'repairbuddy_pass_hash'				=>		'',					// ImportBuddy password hash.
												'repairbuddy_pass_length'			=>		0,					// Length of the ImportBuddy password before it was hashed.
												
												'backup_reminders'					=>		1,					// Todo: High security mode.
												'edits_since_last'					=>		0,					// Number of post/page edits since the last backup began.
												'last_backup'						=>		0,					// Timestamp of when last backup started.
												'last_backup_serial'				=>		'',					// Serial of last backup zip.
												'compression'						=>		1,					// Zip compression.
												'force_compatibility'				=>		0,					// Force compatibility mode even if normal is detected.
												'force_mysqldump_compatibility'		=>		0,					// Force compatibility mode for mysql db dumping. Uses PHP-based rather than command line mysqldump.
												'skip_database_dump'				=>		0,					// When enabled the database dump step will be skipped.
												'backup_nonwp_tables'				=>		0,					// Backup tables not prefixed with the WP prefix.
												'include_tables'					=>		array(),			// Additional tables to include.
												'exclude_tables'					=>		array(),			// Tables to exclude.
												'integrity_check'					=>		1,					// Zip file integrity check on the backup listing.
												'schedules'							=>		array(),			// Array of scheduled schedules.
												'log_level'							=>		'1',				// Valid options: 0 = none, 1 = errors only, 2 = errors + warnings, 3 = debugging (all kinds of actions)
												'excludes'							=>		'',					// Newline deliminated list of directories to exclude from the backup.
												'backup_reminders'					=>		1,					// Whether or not to show reminders to backup on post/page edits & on the WP upgrade page.
												'high_security'						=>		0,					// TODO: Future feature. Strip mysql password & admin user password. Prompt on import.
												'next_schedule_index'				=>		100,				// Next schedule index. Prevent any risk of hanging scheduled crons from having the same ID as a new schedule.
												'archive_limit'						=>		0,					// Maximum number of archives to storage. Deletes oldest if exceeded.
												'archive_limit_size'				=>		0,					// Maximum size of all archives to store. Deletes oldest if exeeded.
												'delete_archives_pre_backup'		=>		0,					// Whether or not to delete all backups prior to backing up.
												'lock_archives_directory'			=>		0,					// Whether or not to lock archives directory via htaccess and lift lock temporarily for download.
												
												'email_notify_scheduled_start'		=>		'',					// Email address(es) to send to when a scheduled backup begins.
												'email_notify_scheduled_complete'	=>		'',					// Email address(es) to send to when a scheduled backup completes.
												'email_notify_error'				=>		'',					// Email address(es) to send to when an error is encountered.
												
												'backups'							=>		array(),			// Contains past and currently happening backups. Stores info such as integrity, stats, etc.
												'remote_sends'						=>		array(),			// Keep a record of several remote sends.
												'remote_destinations'				=>		array(),			// Array of remote destinations (S3, Rackspace, email, ftp, etc)
												'role_access'						=>		'administrator',	// Default role access to the plugin.
												'dropboxtemptoken'					=>		'',					// Temporary Dropbox token for oauth.
												'backup_mode'						=>		'2',				// 1 = 1.x, 2 = 2.x mode
												'multisite_export'					=>		'0',				// Allow individual sites to be exported by admins of said subsite? (Network Admins can always export individual sites).
												'backup_directory'					=>		'',					// Backup directory to store all archives in.
												'temp_directory'					=>		'',					// Temporary directory to use for writing into.
												'log_serial'						=>		'',					// Current log serial to send all output to. Used during backups.
												'notifications'						=>		array(),			// TODO: currently not used.
												'mysqldump_mode'					=>		'prefix',			// prefix, all, or none
												'mysqldump_additional_includes'		=>	'',						// Additional db tables to backup in addition to those calculated by mysql_dumpmode.
												'mysqldump_additional_excludes'		=>	'',						// Additional db tables to EXCLUDE. This is taken into account last, after tables are calculated by mysql_dumpmode AND additional includes calculated.
												'alternative_zip'					=>	'0',					// Alternative zip system (Jeremy).
												'disable_zipmethod_caching'			=>	'0',					// When enabled the available zip methods are not cached. Useful for always showing the test for debugging or customer logging purposes for support.
												'zip_viewer_enabled'				=>	'0',					// Whether or not zip viewing is enabled. 0 for off, 1 for on. Currently off by default.
												
												'stats'								=>		array(
																							'site_size'				=>		0,
																							'site_size_excluded'	=>		0,
																							'site_size_updated'		=>		0,
																							'db_size'				=>		0,
																							'db_size_excluded'		=>		0,
																							'db_size_updated'		=>		0,
																					),
											),
				'migration_defaults'		=>	array(
													'web_address'			=>		'',
													'ftp_server'			=>		'',
													'ftp_username'			=>		'',
													'ftp_password'			=>		'',
													'ftp_path'				=>		'',
													'ftps'					=>		'0',
												),
				'backups_integrity_defaults'=>	array( // key is serial
													'status'				=>		'',
													'status_details'		=>		'',
													'scan_time'				=>		0,
													'file_size'				=>		0,
													'file_modified'			=>		0,
													'detected_type'			=>		'',
												),
				'schedule_defaults'	=>		array(
												'title'						=>		'',
												'type'						=>		'db',
												'interval'					=>		'monthly',
												'first_run'					=>		'',
												'delete_after'				=>		0,
												'remote_destinations'		=>		'',
												'last_run'					=>		0,
											),
				's3_defaults' 		=>		array(
												'title'			=>		'',
												'accesskey'		=>		'',
												'secretkey'		=>		'',
												'bucket'		=>		'',
												'directory'		=>		'',
												'ssl'			=>		1,
												'archive_limit'	=>		0,
											),
				'dropbox_defaults'	=>		array(
												'title'			=>		'',
												'token'			=>		'',
												'directory'		=>		'backupbuddy',
												'archive_limit'	=>		0,
											),
				'rackspace_defaults' =>		array(
												'title'			=>		'',
												'username'		=>		'',
												'api_key'		=>		'',
												'container'		=>		'',
												'server'		=>		'https://auth.api.rackspacecloud.com',
												'archive_limit'	=>		0,
											),
				'email_defaults'	=>		array(
												'title'			=>		'',
												'email'			=>		'',
											),
				'ftp_defaults'		=>		array(
												'title'			=>		'',
												'address'		=>		'',
												'username'		=>		'',
												'password'		=>		'',
												'path'			=>		'',
												'ftps'			=>		0,
												'archive_limit'	=>		0,
											),
				'local_destination_defaults'	=>	array(
												'title'			=>		'',
												'path'			=>		'',
												'url'			=>		'',
												'created'		=>		0,			// timestamp when created.
												'temporary'		=>		true,		// whether or not to auto-cleanup later.
											),
				'wp_minimum'		=>		'3.0.0',
				'php_minimum'		=>		'5.2',
				
				'modules'			=>		array(
												'updater'		=>		true,
												'downsizer'		=>		false,
												'filesystem'	=>		true,
												'format'		=>		true,
											),
			);



// Main plugin file.
$pluginbuddy_init = 'backupbuddy.php';


// Load compatibility functions.
require_once( dirname( __FILE__ ) . '/compat.php' );

// $settings is expected to be populated prior to including PluginBuddy framework. Do not edit below.
require( dirname( __FILE__ ) . '/pluginbuddy/_pluginbuddy.php' );
?>
