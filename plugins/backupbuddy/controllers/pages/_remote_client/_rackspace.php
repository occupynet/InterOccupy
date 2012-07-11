<?php
pb_backupbuddy::$ui->title( 'Rackspace Cloudfiles' );

echo '<h3>Viewing `' . $destination['title'] . '` (' . $destination['type'] . ')</h3>';
	
	$destination = array_merge( pb_backupbuddy::settings( 'rackspace_defaults' ), $destination ); // load defaults
	
	// Rackspace information
	$rs_username = $destination['username'];
	$rs_api_key = $destination['api_key'];
	$rs_container = $destination['container'];
	$rs_server = $destination['server'];
	/*
	if ( isset( $destination['server'] ) ) {
		$rs_server = $destination['server'];
	} else {
		$rs_server = 'https://auth.api.rackspacecloud.com';
	}
	$rs_path = ''; //$destination['path'];
	*/
	
	require_once( pb_backupbuddy::plugin_path() . '/lib/rackspace/cloudfiles.php');
	$auth = new CF_Authentication( $rs_username, $rs_api_key, NULL, $rs_server );
	$auth->authenticate();
	$conn = new CF_Connection( $auth );
	
	// Set container
	$container = @$conn->get_container($rs_container);
	
	// Delete Rackspace backups
	if ( !empty( $_POST['delete_file'] ) ) {
		pb_backupbuddy::verify_nonce();
		
		$delete_count = 0;
		if ( !empty( $_POST['files'] ) && is_array( $_POST['files'] ) ) {	
			// loop through and delete Rackspace files
			foreach ( $_POST['files'] as $rsfile ) {
				$delete_count++;
				// Delete Rackspace file
				$container->delete_object($rsfile);
			}
		}
		if ( $delete_count > 0 ) {
			pb_backupbuddy::alert( sprintf( _n('Deleted %d file', 'Deleted %d files', $delete_count, 'it-l10n-backupbuddy' ), $delete_count) );
		}
	}
	
	// Copy Rackspace backup to the local backup files
	if ( !empty( $_GET['copy_file'] ) ) {
		pb_backupbuddy::alert( sprintf( _x('The remote file is now being copied to your %1$slocal backups%2$s', '%1$s and %2$s are open and close <a> tags', 'it-l10n-backupbuddy' ), '<a href="' . pb_backupbuddy::page_url() . '">', '</a>.' ) );
		pb_backupbuddy::status( 'details',  'Scheduling Cron for creating Rackspace copy.' );
		wp_schedule_single_event( time(), pb_backupbuddy::cron_tag( 'process_rackspace_copy' ), array( $_GET['copy_file'], $rs_username, $rs_api_key, $rs_container, $rs_server ) );
		spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
		update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
	}
	
	// List objects in container
	/*
	if ( $rs_path != '' ) {
		$results = $container->get_objects( 0, NULL, 'backup-', $rs_path );
	} else {
	*/
		$results = $container->get_objects( 0, NULL, 'backup-');
	/* } */

?>
<div style="max-width: 950px;">
	<form id="posts-filter" enctype="multipart/form-data" method="post" action="<?php echo pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'];?>">
		<div class="tablenav">
			<div class="alignleft actions">
				<input type="submit" name="delete_file" value="Delete from Rackspace" class="button-secondary delete" />
			</div>
		</div>
		<table class="widefat">
			<thead>
				<tr class="thead">
					<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
					<th>Backup File <img src="<?php echo pb_backupbuddy::plugin_url(); ?>/images/sort_down.png" style="vertical-align: 0px;" title="Sorted by filename" /></th>
					<th>Last Modified</th>
					<th>File Size</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tfoot>
				<tr class="thead">
					<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
					<th>Backup File <img src="<?php echo pb_backupbuddy::plugin_url(); ?>/images/sort_down.png" style="vertical-align: 0px;" title="Sorted by filename" /></th>
					<th>Last Modified</th>
					<th>File Size</th>
					<th>Actions</th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				
				if ( empty( $results ) ) {
					echo '<tr><td colspan="5" style="text-align: center;"><i>You have not created any Rackspace backups yet.</i></td></tr>';
				} else {
					$file_count = 0;
					foreach ( (array) $results as $backup ) {
						$file_count++;
						?>
						<tr class="entry-row alternate">
							<th scope="row" class="check-column"><input type="checkbox" name="files[]" class="entries" value="<?php echo $backup->name; ?>" /></th>
							<td><?php echo $backup->name; ?></td>
							<td style="white-space: nowrap;">
								<?php
									echo pb_backupbuddy::$format->date( pb_backupbuddy::$format->localize_time( strtotime($backup->last_modified) ) );
									echo '<br /><span class="description">(' . pb_backupbuddy::$format->time_ago( strtotime($backup->last_modified) ) . ' ago)</span>';
								?>
							</td>
							<td style="white-space: nowrap;">
								<?php echo pb_backupbuddy::$format->file_size( $backup->content_length ); ?>
							</td>
							<td>
								<?php echo '<a href="' . pb_backupbuddy::page_url() . '&custom=' . $_GET['custom'] . '&destination_id=' . $_GET['destination_id'] . '&#38;copy_file=' . $backup->name . '">Copy to local</a>'; ?>
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
				<input type="submit" name="delete_file" value="Delete from Rackspace" class="button-secondary delete" />
			</div>
		</div>
		
		<?php pb_backupbuddy::nonce(); ?>
	</form><br />
</div>
