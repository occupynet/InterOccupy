<?php
/*
Plugin Name: Global Footer Content
Plugin URI: http://premium.wpmudev.org/project/global-footer-content
Description: Simply insert any code that you like into the footer of every blog
Author: Barry (Incsub), S H Mohanjith (Incsub), Andrew Billits (Incsub)
Version: 1.0.2
Author URI: http://premium.wpmudev.org
Network: true
WDP ID: 93
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)

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


class global_footer_content {

	var $global_footer_content_settings_page;
	var $global_footer_content_settings_page_long;

	function __construct() {

		add_action( 'ultimatebranding_settings_menu_footer', array(&$this, 'global_footer_content_site_admin_options') );
		add_filter( 'ultimatebranding_settings_menu_footer_process', array(&$this, 'update_global_footer_options'), 10, 1 );

		add_action('wp_footer', array(&$this, 'global_footer_content_output'));
	}

	function global_footer_content() {
		$this->__construct();
	}

	function update_global_footer_options( $status ) {

		$global_footer_content = $_POST['global_footer_content'];
		if ( $global_footer_content == '' ) {
			$global_footer_content = 'empty';
		}

		ub_update_option( 'global_footer_content' , $global_footer_content );

		if($status === false) {
			return $status;
		} else {
			return true;
		}
	}

	function global_footer_content_output() {
		$global_footer_content = ub_get_option('global_footer_content');
		if ( $global_footer_content == 'empty' ) {
			$global_footer_content = '';
		}
		if ( !empty( $global_footer_content ) ) {
			echo stripslashes( $global_footer_content );
		}
	}

	function global_footer_content_site_admin_options() {

		global $wpdb, $wp_roles, $current_user, $global_footer_content_settings_page;

		$global_footer_content = ub_get_option('global_footer_content');
		if ( $global_footer_content == 'empty' ) {
			$global_footer_content = '';
		}

		?>
			<div class="postbox">
			<h3 class="hndle" style='cursor:auto;'><span><?php _e( 'Global Footer Content', 'ub' ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Footer Content', 'ub') ?></th>
						<td>
							<?php
							$args = array("textarea_name" => "global_footer_content", "textarea_rows" => 5);
							wp_editor( stripslashes( $global_footer_content ), "global_footer_content", $args );
							?>
		                	<br />
							<?php _e('What is added here will be shown on every blog or site in your network. You can add tracking code, embeds, terms of service links, etc.', 'ub') ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

}

$ub_globalfootertext = new global_footer_content();


