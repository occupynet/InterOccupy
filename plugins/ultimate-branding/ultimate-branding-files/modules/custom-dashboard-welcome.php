<?php
/*
Plugin Name: Hide Dashboard Welcome
Plugin URI:
Description: Hides the dashboard welcome message
Author: Barry (Incsub)
Version: 1.0
Author URI:
Network: true

Copyright 2012 Incsub (email: admin@incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

function ub_remove_dashboard_welcome( $value = null, $object_id, $meta_key = '', $single = false ) {

	if($meta_key == 'show_welcome_panel') {
		return 0;
	} else {
		return $value;
	}

}

add_filter( "get_user_metadata", 'ub_remove_dashboard_welcome' , 10, 4 );

function ub_dashboard_welcome_manage_output() {

	?>

	<div class="postbox">
		<h3 class="hndle" style='cursor:auto;'><span><?php _e('Hide Dashboard Welcome','ub'); ?></span></h3>
		<div class="inside">
				<p class='description'><?php _e( 'The Dashboard Welcome message is now hidden.', 'ub' ); ?>

		</div>
	</div>

<?php
}

add_action('ultimatebranding_settings_menu_widgets','ub_dashboard_welcome_manage_output');

?>