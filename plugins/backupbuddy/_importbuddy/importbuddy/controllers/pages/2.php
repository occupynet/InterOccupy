<?php
$data = array(
	'step'		=>		'2',
);



pb_backupbuddy::set_greedy_script_limits( true );



parse_options();







/**
 *	parse_options()
 *
 *	Parses various submitted options and settings from step 1.
 *
 *	@return		null
 */
function parse_options() {
	// Set advanced debug options if user set any.
	if ( ( isset( $_POST['skip_files'] ) ) && ( $_POST['skip_files'] == 'on' ) ) { pb_backupbuddy::$options['skip_files'] = true; }
	if ( ( isset( $_POST['skip_database_import'] ) ) && ( $_POST['skip_database_import'] == 'on' ) ) { pb_backupbuddy::$options['skip_database_import'] = true; }
	if ( ( isset( $_POST['skip_database_migration'] ) ) && ( $_POST['skip_database_migration'] == 'on' ) ) { pb_backupbuddy::$options['skip_database_migration'] = true; }
	if ( ( isset( $_POST['mysqlbuddy_compatibility'] ) ) && ( $_POST['mysqlbuddy_compatibility'] == 'on' ) ) { pb_backupbuddy::$options['mysqlbuddy_compatibility'] = true; }
	if ( ( isset( $_POST['wipe_database'] ) ) && ( $_POST['wipe_database'] == 'on' ) ) { pb_backupbuddy::$options['wipe_database'] = true; }
	if ( ( isset( $_POST['wipe_database_all'] ) ) && ( $_POST['wipe_database_all'] == 'on' ) ) { pb_backupbuddy::$options['wipe_database_all'] = true; }
	if ( ( isset( $_POST['skip_htaccess'] ) ) && ( $_POST['skip_htaccess'] == 'on' ) ) { pb_backupbuddy::$options['skip_htaccess'] = true; }
	if ( ( isset( $_POST['ignore_sql_errors'] ) ) && ( $_POST['ignore_sql_errors'] == 'on' ) ) { pb_backupbuddy::$options['ignore_sql_errors'] = true; }
	if ( ( isset( $_POST['force_compatibility_medium'] ) ) && ( $_POST['force_compatibility_medium'] == 'on' ) ) { pb_backupbuddy::$options['force_compatibility_medium'] = true; }
	if ( ( isset( $_POST['force_compatibility_slow'] ) ) && ( $_POST['force_compatibility_slow'] == 'on' ) ) { pb_backupbuddy::$options['force_compatibility_slow'] = true; }
	if ( ( isset( $_POST['force_high_security'] ) ) && ( $_POST['force_high_security'] == 'on' ) ) { pb_backupbuddy::$options['force_high_security'] = true; }
	if ( ( isset( $_POST['show_php_warnings'] ) ) && ( $_POST['show_php_warnings'] == 'on' ) ) { pb_backupbuddy::$options['show_php_warnings'] = true; }
	if ( ( isset( $_POST['file'] ) ) && ( $_POST['file'] != '' ) ) { pb_backupbuddy::$options['file'] = $_POST['file']; }
	if ( ( isset( $_POST['max_execution_time'] ) ) && ( is_numeric( $_POST['max_execution_time'] ) ) ) {
		pb_backupbuddy::$options['max_execution_time'] = $_POST['max_execution_time'];
	} else {
		pb_backupbuddy::$options['max_execution_time'] = 30;
	}
	if ( ( isset( $_POST['log_level'] ) ) && ( $_POST['log_level'] != '' ) ) { pb_backupbuddy::$options['log_level'] = $_POST['log_level']; }
	
	// Set ZIP id (aka serial).
	pb_backupbuddy::$options['zip_id'] = pb_backupbuddy::$classes['core']->get_serial_from_file( pb_backupbuddy::$options['file'] );
}


/**
 *	extract()
 *
 *	Extract backup zip file.
 *
 *	@return		array		True if the extraction was a success OR skipping of extraction is set.
 */
