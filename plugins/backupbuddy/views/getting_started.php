<?php
pb_backupbuddy::$classes['core']->versions_confirm();

pb_backupbuddy::load_script( 'jquery' );

?>



<?php pb_backupbuddy::$ui->start_metabox( 'Welcome', true, 'width: 70%;' ); ?>
	<?php
	_e("BackupBuddy is the all-in-one solution for backups, restoration, and migration.  The single backup ZIP file created can be used with the restore & migration utility to quickly and easily restore your site on the same server or even migrate to a new host with different settings.  Whether you're an end user or a developer, this plugin will bring you peace of mind and added safety in the event of data loss.  Our goal is keeping the backup, restoration, and migration processes easy, fast, and reliable.", 'it-l10n-backupbuddy' );
	echo sprintf(
				__('Throughout the plugin you may hover your mouse over question marks %1$s for tips or click play icons %2$s for video tutorials.', 'it-l10n-backupbuddy' ), 
				pb_backupbuddy::tip( __('This tip provides additional help.', 'it-l10n-backupbuddy' ), '', false ), //the flag false returns a string
				pb_backupbuddy::video( 'WQrOCvOYof4', __('Introduction to BackupBuddy', 'it-l10n-backupbuddy' ), false )
			);
	echo ' Tutorials, walkthroughs, videos, and troubleshooting tips are available in the <b><a href="http://ithemes.com/codex/page/BackupBuddy" style="text-decoration: none;">PluginBuddy Knowledge Base</a></b>.';
	echo '<br><br><b>Getting Started Resources:</b>';
	echo '<ol>';
	echo '	<li type="disc"><a href="http://ithemes.tv/category/backupbuddy/">Watch the latest Getting Started with BackupBuddy training videos</a></li>';
	echo '	<li type="disc"><a href="http://ithemes.com/publishing/getting-started-with-backupbuddy/">Read the Getting Started with BackupBuddy ebook</a></li>';
	echo '</ol>';
	
	echo '<b>Configure the following <a href="';
	if ( is_network_admin() ) {
		echo network_admin_url( 'admin.php' );
	} else {
		echo admin_url( 'admin.php' );
	}
	echo '?page=pb_backupbuddy_settings">Settings</a> before use:</b>';
	?>
	
	<ol>
		<li type="disc">
			<?php echo '<b>' . __( 'Email notifications', 'it-l10n-backupbuddy' ) . '</b> - ' . __( 'This will enable status email notifications. Receive alerts if backups fail.', 'it-l10n-backupbuddy' ); ?>
		</li>
		<li type="disc">
			<?php echo '<b>' . __( 'ImportBuddy & RepairBuddy passwords', 'it-l10n-backupbuddy' ) . '</b> - ' . __( 'This will allow you to download these utilities / scripts.', 'it-l10n-backupbuddy' ); ?>
		</li>
	</ol>
<?php pb_backupbuddy::$ui->end_metabox(); ?>



<?php pb_backupbuddy::$ui->start_metabox( __('Backup', 'it-l10n-backupbuddy' ), true, 'width: 70%;' );; ?>
	<?php
	if ( is_network_admin() ) {
		$backup_page_url = network_admin_url( 'admin.php' );
	} else {
		$backup_page_url = admin_url( 'admin.php' );
	}
	$backup_page_url .= '?page=pb_backupbuddy_backup';
	?>
	<ol>
		<li type="disc">
			<?php
				echo sprintf( __('Perform a <b>Database Backup</b> regularly by clicking `Database Backup` button on the <a href="%s">Backup</a> page. The database contains posts, pages, comments widget content, media titles & descriptions (but not media files), and other WordPress settings. It may be backed up more often without impacting your available storage space or server performance as much as a Full Backup.', 'it-l10n-backupbuddy' ), $backup_page_url );
			?>	
		</li>
		<li type="disc">
		<?php
			echo sprintf( __('Perform a <b>Full Backup</b> by clicking `Full Backup` button on the <a href="%s">Backup</a> page. This backs up all files in your WordPress installation directory (and subdirectories) as well as the database. This will capture everything from the Database Only Backup and also all files in the WordPress directory and subdirectories. This includes files such as media, plugins, themes, images, and any other files found.', 'it-l10n-backupbuddy' ), $backup_page_url );
		?>
		</li>
		<li type="disc"><?php _e('Local backup storage directory', 'it-l10n-backupbuddy' );?>: <span style="background-color: #EEEEEE; padding: 4px;"><i><?php echo str_replace( '\\', '/', pb_backupbuddy::$options['backup_directory'] ); ?></i></span> <?php pb_backupbuddy::tip(' ' . __('This is the local directory that backups are stored in. Backup files include random characters in their name for increased security. BackupBuddy must be able to create this directory & write to it.', 'it-l10n-backupbuddy' ) ); ?></li>
	</ol>
<?php pb_backupbuddy::$ui->end_metabox(); ?>



