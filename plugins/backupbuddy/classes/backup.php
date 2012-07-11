<?php
/*	pb_backupbuddy_backup class
 *	
 *	Handles the actual backup procedures.
 *	
 *	USED BY:
 *
 *	1) Full & DB backups
 *	2) Multisite backups & exports
 *
 */
class pb_backupbuddy_backup {
	
	private $_errors = array();					// No longer used?
	private $_status_logging_started = false;	// Marked true once anything has been status logged during this process. Used by status().
	
	
	
	/*	__construct()
	 *	
	 *	Default contructor. Initialized core and zipbuddy classes.
	 *	
	 *	@return		null
	 */
	function __construct() {
		
		// Load core if it has not been instantiated yet.
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		// Load zipbuddy if it has not been instantiated yet.
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		}
		
	} // End __construct().
	
	
	
	/*	start_backup_process()
	 *	
	 *	Initializes the entire backup process.
	 *	
	 *	@param		string		$type				Backup type. Valid values: db, full, export.
	 *	@param		string		$trigger			What triggered this backup. Valid values: scheduled, manual.
	 *	@param		array		$pre_backup			Array of functions to prepend to the backup steps array.
	 *	@param		array		$post_backup		Array of functions to append to the backup steps array. Ie. sending to remote destination.
	 *	@param		string		$schedule_title		Title name of schedule. Used for tracking what triggered this in logging. For debugging.
	 *	@param		string		$serial_override	If provided then this serial will be used instead of an auto-generated one.
	 *	@param		array		$export_plugins		For use in export backup type. List of plugins to export.
	 *	@return		boolean							True on success; false otherwise.
	 */
	function start_backup_process( $type, $trigger = 'manual', $pre_backup = array(), $post_backup = array(), $schedule_title = '', $serial_override = '', $export_plugins = array() ) {
		
		if ( $serial_override != '' ) {
			$serial = $serial_override;
		} else {
			$serial = pb_backupbuddy::random_string( 10 );
		}
		pb_backupbuddy::set_status_serial( $serial ); // Default logging serial.
		
		pb_backupbuddy::status( 'details', __( 'Starting backup process function.', 'it-l10n-backupbuddy' ) );
		pb_backupbuddy::status( 'details', __('Peak memory usage', 'it-l10n-backupbuddy' ) . ': ' . round( memory_get_peak_usage() / 1048576, 3 ) . ' MB' );
		
		if ( $this->pre_backup( $serial, $type, $trigger, $pre_backup, $post_backup, $schedule_title, $export_plugins ) === false ) {
			return false;
		}
		
		if ( ( $trigger == 'scheduled' ) && ( pb_backupbuddy::$options['email_notify_scheduled_start'] != '' ) ) {
			pb_backupbuddy::status( 'details', __('Sending scheduled backup start email notification if applicable.', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::$classes['core']->mail_notify_scheduled( 'start', __('Scheduled backup', 'it-l10n-backupbuddy' ) . ' (' . $this->_backup['schedule_title'] . ') has begun.' );
		}
		
		if ( ( pb_backupbuddy::$options['backup_mode'] == '2' )  || ( $trigger == 'scheduled' ) ) { // Modern mode with crons.
			
			pb_backupbuddy::status( 'message', 'Running in modern backup mode based on settings. Mode value: `' . pb_backupbuddy::$options['backup_mode'] . '`. Trigger: `' . $trigger . '`.' );
			
			// If using alternate cron on a manually triggered backup then skip running the cron on this pageload to avoid header already sent warnings.
			if ( ( $trigger == 'manual' ) && defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ) {
				$this->cron_next_step( false );
			} else {
				$this->cron_next_step( true );
			}
			
		} else { // Classic mode; everything runs in this single PHP page load.
			
			pb_backupbuddy::status( 'message', 'Running in classic backup mode based on settings.' );
			
			$this->process_backup( $this->_backup['serial'], $trigger );
			
		}
		
		return true;
		
	} // End start_backup_process().
	
	
	
	/*	pre_backup()
	 *	
	 *	Set up the backup data structure containing steps, set up temp directories, etc.
	 *	
	 *	@param		string		$type				Backup type. Valid values: db, full.
	 *	@param		string		$trigger			What triggered this backup. Valid values: scheduled, manual.
	 *	@param		array		$pre_backup			Array of functions to prepend to the backup steps array.
	 *	@param		array		$post_backup		Array of functions to append to the backup steps array. Ie. sending to remote destination.
	 *	@param		string		$schedule_title		Title name of schedule. Used for tracking what triggered this in logging. For debugging.
	 *	@param		array		$export_plugins		For use in export backup type. List of plugins to export.
	 *	@return		boolean							True on success; false otherwise.
	 */
	function pre_backup( $serial, $type, $trigger, $pre_backup = array(), $post_backup = array(), $schedule_title = '', $export_plugins = array() ) {
		
		// Log some status information.
		pb_backupbuddy::status( 'details', __( 'Performing pre-backup procedures.', 'it-l10n-backupbuddy' ) );
		if ( $type == 'full' ) {
			pb_backupbuddy::status( 'message', __( 'Full backup mode.', 'it-l10n-backupbuddy' ) );
		} elseif ( $type == 'db' ) {
			pb_backupbuddy::status( 'message', __( 'Database only backup mode.', 'it-l10n-backupbuddy' ) );
		} elseif ( $type == 'export' ) {
			pb_backupbuddy::status( 'message', __( 'Multisite Site Export mode.', 'it-l10n-backupbuddy' ) );
		} else {
			pb_backupbuddy::status( 'error', __( 'Unknown backup mode.', 'it-l10n-backupbuddy' ) );
		}
		
		
		// Delete all backup archives if this troubleshooting option is enabled.
		if ( pb_backupbuddy::$options['delete_archives_pre_backup'] == '1' ) {
			pb_backupbuddy::status( 'message', 'Deleting all existing backups prior to backup as configured on the settings page.' );
			$file_list = glob( pb_backupbuddy::$options['backup_directory'] . 'backup*.zip' );
			if ( is_array( $file_list ) && !empty( $file_list ) ) {
				foreach( $file_list as $file ) {
					if ( @unlink( $file ) === true ) {
						pb_backupbuddy::status( 'details', 'Deleted backup archive `' . basename( $file ) . '` based on settings to delete all backups.' );
					} else {
						pb_backupbuddy::status( 'details', 'Unable to delete backup archive `' . basename( $file ) . '` based on settings to delete all backups. Verify permissions.' );
					}
				}
			}
		}
				
		// Generate unique serial ID.
		pb_backupbuddy::status( 'details', 'Backup serial generated: `' . $serial . '`.' );
		$this->_backup = &pb_backupbuddy::$options['backups'][$serial]; // Set reference.
		
		
		// Cleanup internal stats.
		pb_backupbuddy::status( 'details', 'Resetting statistics for last backup time and number of edits since last backup.' );
		pb_backupbuddy::$options['last_backup'] = time(); // Reset time since last backup.
		pb_backupbuddy::$options['edits_since_last'] = 0; // Reset edit stats for notifying user of how many posts/pages edited since last backup happened.
		pb_backupbuddy::$options['last_backup_serial'] = $serial;
		
		
		// Prepare some values for setting up the backup data.
		$siteurl_stripped = pb_backupbuddy::$classes['core']->backup_prefix();
		if ( pb_backupbuddy::$options['force_compatibility'] == '1' ) {
			$force_compatibility = true;
		} else {
			$force_compatibility = false;
		}
		
		
		// Set up the backup data.
		$this->_backup = array(
			'serial'				=>		$serial,										// Unique identifier.
			'backup_mode'			=>		pb_backupbuddy::$options['backup_mode'],		// Tells whether modern or classic mode.
			'type'					=>		$type,											// db, manual, or export.
			'start_time'			=>		time(),											// When backup started. Now.
			'finish_time'			=>		0,
			'updated_time'			=>		time(),											// When backup last updated. Subsequent steps update this.
			'status'				=>		array(),										// TODO: what goes in this?
			'schedule_title'		=>		$schedule_title,								// Title of the schedule that made this backup happen (if applicable).
			'backup_directory'		=>		pb_backupbuddy::$options['backup_directory'],	// Directory backups stored in.
			'archive_file'			=>		pb_backupbuddy::$options['backup_directory'] . 'backup-' . $siteurl_stripped . '-' . str_replace( '-', '_', date( 'Y-m-d' ) ) . '-' . $serial . '.zip',			// Unique backup ZIP filename.
			'trigger'				=>		$trigger,										// How backup was triggered: manual or scheduled.
			'force_compatibility'	=>		$force_compatibility,							// Boolean on whether compatibily zip mode was forced or not.
			'steps'					=>		array(),										// Backup steps to perform. Set next in this code.
			'integrity'				=>		array(),										// Used later for tests and stats post backup.
			'temp_directory'		=>		'',												// Temp directory to store SQL and DAT file. Differs for exports. Defined in a moment...
			'backup_root'			=>		'',												// Where to start zipping from. Usually root of site. Defined in a moment...
			'export_plugins'		=>		array(),										// Plugins to export during MS export of a subsite.
			'additional_table_includes'	=>	array(),
			'additional_table_excludes'	=>	array(),
		);
		
		
		// Figure out paths.
		if ( $this->_backup['type'] == 'full' ) {
			$this->_backup['temp_directory'] = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . $serial . '/';
			$this->_backup['backup_root'] = ABSPATH; // ABSPATH contains trailing slash.
		} elseif ( $this->_backup['type'] == 'db' ) {
			$this->_backup['temp_directory'] = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . $serial . '/';
			$this->_backup['backup_root'] = $this->_backup['temp_directory'];
		} elseif ( $this->_backup['type'] == 'export' ) {
			// WordPress unzips into wordpress subdirectory by default so must include that in path.
			$this->_backup['temp_directory'] = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . $serial . '/wordpress/wp-content/uploads/backupbuddy_temp/' . $serial . '/'; // We store temp data for export within the temporary WordPress installation within the temp directory. A bit confusing; sorry about that.
			$this->_backup['backup_root'] = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . $serial . '/wordpress/';
		} else {
			pb_backupbuddy::status( 'error', __('Backup FAILED. Unknown backup type.', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::status( 'action', 'halt_script' ); // Halt JS on page.
		}
		
		
		// Plugins to export (only for MS exports).
		if ( count( $export_plugins ) > 0 ) {
			$this->_backup['export_plugins'] = $export_plugins;
		}
		
		
		// Calculate additional database table inclusion/exclusion.
		$additional_includes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_includes'] );
		array_walk( $additional_includes, create_function('&$val', '$val = trim($val);')); 
		$this->_backup['additional_table_includes'] = $additional_includes;
		$additional_excludes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_excludes'] );
		array_walk( $additional_excludes, create_function('&$val', '$val = trim($val);'));
		$this->_backup['additional_table_excludes'] = $additional_excludes;
		
		
		
		/********* Begin setting up steps array. *********/
		
		if ( $type == 'export' ) {
			pb_backupbuddy::status( 'details', 'Setting up export-specific steps.' );
			
			$this->_backup['steps'][] = array(
				'function'		=>	'ms_download_extract_wordpress',
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
			$this->_backup['steps'][] = array(
				'function'		=>	'ms_create_wp_config',
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
			$this->_backup['steps'][] = array(
				'function'		=>	'ms_copy_plugins',
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
			$this->_backup['steps'][] = array(
				'function'		=>	'ms_copy_themes',
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
			$this->_backup['steps'][] = array(
				'function'		=>	'ms_copy_media',
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
			$this->_backup['steps'][] = array(
				'function'		=>	'ms_copy_users_table', // Create temp user and usermeta tables.
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
		}
		
		if ( pb_backupbuddy::$options['skip_database_dump'] != '1' ) { // Backup database if not skipping.
			$this->_backup['steps'][] = array(
				'function'		=>	'backup_create_database_dump',
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
		} else {
			pb_backupbuddy::status( 'message', __( 'Skipping database dump based on advanced options.', 'it-l10n-backupbuddy' ) );
		}
		
		$this->_backup['steps'][] = array(
			'function'		=>	'backup_zip_files',
			'args'			=>	array(),
			'start_time'	=>	0,
			'finish_time'	=>	0,
			'attempts'		=>	0,
		);
		
		if ( $type == 'export' ) {
			$this->_backup['steps'][] = array( // Multisite export specific cleanup.
				'function'		=>	'ms_cleanup', // Removes temp user and usermeta tables.
				'args'			=>	array(),
				'start_time'	=>	0,
				'finish_time'	=>	0,
				'attempts'		=>	0,
			);
		}
		
		$this->_backup['steps'][] = array(
			'function'		=>	'post_backup',
			'args'			=>	array(),
			'start_time'	=>	0,
			'finish_time'	=>	0,
			'attempts'		=>	0,
		);
		
		// Prepend and append pre backup and post backup steps.				
		$this->_backup['steps'] = array_merge( $pre_backup, $this->_backup['steps'], $post_backup );
		
		/********* End setting up steps array. *********/
		
		
		
		/********* Begin directory creation and security. *********/
		
		pb_backupbuddy::anti_directory_browsing( $this->_backup['backup_directory'] );
		
		// Prepare temporary directory for holding SQL and data file.
		if ( !file_exists( $this->_backup['temp_directory'] ) ) {
			if ( pb_backupbuddy::$filesystem->mkdir( $this->_backup['temp_directory'] ) === false ) {
				pb_backupbuddy::status( 'details', sprintf(__('Error #9002: Unable to create temporary storage directory (%s).', 'it-l10n-backupbuddy' ), $this->_backup['temp_directory']) );
				$this->error( 'Unable to create temporary storage directory (' . $this->_backup['temp_directory'] . ')', '9002' );
				return false;
			}
		}
		if ( !is_writable( $this->_backup['temp_directory'] ) ) {
			pb_backupbuddy::status( 'details', sprintf( __('Error #9015: Temp data directory is not writable. Check your permissions. (%s).', 'it-l10n-backupbuddy' ), $this->_backup['temp_directory'] ) );
			$this->error( 'Temp data directory is not writable. Check your permissions. (' . $this->_backup['temp_directory'] . ')', '9015' );
			return false;
		}
		pb_backupbuddy::anti_directory_browsing( ABSPATH . 'wp-content/uploads/backupbuddy_temp/' );
		
		// Prepare temporary directory for holding ZIP file while it is being generated.
		$this->_backup['temporary_zip_directory'] = pb_backupbuddy::$options['backup_directory'] . 'temp_zip_' . $this->_backup['serial'] . '/';
		if ( !file_exists( $this->_backup['temporary_zip_directory'] ) ) {
			if ( pb_backupbuddy::$filesystem->mkdir( $this->_backup['temporary_zip_directory'] ) === false ) {
				pb_backupbuddy::status( 'details', sprintf( __('Error #9002: Unable to create temporary ZIP storage directory (%s).', 'it-l10n-backupbuddy' ), $this->_backup['temporary_zip_directory'] ) );
				$this->error( 'Unable to create temporary ZIP storage directory (' . $this->_backup['temporary_zip_directory'] . ')', '9002' );
				return false;
			}
		}
		if ( !is_writable( $this->_backup['temporary_zip_directory'] ) ) {
			pb_backupbuddy::status( 'details', sprintf( __('Error #9015: Temp data directory is not writable. Check your permissions. (%s).', 'it-l10n-backupbuddy' ), $this->_backup['temporary_zip_directory'] ) );
			$this->error( 'Temp data directory is not writable. Check your permissions. (' . $this->_backup['temporary_zip_directory'] . ')', '9015' );
			return false;
		}
		
		/********* End directory creation and security *********/
		
		
		// Schedule cleanup of temporary files and such for XX hours in the future just in case everything goes wrong we dont leave junk too long.
		wp_schedule_single_event( ( time() + ( 48 * 60 * 60 ) ), pb_backupbuddy::cron_tag( 'final_cleanup' ), array( $serial ) );
		
		
		// Generate backup DAT (data) file containing details about the backup.
		if ( $this->backup_create_dat_file( $trigger ) !== true ) {
			pb_backupbuddy::status( 'details', __('Problem creating DAT file.', 'it-l10n-backupbuddy' ) );
			return false;
		}
		
		
		// Save all of this.
		pb_backupbuddy::save();
		
		
		pb_backupbuddy::status( 'details', __('Finished pre-backup procedures.', 'it-l10n-backupbuddy' ) );
		pb_backupbuddy::status( 'action', 'finish_settings' );
		
		
		return true;
		
	} // End pre_backup().
	
	
	
	/*	process_backup()
	 *	
	 *	Process and run the next backup step.
	 *	
	 *	@param		string		$serial		Unique backup identifier.
	 *	@param		string		$trigger	What triggered this processing: manual or scheduled.
	 *	@return		boolean					True on success, false otherwise.
	 */
	function process_backup( $serial, $trigger = 'manual' ) {
		pb_backupbuddy::status( 'details', 'Running process_backup() for serial `' . $serial . '`.' );
		
		// Assign reference to backup data structure for this backup.
		$this->_backup = &pb_backupbuddy::$options['backups'][$serial];
		
		$found_next_step = false;
		foreach( $this->_backup['steps'] as &$step ) { // Loop through steps finding first step that has not run.
			
			if ( ( $step['start_time'] != 0 ) && ( $step['finish_time'] == 0 ) ) { // A step has begun but has not finished. This should not happen but the WP cron is funky. Wait a while before continuing.
				
				$step['attempts']++; // Increment this as an attempt.
				pb_backupbuddy::save();
				
				if ( $step['attempts'] < 6 ) {
					$wait_time = 60 * $step['attempts']; // Each attempt adds a minute of wait time.
					pb_backupbuddy::status( 'message', 'A scheduled step attempted to run before the previous step completed. Waiting `' . $wait_time . '` seconds before continuining for it to catch up. Attempt number `' . $step['attempts'] . '`.' );
					$this->cron_next_step( false, $wait_time );
					return false;
				} else { // Too many attempts to run this step.
					pb_backupbuddy::status( 'error', 'A scheduled step attempted to run before the previous step completed. After several attempts (`' . $step['attempts'] . '`) of failure BackupBuddy has given up. Halting backup.' );
					return false;
				}
				
				break;
				
			} elseif ( $step['start_time'] == 0 ) { // Step that has not started yet.
				
				$found_next_step = true;
				$step['start_time'] = time(); // Set this step time to now.
				$step['attempts']++; // Increment this as an attempt.
				pb_backupbuddy::save();
				
				pb_backupbuddy::status( 'details', 'Found next step to run: `' . $step['function'] . '`.' );
				
				break;
				
			} else { // Last case: Finished. Skip.
				// Do nothing for completed steps.
			}
			
		} // End foreach().
		if ( $found_next_step === false ) { // No more steps to perform; return.
			return false;
		}
		pb_backupbuddy::save();
		
		
		pb_backupbuddy::status( 'details', __('Peak memory usage', 'it-l10n-backupbuddy' ) . ': ' . round( memory_get_peak_usage() / 1048576, 3 ) . ' MB' );		
		
		/********* Begin Running Step Function **********/
		if ( method_exists( $this, $step['function'] ) ) {
			pb_backupbuddy::status( 'details', 'Starting function `' . $step['function'] . '` now (' . time() . ').' );
			
			$response = call_user_func_array( array( &$this, $step['function'] ), $step['args'] );
		} else {
			pb_backupbuddy::status( 'error', __( 'Error #82783745: Invalid function `' . $step['function'] . '`' ) );
			$response = false;
		}
		/********* End Running Step Function **********/
		
		
		if ( $response === false ) { // Function finished but reported failure.
			
			pb_backupbuddy::status( 'error', 'Failed function `' . $step['function'] . '`. Backup terminated.' );
			pb_backupbuddy::status( 'details', __('Peak memory usage', 'it-l10n-backupbuddy' ) . ': ' . round( memory_get_peak_usage() / 1048576, 3 ) . ' MB' );
			pb_backupbuddy::status( 'action', 'halt_script' ); // Halt JS on page.
			
			if ( pb_backupbuddy::$options['log_level'] == '3' ) {
				$debugging = "\n\n\n\n\n\nDebugging information sent due to error logging set to high debugging mode: \n\n" . pb_backupbuddy::random_string( 10 ) . base64_encode( print_r( debug_backtrace(), true ) ) . "\n\n";
			} else {
				$debugging = '';
			}
			pb_backupbuddy::$classes['core']->mail_error( 'One or more backup steps reported a failure. Backup failure running function `' . $step['function'] . '` with the arguments `' . implode( ',', $step['args'] ) . '` with backup serial `' . $serial . '`. Please run a manual backup of the same type to verify backups are working properly.' . $debugging );
			
			return false;
			
		} else { // Function finished successfully.
			$step['finish_time'] = time();
			$this->_backup['updated_time'] = time();
			pb_backupbuddy::save();
			
			pb_backupbuddy::status( 'details', sprintf( __('Finished function `%s`.', 'it-l10n-backupbuddy' ), $step['function'] ) );
			pb_backupbuddy::status( 'details', __('Peak memory usage', 'it-l10n-backupbuddy' ) . ': ' . round( memory_get_peak_usage() / 1048576, 3 ) . ' MB' );
			
			$found_another_step = false;
			foreach( $this->_backup['steps'] as $next_step ) { // Loop through each step and see if any have not started yet.
				if ( $next_step['start_time'] == 0 ) { // Another unstarted step exists. Schedule it.
					$found_another_step = true;
					if ( ( pb_backupbuddy::$options['backup_mode'] == '2' )  || ( $trigger == 'scheduled' ) ) {
						$this->cron_next_step();
					} else { // classic mode
						$this->process_backup( $this->_backup['serial'], $trigger );
					}
					
					break;
				}
			} // End foreach().
			
			if ( $found_another_step == false ) {
				pb_backupbuddy::status( 'details', 'Finished backup at ' . pb_backupbuddy::$format->date( time() ) . ' (' . time() . ').' );
				$this->_backup['finish_time'] = time();
				pb_backupbuddy::save();
			}
			
			return true;
		}
		
		
	} // End process_backup().
	
	
	
	/*	cron_next_step()
	 *	
	 *	Schedule the next step into the cron. Defaults to scheduling to happen _NOW_. Automatically opens a loopback to trigger cron in another process by default.
	 *	
	 *	@param		boolean		$spawn_cron			Whether or not to to spawn a loopback to run the cron. If using an offset this most likely should be false. Default: true
	 *	@param		int			$future_offset		Seconds in the future for this process to run. Most likely set $spawn_cron false if using an offset. Default: 0
	 *	@return		null
	 */
	function cron_next_step( $spawn_cron = true, $future_offset = 0 ) {
		
		pb_backupbuddy::status( 'details', 'Scheduling Cron for `' . $this->_backup['serial'] . '`.' );
		
		// Check to see that the database is still around.
		// We have the option of kicking it to get it going again but I'm not sure that we want to do that here since we may have undetected failured in the backup at this point.
		// We should probably kick the DB earlier on a case by case basis to make sure we are safe.
		global $wpdb;
		if ( @mysql_ping( $wpdb->dbh ) === false ) { // Still connected to database.
			pb_backupbuddy::status( 'error', __( 'ERROR #9027: The mySQL server went away and was unavailable for scheduling the next cron step. This is almost always caused by mySQL running out of memory. The backup integrity can no longer be guaranteed so the backup has been halted.' ) );
			return false;
		}
		
		wp_schedule_single_event( ( time() + $future_offset ), pb_backupbuddy::cron_tag( 'process_backup' ), array( $this->_backup['serial'] ) );
		if ( $spawn_cron === true ) {
			spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
		}
		update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
		
		return;
		
	} // End cron_next_step().
	
	
	
	/*	backup_create_dat_file()
	 *	
	 *	Generates backupbuddy_dat.php within the temporary directory containing the
	 *	random serial in its name. This file contains a serialized array that has been
	 *	XOR encrypted for security.  The XOR key is backupbuddy_SERIAL where SERIAL
	 *	is the randomized set of characters in the ZIP filename. This file contains
	 *	various information about the source site.
	 *	
	 *	@param		string			$trigger			What triggered this backup. Valid values: scheduled, manual.
	 *	@return		boolean			true on success making dat file; else false
	 */
	function backup_create_dat_file( $trigger ) {
		
		pb_backupbuddy::status( 'details', __( 'Creating DAT (data) file snapshotting site & backup information.', 'it-l10n-backupbuddy' ) );
		
		global $wpdb, $current_blog;
		
		$is_multisite = $is_multisite_export = false; //$from_multisite is from a site within a network
		$upload_url_rewrite = $upload_url = '';
		if ( ( is_multisite() && ( $trigger == 'scheduled' ) ) || (is_multisite() && is_network_admin() ) ) { // MS Network Export IF ( in a network and triggered by a schedule ) OR ( in a network and logged in as network admin)
			$is_multisite = true;
		} elseif ( is_multisite() ) { // MS Export (individual site)
			$is_multisite_export = true;
			$uploads = wp_upload_dir();
			$upload_url_rewrite = site_url( str_replace( ABSPATH, '', $uploads[ 'basedir' ] ) ); // URL we rewrite uploads to. REAL direct url.
			$upload_url = $uploads[ 'baseurl' ]; // Pretty virtual path to uploads directory.
		}
		
		// Handle wp-config.php file in a parent directory.
		if ( $this->_backup['type'] == 'full' ) {
			$wp_config_parent = false;
			if ( file_exists( ABSPATH . 'wp-config.php' ) ) { // wp-config in normal place.
				pb_backupbuddy::status( 'details', 'wp-config.php found in normal location.' );
			} else { // wp-config not in normal place.
				pb_backupbuddy::status( 'message', 'wp-config.php not found in normal location; checking parent directory.' );
				if ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) { // Config in parent.
					$wp_config_parent = true;
					pb_backupbuddy::status( 'message', 'wp-config.php found in parent directory. Copying wp-config.php to temporary location for backing up.' );
					$this->_backup['wp-config_in_parent'] = true;
					
					copy( dirname( ABSPATH ) . '/wp-config.php', $this->_backup['temp_directory'] . 'wp-config.php' );
				} else {
					pb_backupbuddy::status( 'error', 'wp-config.php not found in normal location NOR parent directory. This will result in an incomplete backup which will be marked as bad.' );
				}
			}
		} else {
			$wp_config_parent = false;
		}
		
		
		$dat_content = array(
			
			// Backup Info.
			'backupbuddy_version'		=> pb_backupbuddy::settings( 'version' ),
			'backup_time'				=> $this->_backup['start_time'],
			'backup_type'				=> $this->_backup['type'],
			'serial'					=> $this->_backup['serial'],
			'trigger'					=> $trigger,													// What triggered this backup. Valid values: scheduled, manual.
			'wp-config_in_parent'		=> $wp_config_parent,											// Whether or not the wp-config.php file is in one parent directory up. If in parent directory it will be copied into the temp serial directory along with the .sql and DAT file. On restore we will NOT place in a parent directory due to potential permission issues, etc. It will be moved into the normal location. Value set to true later in this function if applicable.
			
			// WordPress Info.
			'abspath'					=> ABSPATH,
			'siteurl'					=> site_url(),
			'homeurl'					=> home_url(),
			'blogname'					=> get_option( 'blogname' ),
			'blogdescription'			=> get_option( 'blogdescription' ),
			
			// Database Info.
			'db_prefix'					=> $wpdb->prefix,
			'db_name'					=> DB_NAME,
			'db_user'					=> DB_USER,
			'db_server'					=> DB_HOST,
			'db_password'				=> DB_PASSWORD,
			'db_exclusions'				=> implode( ',', explode( "\n", pb_backupbuddy::$options['mysqldump_additional_excludes'] ) ),
			
			// Multisite Info.
			'is_multisite' 				=> $is_multisite,												// Full Network backup?
			'is_multisite_export' 		=> $is_multisite_export,										// Subsite backup (export)?
			'domain'					=> is_object( $current_blog ) ? $current_blog->domain : '',		// Ex: bob.com
			'path'						=> is_object( $current_blog ) ? $current_blog->path : '',		// Ex: /wordpress/
			'upload_url' 				=> $upload_url,  												// Pretty URL.
			'upload_url_rewrite' 		=> $upload_url_rewrite,											// Real existing URL that the pretty URL will be rewritten to.
			
		); // End setting $dat_content.
		
		
		// If currently using SSL or forcing admin SSL then we will check the hardcoded defined URL to make sure it matches.
		if ( is_ssl() OR ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN == true ) ) {
			$dat_content['siteurl'] = get_option('siteurl');
			pb_backupbuddy::status( 'details', __('Compensating for SSL in siteurl.', 'it-l10n-backupbuddy' ) );
		}
		
		
		// Serialize .dat file array.
		$dat_content = base64_encode( serialize( $dat_content ) );
		
		// Write data to the dat file.
		$dat_file = $this->_backup['temp_directory'] . 'backupbuddy_dat.php';
		if ( false === ( $file_handle = fopen( $dat_file, 'w' ) ) ) {
			pb_backupbuddy::status( 'details', sprintf( __('Error #9017: Temp data file is not creatable/writable. Check your permissions. (%s)', 'it-l10n-backupbuddy' ), $dat_file  ) );
			pb_backupbuddy::status( 'error', 'Temp data file is not creatable/writable. Check your permissions. (' . $dat_file . ')', '9017' );
			return false;
		}
		fwrite( $file_handle, "<?php die('Access Denied.'); ?>\n" . $dat_content );
		fclose( $file_handle );
		
		pb_backupbuddy::status( 'details', __('Finished creating DAT (data) file.', 'it-l10n-backupbuddy' ) );
		
		return true;
		
	} // End backup_create_dat_file().
	
	
	
	/*	backup_create_database_dump()
	 *	
	 *	Prepares configuration and passes to the mysqlbuddy library to handle backing up the database.
	 *	Automatically handles falling back to compatibility modes.
	 *	
	 *	@return		boolean				True on success; false otherwise.
	 */
	function backup_create_database_dump() {
		pb_backupbuddy::status( 'action', 'start_database' );
		pb_backupbuddy::status( 'message', __('Starting database backup process.', 'it-l10n-backupbuddy' ) );
		
		// Default tables to backup.
		if ( pb_backupbuddy::$options['backup_nonwp_tables'] == '1' ) { // Backup all tables.
			$base_dump_mode = 'all';
		} else { // Only backup matching prefix.
			$base_dump_mode = 'prefix';
		}
		
		if ( pb_backupbuddy::$options['force_mysqldump_compatibility'] == '1' ) {
			pb_backupbuddy::status( 'message', 'Forcing database dump compatibility mode based on settings. Use PHP-based dump mode only.' );
			$force_methods = array( 'php' ); // Force php mode only.
		} else {
			pb_backupbuddy::status( 'message', 'Using auto-detected database dump method(s) based on settings.' );
			$force_methods = array(); // Default, auto-detect.
		}
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/mysqlbuddy/mysqlbuddy.php' );
		global $wpdb;
		pb_backupbuddy::$classes['mysqlbuddy'] = new pb_backupbuddy_mysqlbuddy( DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, $wpdb->prefix, $force_methods ); // $database_host, $database_name, $database_user, $database_pass, $old_prefix, $force_method = array()
		
		$result = pb_backupbuddy::$classes['mysqlbuddy']->dump( $this->_backup['temp_directory'], $base_dump_mode, $this->_backup['additional_table_includes'], $this->_backup['additional_table_excludes'] );
		
		
		return $result;

	} // End backup_create_database_dump().
	
	
	
	/*	backup_zip_files()
	 *	
	 *	Create ZIP file containing everything.
	 *	
	 *	@return		boolean			True on success; false otherwise.
	 */
	function backup_zip_files() {
		
		pb_backupbuddy::status( 'action', 'start_files' );
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		// Calculate user-defined directory exclusions AND additional internal BB exclusions such as backup archive directory and temp directory.
		$exclusions = pb_backupbuddy_core::get_directory_exclusions();
		
		// Use compression?
		if ( pb_backupbuddy::$options['compression'] == '1' ) {
			$compression = true;
		} else {
			$compression = false;
		}
		
		pb_backupbuddy::status( 'details', 'Backup root: `' . $this->_backup['backup_root'] . '`.' );
		
		// Additional logging?
		if ( pb_backupbuddy::$options['log_level'] == 3 ) { // Also shows debugging consolse.
			$quiet_response = false;
		} else {
			$quiet_response = true;
		}
		
		
		// MAKE THE ZIP!
		if ( pb_backupbuddy::$classes['zipbuddy']->add_directory_to_zip( $this->_backup['archive_file'], $this->_backup['backup_root'], $compression, $exclusions, $this->_backup['temporary_zip_directory'], $this->_backup['force_compatibility'], $quiet_response ) === true ) {
			pb_backupbuddy::status( 'message', __('Backup ZIP file successfully created.', 'it-l10n-backupbuddy' ) );
			if ( chmod( $this->_backup['archive_file'], 0644) ) {
				pb_backupbuddy::status( 'details', __('Chmod of ZIP file to 0644 succeeded.', 'it-l10n-backupbuddy' ) );
			} else {
				pb_backupbuddy::status( 'details', __('Chmod of ZIP file to 0644 failed.', 'it-l10n-backupbuddy' ) );
			}
		} else {
			pb_backupbuddy::status( 'error', __('Backup FAILED. Unable to successfully generate ZIP archive. Error #3382.', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::status( 'error', __('Error #3382 help: http://ithemes.com/codex/page/BackupBuddy:_Error_Codes#3382', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::status( 'action', 'halt_script' ); // Halt JS on page.
			return false;
		}
		
		
		// Need to make sure the database connection is active. Sometimes it goes away during long bouts doing other things -- sigh.
		// This is not essential so use include and not require (suppress any warning)
		@include_once( pb_backupbuddy::plugin_path() . '/lib/wpdbutils/wpdbutils.php' );
		if ( class_exists( 'pluginbuddy_wpdbutils' ) ) {
			// This is the database object we want to use
			global $wpdb;
			
			// Get our helper object and let it use us to output status messages
			$dbhelper = new pluginbuddy_wpdbutils( $wpdb );
			
			// If we cannot kick the database into life then signal the error and return false which will stop the backup
			// Otherwise all is ok and we can just fall through and let the function return true
			if ( !$dbhelper->kick() ) {
				pb_backupbuddy::status( 'error', __('Backup FAILED. Backup file produced but Database Server has gone away, unable to schedule next backup step', 'it-l10n-backupbuddy' ) );
				return false;
			}
		} else {
			// Utils not available so cannot verify database connection status - just notify
			pb_backupbuddy::status( 'details', __('Database Server connection status unverified.', 'it-l10n-backupbuddy' ) );
		}
		
		return true;
		
	} // End backup_zip_files().
	
	
	
	/*	trim_old_archives()
	 *	
	 *	Get rid of excess archives based on user-defined parameters.
	 *	
	 *	@param		
	 *	@return		
	 */
	function trim_old_archives() {
		
		pb_backupbuddy::status( 'details', __('Trimming old archives (if needed).', 'it-l10n-backupbuddy' ) );
		
		$summed_size = 0;
		
		$file_list = glob( pb_backupbuddy::$options['backup_directory'] . 'backup*.zip' );
		if ( is_array( $file_list ) && !empty( $file_list ) ) {
			foreach( (array) $file_list as $file ) {
				$file_stats = stat( $file );
				$modified_time = $file_stats['ctime'];
				$filename = str_replace( pb_backupbuddy::$options['backup_directory'], '', $file ); // Just the file name.
				$files[$modified_time] = array(
													'filename'				=>		$filename,
													'size'					=>		$file_stats['size'],
													'modified'				=>		$modified_time,
												);
				$summed_size += ( $file_stats['size'] / 1048576 ); // MB
			}
		}
		unset( $file_list );
		if ( empty( $files ) ) { // return if no archives (nothing else to do).
			return true;
		} else {
			krsort( $files );
		}
		
		// Limit by number of archives if set. Deletes oldest archives over this limit.
		if ( ( pb_backupbuddy::$options['archive_limit'] > 0 ) && ( count( $files ) ) > pb_backupbuddy::$options['archive_limit'] ) {
			// Need to trim.
			$i = 0;
			foreach( $files as $file ) {
				$i++;
				if ( $i > pb_backupbuddy::$options['archive_limit'] ) {
					pb_backupbuddy::status( 'details', sprintf( __('Deleting old archive `%s` due as it causes archives to exceed total number allowed.', 'it-l10n-backupbuddy' ), $file['filename'] ) );
					unlink( pb_backupbuddy::$options['backup_directory'] . $file['filename'] );
				}
			}
		}
		
		// Limit by size of archives, oldest first if set.
		$files = array_reverse( $files, true ); // Reversed so we delete oldest files first as long as size limit still is surpassed; true = preserve keys.
		if ( ( pb_backupbuddy::$options['archive_limit_size'] > 0 ) && ( $summed_size > pb_backupbuddy::$options['archive_limit_size'] ) ) {
			// Need to trim.
			foreach( $files as $file ) {
				if ( $summed_size > pb_backupbuddy::$options['archive_limit_size'] ) {
					$summed_size = $summed_size - ( $file['size'] / 1048576 );
					pb_backupbuddy::status( 'details', sprintf( __('Deleting old archive `%s` due as it causes archives to exceed total size allowed.', 'it-l10n-backupbuddy' ),  $file['filename'] ) );
					if ( $file['filename'] != basename( $this->_backup['archive_file'] ) ) { // Delete excess archives as long as it is not the just-made backup.
						unlink( pb_backupbuddy::$options['backup_directory'] . $file['filename'] );
					} else {
						$message = __( 'ERROR #9028: Based on your backup archive limits (size limit) the backup that was just created would be deleted. Skipped deleting this backup. Please update your archive limits.' );
						pb_backupbuddy::status( 'message', $message );
						pb_backupbuddy::$classes['core']->mail_error( $message );
					}
				}
			}
		}
		
		return true;
		
	} // End trim_old_archives().
	
	
	
	/*	post_backup()
	 *	
	 *	Post-backup procedured. Clean up, send notifications, etc.
	 *	
	 *	@return		null
	 */
	function post_backup() {
		pb_backupbuddy::status( 'message', __('Cleaning up after backup.', 'it-l10n-backupbuddy' ) );
		
		// Delete temporary data directory.
		if ( file_exists( $this->_backup['temp_directory'] ) ) {
			pb_backupbuddy::status( 'details', __('Removing temp data directory.', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::$filesystem->unlink_recursive( $this->_backup['temp_directory'] );
		}
		// Delete temporary ZIP directory.
		if ( file_exists( pb_backupbuddy::$options['backup_directory'] . 'temp_zip_' . $this->_backup['serial'] . '/' ) ) {
			pb_backupbuddy::status( 'details', __('Removing temp zip directory.', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::$filesystem->unlink_recursive( pb_backupbuddy::$options['backup_directory'] . 'temp_zip_' . $this->_backup['serial'] . '/' );
		}
		
		$this->trim_old_archives(); // Clean up any old excess archives pushing us over defined limits in settings.
		
		$message = __('completed successfully in ', 'it-l10n-backupbuddy' ) . pb_backupbuddy::$format->time_duration( time() - $this->_backup['start_time'] ) . '. File: ' . basename( $this->_backup['archive_file'] );
		if ( $this->_backup['trigger'] == 'manual' ) {
			// No more manual notifications. Removed Feb 2012 before 3.0.
		} elseif ( $this->_backup['trigger'] == 'scheduled' ) {
			// Load core if it has not been instantiated yet.
			if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
				require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
				pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
			}
			
			pb_backupbuddy::status( 'details', __('Sending scheduled backup complete email notification.', 'it-l10n-backupbuddy' ) );
			pb_backupbuddy::$classes['core']->mail_notify_scheduled( 'complete', __('Scheduled backup', 'it-l10n-backupbuddy' ) . ' (' . $this->_backup['schedule_title'] . ') ' . $message );
		} else {
			pb_backupbuddy::status( 'error', 'Error #4343434. Unknown backup trigger.' );
		}
		
		// Schedule cleanup (12 hours from now; time for remote transfers) of log status file and data structure.
		wp_schedule_single_event( ( time() + ( 12 * 60 * 60 ) ), pb_backupbuddy::cron_tag( 'final_cleanup' ), array( $this->_backup['serial'] ) );
		
		pb_backupbuddy::status( 'message', __('Finished cleaning up.', 'it-l10n-backupbuddy' ) );
		pb_backupbuddy::status( 'action', 'archive_url^' . pb_backupbuddy::ajax_url( 'download_archive' ) . '&backupbuddy_backup=' . basename( $this->_backup['archive_file'] ) );
		
		if ( $this->_backup['backup_mode'] == '1' ) {
			$stats = stat( $this->_backup['archive_file'] );
			pb_backupbuddy::status( 'details', __('Final ZIP file size', 'it-l10n-backupbuddy' ) . ': ' . pb_backupbuddy::$format->file_size( $stats['size'] ) );
			pb_backupbuddy::status( 'action', 'archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ) );
		}
		
		pb_backupbuddy::status( 'message', __('Backup completed successfully in', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::$format->time_duration( time() - $this->_backup['start_time'] ) . '. ' . __('Done.', 'it-l10n-backupbuddy' ) );
		pb_backupbuddy::status( 'action', 'finish_backup' );
		
		return true;
		
	} // End post_backup().
	
	
	
	/*	send_remote_destination()
	 *	
	 *	Send the current backup to a remote destination such as S3, Dropbox, FTP, etc.
	 *	
	 *	@param		int		$destination_id		Destination ID (remote destination array index) to send to.
	 *	@return		boolean						Returns result of pb_backupbuddy::send_remote_destination(). True (success) or false (failure).
	 */
	function send_remote_destination( $destination_id ) {
		pb_backupbuddy::status( 'details', 'Sending file to remote destination ID: `' . $destination_id . '`.' );
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		return pb_backupbuddy::$classes['core']->send_remote_destination( $destination_id, $this->_backup['archive_file'] );
	} // End send_remote_destination().
	
	
	
	/*	post_remote_delete()
	 *	
	 *	Deletes backup archive. Used to delete the backup after sending to a remote destination for scheduled backups.
	 *	
	 *	@return		boolean		True on deletion success; else false.
	 */
	function post_remote_delete() {
		pb_backupbuddy::status( 'details', 'Deleting local copy of file sent remote.' );
		if ( file_exists( $this->_backup['archive_file'] ) ) {
			unlink( $this->_backup['archive_file'] );
		}
		
		if ( file_exists( $this->_backup['archive_file'] ) ) {
			pb_backupbuddy::status( 'details', __('Error. Unable to delete local archive as requested.', 'it-l10n-backupbuddy' ) );
			return false; // Didnt delete.
		} else {
			pb_backupbuddy::status( 'details', __('Deleted local archive as requested.', 'it-l10n-backupbuddy' ) );
			return true; // Deleted.
		}
	} // End post_remote_delete().
	
	
	
	
	
	
	
	
	
	
	/********* BEGIN MULTISITE (Exporting subsite; creates a standalone backup) *********/
	
	
	
	/*	ms_download_extract_wordpress()
	 *	
	 *	Used by Multisite Exporting.
	 *	Downloads and extracts the latest WordPress for making a standalone backup of a subsite.
	 *	Authored by Ron H. Modified by Dustin B.
	 *	
	 *	@return		boolean		True on success, else false.
	 */
	public function ms_download_extract_wordpress() {
		
		pb_backupbuddy::status( 'message', 'Downloading latest WordPress ZIP file.' );
		
		// Step 1 - Download a copy of WordPress.
		if ( !function_exists( 'download_url' ) ) {
			pb_backupbuddy::status( 'details', 'download_url() function not found. Loading `/wp-admin/includes/file.php`.' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		$wp_file = download_url( 'http://wordpress.org/latest.zip' );
		//error_log( 'post');
		if ( is_wp_error( $wp_file ) ) { // Grabbing WordPress ZIP failed.
			pb_backupbuddy::status( 'error', 'Error getting latest WordPress ZIP file: `' . $wp_file->get_error_message() . '`.' );
			return false;
		} else { // Grabbing WordPress ZIP succeeded.
			//error_log ('nowperror' );
			pb_backupbuddy::status( 'details', 'Latest WordPress ZIP file successfully downloaded.' );
		}
		
		
		// Step 2 - Extract WP into a separate directory.
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( $this->_options['backup_directory'] );
		}
		ob_start();
		pb_backupbuddy::$classes['zipbuddy']->unzip( $wp_file, dirname( $this->_backup['backup_root'] ) );
		pb_backupbuddy::status( 'details', 'Debugging information: `' . ob_get_clean() . '`' );
		
		@unlink( $wp_file );
		if ( file_exists( $wp_file ) ) { // Check to see if unlink() worked.
			pb_backupbuddy::status( 'warning', 'Unable to delete temporary WordPress file `' . $wp_file . '`. You may want to delete this after the backup / export completed.' );
		}
		
		return true;
		
	} // End ms_download_wordpress().
	
	
	
	/*	ms_create_wp_config()
	 *	
	 *	Used by Multisite Exporting.
	 *	Creates a standalone wp-config.php file for making a standalone backup from a subsite.
	 *	Authored by Ron H. Modified by Dustin B.
	 *	
	 *	@return		boolean			Currently only returns true.
	 */
	public function ms_create_wp_config() {
		
		pb_backupbuddy::status( 'message', 'Creating new wp-config.php file for temporary WordPress installation.' );
		
		global $current_blog;
		$blog_id = absint( $current_blog->blog_id );
		
		//Step 3 - Create new WP-Config File
		$to_file = "<?php\n";
		$to_file .= sprintf( "define( 'DB_NAME', '%s' );\n", '' );
		$to_file .= sprintf( "define( 'DB_USER', '%s' );\n", '' );
		$to_file .= sprintf( "define( 'DB_PASSWORD', '%s' );\n", '' );
		$to_file .= sprintf( "define( 'DB_HOST', '%s' );\n", '' );
		$charset = defined( 'DB_CHARSET' ) ? DB_CHARSET : '';
		$collate = defined( 'DB_COLLATE' ) ? DB_COLLATE : '';
		$to_file .= sprintf( "define( 'DB_CHARSET', '%s' );\n", $charset );
		$to_file .= sprintf( "define( 'DB_COLLATE', '%s' );\n", $collate );
		
		//Attempt to remotely retrieve salts
		$salts = wp_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );
		if ( !is_wp_error( $salts ) ) { // Success.
			$to_file .= wp_remote_retrieve_body( $salts ) . "\n";
		} else { // Failed.
			pb_backupbuddy::status( 'warning', 'Error getting salts from WordPress.org for wp-config.php. You may need to manually edit your wp-config on restore. Error: `' . $salts->get_error_message() . '`.' );
		}
		$to_file .= sprintf( "define( 'WPLANG', '%s' );\n", WPLANG );
		$to_file .= sprintf( '$table_prefix = \'%s\';' . "\n", 'bbms' . $blog_id . '_' );
		
		$to_file .= "if ( !defined('ABSPATH') ) { \n\tdefine('ABSPATH', dirname(__FILE__) . '/'); }";
		$to_file .= "/** Sets up WordPress vars and included files. */\n
		require_once(ABSPATH . 'wp-settings.php');";
		$to_file .= "\n?>";
		
		//Create the file, save, and close
		$file_handle = fopen( $this->_backup['backup_root'] . 'wp-config.php', 'w' );
		fwrite( $file_handle, $to_file );
		fclose( $file_handle );
		
		pb_backupbuddy::status( 'message', 'Temporary WordPress wp-config.php file created.' );
		
		return true;
	} // End ms_create_wp_config().
	
	
	
	/*	ms_copy_plugins()
	 *	
	 *	Used by Multisite Exporting.
	 *	Copies over the selected plugins for inclusion into the backup for creating a standalone backup from a subsite.
	 *	Authored by Ron H. Modified by Dustin B.
	 *	
	 *	@return		boolean			True on success, else false.
	 */
	public function ms_copy_plugins() {
	
		pb_backupbuddy::status( 'message', 'Copying selected plugins into temporary WordPress installation.' );
		
		//Step 4 - Copy over plugins
		//Move over plugins
		$plugin_items = $this->_backup['export_plugins'];
		//Populate $items_to_copy for all plugins to copy over
		if ( is_array( $plugin_items ) ) {
			$items_to_copy = array();
			//Get content directories by using this plugin as a base
			$content_dir = $dropin_plugins_dir = dirname( dirname( dirname( rtrim( plugin_dir_path(__FILE__), '/' ) ) ) );
			$mu_plugins_dir = $content_dir . '/mu-plugins';
			$plugins_dir = $content_dir . '/plugins';
			
			//Get the special plugins (mu, dropins, network activated)
			foreach ( $plugin_items as $type => $plugins ) {
				foreach ( $plugins as $plugin ) {
					if ( $type == 'mu' ) {
						$items_to_copy[ $plugin ] = $mu_plugins_dir . '/' . $plugin;
					} elseif ( $type == 'dropin' ) {
						$items_to_copy[ $plugin ] = $dropin_plugins_dir . '/' . $plugin;
					} elseif ( $type == 'network' || $type == 'site' ) {
						//Determine if we're a folder-based plugin, or a file-based plugin (such as hello.php)
						$plugin_path = dirname( $plugins_dir . '/' . $plugin );
						if ( basename( $plugin_path ) == 'plugins' ) {
							$plugin_path = $plugins_dir . '/' . $plugin;
						}
						$items_to_copy[ basename( $plugin_path ) ] = $plugin_path;		
					}
				} //end foreach $plugins
			} //end foreach special plugins
			
			
			//Copy the files over
			$wp_dir = '';
			if ( count( $items_to_copy ) > 0 ) {
				$wp_dir = $this->_backup['backup_root'];
				$wp_plugin_dir = $wp_dir . 'wp-content/plugins/';
				foreach ( $items_to_copy as $file => $original_destination ) {
					if ( file_exists( $original_destination ) && file_exists( $wp_plugin_dir ) ) {
						//$this->copy( $original_destination, $wp_plugin_dir . $file ); 
						$result = pb_backupbuddy::$filesystem->recursive_copy( $original_destination, $wp_plugin_dir . $file );
						
						if ( $result === false ) {
							pb_backupbuddy::status( 'error', 'Unable to copy plugin from `' . $original_destination . '` to `' . $wp_plugin_dir . $file . '`. Verify permissions.' );
							return false;
						} else {
							pb_backupbuddy::status( 'details', 'Copied plugin from `' . $original_destination . '` to `' . $wp_plugin_dir . $file . '`.' );
						}
					}
				}
			}
			
			// Finished
			
			pb_backupbuddy::status( 'message', 'Copied selected plugins into temporary WordPress installation.' );
			return true;

		} else {
			// Nothing has technically failed at this point - There just aren't any plugins to copy over.
			
			pb_backupbuddy::status( 'message', 'No plugins were selected for backup. Skipping plugin copying.' );
			return true;
		}
		
		return true; // Shouldnt get here.
		
	} // End ms_copy_plugins().
	
	
	
	/*	ms_copy_themes()
	 *	
	 *	Used by Multisite Exporting.
	 *	Copies over the selected themes for inclusion into the backup for creating a standalone backup from a subsite.
	 *	Authored by Ron H. Modified by Dustin B.
	 *	
	 *	@return		boolean			True on success, else false.
	 */
	public function ms_copy_themes() {
	
		
		pb_backupbuddy::status( 'message', 'Copying theme(s) into temporary WordPress installation.' );
		
		if ( !function_exists( 'wp_get_theme' ) ) {
			pb_backupbuddy::status( 'details', 'wp_get_theme() function not found. Loading `/wp-admin/includes/theme.php`.' );
			require_once( ABSPATH . 'wp-admin/includes/theme.php' );
			pb_backupbuddy::status( 'details', 'Loaded `/wp-admin/includes/theme.php`.' );
		}
		
		// Use new wp_get_theme() if available.
		if ( function_exists( 'wp_get_theme' ) ) { // WordPress v3.4 or newer.
			pb_backupbuddy::status( 'details', 'wp_get_theme() available. Using it.' );
			$current_theme = wp_get_theme();
		} else { // WordPress pre-v3.4
			pb_backupbuddy::status( 'details', 'wp_get_theme() still unavailable (pre WordPress v3.4?). Attempting to use older current_theme_info() fallback.' );
			$current_theme = current_theme_info();
		}
		
				
		//Step 5 - Copy over themes
		$template_dir = $current_theme->template_dir;
		$stylesheet_dir = $current_theme->stylesheet_dir;
		
		pb_backupbuddy::status( 'details', 'Got current theme information.' );
		
		//If $template_dir and $stylesheet_dir don't match, that means we have a child theme and need to copy over the parent also
		$items_to_copy = array();
		$items_to_copy[ basename( $template_dir ) ] = $template_dir;
		if ( $template_dir != $stylesheet_dir ) {
			$items_to_copy[ basename( $stylesheet_dir ) ] = $stylesheet_dir;
		}
		
		pb_backupbuddy::status( 'details', 'About to begin copying theme files...' );
		
		//Copy the files over
		if ( count( $items_to_copy ) > 0 ) {
			$wp_dir = $this->_backup['backup_root'];
			$wp_theme_dir = $wp_dir . 'wp-content/themes/';
			foreach ( $items_to_copy as $file => $original_destination ) {
				if ( file_exists( $original_destination ) && file_exists( $wp_theme_dir ) ) {
					
					$result = pb_backupbuddy::$filesystem->recursive_copy( $original_destination, $wp_theme_dir . $file ); 
					
					if ( $result === false ) {
						pb_backupbuddy::status( 'error', 'Unable to copy theme from `' . $original_destination . '` to `' . $wp_theme_dir . $file . '`. Verify permissions.' );
						return false;
					} else {
						pb_backupbuddy::status( 'details', 'Copied theme from `' . $original_destination . '` to `' . $wp_theme_dir . $file . '`.' );
					}
				} // end if file exists.
			} // end foreach $items_to_copy.
		} // end if.
		
		pb_backupbuddy::status( 'message', 'Copied theme into temporary WordPress installation.' );
		return true;
		
	} // End ms_copy_themes().
	
	
	
	/*	ms_copy_media()
	 *	
	 *	Used by Multisite Exporting.
	 *	Copies over media (wp-content/uploads) for this site for inclusion into the backup for creating a standalone backup from a subsite.
	 *	Authored by Ron H. Modified by Dustin B.
	 *	
	 *	@return		boolean			True on success, else false.
	 */
	public function ms_copy_media() {
		
		pb_backupbuddy::status( 'message', 'Copying media into temporary WordPress installation.' );
		
		//Step 6 - Copy over media/upload files
		$upload_dir = wp_upload_dir();
		$original_upload_base_dir = $upload_dir[ 'basedir' ];
		$destination_upload_base_dir = $this->_backup['backup_root'] . 'wp-content/uploads';
		//$result = pb_backupbuddy::$filesystem->custom_copy( $original_upload_base_dir, $destination_upload_base_dir, array( 'ignore_files' => array( $this->_backup['serial'] ) ) );
		
		// Grab directory upload contents so we can exclude backupbuddy directories.
		$upload_contents = glob( $original_upload_base_dir . '/*' );
		if ( !is_array( $upload_contents ) ) {
			$upload_contents = array();
		}
				
		foreach( $upload_contents as $upload_content ) {
			if ( strpos( $upload_content, 'backupbuddy_' ) === false ) { // Dont copy over any backupbuddy-prefixed uploads directories.
				$result = pb_backupbuddy::$filesystem->recursive_copy( $upload_content, $destination_upload_base_dir . '/' . basename( $upload_content ) );
			}
		}
		
		if ( $result === false ) {
			pb_backupbuddy::status( 'error', 'Unable to copy media from `' . $original_upload_base_dir . '` to `' . $destination_upload_base_dir . '`. Verify permissions.' );
			return false;
		} else {
			pb_backupbuddy::status( 'details', 'Copied media from `' . $original_upload_base_dir . '` to `' . $destination_upload_base_dir . '`.' );
			return true;
		}
		
	} // End ms_copy_media().
	
	
	
	/*	ms_copy_users_table()
	 *	
	 *  Step 7
	 *	Used by Multisite Exporting.
	 *	Copies over users to a temp table for this site for inclusion into the backup for creating a standalone backup from a subsite.
	 *	Authored by Ron H. Modified by Dustin B.
	 *	
	 *	@return		boolean			Currently only returns true.
	 */
	public function ms_copy_users_table() {
		
		pb_backupbuddy::status( 'message', 'Copying temporary users table for users in this blog.' );

		global $wpdb, $current_blog;
		
		$new_user_tablename = $wpdb->prefix . 'users';
		$new_usermeta_tablename = $wpdb->prefix . 'usermeta';
		
		if ( $new_user_tablename == $wpdb->users ) {
			pb_backupbuddy::status( 'message', 'Temporary users table would match existing users table. Skipping creation of this temporary users & usermeta tables.' );
			return true;
		}
		
		// Copy over users table to temporary table.
		pb_backupbuddy::status( 'message', 'Created new table `' . $new_user_tablename . '` like `' . $wpdb->users . '`.' );
		$wpdb->query( "CREATE TABLE `{$new_user_tablename}` LIKE `{$wpdb->users}`" );
		$wpdb->query( "INSERT `{$new_user_tablename}` SELECT * FROM `{$wpdb->users}" );
		
		// Copy over usermeta table to temporary table.
		pb_backupbuddy::status( 'message', 'Created new table `' . $new_usermeta_tablename . '` like `' . $wpdb->usermeta . '`.' );
		$wpdb->query( "CREATE TABLE `{$new_usermeta_tablename}` LIKE `{$wpdb->usermeta}`" );
		$wpdb->query( "INSERT `{$new_usermeta_tablename}` SELECT * FROM `{$wpdb->usermeta}" );
		
		// Get list of users associated with this site.
		$users_to_capture = array();
		$user_args = array(
			'blog_id' => $current_blog->blog_id
		);
		$users = get_users( $user_args );
		if ( $users ) {
			foreach ( $users as $user ) {
				array_push( $users_to_capture, $user->ID );
			}
		}
		$users_to_capture = implode( ',', $users_to_capture );
		
		// Remove users from temporary table that arent associated with this site.
		$wpdb->query( "DELETE from `{$new_user_tablename}` WHERE ID NOT IN( {$users_to_capture} )" );
		$wpdb->query( "DELETE from `{$new_usermeta_tablename}` WHERE user_id NOT IN( {$users_to_capture} )" );
		

		
		pb_backupbuddy::status( 'message', 'Copied temporary users table for users in this blog.' );
		return true;
		
	} // End ms_copy_users_table().
	
	public function ms_cleanup() {
		pb_backupbuddy::status( 'details', 'Beginning Multisite-export specific cleanup.' );
		
		global $wpdb;
		$new_user_tablename = $wpdb->prefix . 'users';
		$new_usermeta_tablename = $wpdb->prefix . 'usermeta';
		
		if ( ( $new_user_tablename == $wpdb->users ) || ( $new_usermeta_tablename == $wpdb->usermeta ) ) {
			pb_backupbuddy::status( 'error', 'Unable to clean up temporary user tables as they match main tables. Skipping to prevent data loss.' );
			return;
		}
		
		pb_backupbuddy::status( 'details', 'Dropping temporary table `' . $new_user_tablename . '`.' );
		$wpdb->query( "DROP TABLE `{$new_user_tablename}`" );
		pb_backupbuddy::status( 'details', 'Dropping temporary table `' . $new_usermeta_tablename . '`.' );
		$wpdb->query( "DROP TABLE `{$new_usermeta_tablename}`" );
		
		pb_backupbuddy::status( 'details', 'Done Multisite-export specific cleanup.' );
	}
	
	/********* END MULTISITE *********/
	
	
	
} // End class.
?>