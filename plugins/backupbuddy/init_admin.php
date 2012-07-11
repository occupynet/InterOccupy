<?php // This code runs whenever in the wp-admin.



/********** MISC **********/



pb_backupbuddy::load();

// Load backupbuddy class with helper functions.
if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
	require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
	pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
}

/********** Begin directory checking. **********/
// Keep backup directory up to date.
if ( pb_backupbuddy::$options['backup_directory'] != ( ABSPATH . 'wp-content/uploads/backupbuddy_backups/' ) ) {
	pb_backupbuddy::status( 'details', 'Backup directory has changed. Updating from `' . pb_backupbuddy::$options['backup_directory'] . '` to `' . ABSPATH . 'wp-content/uploads/backupbuddy_backups/' . '`.' );
	pb_backupbuddy::$options['backup_directory'] = ABSPATH . 'wp-content/uploads/backupbuddy_backups/';
	pb_backupbuddy::save();
}
// Make backup directory if it does not exist yet.
//pb_backupbuddy::status( 'details', 'Verifying backup directory `' . pb_backupbuddy::$options['backup_directory'] . '` exists.' );
if ( !file_exists( pb_backupbuddy::$options['backup_directory'] ) ) {
	pb_backupbuddy::status( 'details', 'Backup directory does not exist. Attempting to create.' );
	if ( pb_backupbuddy::$filesystem->mkdir( pb_backupbuddy::$options['backup_directory'] ) === false ) {
		pb_backupbuddy::status( 'error', sprintf( __('Unable to create backup storage directory (%s)', 'it-l10n-backupbuddy' ) , pb_backupbuddy::$options['backup_directory'] ) );
		pb_backupbuddy::alert( sprintf( __('Unable to create backup storage directory (%s)', 'it-l10n-backupbuddy' ) , pb_backupbuddy::$options['backup_directory'] ), true, '9002' );
	}
}

// Keep temp directory up to date.
if ( pb_backupbuddy::$options['temp_directory'] != ( ABSPATH . 'wp-content/uploads/backupbuddy_temp/' ) ) {
	pb_backupbuddy::status( 'details', 'Temporary directory has changed. Updating from `' . pb_backupbuddy::$options['temp_directory'] . '` to `' . ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . '`.' );
	pb_backupbuddy::$options['temp_directory'] = ABSPATH . 'wp-content/uploads/backupbuddy_temp/';
	pb_backupbuddy::save();
}
// Make backup directory if it does not exist yet.
if ( !file_exists( pb_backupbuddy::$options['temp_directory'] ) ) {
	pb_backupbuddy::status( 'details', 'Temporary directory does not exist. Attempting to create.' );
	if ( pb_backupbuddy::$filesystem->mkdir( pb_backupbuddy::$options['temp_directory'] ) === false ) {
		pb_backupbuddy::status( 'error', sprintf( __('Unable to create temporary storage directory (%s)', 'it-l10n-backupbuddy' ) , pb_backupbuddy::$options['temp_directory'] ) );
		pb_backupbuddy::alert( sprintf( __('Unable to create temporary storage directory (%s)', 'it-l10n-backupbuddy' ) , pb_backupbuddy::$options['temp_directory'] ), true, '9002' );
	}
}
/********** End directory checking. **********/


// The below is no longer needed? - Dustin May 12, 2012:
// ZipBuddy handles all zipping, testing available zip methods, etc. Only runs on backupbuddy pages here.
// TODO: Make sure zipbuddy testing is cached.
/*
//todo: add this back in. temp disabled just for some testing jan 25, 11am.
if ( strstr( pb_backupbuddy::_GET( 'page' ), 'backupbuddy' ) && !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) { // Only run if zipbuddy object not already loaded.
	require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
	pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
}
*/


/********** ACTIONS (admin) **********/



// Set up reminders if enabled.
if ( pb_backupbuddy::$options['backup_reminders'] == '1' ) {
	pb_backupbuddy::add_action( array( 'load-update-core.php', 'wp_update_backup_reminder' ) );
	pb_backupbuddy::add_action( array( 'post_updated_messages', 'content_editor_backup_reminder_on_update' ) );
}

// Display warning to network activate if running in normal mode on a MultiSite Network.
if ( is_multisite() && !pb_backupbuddy::$classes['core']->is_network_activated() ) {
	pb_backupbuddy::add_action( array( 'all_admin_notices', 'multisite_network_warning' ) ); // BB should be network activated while on Multisite.
}



/********** AJAX (admin) **********/



pb_backupbuddy::add_ajax( 'importbuddy' ); // ImportBuddy download link.
pb_backupbuddy::add_ajax( 'repairbuddy' ); // RepairBuddy download link.
pb_backupbuddy::add_ajax( 'backup_status' ); // AJAX querying of backup status for manual backups.
pb_backupbuddy::add_ajax( 'destination_picker' ); // Remote destination picker.
pb_backupbuddy::add_ajax( 'hash' ); // Obtain MD5 hash of a backup file.
pb_backupbuddy::add_ajax( 'migration_picker' ); // Remote destination picker.
pb_backupbuddy::add_ajax( 'remote_send' ); // Remote destination picker.
pb_backupbuddy::add_ajax( 'migrate_status' ); // Magic migration status polling.
pb_backupbuddy::add_ajax( 'ajax_controller_callback_function' ); // Tell WordPress about this AJAX callback.
pb_backupbuddy::add_ajax( 'icicle' ); // Server info page icicle.
pb_backupbuddy::add_ajax( 'remote_test' ); // Remote destination testing.
pb_backupbuddy::add_ajax( 'refresh_site_size' ); // Server info page site size update.
pb_backupbuddy::add_ajax( 'refresh_site_size_excluded' ); // Server info page site size (sans exclusions) update.
pb_backupbuddy::add_ajax( 'refresh_database_size' ); // Server info page database size update.
pb_backupbuddy::add_ajax( 'refresh_database_size_excluded' ); // Server info page site size (sans exclusions) update.
pb_backupbuddy::add_ajax( 'phpinfo' ); // Server info page extended PHPinfo thickbox.
pb_backupbuddy::add_ajax( 'exclude_tree' ); // Directory exclusions picker for settings page.
pb_backupbuddy::add_ajax( 'download_archive' ); // Directory exclusions picker for settings page.
pb_backupbuddy::add_ajax( 'set_backup_note' ); // Used for setting a note on a backup archive in the backup listing.