<?php pb_backupbuddy::$ui->start_metabox( __('Migrate, Restore', 'it-l10n-backupbuddy' ), true, 'width: 70%;' ); ?>
	<?php
	if ( is_network_admin() ) {
		$migrate_page_url = network_admin_url( 'admin.php' );
	} else {
		$migrate_page_url = admin_url( 'admin.php' );
	}
	$migrate_page_url .= '?page=pb_backupbuddy_migrate_restore';
	?>
	Sites may be restored to the same server or migrated to a new server. To fully restore or migrate a site a Full Backup is required as it includes both your database and all files. The importbuddy.php utility is available for manually restoring or migrating.
	Additional information is available on the <a href="<?php echo $migrate_page_url; ?>">Migrate, Restore</a> page.
	<ol>
		<?php
		echo '<li type="disc">';
		echo sprintf( __('Upload the backup file and ImportBuddy Script (obtained on the <a href="%s">Migrate, Restore</a> page) to the directory where you would like your WordPress site to be installed on the destination server. For full backups <b>do not install WordPress</b> on the destination server. Database-only backups require the WordPress installation (files/folders) associated with the backup to already be present on the destination server. The importbuddy.php script will restore all files, including WordPress, from full backups.', 'it-l10n-backupbuddy' ), $migrate_page_url ),
			 '</li>',
			'<li type="disc">',
			__('Navigate to importbuddy.php script in your web browser on the destination server. If you provided an import password you will be prompted for this password before you may continue.', 'it-l10n-backupbuddy' ),
			'</li>',
			'<li type="disc">',
			__('Follow the importing instructions on screen. You must create a mySQL database on the destination server if one does not exist.', 'it-l10n-backupbuddy' ),
			 ' ( <a href="http://pluginbuddy.com/tutorial-create-database-in-cpanel/" target="_new">',
			 __('Database creation video & instructions', 'it-l10n-backupbuddy' ),
			 '</a> )</li>',
			'</li>';
		?>
	</ol>
<?php pb_backupbuddy::$ui->end_metabox(); ?>


<?php

if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { ?> // Show in WINdows but not darWIN.
	<?php pb_backupbuddy::$ui->start_metabox( __('Windows Server Performance Boost', 'it-l10n-backupbuddy' ), true, 'width: 70%;' );; ?>
		<?php
		_e('Windows servers may be able to significantly boost performance, if the server allows executing .exe files, by adding native Zip compatibility executable files <a href="http://pluginbuddy.com/wp-content/uploads/2010/05/backupbuddy_windows_unzip.zip">available for download here</a>. Instructions are provided within the readme.txt in the package.  This package prevents Windows from falling back to Zip compatiblity mode and works for both BackupBuddy and importbuddy.php. This is particularly useful for <a href="http://ithemes.com/codex/page/BackupBuddy:_Local_Development">local development on a Windows machine using a system like XAMPP</a>.', 'it-l10n-backupbuddy' );
		?>
	<?php pb_backupbuddy::$ui->end_metabox(); ?>
<?php } ?>



<?php pb_backupbuddy::$ui->start_metabox( __('Log File', 'it-l10n-backupbuddy' ), true, 'width: 70%;' );; ?>
	<?php
	if ( pb_backupbuddy::_GET( 'cleanup_now' ) != '' ) {
		pb_backupbuddy::alert( 'Performing cleanup procedures now.' );
		pb_backupbuddy::$classes['core']->periodic_cleanup();
	}
	
	
	$log_file = WP_CONTENT_DIR . '/uploads/pb_' . self::settings( 'slug' ) . '/log-' . self::$options['log_serial'] . '.txt';
	
	if ( pb_backupbuddy::_GET( 'reset_log' ) != '' ) {
		if ( file_exists( $log_file ) ) {
			@unlink( $log_file );
		}
		if ( file_exists( $log_file ) ) { // Didnt unlink.
			pb_backupbuddy::alert( 'Unable to clear log file. Please verify permissions on file `' . $log_file . '`.' );
		} else { // Unlinked.
			pb_backupbuddy::alert( 'Cleared log file.' );
		}
	}
	
	echo '<textarea readonly="readonly" style="width: 100%;" wrap="off" cols="65" rows="7">';
	if ( file_exists( $log_file ) ) {
		readfile( $log_file );
	} else {
		echo __('Nothing has been logged.', 'it-l10n-backupbuddy' );
	}
	echo '</textarea>';
	?>
	<p>
		<a href="<?php echo pb_backupbuddy::page_url(); ?>&reset_log=true" class="button secondary-button"><?php _e('Clear Log File', 'it-l10n-backupbuddy' );?></a>
		&nbsp;
		<a href="<?php echo pb_backupbuddy::page_url(); ?>&cleanup_now=true" class="button secondary-button"><?php _e('Cleanup Temporary Data Now', 'it-l10n-backupbuddy' );?></a>
		&nbsp;
		<a id="pluginbuddy_debugtoggle" class="button secondary-button">Debugging Information</a>
		
		
		
		
		<div id="pluginbuddy_debugtoggle_div" style="display: none;">
			<h4><?php _e('Debugging Information', 'it-l10n-backupbuddy');?></h4>
			<?php
			$temp_options = pb_backupbuddy::$options;
			$temp_options['importbuddy_pass_hash'] = '*hidden*';
			$temp_options['repairbuddy_pass_hash'] = '*hidden*';
			echo '<textarea rows="7" cols="65" style="width: 100%;" wrap="off" readonly="readonly">';
			echo 'Plugin Version = '.pb_backupbuddy::settings('name').' '.pb_backupbuddy::settings('version').' ('.pb_backupbuddy::settings('slug').')'."\n";
			echo 'WordPress Version = '.get_bloginfo("version")."\n";
			echo 'PHP Version = '.phpversion()."\n";
			global $wpdb;
			echo 'DB Version = '.$wpdb->db_version()."\n";
			echo "\n".print_r($temp_options);
			echo '</textarea>';
			?>
		</div>
		
		
		
		
		
		
		
	</p>
<?php pb_backupbuddy::$ui->end_metabox(); ?>


<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#pluginbuddy_debugtoggle").click(function() {
			jQuery("#pluginbuddy_debugtoggle_div").slideToggle();
		});
	});
</script>