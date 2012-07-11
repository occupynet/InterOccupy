<?php
class pb_backupbuddy_import {
	
	
	
	/**
	 *	migrate_htaccess()
	 *
	 *	Migrates .htaccess.bb_temp file if it exists.
	 *
	 *	@return		boolean		False only if file is unwritable. True if write success; true if file does not even exist.
	 *
	 */
	function migrate_htaccess() {
		if ( pb_backupbuddy::$options['skip_htaccess'] == true ) {
			pb_backupbuddy::status( 'message', 'Skipping .htaccess migration based on settings.' );
			return true;
		}
		
		if ( !is_writable( ABSPATH . '.htaccess.bb_temp' ) ) {
			pb_backupbuddy::status( 'error', 'Error #9020: Unable to write to `.htaccess.bb_temp` file. Verify permissions.' );
			pb_backupbuddy::alert( 'Warning: Unable to write to file `.htaccess.bb_temp`. Verify this file has proper write permissions. You may receive 404 Not Found errors on your site if this is not corrected. To fix after migration completes: Log in to your WordPress admin and select Settings:: Permalinks from the left menu then save.', '9020' );
			return false;
		}
		
		// If no .htaccess.bb_temp file exists then create a basic default one then migrate that as needed. @since 2.2.32.
		if ( !file_exists( ABSPATH . '.htaccess.bb_temp' ) ) {
			pb_backupbuddy::status( 'message', 'No `.htaccess.bb_temp` file found. Creating basic default .htaccess file.' );
			
			// Default .htaccess file.
			$htaccess_contents = 
"# BEGIN WordPress\n
<IfModule mod_rewrite.c>\n
RewriteEngine On\n
RewriteBase /\n
RewriteRule ^index\\.php$ - [L]\n
RewriteCond %{REQUEST_FILENAME} !-f\n
RewriteCond %{REQUEST_FILENAME} !-d\n
RewriteRule . /index.php [L]\n
</IfModule>\n
# END WordPress\n";
			file_put_contents( ABSPATH . '.htaccess.bb_temp', $htaccess_contents );
			unset( $htaccess_contents );
		}
		
		pb_backupbuddy::status( 'message', 'Migrating `.htaccess.bb_temp` file...' );
		
		$oldurl = strtolower( pb_backupbuddy::$options['dat_file']['siteurl'] );
		$oldurl = str_replace( '/', '\\', $oldurl );
		$oldurl = str_replace( 'http:\\', '', $oldurl );
		$oldurl = trim( $oldurl, '\\' );
		$oldurl = explode( '\\', $oldurl );
		$oldurl[0] = '';
		
		$newurl = strtolower( pb_backupbuddy::$options['siteurl'] );
		$newurl = str_replace( '/', '\\', $newurl );
		$newurl = str_replace( 'http:\\', '', $newurl );
		$newurl = trim( $newurl, '\\' );
		$newurl = explode( '\\', $newurl );
		$newurl[0] = '';
		
		pb_backupbuddy::status( 'message', 'Checking `.htaccess.bb_temp` file.' );
		
		// If the URL (domain and/or URL subdirectory ) has changed, then need to update .htaccess.bb_temp file.
		if ( $newurl !== $oldurl ) {
			pb_backupbuddy::status( 'message', 'URL directory has changed. Updating from `' . implode( '/', $oldurl ) . '` to `' . implode( '/', $newurl ) . '`.' );
			
			$rewrite_lines = array();
			$got_rewrite = false;
			$rewrite_path = implode( '/', $newurl );
			$file_array = file( ABSPATH . '.htaccess.bb_temp' );
			
			foreach ($file_array as $line_number => $line) {
				if ( $got_rewrite == true ) { // In a WordPress section.
					if ( strstr( $line, 'END WordPress' ) ) { // End of a WordPress block so stop replacing.
						$got_rewrite = false;
						$rewrite_lines[] =  $line; // Captures end of WordPress block.
					} else {
						if ( strstr( $line, 'RewriteBase' ) ) { // RewriteBase
							$rewrite_lines[] = 'RewriteBase ' . $rewrite_path . '/' . "\n";
						} elseif ( strstr( $line, 'RewriteRule' ) ) { // RewriteRule
							if ( strstr( $line, '^index\.php$' ) ) { // Handle new strange rewriterule. Leave as is.
								$rewrite_lines[] = $line;
								pb_backupbuddy::status( 'details', 'Htaccess ^index\.php$ detected. Leaving as is.' );
							} else { // Normal spot.
								$rewrite_lines[] = 'RewriteRule . ' . $rewrite_path . '/index.php' . "\n";
							}
						} else {
							$rewrite_lines[] =  $line; // Captures everything inside WordPress block we arent modifying.
						}
					}
				} else { // Outside a WordPress section.
					if ( strstr( $line, 'BEGIN WordPress' ) ) {
						$got_rewrite = true; // Beginning of a WordPress block so start replacing.
					}
					$rewrite_lines[] =  $line; // Captures everything outside of WordPress block.
				}
			}
				
			$handling = fopen( ABSPATH . '.htaccess.bb_temp', 'w');
			fwrite( $handling, implode( $rewrite_lines ) );
			fclose( $handling );
			unset( $handling );
			
			pb_backupbuddy::status( 'message', 'Migrated `.htaccess.bb_temp` file. It will be renamed back to `.htaccess` on the final step.' );
		} else {
			pb_backupbuddy::status( 'message', 'No changes needed for `.htaccess.bb_temp` file.' );
		}
		
		return true;
	} // End migrate_htaccess().
	
	
	
