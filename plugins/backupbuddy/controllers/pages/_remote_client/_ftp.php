<?php
pb_backupbuddy::$ui->title( 'FTP' );

// FTP connection information
$ftp_server = $destination['address'];
$ftp_username = $destination['username'];
$ftp_password = $destination['password'];
$ftp_directory = $destination['path'];
if ( !empty( $ftp_directory ) ) {
	$ftp_directory = $ftp_directory . '/';
}

$port = '21';
if ( strstr( $ftp_server, ':' ) ) {
	$server_params = explode( ':', $ftp_server );
	
	$ftp_server = $server_params[0];
	$port = $server_params[1];
}

// Delete ftp backups
if ( !empty( $_POST['delete_file'] ) ) {
	
	pb_backupbuddy::verify_nonce();
	
	$delete_count = 0;
	if ( !empty( $_POST['files'] ) && is_array( $_POST['files'] ) ) {
		// connect to server
		$conn_id = ftp_connect( $ftp_server, $port ) or die( __('Could not connect to', 'it-l10n-backupbuddy' ) . ' ' . $ftp_server );
		// login with username and password
		$login_result = ftp_login( $conn_id, $ftp_username, $ftp_password );
		
		ftp_chdir( $conn_id, $ftp_directory );
		
		// loop through and delete ftp backup files
		foreach ( $_POST['files'] as $backup ) {
			// try to delete backup
			if ( ftp_delete( $conn_id,  $backup ) ) {
				$delete_count++;
			}
		}
	
		// close this connection
		ftp_close( $conn_id );
	}
	if ( $delete_count > 0 ) {
		pb_backupbuddy::alert( sprintf( _n( 'Deleted %d file.', 'Deleted %d files.', $delete_count, 'it-l10n-backupbuddy' ), $delete_count ) );
	} else {
		pb_backupbuddy::alert( __('No backups were deleted.', 'it-l10n-backupbuddy' ) );
	}
}

// Copy ftp backups to the local backup files
if ( !empty( $_GET['copy_file'] ) ) {
	pb_backupbuddy::alert( sprintf( _x('The remote file is now being copied to your %1$slocal backups%2$s', '%1$s and %2$s are open and close <a> tags', 'it-l10n-backupbuddy' ), '<a href="' . pb_backupbuddy::page_url() . '">', '</a>.' ) );
	pb_backupbuddy::status( 'details',  'Scheduling Cron for creating ftp copy.' );
	wp_schedule_single_event( time(), pb_backupbuddy::cron_tag( 'process_ftp_copy' ), array( $_GET['copy_file'], $ftp_server, $ftp_username, $ftp_password, $ftp_directory) );
	spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
	update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
}

// Retrieve listing of backups
// Connect to server
$conn_id = ftp_connect( $ftp_server, $port ) or die( __('Could not connect to', 'it-l10n-backupbuddy' ). ' ' . $ftp_server );

// Login with username and password
$login_result = ftp_login( $conn_id, $ftp_username, $ftp_password );
// Get contents of the current directory
$contents = ftp_nlist( $conn_id, $ftp_directory );

// Create array of backups and sizes
$backups = array();
foreach ( $contents as $backup ) {
	// check if file is backup
	$pos = strpos( $backup, 'backup-' );
	if ( $pos !== FALSE ) {
		$backups[$backup] = ftp_size( $conn_id, $ftp_directory . $backup );
	}
}
	
// close this connection
ftp_close( $conn_id );


echo '<h3>', __('Viewing', 'it-l10n-backupbuddy' ), ' `' . $destination['title'] . '` (' . $destination['type'] . ')</h3>';
?>
<div style="max-width: 950px;">
<form id="posts-filter" enctype="multipart/form-data" method="post" action="<?php echo pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'];?>">
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" name="delete_file" value="<?php _e('Delete from FTP', 'it-l10n-backupbuddy' );?>" class="button-secondary delete" />
		</div>
	</div>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<?php
					echo '<th>', __('Backup File', 'it-l10n-backupbuddy' ), '<img src="', pb_backupbuddy::plugin_url(), '/images/sort_down.png" style="vertical-align: 0px;" title="', __('Sorted by filename', 'it-l10n-backupbuddy' ), '" /></th>',
						 '<th>', __('File Size',   'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Actions',     'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</thead>
		<tfoot>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<?php
					echo '<th>', __('Backup File', 'it-l10n-backupbuddy' ), '<img src="', pb_backupbuddy::plugin_url(), '/images/sort_down.png" style="vertical-align: 0px;" title="', __('Sorted by filename', 'it-l10n-backupbuddy' ), '" /></th>',
						 '<th>', __('File Size',   'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Actions',     'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</tfoot>
		<tbody>
			<?php
			// List FTP backups
			if ( empty( $backups ) ) {
				echo '<tr><td colspan="5" style="text-align: center;"><i>', __('This directory does not have any backups.', 'it-l10n-backupbuddy' ), '</i></td></tr>';
			} else {
				$file_count = 0;
				foreach ( (array)$backups as $backup => $size ) {
					$file_count++;
					?>
					<tr class="entry-row alternate">
						<th scope="row" class="check-column"><input type="checkbox" name="files[]" class="entries" value="<?php echo $backup; ?>" /></th>
						<td>
							<?php
								echo $backup;
							?>
						</td>
						<td style="white-space: nowrap;">
							<?php echo pb_backupbuddy::$format->file_size( $size ); ?>
						</td>
						<td>
							<?php echo '<a href="' . pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'] . '&#38;copy_file=' . $backup . '">Copy to local</a>'; ?>
						</td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" name="delete_file" value="<?php _e('Delete from FTP', 'it-l10n-backupbuddy' );?>" class="button-secondary delete" />
		</div>
	</div>
	
	<?php pb_backupbuddy::nonce(); ?>
</form><br />
</div>
