<?php
/*
Plugin Name: Rebranded Meta Widget
Plugin URI: http://premium.wpmudev.org/project/rebranded-meta-widget
Version: 1.0.2
Description: Simply replaces the default Meta widget in all Multisite blogs with one that has the "Powered By" link branded for your site
Author: Aaron Edwards (Incsub)
Author URI: http://premium.wpmudev.org
Network: true
WDP ID: 136
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

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

add_action('widgets_init', 'rmw_register');

add_action('ultimatebranding_settings_menu_widgets','rmw_manage_output');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function rmw_register() {
  unregister_widget( 'WP_Widget_Meta' );
	register_widget( 'WP_Widget_Rebranded_Meta' );
}

function rmw_manage_output() {
	global $wpdb, $current_site, $page;

	?>

	<div class="postbox">
		<h3 class="hndle" style='cursor:auto;'><span><?php _e('Rebranded Meta Widget','ub'); ?></span></h3>
		<div class="inside">
			<p class='description'><?php _e( 'The Rebranded Meta Widget is enabled', 'ub' ); ?></p>
			<?php
				echo "<img src='" . ub_files_url('modules/rebranded-meta-widget-files/images/exampleimage.png') . "' />";
			?>
		</div>
	</div>

<?php
}

class WP_Widget_Rebranded_Meta extends WP_Widget {

	function WP_Widget_Rebranded_Meta() {
		$widget_ops = array('classname' => 'widget_meta', 'description' => __( "Log in/out, admin, feed and powered-by links", 'ub' ) );
		$this->WP_Widget('meta', __('Meta'), $widget_ops);
	}

	function widget( $args, $instance ) {

		global $current_site;

		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Meta') : $instance['title'], $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		if(function_exists('get_blog_option')) {

			$blog_id = (isset($current_site->blog_id)) ? $current_site->blog_id : UB_MAIN_BLOG_ID;

			$global_site_link = "http://". $current_site->domain . $current_site->path;
			$global_site_name = get_blog_option($blog_id, 'blogname');
		} else {
			$global_site_link = get_option('home');
			$global_site_name = get_option('blogname');
		}

?>
			<ul>
			<?php wp_register(); ?>
			<li><?php wp_loginout(); ?></li>
			<li><a href="<?php bloginfo('rss2_url'); ?>" title="<?php echo esc_attr(__('Syndicate this site using RSS 2.0')); ?>"><?php _e('Entries <abbr title="Really Simple Syndication">RSS</abbr>'); ?></a></li>
			<li><a href="<?php bloginfo('comments_rss2_url'); ?>" title="<?php echo esc_attr(__('The latest comments to all posts in RSS')); ?>"><?php _e('Comments <abbr title="Really Simple Syndication">RSS</abbr>'); ?></a></li>
			<li><a href="<?php echo $global_site_link; ?>" title="<?php echo esc_attr( sprintf( __('Powered by %s', 'ub'), $global_site_name) ); ?>"><?php echo esc_attr($global_site_name) ?></a></li>
			<?php wp_meta(); ?>
			</ul>
<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = strip_tags($instance['title']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
<?php
	}
}

?>