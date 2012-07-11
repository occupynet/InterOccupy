<?php
$view_data['backups'] = pb_backupbuddy::$classes['core']->backups_list( 'default' );


pb_backupbuddy::load_view( '_backup-home', $view_data );
?>