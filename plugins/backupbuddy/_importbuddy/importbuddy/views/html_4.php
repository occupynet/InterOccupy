<?php
$page_title = 'Database Import';
require_once( '_header.php' );
echo '<div class="wrap">';



$failed = false;

echo pb_backupbuddy::$classes['import']->status_box( 'Importing database content with ImportBuddy ' . pb_backupbuddy::settings( 'version' ) . ' from BackupBuddy v' . pb_backupbuddy::$options['bb_version'] . '...' );
echo '<div id="pb_importbuddy_working"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading_large.gif" title="Working... Please wait as this may take a moment..."></div>';

$import_result = import_database();

echo '<script type="text/javascript">jQuery("#pb_importbuddy_working").hide();</script>';

if ( $import_result[0] == true ) {
	if ( $import_result[1] !== true ) { // if not finished.
		pb_backupbuddy::alert( 'Database too large to import in one step so it will be imported in chunks. Please continue the process until this step is finished. This may take a few steps depending on the size of your database and server speed.' );
		echo '<br>';
		echo 'Please keep continuing until your database has fully imported. This may take a few steps.';
		echo '<form action="?step=4" method=post>';
		echo '<input type="hidden" name="db_continue" value="' . $import_result[1] . '">';
		echo '<input type="hidden" name="options" value="' . htmlspecialchars( serialize( pb_backupbuddy::$options ) ) . '" />';
		echo '</div><!-- /wrap -->';
		echo '<div class="main_box_foot"><input type="submit" name="submit" class="button" value="Continue Database Import &rarr;" /></div>';
		echo '</form>';
	} else {
		echo '<p style="text-align: center;">Initial database import complete!</p><br>';
		echo 'Next the data in the database will be migrated to account for any file path or URL changes.';
		echo '<form action="?step=5" method=post>';
		echo '<input type="hidden" name="options" value="' . htmlspecialchars( serialize( pb_backupbuddy::$options ) ) . '" />';
		echo '</div><!-- /wrap -->';
		echo '<div class="main_box_foot"><input type="submit" name="submit" class="button" value="Next Step &rarr;" /></div>';
		echo '</form>';
	}
} else {
	echo '<br><p style="text-align: center;">Database import failed. Please use your back button to correct any errors.</p>';
	echo '</div><!-- /wrap -->';
}



require_once( '_footer.php' );
?>