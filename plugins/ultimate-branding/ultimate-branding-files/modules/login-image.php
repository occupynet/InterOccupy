<?php
/*
Plugin Name: Login Image
Plugin URI: http://premium.wpmudev.org/project/login-image
Description: Allows you to change the login image
Author: Andrew Billits, Ulrich Sossou (Incsub)
Version: 1.1
Author URI: http://premium.wpmudev.org/project/
Network: true
Text_domain: login_image
WDP ID: 169
*/
/*
Modified from Plugin: Login Image V1.1
By: Andrew Billits, Ulrich Sossou (Incsub)
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

/**
 * Plugin main class
 **/
class Login_Image {

	/**
	 * PHP 4 constructor
	 **/
	function Login_Image() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 **/
	function __construct() {
		global $wp_version;

		// Admin interface
		add_action( 'ultimatebranding_settings_menu_images', array(&$this, 'manage_output') );
		add_filter( 'ultimatebranding_settings_menu_images_process', array( &$this, 'process' ) );

		// Login interface
		add_action( 'login_head', array( &$this, 'stylesheet' ), 99 );
		if ( ! is_multisite() ) {
			add_filter( 'login_headerurl', array(&$this, 'home_url') );
		}
	}

	/**
	 * Add site admin page
	 **/
	function login_headertitle() {
		return esc_attr( bloginfo( 'name' ) );
	}

	function home_url () {
		return home_url();
	}

	function stylesheet() {
		global $current_site;

		$uploaddir = ub_wp_upload_dir();
		$uploadurl = ub_wp_upload_url();

		$login_image_dir = ub_get_option( 'ub_login_image_dir', false );
		$login_image_url = ub_get_option( 'ub_login_image_url', false );

		// Check for backwards compatibility
		if(!$login_image_dir && file_exists($uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png' )) {
			ub_update_option( 'ub_login_image_dir',  $uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png' );
			ub_update_option( 'ub_login_image_url',  $uploadurl . '/ultimate-branding/includes/login-image/login-form-image.png' );

			$login_image_dir = ub_get_option( 'ub_login_image_dir', false );
			$login_image_url = ub_get_option( 'ub_login_image_url', false );
		}

		if ( file_exists( $login_image_dir ) ) {

			list($width, $height) = getimagesize( $login_image_dir );

		?>
		<style type="text/css">
			h1 a {
				background: url(<?php echo $login_image_url; ?>) no-repeat !important;
				width: 326px !Important;
				height: <?php echo $height; ?>px !Important;
				text-indent: -9999px;
				overflow: hidden;
				padding-bottom: 15px;
				display: block !Important;
				background-size: <?php echo $width; ?>px <?php echo $height; ?>px !Important;
			}
		</style>
		<?php
		}
	}

	function process() {
		global $plugin_page;

		if ( isset( $_GET['reset'] ) ) {

			$uploaddir = ub_wp_upload_dir();

			$login_image_dir = ub_get_option( 'ub_login_image_dir', false );

			if( $login_image_dir && file_exists($login_image_dir) ) $this->remove_file( $login_image_dir );

			ub_delete_option( 'ub_login_image_dir' );
			ub_delete_option( 'ub_login_image_url' );

		} elseif( !empty($_FILES['login_form_image_file']['name']) ) {

			$uploaddir = ub_wp_upload_dir();
			$uploadurl = ub_wp_upload_url();

			$uploaddir = untrailingslashit($uploaddir);
			$uploadurl = untrailingslashit($uploadurl);

			$login_image_dir = ub_get_option( 'ub_login_image_dir', false );

			if( $login_image_dir && file_exists($login_image_dir) ) $this->remove_file( $login_image_dir );

			if ( ! is_dir( $uploaddir . '/ultimate-branding/includes/login-image/' ) )
				wp_mkdir_p( $uploaddir . '/ultimate-branding/includes/login-image/' );

			$file = $uploaddir . '/ultimate-branding/includes/login-image/' . basename( $_FILES['login_form_image_file']['name'] );

			$this->remove_file( $file );

			if ( ! move_uploaded_file( $_FILES['login_form_image_file']['tmp_name'], $file ) )
				wp_redirect( add_query_arg( 'error', 'true', wp_get_referer() ) );

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

			$dims = image_resize_dimensions( $orig_w, $orig_h, 326, 0, false );
			if ( ! $dims )
				$dims = array( 0, 0, 0, 0, $orig_w, $orig_h, $orig_w, $orig_h );
			list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

			$newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );

			imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );

			// convert from full colors to index colors, like original PNG.
			if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) )
				imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );

			// we don't need the original in memory anymore
			imagedestroy( $image );

			if ( ! imagepng( $newimage, $uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png' ) )
				wp_redirect( add_query_arg( 'error', 'true', wp_get_referer() ) );

			imagedestroy( $newimage );

			$stat = stat( $uploaddir . '/ultimate-branding/includes/login-image/' );
			$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
			@chmod( $uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png', $perms );

			$this->remove_file( $file );

			ub_update_option( 'ub_login_image_dir',  $uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png' );
			ub_update_option( 'ub_login_image_url',  $uploadurl . '/ultimate-branding/includes/login-image/login-form-image.png' );

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

		$login_image_dir = ub_get_option( 'ub_login_image_dir', false );
		$login_image_url = ub_get_option( 'ub_login_image_url', false );

		// Check for backwards compatibility
		if(!$login_image_dir && file_exists($uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png' )) {
			ub_update_option( 'ub_login_image_dir',  $uploaddir . '/ultimate-branding/includes/login-image/login-form-image.png' );
			ub_update_option( 'ub_login_image_url',  $uploadurl . '/ultimate-branding/includes/login-image/login-form-image.png' );

			$login_image_dir = ub_get_option( 'ub_login_image_dir', false );
			$login_image_url = ub_get_option( 'ub_login_image_url', false );
		}

		?>

		<div class="postbox">
			<h3 class="hndle" style='cursor:auto;'><span><?php _e('Login Image','ub'); ?></span></h3>
			<div class="inside">
					<p class='description'><?php _e( 'This is the image that is displayed on the login page (wp-login.php) - ', 'ub' ); ?>
					<a href='<?php echo wp_nonce_url("?page=" . $page. "&amp;tab=images&amp;reset=yes&amp;action=process", 'ultimatebranding_settings_menu_images') ?>'><?php _e('Reset the image', 'ub') ?></a>
					</p>
					<?php
					if ( $login_image_dir && file_exists( $login_image_dir ) ) {
						echo '<img src="' . $login_image_url . '?'. md5( time() ) . '" />';
					} else {
						echo '<img src="' . site_url( 'wp-admin/images/wordpress-logo.png' ) . '" />';
					}
					?>
					</p>

					<h4><?php _e( 'Change Image', 'login_image' ); ?></h4>
					<p class='description'>
						<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
						<input name="login_form_image_file" id="login_form_image_file" size="20" type="file">
					</p>

					<p class='description'><?php _e( 'Image must be 500KB maximum. It will be cropped to 310px wide and 70px tall. For best results use an image of this size. Allowed Formats: jpeg, gif, and png', 'ub' ); ?></p>
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

}

$ub_loginimage = new Login_Image();

