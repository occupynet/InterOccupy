<?php
/*
Plugin Name: Remove WP Dashboard Widgets
Plugin URI: http://premium.wpmudev.org/project/remove-wordpress-dashboard-widgets
Description: Removes the wordpress dashboard widgets
Author: Barry (Incsub), Andrew Billits, Ulrich Sossou
Version: 1.0.3
Author URI: http://premium.wpmudev.org/
WDP ID: 172
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

add_action( 'wp_dashboard_setup', 'remove_wp_dashboard_widgets' );

add_action('ultimatebranding_settings_menu_widgets','rwpwidgets_manage_output');
add_filter('ultimatebranding_settings_menu_widgets_process', 'rwpwidgets_process_save');

function rwpwidgets_process_save( $status ) {

	$active = array(); //get_site_option( 'rwp_active_dashboard_widgets', array() );

	foreach( (array) $_POST['active'] as $key => $value ) {
		if(!isset($active[$value])) {
			$active[$value] = $value;
		}
	}

	ub_update_option( 'rwp_active_dashboard_widgets', $active);

	if($status === false) {
		return $status;
	} else {
		return true;
	}
}

function rwpwidgets_manage_output() {
	global $wpdb, $current_site, $page;
	global $wp_meta_boxes;

	$available_widgets = array(	'dashboard_browser_nag' => __('Browser Nag','ub'),
								'dashboard_right_now' => __('Right Now','ub'),
								'dashboard_recent_comments' => __('Recent Comments','ub'),
								'dashboard_incoming_links' => __('Incoming Links','ub'),
								'dashboard_plugins' => __('Plugins','ub'),
								'dashboard_quick_press' => __('QuickPress','ub'),
								'dashboard_recent_drafts' => __('Recent Drafts','ub'),
								'dashboard_primary' => __('Primary Feed','ub'),
								'dashboard_secondary' => __('Secondary Feed','ub')
								);

	$active = ub_get_option( 'rwp_active_dashboard_widgets', array() );

	?>

	<div class="postbox">
		<h3 class="hndle" style='cursor:auto;'><span><?php _e('Remove WordPress Dashboard Widgets ','ub'); ?></span></h3>
		<div class="inside">
				<p class='description'><?php _e( 'Select which widgets you want to remove from all dashboards on your network from the list below.', 'ub' ); ?>
				<ul class='availablewidgets'>
				<?php
					foreach($available_widgets as $key => $title) {
						?>
						<li><input type='checkbox' name='active[]' value='<?php echo $key; ?>' <?php if(in_array($key, $active)) echo "checked='checked'"; ?> />&nbsp;<?php echo $title; ?></li>
						<?php
					}
				?>
				</ul>
		</div>
	</div>

<?php
}

function remove_wp_dashboard_widgets() {

	global $wp_meta_boxes;

	$active = ub_get_option( 'rwp_active_dashboard_widgets', array() );

	foreach($active as $key => $value) {
		switch($key) {

			case 'dashboard_browser_nag':
			case 'dashboard_right_now':
			case 'dashboard_recent_comments':
			case 'dashboard_incoming_links':
			case 'dashboard_plugins':			remove_meta_box( $key, 'dashboard', 'normal' );
												break;
			case 'dashboard_quick_press':
			case 'dashboard_recent_drafts':
			case 'dashboard_primary':
			case 'dashboard_secondary':			remove_meta_box( $key, 'dashboard', 'side' );
												break;

		}
	}

}

