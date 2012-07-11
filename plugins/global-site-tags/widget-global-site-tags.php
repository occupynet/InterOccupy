<?php
/*
Plugin Name: Global Site Tags Widget
Plugin URI: http://premium.wpmudev.org/project/global-site-tags
Description: This powerful plugin allows you to simply display a global tag cloud for your entire WordPress Multisite network. How cool is that!
Author: Andrew Billits (Incsub)
Version: 2.1
Author URI: http://premium.wpmudev.org
*/

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//
$widget_global_site_tags_title = __('Global Site Tags', "globalsitetags");
$widget_global_site_tags_nice_title = 'global_site_tags';
$widget_global_site_tags_description = __('Displays tags from all blogs', "globalsitetags");
$widget_global_site_tags_height = 600;
$widget_global_site_tags_width = 300;
//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

add_action('widgets_init', 'widget_global_site_tags_init');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function widget_global_site_tags_init() {
	register_widget('widget_global_site_tags');
}

//------------------------------------------------------------------------//
//---Classes--------------------------------------------------------------//
//------------------------------------------------------------------------//
class widget_global_site_tags extends WP_Widget {

	//Declares the widget_global_site_tags class.
	function widget_global_site_tags() {
		global $widget_global_site_tags_title, $widget_global_site_tags_nice_title, $widget_global_site_tags_description, $widget_global_site_tags_height, $widget_global_site_tags_width;

		// Load the text-domain
		$locale = apply_filters( 'globalsitetags_locale', get_locale() );
		$mofile = dirname(__FILE__) . "/languages/globalsitetags-$locale.mo";

		if ( file_exists( $mofile ) )
			load_textdomain( 'globalsitetags', $mofile );

		$widget_ops = array('classname' => 'widget_global_site_tags', 'description' => __($widget_global_site_tags_description) );
		$control_ops = array('width' => $widget_global_site_tags_width, 'height' => $widget_global_site_tags_height);
		$this->WP_Widget($widget_global_site_tags_nice_title, __($widget_global_site_tags_title), $widget_ops, $control_ops);
	}


	//Displays the Widget
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title']);
		$lineOne = empty($instance['lineOne']) ? 'Hello' : $instance['lineOne'];
		$lineTwo = empty($instance['lineTwo']) ? 'World' : $instance['lineTwo'];

		//Before the widget
		echo $before_widget;

		//The title
		if ( $title )
		echo $before_title . $title . $after_title;

		//Make the Hello World Example widget
		//echo '<div style="text-align:center;padding:10px;">' . $lineOne . '<br />' . $lineTwo . "</div>";
		echo global_site_tags_tag_cloud('',$instance['number'],$instance['tag_cloud_order'],$instance['low_font_size'],$instance['high_font_size'],'','', $instance['poststype']);

