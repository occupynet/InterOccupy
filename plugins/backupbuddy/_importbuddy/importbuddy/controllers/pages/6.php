<?php
$data = array(
	'step'		=>		'6',
);



pb_backupbuddy::set_greedy_script_limits( true );




/*	cleanup()
 *	
 *	Cleans up any temporary files left by importbuddy.
 *	
 *	@return		null
 */
function cleanup() {
	if ( isset( $_POST['delete_backup'] ) && ( $_POST['delete_backup'] == '1' ) ) {
		remove_file( pb_backupbuddy::$options['file'], 'backup .ZIP file (' . pb_backupbuddy::$options['file'] . ')', true );
	}
	
	if ( isset( $_POST['delete_temp'] ) && ( $_POST['delete_temp'] == '1' ) ) {
		// Full backup .sql file
		remove_file( ABSPATH . 'wp-content/uploads/temp_'.pb_backupbuddy::$options['zip_id'].'/db.sql', 'db.sql (backup database dump)', false );
		remove_file( ABSPATH . 'wp-content/uploads/temp_'.pb_backupbuddy::$options['zip_id'].'/db_1.sql', 'db_1.sql (backup database dump)', false );
		remove_file( ABSPATH . 'wp-content/uploads/backupbuddy_temp/'.pb_backupbuddy::$options['zip_id'].'/db_1.sql', 'db_1.sql (backup database dump)', false );
		// DB only sql file
		remove_file( ABSPATH . 'db.sql', 'db.sql (backup database dump)', false );
		remove_file( ABSPATH . 'db_1.sql', 'db_1.sql (backup database dump)', false );
		
		// Full backup dat file
		remove_file( ABSPATH . 'wp-content/uploads/temp_' . pb_backupbuddy::$options['zip_id'] . '/backupbuddy_dat.php', 'backupbuddy_dat.php (backup data file)', false );
		remove_file( ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . pb_backupbuddy::$options['zip_id'] . '/backupbuddy_dat.php', 'backupbuddy_dat.php (backup data file)', false );
		// DB only dat file
		remove_file( ABSPATH . 'backupbuddy_dat.php', 'backupbuddy_dat.php (backup data file)', false );
		
		remove_file( ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . pb_backupbuddy::$options['zip_id'] . '/', 'Temporary backup directory.', false );
		
		remove_file( ABSPATH . 'importbuddy/', 'ImportBuddy Directory', true );
		remove_file( ABSPATH . 'importbuddy/_settings_dat.php', '_settings_dat.php (temporary settings file)', false );
	}
	if ( isset( $_POST['delete_importbuddy'] ) && ( $_POST['delete_importbuddy'] == '1' ) ) {
		remove_file( 'importbuddy.php', 'importbuddy.php (this script)', true );
	}
	// Delete log file last.
	if ( isset( $_POST['delete_importbuddylog'] ) && ( $_POST['delete_importbuddylog'] == '1' ) ) {
		remove_file( 'importbuddy-' . pb_backupbuddy::$options['log_serial'] . '.txt', 'importbuddy-' . pb_backupbuddy::$options['log_serial'] . '.txt log file', true );
	}
}



// Used by cleanup() above.
function remove_file( $file, $description, $error_on_missing = false ) {
	pb_backupbuddy::status( 'message', 'Deleting `' . $description . '`...' );

	@chmod( $file, 0755 ); // High permissions to delete.
	
	if ( is_dir( $file ) ) { // directory.
		pb_backupbuddy::$filesystem->unlink_recursive( $file );
		if ( file_exists( $file ) ) {
			pb_backupbuddy::status( 'error', 'Unable to delete directory: `' . $description . '`. You should manually delete it.' );
		} else {
			pb_backupbuddy::status( 'message', 'Deleted.', false ); // No logging of this action to prevent recreating log.
		}
	} else { // file
		if ( file_exists( $file ) ) {
			if ( @unlink( $file ) != 1 ) {
				pb_backupbuddy::status( 'error', 'Unable to delete file: `' . $description . '`. You should manually delete it.' );
			} else {
				pb_backupbuddy::status( 'message', 'Deleted.', false ); // No logging of this action to prevent recreating log.
			}
		}
	}
} // End remove_file().





if ( $mode == 'html' ) {
	pb_backupbuddy::load_view( 'html_6', $data );
} else { // API mode.
	cleanup();
}
?>