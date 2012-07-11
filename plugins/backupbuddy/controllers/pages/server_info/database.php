<br><?php
if ( !isset( $parent_class ) ) {
	$parent_class = $this;
}
if ( defined( 'pluginbuddy_importbuddy' ) ) {
	//$parent_class->admin_scripts();
}
?>

<table class="widefat">
	<thead>
		<tr class="thead">
			<?php
				echo '<th>', __('Database Table', 'it-l10n-backupbuddy' ),'</th>',
					 '<th>', __('Status', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Engine', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Last Updated', 'it-l10n-backupbuddy' ),'</th>',
					 '<th>', __('Rows', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Size', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Size with Exclusions', 'it-l10n-backupbuddy' ), '</th>';
			?>
		</tr>
	</thead>
	<tfoot>
		<tr class="thead">
			<?php
				echo '<th>', __('Database Table', 'it-l10n-backupbuddy' ),'</th>',
					 '<th>', __('Status', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Engine', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Last Updated', 'it-l10n-backupbuddy' ),'</th>',
					 '<th>', __('Rows', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Size', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Size with Exclusions', 'it-l10n-backupbuddy' ), '</th>';
			?>
		</tr>
	</tfoot>
	<tbody>
		<?php
		global $wpdb;
		$prefix = $wpdb->prefix;
		$prefix_length = strlen( $wpdb->prefix );
		
		$additional_includes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_includes'] );
		array_walk( $additional_includes, create_function('&$val', '$val = trim($val);')); 
		$additional_excludes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_excludes'] );
		array_walk( $additional_excludes, create_function('&$val', '$val = trim($val);')); 

		
		$total_size = 0;
		$total_size_with_exclusions = 0;
		$total_rows = 0;
		$result = mysql_query("SHOW TABLE STATUS");
		while( $rs = mysql_fetch_array( $result ) ) {
			$excluded = true; // Default.
			
			// TABLE STATUS.
			$resultb = mysql_query("CHECK TABLE `{$rs['Name']}`");
			while( $rsb = mysql_fetch_array( $resultb ) ) {
				if ( $rsb['Msg_type'] == 'status' ) {
					$status = $rsb['Msg_text'];
				}
			}
			mysql_free_result( $resultb );
			
			// TABLE SIZE.
			$size = ( $rs['Data_length'] + $rs['Index_length'] );
			$total_size += $size;
			
			
			// HANDLE EXCLUSIONS.
			if ( pb_backupbuddy::$options['backup_nonwp_tables'] == 0 ) { // Only matching prefix.
				if ( ( substr( $rs['Name'], 0, $prefix_length ) == $prefix ) OR ( in_array( $rs['Name'], $additional_includes ) ) ) {
					if ( !in_array( $rs['Name'], $additional_excludes ) ) {
						$total_size_with_exclusions += $size;
						$excluded = false;
					}
				}
			} else { // All tables.
				if ( !in_array( $rs['Name'], $additional_excludes ) ) {
					$total_size_with_exclusions += $size;
					$excluded = false;
				}
			}
			
			echo '<tr class="entry-row alternate"';
			if ( $excluded === true ) {
				echo ' style="background: #F9B6B6;"';
			}
			echo '>';
			echo '	<td>' . $rs['Name'] . '</td>';
			echo '	<td>' . $status . '</td>';
			echo '	<td>' . $rs['Engine'] . '</td>';
			echo '	<td>' . $rs['Update_time'] . '</td>';
			echo '	<td>' . $rs['Rows'] . '</td>';
			echo '	<td>' . pb_backupbuddy::$format->file_size( $size ) . '</td>';
			if ( $excluded === true ) {
				echo '	<td><i>Excluded</i></td>';
			} else {
				echo '	<td>' . pb_backupbuddy::$format->file_size( $size ) . '</td>';
			}
			
			
			
			$total_rows += $rs['Rows'];
			echo '</tr>';
		}
		echo '<tr class="entry-row alternate">';
		echo '	<td>&nbsp;</td>';
		echo '	<td>&nbsp;</td>';
		echo '	<td>&nbsp;</td>';
		echo '<td><b>',__('TOTALS','it-l10n-backupbuddy' ),':</b></td>';
		echo '<td><b>' . $total_rows . '</b></td>';
		echo '<td><b>' . pb_backupbuddy::$format->file_size( $total_size ) . '</b></td>';
		echo '<td><b>' . pb_backupbuddy::$format->file_size( $total_size_with_exclusions ) . '</b></td>';
		echo '</tr>';
		
		pb_backupbuddy::$options['stats']['db_size'] = $total_size;
		pb_backupbuddy::$options['stats']['db_size_excluded'] = $total_size_with_exclusions;
		pb_backupbuddy::$options['stats']['db_size_updated'] = time();
		pb_backupbuddy::save();
		
		unset( $total_size );
		unset( $total_rows );
		mysql_free_result( $result );
		?>
	</tbody>
</table><br>