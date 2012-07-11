<?php
	if ( !empty( pb_backupbuddy::$options['remote_destinations'][$_GET['destination_id']] ) ) {
		$destination = &pb_backupbuddy::$options['remote_destinations'][$_GET['destination_id']];
	} else {
		echo __('Error #438934894349. Invalid destination ID.', 'it-l10n-backupbuddy' );
	}
	
	if ( $destination['type'] == 's3' ) {
		require( pb_backupbuddy::plugin_path() . '/controllers/pages/_remote_client/_s3.php' );
	} elseif ( $destination['type'] == 'rackspace' ) {
		require( pb_backupbuddy::plugin_path() . '/controllers/pages/_remote_client/_rackspace.php' );
	} elseif ( $destination['type'] == 'ftp' ) {
		require( pb_backupbuddy::plugin_path() . '/controllers/pages/_remote_client/_ftp.php' );
	} elseif ( $destination['type'] == 'dropbox' ) {
		require( pb_backupbuddy::plugin_path() . '/controllers/pages/_remote_client/_dropbox.php' );
	} else {
		echo __('Sorry, a remote client is not available for this destination at this time.', 'it-l10n-backupbuddy' );
	}
?>
