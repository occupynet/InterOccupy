<?php
$page_title = 'Unzipping Backup File';
require_once( '_header.php' );
echo '<div class="wrap">';

echo pb_backupbuddy::$classes['import']->status_box( 'Extracting backup ZIP file with ImportBuddy ' . pb_backupbuddy::settings( 'version' ) . ' from BackupBuddy v' . pb_backupbuddy::$options['bb_version'] . '...' );
echo '<div id="pb_importbuddy_working"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading_large.gif" title="Working... Please wait as this may take a moment..."></div>';

$results = extract_files();

echo '<script type="text/javascript">jQuery("#pb_importbuddy_working").hide();</script>';

if ( true === $results ) { // Move on to next step.
	echo '<br><br><p style="text-align: center;">Files successfully extracted.</p>';
	echo '<form action="?step=3" method=post>';
	echo '<input type="hidden" name="options" value="' . htmlspecialchars( serialize( pb_backupbuddy::$options ) ) . '" />';
	echo '</div><!-- /wrap -->';
	echo '<div class="main_box_foot"><input type="submit" name="submit" value="Next Step &rarr;" class="button" /></div>';
	echo '</form>';
} else {
	pb_backupbuddy::alert( 'File extraction process did not complete successfully. Unable to continue to next step. Manually extract the backup ZIP file and choose to "Skip File Extraction" from the advanced options on Step 1.', true, '9005' );
	echo '<p style="text-align: center;"><a href="http://pluginbuddy.com/tutorials/unzip-backup-zip-file-in-cpanel/">Click here for instructions on manual ZIP extraction as a work-around.</a></p>';
	echo '</div><!-- /wrap -->';
}

rename_htaccess_temp(); // Rename .htaccess to .htaccess.bb_temp until end of migration.

get_dat_from_backup();


require_once( '_footer.php' ); ?>