<?php
pb_backupbuddy::load_script( 'admin.js' );



pb_backupbuddy::$ui->title( __( 'Server Tools', 'it-l10n-backupbuddy' ) );
pb_backupbuddy::$classes['core']->versions_confirm();


echo '<a name="database_replace"></a>';
pb_backupbuddy::$ui->start_metabox( __( 'Database Mass Text Replacement', 'it-l10n-backupbuddy' ) );
pb_backupbuddy::load_view( '_server_tools-database_replace' );
pb_backupbuddy::$ui->end_metabox();

/*	
// Handles thickbox auto-resizing. Keep at bottom of page to avoid issues.
if ( !wp_script_is( 'media-upload' ) ) {
	wp_enqueue_script( 'media-upload' );
	wp_print_scripts( 'media-upload' );
}
*/
?>