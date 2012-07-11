<?php
$page_title = 'Final Cleanup';
require_once( '_header.php' );
echo '<div class="wrap">';

echo pb_backupbuddy::$classes['import']->status_box( 'Cleaning up after restore with ImportBuddy ' . pb_backupbuddy::settings( 'version' ) . ' from BackupBuddy v' . pb_backupbuddy::$options['bb_version'] . '...' );
echo '<div id="pb_importbuddy_working"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading_large.gif" title="Working... Please wait as this may take a moment..."></div>';



/* The following lines seem to sometimes result in an instant error message reporting the page has met max execution time. Odd. Disabling for now.

@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
//@ini_set('implicit_flush', 1);
//for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
//ob_implicit_flush(1);

*/



// Attempt to flush the page and pause so assets (CSS, images) can load before actual files get deleted by cleanup().
flush();
sleep( 4 ); // Pause server-side for 4 seconds to give time for their browser to load assets.
flush();


// Cleanup!
cleanup();


echo '<script type="text/javascript">jQuery("#pb_importbuddy_working").hide();</script>';

echo 'This step handles cleanup of files. It is common to not be able to delete some files due to permission errors. You may manually delete them.<br><br>';

echo '<h3 style="text-align: center;">Your site is ready to go at<br><br>';
echo '<a href="' . pb_backupbuddy::$options['home'] . '" target="_new"><b>' . pb_backupbuddy::$options['home'] . '</b></a><br><br>';
echo 'Thank you for choosing BackupBuddy!</h3>';


echo '</div></div><br><br><br>';
?>