/********** DASHBOARD (admin) **********/



// Display stats in Dashboard.
if ( ( !is_multisite() ) || ( is_multisite() && is_network_admin() ) ) { // Only show if standalon OR in main network admin.
	pb_backupbuddy::add_dashboard_widget( 'stats', 'BackupBuddy', 'godmode' );
}

/********** FILTERS (admin) **********/
pb_backupbuddy::add_filter( 'plugin_row_meta', 10, 2 );


/********** PAGES (admin) **********/

if ( is_multisite() && pb_backupbuddy::$classes['core']->is_network_activated() && !defined( 'PB_DEMO_MODE' ) ) { // Multisite installation.
	if ( is_network_admin() ) { // Network Admin pages
		pb_backupbuddy::add_page( '', 'getting_started', array( pb_backupbuddy::settings( 'name' ), 'Getting Started' ) );
		pb_backupbuddy::add_page( 'getting_started', 'backup', __( 'Backup', 'it-l10n-backupbuddy' ), 'manage_network' );
		pb_backupbuddy::add_page( 'getting_started', 'migrate_restore', __( 'Migrate, Restore', 'it-l10n-backupbuddy' ), 'manage_network' );
		pb_backupbuddy::add_page( 'getting_started', 'multisite_import', __( 'Multisite Import', 'it-l10n-backupbuddy' ), 'manage_network' );
		pb_backupbuddy::add_page( 'getting_started', 'malware_scan', __( 'Malware Scan', 'it-l10n-backupbuddy' ), 'manage_network' );
		pb_backupbuddy::add_page( 'getting_started', 'server_info', __( 'Server Information', 'it-l10n-backupbuddy' ), 'manage_network' );
		//pb_backupbuddy::add_page( 'getting_started', 'server_tools', __( 'Server Tools', 'it-l10n-backupbuddy' ), 'manage_network' );
		pb_backupbuddy::add_page( 'getting_started', 'scheduling', __( 'Scheduling', 'it-l10n-backupbuddy' ), 'manage_network' );
		pb_backupbuddy::add_page( 'getting_started', 'settings', __( 'Settings', 'it-l10n-backupbuddy' ), 'manage_network' );
	} else { // Subsite pages.
		// TODO: Make the following work so the network admin ALWAYS can export even if admin exports are not enabled. Problem: current_user_can() is not available this early. Not sure best fix yet.
		//if ( current_user_can( 'manage_network' ) || ( ( current_user_can( 'activate_plugins' ) ) && ( pb_backupbuddy::$options[ 'multisite_export' ] == '1' ) ) ) { // Add export menus if: is network admin _OR_ ( is an admin AND exporting is enabled ).
		
		$export_note = '';
		
		$options = get_site_option( 'pb_' . pb_backupbuddy::settings( 'slug' ) );
		$multisite_export = $options[ 'multisite_export' ];
		unset( $options );

		if ( $multisite_export == '1' ) { // Settings enable admins to export. Set capability to admin and higher only.
			$capability = 'administrator';
			$export_title = '<span title="Note: Enabled for both subsite Admins and Network Superadmins based on BackupBuddy settings">Export Site</span>';
		} else { // Settings do NOT allow admins to export; set capability for superadmins only.
			$capability = 'manage_network';
			$export_title = '<span title="Note: Enabled for Network Superadmins only based on BackupBuddy settings">Export Site (SA)</span>';
		}
				
		//pb_backupbuddy::add_page( '', 'getting_started', array( pb_backupbuddy::settings( 'name' ), 'Getting Started' . $export_note ), $capability );
		pb_backupbuddy::add_page( '', 'multisite_export', $export_title, $capability );
	}
} else { // Standalone site.
	pb_backupbuddy::add_page( '', 'getting_started', array( pb_backupbuddy::settings( 'name' ), 'Getting Started' ) );
	pb_backupbuddy::add_page( 'getting_started', 'backup', __( 'Backup', 'it-l10n-backupbuddy' ), 'administrator' );
	pb_backupbuddy::add_page( 'getting_started', 'migrate_restore', __( 'Migrate, Restore', 'it-l10n-backupbuddy' ), 'administrator' );
	pb_backupbuddy::add_page( 'getting_started', 'malware_scan', __( 'Malware Scan', 'it-l10n-backupbuddy' ), 'administrator' );
	pb_backupbuddy::add_page( 'getting_started', 'server_info', __( 'Server Information', 'it-l10n-backupbuddy' ), 'administrator' );
	//pb_backupbuddy::add_page( 'getting_started', 'server_tools', __( 'Server Tools', 'it-l10n-backupbuddy' ), 'administrator' );
	pb_backupbuddy::add_page( 'getting_started', 'scheduling', __( 'Scheduling', 'it-l10n-backupbuddy' ), 'administrator' );
	pb_backupbuddy::add_page( 'getting_started', 'settings', __( 'Settings', 'it-l10n-backupbuddy' ), 'administrator' );
}



/********** LIBRARIES & CLASSES (admin) **********/



/********** OTHER (admin) **********/



?>