	/**
	 *	wipe_database()
	 *
	 *	Clear out the existing database to prepare for importing new data.
	 *
	 *	@return			boolean		Currently always true.
	 */
	function wipe_database( $prefix ) {
		if ( $prefix == '' ) {
			pb_backupbuddy::status( 'warning', 'No database prefix specified to wipe.' );
			return false;
		}
		pb_backupbuddy::status( 'message', 'Beginning wipe of database tables matching prefix `' . $prefix . '`...' );
		
		// Connect to database.
		$this->connect_database();
		
		$query = "SHOW TABLES LIKE '" . mysql_real_escape_string( str_replace( '_', '\_', $prefix ) ) . "%'"; // Underscore must be escaped for use in mysql LIKE to make literal.
		
		pb_backupbuddy::status( 'message', 'Drop query: `' . $query . '`.' );
		$result = mysql_query( $query );
		$table_wipe_count = mysql_num_rows( $result );
		while( $row = mysql_fetch_row( $result ) ) {
			pb_backupbuddy::status( 'details', 'Dropping table `' . $row[0] . '`.' );
			mysql_query( 'DROP TABLE `' . $row[0] . '`' );
		}
		mysql_free_result( $result ); // Free memory.
		pb_backupbuddy::status( 'message', 'Wiped database of ' . $table_wipe_count . ' tables.' );
		
		return true;
	} // End wipe_database().
	
	
	
	/**
	 *	wipe_database()
	 *
	 *	Clear out the existing database to prepare for importing new data.
	 *
	 *	@return			boolean		Currently always true.
	 */
	function wipe_database_all( $confirm = false ) {
		if ( $confirm !== true ) {
			die( 'Error #5466566: Parameter 1 to wipe_database_all() must be boolean true to proceed.' );
		}
		
		pb_backupbuddy::status( 'message', 'Beginning wipe of ALL database tables...' );
		
		// Connect to database.
		$this->connect_database();
		
		$query = "SHOW TABLES";
		
		pb_backupbuddy::status( 'message', 'Drop query: `' . $query . '`.' );
		$result = mysql_query( $query );
		$table_wipe_count = mysql_num_rows( $result );
		while( $row = mysql_fetch_row( $result ) ) {
			pb_backupbuddy::status( 'details', 'Dropping table `' . $row[0] . '`.' );
			mysql_query( 'DROP TABLE `' . $row[0] . '`' );
		}
		mysql_free_result( $result ); // Free memory.
		pb_backupbuddy::status( 'message', 'Wiped database of ' . $table_wipe_count . ' tables.' );
		
		return true;
	} // End wipe_database_all().
	
	
	
	/*	preg_escape_back()
	 *	
	 *	Escape backreferences from string for use with regex. Used by migrate_wp_config().
	 *	@see migrate_wp_config()
	 *	
	 *	@param		string		$string		String to escape.
	 *	@return		string					Escaped string.
	 */
	function preg_escape_back($string) {
		// Replace $ with \$ and \ with \\
		$string = preg_replace('#(?<!\\\\)(\\$|\\\\)#', '\\\\$1', $string);
		return $string;
	} // End preg_escape_back().
	
	
	
