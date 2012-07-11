<?php

class pb_backupbuddy_ajax extends pb_backupbuddy_ajaxcore {
	
	
	
	public function ajax_controller_callback_function() {
		echo 'a_post_variable: ' . pb_backupbuddy::_POST( 'a_post_variable' );  // aka $_POST['a_post_variable']
		echo 'selection: ' . pb_backupbuddy::_POST( 'selection' ); // aka $_POST['selection']
		
		die();
	}
	
	
	public function backup_status() {
		// Make sure the serial exists.
		if ( ( pb_backupbuddy::_POST( 'serial' ) == '' ) || empty( pb_backupbuddy::$options['backups'][$_POST['serial']] ) ) {
			echo '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|0|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|error|~|Error #5445589. Invalid backup serial (' . htmlentities( pb_backupbuddy::_POST( 'serial' ) ) . '). Please check directory permissions and your PHP error_log as an early backup function (such as pre_backup) may have failed. Fatal error.' . "\n";
			echo '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|0|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|action|~|halt_script' . "\n";
		} else {
			// Return the status information since last retrieval.
			$return_status = '!' . pb_backupbuddy::$format->localize_time( time() ) . "|~|0|~|0|~|ping\n";
			
			//error_log( print_r( pb_backupbuddy::$options['backups'], true ) );
			foreach( pb_backupbuddy::$options['backups'][$_POST['serial']]['steps'] as $step ) {
				if ( ( $step['start_time'] != 0 ) && ( $step['finish_time'] == 0 ) ) { // A step has begun but has not finished. This should not happen but the WP cron is funky. Wait a while before continuing.
					pb_backupbuddy::status( 'details', 'Waiting for function `' . $step['function'] . '` to complete. Started ' . ( time() - $step['start_time'] ) . ' seconds ago.', $_POST['serial'] );
					if ( ( time() - $step['start_time'] ) > 300 ) {
						pb_backupbuddy::status( 'warning', 'The function `' . $step['function'] . '` is taking an abnormally long time to complete (' . ( time() - $step['start_time'] ) . ' seconds). The backup may have stalled.', $_POST['serial'] );
					}
				} elseif ( $step['start_time'] == 0 ) { // Step that has not started yet.
				} else { // Last case: Finished. Skip.
					// Do nothing.
				}
			}
			
			/********** Begin file sizes for status updates. *********/
			
			$temporary_zip_directory = pb_backupbuddy::$options['backup_directory'] . 'temp_zip_' . $_POST['serial'] . '/';
			if ( file_exists( $temporary_zip_directory ) ) { // Temp zip file.
				$directory = opendir( $temporary_zip_directory );
				while( $file = readdir( $directory ) ) {
					if ( ( $file != '.' ) && ( $file != '..' ) ) {
						$stats = stat( $temporary_zip_directory . $file );
						$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|details|~|' . __('Temporary ZIP file size', 'it-l10n-backupbuddy' ) .': ' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";;
						$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|action|~|archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";
					}
				}
				closedir( $directory );
				unset( $directory );
			}
			if( file_exists( pb_backupbuddy::$options['backups'][$_POST['serial']]['archive_file'] ) ) { // Final zip file.
				$stats = stat( pb_backupbuddy::$options['backups'][$_POST['serial']]['archive_file'] );
				$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|details|~|' . __('Final ZIP file size', 'it-l10n-backupbuddy' ) . ': ' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";;
				$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|action|~|archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";
			}
			
			/********** End file sizes for status updates. *********/
			
			
			$status_lines = pb_backupbuddy::get_status( pb_backupbuddy::_POST( 'serial' ), true, false, true ); // Clear file, dont unlink file (pclzip cant handle files unlinking mid-zip), dont show getting status message.
			if ( $status_lines !== false ) { // Only add lines if there is status contents.
				foreach( $status_lines as $status_line ) {
					//$return_status .= '!' . $status_line[0] . '|' . $status_line[3] . '|' . $status_line[4] . '( ' . $status_line[1] . 'secs / ' . $status_line[2] . 'MB )' . "\n";
					$return_status .= '!' . implode( '|~|', $status_line ) . "\n";
				}
			}
			
			// Return messages.
			echo $return_status;
		}
		
		die();
	} // End backup_status().
	
	
	
	
	public function importbuddy() {
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		pb_backupbuddy::$classes['core']->importbuddy(); // Outputs importbuddy to browser for download.
		
		die();
	} // End importbuddy().
	
	
	
