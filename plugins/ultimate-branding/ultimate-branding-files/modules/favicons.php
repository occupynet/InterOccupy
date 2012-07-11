<?php
/*
Plugin Name: Custom Multisite Favicons
Plugin URI:
Description: Change the Favicon for the network
Author: Barry (Incsub), Philip John (Incsub)
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


class ub_favicons {

	var $build = 1;

	var $db;

	function __construct() {

		// Admin interface
		add_action( 'ultimatebranding_settings_menu_images', array(&$this, 'manage_output') );
		add_filter( 'ultimatebranding_settings_menu_images_process', array( &$this, 'process' ) );

		add_action('admin_head', array(&$this, 'admin_head') );
		add_action('wp_head', array(&$this, 'wp_head') );

	}

	function ub_favicons() {
		$this->__construct();
	}

	function process() {
		global $plugin_page;

		if ( isset( $_GET['resetfavicon'] ) ) {

			$uploaddir = ub_wp_upload_dir();

			$favicon_dir = ub_get_option( 'ub_favicon_dir', false );

			if( $favicon_dir && file_exists($favicon_dir) ) $this->remove_file( $favicon_dir );

			ub_delete_option( 'ub_favicon_dir' );
			ub_delete_option( 'ub_favicon_url' );

		} elseif( !empty($_FILES['favicon_file']['name']) ) {

			$uploaddir = ub_wp_upload_dir();
			$uploadurl = ub_wp_upload_url();

			$favicon_dir = ub_get_option( 'ub_favicon_dir', false );

			if( $favicon_dir && file_exists($favicon_dir) ) $this->remove_file( $favicon_dir );

			if ( ! is_dir( $uploaddir . '/ultimate-branding/includes/favicon/' ) )
				wp_mkdir_p( $uploaddir . '/ultimate-branding/includes/favicon/' );

			$file = $uploaddir . '/ultimate-branding/includes/favicon/' . basename( $_FILES['favicon_file']['name'] );

			$this->remove_file( $file );

			if ( ! move_uploaded_file( $_FILES['favicon_file']['tmp_name'], $file ) )
				wp_redirect( add_query_arg( 'error', 'true', $referer ) );

			@chmod( $file, 0777 );

			if ( ! function_exists('imagecreatefromstring') )
				return __('The GD image library is not installed.');

			// Set artificially high because GD uses uncompressed images in memory
			@ini_set('memory_limit', '256M');
			$image = imagecreatefromstring( file_get_contents( $file ) );

			if ( ! is_resource( $image ) )
				wp_redirect( add_query_arg( 'error', 'true', wp_get_referer() ) );

			$size = @getimagesize( $file );
			if ( ! $size )
				wp_redirect( add_query_arg( 'error', 'true', wp_get_referer() ) );

			list( $orig_w, $orig_h, $orig_type ) = $size;

			$dims = image_resize_dimensions( $orig_w, $orig_h, 16, 16, true );
			if ( ! $dims )
				$dims = array( 0, 0, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h );

			list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

			$newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );

			imagecopyresampled( $newimage, $image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );

			// convert from full colors to index colors, like original PNG.
			if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) )
				imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );

			// we don't need the original in memory anymore
			imagedestroy( $image );

			if ( ! imagepng( $newimage, $uploaddir . '/ultimate-branding/includes/favicon/favicon.png' ) )
				wp_redirect( add_query_arg( 'error', 'true', wp_get_referer() ) );

			imagedestroy( $newimage );

			$stat = stat( $uploaddir . '/ultimate-branding/includes/favicon/' );
			$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
			@chmod( $uploaddir . '/ultimate-branding/includes/favicon/favicon.png', $perms );

			$this->remove_file( $file );

			ub_update_option( 'ub_favicon_dir',  $uploaddir . '/ultimate-branding/includes/favicon/favicon.png' );
			ub_update_option( 'ub_favicon_url',  $uploadurl . '/ultimate-branding/includes/favicon/favicon.png' );

		}

		return true;
	}

	function manage_output() {
		global $wpdb, $current_site, $page;

		if ( isset( $_GET['error'] ) )
			echo '<div id="message" class="error fade"><p>' . __( 'There was an error uploading the file, please try again.', 'ub' ) . '</p></div>';
		elseif ( isset( $_GET['updated'] ) )
			echo '<div id="message" class="updated fade"><p>' . __( 'Changes saved.', 'ub' ) . '</p></div>';


		$uploaddir = ub_wp_upload_dir();
		$uploadurl = ub_wp_upload_url();

		$favicon_dir = ub_get_option( 'ub_favicon_dir', false );
		$favicon_url = ub_get_option( 'ub_favicon_url', false );

		// Check for backwards compatibility
		if(!$favicon_dir && file_exists($uploaddir . '/ultimate-branding/includes/favicon/favicon.png' )) {
			ub_update_option( 'ub_favicon_dir',  $uploaddir . '/ultimate-branding/includes/favicon/favicon.png' );
			ub_update_option( 'ub_favicon_url',  $uploadurl . '/ultimate-branding/includes/favicon/favicon.png' );

			$favicon_dir = ub_get_option( 'ub_favicon_dir', false );
			$favicon_url = ub_get_option( 'ub_favicon_url', false );
		}

		?>

		<div class="postbox">
			<h3 class="hndle" style='cursor:auto;'><span><?php _e('Favicons','ub'); ?></span></h3>
			<div class="inside">
					<p class='description'><?php _e( 'This is the image that is displayed as a Favicon - ', 'ub' ); ?>
					<a href='<?php echo wp_nonce_url("?page=" . $page. "&amp;tab=images&amp;resetfavicon=yes&amp;action=process", 'ultimatebranding_settings_menu_images') ?>'><?php _e('Reset the image', 'ub') ?></a>
					</p>
					<?php
					if ( $favicon_dir && file_exists( $favicon_dir ) ) {
						echo '<img src="' . $favicon_url . '?'. md5( time() ) . '" />';
					} else {
						_e( 'None set', 'ub' );
					}
					?>
					</p>

					<h4><?php _e( 'Change Image', 'ub' ); ?></h4>
					<p class='description'>
						<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
						<input name="favicon_file" id="favicon_file" size="20" type="file">
					</p>

					<p class='description'><?php _e( 'Image must be 500KB maximum. It will be cropped to 16px wide and 16px tall. For best results use an image of this size. Allowed Formats: jpeg, gif, and png', 'ub' ); ?></p>
					<p class='description'><?php _e( 'Note that gif animations will not be preserved.', 'ub' ); ?></p>

			</div>
		</div>

	<?php
	}

	/**
	 * Delete a file
	 **/
	function remove_file( $file ) {
		@chmod( $file, 0777 );
		if( @unlink( $file ) ) {
			return true;
		} else {
			return false;
		}
	}


	function admin_head() {

		$uploaddir = ub_wp_upload_dir();
		$uploadurl = ub_wp_upload_url();

		if ( file_exists( $uploaddir . '/ultimate-branding/includes/favicon/favicon.png' ) ) {
			$site_ico = $uploadurl . '/ultimate-branding/includes/favicon/favicon.png';

			echo '<style type="text/css">
			#header-logo { background-image: url(' . $site_ico . '); }
			#wp-admin-bar-wp-logo > .ab-item .ab-icon { background-image: url(' . $site_ico . '); background-position: 0; }
			</style>';
		}

	}

	function wp_head() {

		$uploaddir = ub_wp_upload_dir();
		$uploadurl = ub_wp_upload_url();

		$favicon_dir = ub_get_option( 'ub_favicon_dir', false );
		$favicon_url = ub_get_option( 'ub_favicon_url', false );

		// Check for backwards compatibility
		if(!$favicon_dir && file_exists($uploaddir . '/ultimate-branding/includes/favicon/favicon.png' )) {
			ub_update_option( 'ub_favicon_dir',  $uploaddir . '/ultimate-branding/includes/favicon/favicon.png' );
			ub_update_option( 'ub_favicon_url',  $uploadurl . '/ultimate-branding/includes/favicon/favicon.png' );

			$favicon_dir = ub_get_option( 'ub_favicon_dir', false );
			$favicon_url = ub_get_option( 'ub_favicon_url', false );
		}

		if ( $favicon_dir && file_exists( $favicon_dir ) ) {
			echo '<link rel="shortcut icon" href="' . $favicon_url . '" />';
		}

	}


}

$ub_favicons = new ub_favicons();

?>