	/**
	 *	migrate_wp_config()
	 *
	 *	Migrates and updates the wp-config.php file contents as needed.
	 *
	 *	@return			null			True on success. On false returns the new wp-config file content.
	 */
	function migrate_wp_config() {
		pb_backupbuddy::status( 'message', 'Starting migration of wp-config.php file...' );
		
		flush();
		
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			// Useful REGEX site: http://gskinner.com/RegExr/
			
			$updated_home_url = false;
			$wp_config = array();
			$lines = file( ABSPATH . 'wp-config.php' );
			
			$patterns = array();
			$replacements = array();
			
			/*
			Update WP_SITEURL, WP_HOME if they exist.
			Update database DB_NAME, DB_USER, DB_PASSWORD, and DB_HOST.
			RegExp: /define\([\s]*('|")WP_SITEURL('|"),[\s]*('|")(.)*('|")[\s]*\);/gi
			pattern: define\([\s]*('|")WP_SITEURL('|"),[\s]*('|")(.)*('|")[\s]*\);
			*/
			$pattern[0] = '/define\([\s]*(\'|")WP_SITEURL(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[0] = "define( 'WP_SITEURL', '" . trim( pb_backupbuddy::$options['siteurl'], '/' ) . "' );";
			pb_backupbuddy::status( 'details', 'wp-config.php: Setting WP_SITEURL (if applicable) to `' . trim( pb_backupbuddy::$options['siteurl'], '/' ) . '`.' );
			$pattern[1] = '/define\([\s]*(\'|")WP_HOME(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[1] = "define( 'WP_HOME', '" . trim( pb_backupbuddy::$options['home'], '/' ) . "' );";
			pb_backupbuddy::status( 'details', 'wp-config.php: Setting WP_HOME (if applicable) to `' . trim( pb_backupbuddy::$options['home'], '/' ) . '`.' );
			
			$pattern[2] = '/define\([\s]*(\'|")DB_NAME(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[2] = "define( 'DB_NAME', '" . pb_backupbuddy::$options['db_name'] . "' );";
			$pattern[3] = '/define\([\s]*(\'|")DB_USER(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[3] = "define( 'DB_USER', '" . pb_backupbuddy::$options['db_user'] . "' );";
			$pattern[4] = '/define\([\s]*(\'|")DB_PASSWORD(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[4] = "define( 'DB_PASSWORD', '" . $this->preg_escape_back( pb_backupbuddy::$options['db_password'] ) . "' );";
			$pattern[5] = '/define\([\s]*(\'|")DB_HOST(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[5] = "define( 'DB_HOST', '" . pb_backupbuddy::$options['db_server'] . "' );";
			
			// If multisite, update domain.
			$pattern[6] = '/define\([\s]*(\'|")DOMAIN_CURRENT_SITE(\'|"),[\s]*(\'|")(.)*(\'|")[\s]*\);/i';
			$replace[6] = "define( 'DOMAIN_CURRENT_SITE', '" . pb_backupbuddy::$options['domain'] . "' );";
			pb_backupbuddy::status( 'details', 'wp-config.php: Setting DOMAIN_CURRENT_SITE (if applicable) to `' . pb_backupbuddy::$options['domain'] . '`.' );
			
			/*
			Update table prefix.
			RegExp: /\$table_prefix[\s]*=[\s]*('|")(.)*('|");/gi
			pattern: \$table_prefix[\s]*=[\s]*('|")(.)*('|");
			*/
			$pattern[7] = '/\$table_prefix[\s]*=[\s]*(\'|")(.)*(\'|");/i';
			$replace[7] = '$table_prefix = \'' . pb_backupbuddy::$options['db_prefix'] . '\';';
			
			// Perform the actual replacement.
			$lines = preg_replace( $pattern, $replace, $lines );
			
			// Check that we can write to this file.
			if ( !is_writable( ABSPATH . 'wp-config.php' ) ) {
				pb_backupbuddy::alert( 'ERROR: Unable to write to file wp-config.php. Verify this file has proper write permissions.', true, '9020' );
				return $lines;
			}
			
			// Write changes to config file.
			if ( false === ( file_put_contents( ABSPATH . 'wp-config.php', $lines ) ) ) {
				pb_backupbuddy::alert( 'ERROR: Unable to save changes to wp-config.php. Verify this file has proper write permissions.', true, '9020' );
				return $lines;
			}
			
			unset( $lines );
		} else {
			pb_backupbuddy::status( 'warning', 'Warning: wp-config.php file not found.' );
			pb_backupbuddy::alert( 'Note: wp-config.php file not found. This is normal for a database only backup.' );
		}
		