	public function repairbuddy() {
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		pb_backupbuddy::$classes['core']->repairbuddy(); // Outputs repairbuddy to browser for download.
		
		die();
	} // End repairbuddy().
	
	
	
	public function destination_picker() {
		pb_backupbuddy::load();
		
		pb_backupbuddy::$ui->ajax_header();
		
		require_once( 'ajax/_destination_picker.php' );
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End destination_picker().
	
	
	
	public function hash() {
		pb_backupbuddy::load();
		
		pb_backupbuddy::$ui->ajax_header();
		
		require_once( 'ajax/_hash.php' );
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End destination_picker().
	
	
	
	public function migration_picker() {
		pb_backupbuddy::load();
		
		pb_backupbuddy::$ui->ajax_header();
		
		require_once( 'ajax/_migration_picker.php' );
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End migration_picker().
	
	
	
	/*	remote_send()
	 *	
	 *	Send backup archive to a remote destination manually. Optionally sends importbuddy.php with files.
	 *	
	 *	@return		null
	 */
	public function remote_send() {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			die( 'Access denied in demo mode.' );
		}
		
		if ( pb_backupbuddy::_POST( 'send_importbuddy' ) == '1' ) {
			$send_importbuddy = true;
		} else {
			$send_importbuddy = false;
		}
		
		wp_schedule_single_event( time(), pb_backupbuddy::cron_tag( 'remote_send' ), array( $_POST['destination_id'], pb_backupbuddy::$options['backup_directory'] . $_POST['file'], $_POST['trigger'], $send_importbuddy ) );
		spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
		update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
		
		echo 1;
		die();
	} // End remote_send().
	
	
	
	/*	migrate_status()
	 *	
	 *	Gives the current migration status. Echos.
	 *	
	 *	@return		null
	 */
	function migrate_status() {
		
		$step = pb_backupbuddy::_POST( 'step' );
		$backup_file = pb_backupbuddy::_POST( 'backup_file' );
		$url = trim( pb_backupbuddy::_POST( 'url' ) );
		
		switch( $step ) {
			case 'step1': // Make sure backup file has been transferred properly.
				// Find last migration.
				$last_migration_key = '';
				foreach( pb_backupbuddy::$options['remote_sends'] as $send_key => $send ) { // Find latest migration send for this file.
					if ( basename( $send['file'] ) == $backup_file ) {
						if ( $send['trigger'] == 'migration' ) {
							$last_migration_key = $send_key;
						}
					}
				} // end foreach.
				$migrate_send_status = pb_backupbuddy::$options['remote_sends'][$last_migration_key]['status'];
				
				if ( $migrate_send_status == 'timeout' ) {
					$status_message = 'Status: Waiting for backup to finish uploading to server...';
					$next_step = '1';
				} elseif ( $migrate_send_status == 'failure' ) {
					$status_message = 'Status: Sending backup to server failed.';
					$next_step = '0';
				} elseif ( $migrate_send_status == 'success' ) {
					$status_message = 'Status: Success sending backup to server.';
					$next_step = '2';
				}
				die( json_encode( array(
					'status_code' 		=>		$migrate_send_status,
					'status_message'	=>		$status_message,
					'next_step'			=>		$next_step,
				) ) );
				
				break;
				
			case 'step2': // Hit importbuddy file to make sure URL is correct, it exists, and extracts itself fine.
				
				$url = rtrim( $url, '/' ); // Remove trailing slash if its there.
				if ( strpos( $url, 'importbuddy.php' ) === false ) { // If no importbuddy.php at end of URL add it.
					$url .= '/importbuddy.php';
				}
				
				if ( ( false === strstr( $url, 'http://' ) ) && ( false === strstr( $url, 'https://' ) ) ) { // http or https is missing; prepend it.
					$url = 'http://' . $url;
				}
				
				$response = wp_remote_get( $url . '?api=ping', array(
						'method' => 'GET',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => null,
						'cookies' => array()
					)
				);
				
				
				if( is_wp_error( $response ) ) {
					die( json_encode( array(
						'status_code' 		=>		'failure',
						'status_message'	=>		'Status: HTTP error checking for importbuddy.php at `' . $url . '`. Error: `' . $response->get_error_message() . '`.',
						'next_step'			=>		'0',
					) ) );
				}
				
				
				if ( trim( $response['body'] ) == 'pong' ) { // Importbuddy found.
					die( json_encode( array(
						'import_url'		=>		$url . '?display_mode=embed&file=' . pb_backupbuddy::_POST( 'backup_file' ) . '&v=' . pb_backupbuddy::$options['importbuddy_pass_hash'],
						'status_code' 		=>		'success',
						'status_message'	=>		'Sucess verifying URL is valid importbuddy.php location. Continue migration below.',
						'next_step'			=>		'0',
					) ) );
				} else { // No importbuddy here.
					die( json_encode( array(
						'status_code' 		=>		'failure',
						'status_message'	=>		__( 'Status: importbuddy.php not found at provided URL. Enter a new URL above and try again.', 'it-l10n-backupbuddy' ),
						'next_step'			=>		'0',
					) ) );
				}
				
				break;
				
			default:
				echo 'Invalid migrate_status() action `' . pb_backupbuddy::_POST( 'action' ) . '`.';
				break;
		} // End switch on action.
		
		die();
		
	} // End migrate_status().
	
	
	
	/*	icicle()
	 *	
	 *	Builds and returns graphical directory size listing. Echos.
	 *	
	 *	@return		null
	 */
	public function icicle() {
		pb_backupbuddy::set_greedy_script_limits(); // Building the directory tree can take a bit.
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		$response = pb_backupbuddy::$classes['core']->build_icicle( ABSPATH, ABSPATH, '', -1 );
		
		echo $response[0];
		die();
	} // End icicle().
	
	
	
	/*	remote_test()
	 *	
	 *	Remote destination testing. Echos.
	 *	
	 *	@return		null
	 */
	function remote_test() {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			die( 'Access denied in demo mode.' );
		}
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		if ( $_POST['#type'] == 's3' ) {
			if ( $_POST['#ssl'] == '1' ) {
				$s3_ssl = true;
			} else {
				$s3_ssl = false;
			}
			if ( true === ( $response = pb_backupbuddy::$classes['core']->test_s3( $_POST['#accesskey'], $_POST['#secretkey'], $_POST['#bucket'], $_POST['#directory'], $s3_ssl ) ) ) {
				echo __('Test completed successfully.', 'it-l10n-backupbuddy' );
			} else {
				echo __('Failure', 'it-l10n-backupbuddy' ) . '; ' . $response;
			}
		} elseif ( $_POST['#type'] == 'rackspace' ) {
			if ( true === ( $response = pb_backupbuddy::$classes['core']->test_rackspace( $_POST['#username'], $_POST['#api_key'], $_POST['#container'], $_POST['#server'] ) ) ) {
				echo __('Test completed successfully.', 'it-l10n-backupbuddy' );
			} else {
				echo __('Failure', 'it-l10n-backupbuddy' ) . '; ' . $response;
			}
		} elseif ( $_POST['#type'] == 'ftp' ) {
			if ( $_POST['#ftps'] == '0' ) {
				$ftp_type = 'ftp';
			} else {
				$ftp_type = 'ftps';
			}
			if ( true === ( $response = pb_backupbuddy::$classes['core']->test_ftp( $_POST['#address'], $_POST['#username'], $_POST['#password'], $_POST['#path'], $ftp_type ) ) ) {
				echo __('Test completed successfully.', 'it-l10n-backupbuddy' );
			} else {
				echo __('Failure', 'it-l10n-backupbuddy' ) . '; ' . $response;
			}
		} elseif ( pb_backupbuddy::_POST( '#type' ) == 'local' ) { // Used for automatic migration currently.
			
			if ( !file_exists( pb_backupbuddy::_POST( 'path' ) ) ) {
				pb_backupbuddy::$filesystem->mkdir( pb_backupbuddy::_POST( 'path' ) );
			}
				
			if ( is_writable( pb_backupbuddy::_POST( 'path' ) ) === true ) {
				
				if ( pb_backupbuddy::_POST( 'url' ) == '' ) { // No URL provided.
					echo __('Test completed successfully without URL. You may enter it on the next page.', 'it-l10n-backupbuddy' );
				} else { // URL provided.
					
					if ( file_exists( rtrim( pb_backupbuddy::_POST( 'path' ), '/\\' ) . '/wp-login.php' ) ) {
						echo 'Warning: WordPress appears to already exist in this location. ';
					}
					
					$test_filename = 'migrate_test_' . pb_backupbuddy::random_string( 10 ) . '.php';
					$test_file_path = rtrim( pb_backupbuddy::_POST( 'path' ), '/\\' ) . '/' . $test_filename;
					file_put_contents( $test_file_path, "<?php die( '1' ); ?>" );
					
					$response = wp_remote_get( rtrim( pb_backupbuddy::_POST( 'url' ), '/\\' ) . '/' . $test_filename, array(
							'method' => 'GET',
							'timeout' => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'blocking' => true,
							'headers' => array(),
							'body' => null,
							'cookies' => array()
						)
					);
					
					unlink( $test_file_path );
					
					if( is_wp_error( $response ) ) {
						die( __( 'Failure. Unable to connect to the provided URL.', 'it-l10n-backupbuddy' ) );
					}
					
					if ( trim( $response['body'] ) == '1' ) {
						echo __('Test completed successfully. Path and URL appear valid and match.', 'it-l10n-backupbuddy' );
					} else {
						echo __('Failure. The path appears valid but the URL does not correspond to it.', 'it-l10n-backupbuddy' );
					}
				}
			} else {
				echo __('Failure', 'it-l10n-backupbuddy' ) . '; The path does not allow writing. Please verify write file permissions.';
			}
			
		} else {
			echo 'Error #4343489. There is not an automated test available for this service `' . $_POST['#type'] . '` at this time.';
		}
		
		die();
	} // End remote_test().
	
	
	
	/*	refresh_site_size()
	 *	
	 *	Server info page site size refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_site_size() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$site_size = pb_backupbuddy::$classes['core']->get_site_size(); // array( site_size, site_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $site_size[0] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	refresh_site_size_excluded()
	 *	
	 *	Server info page site size (sans exclusions) refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_site_size_excluded() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$site_size = pb_backupbuddy::$classes['core']->get_site_size(); // array( site_size, site_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $site_size[1] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	refresh_database_size()
	 *	
	 *	Server info page database size refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_database_size() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$database_size = pb_backupbuddy::$classes['core']->get_database_size(); // array( database_size, database_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $database_size[1] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	refresh_database_size_excluded()
	 *	
	 *	Server info page database size (sans exclusions) refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_database_size_excluded() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$database_size = pb_backupbuddy::$classes['core']->get_database_size(); // array( database_size, database_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $database_size[1] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	exclude_tree()
	 *	
	 *	Directory exclusion tree for settings page.
	 *	
	 *	@return		null
	 */
	function exclude_tree() {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			die( 'Access denied in demo mode.' );
		}
		
		$root = ABSPATH;
		$_POST['dir'] = urldecode( $_POST['dir'] );
		if( file_exists( $root . $_POST['dir'] ) ) {
			$files = scandir( $root . $_POST['dir'] );
			natcasesort( $files );
			if( count( $files ) > 2 ) { /* The 2 accounts for . and .. */
				echo '<ul class="jqueryFileTree" style="display: none;">';
				foreach( $files as $file ) {
					if( file_exists( $root . $_POST['dir'] . $file ) && ( $file != '.' ) && ( $file != '..' ) && ( is_dir( $root . $_POST['dir'] . $file ) ) ) {
						echo '<li class="directory collapsed"><a href="#" rel="' . htmlentities($_POST['dir'] . $file) . '/">' . htmlentities($file) . ' <img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_delete.png" style="vertical-align: -3px;" /></a></li>';
					}
				}
				echo '</ul>';
			} else {
				echo '<ul class="jqueryFileTree" style="display: none;">';
				echo '<li><a href="#" rel="' . htmlentities( $_POST['dir'] . 'NONE' ) . '"><i>Empty Directory ...</i></a></li>';
				echo '</ul>';
			}
		} else {
			echo 'Error #1127555. Unable to read site root.';
		}
		
		die();
	} // End exclude_tree().
	
	
	