		//After the widget
		echo $after_widget;
	}


	//Saves the widgets settings.
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['tag_cloud_order'] = strip_tags(stripslashes($new_instance['tag_cloud_order']));
		$instance['number'] = strip_tags(stripslashes($new_instance['number']));
		$instance['high_font_size'] = strip_tags(stripslashes($new_instance['high_font_size']));
		$instance['low_font_size'] = strip_tags(stripslashes($new_instance['low_font_size']));
		$instance['poststype'] = strip_tags(stripslashes($new_instance['poststype']));

		return $instance;
	}

	//Creates the edit form for the widget.
	function form($instance) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('title'=>'', 'tag_cloud_order'=>'count', 'number'=>25, 'high_font_size'=>52, 'low_font_size'=>14, 'poststype' => 'post') );

		//Output the options
		?>
		<p style="text-align:left;"><label for="<?php echo $this->get_field_name('title'); ?>"><?php _e('Widget Title', "globalsitetags"); ?><br /><input style="width: 100%;" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo htmlspecialchars($instance['title']); ?>" />
        </label></p>
		<p style="text-align:left;"><label for="<?php echo $this->get_field_name('tag_cloud_order'); ?>"><?php _e('Tag Cloud Order', "globalsitetags"); ?><br /><select style="width: 100%;" id="<?php echo $this->get_field_id('tag_cloud_order'); ?>" name="<?php echo $this->get_field_name('tag_cloud_order'); ?>">
            <option value="count" <?php if ( $instance['tag_cloud_order'] == 'count' ) { echo 'selected="selected"'; } ?> ><?php _e('Tag Count', "globalsitetags"); ?></option>
            <option value="most_recent" <?php if ( $instance['tag_cloud_order'] == 'most_recent' ) { echo 'selected="selected"'; } ?> ><?php _e('Most Recent', "globalsitetags"); ?></option>
        </select>
        </label></p>
		<p style="text-align:left;"><label for="<?php echo $this->get_field_name('number'); ?>"><?php _e('Number of Tags', "globalsitetags"); ?><br /><select style="width: 100%;" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>">
        	<?php
			unset($counter);
			for ( $counter = 1; $counter <= 100; $counter += 1) {
			?>
            <option value="<?php echo $counter; ?>" <?php if ( $instance['number'] == $counter ) { echo 'selected="selected"'; } ?> ><?php echo $counter; ?></option>
        	<?php
			}
			?>
        </select>
        </label></p>

		<p style="text-align:left;"><label for="<?php echo $this->get_field_name('high_font_size'); ?>"><?php _e('Largest Font Size', "globalsitetags"); ?><br /><select style="width: 100%;" id="<?php echo $this->get_field_id('high_font_size'); ?>" name="<?php echo $this->get_field_name('high_font_size'); ?>">
        	<?php
			unset($counter);
			for ( $counter = 1; $counter <= 100; $counter += 1) {
			?>
            <option value="<?php echo $counter; ?>" <?php if ( $instance['high_font_size'] == $counter ) { echo 'selected="selected"'; } ?> ><?php echo $counter; ?>px</option>
        	<?php
			}
			?>
        </select>
        </label></p>

		<p style="text-align:left;"><label for="<?php echo $this->get_field_name('low_font_size'); ?>"><?php _e('Smallest Font Size', "globalsitetags"); ?><br /><select style="width: 100%;" id="<?php echo $this->get_field_id('low_font_size'); ?>" name="<?php echo $this->get_field_name('low_font_size'); ?>">
        	<?php
			unset($counter);
			for ( $counter = 1; $counter <= 100; $counter += 1) {
			?>
            <option value="<?php echo $counter; ?>" <?php if ( $instance['low_font_size'] == $counter ) { echo 'selected="selected"'; } ?> ><?php echo $counter; ?>px</option>
        	<?php
			}
			?>
        </select>
        </label></p>

		<?php
			$post_types = $this->get_post_types();

		?>

		<p style="text-align:left;"><label for="<?php echo $this->get_field_name( 'poststype' ); ?>" style="line-height:35px;display:block;"><?php _e('Post Type', 'globalsitetags'); ?>:<br />
        <select name="<?php echo $this->get_field_name( 'poststype' ); ?>" id="<?php echo $this->get_field_id( 'poststype' ); ?>" style="width:95%;">
        	<option value="all" <?php selected( $instance['poststype'], 'all' ); ?> ><?php _e('all', 'globalsitetags'); ?></option>
		<?php
				if(!empty($post_types)) {
					foreach( $post_types as $r ) {
						?>
						<option value="<?php echo $r; ?>" <?php selected( $instance['poststype'], $r ); ?> ><?php _e($r, 'globalsitetags'); ?></option>
						<?php
					}
				} else {
					?>
					<option value="post" <?php selected( $instance['poststype'], 'post' ); ?> ><?php _e('post', 'globalsitetags'); ?></option>
					<?php
				}
		?>
        </select>
        </label></p>

		<?php
	}

	function get_post_types() {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT post_type FROM " . $wpdb->base_prefix . "site_posts GROUP BY post_type" );

		$results = $wpdb->get_col( $sql );

		return $results;
	}

}
?>