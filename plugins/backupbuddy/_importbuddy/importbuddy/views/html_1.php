<?php
if ( pb_backupbuddy::$options['password'] == pb_backupbuddy::_GET( 'v' ) ) { // Hash passed for magic migration.
	$need_auth = false;
	$page_title = 'Choose your backup file';

	pb_backupbuddy::$options['password_verify'] = pb_backupbuddy::$options['password'];
	pb_backupbuddy::save();

} else { // No hash passed; authenticate normally.

	if (
			( pb_backupbuddy::_POST( 'password' ) == '' )
			||
			( pb_backupbuddy::$options['password'] != md5( pb_backupbuddy::_POST( 'password' ) ) )
		) {
		$need_auth = true;
		$page_title = 'Authentication Required';
	} else {
		$need_auth = false;
		$page_title = 'Choose your backup file';
	
		pb_backupbuddy::$options['password_verify'] = pb_backupbuddy::$options['password'];
		pb_backupbuddy::save();
	}
}
require_once( '_header.php' );

echo pb_backupbuddy::$classes['import']->status_box( 'Step 1 debugging information for ImportBuddy ' . pb_backupbuddy::settings( 'version' ) . ' from BackupBuddy v' . pb_backupbuddy::$options['bb_version'] . '...', true );
?>

<div class="wrap">

