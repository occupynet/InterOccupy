<?php
// Helper functions for BackupBuddy.
// TODO: Eventually break out of a lot of these from BB core. Migrating from old framework to new resulted in this mid-way transition but it's a bit messy...

class pb_backupbuddy_core {
	
	
	
	/*	is_network_activated()
	 *	
	 *	Returns a boolean indicating whether a plugin is network activated or not.
	 *	
	 *	@return		boolean			True if plugin is network activated, else false.
	 */
	function is_network_activated() {
		
		if ( !function_exists( 'is_plugin_active_for_network' ) ) { // Function is not available on all WordPress pages for some reason according to codex.
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active_for_network( basename( pb_backupbuddy::plugin_path() ) . '/' . pb_backupbuddy::settings( 'init' ) ) ) { // Path relative to wp-content\plugins\ directory.
			return true;
		} else {
			return false;
		}
		
	} // End is_network_activated().
	
	
	
	/*	backup_integrity_check()
	 *	
	 *	Scans a backup file and saves the result in data structure. Checks for key files & that .zip can be read properly. Stores results with details in data structure.
	 *	
	 *	@param		string		$file		Full pathname & filename to backup file to check.
	 *	@return		boolean					True if integrity 100% passed, else false.
	 */
	function backup_integrity_check( $file ) {
		
		$serial = $this->get_serial_from_file( $file );
		
		// User selected to rescan a file.
		if ( pb_backupbuddy::_GET( 'reset_integrity' ) == $serial ) {
			pb_backupbuddy::alert( 'Rescanning backup integrity for backup file `' . basename( $file ) . '`' );
		}
		
		if ( isset( pb_backupbuddy::$options['backups'][$serial]['integrity'] ) && ( count( pb_backupbuddy::$options['backups'][$serial]['integrity'] ) > 0 ) && ( pb_backupbuddy::_GET( 'reset_integrity' ) != $serial ) ) { // Already have integrity data and NOT resetting this one.
			pb_backupbuddy::status( 'details', 'Integrity data for backup `' . $serial . '` is cached; not scanning again.' );
			return;
		} elseif ( pb_backupbuddy::_GET( 'reset_integrity' ) == $serial ) { // Resetting this one.
			pb_backupbuddy::status( 'details', 'Resetting backup integrity stats for backup with serial `' . $serial . '`.' );
		}
		
		if ( pb_backupbuddy::$options['integrity_check'] == '0' ) { // Integrity checking disabled.
			$file_stats = @stat( $file );
			if ( $file_stats === false ) { // stat failure.
				pb_backupbuddy::alert( 'Error #4539774. Unable to get file details ( via stat() ) for file `' . $file . '`. The file may be corrupt or too large for the server.' );
				$file_size = 0;
				$file_modified = 0;
			} else { // stat success.
				$file_size = $file_stats['size'];
				$file_modified = $file_stats['mtime'];
			}
			unset( $file_stats );
			
			$integrity = array(
				'status'				=>		'Unknown',
				'status_details'		=>		__( 'Integrity checking disabled based on settings. This file has not been verified.', 'it-l10n-backupbuddy' ),
				'scan_time'				=>		0,
				'detected_type'			=>		'unknown',
				'size'					=>		$file_size,
				'modified'				=>		$file_modified,
				'file'					=>		basename( $file ),
				'comment'				=>		false,
			);
			pb_backupbuddy::$options['backups'][$serial]['integrity'] = array_merge( pb_backupbuddy::settings( 'backups_integrity_defaults' ), $integrity );
			pb_backupbuddy::save();
			
			return;
		}
		
		// Defaults
		$integrity_checked = true;
		$found_dat = false;
		$found_sql = false;
		$found_wpc = false;
		$backup_type = '';
		
		
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		}
		
		
		// Redirect logging output to a certain log file.
		pb_backupbuddy::set_status_serial( 'zipbuddy_test' );
		
		
		// Look for comment.
		$comment = pb_backupbuddy::$classes['zipbuddy']->get_comment( $file );
				
		
		// Check for DAT file.
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'wp-content/uploads/backupbuddy_temp/' . $serial . '/backupbuddy_dat.php' ) === true ) { // Post 2.0 full backup
			$found_dat = true;
			$backup_type = 'full';
		}
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'wp-content/uploads/temp_' . $serial . '/backupbuddy_dat.php' ) === true ) { // Pre 2.0 full backup
			$found_dat = true;
			$backup_type = 'full';
		}
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'backupbuddy_dat.php' ) === true ) { // DB backup
			$found_dat = true;
			$backup_type = 'db';
		}
		
		
		// Check for DB SQL file.
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'wp-content/uploads/backupbuddy_temp/' . $serial . '/db_1.sql' ) === true ) { // post 2.0 full backup
			$found_sql = true;
			$backup_type = 'full';
		}
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'wp-content/uploads/temp_' . $serial . '/db.sql' ) === true ) { // pre 2.0 full backup
			$found_sql = true;
			$backup_type = 'full';
		}
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'db_1.sql' ) === true ) { // db only backup 2.0+
			$found_sql = true;
			$backup_type = 'db';
		}
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'db.sql' ) === true ) { // db only backup pre-2.0
			$found_sql = true;
			$backup_type = 'db';
		}
		
		
		// Check for WordPress config file.
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'wp-config.php' ) === true ) {
			$found_wpc = true;
			$backup_type = 'full';
		}
		if ( pb_backupbuddy::$classes['zipbuddy']->file_exists( $file, 'wp-content/uploads/backupbuddy_temp/' . $serial . '/wp-config.php' ) === true ) {
			$found_wpc = true;
			$backup_type = 'full';
		}
		
		
		// Calculate status from results.
		$integrity_status = 'pass';
		$integrity_description = '';
		$status_items = pb_backupbuddy::get_status( 'zipbuddy_test' );
		$integrity_zipresult_details = array();
		foreach( (array)$status_items as $status_item ) {
			$integrity_zipresult_details[] = $status_item[4];
		}
		$integrity_zipresult_details = implode( '<br />', $integrity_zipresult_details );
		
		
		// Clear logging to certain file.
		pb_backupbuddy::set_status_serial( '' );
		
		
		// Calculate status descriptions.
		if ( $found_dat !== true ) {
			$integrity_status = 'fail';
			$integrity_description .= __('Missing .dat file.', 'it-l10n-backupbuddy' ) . '<br />';
		}
		if ( $found_sql !== true ) {
			$integrity_status = 'fail';
			$integrity_description .= __('Missing DB SQL file.', 'it-l10n-backupbuddy' ) . '<br />';
		}
		if ( ($backup_type == 'full' ) && ( $found_wpc !== true ) ) {
			$integrity_status = 'fail';
			$integrity_description .= __('Missing WP config file.', 'it-l10n-backupbuddy' ) . '<br />';
		}
		$integrity_scantime = time();
		if ( $integrity_status == 'pass' ) { // All tests passed.
			$integrity_description = 'All tests passed.';
		} else { // One or more test failures encountered.
			$integrity_description .= '<br />' . __('Technical Details', 'it-l10n-backupbuddy' ) . ':<br />' . $integrity_zipresult_details;
		}
		
		// Get file information from file system.
		$file_stats = @stat( $file );
		if ( $file_stats === false ) { // stat failure.
			pb_backupbuddy::alert( 'Error #4539774. Unable to get file details ( via stat() ) for file `' . $file . '`. The file may be corrupt or too large for the server.' );
			$file_size = 0;
			$file_modified = 0;
		} else { // stat success.
			$file_size = $file_stats['size'];
			$file_modified = $file_stats['ctime']; // Created time.
		}
		unset( $file_stats );
		
		// Compile array of results for saving into data structure.
		$integrity = array(
			'status'				=>		$integrity_status,
			'status_details'		=>		$integrity_description,
			'scan_time'				=>		$integrity_scantime,
			'detected_type'			=>		$backup_type,
			'size'					=>		$file_size,
			'modified'				=>		$file_modified,				// Actually created time now.
			'file'					=>		basename( $file ),
			'comment'				=>		$comment,					// boolean false if no comment. string if comment.
		);
		pb_backupbuddy::$options['backups'][$serial]['integrity'] = array_merge( pb_backupbuddy::settings( 'backups_integrity_defaults' ), $integrity );
		pb_backupbuddy::save();
		//pb_backupbuddy::$classes['zipbuddy']->clear_status();
		
		
		if ( $integrity_status == 'pass' ) { // 100% success
			return true;
		} else {
			return false;
		}
		
		
	} // End backup_integrity_check().
	
	
	
	/*	get_serial_from_file()
	 *	
	 *	Returns the backup serial based on the filename.
	 *	
	 *	@param		string		$file		Filename containing a serial to extract.
	 *	@return		string					Serial found.
	 */
	public function get_serial_from_file( $file ) {
		
		$serial = strrpos( $file, '-' ) + 1;
		$serial = substr( $file, $serial, ( strlen( $file ) - $serial - 4 ) );
		
		return $serial;
		
	} // End get_serial_from_file().
	
	
	
	/**
	 * versions_confirm()
	 *
	 * Check the version of an item and compare it to the minimum requirements BackupBuddy requires.
	 *
	 * @param		string		$type		Optional. If left blank '' then all tests will be performed. Valid values: wordpress, php, ''.
	 * @param		boolean		$notify		Optional. Whether or not to alert to the screen (and throw error to log) of a version issue.\
	 * @return		boolean					True if the selected type is a bad version
	 */
	function versions_confirm( $type = '', $notify = false ) {
		
		$bad_version = false;
		
		if ( ( $type == 'wordpress' ) || ( $type == '' ) ) {
			global $wp_version;
			if ( version_compare( $wp_version, pb_backupbuddy::settings( 'wp_minimum' ), '<=' ) ) {
				if ( $notify === true ) {
					pb_backupbuddy::alert( sprintf( __('ERROR: BackupBuddy requires WordPress version %1$s or higher. You may experience unexpected behavior or complete failure in this environment. Please consider upgrading WordPress.', 'it-l10n-backupbuddy' ), $this->_wp_minimum) );
					pb_backupbuddy::log( 'Unsupported WordPress Version: ' . $wp_version , 'error' );
				}
				$bad_version = true;
			}
		}
		if ( ( $type == 'php' ) || ( $type == '' ) ) {
			if ( version_compare( PHP_VERSION, pb_backupbuddy::settings( 'php_minimum' ), '<=' ) ) {
				if ( $notify === true ) {
					pb_backupbuddy::alert( sprintf( __('ERROR: BackupBuddy requires PHP version %1$s or higher. You may experience unexpected behavior or complete failure in this environment. Please consider upgrading PHP.', 'it-l10n-backupbuddy' ), PHP_VERSION ) );
					pb_backupbuddy::log( 'Unsupported PHP Version: ' . PHP_VERSION , 'error' );
				}
				$bad_version = true;
			}
		}
		
		return $bad_version;
		
	}
	
	
	
	/*	get_directory_exclusions()
	 *	
	 *	Get sanitized directory exclusions. See important note below!
	 *	IMPORTANT NOTE: Cannot exclude the temp directory here as this is where SQL and DAT files are stored for inclusion in the backup archive.
	 *	
	 *	@return		array				Array of directories to exclude.
	 */
	public static function get_directory_exclusions() {
		
		// Get initial array.
		$exclusions = trim( pb_backupbuddy::$options['excludes'] ); // Trim string.
		$exclusions = preg_split('/\n|\r|\r\n/', $exclusions ); // Break into array on any type of line ending.
		
		// Add additional internal exclusions.
		$exclusions[] = str_replace( rtrim( ABSPATH, '\\\/' ), '', pb_backupbuddy::$options['backup_directory'] ); // Exclude backup directory.
		
		// Clean up & sanitize array.
		array_walk( $exclusions, create_function( '&$val', '$val = rtrim( trim( $val ), \'/\' );' ) ); // Apply trim to all items within.
		$exclusions = array_filter( $exclusions, 'strlen' ); // Remove any empty / blank lines.
		
		// IMPORTANT NOTE: Cannot exclude the temp directory here as this is where SQL and DAT files are stored for inclusion in the backup archive.
		return $exclusions;
		
	} // End get_directory_exclusions().
	
	
	
	/*	mail_error()
	 *	
	 *	Sends an error email to the defined email address(es) on settings page.
	 *	
	 *	@param		string		$message	Message to be included in the body of the email.
	 *	@return		null
	 */
	function mail_error( $message ) {
		
		pb_backupbuddy::status( 'error', 'Mail error: `' . $message . '`.' );
		
		if ( defined( 'PB_DEMO_MODE' ) ) {
			return;
		}
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			$this->load();
		}
		
		$email = pb_backupbuddy::$options['email_notify_error'];
		if ( !empty( $email ) ) {
			wp_mail( $email, "BackupBuddy Error - " . site_url(), "An error occurred with BackupBuddy v" . pb_backupbuddy::settings( 'version' ) . " on " . date(DATE_RFC822) . " for the site ". site_url() . ".  The error is displayed below:\r\n\r\n".$message, 'From: '.$email."\r\n".'Reply-To: '.get_option('admin_email')."\r\n");
		}
		
	} // End mail_error().
	
	
	
	/*	mail_notify_scheduled()
	 *	
	 *	Sends a message email to the defined email address(es) on settings page.
	 *	
	 *	@param		string		$start_or_complete	Whether this is the notifcation for starting or completing. Valid values: start, complete
	 *	@param		string		$message			Message to be included in the body of the email.
	 *	@return		null
	 */
	function mail_notify_scheduled( $start_or_complete, $message ) {
		
		pb_backupbuddy::status( 'details', 'Sending email notification for scheduled backup if applicable. Start or complete: `' . $start_or_complete . '`, Message: `' . $message . '`.' );
		
		if ( defined( 'PB_DEMO_MODE' ) ) {
			return;
		}
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			pb_backupbuddy::load();
		}
		
		if ( $start_or_complete == 'start' ) {
			$email = pb_backupbuddy::$options['email_notify_scheduled_start'];
		} elseif ( $start_or_complete == 'complete' ) {
			$email = pb_backupbuddy::$options['email_notify_scheduled_complete'];
		} else {
		}
		
		
		$subject = "BackupBuddy Scheduled Backup - " . site_url();
		$body = "A scheduled backup occurred with BackupBuddy v" . pb_backupbuddy::settings( 'version' ) . " on " . date(DATE_RFC822) . " for the site ". site_url() . ".\n\n";
		$body .= "The notice is displayed below:\r\n\r\n".$message;
		
		// TODO: on completed also provide final backup size, time taken, etc? Issue PBBB-244.
		
		pb_backupbuddy::status( 'details', 'Sending email to address `' . $email . '` as defined in settings.' );
		if ( !empty( $email ) ) {
			wp_mail( $email, $subject, $body, 'From: '.$email."\r\n".'Reply-To: '.get_option('admin_email')."\r\n");
		}
	} // End mail_notify_scheduled().
	
	
	
	/*	backup_prefix()
	 *	
	 *	Strips all non-file-friendly characters from the site URL. Used in making backup zip filename.
	 *	
	 *	@return		string		The filename friendly converted site URL.
	 */
	function backup_prefix() {
		
		$siteurl = site_url();
		$siteurl = str_replace( 'http://', '', $siteurl );
		$siteurl = str_replace( 'https://', '', $siteurl );
		$siteurl = str_replace( '/', '_', $siteurl );
		$siteurl = str_replace( '\\', '_', $siteurl );
		$siteurl = str_replace( '.', '_', $siteurl );
		$siteurl = str_replace( ':', '_', $siteurl ); // Alternative port from 80 is stored in the site url.
		return $siteurl;
		
	} // End backup_prefix().
	
	
	
	/*	send_remote_destination()
	 *	
	 *	function description
	 *	
	 *	@param		int		$destination_id		ID number (index of the destinations array) to send it.
	 *	@param		string	$file				Full file path of file to send.
	 *	@param		string	$trigger			What triggered this backup. Valid values: scheduled, manual.
	 *	@param		bool	$send_importbuddy	Whether or not importbuddy.php should also be sent with the file to destination.
	 *	@return		
	 */
	function send_remote_destination( $destination_id, $file, $trigger = '', $send_importbuddy = false ) {
		
		if ( defined( 'PB_DEMO_MODE' ) ) {
			return false;
		}
		
		// Record some statistics.
		$identifier = pb_backupbuddy::random_string( 12 );
		pb_backupbuddy::$options['remote_sends'][$identifier] = array(
			'destination'		=>	$destination_id,
			'file'				=>	$file,
			'trigger'			=>	$trigger,						// What triggered this backup. Valid values: scheduled, manual.
			'send_importbuddy'	=>	$send_importbuddy,
			'start_time'		=>	time(),
			'finish_time'		=>	0,
			'status'			=>	'timeout',  // success, failure, timeout (default assumption if this is not updated in this PHP load)
		);
		pb_backupbuddy::save();
		
		// Reference for easier usage.
		$destination = &pb_backupbuddy::$options['remote_destinations'][$destination_id];
		
		// Determine destination type and pass to its method to send. TODO: Modularize this in the future. This has slowly crept into a mess...
		if ( $destination['type'] == 's3' ) {
			$destination = array_merge( pb_backupbuddy::settings( 's3_defaults' ), $destination ); // load defaults
			if ( ( $destination['ssl'] == '1' ) || ( $destination['ssl'] === true ) ) {
				$s3_ssl = true;
			} else {
				$s3_ssl = false;
			}
			$response = $this->remote_send_s3( $destination['accesskey'], $destination['secretkey'], $destination['bucket'], $destination['directory'], $s3_ssl, $file, $destination['archive_limit'], $send_importbuddy );
		} elseif ( $destination['type'] == 'dropbox' ) {
			$destination = array_merge( pb_backupbuddy::settings( 'dropbox_defaults' ), $destination ); // load defaults
			$response = $this->remote_send_dropbox( $destination['token'], $destination['directory'], $file, $destination['archive_limit'], $send_importbuddy );
		} elseif ( $destination['type'] == 'rackspace' ) {
			$destination = array_merge( pb_backupbuddy::settings( 'rackspace_defaults' ), $destination ); // load defaults
			$response = $this->remote_send_rackspace( $destination['username'], $destination['api_key'], $destination['container'], $file, $destination['archive_limit'], $destination['server'], $send_importbuddy );
		} elseif ( $destination['type'] == 'email' ) {
			$destination = array_merge( pb_backupbuddy::settings( 'email_defaults' ), $destination ); // load defaults
			$response = $this->remote_send_email( $destination['email'], $file, $send_importbuddy );
		} elseif ( $destination['type'] == 'ftp' ) {
			$destination = array_merge( pb_backupbuddy::settings( 'ftp_defaults' ), $destination ); // load defaults
			$response = $this->remote_send_ftp( $destination['address'], $destination['username'], $destination['password'], $destination['path'], $destination['ftps'], $file, $destination['archive_limit'], $send_importbuddy );
		} elseif ( $destination['type'] == 'local' ) {
			$destination = array_merge( pb_backupbuddy::settings( 'local_destination_defaults' ), $destination ); // load defaults
			$response = $this->remote_send_local( $destination['path'], $file, $send_importbuddy );
		} else {
			return false; // Invalid destination.
		}
		
		// Update stats.
		pb_backupbuddy::$options['remote_sends'][$identifier]['finish_time'] = time();
		if ( $response === true ) { // succeeded.
			pb_backupbuddy::$options['remote_sends'][$identifier]['status'] = 'success';
		} else { // failed.
			pb_backupbuddy::$options['remote_sends'][$identifier]['status'] = 'failure';
		}
		pb_backupbuddy::save();
		
		return $response;
		
	} // End send_remote_destination().
	
	
	
	/*	remote_send_dropbox()
	 *	
	 *	Send to this remote destination.
	 *	
	 *	@param		string		$token				Dropbox token??
	 *	@param		string		$directory			Directory to send into.
	 *	@param		string		$file				Full file path to the file to send.
	 *	@param		int			$limit				Maximum number of backups for this site in this directory for this account. No limit if zero 0.
	 *	@param		bool		$send_importbuddy	Whether or not to also send importbuddy.php.
	 *	@return		bool							True on success, else false.
	 */
	function remote_send_dropbox( $token, $directory, $file, $limit = 0, $send_importbuddy = false ) {
		
		// Normalize picky dropbox directory.
		$directory = trim( $directory, '\\/' );
		$directory = str_replace( ' ', '%20', $directory );
		
		pb_backupbuddy::status( 'details',  'Starting Dropbox transfer.' );
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/dropbuddy/dropbuddy.php' );
		$dropbuddy = new pb_backupbuddy_dropbuddy( $token );
		if ( $dropbuddy->authenticate() !== true ) {
			pb_backupbuddy::status( 'details',  'Dropbox authentication failed in remote_send_dropbox.' );
			return false;
		}
		
		pb_backupbuddy::status( 'details',  'About to put object `' . basename( $file ) . '` to Dropbox cron.' );
		$status = $dropbuddy->put_file( $directory . '/' . basename( $file ), $file );
		if ( $status === true ) {
			pb_backupbuddy::status( 'details',  'SUCCESS sending to Dropbox!' );
		} else {
			pb_backupbuddy::status( 'details',  'Dropbox file send FAILURE. HTTP Status: ' . $status['httpStatus'] . '; Body: ' . $status['body'], 'error' );
			return false;
		}
		
		// Handle sending importbuddy.php.
		if ( $send_importbuddy === true ) {
			pb_backupbuddy::status( 'details', 'Sending importbuddy to Dropbox based on settings.' );
			$importbuddy_temp = pb_backupbuddy::$options['temp_directory'] . 'importbuddy_' . pb_backupbuddy::random_string( 10 ) . '.php.tmp'; // Full path & filename to temporary importbuddy
			$this->importbuddy( $importbuddy_temp ); // Create temporary importbuddy.
			$dropbuddy->put_file( $directory . '/' . basename( $file ), $file );
			@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
		}
		
		// Start remote backup limit
		if ( $limit > 0 ) {
			pb_backupbuddy::status( 'details',  'Dropbox file limit in place. Proceeding with enforcement.' );
			$meta_data = $dropbuddy->get_meta_data( $directory );
			
			// Create array of backups and organize by date
			$bkupprefix = $this->backup_prefix();
			
			$backups = array();
			foreach ( (array) $meta_data['contents'] as $file ) {
				// check if file is backup
				if ( ( strpos( $file['path'], 'backup-' . $bkupprefix . '-' ) !== FALSE ) ) {
					$backups[$file['path']] = strtotime( $file['modified'] );
				}
			}
			arsort($backups);
			
			if ( ( count( $backups ) ) > $limit ) {
				pb_backupbuddy::status( 'details',  'Dropbox backup file count of `' . count( $backups ) . '` exceeds limit of `' . $limit . '`.' );
				$i = 0;
				$delete_fail_count = 0;
				foreach( $backups as $buname => $butime ) {
					$i++;
					if ( $i > $limit ) {
						if ( !$dropbuddy->delete( $buname ) ) { // Try to delete backup on Dropbox. Increment failure count if unable to.
							pb_backupbuddy::status( 'details',  'Unable to delete excess Dropbox file: `' . $buname . '`' );
							$delete_fail_count++;
						}
					}
				}
				
				if ( $delete_fail_count !== 0 ) {
					$this->mail_error( sprintf( __('Dropbox remote limit could not delete %s backups.', 'it-l10n-backupbuddy' ), $delete_fail_count) );
				}
			}
		} else {
			pb_backupbuddy::status( 'details',  'No Dropbox file limit to enforce.' );
		}
		// End remote backup limit
		
		return true; // Success if made it this far.
		
	} // End remote_send_dropbox().
	
	
	
	/*	remote_send_s3()
	 *	
	 *	Send to this remote destination.
	 *	
	 *	@param		string		$accesskey			Amazon access key.
	 *	@param		string		$secretkey			Amazon secret key.
	 *	@param		string		$bucket				Amazon bucket to put into.
	 *	@param		string		$directory			Amazon directory to put into.
	 *	@param		bool		$ssl				Whether or not to use SSL encryption for connecting.
	 *	@param		string		$file				Full file path to the file to send.
	 *	@param		int			$limit				Maximum number of backups for this site in this directory for this account. No limit if zero 0.
	 *	@param		bool		$send_importbuddy	Whether or not to also send importbuddy.php.
	 *	@return		bool							True on success, else false.
	 */
	function remote_send_s3( $accesskey, $secretkey, $bucket, $directory = '', $ssl, $file, $limit = 0, $send_importbuddy = false ) {
		
		pb_backupbuddy::status( 'details',  'Starting Amazon S3 transfer.' );
		
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/s3/s3.php' );
		$s3 = new pb_backupbuddy_S3( $accesskey, $secretkey, $ssl );
		
		
		// Set bucket with permissions.
		pb_backupbuddy::status( 'details',  'About to put bucket `' . $bucket . '` to Amazon S3 cron.' );
		$s3->putBucket( $bucket, pb_backupbuddy_S3::ACL_PRIVATE );
		pb_backupbuddy::status( 'details',  'About to put object `' . basename( $file ) . '` to Amazon S3 cron.' );
		if ( !empty( $directory ) ) {
			$directory = $directory . '/';
		}
		
		// Send file.
		if ( true === ( $s3_response = $s3->putObject( pb_backupbuddy_S3::inputFile( $file ), $bucket, $directory . basename( $file ), pb_backupbuddy_S3::ACL_PRIVATE) ) ) {
			pb_backupbuddy::status( 'details',  'SUCCESS sending to Amazon S3! Response: ' . $s3_response );
			
			if ( $send_importbuddy === true ) {
				pb_backupbuddy::status( 'details', 'Sending importbuddy to S3 based on settings.' );
				$importbuddy_temp = pb_backupbuddy::$options['temp_directory'] . 'importbuddy_' . pb_backupbuddy::random_string( 10 ) . '.php.tmp'; // Full path & filename to temporary importbuddy
				$this->importbuddy( $importbuddy_temp ); // Create temporary importbuddy.
				$s3->putObject( pb_backupbuddy_S3::inputFile( $importbuddy_temp ), $bucket, $directory . 'importbuddy.php', pb_backupbuddy_S3::ACL_PRIVATE );
				@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
			}
			
			// Start remote backup limit
			if ( $limit > 0 ) {
				$results = $s3->getBucket( $bucket );
				
				// Create array of backups and organize by date
				$bkupprefix = $this->backup_prefix();
				
				$backups = array();
				foreach( $results as $rekey => $reval ) {
					$pos = strpos( $rekey, $directory . 'backup-' . $bkupprefix . '-' );
					if ( $pos !== FALSE ) {
						$backups[$rekey] = $results[$rekey]['time'];
					}
				}
				arsort( $backups );
				
				
				if ( ( count( $backups ) ) > $limit ) {
					$i = 0;
					$delete_fail_count = 0;
					foreach( $backups as $buname => $butime ) {
						$i++;
						if ( $i > $limit ) {
							if ( !$s3->deleteObject( $bucket, $buname ) ) {
								pb_backupbuddy::status( 'details',  'Unable to delete excess S3 file `' . $buname . '` in bucket `' . $bucket . '`.' );
								$delete_fail_count++;
							}
						}
					}
					if ( $delete_fail_count !== 0 ) {
						$this->mail_error( sprintf( __('Amazon S3 remote limit could not delete %s backups.', 'it-l10n-backupbuddy' ),  $delete_fail_count ) );
					}
				}
			} else {
				pb_backupbuddy::status( 'details',  'No S3 file limit to enforce.' );
			}
			// End remote backup limit
			
			return true; // Success
		} else { // Failed.
			$error_message = 'ERROR #9024: Connected to Amazon S3 but unable to put file. There is a problem with one of the following S3 settings: bucket, directory, or S3 permissions. Details:' . "\n\n" . $s3_response . "\n\n" . 'http://ithemes.com/codex/page/BackupBuddy:_Error_Codes#9024';
			$this->mail_error( __( $error_message, 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::status( 'details',  $error_message, 'error' );
			
			return false; // Failed.
		}
		
	} // End remote_send_s3().
	
	
	
	/*	remote_send_rackspace()
	 *	
	 *	Send to this remote destination. by Skyler Moore?
	 *	
	 *	@param		string		$rs_username		Rackspace username.
	 *	@param		string		$rs_api_key			Rackspace API key.
	 *	@param		string		$rs_container		Rackspace container to send into.
	 *	@param		string		$rs_file			Full file path to the file to send.
	 *	@param		int			$limit				Maximum number of backups for this site in this directory for this account. No limit if zero 0.
	 *	@param		string		$rs_server			Server address to connect to for sending. For instance the UK Rackspace cloud URL differs.
	 *	@param		bool		$send_importbuddy	Whether or not to also send importbuddy.php.
	 *	@return		bool							True on success, else false.
	 */
	function remote_send_rackspace( $rs_username, $rs_api_key, $rs_container, $rs_file, $limit = 0, $rs_server, $send_importbuddy = false ) {
		
		pb_backupbuddy::status( 'details',  'Starting Rackspace transfer.' );
		
		$rs_file = basename( $rs_file );
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/rackspace/cloudfiles.php' );
		$auth = new CF_Authentication( $rs_username, $rs_api_key, NULL, $rs_server );
		$auth->authenticate();
		$conn = new CF_Connection( $auth );

		// Set container
		$container = $conn->get_container($rs_container);
		
		pb_backupbuddy::status( 'details',  'About to put object `' . basename( $rs_file ) . '` to Rackspace cron.' );
		
		// Put file to Rackspace.
		$testbackup = $container->create_object( $rs_file );
		if ( $testbackup->load_from_filename( ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . $rs_file ) ) {
			
			if ( $send_importbuddy === true ) {
				pb_backupbuddy::status( 'details', 'Sending importbuddy to Rackspace based on settings.' );
				$importbuddy_temp = pb_backupbuddy::$options['temp_directory'] . 'importbuddy_' . pb_backupbuddy::random_string( 10 ) . '.php.tmp'; // Full path & filename to temporary importbuddy
				$this->importbuddy( $importbuddy_temp ); // Create temporary importbuddy.
				
				$rs_importbuddy = $container->create_object( 'importbuddy.php' );
				$rs_importbuddy->load_from_filename( $importbuddy_temp );
				
				@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
			}
			
			// Start remote backup limit
			if ( $limit > 0 ) {
				$bkupprefix = $this->backup_prefix();
				
				$results = $container->get_objects( 0, NULL, 'backup-' . $bkupprefix . '-' );
				// Create array of backups and organize by date
				$backups = array();
				foreach( $results as $backup ) {
					$backups[$backup->name] = $backup->last_modified;
				}
				arsort( $backups );
				
				if ( ( count( $backups ) ) > $limit ) {
					$i = 0;
					$delete_fail_count = 0;
					foreach( $backups as $buname => $butime ) {
						$i++;
						if ( $i > $limit ) {
							if ( !$container->delete_object( $buname ) ) {
								pb_backupbuddy::status( 'details',  'Unable to delete excess Rackspace file `' . $buname . '`' );
								$delete_fail_count++;
							}
						}
					}
					
					if ( $delete_fail_count !== 0 ) {
						$this->mail_error( sprintf( __('Rackspace remote limit could not delete %s backups.', 'it-l10n-backupbuddy' ), $delete_fail_count  ) );
					}
				}
			} else {
				pb_backupbuddy::status( 'details',  'No Rackspace file limit to enforce.' );
			}
			// End remote backup limit
			
			return true; // Success.
		} else { // Failed.
			$error_message = 'ERROR #9025: Connected to Rackspace but unable to put file. Verify Rackspace settings included Rackspace permissions.' . "\n\n" . 'http://ithemes.com/codex/page/BackupBuddy:_Error_Codes#9025';
			$this->mail_error( __( $error_message, 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::status( 'details',  $error_message, 'error' );
			
			return false; // Failed.
		}
		
	} // End remote_send_rackspace().
	
	
	
	/*	remote_send_ftp()
	 *	
	 *	Send to this remote destination.
	 *	
	 *	@param		string		$server				FTP server. Ex: ftp.pluginbuddy.com
	 *	@param		string		$username			FTP username.
	 *	@param		string		$password			FTP password.
	 *	@param		string		$path				Remote FTP path.
	 *	@param		bool		$ftps				Whether or not to use FTPs mode.
	 *	@param		string		$file				Full file path to the file to send.
	 *	@param		int			$limit				Maximum number of backups for this site in this directory for this account. No limit if zero 0.
	 *	@param		bool		$send_importbuddy	Whether or not to also send importbuddy.php.
	 *	@return		bool							True on success, else false.
	 */
	function remote_send_ftp( $server, $username, $password, $path, $ftps, $file, $limit = 0, $send_importbuddy = false ) {
		pb_backupbuddy::status( 'details',  'Starting remote send to FTP.' );
		
		$port = '21'; // Default FTP port.
		if ( strstr( $server, ':' ) ) { // Handle custom FTP port.
			$server_params = explode( ':', $server );
			
			$server = $server_params[0];
			$port = $server_params[1];
		}
		
		
		// Connect to server.
		if ( $ftps == '1' ) { // Connect with FTPs.
			if ( function_exists( 'ftp_ssl_connect' ) ) {
				$conn_id = ftp_ssl_connect( $server, $port );
				if ( $conn_id === false ) {
					pb_backupbuddy::status( 'details',  'Unable to connect to FTPS  (check address/FTPS support).', 'error' );
					return false;
				} else {
					pb_backupbuddy::status( 'details',  'Connected to FTPs.' );
				}
			} else {
				pb_backupbuddy::status( 'details',  'Your web server doesnt support FTPS in PHP.', 'error' );
				return false;
			}
		} else { // Connect with FTP (normal).
			if ( function_exists( 'ftp_connect' ) ) {
				$conn_id = ftp_connect( $server, $port );
				if ( $conn_id === false ) {
					pb_backupbuddy::status( 'details',  'ERROR: Unable to connect to FTP (check address).', 'error' );
					return false;
				} else {
					pb_backupbuddy::status( 'details',  'Connected to FTP.' );
				}
			} else {
				pb_backupbuddy::status( 'details',  'Your web server doesnt support FTP in PHP.', 'error' );
				return false;
			}
		}
		
		
		// Log in.
		$login_result = @ftp_login( $conn_id, $username, $password );
		if ( $login_result === false ) {
			$this->mail_error( 'ERROR #9011 ( http://ithemes.com/codex/page/BackupBuddy:_Error_Codes#9011 ).  FTP/FTPs login failed on scheduled FTP.' );
			return false;
		} else {
			pb_backupbuddy::status( 'details',  'Logged in. Sending backup via FTP/FTPs ...' );
		}
		
		
		// Create directory if it does not exist.
		@ftp_mkdir( $conn_id, $path );
		
		
		// Upload file.
		$upload = ftp_put( $conn_id, $path . '/' . basename( $file ), $file, FTP_BINARY );
		if ( $upload === false ) {
			$this->mail_error( 'ERROR #9012 ( http://ithemes.com/codex/page/BackupBuddy:_Error_Codes#9012 ).  FTP/FTPs file upload failed. Check file permissions & disk quota.' );
		} else {
			pb_backupbuddy::status( 'details',  'Done uploading backup file to FTP/FTPs.' );
			
			// Handle sending importbuddy.php.
			if ( $send_importbuddy === true ) {
				pb_backupbuddy::status( 'details', 'Sending importbuddy to FTP based on settings.' );
				$importbuddy_temp = pb_backupbuddy::$options['temp_directory'] . 'importbuddy_' . pb_backupbuddy::random_string( 10 ) . '.php.tmp'; // Full path & filename to temporary importbuddy
				$this->importbuddy( $importbuddy_temp ); // Create temporary importbuddy.
				pb_backupbuddy::status( 'details', 'Generated importbuddy; putting to FTP.' );
				ftp_put( $conn_id, $path . '/' . 'importbuddy.php', $importbuddy_temp, FTP_BINARY );
				
				@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
			}
			
			// Start remote backup limit
			if ( $limit > 0 ) {
				pb_backupbuddy::status( 'details', 'Getting contents of backup directory.' );
				$contents = ftp_nlist( $conn_id, $path );
				
				// Change to path we will be deleting from.
				pb_backupbuddy::status( 'details', 'Entering FTP directory `' . $path . '`.' );
				ftp_chdir( $conn_id, $path );
				
				// Create array of backups
				$bkupprefix = $this->backup_prefix();
				
				$backups = array();
				foreach ( $contents as $backup ) {
					// check if file is backup
					$pos = strpos( $backup, 'backup-' . $bkupprefix . '-' );
					if ( $pos !== FALSE ) {
						array_push( $backups, $backup );
					}
				}
				$results = array_reverse( (array)$backups );
				
				if ( ( count( $results ) ) > $limit ) {
					$delete_fail_count = 0;
					$i = 0;
					foreach( $results as $backup ) {
						$i++;
						if ( $i > $limit ) {
							if ( !ftp_delete( $conn_id, $backup ) ) {
								pb_backupbuddy::status( 'details', 'Unable to delete excess FTP file `' . $backup . '` in path `' . $path . '`.' );
								$delete_fail_count++;
							}
						}
					}
					if ( $delete_fail_count !== 0 ) {
						$this->mail_error( sprintf( __('FTP remote limit could not delete %s backups. Please check and verify file permissions.', 'it-l10n-backupbuddy' ), $delete_fail_count  ) );
					}
				}
			} else {
				pb_backupbuddy::status( 'details',  'No FTP file limit to enforce.' );
			}
			// End remote backup limit
		}
		ftp_close( $conn_id );
		
		return true;
			
	} // End remote_send_ftp().
	
	
	
	/*	remote_send_local()
	 *	
	 *	Send to this local path on same server.
	 *	
	 *	@param		string		$path				Local path on same server.
	 *	@param		string		$file				Full file path to the file to send.
	 *	@param		bool		$send_importbuddy	Whether or not to also send importbuddy.php.
	 *	@return		bool							True on success, else false.
	 */
	function remote_send_local( $path, $file, $send_importbuddy = false ) {
		pb_backupbuddy::status( 'details',  'Starting send to local path.' );
		
		if ( true !== @copy( $file, $path . '/' . basename( $file ) ) ) {
			$this->mail_error( 'Unable to copy file to local path `' . $path . '`. Please verify the directory exists and permissions permit writing.' );
			return false;
		}
		
		if ( $send_importbuddy === true ) {
			pb_backupbuddy::status( 'details', 'Sending importbuddy to local path based on settings.' );
			$importbuddy_temp = pb_backupbuddy::$options['temp_directory'] . 'importbuddy_' . pb_backupbuddy::random_string( 10 ) . '.php.tmp'; // Full path & filename to temporary importbuddy
			$this->importbuddy( $importbuddy_temp ); // Create temporary importbuddy.
			pb_backupbuddy::status( 'details', 'Generated importbuddy; putting to path.' );
			
			if ( true !== @copy( $importbuddy_temp, $path . '/importbuddy.php' ) ) {
				@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
				return false;
			} else {
				@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
			}
		}
		
		return true;
	} // end remote_send_local().
	
	
	
	function remote_send_email( $email, $file, $send_importbuddy = false ) {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			return;
		}
		
		if ( $send_importbuddy === true ) {
			pb_backupbuddy::status( 'details', 'Sending importbuddy to Email based on settings.' );
			$importbuddy_temp = pb_backupbuddy::$options['temp_directory'] . 'importbuddy_' . pb_backupbuddy::random_string( 10 ) . '.php.tmp'; // Full path & filename to temporary importbuddy
			$this->importbuddy( $importbuddy_temp ); // Create temporary importbuddy.
			
			if ( is_array( $file ) ) { // array
				$file[] = $importbuddy_temp;
			} else { // string
				$file = array( $file, $importbuddy_temp );
			}
			
		}
		
		pb_backupbuddy::status( 'details',  'Sending remote email.' );
		$headers = 'From: BackupBuddy <' . get_option('admin_email') . '>' . "\r\n\\";
		wp_mail( $email, 'BackupBuddy Backup', 'BackupBuddy backup for ' . site_url(), $headers, $file );
		pb_backupbuddy::status( 'details',  'Sent remote email.' );
		
		if ( $send_importbuddy === true ) {
			@unlink( $importbuddy_temp ); // Delete temporary importbuddy.
		}
	}
	
	
	
	
	/*	function_name()
	 *	
	 *	function description
	 *	
	 *	@param		string		$type			Valid options: default, migrate
	 *	@param		boolean		$subsite_mode	When in subsite mode only backups for that specific subsite will be listed.
	 *	@return		
	 */
	public function backups_list( $type = 'default', $subsite_mode = false ) {
		
		if ( pb_backupbuddy::_POST( 'bulk_action' ) == 'delete_backup' ) {
			$needs_save = false;
			pb_backupbuddy::verify_nonce( pb_backupbuddy::_POST( '_wpnonce' ) ); // Security check to prevent unauthorized deletions by posting from a remote place.
			$deleted_files = array();
			foreach( pb_backupbuddy::_POST( 'items' ) as $item ) {
				if ( file_exists( pb_backupbuddy::$options['backup_directory'] . $item ) ) {
					if ( @unlink( pb_backupbuddy::$options['backup_directory'] . $item ) === true ) {
						$deleted_files[] = $item;
						
						if ( count( pb_backupbuddy::$options['backups'] ) > 3 ) { // Keep a minimum number of backups in array for stats.
							$this_serial = $this->get_serial_from_file( $item );
							unset( pb_backupbuddy::$options['backups'][$this_serial] );
							$needs_save = true;
						}
					} else {
						pb_backupbuddy::alert( 'Error: Unable to delete backup file `' . $item . '`. Please verify permissions.', true );
					}
				} // End if file exists.
			} // End foreach.
			if ( $needs_save === true ) {
				pb_backupbuddy::save();
			}
			
			pb_backupbuddy::alert( __( 'Deleted backup(s):', 'it-l10n-backupbuddy' ) . ' ' . implode( ', ', $deleted_files ) );
		} // End if deleting backup(s).
		
		
		$backups = array();
		$backup_sort_dates = array();
		$files = glob( pb_backupbuddy::$options['backup_directory'] . 'backup*.zip' );
		if ( is_array( $files ) && !empty( $files ) ) { // For robustness. Without open_basedir the glob() function returns an empty array for no match. With open_basedir in effect the glob() function returns a boolean false for no match.
			
			$backup_prefix = $this->backup_prefix(); // Backup prefix for this site. Used for MS checking that this user can see this backup.
			foreach( $files as $file_id => $file ) {
				
				if ( ( $subsite_mode === true ) && is_multisite() ) { // If a Network and NOT the superadmin must make sure they can only see the specific subsite backups for security purposes.
					
					// Only allow viewing of their own backups.
					if ( !strstr( $file, $backup_prefix ) ) {
						unset( $files[$file_id] ); // Remove this backup from the list. This user does not have access to it.
						continue; // Skip processing to next file.
						echo 'bob';
					}
				}
				
				$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( $file );
				
				
				// Populate integrity data structure in options.
				pb_backupbuddy::$classes['core']->backup_integrity_check( $file );
				
				$pretty_status = array(
					'pass'	=>	'Good',
					'fail'	=>	'<font color="red">Bad</font>',
				);
				$pretty_type = array(
					'full'	=>	'Full',
					'db'	=>	'Database',
				);
				
				//echo '<pre>' . print_r( pb_backupbuddy::$options['backups'][$serial], true ) . '</pre>';
		
				// Calculate time for each step.
				$step_times = array();
				$step_time_details = array();
				$zip_time = 0;
				if ( isset( pb_backupbuddy::$options['backups'][$serial]['steps'] ) ) {
					foreach( pb_backupbuddy::$options['backups'][$serial]['steps'] as $step ) {
						if ( !isset( $step['finish_time'] ) || ( $step['finish_time'] == 0 ) ) {
							$step_times[] = '<span class="description">Unknown</span>';
						} else {
							$step_time = $step['finish_time'] - $step['start_time'];
							$step_times[] = $step_time;
							
							// Pretty step name:
							if ( $step['function'] == 'backup_create_database_dump' ) {
								$step_name = 'Database dump';
							} elseif ( $step['function'] == 'backup_zip_files' ) {
								$step_name = 'Zip archive creation';
							} elseif ( $step['function'] == 'post_backup' ) {
								$step_name = 'Post-backup cleanup';
							} else {
								$step_name = $step['function'];
							}
							
							$step_time_details[] = '<b>' . $step_name . '</b><br>&nbsp;&nbsp;&nbsp;' . $step_time . ' seconds in ' . $step['attempts'] . ' attempts.';
							if ( $step['function'] == 'backup_zip_files' ) {
								$zip_time = $step_time;
							}
						}
					} // End foreach.
				} else { // End if serial in array is set.
					$step_times[] = '<span class="description">Unknown</span>';
				} // End if serial in array is NOT set.
				$step_times = implode( ', ', $step_times );
				
				//echo '<pre>' . print_r( pb_backupbuddy::$options['backups'][$serial], true ) . '</pre>';
				
				// Calculate start and finish.
				if ( isset( pb_backupbuddy::$options['backups'][$serial]['start_time'] ) && isset( pb_backupbuddy::$options['backups'][$serial]['finish_time'] ) && ( pb_backupbuddy::$options['backups'][$serial]['start_time'] >0 ) && ( pb_backupbuddy::$options['backups'][$serial]['finish_time'] > 0 ) ) {
					$start_time = pb_backupbuddy::$options['backups'][$serial]['start_time'];
					$finish_time = pb_backupbuddy::$options['backups'][$serial]['finish_time'];
					$total_time = $finish_time - $start_time;
				} else {
					$total_time = '<span class="description">Unknown</span>';
				}
				
				// Calculate write speed in MB/sec for this backup.
				if ( $zip_time == '0' ) { // Took approx 0 seconds to backup so report this speed.
					if ( !isset( $finish_time ) || ( $finish_time == '0' ) ) {
						$write_speed = '<span class="description">Unknown</span>';
					} else {
						$write_speed = '> ' . pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['backups'][$serial]['integrity']['size'] );
					}
				} else {
					$write_speed = pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['backups'][$serial]['integrity']['size'] / $zip_time );
				}
				
				// Figure out trigger.
				if ( isset( pb_backupbuddy::$options['backups'][$serial]['trigger'] ) ) {
					$trigger = pb_backupbuddy::$options['backups'][$serial]['trigger'];
				} else {
					$trigger = __( 'Unknown', 'it-l10n-backupbuddy' );
				}
				
				// HTML output for stats.
				$statistics = "
					<span style='width: 80px; display: inline-block;'>Total time:</span>{$total_time} secs<br>
					<span style='width: 80px; display: inline-block;'>Step times:</span>{$step_times}<br>
					<span style='width: 80px; display: inline-block;'>Write speed:</span>{$write_speed}/sec
				";
				
				// HTML output for stats details (for tooltip).
				$statistic_details = '<br><br>' . implode( '<br>', $step_time_details ) . '<br><br><i>Trigger: ' . $trigger . '</i>';
				
				// Calculate time ago.
				$time_ago = '<span class="description">' . pb_backupbuddy::$format->time_ago( pb_backupbuddy::$options['backups'][$serial]['integrity']['modified'] ) . ' ago</span>';
				
				// Calculate main row string.
				if ( $type == 'default' ) { // Default backup listing.
					$main_string = '<a href="' . pb_backupbuddy::ajax_url( 'download_archive' ) . '&backupbuddy_backup=' . basename( $file ) . '">' . basename( $file ) . '</a>';
				} elseif ( $type == 'migrate' ) { // Migration backup listing.
					$main_string = '<a class="pb_backupbuddy_hoveraction_migrate" rel="' . basename( $file ) . '" href="' . pb_backupbuddy::page_url() . '&migrate=' . basename( $file ) . '&value=' . basename( $file ) . '">' . basename( $file ) . '</a>';
				} else {
					$main_string = '{Unknown type.}';
				}
				// Add comment to main row string if applicable.
				if ( isset( pb_backupbuddy::$options['backups'][$serial]['integrity']['comment'] ) && ( pb_backupbuddy::$options['backups'][$serial]['integrity']['comment'] !== false ) && ( pb_backupbuddy::$options['backups'][$serial]['integrity']['comment'] !== '' ) ) {
					$main_string .= '<br><span class="description">Note: <span class="pb_backupbuddy_notetext">' . pb_backupbuddy::$options['backups'][$serial]['integrity']['comment'] . '</span></span>';
				}
				
				$backups[basename( $file )] = array(
					array( basename( $file ), $main_string ),
					pb_backupbuddy::$format->date( pb_backupbuddy::$options['backups'][$serial]['integrity']['modified'] ) . '<br>' . $time_ago,
					pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['backups'][$serial]['integrity']['size'] ),
					pb_backupbuddy::$format->prettify( pb_backupbuddy::$options['backups'][$serial]['integrity']['status'], $pretty_status ) . ' ' . pb_backupbuddy::tip( pb_backupbuddy::$options['backups'][$serial]['integrity']['status_details'] . '<br><br>Checked ' . pb_backupbuddy::$format->date( pb_backupbuddy::$options['backups'][$serial]['integrity']['scan_time'] ) . $statistic_details, '', false ) . ' <a href="' . pb_backupbuddy::page_url() . '&reset_integrity=' . $serial  . '" title="' . __('Refresh backup integrity status for this file', 'it-l10n-backupbuddy' ) . '"><img src="' . pb_backupbuddy::plugin_url() . '/images/refresh_gray.gif" style="vertical-align: -1px;"></a>',
					pb_backupbuddy::$format->prettify( pb_backupbuddy::$options['backups'][$serial]['integrity']['detected_type'], $pretty_type ),
					$statistics,
				);
				
				$backup_sort_dates[basename( $file)] = pb_backupbuddy::$options['backups'][$serial]['integrity']['modified'];
				
			} // End foreach().
			
		} // End if.
		
		// Sort backup sizes.
		arsort( $backup_sort_dates );
		// Re-arrange backups based on sort dates.
		$sorted_backups = array();
		foreach( $backup_sort_dates as $backup_file => $backup_sort_date ) {
			$sorted_backups[$backup_file] = $backups[$backup_file];
			unset( $backups[$backup_file] );
		}
		unset( $backups );
		
		
		return $sorted_backups;
		
	}
	
	
	
	// If output file not specified then outputs to browser as download.
	// IMPORTANT: If outputting to browser (no output file) must die() after outputting content if using AJAX. Do not output to browser anything after this function in this case.
	public function importbuddy( $output_file = '' ) {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			echo 'Access denied in demo mode.';
			return;
		}
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			pb_backupbuddy::load();
		}
		$output = file_get_contents( pb_backupbuddy::plugin_path() . '/_importbuddy/_importbuddy.php' );
		if ( pb_backupbuddy::$options['importbuddy_pass_hash'] != '' ) {
			$output = preg_replace('/#PASSWORD#/', pb_backupbuddy::$options['importbuddy_pass_hash'], $output, 1 ); // Only replaces first instance.
		}
		$output = preg_replace('/#VERSION#/', pb_backupbuddy::settings( 'version' ), $output, 1 ); // Only replaces first instance.
		
		// PACK IMPORTBUDDY
		$_packdata = array( // NO TRAILING OR PRECEEDING SLASHES!
			
			'_importbuddy/importbuddy'			=>		'importbuddy',
			'classes/_migrate_database.php'		=>		'importbuddy/classes/_migrate_database.php',
			'classes/core.php'					=>		'importbuddy/classes/core.php',
			'classes/import.php'				=>		'importbuddy/classes/import.php',
			
			'images/working.gif'				=>		'importbuddy/images/working.gif',
			'images/bullet_go.png'				=>		'importbuddy/images/bullet_go.png',
			
			
			'lib/dbreplace'						=>		'importbuddy/lib/dbreplace',
			'lib/dbimport'						=>		'importbuddy/lib/dbimport',
			'lib/commandbuddy'					=>		'importbuddy/lib/commandbuddy',
			'lib/zipbuddy'						=>		'importbuddy/lib/zipbuddy',
			'lib/mysqlbuddy'					=>		'importbuddy/lib/mysqlbuddy',
			'lib/textreplacebuddy'				=>		'importbuddy/lib/textreplacebuddy',
			
			'pluginbuddy'						=>		'importbuddy/pluginbuddy',
			
			'controllers/pages/server_info'		=>		'importbuddy/controllers/pages/server_info',
			'controllers/pages/server_info.php'	=>		'importbuddy/controllers/pages/server_info.php',
			//'classes/_get_backup_dat.php'		=>		'importbuddy/classes/_get_backup_dat.php',
			
		);
		
		$output .= "\n<?php /*\n###PACKDATA,BEGIN\n";
		foreach( $_packdata as $pack_source => $pack_destination ) {
			$pack_source = '/' . $pack_source;
			if ( is_dir( pb_backupbuddy::plugin_path() . $pack_source ) ) {
				$files = pb_backupbuddy::$filesystem->deepglob( pb_backupbuddy::plugin_path() . $pack_source );
			} else {
				$files = array( pb_backupbuddy::plugin_path() . $pack_source );
			}
			foreach( $files as $file ) {
				if ( is_file( $file ) ) {
					$source = str_replace( pb_backupbuddy::plugin_path(), '', $file );
					$destination = $pack_destination . substr( $source, strlen( $pack_source ) );
					$output .= "###PACKDATA,FILE_START,{$source},{$destination}\n";
					$output .= base64_encode( file_get_contents( $file ) );
					$output .= "\n";
					$output .= "###PACKDATA,FILE_END,{$source},{$destination}\n";
				}
			}
		}
		$output .= "###PACKDATA,END\n*/";
		
		if ( $output_file == '' ) { // No file so output to browser.
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/plain; name=importbuddy.php' );
			header( 'Content-Disposition: attachment; filename=importbuddy.php' );
			header( 'Expires: 0' );
			header( 'Content-Length: ' . strlen( $output ) );
			
			flush();
			echo $output;
			flush();
			
			// BE SURE TO die() AFTER THIS AND NOT OUTPUT TO BROWSER!
		} else { // Write to file.
			file_put_contents( $output_file, $output );
		}
				
	} // End importbuddy().
	
	
	
	// TODO: RepairBuddy is not yet converted into new framework so just using pre-BB3.0 version for now.
	public function repairbuddy( $output_file = '' ) {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			echo 'Access denied in demo mode.';
			return;
		}
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			pb_backupbuddy::load();
		}
		$output = file_get_contents( pb_backupbuddy::plugin_path() . '/_repairbuddy.php' );
		if ( pb_backupbuddy::$options['repairbuddy_pass_hash'] != '' ) {
			$output = preg_replace('/#PASSWORD#/', pb_backupbuddy::$options['repairbuddy_pass_hash'], $output, 1 ); // Only replaces first instance.
		}
		$output = preg_replace('/#VERSION#/', pb_backupbuddy::settings( 'version' ), $output, 1 ); // Only replaces first instance.
		
		
		if ( $output_file == '' ) { // No file so output to browser.
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/plain; name=repairbuddy.php' );
			header( 'Content-Disposition: attachment; filename=repairbuddy.php' );
			header( 'Expires: 0' );
			header( 'Content-Length: ' . strlen( $output ) );
			
			flush();
			echo $output;
			flush();
			
			// BE SURE TO die() AFTER THIS AND NOT OUTPUT TO BROWSER!
		} else { // Write to file.
			file_put_contents( $output_file, $output );
		}
				
	} // End repairbuddy().
	
	
	
	function pretty_destination_type( $type ) {
		if ( $type == 'rackspace' ) {
			return 'Rackspace';
		} elseif ( $type == 'email' ) {
			return 'Email';
		} elseif ( $type == 's3' ) {
			return 'Amazon S3';
		} elseif ( $type == 'ftp' ) {
			return 'FTP';
		} elseif ( $type == 'dropbox' ) {
			return 'Dropbox';
		} else {
			return $type;
		}
	} // End pretty_destination_type().
	
	
	
	// $max_depth	int		Maximum depth of tree to display.  Npte that deeper depths are still traversed for size calculations.
	function build_icicle( $dir, $base, $icicle_json, $max_depth = 10, $depth_count = 0, $is_root = true ) {
		$bg_color = '005282';
		
		$depth_count++;
		$bg_color = dechex( hexdec( $bg_color ) - ( $depth_count * 15 ) );
		
		$icicle_json = '{' . "\n";
		
		$dir_name = $dir;
		$dir_name = str_replace( ABSPATH, '', $dir );
		$dir_name = str_replace( '\\', '/', $dir_name );
		
		$dir_size = 0;
		$sub = opendir( $dir );
		$has_children = false;
		while( $file = readdir( $sub ) ) {
			if ( ( $file == '.' ) || ( $file == '..' ) ) {
				continue; // Next loop.
			} elseif ( is_dir( $dir . '/' . $file ) ) {
				
				$dir_array = '';
				$response = $this->build_icicle( $dir . '/' . $file, $base, $dir_array, $max_depth, $depth_count, false );
				if ( ( $max_depth-1 > 0 ) || ( $max_depth == -1 ) ) { // Only adds to the visual tree if depth isnt exceeded.
					if ( $max_depth > 0 ) {
						$max_depth = $max_depth - 1;
					}
					
					if ( $has_children === false ) { // first loop add children section
						$icicle_json .= '"children": [' . "\n";
					} else {
						$icicle_json .= ',';
					}
					$icicle_json .= $response[0];
					
					$has_children = true;
				}
				$dir_size += $response[1];
				unset( $response );
				unset( $file );
				
				
			} else {
				$stats = stat( $dir . '/' . $file );
				$dir_size += $stats['size'];
				unset( $file );
			}
		}
		closedir( $sub );
		unset( $sub );
		
		if ( $has_children === true ) {
			$icicle_json .= ' ]' . "\n";
		}
		
		if ( $has_children === true ) {
			$icicle_json .= ',';
		}
		
		$icicle_json .= '"id": "node_' . str_replace( '/', ':', $dir_name ) . ': ^' . str_replace( ' ', '~', pb_backupbuddy::$format->file_size( $dir_size ) ) . '"' . "\n";
		
		$dir_name = str_replace( '/', '', strrchr( $dir_name, '/' ) );
		if ( $dir_name == '' ) { // Set root to be /.
			$dir_name = '/';
		}
		$icicle_json .= ', "name": "' . $dir_name . ' (' . pb_backupbuddy::$format->file_size( $dir_size ) . ')"' . "\n";
		
		$icicle_json .= ',"data": { "$dim": ' . ( $dir_size + 10 ) . ', "$color": "#' . str_pad( $bg_color, 6, '0', STR_PAD_LEFT ) . '" }' . "\n";
		$icicle_json .= '}';
		
		if ( $is_root !== true ) {
			//$icicle_json .= ',x';
		}
		
		return array( $icicle_json, $dir_size );
	} // End build_icicle().
	
	
	// return array of tests and their results.
	public function preflight_check() {
		$tests = array();
		
		
		// LOOPBACKS TEST.
		if ( ( $loopback_response = $this->loopback_test() ) === true ) {
			$success = true;
			$message = '';
		} else { // failed
			$success = false;
			if ( defined( 'ALTERNATE_WP_CRON' ) && ( ALTERNATE_WP_CRON == true ) ) {
				$message = __('Running in Alternate WordPress Cron mode. HTTP Loopback Connections are not enabled on this server but you have overridden this in the wp-config.php file (this is a good thing).', 'it-l10n-backupbuddy' ) . ' <a href="http://ithemes.com/codex/page/BackupBuddy:_Frequent_Support_Issues#HTTP_Loopback_Connections_Disabled" target="_new">' . __('Additional Information Here', 'it-l10n-backupbuddy' ) . '</a>.';
			} else {
				$message = __('HTTP Loopback Connections are not enabled on this server. You may encounter stalled or significantly delayed backups.', 'it-l10n-backupbuddy' ) . ' <a href="http://ithemes.com/codex/page/BackupBuddy:_Frequent_Support_Issues#HTTP_Loopback_Connections_Disabled" target="_new">' . __('Click for instructions on how to resolve this issue.', 'it-l10n-backupbuddy' ) . '</a>';
			}
		}
		$tests[] = array(
			'test'		=>	'loopbacks',
			'success'	=>	$success,
			'message'	=>	$message,
		);
		
		
		// WORDPRESS IN SUBDIRECTORIES TEST.
		$wordpress_locations = $this->get_wordpress_locations();
		if ( count( $wordpress_locations ) > 0 ) {
			$success = false;
			$message = __( 'WordPress may have been detected in one or more subdirectories. Backing up multiple instances of WordPress may result in server timeouts due to increased backup time. You may exclude WordPress directories via the Settings page. Detected non-excluded locations:', 'it-l10n-backupbuddy' ) . ' ' . implode( ', ', $wordpress_locations );
		} else {
			$success = true;
			$message = '';
		}
		$tests[] = array(
			'test'		=>	'wordpress_subdirectories',
			'success'	=>	$success,
			'message'	=>	$message,
		);
		
		
		// Log file directory writable for status logging.
		$status_directory = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/';
		if ( !is_writable( $status_directory ) ) {
			$success = false;
			$message = 'The status log file directory `' . $status_directory . '` is not writable. Please verify permissions before creating a backup. Backup status information will be unavailable until this is resolved.';
		} else {
			$success = true;
			$message = '';
		}
		$tests[] = array(
			'test'		=>	'status_directory_writable',
			'success'	=>	$success,
			'message'	=>	$message,
		);
		
		
		// CHECK ZIP AVAILABILITY.
		require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
		
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		}
		
		if ( !in_array( 'exec', pb_backupbuddy::$classes['zipbuddy']->_zip_methods ) ) {
			$success = false;
			$message =  __('Your server does not support command line ZIP. Backups will be performed in compatibility mode.', 'it-l10n-backupbuddy' ) 
						    . __('Directory/file exclusion is not available in this mode so even existing backups will be backed up.', 'it-l10n-backupbuddy' ) 
						    . ' '
						    . __('You may encounter stalled or significantly delayed backups.', 'it-l10n-backupbuddy' ) 
						    .  '<a href="http://ithemes.com/codex/page/BackupBuddy:_Frequent_Support_Issues#Compatibility_Mode" target="_new">' 
						    . ' ' . __('Click for instructions on how to resolve this issue.', 'it-l10n-backupbuddy' ) 
						    . '</a>';
		} else { // Success.
			$success = true;
			$message = '';
		}
		$tests[] = array(
			'test'		=>	'zip_methods',
			'success'	=>	$success,
			'message'	=>	$message,
		);
		
		// Show warning if recent backups reports it is not complete yet. (3min is recent)
		if ( isset( pb_backupbuddy::$options['backups'][pb_backupbuddy::$options['last_backup_serial']]['updated_time'] ) && ( time() - pb_backupbuddy::$options['backups'][pb_backupbuddy::$options['last_backup_serial']]['updated_time'] < 180 ) ) { // Been less than 3min since last backup.
			if ( !empty( pb_backupbuddy::$options['backups'][pb_backupbuddy::$options['last_backup_serial']]['steps'] ) ) {
				
				$found_unfinished = false;
				foreach( pb_backupbuddy::$options['backups'][pb_backupbuddy::$options['last_backup_serial']]['steps'] as $step ) {
					if ( $step['finish_time'] == '0' ) { // Found an unfinished step.
						$found_unfinished = true;
						break;
					}
				}
				
				if ( $found_unfinished === true ) {
					$tests[] = array(
						'test'		=>	'recent_backup',
						'success'	=>	false,
						'message'	=>	__('A backup was recently started and reports unfinished steps. You should not begin another backup unless you are sure the previous backup has completed or failed.', 'it-l10n-backupbuddy' ) .
										' Last updated: ' . pb_backupbuddy::$format->date( pb_backupbuddy::$options['backups'][pb_backupbuddy::$options['last_backup_serial']]['updated_time'] ) . '; '.
										' Serial: ' . pb_backupbuddy::$options['last_backup_serial']
						,
					);
				}
			}
		}
				
		return $tests;
		
	} // End preflight_check().
	
	
	
	
	// returns true on success, error message otherwise.
	/*	loopback_test()
	 *	
	 *	Connects back to same site via AJAX call to an AJAX slug that has NOT been registered.
	 *	WordPress AJAX returns a -1 (or 0 in newer version?) for these. Also not logged into
	 *	admin when connecting back. Checks to see if body contains -1 / 0. If loopbacks are not
	 *	enabled then will fail connecting or do something else.
	 *	
	 *	
	 *	@param		
	 *	@return		boolean		True on success, string error message otherwise.
	 */
	function loopback_test() {
		$loopback_url = admin_url('admin-ajax.php');
		$response = wp_remote_get(
			$loopback_url,
			array(
				'method' => 'GET',
				'timeout' => 5, // 5 second delay. A loopback should be very fast.
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => null,
				'cookies' => array()
			)
		);
		
		if( is_wp_error( $response ) ) {
			return 'Error: ' . $response->get_error_message();
		} else {
			if ( ( $response['body'] == '-1' ) || ( $response['body'] == '0' ) ) {
				return true;
			} else {
				return 'A loopback seemed to occur but the value `' . $response['body'] . '` was not correct.';
			}
		}
	}
	
	
	
	
	// Returns array of subdirectories that contain WordPress.
	function get_wordpress_locations() {
		$wordpress_locations = array();
		
		$files = glob( ABSPATH . '*/' );
		if ( !is_array( $files ) || empty( $files ) ) {
			$files = array();
		}
		
		foreach( $files as $file ) {
			if ( file_exists( $file . 'wp-config.php' ) ) {
				$wordpress_locations[]  = rtrim( '/' . str_replace( ABSPATH, '', $file ), '/\\' );
			}
		}
		
		// Remove any excluded directories from showing up in this.
		$directory_exclusions = $this->get_directory_exclusions();		
		$wordpress_locations = array_diff( $wordpress_locations, $directory_exclusions );
		
		return $wordpress_locations;
	}
	
	
	/*	test_s3()
	 *	
	 *	Test provided S3 connection details.
	 *	
	 *	@param		boolean			$ssl		True to use SSL, false no SSL.
	 *	@return		boolean/string				Boolean true on success, else a string of the error message.
	 */
	function test_s3( $accesskey, $secretkey, $bucket, $directory = '', $ssl ) {
		if ( empty( $accesskey ) || empty( $secretkey ) || empty( $bucket ) ) {
			return __('Missing one or more required fields.', 'it-l10n-backupbuddy' );
		}
		
		$bucket_requirements = __( "Your bucket name must meet certain criteria. It must fulfill the following: \n\n Characters may be lowercase letters, numbers, periods (.), and dashes (-). \n Must start with a number or letter. \n Must be between 3 and 63 characters long. \n Must not be formatted as an IP address (e.g., 192.168.5.4). \n Should be between 3 and 63 characters long. \n Should not end with a dash. \n Cannot contain two, adjacent periods. \n Cannot contain dashes next to periods.", 'it-l10n-backupbuddy' );
		if ( preg_match( "/^[a-z0-9][a-z0-9\-\.\_]*(?<!-)$/i", $bucket ) == 0 ) { // Starts with a-z or 0-9; middle is a-z, 0-9, -, or .; cannot end in a dash.
			return __( 'Your bucket failed one or more things in the check: Starts with a-z or 0-9; middle is a-z, 0-9, -, or .; cannot end in a dash.', 'it-l10n-backupbuddy' ) . ' ' . $bucket_requirements;
		}
		if ( ( strlen( $bucket ) < 3 ) || ( strlen( $bucket ) > 63 ) ) { // Must be between 3 and 63 characters long
			return __( 'Your bucket must be between 3 and 63 characters long.', 'it-l10n-backupbuddy' ) . ' ' . $bucket_requirements;
		}
		if ( ( strstr( $bucket, '.-' ) !== false ) || ( strstr( $bucket, '-.' ) !== false ) || ( strstr( $bucket, '..' ) !== false ) ) { // Bucket names cannot contain dashes next to periods (e.g., "my-.bucket.com" and "my.-bucket" are invalid)
			return __( 'Your bucket contains a period next to a dash.', 'it-l10n-backupbuddy' ) . ' ' . $bucket_requirements;
		}
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/s3/s3.php' );
		$s3 = new pb_backupbuddy_S3( $accesskey, $secretkey, $ssl );
		
		
		if ( $s3->getBucketLocation( $bucket ) === false ) { // Easy way to see if bucket already exists.
			$s3->putBucket( $bucket, pb_backupbuddy_S3::ACL_PRIVATE );
		}
		
		if ( !empty( $directory ) ) {
			$directory = $directory . '/';
		}
		if ( $s3->putObject( __('Upload test for BackupBuddy for Amazon S3', 'it-l10n-backupbuddy' ), $bucket, $directory . 'backupbuddy.txt', pb_backupbuddy_S3::ACL_PRIVATE) ) {
			// Success... just delete temp test file later...
		} else {
			return __('Unable to upload. Verify your keys, bucket name, and account permissions.', 'it-l10n-backupbuddy' );
		}
		
		if ( ! pb_backupbuddy_S3::deleteObject( $bucket, $directory . 'backupbuddy.txt' ) ) {
			return __('Partial success. Could not delete temp file.', 'it-l10n-backupbuddy' );
		}
		
		return true; // Success!
	}
	
	function test_rackspace( $rs_username, $rs_api_key, $rs_container, $rs_server ) {
		if ( empty( $rs_username ) || empty( $rs_api_key ) || empty( $rs_container ) ) {
			return __('Missing one or more required fields.', 'it-l10n-backupbuddy' );
		}
		require_once(pb_backupbuddy::plugin_path() . '/lib/rackspace/cloudfiles.php');
		$auth = new CF_Authentication( $rs_username, $rs_api_key, NULL, $rs_server );
		if ( !$auth->authenticate() ) {
			return __('Unable to authenticate. Verify your username/api key.', 'it-l10n-backupbuddy' );
		}

		$conn = new CF_Connection( $auth );

		// Set container
		$container = @$conn->get_container( $rs_container ); // returns object on success, string error message on failure.
		if ( !is_object( $container ) ) {
			return __( 'There was a problem selecting the container:', 'it-l10n-backupbuddy' ) . ' ' . $container;
		}
		// Create test file
		$testbackup = @$container->create_object( 'backupbuddytest.txt' );
		if ( !$testbackup->load_from_filename( pb_backupbuddy::plugin_path() . '/readme.txt') ) {
			return __('BackupBuddy was not able to write the test file.', 'it-l10n-backupbuddy' );
		}
		
		// Delete test file from Rackspace
		if ( !$container->delete_object( 'backupbuddytest.txt' ) ) {
			return __('Unable to delete file from container.', 'it-l10n-backupbuddy' );
		}
		
		return true; // Success
	}

	function test_ftp( $server, $username, $password, $path, $type = 'ftp' ) {
		if ( ( $server == '' ) || ( $username == '' ) || ( $password == '' ) ) {
			return __('Missing required input.', 'it-l10n-backupbuddy' );
		}
		
		$port = '21';
		if ( strstr( $server, ':' ) ) {
			$server_params = explode( ':', $server );
			
			$server = $server_params[0];
			$port = $server_params[1];
		}
		
		if ( $type == 'ftp' ) {
			$conn_id = @ftp_connect( $server, $port );
			if ( $conn_id === false ) {
				return __('Unable to connect to FTP address `' . $server . '` on port `' . $port . '`.', 'it-l10n-backupbuddy' );
			}
		} else {
			if ( function_exists( 'ftp_ssl_connect' ) ) {
				$conn_id = @ftp_ssl_connect( $server, $port );
				if ( $conn_id === false ) {
					return __('Destination server does not support FTPS?', 'it-l10n-backupbuddy' );
				}
			} else {
				return __('Your web server doesnt support FTPS.', 'it-l10n-backupbuddy' );
			}
		}
		
		$login_result = @ftp_login( $conn_id, $username, $password );
		
		if ( ( !$conn_id ) || ( !$login_result ) ) {
		   return __('Unable to login. Bad user/pass.', 'it-l10n-backupbuddy' );
		} else {
			
			// Create directory if it does not exist.
			@ftp_mkdir( $conn_id, $path );

			$tmp = tmpfile(); // Write tempory text file to stream.
			fwrite( $tmp, 'Upload test for BackupBuddy' );
			rewind( $tmp );
			$upload = @ftp_fput( $conn_id, $path . '/backupbuddy.txt', $tmp, FTP_BINARY );
			fclose( $tmp );
			
			if ( !$upload ) {
				@ftp_delete( $conn_id, $path . '/backupbuddy.txt' ); // Just in case it partionally made file. This has happened oddly.
				return __('Failure uploading. Check path & permissions.', 'it-l10n-backupbuddy' );
			} else {
				ftp_delete( $conn_id, $path . '/backupbuddy.txt' );
			}
		}
		@ftp_close($conn_id);
		
		return true; // Success if we got this far.
	}
	
	
	
	// TODO: coming soon.
	// Run through potential orphaned files, data structures, etc caused by failed backups and clean things up.
	// Also verifies anti-directory browsing files exists, etc.
	function periodic_cleanup( $backup_age_limit = 43200, $die_on_fail = true ) {
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			$this->load();
		}
		
		
		// TODO: Check for orphaned .gz files in root from PCLZip.
		// TODO: Cleanup any orphaned temp ZIP creation directories under the backups directory. wp-content/uploads/backupbuddy_backups/temp_zip_XXSERIALXX/
		
		
		// Cleanup backup itegrity portion of array.
		$this->trim_backups_integrity_stats();
		
		
		// Cleanup logs in pb_backupbuddy dirctory.
		$log_directory = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/';
		$files = glob( $log_directory . '*.txt' );
		if ( is_array( $files ) && !empty( $files ) ) { // For robustness. Without open_basedir the glob() function returns an empty array for no match. With open_basedir in effect the glob() function returns a boolean false for no match.
			foreach( $files as $file ) {
				$file_stats = stat( $file );
				if ( ( time() - $file_stats['mtime'] ) > $backup_age_limit ) { // If older than 12 hours, delete the log.
					@unlink( $file );
				}
			}
		}
		
		
		// Cleanup excess backup stats.
		if ( count( pb_backupbuddy::$options['backups'] ) > 3 ) { // Keep a minimum number of backups in array for stats.
			$number_backups = count( pb_backupbuddy::$options['backups'] );
			$kept_loop_count = 0;
			
			$needs_save = false;
			foreach( pb_backupbuddy::$options['backups'] as $backup_serial => $backup ) {
				if ( ( $number_backups - $kept_loop_count ) > 3 ) {
					if ( isset( $backup['archive_file'] ) && !file_exists( $backup['archive_file'] ) ) {
						unset( pb_backupbuddy::$options['backups'][$backup_serial] );
						$needs_save = true;
					} else {
						$kept_loop_count++;
					}
				}
			}
			if ( $needs_save === true ) {
				//echo 'saved';
				pb_backupbuddy::save();
			}
		}
		
		
		// Cleanup any temporary local destinations.
		foreach( pb_backupbuddy::$options['remote_destinations'] as $destination_id => $destination ) {
			if ( ( $destination['type'] == 'local' ) && ( $destination['temporary'] === true ) ) { // If local and temporary.
				if ( ( time() - $destination['created'] ) > $backup_age_limit ) { // Older than 12 hours; clear out!
					pb_backupbuddy::status( 'details', 'Cleaned up stale local destination `' . $destination_id . '`.' );
					unset( pb_backupbuddy::$options['remote_destinations'][$destination_id] );
					pb_backupbuddy::save();
				}
			}
		}
		
		
		// Cleanup excess remote sending stats.
		$this->trim_remote_send_stats();
		
		
		// Check for orphaned backups in the data structure that havent been updates in 12+ hours & cleanup after them.
		foreach( (array)pb_backupbuddy::$options['backups'] as $backup_serial => $backup ) {
			if ( isset( $backup['updated_time'] ) ) {
				if ( ( time() - $backup['updated_time'] ) > $backup_age_limit ) { // If more than 12 hours has passed...
					pb_backupbuddy::status( 'details', 'Cleaned up stale backup `' . $backup_serial . '`.' );
					$this->final_cleanup( $backup_serial );
				}
			}
		}
		
		
		// Verify existance of anti-directory browsing files in backup directory.
		pb_backupbuddy::anti_directory_browsing( pb_backupbuddy::$options['backup_directory'], $die_on_fail );
		
		
		// Verify existance of anti-directory browsing files in status log directory.
		$status_directory = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/';
		pb_backupbuddy::anti_directory_browsing( $status_directory, $die_on_fail );
		
		
		// Handle high security mode archives directory .htaccess system. If high security backup directory mode: Make sure backup archives are NOT downloadable by default publicly. This is only lifted for ~8 seconds during a backup download for security. Overwrites any existing .htaccess in this location.
		if ( pb_backupbuddy::$options['lock_archives_directory'] == '0' ) { // Normal security mode. Put normal .htaccess.
			pb_backupbuddy::status( 'details', 'Removing .htaccess high security mode for backups directory. Normal mode .htaccess to be added next.' );
			// Remove high security .htaccess.
			if ( file_exists( pb_backupbuddy::$options['backup_directory'] . '.htaccess' ) ) {
				$unlink_status = @unlink( pb_backupbuddy::$options['backup_directory'] . '.htaccess' );
				if ( $unlink_status === false ) {
					pb_backupbuddy::alert( 'Error #844594. Unable to temporarily remove .htaccess security protection on archives directory to allow downloading. Please verify permissions of the BackupBuddy archives directory or manually download via FTP.' );
				}
			}
			
			// Place normal .htaccess.
			pb_backupbuddy::anti_directory_browsing( pb_backupbuddy::$options['backup_directory'], $die_on_fail );
		
		} else { // High security mode. Make sure high security .htaccess in place.
			pb_backupbuddy::status( 'details', 'Adding .htaccess high security mode for backups directory.' );
			$htaccess_creation_status = @file_put_contents( pb_backupbuddy::$options['backup_directory'] . '.htaccess', 'deny from all' );
			if ( $htaccess_creation_status === false ) {
				pb_backupbuddy::alert( 'Error #344894545. Security Warning! Unable to create security file (.htaccess) in backups archive directory. This file prevents unauthorized downloading of backups should someone be able to guess the backup location and filenames. This is unlikely but for best security should be in place. Please verify permissions on the backups directory.' );
			}
			
		}
		
		
		// Verify existance of anti-directory browsing files in temporary directory.
		pb_backupbuddy::anti_directory_browsing( pb_backupbuddy::$options['temp_directory'], $die_on_fail );
		
		
		// Remove any copy of importbuddy.php in root.
		if ( file_exists( ABSPATH . 'importbuddy.php' ) ) {
			pb_backupbuddy::status( 'details', 'Unlinked importbuddy.php in root of site.' );
			unlink( ABSPATH . 'importbuddy.php' );
		}
		
		
		// Remove any copy of importbuddy directory in root.
		if ( file_exists( ABSPATH . 'importbuddy/' ) ) {
			pb_backupbuddy::status( 'details', 'Unlinked importbuddy directory recursively in root of site.' );
			pb_backupbuddy::$filesystem->unlink_recursive( ABSPATH . 'importbuddy/' );
		}
		
		
	} // End periodic_cleanup().
	
	
	
	public function final_cleanup( $serial ) {
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			pb_backupbuddy::load();
		}
		
		pb_backupbuddy::status( 'details', 'cron_final_cleanup started' );
		
		// Delete temporary data directory.
		if ( isset( pb_backupbuddy::$options['backups'][$serial]['temp_directory'] ) && file_exists( pb_backupbuddy::$options['backups'][$serial]['temp_directory'] ) ) {
			pb_backupbuddy::$filesystem->unlink_recursive( pb_backupbuddy::$options['backups'][$serial]['temp_directory'] );
		}
		
		// Delete temporary zip directory.
		if ( isset( pb_backupbuddy::$options['backups'][$serial]['temporary_zip_directory'] ) && file_exists( pb_backupbuddy::$options['backups'][$serial]['temporary_zip_directory'] ) ) {
			pb_backupbuddy::$filesystem->unlink_recursive( pb_backupbuddy::$options['backups'][$serial]['temporary_zip_directory'] );
		}
		
		// Delete status log text file.
		if ( file_exists( pb_backupbuddy::$options['backup_directory'] . 'temp_status_' . $serial . '.txt' ) ) {
			unlink( pb_backupbuddy::$options['backup_directory'] . 'temp_status_' . $serial. '.txt' );
		}
				
	} // End final_cleanup().
	
	
	
	/*	trim_remote_send_stats()
	 *	
	 *	Handles trimming the number of remote sends to the most recent ones.
	 *	
	 *	@return		null
	 */
	public function trim_remote_send_stats() {
		
		$limit = 5; // Maximum number of remote sends to keep track of.
		
		// Return if limit not yet met.
		if ( count( pb_backupbuddy::$options['remote_sends'] ) <= $limit ) {
			return;
		}
		
		// Uses the negative offset of array_slice() to grab the last X number of items from array.
		pb_backupbuddy::$options['remote_sends'] = array_slice( pb_backupbuddy::$options['remote_sends'], ( $limit * -1 ) );
		pb_backupbuddy::save();
		
	} // End trim_remote_send_stats().
	
	
	/*	trim_backups_integrity_stats()
	 *	
	 *	Handles trimming the number of backup integrity items in the data structure. Trims to all backups left or 10, whichever is more.
	 *	
	 *	@param		
	 *	@return		
	 */
	public function trim_backups_integrity_stats() {
		pb_backupbuddy::status( 'details', 'Trimming backup integrity stats.' );
		
		$minimum = 10; // Minimum number of backups' integrity to keep track of.
		
		if ( !isset( pb_backupbuddy::$options['backups'] ) ) { // No integrity checks yet.
			return;
		}
		
		// Put newest backups first.
		$existing_backups = array_reverse( pb_backupbuddy::$options['backups'] );
		
		// Remove any backup integrity stats for deleted backups. Will re-add from temp array if we drop under the minimum.
		foreach( $existing_backups as $backup_serial => $existing_backup ) {
			if ( !isset( $existing_backup['archive_file'] ) || ( !file_exists( $existing_backup['archive_file'] ) ) ) {
				unset( pb_backupbuddy::$options['backups'][$backup_serial] ); // File gone so erase from options. Will re-add if we go under our minimum.
			}
		} // End foreach.
		
		
		// If dropped under the minimum try to add back in some to get enough sample points.
		if ( count( pb_backupbuddy::$options['backups'] ) < $minimum ) {
			foreach( $existing_backups as $backup_serial => $existing_backup ) {
				if ( !isset( pb_backupbuddy::$options['backups'][$backup_serial] ) ) {
					pb_backupbuddy::$options['backups'][$backup_serial] = $existing_backup; // Add item.
				}
				// If hit minimum then stop looping to add.
				if ( count( pb_backupbuddy::$options['backups'] ) >= $minimum ) {
					break;
				}
			}
			// Put array back in normal order.
			pb_backupbuddy::$options['backups'] = array_reverse( pb_backupbuddy::$options['backups'] );
			pb_backupbuddy::save();
		} else { // Still have enough stats. Save.
			pb_backupbuddy::$options['backups'] = array_reverse( pb_backupbuddy::$options['backups'] );
			pb_backupbuddy::save();
		}
		
	} // End trim_backups_integrity_stats().
	
	
	
	/*	get_site_size()
	 *	
	 *	Returns an array with the site size and the site size sans exclusions. Saves updates stats in options.
	 *	
	 *	@return		array		Index 0: site size; Index 1: site size sans excluded files/dirs.
	 */
	public function get_site_size() {
		$exclusions = pb_backupbuddy_core::get_directory_exclusions();
		$dir_array = array();
		$result = pb_backupbuddy::$filesystem->dir_size_map( ABSPATH, ABSPATH, $exclusions, $dir_array );
		unset( $dir_array ); // Free this large chunk of memory.
		
		$total_size = pb_backupbuddy::$options['stats']['site_size'] = $result[0];
		$total_size_excluded = pb_backupbuddy::$options['stats']['site_size_excluded'] = $result[1];
		pb_backupbuddy::$options['stats']['site_size_updated'] = time();
		pb_backupbuddy::save();
		
		return array( $total_size, $total_size_excluded );
	} // End get_site_size().
	
	
	
	/*	get_database_size()
	 *	
	 *	Return array of database size, database sans exclusions.
	 *	
	 *	@return		array			Index 0: db size, Index 1: db size sans exclusions.
	 */
	public function get_database_size() {
		global $wpdb;
		$prefix = $wpdb->prefix;
		$prefix_length = strlen( $wpdb->prefix );
		
		$additional_includes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_includes'] );
		array_walk( $additional_includes, create_function('&$val', '$val = trim($val);')); 
		$additional_excludes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_excludes'] );
		array_walk( $additional_excludes, create_function('&$val', '$val = trim($val);')); 
		
		$total_size = 0;
		$total_size_with_exclusions = 0;
		$result = mysql_query("SHOW TABLE STATUS");
		while( $rs = mysql_fetch_array( $result ) ) {
			$excluded = true; // Default.
			
			// TABLE STATUS.
			$resultb = mysql_query("CHECK TABLE `{$rs['Name']}`");
			while( $rsb = mysql_fetch_array( $resultb ) ) {
				if ( $rsb['Msg_type'] == 'status' ) {
					$status = $rsb['Msg_text'];
				}
			}
			mysql_free_result( $resultb );
			
			// TABLE SIZE.
			$size = ( $rs['Data_length'] + $rs['Index_length'] );
			$total_size += $size;
			
			
			// HANDLE EXCLUSIONS.
			if ( pb_backupbuddy::$options['backup_nonwp_tables'] == 0 ) { // Only matching prefix.
				if ( ( substr( $rs['Name'], 0, $prefix_length ) == $prefix ) OR ( in_array( $rs['Name'], $additional_includes ) ) ) {
					if ( !in_array( $rs['Name'], $additional_excludes ) ) {
						$total_size_with_exclusions += $size;
						$excluded = false;
					}
				}
			} else { // All tables.
				if ( !in_array( $rs['Name'], $additional_excludes ) ) {
					$total_size_with_exclusions += $size;
					$excluded = false;
				}
			}
			
		}
		
		pb_backupbuddy::$options['stats']['db_size'] = $total_size;
		pb_backupbuddy::$options['stats']['db_size_excluded'] = $total_size_with_exclusions;
		pb_backupbuddy::$options['stats']['db_size_updated'] = time();
		pb_backupbuddy::save();
		
		mysql_free_result( $result );
		
		return array( $total_size, $total_size_with_exclusions );
	} // End get_database_size().
	
	
	
	/* Doesnt work?
	public function error_handler( $error_number, $error_string, $error_file, $error_line ) {
		pb_backupbuddy::status( 'error', "PHP error caught. Error #`{$error_number}`; Description: `{$error_string}`; File: `{$error_file}`; Line: `{$error_line}`."  );
		return true;
	}
	*/
}



?>