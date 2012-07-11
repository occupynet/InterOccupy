<?php
// TODO: move all output into the view.

pb_backupbuddy::load_script( 'filetree.js' );
pb_backupbuddy::load_style( 'filetree.css' );


?>


<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#exlude_dirs').fileTree({ root: '/', multiFolder: false, script: '<?php echo pb_backupbuddy::ajax_url( 'exclude_tree' ); ?>' }, function(file) {
			//alert('file:'+file);
			}, function(directory) {
			if ( ( directory == '/wp-content/' ) || ( directory == '/wp-content/uploads/' ) || ( directory == '/wp-content/uploads/backupbuddy_backups/' ) || ( directory == '/wp-content/uploads/backupbuddy_temp/' ) ) {
				alert( '<?php _e('You cannot exclude /wp-content/, /wp-content/uploads/, or BackupBuddy directories.  However, you may exclude subdirectories within these. BackupBuddy directories such as backupbuddy_backups are automatically excluded and cannot be added to exclusion list.', 'it-l10n-backupbuddy' );?>' );
			} else {
				jQuery('#pb_backupbuddy_excludes').val( directory + "\n" + jQuery('#pb_backupbuddy_excludes').val() );
			}
		});
	});
	
	function pb_backupbuddy_selectdestination( destination_id, destination_title, callback_data ) {
		window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&custom=remoteclient&destination_id=' + destination_id;
	}
</script>


<?php
pb_backupbuddy::$ui->title( 'BackupBuddy Settings' );

pb_backupbuddy::$classes['core']->versions_confirm();











/* BEGIN DISALLOWING DEFAULT IMPORT/REPAIR PASSWORD */
if ( strtolower( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) == 'myp@ssw0rd' ) {
	pb_backupbuddy::alert( 'Warning: The example password is not allowed for security reasons for ImportBuddy. Please choose another password.' );
	$_POST['pb_backupbuddy_importbuddy_pass_hash'] = '';
}
if ( strtolower( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) ) == 'myp@ssw0rd' ) {
	pb_backupbuddy::alert( 'Warning: The example password is not allowed for security reasons for RepairBuddy. Please choose another password.' );
	$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = '';
}
/* END DISALLOWING DEFAULT IMPORT/REPAIR PASSWORD */


/* BEGIN REPLACING IMPORTBUDDY/REPAIRBUDDY_PASS_HASH WITH VALUE OF ACTUAL HASH */
// ImportBuddy hash replace.
if ( ( str_replace( ')', '', pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) != '' ) && ( md5( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) != pb_backupbuddy::$options['importbuddy_pass_hash'] ) ) {
	//echo 'posted value: ' . pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) . '<br>';	
	//echo 'hash: ' . md5( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) . '<br>';
	pb_backupbuddy::$options['importbuddy_pass_length'] = strlen( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) );
	$_POST['pb_backupbuddy_importbuddy_pass_hash'] = md5( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) );
} else { // Keep the same.
	$_POST['pb_backupbuddy_importbuddy_pass_hash'] = pb_backupbuddy::$options['importbuddy_pass_hash'];
}
// Set importbuddy dummy text to display in form box. Equal length to the provided password.
$importbuddy_pass_dummy_text = str_pad( '', pb_backupbuddy::$options['importbuddy_pass_length'], ')' );

// RepairBuddy hash replace.
if ( ( str_replace( ')', '', pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) ) != '' ) && ( md5( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) ) != pb_backupbuddy::$options['repairbuddy_pass_hash'] ) ) {
	pb_backupbuddy::$options['repairbuddy_pass_length'] = strlen( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) );
	$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = md5( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) );
} else { // Keep the same.
	$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = pb_backupbuddy::$options['repairbuddy_pass_hash'];
}
// Set repairbuddy dummy text to display in form box. Equal length to the provided password.
$repairbuddy_pass_dummy_text = str_pad( '', pb_backupbuddy::$options['repairbuddy_pass_length'], ')' );
/* END REPLACING IMPORTBUDDY/REPAIRBUDDY_PASS_HASH WITH VALUE OF ACTUAL HASH */


/* BEGIN SAVE MULTISITE SPECIFIC SETTINGS IN SET OPTIONS SO THEY ARE AVAILABLE GLOBALLY */
if ( is_multisite() ) {
	// Save multisite export option to the global site/network options for global retrieval.
	$options = get_site_option( 'pb_' . pb_backupbuddy::settings( 'slug' ) );
	$options[ 'multisite_export' ] = pb_backupbuddy::_POST( 'pb_backupbuddy_multisite_export' );
	update_site_option( 'pb_' . pb_backupbuddy::settings( 'slug' ), $options );
	unset( $options );
}
/* END SAVE MULTISITE SPECIFIC SETTINGS IN SET OPTIONS SO THEY ARE AVAILABLE GLOBALLY */










