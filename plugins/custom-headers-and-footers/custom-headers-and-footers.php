<?php
/*
Plugin Name: Custom Headers and Footers
Plugin URI: http://www.poradnik-webmastera.com/projekty/custom_headers_and_footers/
Description: This plugin adds custom header and footer for main page content.
Author: Daniel Frużyński
Version: 1.2
Author URI: http://www.poradnik-webmastera.com/
Text Domain: custom-headers-and-footers
License: GPL2
*/

/*  Copyright 2009-2011  Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists( 'CustomHeadersAndFooters' ) ) {

class CustomHeadersAndFooters {
	// Constructor
	function CustomHeadersAndFooters() {
		// Initialise plugin
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		
		add_action( 'wp_head', array( &$this, 'wp_head' ) );
		add_action( 'loop_start', array( &$this, 'loop_start' ) );
		add_action( 'loop_end', array( &$this, 'loop_end' ) );
		add_action( 'wp_footer', array( &$this, 'wp_footer' ) );
	}
	
	// Initialise plugin
	function init() {
		load_plugin_textdomain( 'custom-headers-and-footers', false, dirname( plugin_basename ( __FILE__ ) ).'/lang' );
	}
	
	// Initialise plugin - admin part
	function admin_init() {
		register_setting( 'custom-headers-and-footers', 'chaf_meta_header', 'trim' );
		register_setting( 'custom-headers-and-footers', 'chaf_header', 'trim' );
		register_setting( 'custom-headers-and-footers', 'chaf_footer', 'trim' );
		register_setting( 'custom-headers-and-footers', 'chaf_blog_footer', 'trim' );
	}
	
	// Add Admin menu option
	function admin_menu() {
		add_submenu_page( 'options-general.php', 'Custom Headers and Footers', 
			'Custom Headers and Footers', 'manage_options', __FILE__, array( &$this, 'options_panel' ) );
	}
	
	// Display meta header
	function wp_head() {
		$meta = get_option( 'chaf_meta_header', '' );
		if ( $meta != '' ) {
			echo $meta, "\n";
		}
	}
	
	// Display header
	function loop_start( &$wp_query ) {
		global $wp_the_query;
		if ( ( $wp_query === $wp_the_query ) && !is_admin() && !is_feed() && !is_robots() && !is_trackback() ) {
			$text = get_option( 'chaf_header', '' );
			
			$text = convert_smilies( $text );
			$text = do_shortcode( $text );
			
			if ( $text != '' ) {
				echo $text, "\n";
			}
		}
	}
	
	// Display footer
	function loop_end( &$wp_query ) {
		global $wp_the_query;
		if ( ( $wp_query === $wp_the_query ) && !is_admin() && !is_feed() && !is_robots() && !is_trackback() ) {
			$text = get_option( 'chaf_footer', '' );
			
			$text = convert_smilies( $text );
			$text = do_shortcode( $text );
			
			if ( $text != '' ) {
				echo $text, "\n";
			}
		}
	}
	
	// Display blog footer
	function wp_footer() {
		if ( !is_admin() && !is_feed() && !is_robots() && !is_trackback() ) {
			$text = get_option( 'chaf_blog_footer', '' );
			
			$text = convert_smilies( $text );
			$text = do_shortcode( $text );
			
			if ( $text != '' ) {
				echo $text, "\n";
			}
		}
	}
	
	// Handle options panel
	function options_panel() {
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e('Custom Headers and Footers - Options', 'custom-headers-and-footers'); ?></h2>

<form name="dofollow" action="options.php" method="post">
<?php settings_fields( 'custom-headers-and-footers' ); ?>
<table class="form-table">

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="chaf_meta_header"><?php _e('Meta Headers:', 'custom-headers-and-footers'); ?></label>
</th>
<td>
<textarea rows="5" cols="57" id="chaf_meta_header" name="chaf_meta_header"><?php echo esc_html( get_option( 'chaf_meta_header' ) ); ?></textarea><br /><?php _e('This will be printed in <code>&lt;head&gt;</code> section, so you can use it to add additional meta tags.', 'custom-headers-and-footers'); ?>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="chaf_header"><?php _e('Header:', 'custom-headers-and-footers'); ?></label>
</th>
<td>
<textarea rows="5" cols="57" id="chaf_header" name="chaf_header"><?php echo esc_html( get_option( 'chaf_header' ) ); ?></textarea><br /><?php _e('This will be printed just before posts/pages and their lists.', 'custom-headers-and-footers'); ?>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="chaf_footer"><?php _e('Footer:', 'custom-headers-and-footers'); ?></label>
</th>
<td>
<textarea rows="5" cols="57" id="chaf_footer" name="chaf_footer"><?php echo esc_html( get_option( 'chaf_footer' ) ); ?></textarea><br /><?php _e('This will be printed just after posts/pages and their lists.', 'custom-headers-and-footers'); ?>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<label for="chaf_blog_footer"><?php _e('Blog Footer:', 'custom-headers-and-footers'); ?></label>
</th>
<td>
<textarea rows="5" cols="57" id="chaf_blog_footer" name="chaf_blog_footer"><?php echo esc_html( get_option( 'chaf_blog_footer' ) ); ?></textarea><br /><?php _e('This will be printed in blog footer.', 'custom-headers-and-footers'); ?>
</td>
</tr>

</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Save settings', 'custom-headers-and-footers'); ?>" /> 
</p>

</form>
</div>
<?php
	}
}

// Add functions from WP2.8 for previous WP versions
if ( !function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return wp_specialchars( $text );
	}
}

if ( !function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return attribute_escape( $text );
	}
}

add_option( 'chaf_meta_header', '' );
add_option( 'chaf_header', '' );
add_option( 'chaf_footer', '' );
add_option( 'chaf_blog_footer', '' );

$wp_custom_headers_and_footers = new CustomHeadersAndFooters();

} /* END */

?>