<?php
pb_backupbuddy::set_greedy_script_limits( true );



$data = array(
	'detected_max_execution_time'		=>		30,
	'backup_archives'					=>		get_archives_list(),
	'wordpress_exists'					=>		wordpress_exists(),
	'step'								=>		'1',
);


$detected_max_execution_time = str_ireplace( 's', '', ini_get( 'max_execution_time' ) );
if ( is_numeric( $detected_max_execution_time ) ) {
	$data['detected_max_execution_time'] = $detected_max_execution_time;
}


/**
 *	upload()
 *
 *	Processes uploaded backup file.
 *
 *	@return		array		True on upload success; false otherwise.
 */
function upload() {
	if ( isset( $_POST['upload'] ) && ( $_POST['upload'] == 'local' ) ) {
		if ( pb_backupbuddy::$options['password'] != '#PASSWORD#' ) {
			$path_parts = pathinfo( $_FILES['file']['name'] );
			if ( ( strtolower( substr( $_FILES['file']['name'], 0, 6 ) ) == 'backup' ) && ( strtolower( $path_parts['extension'] ) == 'zip' ) ) {
				if ( move_uploaded_file( $_FILES['file']['tmp_name'], basename( $_FILES['file']['name'] ) ) ) {
					pb_backupbuddy::alert( 'File Uploaded. Your backup was successfully uploaded.' );
					return true;
				} else {
					pb_backupbuddy::alert( 'Sorry, there was a problem uploading your file.', true );
					return false;
				}
			} else {
				pb_backupbuddy::alert( 'Only properly named BackupBuddy zip archives with a zip extension may be uploaded.', true );
				return false;
			}
		} else {
			pb_backupbuddy::alert( 'Upload Access Denied. To prevent unauthorized file uploads an importbuddy password must be configured and properly entered to use this feature.' );
			return false;
		}
	}
}


/**
 *	get_archives_list()
 *
 *	Returns an array of backup archive zip filenames found.
 *
 *	@return		array		Array of .zip filenames; path NOT included.
 */
function get_archives_list() {
	if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
		require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
		pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( ABSPATH );
	}
	
	// List backup files in this directory.
	$backup_archives_glob = glob( ABSPATH . 'backup*.zip' );
	if ( !is_array( $backup_archives_glob ) || empty( $backup_archives_glob ) ) { // On failure glob() returns false or an empty array depending on server settings so normalize here.
		$backup_archives_glob = array();
	}
	foreach( $backup_archives_glob as $backup_archive ) {
		$comment = pb_backupbuddy::$classes['zipbuddy']->get_comment( $backup_archive );
		if ( $comment === false ) {
			$comment = '';
		}
		
		$this_archive = array(
			'file'		=>		basename( $backup_archive ),
			'comment'	=>		$comment,
		);
		$backup_archives[] = $this_archive;
	}
	unset( $backup_archives_glob );
	
	
	return $backup_archives;
}


/**
 *	wordpress_exists()
 *
 *	Notifies the user with an alert if WordPress appears to already exist in this directory.
 *
 *	@return		boolean		True if WordPress already exists; false otherwise.
 */
function wordpress_exists() {
	if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
		return true;
	} else {
		return false;
	}
}


function phpini_exists() {
	return file_exists( ABSPATH . 'php.ini' );
}


function htaccess_exists() {
	return file_exists( ABSPATH . '.htaccess' );
}




if ( $mode == 'html' ) {
	pb_backupbuddy::load_view( 'html_1', $data );
} else { // API mode.
	upload();
	if ( wordpress_exists() === true ) {
	}
	if ( phpini_exists() === true ) {
	}
	if ( htaccess_exists() === true ) {
	}
}
?>