/* BEGIN CONFIGURING PLUGIN SETTINGS FORM */

$settings_form = new pb_backupbuddy_settings( 'settings', '', '', 320 );


$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_2',
	'title'		=>		__( 'General Options', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#6', __('General Options Tutorial', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'password',
	'name'		=>		'importbuddy_pass_hash',
	'title'		=>		__('ImportBuddy password', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: myp@ssw0rD] - Required password for running the ImportBuddy import/migration script. This prevents unauthorized access when using this tool. You should not use your WordPress password here.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'',
	'value'		=>		$importbuddy_pass_dummy_text,
	//'classes'	=>		'regular-text code',
) );
$settings_form->add_setting( array(
	'type'		=>		'password',
	'name'		=>		'repairbuddy_pass_hash',
	'title'		=>		__('RepairBuddy password', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: myp@ssw0rD] - Required password for running the RepairBuddy troubleshooting/repair script. This prevents unauthorized access when using this tool. You should not use your WordPress password here.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'',
	'value'		=>		$repairbuddy_pass_dummy_text,
	//'classes'	=>		'regular-text code',
) );

$log_file = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/log-' . pb_backupbuddy::$options['log_serial'] . '.txt';
$settings_form->add_setting( array(
	'type'		=>		'select',
	'name'		=>		'log_level',
	'title'		=>		__('Logging / Debug level', 'it-l10n-backupbuddy' ),
	'options'	=>		array(
								'0'		=>		__( 'None', 'it-l10n-backupbuddy' ),
								'1'		=>		__( 'Errors Only', 'it-l10n-backupbuddy' ),
								'2'		=>		__( 'Errors & Warnings', 'it-l10n-backupbuddy' ),
								'3'		=>		__( 'Everything (debug mode)', 'it-l10n-backupbuddy' ),
							),
	'tip'		=>		sprintf( __('[Default: Errors Only] - This option controls how much activity is logged for records or debugging. When in debug mode error emails will contain encrypted debugging data for support. Log file: %s', 'it-l10n-backupbuddy' ), $log_file ),
	'rules'		=>		'required',
) );

$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'backup_reminders',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Enable backup reminders', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: enabled] - When enabled links will be displayed upon post or page edits and during WordPress upgrades to remind and allow rapid backing up after modifications or before upgrading.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'',
	'rules'		=>		'required',
) );





$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_1',
	'title'		=>		__( 'Email Notification Recipients', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#6', __('Email Notifications Tutorial', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_scheduled_start',
	'title'		=>		__('Scheduled backup started email recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon scheduled backup starting. Use commas to separate multiple email addresses. Notifications will not be sent for remote destination file transfers.', 'it-l10n-backupbuddy' ),
	//'rules'		=>		'string[0-500]',
	'classes'	=>		'regular-text',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_scheduled_complete',
	'title'		=>		__('Scheduled backup completed email recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon scheduled backup completion. Use commas to separate multiple email addresses.', 'it-l10n-backupbuddy' ),
	//'rules'		=>		'required|string[1-500]',
	'classes'	=>		'regular-text',
) );
/*
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_manual_started',
	'title'		=>		__( 'Manual backup started email recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon manually triggered backup starting. Use commas to separate multiple email addresses.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[1-500]',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_manual_completed',
	'title'		=>		__( 'Manual backup completed email recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon manually triggered backup completion. Use commas to separate multiple email addresses.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[1-500]',
) );
*/
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_error',
	'title'		=>		__('Error notification recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon encountering any errors or problems. Use commas to separate multiple email addresses.', 'it-l10n-backupbuddy' ),
	'classes'	=>		'regular-text',
	//'rules'		=>		'required|string[1-500]',
) );




$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_archivestoragelimits',
	'title'		=>		__( 'Archive Storage Limits', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#45', __('Archive Storage Limits Tutorial', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'archive_limit',
	'title'		=>		__('Maximum number of archived backups', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: 10] - Maximum number of archived backups to store. Any new backups created after this limit is met will result in your oldest backup(s) being deleted to make room for the newer ones. Changes to this setting take place once a new backup is made. Set to zero (0) for no limit.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[1-500]',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'archive_limit_size',
	'title'		=>		__('Maximum size of archived backups', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: 350] - Maximum size (in MB) to allow your total archives to reach. Any new backups created after this limit is met will result in your oldest backup(s) being deleted to make room for the newer ones. Changes to this setting take place once a new backup is made. Set to zero (0) for no limit.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[1-500]',
) );



