<?php
if ( !current_user_can( 'activate_plugins' ) ) {
	die( 'Access Denied. Error 3454754.' );
}


// Sanitize a bit.
$_GET['zip_viewer'] = str_replace( '\\', '', pb_backupbuddy::_GET( 'zip_viewer' ) );
$_GET['zip_viewer'] = str_replace( '/', '', pb_backupbuddy::_GET( 'zip_viewer' ) );


// Set up zipbuddy.
if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
	require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
	pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
}


// Make sure we have ziparchive available. File list currently requires it.
if ( !in_array( 'ziparchive', pb_backupbuddy::$classes['zipbuddy']->_zip_methods ) ) {
	pb_backupbuddy::alert( __( 'Error #4455. ZipArchive is not available on your server. Please contact your host for assistance in enabling this.', 'it-l10n-backupbuddy' ), true );
	return false;
}


// Get file listing.
$results = pb_backupbuddy::$classes['zipbuddy']->get_file_list( pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_GET( 'zip_viewer' ) );
if ( $results === false ) {
	pb_backupbuddy::alert( __( 'Error #628855. Error reading ZIP file.', 'it-l10n-backupbuddy' ), true );
} else {
	
	
	// Prettify data.
	foreach( $results as &$result ) {
		$result[1] = pb_backupbuddy::$format->file_size( $result[1] ); // file size.
		$result[2] = pb_backupbuddy::$format->file_size( $result[2] ); // compressed size.
		
		$ago = $result[3];
		$result[3] = pb_backupbuddy::$format->date( $ago ) .
					'<br><span class="description">' . pb_backupbuddy::$format->time_ago( $ago ) . ' ago</span>'; // modified.
	}
	
	
	// Display table.
	pb_backupbuddy::$ui->list_table(
		$results,
		array(
			'action'		=>	pb_backupbuddy::page_url(),
			'columns'		=>	array(
					'File',
					'Size (original)',
					'Compressed Size',
					'Modified',
				),
			//'hover_actions'	=>	$hover_actions,
			//'hover_action_column_key'	=>	'0',
			//'bulk_actions'	=>	array( 'delete_backup' => 'Delete' ),
			'css'			=>		'width: 100%;',
		)
	);

}


// Give a link back to backups.
echo '<br><br><br>';
echo pb_backupbuddy::$ui->button( pb_backupbuddy::page_url(), '&larr; back to backups' );
?>