<?php
/**
 *	pluginbuddy_zipbuddy Class
 *
 *	Handles zipping and unzipping, using the best methods available and falling back to worse methods
 *	as needed for compatibility. Allows for forcing compatibility modes.
 *	
 *	Version: 2.0.0
 *	Author: Dustin Bolton
 *	Author URI: http://dustinbolton.com/
 *
 *	$temp_dir		string		Temporary directory absolute path for temporary file storage. Must be writable!
 *	$zip_methods	array		Optional. Array of available zip methods to use. Useful for not having to re-test every time.
 *								If omitted then a test will be performed to find the methods that work on this host.
 *	$mode			string		Future use to allow for other compression methods other than zip. Currently not in use.
 *
 */

// Try and load the experimental version - if successful then class will exist and remaining code will be ignored
if (
		( defined( 'USE_EXPERIMENTAL_ZIPBUDDY' ) && ( true === USE_EXPERIMENTAL_ZIPBUDDY ) )
		||
		( isset( pb_backupbuddy::$options['alternative_zip'] ) && ( '1' == pb_backupbuddy::$options['alternative_zip'] ) )
	) {
		require_once( dirname( __FILE__ ) . '/x-zipbuddy.php' );
}

if ( !class_exists( "pluginbuddy_zipbuddy" ) ) {
	class pluginbuddy_zipbuddy {
		
		
		/********** Properties **********/
		
		
		const ZIP_METHODS_TRANSIENT = 'pb_backupbuddy_avail_zip_methods_classic';
		const ZIP_EXECPATH_TRANSIENT = 'pb_backupbuddy_exec_path_classic';
		const ZIP_TRANSIENT_LIFE = 60;
		
		private $_commandbuddy;
		public $_zip_methods;		// Array of available zip methods.
		
		
		/********** Methods **********/
		
		
		function __construct( $temp_dir, $zip_methods = array(), $mode = 'zip' ) {
			//$this->_status = array();
			$this->_tempdir = $temp_dir;
			$this->_execpath = '';
			
			// Handles command line execution.
			require_once( pb_backupbuddy::plugin_path() . '/lib/commandbuddy/commandbuddy.php' );
			$this->_commandbuddy = new pb_backupbuddy_commandbuddy();
			
			if ( !empty( $zip_methods ) && ( count( $zip_methods ) > 0 ) ) {
				$this->_zip_methods = $zip_methods;
			} else {
				if ( function_exists( 'get_transient' ) ) { // Inside WordPress
					
					if ( pb_backupbuddy::$options['disable_zipmethod_caching'] == '1' ) {
						pb_backupbuddy::status( 'details', 'Zip method caching disabled based on settings.' );
						$available_methods = false;
						$exec_path = false;
					} else { // Use caching.
						$available_methods = get_transient( self::ZIP_METHODS_TRANSIENT );
						$exec_path = get_transient( self::ZIP_EXECPATH_TRANSIENT );
					}
					
					if ( ( $available_methods === false ) || ( $exec_path === false ) ) {
						pb_backupbuddy::status( 'details', 'Zip methods or exec path were not cached; detecting...' );
						$this->_zip_methods = $this->available_zip_methods( false, $mode );
						set_transient( self::ZIP_METHODS_TRANSIENT, $this->_zip_methods, self::ZIP_TRANSIENT_LIFE );
						set_transient( self::ZIP_EXECPATH_TRANSIENT, $this->_execpath, self::ZIP_TRANSIENT_LIFE ); // Calculated and set in available_zip_methods().
						pb_backupbuddy::status( 'details', 'Caching zipbuddy classic methods & exec path for `' . self::ZIP_TRANSIENT_LIFE . '` seconds.' );
					} else {
						pb_backupbuddy::status( 'details', 'Using cached zipbuddy classic methods: `' . implode( ',', $available_methods ) . '`.' );
						pb_backupbuddy::status( 'details', 'Using cached zipbuddy classic exec path: `' . $exec_path . '`.' );
						$this->_zip_methods = $available_methods;
					}
				} else { // Outside WordPress
					$this->_zip_methods = $this->available_zip_methods( false, $mode );
					pb_backupbuddy::status( 'details', 'Zipbuddy classic methods not cached due to being outside WordPress.' );
				}
			}
		}
		
		
		// Returns true if the file (with path) exists in the ZIP.
		// If leave_open is true then the zip object will be left open for faster checking for subsequent files within this zip
		function file_exists( $zip_file, $locate_file, $leave_open = false ) {
			if ( in_array( 'ziparchive', $this->_zip_methods ) ) {
				$this->_zip = new ZipArchive;
				if ( $this->_zip->open( $zip_file ) === true ) {
						if ( $this->_zip->locateName( $locate_file ) === false ) { // File not found in zip.
							$this->_zip->close();
							pb_backupbuddy::status( 'details', __('File not found (ziparchive)','it-l10n-backupbuddy' ) . ': ' . $locate_file );
							return false;
						}
						$this->_zip->close();
					return true; // Never ran into a file missing so must have found them all.
				} else {
					pb_backupbuddy::status( 'details', sprintf( __('ZipArchive failed to open file to check if file exists (looking for %1$s in %2$s).','it-l10n-backupbuddy' ), $locate_file , $zip_file ) );
					
					return false;
				}
			}
			
			// If we made it this far then ziparchive not available/failed.
			if ( in_array( 'pclzip', $this->_zip_methods ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
				$this->_zip = new PclZip( $zip_file );
				if ( ( $file_list = $this->_zip->listContent() ) == 0 ) { // If zero, zip is corrupt or empty.
					pb_backupbuddy::status( 'details', $this->_zip->errorInfo( true ) );
				} else {
					foreach( $file_list as $file ) {
						if ( $file['filename'] == $locate_file ) { // Found file.
							return true;
						}
					}
					pb_backupbuddy::status( 'details', __('File not found (pclzip)','it-l10n-backupbuddy' ) . ': ' . $locate_file );
					return false;
				}
			} else {
				pb_backupbuddy::status( 'details', __('Unable to check if file exists: No compatible zip method found.','it-l10n-backupbuddy' ) );
				return false;
			}
		}
		
		
		
		/*	set_comment()
		 *	
		 *	Retrieve archive comment.
		 *	
		 *	@param		string			$zip_file		Filename of archive to set comment on.
		 *	@param		string			$comment		Comment to apply to archive.
		 *	@return		boolean/string					true on success, error message otherwise.
		 */
		function set_comment( $zip_file, $comment ) {
			if ( in_array( 'ziparchive', $this->_zip_methods ) ) {
				$this->_zip = new ZipArchive;
				if ( $this->_zip->open( $zip_file ) === true ) {
						$result = $this->_zip->setArchiveComment( $comment );
						$this->_zip->close();
						return $result;
					return true; // Never ran into a file missing so must have found them all.
				} else {
					$message = 'ZipArchive failed to open file to set comment in file: `' . $zip_file . '`.';
					pb_backupbuddy::status( 'details', $message );
					return $message;
				}
			}
			
			$message = "\n\nYour host does not support ZipArchive.\nThe note will only be stored internally in your settings and not in the zip file itself.";
			pb_backupbuddy::status( 'details', $message );
			return $message;
			
		} // End set_comment().
		
		
		
		/*	get_comment()
		 *	
		 *	Retrieve archive comment.
		 *	
		 *	@param		string		$zip_file		Filename of archive to retrieve comment from.
		 *	@return		string						Zip comment.
		 */
		function get_comment( $zip_file ) {
			if ( in_array( 'ziparchive', $this->_zip_methods ) ) {
				$this->_zip = new ZipArchive;
				if ( $this->_zip->open( $zip_file ) === true ) {
						$comment = $this->_zip->getArchiveComment();
						$this->_zip->close();
						return $comment;
					return true; // Never ran into a file missing so must have found them all.
				} else {
					pb_backupbuddy::status( 'details', sprintf( __('ZipArchive failed to open file to retrieve comment in file %1$s','it-l10n-backupbuddy' ), $zip_file ) );
					return false;
				}
			}
			
			// If we made it this far then ziparchive not available/failed.
			if ( in_array( 'pclzip', $this->_zip_methods ) ) {
				if ( !class_exists( 'PclZip' ) ) {
					return false;
				}
				$this->_zip = new PclZip( $zip_file );
				if ( ( $comment = $this->_zip->properties() ) == 0 ) { // If zero, zip is corrupt or no comment.
					return false;
				} else {
					return $comment['comment'];
				}
			}
			
			pb_backupbuddy::status( 'details', __('Unable to get comment: No compatible zip method found.','it-l10n-backupbuddy' ) );
			return false;
		} // End get_comment().
		
		
		
		// FOR FUTURE USE; NOT YET IMPLEMENTED. Use to check .sql file is non-empty.
		function file_stats( $zip_file, $locate_file, $leave_open = false ) {
			if ( in_array( 'ziparchive', $this->_zip_methods ) ) {
				$this->_zip = new ZipArchive;
				if ( $this->_zip->open( $zip_file ) === true ) {
					if ( ( $stats = $this->_zip->statName( $locate_file ) ) === false ) { // File not found in zip.
						$this->_zip->close();
						pb_backupbuddy::status( 'details', __('File not found (ziparchive) for stats','it-l10n-backupbuddy' ) . ': ' . $locate_file );
						return false;
					}
					$this->_zip->close();
					return $stats;
				} else {
					pb_backupbuddy::status( 'details', sprintf( __('ZipArchive failed to open file to check stats (looking in %1$s).','it-l10n-backupbuddy' ), $zip_file ) );
					
					return false;
				}
			}
			
			// If we made it this far then ziparchive not available/failed.
			if ( in_array( 'pclzip', $this->_zip_methods ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
				$this->_zip = new PclZip( $zip_file );
				if ( ( $file_list = $this->_zip->listContent() ) == 0 ) { // If zero, zip is corrupt or empty.
					pb_backupbuddy::status( 'details', $this->_zip->errorInfo( true ) );
				} else {
					foreach( $file_list as $file ) {
						if ( $file['filename'] == $locate_file ) { // Found file.
							return true;
						}
					}
					pb_backupbuddy::status( 'details', __('File not found (pclzip)','it-l10n-backupbuddy' ) . ': ' . $locate_file );
					return false;
				}
			} else {
				pb_backupbuddy::status( 'details', __('Unable to check if file exists: No compatible zip method found.','it-l10n-backupbuddy' ) );
				return false;
			}
		}
		
		
		
		/*	get_zip_methods()
		 *	
		 *	Get an array of the zip methods. Useful for transient caching for constructor.
		 *	
		 *	@return		array		Array of methods.
		 */
		public function get_zip_methods() {
			$this->_zip_methods;
		} // End get_zip_methods();
		
		/**
		 *	add_directory_to_zip()
		 *
		 *	Adds a directory to a new or existing (TODO: not yet available) ZIP file.
		 *
		 *	$zip_file					string				Full path & filename of ZIP file to create.
		 *	$add_directory				string				Full directory to add to zip file.
		 *	$compression				boolean				True to enable ZIP compression,
		 *													(if possible with available zip methods)
		 *	$excludes					array( string )		Array of strings of paths/files to exclude from zipping,
		 *													(if possible with available zip methods).
		 *	$temporary_zip_directory	string				Optional. Full directory path to directory to temporarily place ZIP
		 *													file while creating. Uses same directory if omitted.
		 *	$force_compatibility_mode	boolean				True: only use PCLZip. False: try exec first if available,
		 *													and fallback to lesser methods as required.
		 *
		 *	@return									true on success, false otherwise
		 *
		 */
		function add_directory_to_zip( $zip_file, $add_directory, $compression, $excludes = array(), $temporary_zip_directory = '', $force_compatibility_mode = false ) {
			if ( $force_compatibility_mode === true ) {
				$zip_methods = array( 'pclzip' );
				pb_backupbuddy::status( 'message', __('Forced compatibility mode (PCLZip) based on settings. This is slower and less reliable.','it-l10n-backupbuddy' ) );
			} else {
				$zip_methods = $this->_zip_methods;
				pb_backupbuddy::status( 'details', __('Using all available zip methods in preferred order.','it-l10n-backupbuddy' ) );
			}
			
			$append = false; // Possible future option to allow appending if file exists.
			
			if ( !empty( $temporary_zip_directory ) ) {
				if ( !file_exists( $temporary_zip_directory ) ) { // Create temp dir if it does not exist.
					mkdir( $temporary_zip_directory );
				}
			}
			
			pb_backupbuddy::status( 'details', __('Creating ZIP file','it-l10n-backupbuddy' ) . ' `' . $zip_file . '`. ' . __('Adding directory','it-l10n-backupbuddy' ) . ' `' . $add_directory . '`. ' . __('Compression','it-l10n-backupbuddy' ) . ': ' . $compression . '; ' . __('Excludes','it-l10n-backupbuddy' ) . ': ' . implode( ',', $excludes ) );
			
			if ( in_array( 'exec', $zip_methods ) ) {
				pb_backupbuddy::status( 'details', __('Using exec() method for ZIP.','it-l10n-backupbuddy' ) );
				
				$command = 'zip -q -r';
				
				if ( $compression !== true ) {
					$command .= ' -0';
					pb_backupbuddy::status( 'details', __('Exec compression disabled based on settings.','it-l10n-backupbuddy' ) );
				}
				if ( file_exists( $zip_file ) ) {
					if ( $append === true ) {
						pb_backupbuddy::status( 'details', __('ZIP file exists. Appending based on options.','it-l10n-backupbuddy' ) );
						$command .= ' -g';
					} else {
						pb_backupbuddy::status( 'details', __('ZIP file exists. Deleting & writing based on options.','it-l10n-backupbuddy' ) );
						unlink( $zip_file );
					}
				}
				
				//$command .= " -r";
				
				// Set temporary directory to store ZIP while it's being generated.
				if ( !empty( $temporary_zip_directory ) ) {
					$command .= " -b '{$temporary_zip_directory}'";
				}
				
				$command .= " '{$zip_file}' . -i '*'";
				
				if ( count( $excludes ) > 0 ) {
					pb_backupbuddy::status( 'details', __('Calculating directories to exclude from backup.','it-l10n-backupbuddy' ) );
					$command .= ' -x';
					
					$excluding_additional = false;
					$exclude_count = 0;
					foreach ( $excludes as $exclude ) {
						//$exclude = preg_replace( '|[/\\\\]$|', '', $exclude );
						$exclude = trim( $exclude, "\n\r\0" );
						if ( $exclude != '' ) {
							if ( !strstr( $exclude, 'backupbuddy_backups' ) ) { // Set variable to show we are excluding additional directories besides backup dir.
								$excluding_additional = true;
							}
							
							//$exclude = $exclude . '/';
							
							if ( substr( $exclude, -1, 1) != '/' ) {
								$exclude = $exclude . '/';
							}
							
							pb_backupbuddy::status( 'details', __('Excluding','it-l10n-backupbuddy' ) . ': ' . $exclude );
							$command .= " '{$exclude}*'";
							
							$exclude_count++;
						}
					}
				}
				
				$command .= ' "/importbuddy.php" 2>&1'; //  2>&1 redirects STDERR to STDOUT
				
				if ( $excluding_additional === true ) {
					pb_backupbuddy::status( 'message', __( 'Excluding archives directory and additional directories defined in settings.','it-l10n-backupbuddy' ) . ' ' . $exclude_count . ' ' . __( 'total','it-l10n-backupbuddy' ) . '.' );
				} else {
					pb_backupbuddy::status( 'message', __( 'Only excluding archives directory based on settings.','it-l10n-backupbuddy' ) . ' ' . $exclude_count . ' ' . __( 'total','it-l10n-backupbuddy' ) . '.' );
				}
				unset( $exclude_count );
				
				$working_dir = getcwd();
				chdir( $add_directory ); // Change directory to the path we are adding.
				
				if ( $this->_execpath != '' ) {
					pb_backupbuddy::status( 'details', __( 'Using custom exec() path: ', 'it-l10n-backupbuddy' ) . $this->_execpath );
				}
				
				// Run ZIP command.
				if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { // Running Windows. (not darwin)
					if ( file_exists( ABSPATH . 'zip.exe' ) ) {
						pb_backupbuddy::status( 'message', __('Attempting to use provided Windows zip.exe.','it-l10n-backupbuddy' ) );
						$command = str_replace( '\'', '"', $command ); // Windows wants double quotes
						$command = ABSPATH . $command;
					}
					
					pb_backupbuddy::status( 'details', __('Exec command (Windows)','it-l10n-backupbuddy' ) . ': ' . $command );
					list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $this->_execpath . $command );
				} else { // Allow exec warnings not in Windows
					pb_backupbuddy::status( 'details', __('Exec command (Linux)','it-l10n-backupbuddy' ) . ': ' . $command );
					list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $this->_execpath . $command );
				}
				
				sleep( 2 );
				// Verify zip command was created and exec reports no errors. If fails then falls back to other methods.
				if ( ( ! file_exists( $zip_file ) ) || ( $exec_exit_code == '-1' ) ) { // File not made or error returned.
					if ( ! file_exists( $zip_file ) ) {
						pb_backupbuddy::status( 'details', __( 'Exec command ran but ZIP file did not exist.','it-l10n-backupbuddy' ) );
					}
					pb_backupbuddy::status( 'message', __( 'Full speed mode did not complete. Trying compatibility mode next.','it-l10n-backupbuddy' ) );
					if ( file_exists( $zip_file ) ) { // If file was somehow created, its likely damaged since an error was thrown. Delete it.
						pb_backupbuddy::status( 'details', __( 'Cleaning up damaged ZIP file. Issue #3489328998.','it-l10n-backupbuddy' ) );
						unlink( $zip_file );
					}
					
					// If exec completed but left behind a temporary file/directory (often happens if a third party process killed off exec) then clean it up.
					if ( file_exists( $temporary_zip_directory ) ) {
						pb_backupbuddy::status( 'details', __( 'Cleaning up incomplete temporary ZIP file. Issue #343894.','it-l10n-backupbuddy' ) );
						$this->delete_directory_recursive( $temporary_zip_directory );
					}
				} else {
					pb_backupbuddy::status( 'message', __( 'Full speed mode completed & generated ZIP file.','it-l10n-backupbuddy' ) );
					return true;
				}
				
				chdir( $working_dir );
				
				unset( $command );
				unset( $exclude );
				unset( $excluding_additional );
				
				pb_backupbuddy::status( 'details', __('Exec command did not succeed. Falling back.','it-l10n-backupbuddy' ) );
			}
			
			if ( in_array( 'pclzip', $zip_methods ) ) {
				pb_backupbuddy::status( 'message', __('Using Compatibility Mode for ZIP. This is slower and less reliable.','it-l10n-backupbuddy' ) );
				pb_backupbuddy::status( 'message', __('If your backup times out in compatibility mode try disabled zip compression.','it-l10n-backupbuddy' ) );
				pb_backupbuddy::status( 'message', __('WARNING: Directory/file exclusion unavailable in Compatibility Mode. Even existing old backups will be backed up.','it-l10n-backupbuddy' ) );
				
				require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
				
				// Determine zip file name / path.
				if ( !empty( $temporary_zip_directory ) ) {
					$pclzip_file = $temporary_zip_directory . basename( $zip_file );
				} else {
					$pclzip_file = $zip_file;
				}
				
				
				if ( !file_exists( dirname( $pclzip_file ) ) ) {
					pb_backupbuddy::status( 'details', 'Creating PCLZip file directory `' . dirname( $pclzip_file ) . '`.' );
					mkdir( dirname( $pclzip_file ) );
				}
				
				// Instantiate PclZip Object.
				pb_backupbuddy::status( 'details', 'PclZip zip filename: `' . $pclzip_file . '`.' );
				$pclzip = new PclZip( $pclzip_file );
				
				if ( $compression !== true ) {
					pb_backupbuddy::status( 'details', __('PCLZip compression disabled based on settings.','it-l10n-backupbuddy' ) );
					$arguments = array( $add_directory, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $add_directory );
				} else {
					pb_backupbuddy::status( 'details', __('PCLZip compression enabled based on settings.','it-l10n-backupbuddy' ) );
					$arguments = array( $add_directory, PCLZIP_OPT_REMOVE_PATH, $add_directory );
				}
				
				$mode = 'create';
				if ( file_exists( $zip_file ) && ( $append === true ) ) {
					pb_backupbuddy::status( 'details', __('ZIP file exists. Appending based on options.','it-l10n-backupbuddy' ) );
					$mode = 'append';
				}
				
				if ( $mode == 'append' ) {
					pb_backupbuddy::status( 'details', __('Appending to ZIP file via PCLZip.','it-l10n-backupbuddy' ) );
					$result = call_user_func_array( array( &$pclzip, 'add' ), $arguments );
				} else { // create
					pb_backupbuddy::status( 'details', __( 'Creating ZIP file via PCLZip','it-l10n-backupbuddy' ) . ':' . implode( ';', $arguments ) );
					//error_log( 'pclzip args: ' . print_r( $arguments, true ) . "\n" );
					$result = call_user_func_array( array( &$pclzip, 'create' ), $arguments );
				}
				
				if ( !empty( $temporary_zip_directory ) ) {
					if ( file_exists( $temporary_zip_directory . basename( $zip_file ) ) ) {
						pb_backupbuddy::status( 'details', __('Renaming PCLZip File...','it-l10n-backupbuddy' ) );
						rename( $temporary_zip_directory . basename( $zip_file ), $zip_file );
						if ( file_exists( $zip_file ) ) {
							pb_backupbuddy::status( 'details', __('Renaming PCLZip success.','it-l10n-backupbuddy' ) );
						} else {
							pb_backupbuddy::status( 'details', __('Renaming PCLZip failure.','it-l10n-backupbuddy' ) );
						}
					} else {
						pb_backupbuddy::status( 'details', __('Temporary PCLZip archive file expected but not found. Please verify permissions on the ZIP archive directory.','it-l10n-backupbuddy' ) );
					}
				}
				
				pb_backupbuddy::status( 'details', __( 'PCLZip error message (if any):' ) . ' ' . $pclzip->errorInfo( true ) );
				
				if ( false !== strpos( $pclzip->errorInfo( true ), 'PCLZIP_ERR_READ_OPEN_FAIL' ) ) {
					pb_backupbuddy::status( 'details', 'PCLZIP_ERR_READ_OPEN_FAIL details: This error indicates that fopen failed (returned false) when trying to open the file in the mode specified. This is almost always due to permissions.' );
				}
				
				// If not a result of 0 and the file exists then it looks like the backup was a success.
				if ( ( $result != 0 ) && file_exists( $zip_file ) ) {
					pb_backupbuddy::status( 'details', __('Backup file created in compatibility mode (PCLZip).','it-l10n-backupbuddy' ) );
					return true;
				} else {
					if ( $result == 0 ) {
						pb_backupbuddy::status( 'details', __('PCLZip returned status 0.','it-l10n-backupbuddy' ) );
					}
					if ( !file_exists( $zip_file ) ) {
						pb_backupbuddy::status( 'details', __('PCLZip archive ZIP file was not found.','it-l10n-backupbuddy' ) );
					}
				}
				
				unset( $result );
				unset( $mode );
				unset( $arguments );
				unset( $pclzip );
			}
			
			// If we made it this far then something didnt result in a success.
			return false;
		}
		
		
		/**
		 *	unzip()
		 *
		 *	Extracts the contents of a zip file to the specified directory using the best unzip methods possible.
		 *
		 *	$zip_file					string		Full path & filename of ZIP file to create.
		 *	$destination_directory		string		Full directory path to extract into.
		 *	$force_compatibility_mode	mixed		false (default): use best methods available (zip exec first), falling back as needed.
		 *											ziparchive: first fallback method. (Medium performance)
		 *											pclzip: second fallback method. (Worst performance; buggy)
		 *
		 *	@return``								true on success, false otherwise
		 */
		function unzip( $zip_file, $destination_directory, $force_compatibility_mode = false ) {
			
			$destination_directory = rtrim( $destination_directory, '\\/' ) . '/'; // Make sure trailing slash exists to normalize.
			
			if ( $force_compatibility_mode == 'ziparchive' ) {
				$zip_methods = array( 'ziparchive' );
				pb_backupbuddy::status( 'message', __('Forced compatibility mode (ZipArchive; medium speed) based on settings. This is slower and less reliable.','it-l10n-backupbuddy' ) );
			} elseif ( $force_compatibility_mode == 'pclzip' ) {
				$zip_methods = array( 'pclzip' );
				pb_backupbuddy::status( 'message', __('Forced compatibility mode (PCLZip; slow speed) based on settings. This is slower and less reliable.','it-l10n-backupbuddy' ) );
			} else {
				$zip_methods = $this->_zip_methods;
				pb_backupbuddy::status( 'details', __('Using all available zip methods in preferred order.','it-l10n-backupbuddy' ) );
			}
			
			if ( in_array( 'exec', $zip_methods ) ) {
				pb_backupbuddy::status( 'details',  'Starting highspeed extraction (exec)... This may take a moment...' );
				
				$command = 'unzip -qo'; // q = quiet, o = overwrite without prompt.
				$command .= " '$zip_file' -d '$destination_directory' -x 'importbuddy.php'"; // x excludes importbuddy script to prevent overwriting newer importbuddy on extract step.
			
				// Handle windows.
				if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { // Running Windows. (not darwin)
					if ( file_exists( ABSPATH . 'unzip.exe' ) ) {
						pb_backupbuddy::status( 'details',  'Attempting to use Windows unzip.exe.' );
						$command = str_replace( '\'', '"', $command ); // Windows wants double quotes
						$command = ABSPATH . $command;
					}
				}
				
				$command .= '  2>&1'; // Redirect STDERR to STDOUT.
				
				if ( $this->_execpath != '' ) {
					pb_backupbuddy::status( 'details', __( 'Using custom exec() path: ', 'it-l10n-backupbuddy' ) . $this->_execpath );
				}
				
				pb_backupbuddy::status( 'details', 'Running ZIP command. This may take a moment.' );
				list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $this->_execpath . $command );
				
				$failed = false; // Default.
				
				if ( !file_exists( $destination_directory . 'wp-login.php' ) && !file_exists( $destination_directory . 'db_1.sql' ) && !file_exists( $destination_directory . 'wordpress/wp-login.php' ) ) { // wp-login.php for WordPress, db_1.sql for DB backup, wordpress/wp-login.php for fresh WordPress downloaded from wp.org for MS export
					pb_backupbuddy::status( 'error', 'Both wp-login.php (full backups) and db_1.sql (database only backups) are missing after extraction. Unzip process appears to have failed.' );
					$failed = true;
				}
				
				if ( $exec_exit_code != '0' ) {
					pb_backupbuddy::status( 'error',  'Exit code `' . $exec_exit_code . '` indicates a problem was encountered.' );
					$failed = true;
				}
				
				// Sometimes exec returns success codes but never extracted actual files. Do a check to make sure known files were extracted to verify against that.
				if ( $failed === false ) {
					pb_backupbuddy::status( 'message', 'File extraction complete.' );
					return true;
				} else {
					pb_backupbuddy::status( 'message',  'Falling back to next compatibility mode.' );
				}
			}
			
			if ( in_array( 'ziparchive', $zip_methods ) ) {
				pb_backupbuddy::status( 'details',  'Starting medium speed extraction (ziparchive)... This may take a moment...' );
				
				$zip = new ZipArchive;
				if ( $zip->open( $zip_file ) === true ) {
					if ( true === $zip->extractTo( $destination_directory ) ) {
						pb_backupbuddy::status( 'details',  'ZipArchive extraction success.' );
						$zip->close();
						return true;
					} else {
						$zip->close();
						pb_backupbuddy::status( 'message',  'Error: ZipArchive was available but failed extracting files.  Falling back to next compatibility mode.' );
					}
				} else {
					pb_backupbuddy::status( 'message',  'Error: Unable to open zip file via ZipArchive. Falling back to next compatibility mode.' );
				}
			}
			
			if ( in_array( 'pclzip', $zip_methods ) ) {
				pb_backupbuddy::status( 'details',  'Starting low speed extraction (pclzip)... This may take a moment...' );
				
				if ( !class_exists( 'PclZip' ) ) {
					$pclzip_file = pb_backupbuddy::plugin_path() . '/lib/pclzip/pclzip.php';
					if ( file_exists( $pclzip_file ) ) {
						require_once( $pclzip_file );
					}
				}
				//require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
				if ( !class_exists( 'PclZip' ) ) { // Note: Outside WordPress if this is not available.
					$pclzip_file = pb_backupbuddy::plugin_path() . '/lib/pclzip/pclzip.php';
					if ( file_exists( $pclzip_file ) ) {
						require_once( $pclzip_file );
					}
				}
				$archive = new PclZip( $zip_file );
				$result = $archive->extract(); // Extract to current directory. Explicity using PCLZIP_OPT_PATH results in extraction to a PCLZIP_OPT_PATH subfolder.
				
				if ( 0 == $result ) {
					pb_backupbuddy::status( 'details',  'PCLZip Failure: ' . $archive->errorInfo( true ) );
					pb_backupbuddy::status( 'message',  'Low speed (PCLZip) extraction failed.', $archive->errorInfo( true ) );
				} else {
					return true;
				}
			}
			
			// Nothing succeeded if we made it this far...
			return false;
		}
		
		
		// Test availability of ZipArchive and that it actually works.
		function test_ziparchive() {
			if ( class_exists( 'ZipArchive' ) ) {
				$test_file = $this->_tempdir . 'temp_test_' . uniqid() . '.zip';
				
				$zip = new ZipArchive;
				if ( $zip->open( $test_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE ) === true ) {
					$zip->addFile( __FILE__, 'this_is_a_test.txt');
					$zip->close();
					if ( file_exists( $test_file ) ) {
						unlink( $test_file );
						pb_backupbuddy::status( 'details', __('ZipArchive test passed.','it-l10n-backupbuddy' ) );
						return true;
					} else {
						pb_backupbuddy::status( 'details', __('ZipArchive test failed: Zip file not found.','it-l10n-backupbuddy' ) );
						return false;
					}
				} else {
					pb_backupbuddy::status( 'details', __('ZipArchive test FAILED: Unable to create/open zip file.','it-l10n-backupbuddy' ) );
					return false;
				}
			}
		}
		
		
		
		/*	get_file_list()
		 *	
		 *	Get an array of all files in a zip file.
		 *	
		 *	@param		
		 *	@return		array	
		 */
		public function get_file_list( $file ) {
			
			if ( !in_array( 'ziparchive', $this->_zip_methods ) ) { // Currently only available if ziparchive is available.
				return false;
			}
			
			$za = new ZipArchive();
			$za->open( $file );
			
			$result = array();
			
			for( $i = 0; $i < $za->numFiles; $i++ ){
				$stat = $za->statIndex( $i );
				$result[] = array(
					$stat['name'],
					$stat['size'],
					$stat['comp_size'],
					$stat['mtime'],
				);
			} // end for.
			
			return $result;
			
		} // End get_file_list().
		
		
								
		/*	available_zip_methods()
		 *	
		 *	Test availability of zip methods to determine which exist and actually work.
		 *	Detects the available zipping methods on this server. Tests command line zip via exec(), PHP's ZipArchive, or emulated zip via the PHP PCLZip library.
		 *	TODO: Actually test unzipping in unzip mode not just zipping and assuming the other will work
		 *	
		 *	@param		boolean		$return_best	
		 *	@param		string		$mode			Possible values: zip, unzip
		 *	@return		array						Possible return values: exec, ziparchive, pclzip
		 */
		function available_zip_methods( $return_best = true, $mode = 'zip' ) {
			$return = array();
			$test_file = $this->_tempdir . 'temp_' . uniqid() . '.zip';
			
			// Test command-line ZIP.
			if ( function_exists( 'exec' ) ) {
				$command = 'zip';
				$run_exec_zip_test = true;
				
				// Handle windows.
				if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { // Running Windows. (not darwin)
					if ( file_exists( ABSPATH . 'zip.exe' ) ) {
						$command = ABSPATH . $command;
					}
					// If unzip mode and unzip.exe is found then assume we have that option for unzipping since we arent actually testing unzip.
					if ( $mode == 'unzip' ) {
						$run_exec_zip_test = false;
						if ( file_exists( ABSPATH . 'unzip.exe' ) ) {
							array_push( $return, 'exec' );
						}
					}
					
					$exec_paths = array( '' );
				} else { // *NIX system.
					$exec_paths = array( '', '/usr/bin/', '/usr/local/bin/', '/usr/local/sbin/', '/usr/sbin/', '/sbin/', '/bin/' ); // Include preceeding & trailing slash.
				}
				
				if ( $run_exec_zip_test === true ) {
					// Possible locations to find the ZIP executable. Start with a blank string to attempt to run in current directory.
					
					pb_backupbuddy::status( 'details', 'Trying exec() in the following paths: `' . implode( ',', $exec_paths ) . '`' );
					
					$exec_completion = false; // default state.
					while( $exec_completion === false ) { // Check all possible zip path locations starting with current dir. Usually the path is set to make this work without hunting.
						if ( empty( $exec_paths ) ) {
							$exec_completion = true;
							pb_backupbuddy::status( 'error', __( 'Exhausted all known exec() path possibilities with no success.', 'it-l10n-backupbuddy' ) );
							break;
						}
						$path = array_shift( $exec_paths );
						pb_backupbuddy::status( 'details', __( 'Trying exec() ZIP path:', 'it-l10n-backupbuddy' ) . ' `' . $path . '`.' );
						
						$exec_command = $path . $command . ' "' . $test_file . '" "' . __FILE__ . '"  2>&1'; //  2>&1 to redirect STRERR to STDOUT.
						pb_backupbuddy::status( 'details', 'Zip test exec() command: `' . $exec_command . '`' );
						list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $exec_command );
						
						if ( ( !file_exists( $test_file ) ) || ( $exec_exit_code == '-1' ) ) { // File not made or error returned.
							$exec_completion = false;
							
							if ( $exec_exit_code == '-1' ) {
								pb_backupbuddy::status( 'details', __( 'Exec command returned -1.', 'it-l10n-backupbuddy' ) );
							}
							if ( !file_exists( $test_file ) ) {
								pb_backupbuddy::status( 'details', __( 'Exec command ran but ZIP file did not exist.', 'it-l10n-backupbuddy' ) );
							}
							if ( file_exists( $test_file ) ) { // If file was somehow created, do cleanup on it.
								pb_backupbuddy::status( 'details', __( 'Cleaning up damaged ZIP file. Issue #3489328998.', 'it-l10n-backupbuddy' ) );
								unlink( $test_file );
							}
						} else { // Success.
							$exec_completion = true;
							
							if ( !unlink( $test_file ) ) {
								echo sprintf( __( 'Error #564634. Unable to delete test file (%s)!', 'it-l10n-backupbuddy' ), $test_file );
							}
							array_push( $return, 'exec' );
							$this->_execpath = $path;
							
							break;
						}
					} // end while
				} // End $run_exec_test === true.
			} // End function_exists( 'exec' ).
			
			// Test ZipArchive
			if ( class_exists( 'ZipArchive' ) ) {
				if ( $this->test_ziparchive() === true ) {
					array_push( $return, 'ziparchive' );
				}
			}
			
			// Test PCLZip
			if ( class_exists( 'PclZip' ) ) { // Class already loaded.
				array_push( $return, 'pclzip' );
			} else { // Class not loaded. Seek it out.
				
				if ( file_exists( ABSPATH . 'wp-admin/includes/class-pclzip.php' ) ) { // Inside WP.
					require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
					array_push( $return, 'pclzip' );
				} elseif ( file_exists( pb_backupbuddy::plugin_path() . '/lib/pclzip/pclzip.php' ) ) { // ImportBuddy.
					require_once( pb_backupbuddy::plugin_path() . '/lib/pclzip/pclzip.php' );
					array_push( $return, 'pclzip' );
				}
				
			}
			
			return $return;
		} // End available_zip_methods().
		
		
		
		// Recursively delete a directory and all content within.
		function delete_directory_recursive( $directory ) {
			$directory = preg_replace( '|[/\\\\]+$|', '', $directory );
			
			$files = glob( $directory . '/*', GLOB_MARK );
			if ( is_array( $files ) && !empty( $files ) ) {
				foreach( $files as $file ) {
					if( '/' === substr( $file, -1 ) )
						$this->rmdir_recursive( $file );
					else
						unlink( $file );
				}
			}
			
			if ( is_dir( $directory ) ) rmdir( $directory );
			
			if ( is_dir( $directory ) )
				return false;
			return true;
		} // End delete_directory_recursive().
		
		
		
		function set_zip_methods( $methods ) {
			$this->_zip_methods = $methods;
		} // End set_zip_methods().
		
		
		
	} // End class
	
	//$pluginbuddy_zipbuddy = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
}
?>