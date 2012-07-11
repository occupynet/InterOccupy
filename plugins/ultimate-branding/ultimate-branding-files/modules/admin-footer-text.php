<?php
/*
Plugin Name: Dashboard Footer Content
Plugin URI: http://premium.wpmudev.org/project/admin-footer-text
Description: Display text in admin dashboard footer
Author: Barry (Incsub), S H Mohanjith (Incsub), Andrew Billits (Incsub)
Version: 1.0.8
Author URI: http://premium.wpmudev.org
WDP ID: 53
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

/**
 * Escaping for textarea values.
 *
 * @since 3.1
 *
 * Added for compatibility with WordPress 3.0.*
 *
 * @param string $text
 * @return string
 */
if( !function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		$safe_text = htmlspecialchars( $text, ENT_QUOTES );
		return apply_filters( 'esc_textarea', $safe_text, $text );
	}
}

class Admin_Footer_Text {

	var $admin_footer_text_default = '';
	var $update_text_default = '';

	function Admin_Footer_Text() {
		$this->__construct();
	}

	function __construct() {

		add_action( 'ultimatebranding_settings_menu_footer', array(&$this, 'output_admin_options') );
		add_filter( 'ultimatebranding_settings_menu_footer_process', array(&$this, 'update_admin_options'), 10, 1 );

		add_filter( 'admin_footer_text', array( &$this, 'output' ), 1, 1 );

		add_filter( 'update_footer' , array( &$this, 'blank_version' ), 99 );

		add_action( 'in_admin_footer' , array(&$this, 'stuff'));

	}

	function stuff() {
		global $wp_filter;

		//print_r($wp_filter);
	}

	function update_admin_options( $status ) {
		ub_update_option( 'admin_footer_text' , stripslashes( $_POST['admin_footer_text'] ) );

		if($status === false) {
			return $status;
		} else {
			return true;
		}
	}

	function output( $footer_text ) {

		$admin_footer_text = ub_get_option( 'admin_footer_text' );

		if ( empty( $admin_footer_text ) ) {
			$footer_text = $this->admin_footer_text_default;
		} else {
			$footer_text = $admin_footer_text;
		}
		return $footer_text;
	}

	function blank_version( $version ) {

		return '';
	}

	function output_admin_options() {

		$admin_footer_text = ub_get_option('admin_footer_text');

		if ( empty( $admin_footer_text ) )
			$admin_footer_text = $this->admin_footer_text_default;
		?>
			<div class="postbox">
			<h3 class="hndle" style='cursor:auto;'><span><?php _e( 'Dashboard Footer Content', 'ub' ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Footer Text', 'ub' ) ?></th>
						<td>
							<?php
							$args = array("textarea_name" => "admin_footer_text", "textarea_rows" => 5);
							wp_editor( stripslashes($admin_footer_text) , "admin_footer_text", $args );
							?>
							<br />
							<?php _e( 'HTML Allowed.', 'ub' ) ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Setting field for singlesite
	 **/
	function site_option() {

		$admin_footer_text = get_option( 'admin_footer_text' );

		if ( empty( $admin_footer_text ) )
			$admin_footer_text = $this->admin_footer_text_default;

		echo '<textarea name="admin_footer_text" type="text" rows="5" wrap="soft" id="admin_footer_text" style="width: 95%" />' . esc_textarea( $admin_footer_text ) . '</textarea>
		<p class="description"> ' . __( 'HTML Allowed.', 'ub' ) . '</p>';
	}

	/**
	 * Verify if plugin is network activated
	 **/
	function is_plugin_active_for_network( $plugin ) {
		if ( ! is_multisite() )
			return false;

		$plugins = get_site_option( 'active_sitewide_plugins');
		if ( isset($plugins[$plugin]) )
			return true;

		return false;
	}

}

$ub_adminfootertext = new Admin_Footer_Text();

