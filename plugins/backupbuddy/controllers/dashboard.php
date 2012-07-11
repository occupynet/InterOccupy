<?php

class pb_backupbuddy_dashboard extends pb_backupbuddy_dashboardcore {


	/*	stats()
	 *	
	 *	Displays (echos out) an overview of stats into the WordPress Dashboard.
	 *	
	 *	@return		null
	 */
	function stats() {
		echo '<style type="text/css">';
		echo '	.pb_fancy {';
		echo '		font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;';
		echo '		font-size: 18px;';
		echo '		color: #21759B;';
		echo '	}';
		echo '</style>';
		
		echo '<div>';
		
		$backup_url = 'admin.php?page=pb_backupbuddy_backup';
		
		$files = glob( pb_backupbuddy::$options['backup_directory'] . 'backup*.zip' );
		if ( !is_array( $files ) || empty( $files ) ) {
			$files = array();
		}
		array_multisort( array_map( 'filemtime', $files ), SORT_NUMERIC, SORT_DESC, $files );
		
		echo sprintf( __('You currently have %s stored backups.', 'it-l10n-backupbuddy' ), '<span class="pb_fancy"><a href="' . $backup_url . '">' . count( $files ) . '</a></span>');
		if ( pb_backupbuddy::$options['last_backup'] == 0 ) {
			echo ' ', __( 'You have not created any backups.', 'it-l10n-backupbuddy' );
		} else {
			echo ' ', sprintf( __(' Your most recent backup was %s ago.', 'it-l10n-backupbuddy' ), '<span class="pb_fancy"><a href="' . $backup_url . '">' . pb_backupbuddy::$format->time_ago( pb_backupbuddy::$options['last_backup'] ) . '</a></span>');
		}
		echo ' ', sprintf( __('There have been %s post/page modifications since your last backup.', 'it-l10n-backupbuddy' ), '<span class="pb_fancy"><a href="' . $backup_url . '">' . pb_backupbuddy::$options['edits_since_last'] . '</a></span>' );
		echo ' <span class="pb_fancy"><a href="' . $backup_url . '">', __('Go create a backup!', 'it-l10n-backupbuddy' ), '</a></span>';
		
		echo '</div>';
	}


}
?>