if ( is_multisite() ) {
	$settings_form->add_setting( array(
		'type'		=>		'title',
		'name'		=>		'title_multisite',
		'title'		=>		__( 'Multisite', 'it-l10n-backupbuddy' ),
	) );
	$settings_form->add_setting( array(
		'type'		=>		'checkbox',
		'name'		=>		'multisite_export',
		'title'		=>		__( 'Allow individual site exports by administrators?', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( '_oKGIzzuVzw', __('Multisite export', 'it-l10n-backupbuddy' ), false ),
		'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
		'tip'		=>		__('[Default: disabled] - When enabled individual sites may be exported by Administrators of the individual site. Network Administrators always see this menu (notes with the words SuperAdmin in parentheses in the menu when only SuperAdmins have access to the feature).', 'it-l10n-backupbuddy' ),
		'rules'		=>		'required',
		'after'		=>		'<span class="description"> ' . __( 'Check to extend Site Exporting functionality to subsite Administrators.', 'it-l10n-backupbuddy' ) . '</span>',
	) );
}



$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_mysqltables',
	'title'		=>		__( 'Database Backup', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#62', __('Database backup settings', 'it-l10n-backupbuddy' ), false ),
) );

global $wpdb;
$settings_form->add_setting( array(
	'type'		=>		'radio',
	'name'		=>		'backup_nonwp_tables',
	'options'	=>		array( '0' => 'This WordPress\' tables (' . $wpdb->prefix . ')', '1' => 'All tables' ),
	'title'		=>		__( '<b>Default</b> database tables to backup', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: This WordPress\' tables prefix (' . $wpdb->prefix . ')] - Determines the default set of tables to backup.  If this WordPress\' tables is selected then only tables with the same prefix (for example ' . $wpdb->prefix . ' for this installation) will be backed up by default.  If all are selected then all tables will be backed up by default. Additional inclusions & exclusions may be defined below.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'rules'		=>		'required',
) );


$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'mysqldump_additional_includes',
	'title'		=>		__('Additional tables to <b>include</b>', 'it-l10n-backupbuddy' ) . '<br><span class="description">' . __( 'One table per line.', 'it-l10n-backupbuddy' ) . '</span>',
	'tip'		=>		__('Additional databases tables to INCLUDE in the backup in addition to the defaults.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'',
	'css'		=>		'width: 100%;',
) );
$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'mysqldump_additional_excludes',
	'title'		=>		__('Additional tables to <b>exclude</b>', 'it-l10n-backupbuddy' ) . '<br><span class="description">' . __( 'One table per line.', 'it-l10n-backupbuddy' ) . '</span>',
	'tip'		=>		__('Additional databases tables to EXCLUDE from the backup. Exclusions are exempted after calculating defaults and additional table includes first. These may include non-WordPress and WordPress tables. WARNING: Excluding WordPress tables results in an incomplete backup and could result in failure in the ability to restore or data loss. Use with caution.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'',
	'css'		=>		'width: 100%;',
) );





$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_exclusions',
	'title'		=>		__( 'Directory Exclusions', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#94', __('Backup Directory Excluding Tutorial', 'it-l10n-backupbuddy' ), false ),
) );

$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'excludes',
	'title'		=>		'Click directories to navigate or click <img src="' . pb_backupbuddy::plugin_url() .'/images/bullet_delete.png" style="vertical-align: -3px;"> to exclude.' . ' ' .
						pb_backupbuddy::tip( __('Click on a directory name to navigate directories. Click the red minus sign to the right of a directory to place it in the exclusion list. /wp-content/, /wp-content/uploads/, and BackupBuddy backup & temporary directories cannot be excluded. BackupBuddy directories are automatically excluded.', 'it-l10n-backupbuddy' ), '', false ) .
						'<br><div id="exlude_dirs" class="jQueryOuterTree"></div>' .
						'<span class="description">' . __( 'Available if server does not require compatibility mode.', 'it-l10n-backupbuddy' ) . '</span>' .
						pb_backupbuddy::tip( __('If you receive notifications that your server is entering compatibility mode or that native zip functionality is unavailable then this feature will not be available due to technical limitations of the compatibility mode.  Ask your host to correct the problems causing compatibility mode or move to a new server.', 'it-l10n-backupbuddy' ), '', false ),
	//'tip'		=>		,
	'rules'		=>		'string[0-9000]',
	'css'		=>		'width: 100%; height: 103px;',
	'before'	=>		__('Excluded directories (relative to WordPress installation directory)' , 'it-l10n-backupbuddy' ) . pb_backupbuddy::tip( __('List paths relative to the WordPress installation directory to be excluded from backups.  You may use the directory selector to the left to easily exclude directories by ctrl+clicking them.  Paths are relative to root, for example: /wp-content/uploads/junk/', 'it-l10n-backupbuddy' ), '', false ) . '<br>',
	'after'		=>		'<span class="description">' . __( 'One directory exclusion per line. This may be manually edited.', 'it-l10n-backupbuddy' ) . '</span>',
) );



