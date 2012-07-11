All scheduled WordPress tasks (CRON jobs) are listed below. Use caution when manually running or deleting scheduled CRON
jobs as plugins, themes, or WordPress itself may expect these to remain in place. WordPress will recreate any mandatory
internal CRON jobs automatically if they are removed.<br><br>
<?php
$cron = get_option('cron');


// Handle CRON deletions.
if ( pb_backupbuddy::_POST( 'bulk_action' ) == 'delete_cron' ) {
	if ( defined( 'PB_DEMO_MODE' ) ) {
		pb_backupbuddy::alert( 'Access denied in demo mode.', true );
	} else {
		$delete_items = pb_backupbuddy::_POST( 'items' );
		
		$deleted_crons = array(); // For listing in alert.
		foreach( $delete_items as $delete_item ) {
			$cron_parts = explode( '|', $delete_item );
			$timestamp = $cron_parts[0];
			$cron_hook = $cron_parts[1];
			$cron_key = $cron_parts[2];
			
			if ( isset( $cron[ $timestamp ][ $cron_hook ][ $cron_key ] ) ) { // Run cron.
				
				$cron_array = $cron[ $timestamp ][ $cron_hook ][ $cron_key ]; // Get cron array based on passed values.
				wp_unschedule_event( $timestamp, $cron_hook, $cron_array['args'] ); // Delete the scheduled cron.
				$deleted_crons[] = $cron_hook . ' / ' . $cron_key; // Add deleted cron to list of deletions for display.
				
			} else { // Cron not found, error.
				pb_backupbuddy::alert( 'Invalid CRON job. Not found.', true );
			}
			
		}
		
		pb_backupbuddy::alert( __('Deleted sheduled CRON event(s):', 'it-l10n-backupbuddy' ) . '<br>' . implode( '<br>', $deleted_crons ) );
		$cron = get_option('cron'); // Reset to most up to date status for cron listing below. Takes into account deletions.
	}
}



// Handle RUNNING cron jobs manually.
if ( !empty( $_GET['run_cron'] ) ) {
	if ( defined( 'PB_DEMO_MODE' ) ) {
		pb_backupbuddy::alert( 'Access denied in demo mode.', true );
	} else {
		$cron_parts = explode( '|', pb_backupbuddy::_GET( 'run_cron' ) );
		$timestamp = $cron_parts[0];
		$cron_hook = $cron_parts[1];
		$cron_key = $cron_parts[2];
		
		if ( isset( $cron[ $timestamp ][ $cron_hook ][ $cron_key ] ) ) { // Run cron.
			$cron_array = $cron[ $timestamp ][ $cron_hook ][ $cron_key ]; // Get cron array based on passed values.
			
			/*
			if ( count( $cron_array['args'] ) == 1 ) {
				$args = $cron_array['args'][0];
			} else {
				$args = $cron_array['args'];
			}
			*/
			
			do_action_ref_array( $cron_hook, $cron_array['args'] ); // Run the cron job!
			
			pb_backupbuddy::alert( 'Ran CRON event `' . $cron_hook . ' / ' . $cron_key . '`. Its schedule was not modified.' );
		} else { // Cron not found, error.
			pb_backupbuddy::alert( 'Invalid CRON job. Not found.', true );
		}
	}
}



// Loop through each cron time to create $crons array for displaying later.
$crons = array();
foreach ( (array) $cron as $time => $cron_item ) {
	if ( is_numeric( $time ) ) {
		// Loop through each schedule for this time
		foreach ( (array) $cron_item as $hook_name => $event ) {
			foreach ( (array) $event as $item_name => $item ) {
				
				// Determine period.
				if ( !empty( $item['schedule'] ) ) { // Recurring schedule.
					$period = $item['schedule'];
				} else { // One-time only cron.
					$period = __('one time only', 'it-l10n-backupbuddy' );
				}
				
				// Determine interval.
				if ( !empty( $item['interval'] ) ) {
					$interval = $item['interval'] . ' seconds';
				} else {
					$interval = __('one time only', 'it-l10n-backupbuddy' );
				}
				
				// Determine arguments.
				if ( !empty( $item['args'] ) ) {
					$arguments = implode( ',', $item['args'] );
				} else {
					$arguments = __('none', 'it-l10n-backupbuddy' );
				}
				
				// Populate crons array for displaying later.
				$crons[$time . '|' . $hook_name . '|' . $item_name] = array(
					'<span title=\'Key: ' . $item_name . '\'>' . $hook_name . '</span>',
					'<span title="Timestamp: ' . $time . '">' . pb_backupbuddy::$format->date( pb_backupbuddy::$format->localize_time( $time ) ) . '</span>',
					$period,
					$interval,
					$arguments,
				);
				
			} // End foreach.
			unset( $item );
			unset( $item_name );
		} // End foreach.
		unset( $event );
		unset( $hook_name );
	} // End if is_numeric.
} // End foreach.
unset( $cron_item );
unset( $time );



// Display CRON table.
pb_backupbuddy::$ui->list_table(
	$crons, // Array of cron items set in code section above.
	array(
		'action'					=>	pb_backupbuddy::page_url(),
		'columns'					=>	array(
											__( 'Event', 'it-l10n-backupbuddy' ),
											__( 'Run Time', 'it-l10n-backupbuddy' ),
											__( 'Period', 'it-l10n-backupbuddy' ),
											__( 'Interval', 'it-l10n-backupbuddy' ),
											__( 'Arguments', 'it-l10n-backupbuddy' ),
										),
		'css'						=>		'width: 100%;',
		'hover_actions'				=>	array(
											'run_cron'	=>	'Run cron job now',
										),
		'bulk_actions'	=>	array( 'delete_cron' => 'Delete' ),
		'hover_action_column_key'	=>	'0',
	)
);






if ( empty( $_GET['show_cron_array'] ) ) {
	echo '<br>';
	echo '<center>';
	echo '<a href="' . pb_backupbuddy::page_url() . '&show_cron_array=true" style="text-decoration: none;">' . __('Display CRON Debugging Array', 'it-l10n-backupbuddy' ) . '</a> &middot; ' . __('Current Time', 'it-l10n-backupbuddy' ) . ': ' . pb_backupbuddy::$format->date( time() + ( get_option( 'gmt_offset' ) * 3600 ) ) . ' (' . time() . ')';
	echo '<br>';
	echo 'Additional cron control is available via the free plugin <a target="_new" href="http://wordpress.org/extend/plugins/wp-cron-control/">WP-Cron Control</a> by Automaticc.';
	echo '</center>';
} else {
	echo __('Current Time', 'it-l10n-backupbuddy' ) . ': ' . pb_backupbuddy::$format->date( time() + ( get_option( 'gmt_offset' ) * 3600 ) ) . ' (' . time() . ')';
	echo '<br><textarea readonly="readonly" style="width: 793px;" rows="13" cols="75" wrap="off">';
	print_r( $cron );
	echo '</textarea>';
}
echo '<br>';

unset( $cron );
?>
