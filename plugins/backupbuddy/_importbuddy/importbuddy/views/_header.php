<!DOCTYPE html>
<!--[if IE 8]>
<html xmlns="http://www.w3.org/1999/xhtml" class="ie8"  dir="ltr" lang="en-US">
<![endif]-->
<!--[if !(IE 8) ]><!-->
<html xmlns="http://www.w3.org/1999/xhtml"  dir="ltr" lang="en-US">
<!--<![endif]-->
	<head>
		<title>BackupBuddy importbuddy.php by PluginBuddy.com</title>
		<meta name="robots" content="noindex">
			<?php
		pb_backupbuddy::load_style( 'style.css' );
		
		pb_backupbuddy::load_script( 'jquery.js' );
		pb_backupbuddy::load_script( 'ui.core.js' );
		pb_backupbuddy::load_script( 'ui.widget.js' );
		pb_backupbuddy::load_script( 'ui.tabs.js' );
		pb_backupbuddy::load_script( 'tooltip.js' );
		pb_backupbuddy::load_script( 'importbuddy.js' );
		?>
	</head>
		<?php
		if ( pb_backupbuddy::$options['display_mode'] == 'normal' ) {
			echo '<body>';
			echo '<center><img src="importbuddy/images/bb-logo.png" title="BackupBuddy Restoration & Migration Tool" style="margin-top: 10px;"></center><br>';
		} else { // Magic migration mode inside WordPress (in an iframe).
			echo '<body onLoad="window.parent.scroll(0,0);">'; // Auto scroll to top of parent while in iframe.
		}
		
		//<a href="http://ithemes.com/codex/page/BackupBuddy" style="text-decoration: none;">Need help? See the <b>Knowledge Base</b> for tutorials & more.</a><br> ?>
		
		<center>
		<?php
		if ( pb_backupbuddy::$options['skip_files'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to skip files is set to true. Files will not be extracted.<br>';
		}
		if ( pb_backupbuddy::$options['wipe_database'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to wipe database with same prefix is set to true. All existing tables with the selected new prefix will be erased.<br>';
		}
		if ( pb_backupbuddy::$options['wipe_database_all'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to wipe ALL database tables is set to true. All existing database content will be erased. Use caution.<br>';
		}
		if ( pb_backupbuddy::$options['skip_database_import'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to skip database import set to true. The database will not be imported.<br>';
		}
		if ( pb_backupbuddy::$options['skip_database_migration'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to skip database import set to true. The database will not be migrated.<br>';
		}
		if ( pb_backupbuddy::$options['mysqlbuddy_compatibility'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to import database in compatibility mode (pre-v3.0) set to true. This is slower.<br>';
		}
		if ( pb_backupbuddy::$options['skip_htaccess'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to skip migrating the htaccess file is set to true. The file will not be migrated if needed.<br>';
		}
		if ( pb_backupbuddy::$options['force_compatibility_medium'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to force medium compatibility mode. This may result in slower, less reliable operation.<br>';
		}
		if ( pb_backupbuddy::$options['force_compatibility_slow'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to force slow compatibility mode. This may result in slower, less reliable operation.<br>';
		}
		if ( pb_backupbuddy::$options['force_high_security'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to force high security mode. You may be prompted for more information than normal.<br>';
		}
		if ( pb_backupbuddy::$options['show_php_warnings'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to strictly report all errors & warnings from PHP is set to true. This may cause operation problems.<br>';
		}
		if ( pb_backupbuddy::$options['ignore_sql_errors'] != false ) {
			echo '<img src="' . pb_backupbuddy::plugin_url() . '/images/bullet_error.png" style="vertical-align: -3px;">';
			echo 'Debug option to ignore existing database table and other SQL errors enabled. Data may be appending to existing tables. Use with caution.<br>';
		}
		echo '</center>';
		?>
		
		<div style="display: none;" id="pb_importbuddy_blankalert"><?php pb_backupbuddy::alert( '#TITLE# #MESSAGE#', true, '9021' ); ?></div>
		
		<div style="width: 700px; margin-left: auto; margin-right: auto;">
			<div class="main_box">
				<div class="main_box_head">
					Step <span class="step_number"><?php echo $step; ?></span> of 6: <?php echo $page_title; ?>
				</div>