function extract_files() {
	if ( true === pb_backupbuddy::$options['skip_files'] ) { // Option to skip all file updating / extracting.
		pb_backupbuddy::status( 'message', 'Skipped extracting files based on debugging options.' );
		return true;
	} else {
		pb_backupbuddy::set_greedy_script_limits();
		
		pb_backupbuddy::status( 'message', 'Unzipping into `' . ABSPATH . '`' );
		
		$backup_archive = ABSPATH . pb_backupbuddy::$options['file'];
		$destination_directory = ABSPATH;
		
		// Set compatibility mode if defined in advanced options.
		$compatibility_mode = false; // Default to no compatibility mode.
		if ( pb_backupbuddy::$options['force_compatibility_medium'] != false ) {
			$compatibility_mode = 'ziparchive';
		} elseif ( pb_backupbuddy::$options['force_compatibility_slow'] != false ) {
			$compatibility_mode = 'pclzip';
		}
		
		// Zip & Unzip library setup.
		require_once( ABSPATH . 'importbuddy/lib/zipbuddy/zipbuddy.php' );
		$_zipbuddy = new pluginbuddy_zipbuddy( ABSPATH, '', 'unzip' );
		
		// Extract zip file & verify it worked.
		if ( true !== ( $result = $_zipbuddy->unzip( $backup_archive, $destination_directory, $compatibility_mode ) ) ) {
			pb_backupbuddy::status( 'error', 'Failed unzipping archive.' );
			pb_backupbuddy::alert( 'Failed unzipping archive.', true );
			return false;
		} else { // Reported success; verify extraction.
			$_backupdata_file = ABSPATH . 'wp-content/uploads/temp_' . pb_backupbuddy::$options['zip_id'] . '/backupbuddy_dat.php'; // Full backup dat file location
			$_backupdata_file_dbonly = ABSPATH . 'backupbuddy_dat.php'; // DB only dat file location
			$_backupdata_file_new = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . pb_backupbuddy::$options['zip_id'] . '/backupbuddy_dat.php'; // Full backup dat file location
			if ( !file_exists( $_backupdata_file ) && !file_exists( $_backupdata_file_dbonly ) && !file_exists( $_backupdata_file_new ) ) {
				pb_backupbuddy::status( 'error', 'Error #9004: Key files missing.', 'The unzip process reported success but the backup data file, backupbuddy_dat.php was not found in the extracted files. The unzip process either failed (most likely) or the zip file is not a proper BackupBuddy backup.' );
				pb_backupbuddy::alert( 'Error: Key files missing. The unzip process reported success but the backup data file, backupbuddy_dat.php was not found in the extracted files. The unzip process either failed (most likely) or the zip file is not a proper BackupBuddy backup.', true, '9004' );
				return false;
			}
			pb_backupbuddy::status( 'details', 'Success extracting Zip File "' . ABSPATH . pb_backupbuddy::$options['file'] . '" into "' . ABSPATH . '".' );
			return true;
		}
	}
}



/**
 *	load_backup_dat()
 *
 *	Gets the serialized data from the backupbuddy_dat.php file inside of the backup ZIP.
 *	This happens post-file-extraction.
 *
 *	Saves data to $this->_backupdata.
 *
 *	@return			null
 *
 */	
function get_dat_from_backup() {
	$maybe_backupdata_file = ABSPATH . 'wp-content/uploads/temp_'. pb_backupbuddy::$options['zip_id'] .'/backupbuddy_dat.php'; // Full backup dat file location
	$maybe_backupdata_file_new = ABSPATH . 'wp-content/uploads/backupbuddy_temp/'. pb_backupbuddy::$options['zip_id'] .'/backupbuddy_dat.php'; // Full backup dat file location
	
	if ( file_exists( $maybe_backupdata_file ) ) { // Full backup location.
		$dat_file = $maybe_backupdata_file;
	} elseif ( file_exists( $maybe_backupdata_file_new ) ) { // Full backup location.
		$dat_file = $maybe_backupdata_file_new;
	} elseif ( file_exists( ABSPATH . 'backupbuddy_dat.php' ) ) { // DB only location.
		$dat_file = ABSPATH . 'backupbuddy_dat.php';
	} else {
		$dat_file = '';
		echo 'Error: Unable to find DAT file. Verify you did not rename the backup archive ZIP filename.';
	}
	
	if ( $dat_file != '' ) {
		pb_backupbuddy::$options['dat_file'] = pb_backupbuddy::$classes['import']->get_dat_file_array( $dat_file );
	}
	pb_backupbuddy::save();
}



/*	rename_htaccess_temp()
 *	
 *	Renames .htaccess to .htaccess.bb_temp until last ImportBuddy step to avoid complications.
 *	
 *	@return		null
 */
function rename_htaccess_temp() {
	
	if ( !file_exists( ABSPATH . '.htaccess' ) ) {
		pb_backupbuddy::status( 'details', 'No .htaccess file found. Skipping temporary file rename.' );
	}
	
	$result = @rename( ABSPATH . '.htaccess', ABSPATH . '.htaccess.bb_temp' );
	if ( $result === true ) { // Rename succeeded.
		pb_backupbuddy::status( 'message', 'Renamed `.htaccess` file to `.htaccess.bb_temp` until final ImportBuddy step.' );
	} else { // Rename failed.
		pb_backupbuddy::status( 'warning', 'Unable to rename `.htaccess` file to `.htaccess.bb_temp`. Your file permissions may be too strict. You may wish to manually rename this file and/or check permissions before proceeding.' );
	}
	
} // End rename_htaccess_temp().







if ( $mode == 'html' ) {
	pb_backupbuddy::load_view( 'html_2', $data );
} else { // API mode.
	extract();
	get_dat_from_backup();
}
?>