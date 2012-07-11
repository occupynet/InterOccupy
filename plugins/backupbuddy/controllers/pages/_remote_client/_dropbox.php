<?php
pb_backupbuddy::$ui->title( 'Dropbox' );

require_once( pb_backupbuddy::plugin_path() . '/lib/dropbuddy/dropbuddy.php' );
$dropbuddy = new pb_backupbuddy_dropbuddy( $destination['token'] );
if ( $dropbuddy->authenticate() === true ) {
	$account_info = $dropbuddy->get_account_info();
} else {
	$account_info = false;
}

$meta_data = $dropbuddy->get_meta_data( $destination['directory'] );


/*
echo '<pre>';
print_r( $meta_data ) );
echo '</pre>';
*/

// Delete dropbox backups
if ( !empty( $_POST['delete_file'] ) ) {
	pb_backupbuddy::verify_nonce();
	$delete_count = 0;
	if ( !empty( $_POST['files'] ) && is_array( $_POST['files'] ) ) {
		// loop through and delete dropbox files
		foreach ( $_POST['files'] as $dropboxfile ) {
			$delete_count++;
			// Delete dropbox file
			$dropbuddy->delete( $dropboxfile );
		}
	}
	if ( $delete_count > 0 ) {
		pb_backupbuddy::alert( sprintf( _n('Deleted %d file', 'Deleted %d files', $delete_count, 'it-l10n-backupbuddy' ), $delete_count) );
		$meta_data = $dropbuddy->get_meta_data( $destination['directory'] ); // Refresh listing.
	}
}

// Copy dropbox backups to the local backup files
if ( !empty( $_GET['copy_file'] ) ) {
	pb_backupbuddy::alert( sprintf( _x('The remote file is now being copied to your %1$slocal backups%2$s', '%1$s and %2$s are open and close <a> tags', 'it-l10n-backupbuddy' ), '<a href="' . pb_backupbuddy::page_url() . '">', '</a>.' ) );
	pb_backupbuddy::status( 'details',  'Scheduling Cron for creating Dropbox copy.' );
	wp_schedule_single_event( time(), pb_backupbuddy::cron_tag( 'process_dropbox_copy' ), array( $_GET['destination_id'], $_GET['copy_file'] ) );
	spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
	update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
}

echo '<h3>', __('Viewing', 'it-l10n-backupbuddy' ),' `' . $destination['title'] . '` (' . $destination['type'] . ')</h3>';
?>

<div style="max-width: 950px;">
<form id="posts-filter" enctype="multipart/form-data" method="post" action="<?php echo pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'];?>">
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" name="delete_file" value="<?php _e('Delete from Dropbox', 'it-l10n-backupbuddy' );?>" class="button-secondary delete" />
		</div>
	</div>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<?php 
					echo '<th>', __('Backup File', 'it-l10n-backupbuddy' ), '<img src="', pb_backupbuddy::plugin_url(), '/images/sort_down.png" style="vertical-align: 0px;" title="', __('Sorted by filename', 'it-l10n-backupbuddy' ), '" /></th>',
						 '<th>', __('Last Modified', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('File Size', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Actions', 'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</thead>
		<tfoot>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<?php
					echo '<th>', __('Backup File', 'it-l10n-backupbuddy' ), '<img src="', pb_backupbuddy::plugin_url(), '/images/sort_down.png" style="vertical-align: 0px;" title="', __('Sorted by filename', 'it-l10n-backupbuddy' ), '" /></th>',
						 '<th>', __('Last Modified', 'it-l10n-backupbuddy' ),'</th>',
						 '<th>', __('File Size', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Actions', 'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</tfoot>
		<tbody>
			<?php
			// List dropbox backups
			if ( empty( $meta_data['contents'] ) ) {
				echo '<tr><td colspan="5" style="text-align: center;"><i>', __('You have not created any dropbox backups yet.', 'it-l10n-backupbuddy' ) ,' </i></td></tr>';
			} else {
				$file_count = 0;
				foreach ( (array) $meta_data['contents'] as $file ) {
					// check if file is backup
					if ( strstr( $file['path'], 'backup-' ) ) {
						$file_count++;
						?>
						<tr class="entry-row alternate">
							<th scope="row" class="check-column"><input type="checkbox" name="files[]" class="entries" value="<?php echo $file['path']; ?>" /></th>
							<td>
								<?php
									echo str_replace( '/' . $destination['directory'] . '/', '', $file['path'] );
								?>
							</td>
							<td style="white-space: nowrap;">
								<?php
									$modified = strtotime( $file['modified'] );
									echo pb_backupbuddy::$format->date( pb_backupbuddy::$format->localize_time( $modified ) );
									echo '<br /><span class="description">(' . pb_backupbuddy::$format->time_ago( $modified ) . ' ', __('ago', 'it-l10n-backupbuddy' ), ')</span>';
								?>
							</td>
							<td style="white-space: nowrap;">
								<?php echo pb_backupbuddy::$format->file_size( $file['bytes'] ); ?>
							</td>
							<td>
								<?php echo '<a href="' . pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'] . '&#38;copy_file=' . $file['path'] . '">',__('Copy to local', 'it-l10n-backupbuddy' ), '</a>'; ?>
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
			<input type="submit" name="delete_file" value="<?php _e('Delete from Dropbox', 'it-l10n-backupbuddy' );?>" class="button-secondary delete" />
		</div>
	</div>
	
	<?php pb_backupbuddy::nonce(); ?>
</form><br />
</div>
