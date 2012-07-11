<?php
// @author Skyler Moore 2011.

pb_backupbuddy::$ui->title( 'Amazon S3' );
		
// S3 information
$aws_accesskey = $destination['accesskey'];
$aws_secretkey = $destination['secretkey'];
$aws_bucket = $destination['bucket'];
$aws_directory = $destination['directory'];
if ( !empty( $aws_directory ) ) {
	$aws_directory = $aws_directory . '/';
}

if ( $destination['ssl'] == '1' ) {
	$s3_ssl = true;
} else {
	$s3_ssl = false;
}


require_once( pb_backupbuddy::plugin_path() . '/lib/s3/s3.php' );
$s3 = new pb_backupbuddy_S3( $aws_accesskey, $aws_secretkey, $s3_ssl );

// Delete S3 backups
if ( !empty( $_POST['delete_file'] ) ) {
	pb_backupbuddy::verify_nonce();
	
	$delete_count = 0;
	if ( !empty( $_POST['files'] ) && is_array( $_POST['files'] ) ) {
		// loop through and delete s3 files
		foreach ( $_POST['files'] as $s3file ) {
			$delete_count++;
			// Delete S3 file
			$s3->deleteObject($aws_bucket, $s3file);
		}
	}
	if ( $delete_count > 0 ) {
		pb_backupbuddy::alert( sprintf( _n('Deleted %d file.', 'Deleted %d files.', $delete_count, 'it-l10n-backupbuddy' ) , $delete_count ) );
	}
}

// Copy S3 backups to the local backup files
if ( !empty( $_GET['copy_file'] ) ) {
	pb_backupbuddy::alert( sprintf( _x('The remote file is now being copied to your %1$slocal backups%2$s', '%1$s and %2$s are open and close <a> tags', 'it-l10n-backupbuddy' ), '<a href="' . pb_backupbuddy::page_url() . '">', '</a>.' ) );
	pb_backupbuddy::status( 'details',  'Scheduling Cron for creating s3 copy.' );
	wp_schedule_single_event( time(), pb_backupbuddy::cron_tag( 'process_s3_copy' ), array( $_GET['copy_file'], $aws_accesskey, $aws_secretkey, $aws_bucket, $aws_directory, $s3_ssl ) );
	spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
	update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
}

echo '<h3>', __('Viewing', 'it-l10n-backupbuddy' ), ' `' . $destination['title'] . '` (' . $destination['type'] . ')</h3>';
?>
<div style="max-width: 950px;">
<form id="posts-filter" enctype="multipart/form-data" method="post" action="<?php echo pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'];?>">
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" name="delete_file" value="<?php _e('Delete from S3', 'it-l10n-backupbuddy' );?>" class="button-secondary delete" />
		</div>
	</div>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<?php
					echo '<th>', __('Backup File',   'it-l10n-backupbuddy' ), '<img src="', pb_backupbuddy::plugin_url(), '/images/sort_down.png" style="vertical-align: 0px;" title="', __('Sorted by filename', 'it-l10n-backupbuddy' ) ,'" /></th>',
						 '<th>', __('Last Modified', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('File Size',     'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Actions',       'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</thead>
		<tfoot>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<?php
					echo '<th>', __('Backup File',   'it-l10n-backupbuddy' ), '<img src="', pb_backupbuddy::plugin_url(), '/images/sort_down.png" style="vertical-align: 0px;" title="', __('Sorted by filename', 'it-l10n-backupbuddy' ) ,'" /></th>',
						 '<th>', __('Last Modified', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('File Size',     'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Actions',       'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</tfoot>
		<tbody>
			<?php
			// List s3 backups
			$results = $s3->getBucket( $aws_bucket);
			
			if ( empty( $results ) ) {
				echo '<tr><td colspan="5" style="text-align: center;"><i>', __('You have not created any S3 backups yet.', 'it-l10n-backupbuddy' ), '</i></td></tr>';
			} else {
				$file_count = 0;
				foreach ( (array) $results as $rekey => $reval ) {
					// check if file is backup
					$pos = strpos( $rekey, $aws_directory . 'backup-' );
					if ( $pos !== FALSE ) {
						$file_count++;
						?>
						<tr class="entry-row alternate">
							<th scope="row" class="check-column"><input type="checkbox" name="files[]" class="entries" value="<?php echo $rekey; ?>" /></th>
							<td>
								<?php
									$bubup = str_replace( $aws_directory . 'backup-', 'backup-', $rekey );
									echo $bubup;
								?>
							</td>
							<td style="white-space: nowrap;">
								<?php
									echo pb_backupbuddy::$format->date( pb_backupbuddy::$format->localize_time( $results[$rekey]['time'] ) );
									echo '<br /><span class="description">(' . pb_backupbuddy::$format->time_ago( $results[$rekey]['time'] ) . ' ago)</span>';
								?>
							</td>
							<td style="white-space: nowrap;">
								<?php echo pb_backupbuddy::$format->file_size( $results[$rekey]['size'] ); ?>
							</td>
							<td>
								<?php echo '<a href="' . pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'] . '&#38;copy_file=' . $bubup . '">', __('Copy to local', 'it-l10n-backupbuddy' ), '</a>'; ?>
							</td>
						</tr>
						<?php
					}
				}
			}
			?>
		</tbody>
	</table>
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" name="delete_file" value="Delete from S3" class="button-secondary delete" />
		</div>
	</div>
	
	<?php pb_backupbuddy::nonce(); ?>
</form><br />
</div>
