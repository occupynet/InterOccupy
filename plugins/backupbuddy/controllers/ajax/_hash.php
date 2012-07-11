<h1><?php _e( 'MD5 Checksum Hash', 'it-l10n-backupbuddy' ); ?></h1>
<?php
_e( 'This is a string of characters that uniquely represents this file.  If this file is in any way manipulated then this string of characters will change.  This allows you to later verify that the file is intact and uncorrupted.  For instance you may verify the file after uploading it to a new location by making sure the MD5 checksum matches.', 'it-l10n-backupbuddy' );

$hash = md5_file( pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_GET( 'callback_data' ) );

echo '<br><br><br>';
echo '<b>Hash:</b> &nbsp;&nbsp;&nbsp; <input type="text" size="40" value="' . $hash . '" readonly="readonly">';
?>