<?php
if ( $need_auth !== false ) { // Need authentication.
	if ( pb_backupbuddy::_POST( 'password' ) != '' ) {
		pb_backupbuddy::alert( 'Invalid password. Please enter the password you provided within BackupBuddy Settings.' );
		echo '<br>';
	}
	?>
	Enter your ImportBuddy password to continue.
	<br><br>
	<form method="post">
		<input type="password" name="password">
		<input type="submit" name="submit" value="Authenticate" class="button">
	</form>
	
	</div><!-- /wrap -->
<?php
} else {
	
	
	if ( pb_backupbuddy::_GET( 'file' ) == '' ) {
		echo '<p>Select the BackupBuddy backup file you would like to import or migrate.</p>';
	}
	?>
	
	<p>
		Throughout the restore process you may hover over question marks
		<?php pb_backupbuddy::tip( 'This is an example help tip. Hover over these for additional help.' ); ?> 
		for additional help. For support see the <a href="http://ithemes.com/codex/page/BackupBuddy" target="_blank">Knowledge Base</a>
		or <a href="http://pluginbuddy.com/support/" target="_blank">Support Forum</a>.
	</p>
	
	
	<?php
	upload();
	
	echo '<br><br>';
	
	
	
	if ( pb_backupbuddy::_GET( 'file' ) != '' ) {
		echo '
		<div style="padding: 15px; background: #FFFFFF;">Restoring from backup <i>' . htmlentities( pb_backupbuddy::_GET( 'file' ) ) . '</i></div>
		<form action="?step=2" method="post">
			<input type="hidden" name="options" value="' . htmlspecialchars( serialize( pb_backupbuddy::$options ) ) . '" />
			<input type="hidden" name="file" value="' . htmlspecialchars( pb_backupbuddy::_GET( 'file' ) ) . '">
		';
	} else {
		?>
		
		<div id="pluginbuddy-tabs">
			<ul>
				<li><a href="#pluginbuddy-tabs-server"><span>Server</span></a></li>
				<li><a href="#pluginbuddy-tabs-upload"><span>Upload</span></a></li>
			</ul>
			<div class="tabs-borderwrap">
				
				<div id="pluginbuddy-tabs-upload">
					<div class="tabs-item">
						<?php
						if ( pb_backupbuddy::$options['password'] == '#PASSWORD#' ) {
							echo 'To prevent unauthorized file uploads an importbuddy password must be configured and properly entered to use this feature.';
						} else {
						?>
						<form enctype="multipart/form-data" action="?step=1" method="POST">
							<input type="hidden" name="upload" value="local">
							<input type="hidden" name="options" value="<?php echo htmlspecialchars( serialize( pb_backupbuddy::$options ) ); ?>'" />
							Choose backup to upload: <input name="file" type="file" />&nbsp;
							<input type="submit" value="Upload" class="toggle button-secondary">
						</form>
						<?php
						}
						?>
					</div>
				</div>
				
				<div id="pluginbuddy-tabs-server">
					<div class="tabs-item">
						<?php
						if ( empty( $backup_archives ) ) { // No backups found.
							pb_backupbuddy::alert( 'ERROR: Unable to find any BackupBuddy backup files.',
								'Upload the backup zip archive into the same directory as this file,
								keeping the original file name. Example: backup-your_com-2011_07_19-g1d1jpvd4e.zip<br><br>
								If you manually extracted, upload the backup file, select it, then select <i>Advanced
								Troubleshooting Options</i> & click <i>Skip Zip Extraction</i>.', true );
						} else { // Found one or more backups.
							?>
								<form action="?step=2" method="post">
									<input type="hidden" name="options" value="<?php echo htmlspecialchars( serialize( pb_backupbuddy::$options ) ); ?>'" />
							<?php
							echo '<div class="backup_select_text">Select from your stored backups ';
							echo pb_backupbuddy::tip( 'Select the backup file you would like to restore data from. This must be a valid BackupBuddy backup archive with its original filename. Remember to delete importbuddy.php and this backup file from your server after migration.', '', true );
							echo '</div>';
							foreach( $backup_archives as $backup_id => $backup_archive ) {
								echo '<input type="radio" ';
								if ( $backup_id == 0 ) {
									echo 'checked="checked" ';
								}
								echo 'name="file" value="' . $backup_archive['file'] . '"> ' . $backup_archive['file'] . '<br>';
								if ( $backup_archive['comment'] != '' ) {
									echo '<div style="margin-left: 27px; margin-top: 6px;" class="description">Note: ' . $backup_archive['comment'] . '</div>';
								}
							}
							
							//echo '&nbsp;&nbsp;&nbsp;<span class="toggle button-secondary" id="pb_importbuddy_gethash">Get MD5 Hash</span>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	<?php } // End file not given in querystring. ?>
	
	
	<div style="margin-left: 15px;">
		<span class="toggle button-secondary" id="serverinfo">Server Information</span> <span class="toggle button-secondary" id="advanced">Advanced Configuration Options</span>
		<div id="toggle-advanced" class="toggled" style="margin-top: 12px;">
			<?php
			//pb_backupbuddy::alert( 'WARNING: These are advanced configuration options.', 'Use caution as improper use could result in data loss or other difficulties.' );
			?>
			<b>WARNING:</b> Improper use of Advanced Options could result in data loss.<br>
			&nbsp;&nbsp;&nbsp;&nbsp;Leave as is unless you understand what these settings do.
			<br><br>
			
			
			
			<input type="checkbox" name="wipe_database" onclick="
				if ( !confirm( 'WARNING! WARNING! WARNING! WARNING! WARNING! \n\nThis will clear any existing WordPress installation or other content in this database that matches the new site database prefix you specify. This could result in loss of posts, comments, pages, settings, and other software data loss. Verify you are using the exact database settings you want to be using. PluginBuddy & all related persons hold no responsibility for any loss of data caused by using this option. \n\n Are you sure you want to do this and potentially wipe existing data matching the specified table prefix? \n\n WARNING! WARNING! WARNING! WARNING! WARNING!' ) ) {
					return false;
				}
			" /> Wipe database tables that match new prefix on import. <span style="color: orange;">Use caution.</span> <?php pb_backupbuddy::tip( 'WARNING: Checking this box will have this script clear ALL existing data from your database that match the new database prefix prior to import, possibly including non-WordPress data. This is useful if you are restoring over an existing site or repairing a failed migration. Use caution when using this option and double check the destination prefix. Use with caution. This cannot be undone.' ); ?><br>
			
			<input type="checkbox" name="wipe_database_all" onclick="
				if ( !confirm( 'WARNING! WARNING! WARNING! WARNING! WARNING! \n\nThis will clear any existing WordPress installation or other content in this database that matches the new site database prefix you specify. This could result in loss of posts, comments, pages, settings, and other software data loss. Verify you are using the exact database settings you want to be using. PluginBuddy & all related persons hold no responsibility for any loss of data caused by using this option. \n\n Are you sure you want to do this and potentially wipe ALL existing data? \n\n WARNING! WARNING! WARNING! WARNING! WARNING!' ) ) {
					return false;
				}
			" /> Wipe <b>ALL</b> database tables, erasing <b>ALL</b> database content. <span style="color: red;">Use extreme caution.</span> <?php pb_backupbuddy::tip( 'WARNING: Checking this box will have this script clear ALL existing data from your database, period, including non-WordPress data found. Use with extreme caution, verifying you are using the exact correct database settings. This cannot be undone.' ); ?><br>
			
			
			<input type="checkbox" name="ignore_sql_errors"> Ignore existing WordPress tables and import (merge tables) anyways. <?php pb_backupbuddy::tip( 'When checked ImportBuddy will allow importing database tables that have the same name as existing tables. This results in a merge of the existing data with the imported database being merged. Note that this is does NOT update existing data and only ADDS new database table rows. All other SQL conflict errors will be suppressed as well. Use this feature with caution.' ); ?><br>
			<input type="checkbox" name="skip_files"> Skip zip file extraction. <?php pb_backupbuddy::tip( 'Checking this box will prevent extraction/unzipping of the backup ZIP file.  You will need to manually extract it either on your local computer then upload it or use a server-based tool such as cPanel to extract it. This feature is useful if the extraction step is unable to complete for some reason.' ); ?><br>
			<input type="checkbox" name="skip_database_import"> Skip import of database. <br>
			<input type="checkbox" name="mysqlbuddy_compatibility"> Force database import compatibility (pre-v3.0) mode. <br>
			<input type="checkbox" name="skip_database_migration"> Skip migration of database. <br>
			<input type="checkbox" name="skip_htaccess"> Skip migration of .htaccess file. <br>
			<!-- TODO: <input type="checkbox" name="merge_databases" /> Ignore existing WordPress data & merge database.<?php pb_backupbuddy::tip( 'This may result in data conflicts, lost database data, or incomplete restores.' ); ?></a><br> -->
			<input type="checkbox" name="force_compatibility_medium" /> Force medium speed compatibility mode (ZipArchive). <br>
			<input type="checkbox" name="force_compatibility_slow" /> Force slow speed compatibility mode (PCLZip). <br>
			<?php //<input type="checkbox" name="force_high_security"> Force high security on a normal security backup<br> ?>
			<input type="checkbox" name="show_php_warnings" /> Show detailed PHP warnings. <br>
			<br>
			PHP Maximum Execution Time: <input type="text" name="max_execution_time" value="<?php echo $detected_max_execution_time; ?>" size="5"> seconds. <?php pb_backupbuddy::tip( 'The maximum allowed PHP runtime. If your database import step is timing out then lowering this value will instruct the script to limit each `chunk` to allow it to finish within this time period.' ); ?>
			<br>
			Error Logging to importbuddy.txt: <select name="log_level">
				<option value="0">None</option>
				<option value="1" selected>Errors Only (default)</option>
				<option value="2">Errors & Warnings</option>
				<option value="3">Everything (debug mode)</option>
			</select> <?php pb_backupbuddy::tip( 'Errors and other debugging information will be written to importbuddy.txt in the same directory as importbuddy.php.  This is useful for debugging any problems encountered during import.  Support may request this file to aid in tracking down any problems or bugs.' ); ?>
		</div>
		<?php
		echo '<div id="toggle-serverinfo" class="toggled" style="margin-top: 12px;">';
		$server_info_file = ABSPATH . 'importbuddy/controllers/pages/server_info.php';
		if ( file_exists( $server_info_file ) ) {
			require_once( $server_info_file );
		} else {
			echo '{Error: Missing server tools file `' . $server_info_file . '`.}';
		}
		echo '</div>';
		?>
	</div>
	<br>
	<?php
	echo '<br>';
	
	/********* Start warnings for existing files. *********/
	if ( wordpress_exists() === true ) {
		pb_backupbuddy::alert( 'WARNING: Existing WordPress installation found. It is strongly recommended that existing WordPress files and database be removed prior to migrating or restoring to avoid conflicts. You should not install WordPress prior to migrating.' );
	}
	if ( phpini_exists() === true ) {
		pb_backupbuddy::alert( 'WARNING: Existing php.ini file found. If your backup also contains a php.ini file it may overwrite the current one, possibly resulting in changes in cofiguration or problems. Make a backup of your existing file if your are unsure.' );
	}
	if ( htaccess_exists() === true ) {
		pb_backupbuddy::alert( 'WARNING: Existing .htaccess file found. If your backup also contains a .htaccess file it may overwrite the current one, possibly resulting in changed in configuration or problems. Make a backup of your existing file if you are unsure.' );
	}
	/********* End warnings for existing files. *********/
	
	// If one or more backup files was found then provide a button to continue.
	if ( !empty( $backup_archives ) ) {
		echo '</div><!-- /wrap -->';
		echo '<div class="main_box_foot">';
		echo '<input type="submit" name="submit" value="Next Step &rarr;" class="button">';
		echo '</div>';
	} else {
		pb_backupbuddy::alert( 'Upload a backup file to continue.' );
		echo '</div><!-- /wrap -->';
	}
	echo '</form>';
	?>
	
<?php
}
require_once( '_footer.php' );
?>