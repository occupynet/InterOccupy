<?php

include_once(dirname(__FILE__).'/config.php');

global $gcs_plugin_name;

add_action( 'widgets_init', 'gsc_load_widgets' );

/**
 * Register widget.
 */
function gsc_load_widgets() {
	register_widget( 'GSC_Widget' );
}

/**
 * Google Custom Search Widget class.
 */
class GSC_Widget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function GSC_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'google custom search', 'description' => __('Unleash Google Search on Your Website.', 'google custom search') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'gsc-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'gsc-widget', __('Google Custom Search'), $widget_ops, $control_ops );
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		$gsc_search_engine_id = get_option('gsc_search_engine_id');

		/* Get variables from the widget settings. */
		$display_results_option = $instance['display_results_option'];
		$hide_widget_format = isset( $instance['hide_widget_format'] ) ? $instance['hide_widget_format'] : false;

		/* Before widget (defined by themes). */
		if ( ! $hide_widget_format )
				echo $before_widget;

		display_search_box($display_results_option);		

		/* After widget (defined by themes). */
		if ( ! $hide_widget_format )
			echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['display_results_option'] = $new_instance['display_results_option'];
		$instance['hide_widget_format'] = $new_instance['hide_widget_format'];

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'display_results_option' => 0, 'hide_widget_format' => false );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Display Results: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'display_results_option' ); ?>"><?php _e('Display Results:', 'example'); ?></label> 
			<select id="<?php echo $this->get_field_id( 'display_results_option' ); ?>" name="<?php echo $this->get_field_name( 'display_results_option' ); ?>" class="widefat" style="width:100%;">
				<option <?php if ( DISPLAY_RESULTS_AS_POP_UP == $instance['display_results_option'] ) echo 'selected="selected"'; ?> value=0 >Pop-up </option>
				<option <?php if ( DISPLAY_RESULTS_IN_UNDER_SEARCH_BOX == $instance['display_results_option'] ) echo 'selected="selected"'; ?> value=1 >Within Widget </option>
				<option <?php if ( DISPLAY_RESULTS_CUSTOM == $instance['display_results_option'] ) echo 'selected="selected"'; ?> value=2 >Custom (needs configuring. refer to plugin docs)</option>
			</select>
		</p>

		<!-- Hide Widget Format -->
		<p>
			<input class="checkbox" type="checkbox" <?php if($instance['hide_widget_format']=='on') echo 'checked'; ?> id="<?php echo $this->get_field_id( 'hide_widget_format' ); ?>" name="<?php echo $this->get_field_name( 'hide_widget_format' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'hide_widget_format' ); ?>"><?php _e('Hide Widget Format?', 'example'); ?></label>
		</p>

	<?php
	}
}

?>