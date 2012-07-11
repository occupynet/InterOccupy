<?php
/*	Extends \Widget_Widget()
 *	
 *	Each widget must extend the \WP_Widget class (\ is for namespace).
 *	Class name format: pb_{PLUGINSLUG}_widget_{WIDGETSLUG}
 */
class pb_backupbuddy_widget_myslug extends WP_Widget {
	
	
	function __construct() {
		parent::WP_Widget(
							/* SLUG */				'myslug',
							/* TITLE0 */			'Carousel',
							/* DESCRIPTION */		array( 'description' => 'Display an image carousel.' )
						);
	}
	
	
	/**
	 *	\WP_Widget::widget() Override
	 *
	 *	Function is called when a widget is to be displayed. Use echo to display to page.
	 *
	 *	@param		$args		array		?
	 *	@param		$instance	array		Associative array containing the options saved on the widget form.
	 *	@return		null
	 */
	function widget( $args, $instance ) {
		if ( ( $instance['group'] != '' ) && ( isset( pb_backupbuddy::$options['groups'][$instance['group']] ) ) ) {
			pb_backupbuddy::load_controller( '_run_carousel' );
			echo run_carousel( $instance['group'] );
		} else {
			echo '{Error #45455489. Unknown group in widget. Please verify your widget settings are up to date for this Carousel item.}';
		}
	}
	
	
	/**
	 *	\WP_Widget::form() Override
	 *
	 *	Displays the widget form on the widget selection page for setting widget settings.
	 *	Widget defaults are pre-merged into $instance right before this function is called.
	 *	Use $widget->get_field_id() and $widget->get_field_name() to get field IDs and names for form elements.
	 *	Anything to display should be echo'd out.
	 *	@see WP_Widget class
	 *
	 *	@param		$instance	array		Associative array containing the options set previously in this form and/or the widget defaults (merged already).
	 *	@return		null
	 */
	function form( $instance ) {
		$instance = array_merge( (array)pb_backupbuddy::settings( 'widget_defaults' ), (array)$instance );
		
		if ( empty( pb_backupbuddy::$options['groups'] ) ) {
			echo 'You must create a PluginBuddy Carousel group to place this widget. Please do so within the plugin\'s page.';
		} else {
			?>
			<label for="<?php echo $this->get_field_id('group'); ?>">
				Carousel Group:
				<select class="widefat" id="<?php echo $this->get_field_id('group'); ?>" name="<?php echo $this->get_field_name('group'); ?>">
					<?php
					foreach ( (array) pb_backupbuddy::$options['groups'] as $id => $group ) {
						if( $instance['group'] == $id) {
							$select = ' selected ';
						} else {
							$select = '';
						}
						echo '<option value="' . $id . '"' . $select . '>' . stripslashes( $group['title'] ) . ' (' . count( $group['images'] ) . ' images)</option>';
					}
					?>
				</select>
			</label>
			
			<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
			<?php
		}
	}
	
	
} // End extending WP_Widget class.
?>