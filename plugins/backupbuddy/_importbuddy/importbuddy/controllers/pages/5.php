<?php
$data = array(
	'step'		=>		'5',
);



pb_backupbuddy::set_greedy_script_limits( true );




/**
 *	migrate_database()
 *
 *	Connects database and performs migration of DB content. Handles skipping.
 *
 *	@return		null
 */
function migrate_database() {
	pb_backupbuddy::$classes['import']->connect_database();
	
	if ( false === pb_backupbuddy::$options['skip_database_migration'] ) {
		return pb_backupbuddy::$classes['import']->migrate_database();
	} else {
		pb_backupbuddy::status( 'message', 'Skipping database migration based on settings.' );
		return true;
	}
}


/**
 *	migrate_wp_config()
 *
 *	Passthrough for suture use; trying to funnel all essential migration steps through the API files.
 *
 *	@return		true on success, new wp config file content on failure.
 */
function migrate_wp_config() {
	if ( isset( pb_backupbuddy::$options['dat_file']['wp-config_in_parent'] ) ) {
		if ( pb_backupbuddy::$options['dat_file']['wp-config_in_parent'] === true ) { // wp-config.php used to be in parent. Must copy from temp dir to root.
			pb_backupbuddy::status( 'details', 'DAT file indicates wp-config.php was previously in the parent directory. Copying into site root.' );
			
			$config_source = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . pb_backupbuddy::$options['zip_id'] . '/wp-config.php';
			$result = copy( $config_source, ABSPATH . 'wp-config.php' );
			if ( $result === true ) {
				pb_backupbuddy::status( 'message', 'wp-config.php file was restored to the root of the site `' . ABSPATH . 'wp-config.php`. It was previously in the parent directory of the source site. You may move it manually to the parent directory.' );
			} else {
				pb_backupbuddy::status( 'error', 'Unable to move wp-config.php file from temporary location `' . $config_source . '` to root.' );
			}
			
		} else { // wp-config.php was in normal location on source site. Nothing to do.
			pb_backupbuddy::status( 'details', 'DAT file indicates wp-config.php was previously in the normal location.' );
		}
	} else { // Pre 3.0 backup
		pb_backupbuddy::status( 'details', 'Backup pre-v3.0 so wp-config.php must be in normal location.' );
	}
	
	return pb_backupbuddy::$classes['import']->migrate_wp_config();
}


/*	verify_database()
 *	
 *	Verify various contents of the database after all migration is complete.
 *	
 *	@param		
 *	@return		
 */
function verify_database() {
	
	pb_backupbuddy::$classes['import']->connect_database();
	$db_prefix = pb_backupbuddy::$options['db_prefix'];
	
	// Check site URL.
	$result = mysql_query( "SELECT option_value FROM `{$db_prefix}options` WHERE option_name='siteurl' LIMIT 1" );
	if ( $result === false ) {
		pb_backupbuddy::status( 'error', 'Unable to retrieve siteurl from database. A portion of the database may not have imported (or with the wrong prefix).' );
	} else {
		while( $row = mysql_fetch_row( $result ) ) {
			pb_backupbuddy::status( 'details', 'Final site URL: `' . $row[0] . '`.' );
		}
		mysql_free_result( $result ); // Free memory.
	}
	
	// Check home URL.
	$result = mysql_query( "SELECT option_value FROM `{$db_prefix}options` WHERE option_name='home' LIMIT 1" );
	if ( $result === false ) {
		pb_backupbuddy::status( 'error', 'Unable to retrieve home [url] from database. A portion of the database may not have imported (or with the wrong prefix).' );
	} else {
		while( $row = mysql_fetch_row( $result ) ) {
			pb_backupbuddy::status( 'details', 'Final home URL: `' . $row[0] . '`.' );
		}
	}
	mysql_free_result( $result ); // Free memory.
	
	// Verify media upload path.
	$result = mysql_query( "SELECT option_value FROM `{$db_prefix}options` WHERE option_name='upload_path' LIMIT 1" );
	if ( $result === false ) {
		pb_backupbuddy::status( 'error', 'Unable to retrieve upload_path from database. A portion of the database may not have imported (or with the wrong prefix).' );
		$media_upload_path = '{ERR_34834984-UNKNOWN}';
	} else {
		while( $row = mysql_fetch_row( $result ) ) {
			$media_upload_path = $row[0];
		}
	}
	mysql_free_result( $result ); // Free memory.
	
	pb_backupbuddy::status( 'details', 'Media upload path in database options table: `' . $media_upload_path . '`.' );
	if ( substr( $media_upload_path, 0, 1 ) == '/' ) { // Absolute path.
		if ( !file_exists( $media_upload_path ) ) { // Media path does not exist.
			$media_upload_message = 'Your media upload path is assigned a directory which does not appear to exist on this server. Please verify it is correct in your WordPress settings. Current path: `' . $media_upload_path . '`.';
			pb_backupbuddy::alert( $media_upload_message );
			pb_backupbuddy::status( 'warning', $media_upload_message );
		} else { // Media path does exist.
			pb_backupbuddy::status( 'details', 'Your media upload path is assigned an absolute path which appears to be correct.' );
		}
	} else { // Relative path.
		pb_backupbuddy::status( 'details', 'Your media upload path is assigned a relative path; validity not tested.' );
	}
	
} // End verify_database().



/*	rename_htaccess_temp_back()
 *	
 *	Renames .htaccess to .htaccess.bb_temp until last ImportBuddy step to avoid complications.
 *	
 *	@return		null
 */
function rename_htaccess_temp_back() {
	
	if ( !file_exists( ABSPATH . '.htaccess.bb_temp' ) ) {
		pb_backupbuddy::status( 'details', 'No `.htaccess.bb_temp` file found. Skipping temporary file rename.' );
	}
	
	$result = @rename( ABSPATH . '.htaccess.bb_temp', ABSPATH . '.htaccess' );
	if ( $result === true ) { // Rename succeeded.
		pb_backupbuddy::status( 'message', 'Renamed `.htaccess.bb_temp` file to `.htaccess` until final ImportBuddy step.' );
	} else { // Rename failed.
		pb_backupbuddy::status( 'error', 'Unable to rename `.htaccess.bb_temp` file to `.htaccess`. Your file permissions may be too strict. You may wish to manually rename this file and/or check permissions before proceeding.' );
	}
	
} // End rename_htaccess_temp_back().



if ( $mode == 'html' ) {
	pb_backupbuddy::load_view( 'html_5', $data );
} else { // API mode.
	$result = migrate_database();
	if ( $result === true ) {
		migrate_wp_config();
		verify_database();
	}
}
?>