$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_troubleshooting',
	'title'		=>		__( 'Troubleshooting & Compatibility', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#108', __('Troubleshooting options', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'lock_archives_directory',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Lock archive directory (high security)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: disabled] - When enabled all downloads of archives via the web will be prevented under all circumstances via .htaccess file. If your server permits it, they will only be unlocked temporarily on click to download. If your server does not support this unlocking then you will have to access the archives via the server (such as by FTP).', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check for enhanced security to block backup downloading.', 'it-l10n-backupbuddy' ) . ' This may<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;result in an inability to download backups while enabled on some servers.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'delete_archives_pre_backup',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Delete all backup archives prior to backups', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: disabled] - When enabled all local backup archives will be deleted prior to each backup. This is useful if in compatibilty mode to prevent backing up existing files.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check if using compatibilty mode & exclusions are unavailable.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'compression',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Enable zip compression', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: enabled] - ZIP compression decreases file sizes of stored backups. If you are encountering timeouts due to the script running too long, disabling compression may allow the process to complete faster.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Uncheck for large sites causing backups to not complete.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'integrity_check',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Perform integrity check on backup files', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: enabled] - By default each backup file is checked for integrity and completion the first time it is viewed on the Backup page.  On some server configurations this may cause memory problems as the integrity checking process is intensive.  If you are experiencing out of memory errors on the Backup file listing, you can uncheck this to disable this feature.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __( 'Uncheck if having problems viewing your backup listing.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'force_compatibility',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Force compatibility mode zip', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: disabled] - (WARNING: This forces the potentially slower mode of zip creation. Only use if absolutely necessary. Checking this box can cause backup failures if it is not needed.) Under normal circumstances compatibility mode is automatically entered as needed without user intervention. However under some server configurations the native backup system is unavailable but is incorrectly reported as functioning by the server.  Forcing compatibility may fix problems in this situation by bypassing the native backup system check entirely.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check if absolutely necessary or directed by support.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'force_mysqldump_compatibility',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Force compatibility mode database dump', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: disabled] - WARNING: This forces the potentially slower mode of database dumping. Under normal circumstances mysql dump compatibility mode is automatically entered as needed without user intervention.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __( 'Check if database dumping fails. Pre-v3.x mode.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'skip_database_dump',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Skip database dump on backup', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: disabled] - (WARNING: This prevents BackupBuddy from backing up the database during any kind of backup. This is for troubleshooting / advanced usage only to work around being unable to backup the database.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check if unable to backup database for some reason.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'alternative_zip',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Alternative zip system (BETA)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] Use if directed by support. Allows use of directory exclusion when in PCLZip Compatibility Mode.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check if stuck in compatibiilty (PCLZip) mode for directory exclusions,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;having trouble during ZIP creation, or directed by support.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'disable_zipmethod_caching',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Disable zip method caching', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] Use if directed by support. Bypasses caching available zip methods so they are always displayed in logs. When unchecked BackupBuddy will cache command line zip testing for a few minutes so it does not run too often. This means that your backup status log may not always show the test results unless you disable caching.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check if you always want available zip methods to be tested and displayed<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;in backup log files. This is useful for support to see if command line zip is failing.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'zip_viewer_enabled',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Allow viewing zip contents (BETA)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] This feature is currently in beta. If your server supports ZipArchive, when enabled you may select to `View zip contents` from the backup listing on the Backups page. This allows you to view a listing of files within a ZIP archive.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check for Beta feature for viewing a list of files in an archive.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'select',
	'name'		=>		'backup_mode',
	'title'		=>		__('Manual backup mode', 'it-l10n-backupbuddy' ),
	'options'	=>		array(
								'1'		=>		__( 'Classic (v1.x)', 'it-l10n-backupbuddy' ),
								'2'		=>		__( 'Modern (v2.x)', 'it-l10n-backupbuddy' ),
							),
	'tip'		=>		__('[Default: Modern] - If you are encountering difficulty backing up due to WordPress cron, HTTP Loopbacks, or other features specific to version 2.x you can try classic mode which runs like BackupBuddy v1.x did.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required',
) );