	/*	download_archive()
	 *	
	 *	Handle allowing download of archive.
	 *	
	 *	@param		
	 *	@return		
	 */
	public function download_archive() {
		
		if ( is_multisite() && !current_user_can( 'manage_network' ) ) { // If a Network and NOT the superadmin must make sure they can only download the specific subsite backups for security purposes.
			// Load core if it has not been instantiated yet.
			if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
				require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
				pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
			}
			
			// Only allow downloads of their own backups.
			if ( !strstr( pb_backupbuddy::_GET( 'backupbuddy_backup' ), pb_backupbuddy::$classes['core']->backup_prefix() ) ) {
				die( 'Access Denied. You may only download backups specific to your Multisite Subsite. Only Network Admins may download backups for another subsite in the network.' );
			}
		}
		
		$download_url = site_url() . '/wp-content/uploads/backupbuddy_backups/' . pb_backupbuddy::_GET( 'backupbuddy_backup' );
		
		if ( pb_backupbuddy::$options['lock_archives_directory'] == '1' ) { // High security mode.
			
			if ( file_exists( pb_backupbuddy::$options['backup_directory'] . '.htaccess' ) ) {
				$unlink_status = @unlink( pb_backupbuddy::$options['backup_directory'] . '.htaccess' );
				if ( $unlink_status === false ) {
					die( 'Error #844594. Unable to temporarily remove .htaccess security protection on archives directory to allow downloading. Please verify permissions of the BackupBuddy archives directory or manually download via FTP.' );
				}
			}
			
			header( 'Location: ' . $download_url );
			ob_clean();
			flush();
			sleep( 8 ); // Wait 8 seconds before creating security file.
			
			$htaccess_creation_status = @file_put_contents( pb_backupbuddy::$options['backup_directory'] . '.htaccess', 'deny from all' );
			if ( $htaccess_creation_status === false ) {
				die( 'Error #344894545. Security Warning! Unable to create security file (.htaccess) in backups archive directory. This file prevents unauthorized downloading of backups should someone be able to guess the backup location and filenames. This is unlikely but for best security should be in place. Please verify permissions on the backups directory.' );
			}
			
		} else { // Normal mode.
			header( 'Location: ' . $download_url );
		}
		
		
		
		die();
	} // End download_archive().
	
	
	
	// Server info page phpinfo button.
	public function phpinfo() {
		phpinfo();
		die();
	}
	
	
	
	/*	set_backup_note()
	 *	
	 *	Used for setting a note to a backup archive.
	 *	
	 *	@return		null
	 */
	public function set_backup_note() {
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		}
		
		$backup_file = pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_POST( 'backup_file' );
		$note = pb_backupbuddy::_POST( 'note' );
		$note = ereg_replace( "[[:space:]]+", ' ', $note );
		$note = ereg_replace( "[^[:print:]]", '', $note );
		$note = htmlentities( substr( $note, 0, 200 ) );
		
		
		// Returns true on success, else the error message.
		$comment_result = pb_backupbuddy::$classes['zipbuddy']->set_comment( $backup_file, $note );
		
		
		if ( $comment_result !== true ) {
			echo $comment_result;
		} else {
			echo '1';
		}
		
		// Even if we cannot save the note into the archive file, store it in internal settings.
		$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( $backup_file );
		pb_backupbuddy::$options['backups'][$serial]['integrity']['comment'] = $note;
		pb_backupbuddy::save();
		
		
		die();
	} // End set_backup_note().
	
	
}
?>