<?php

class pb_backupbuddy_cron extends pb_backupbuddy_croncore {
	
	function process_backup( $serial = 'blank' ) {
		pb_backupbuddy::set_status_serial( $serial );
		pb_backupbuddy::set_greedy_script_limits();
		pb_backupbuddy::status( 'message', 'Processing cron step for serial `' . $serial . '`...' );
		
		if ( !isset( pb_backupbuddy::$classes['backup'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/backup.php' );
			pb_backupbuddy::$classes['backup'] = new pb_backupbuddy_backup();
		}
		pb_backupbuddy::$classes['backup']->process_backup( $serial );
	}
	
	
	
	// Cleanup final remaining bits post backup. Handled here so log file can be accessed by AJAX temporarily after backup.
	// Also called when finished_backup action is seen being sent to AJAX signalling we can clear it NOW since AJAX is done.
	// Also pre_backup() of backup.php schedules this 6 hours in the future of the backup in case of failure.
	public function final_cleanup( $serial ) {
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core( $serial );
		}
		
		pb_backupbuddy::$classes['core']->final_cleanup( $serial );
		
	} // End final_cleanup().
	
	
	
	// @param	string		$trigger	What triggered this backup. Valid values: scheduled, manual.
	public function remote_send( $destination, $file, $trigger, $send_importbuddy = false ) {
		pb_backupbuddy::set_greedy_script_limits();
		pb_backupbuddy::status( 'message', 'Sending `' . $file . '` to remote destination `' . $destination . '`.' );
		
		if ( !isset( pb_backupbuddy::$options ) ) {
			pb_backupbuddy::load();
		}
		
		pb_backupbuddy::status( 'details', 'Setting greedy script limits.' );
		pb_backupbuddy::set_greedy_script_limits();
		
		pb_backupbuddy::status( 'details', 'Launching remote send.' );
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		pb_backupbuddy::$classes['core']->send_remote_destination( $destination, $file, $trigger, $send_importbuddy );
	}
	
	
	
	
	// Eventually pull these out so that all remote destinations are isolated to themselves in classes for each destination.
	
	// Copy a remote S3 backup to local backup directory
	// $ssl boolean
	function process_s3_copy( $s3file, $accesskey, $secretkey, $bucket, $directory, $ssl ) {
		pb_backupbuddy::status( 'details', 'Copying remote S3 file `' . $s3file . '` down to local.' );
		pb_backupbuddy::set_greedy_script_limits();
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/s3/s3.php');
		$s3 = new pb_backupbuddy_S3( $accesskey, $secretkey, $ssl );
		
		$destination_file = ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . $s3file;
		if ( file_exists( $destination_file ) ) {
			$destination_file = str_replace( 'backup-', 'backup_copy_' . pb_backupbuddy::random_string( 5 ) . '-', $destination_file );
		}
		
		pb_backupbuddy::status( 'details', 'About to get S3 object...' );
		$s3->getObject($bucket, $directory . $s3file, $destination_file );
		pb_backupbuddy::status( 'details', 'S3 object retrieved.' );
	} // End process_s3_copy().
	
	
	
	// Copy Dropbox backup to local backup directory
	function process_dropbox_copy( $destination_id, $file ) {
		pb_backupbuddy::set_greedy_script_limits();
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/dropbuddy/dropbuddy.php' );
		$dropbuddy = new pb_backupbuddy_dropbuddy( pb_backupbuddy::$options['remote_destinations'][$destination_id]['token'] );
		if ( $dropbuddy->authenticate() !== true ) {
			if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
				require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
				pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
			}
			pb_backupbuddy::$classes['core']->mail_error( 'Dropbox authentication failed in cron_process_dropbox_copy.' );
			return false;
		}
		
		$destination_file = ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . $file;
		if ( file_exists( $destination_file ) ) {
			$destination_file = str_replace( 'backup-', 'backup_copy_' . pb_backupbuddy::random_string( 5 ) . '-', $destination_file );
		}
		
		pb_backupbuddy::status( 'error', 'About to get object (the file) from Dropbox cron.' );
		file_put_contents( $destination_file, $dropbuddy->get_file( $file ) );
		pb_backupbuddy::status( 'error', 'Got object from Dropbox cron.' );
	}
	
	// Copy Rackspace backup to local backup directory
	function process_rackspace_copy( $rs_backup, $rs_username, $rs_api_key, $rs_container, $rs_server ) {
		pb_backupbuddy::set_greedy_script_limits();
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/rackspace/cloudfiles.php' );
		$auth = new CF_Authentication( $rs_username, $rs_api_key, NULL, $rs_server );
		$auth->authenticate();
		$conn = new CF_Connection( $auth );

		// Set container
		$container = $conn->get_container( $rs_container );
		
		// Get file from Rackspace
		$rsfile = $container->get_object( $rs_backup );
		
		$destination_file = ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . $rs_backup;
		if ( file_exists( $destination_file ) ) {
			$destination_file = str_replace( 'backup-', 'backup_copy_' . pb_backupbuddy::random_string( 5 ) . '-', $destination_file );
		}
		
		$fso = fopen( ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . $rs_backup, 'w' );
		$rsfile->stream($fso);
		fclose($fso);
	}
	
	
	// Copy FTP backup to local backup directory
	function process_ftp_copy( $backup, $ftp_server, $ftp_username, $ftp_password, $ftp_directory ) {
		pb_backupbuddy::set_greedy_script_limits();
		
		// connect to server
		$conn_id = ftp_connect( $ftp_server ) or die( 'Could not connect to ' . $ftp_server );
		// login with username and password
		$login_result = ftp_login( $conn_id, $ftp_username, $ftp_password );
	
		// try to download $server_file and save to $local_file
		$destination_file = ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . $backup;
		if ( file_exists( $destination_file ) ) {
			$destination_file = str_replace( 'backup-', 'backup_copy_' . pb_backupbuddy::random_string( 5 ) . '-', $destination_file );
		}
		if ( ftp_get( $conn_id, $destination_file, $ftp_directory . $backup, FTP_BINARY ) ) {
		    pb_backupbuddy::status( 'message', 'Successfully wrote remote file locally to `' . $destination_file . '`.' );
		} else {
		    pb_backupbuddy::status( 'error', 'Error writing remote file locally to `' . $destination_file . '`.' );
		}
	
		// close this connection
		ftp_close( $conn_id );
	}
	
	
}
?>