$settings_form->process(); // Handles processing the submitted form (if applicable).
$settings_form->set_value( 'importbuddy_pass_hash', $importbuddy_pass_dummy_text );
$settings_form->set_value( 'repairbuddy_pass_hash', $repairbuddy_pass_dummy_text );
$data['settings_form'] = &$settings_form; // For use in view.

/* END CONFIGURING PLUGIN SETTINGS FORM */





pb_backupbuddy::$classes['core']->periodic_cleanup( 43200, false ); // Cleans up and also makes sure directory security is always configured right on downloads after settings changes.

//$settings_form->clear_values();


// Load settings view.
pb_backupbuddy::load_view( 'settings', $data );
?>






<style type="text/css">
	/* Core Styles - USED BY DIRECTORY EXCLUDER */
	.jqueryFileTree LI.directory { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/directory.png') left top no-repeat; }
	.jqueryFileTree LI.expanded { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/folder_open.png') left top no-repeat; }
	.jqueryFileTree LI.file { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/file.png') left top no-repeat; }
	.jqueryFileTree LI.wait { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/spinner.gif') left top no-repeat; }
	/* File Extensions*/
	.jqueryFileTree LI.ext_3gp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_afp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_afpa { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_asp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_aspx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_avi { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_bat { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') left top no-repeat; }
	.jqueryFileTree LI.ext_bmp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_c { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_cfm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_cgi { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_com { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') left top no-repeat; }
	.jqueryFileTree LI.ext_cpp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_css { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/css.png') left top no-repeat; }
	.jqueryFileTree LI.ext_doc { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/doc.png') left top no-repeat; }
	.jqueryFileTree LI.ext_exe { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') left top no-repeat; }
	.jqueryFileTree LI.ext_gif { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_fla { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/flash.png') left top no-repeat; }
	.jqueryFileTree LI.ext_h { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_htm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/html.png') left top no-repeat; }
	.jqueryFileTree LI.ext_html { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/html.png') left top no-repeat; }
	.jqueryFileTree LI.ext_jar { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/java.png') left top no-repeat; }
	.jqueryFileTree LI.ext_jpg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_jpeg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_js { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') left top no-repeat; }
	.jqueryFileTree LI.ext_lasso { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_log { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/txt.png') left top no-repeat; }
	.jqueryFileTree LI.ext_m4p { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') left top no-repeat; }
	.jqueryFileTree LI.ext_mov { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_mp3 { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') left top no-repeat; }
	.jqueryFileTree LI.ext_mp4 { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_mpg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_mpeg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_ogg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') left top no-repeat; }
	.jqueryFileTree LI.ext_pcx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_pdf { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/pdf.png') left top no-repeat; }
	.jqueryFileTree LI.ext_php { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/php.png') left top no-repeat; }
	.jqueryFileTree LI.ext_png { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_ppt { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ppt.png') left top no-repeat; }
	.jqueryFileTree LI.ext_psd { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/psd.png') left top no-repeat; }
	.jqueryFileTree LI.ext_pl { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') left top no-repeat; }
	.jqueryFileTree LI.ext_py { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') left top no-repeat; }
	.jqueryFileTree LI.ext_rb { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') left top no-repeat; }
	.jqueryFileTree LI.ext_rbx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') left top no-repeat; }
	.jqueryFileTree LI.ext_rhtml { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') left top no-repeat; }
	.jqueryFileTree LI.ext_rpm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/linux.png') left top no-repeat; }
	.jqueryFileTree LI.ext_ruby { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') left top no-repeat; }
	.jqueryFileTree LI.ext_sql { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/db.png') left top no-repeat; }
	.jqueryFileTree LI.ext_swf { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/flash.png') left top no-repeat; }
	.jqueryFileTree LI.ext_tif { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_tiff { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') left top no-repeat; }
	.jqueryFileTree LI.ext_txt { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/txt.png') left top no-repeat; }
	.jqueryFileTree LI.ext_vb { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_wav { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') left top no-repeat; }
	.jqueryFileTree LI.ext_wmv { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') left top no-repeat; }
	.jqueryFileTree LI.ext_xls { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/xls.png') left top no-repeat; }
	.jqueryFileTree LI.ext_xml { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') left top no-repeat; }
	.jqueryFileTree LI.ext_zip { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/zip.png') left top no-repeat; }
</style>