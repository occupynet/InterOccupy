<?php
/*
Plugin Name: Remove Permalinks Menu Item
Plugin URI: http://premium.wpmudev.org/project/remove-permalinks-menu-item
Description: Removes the 'permalinks' configuration options
Author: Andrew Billits, Ulrich Sossou
Version: 1.0.3
Author URI: http://premium.wpmudev.org/
WDP ID: 171
*/

add_action( 'admin_menu', 'remove_permalinks_menu_item' );

function remove_permalinks_menu_item() {
	global $submenu;
	unset( $submenu['options-general.php'][40] );
}

add_action('ultimatebranding_settings_menu_permalinks','rpm_manage_output');

function rpm_manage_output() {
	global $wpdb, $current_site, $page;

	?>

	<div class="postbox">
		<h3 class="hndle" style='cursor:auto;'><span><?php _e('Remove Permalinks Menu Item','ub'); ?></span></h3>
		<div class="inside">
				<p class='description'><?php _e( 'The Permalinks menu item is hidden.', 'ub' ); ?>

		</div>
	</div>

<?php
}
