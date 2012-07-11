<?php

class pb_backupbuddy_filters extends pb_backupbuddy_filterscore {
	
	
	
	public function cron_schedules( $schedules = array() ) {
		$schedules = array();
		$schedules['weekly'] = array( 'interval' => 604800, 'display' => 'Once Weekly' );
		$schedules['twicemonthly'] = array( 'interval' => 1296000, 'display' => 'Twice Monthly' );
		$schedules['monthly'] = array( 'interval' => 2592000, 'display' => 'Once Monthly' );
		return $schedules;
	} // End cron_schedules().
	
	
	
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( isset( $plugin_meta[2] ) && strstr( $plugin_meta[2], 'backupbuddy' ) ) {
			$plugin_meta[3] = $plugin_meta[2];
			$plugin_meta[2] = $plugin_meta[1];
			$plugin_meta[1] = 'By <a href="http://pluginbuddy.com">The PluginBuddy Team</a>';
			
			return $plugin_meta;
		} else {
			return $plugin_meta;
		}
	} // End plugin_row_meta().
	
	
	
} // End class pb_backupbuddy_filters.
?>