		pb_backupbuddy::status( 'message', 'Migration of wp-config.php complete.' );
		
		return true;
	} // End migrate_wp_config().
	
	
	function get_dat_file_array( $dat_file ) {
		pb_backupbuddy::status( 'details', 'Loading backup dat file.' );
		
		if ( file_exists( $dat_file ) ) {
			$backupdata = file_get_contents( $dat_file );
		} else { // Missing.
			pb_backupbuddy::alert( 'Error #9003: BackupBuddy data file (backupbuddy_dat.php) missing or unreadable. There may be a problem with the backup file, the files could not be extracted (you may manually extract the zip file in this directory to manually do this portion of restore), or the files were deleted before this portion of the restore was reached.  Start the import process over or try manually extracting (unzipping) the files then starting over. Restore will not continue to protect integrity of any existing data.', true, '9003' );
			die( ' Halted.' );
		}
		
		// Unserialize data; If it fails it then decodes the obscufated data then unserializes it. (new dat file method starting at 2.0).
		if ( !is_serialized( $backupdata ) || ( false === ( $return = unserialize( $backupdata ) ) ) ) {
			// Skip first line.
			$second_line_pos = strpos( $backupdata, "\n" ) + 1;
			$backupdata = substr( $backupdata, $second_line_pos );
			
			// Decode back into an array.
			$return = unserialize( base64_decode( $backupdata ) );
		}
		
		pb_backupbuddy::status( 'details', 'Successfully loaded backup dat file.' );
		return $return;
	} // End load_dat_file().
	
	
	// TODO: switch to using pb_backupbuddy::status_box() instead.
	/**
	 *	status_box()
	 *
	 *	Displays a textarea for placing status text into.
	 *
	 *	@param			$default_text	string		First line of text to display.
	 *	@param			boolean			$hidden		Whether or not to apply display: none; CSS.
	 *	@return							string		HTML for textarea.
	 */
	function status_box( $default_text = '', $hidden = false ) {
		define( 'PB_STATUS', true ); // Tells framework status() function to output future logging info into status box via javascript.
		$return = '<textarea readonly="readonly" id="importbuddy_status" wrap="off"';
		if ( $hidden === true ) {
			$return .= ' style="display: none; "';
		}
		$return .= '>' . $default_text . '</textarea>';
		
		return $return;
	}
	
	
	
	
		/**
	 *	connect()
	 *
	 *	Initializes a connection to the mysql database.
	 *
	 *	@return		boolean		True on success; else false. Success testing is very loose.
	 */
	function connect_database() {
		// Set up database connection.
		if ( false === @mysql_connect( pb_backupbuddy::$options['db_server'], pb_backupbuddy::$options['db_user'], pb_backupbuddy::$options['db_password'] ) ) {
			pb_backupbuddy::alert( 'ERROR: Unable to connect to database server and/or log in. Verify the database server name, username, and password. Details: ' . mysql_error(), true, '9006' );
			return false;
		}
		$database_name = mysql_real_escape_string( pb_backupbuddy::$options['db_name'] );
		
		flush();
		
		// Select the database.
		if ( false === @mysql_select_db( pb_backupbuddy::$options['db_name'] ) ) {
			pb_backupbuddy::status( 'error', 'Error: Unable to connect or authenticate to database `' . pb_backupbuddy::$options['db_name'] . '`.' );
			return false;
		}
		
		// Set up character set. Important.
		mysql_query("SET NAMES 'utf8'");
		
		return true;
	}
	
	
	
	/**
	 *	migrate_database()
	 *
	 *	Migrates the already imported database's content for updates ABSPATH and URL.
	 *
	 *	@return		boolean		True=success, False=failed.
	 *
	 */
	function migrate_database() {
		require( 'importbuddy/classes/_migrate_database.php' );
		return $return;
	}
	
	
	
} // End class.
?>