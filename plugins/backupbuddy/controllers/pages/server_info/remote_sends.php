<?php

pb_backupbuddy::$classes['core']->trim_remote_send_stats();

$remote_sends = array_reverse( pb_backupbuddy::$options['remote_sends'] ); // Reverse array so most recent is first.

$sends = array();
foreach( $remote_sends as $remote_send ) {
	
	// Set up some variables based on whether file finished sending yet or not.
	if ( $remote_send['finish_time'] > 0 ) { // Finished sending.
		$time_ago = pb_backupbuddy::$format->time_ago( $remote_send['finish_time'] ) . ' ago; took ';
		$duration = pb_backupbuddy::$format->time_duration( $remote_send['finish_time'] - $remote_send['start_time'] );
		$finish_time = pb_backupbuddy::$format->date( $remote_send['finish_time'] );
	} else { // Did not finish (yet?).
		$time_ago = 'Not finished sending.';
		$duration = '';
		$finish_time = '<span class="description">Unknown</span>';
	}
	
	if ( isset( $remote_send['send_importbuddy'] ) && ( $remote_send['send_importbuddy'] === true ) ) {
		$send_importbuddy = '<br><span class="description">+ importbuddy.php</span>';
	} else {
		$send_importbuddy = '';
	}
	
	// Status verbage & styling based on send status.
	if ( $remote_send['status'] == 'success' ) {
		$status = 'Success';
	} elseif ( $remote_send['status'] == 'timeout' ) {
		$status = '<font color=red>Timeout or still sending</font>';
	} else {
		$status = '<font color=red>' . ucfirst( $remote_send['status'] ) . '</font>';
	}
	
	// Determine destination.
	if ( isset( pb_backupbuddy::$options['remote_destinations'][$remote_send['destination']] ) ) { // Valid destination.
		$destination = pb_backupbuddy::$options['remote_destinations'][$remote_send['destination']]['title'] . ' (' . pb_backupbuddy::$options['remote_destinations'][$remote_send['destination']]['type'] . ')';
	} else { // Invalid destination (been deleted since send?).
		$destination = '<span class="description">Unknown</span>';
	}
	
	// Push into array.
	$sends[] = array(
		basename( $remote_send['file'] ) . $send_importbuddy,
		$destination,
		$status,
		$remote_send['trigger'],
		'Start: ' . pb_backupbuddy::$format->date( $remote_send['start_time'] ) . '<br>' .
		'Finish: ' . $finish_time . '<br>' .
		'<span class="description">' . $time_ago  . $duration . '</span>',
	);
} // End foreach.


if ( count( $sends ) == 0 ) {
	echo '<br>' . __( 'There have been no recent remote file transfers.', 'it-l10n-backupbuddy' ) . '<br>';
} else {
	?>
	Below are the most recent remote file transfers. Statistics and logging are only kept for the most recent 10 backups.
	Please note that these statistics are only available for backups made since BackupBuddy version 3.0.
	<br><br>
	<?php
	pb_backupbuddy::$ui->list_table(
		$sends,
		array(
			'action'		=>	pb_backupbuddy::page_url(),
			'columns'		=>	array(
				__( 'Backup File', 'it-l10n-backupbuddy' ),
				__( 'Destination', 'it-l10n-backupbuddy' ),
				__( 'Status', 'it-l10n-backupbuddy' ),
				__( 'Trigger', 'it-l10n-backupbuddy' ),
				__( 'Transfer Time', 'it-l10n-backupbuddy' ) . ' <img src="' . pb_backupbuddy::plugin_url() . '/images/sort_down.png" style="vertical-align: 0px;" title="Sorted most recent started first">',
				),
			'css'			=>		'width: 100%;',
		)
	);
}

?><br>