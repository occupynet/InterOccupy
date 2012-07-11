<?php



/*	Extends \Widget_Widget()
 *	
 *	Each widget must extend the WP_Widget class.
 */
class pb_backupbuddy_widget_copiouscomments extends WP_Widget {
	
	
	function __construct() {
		parent::WP_Widget(
							/* SLUG */				'copiouscomments',
							/* TITLE0 */			'Copious Comments',
							/* DESCRIPTION */		array( 'description' => 'Displays most commented posts with comment count and bar.' )
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
		pb_backupbuddy::load_controller( '_run_copious' );
		echo run_copious( $instance );
	}
	
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
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
		?>
		<label for="<?php echo $this->get_field_id('title'); ?>">Title:
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
		</label>
		<label for="<?php echo $this->get_field_id('posts'); ?>">Number of posts to display in list:
			<input class="widefat" id="<?php echo $this->get_field_id('posts'); ?>" name="<?php echo $this->get_field_name('posts'); ?>" type="text" value="<?php echo $instance['posts']; ?>" />
		</label>
		<label for="<?php echo $this->get_field_id('width'); ?>">Max width of widget (in percent) <?php pb_backupbuddy::tip( 'Maximum width in percent to allow this to use. If you want to limit the width or correct for padding issues, reduce this number to a lower percent. Valid values are 1 to 100.' ); ?> :
			<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $instance['width']; ?>" />
		</label>
		<label for="<?php echo $this->get_field_id('truncate'); ?>">Max characters in post title <?php pb_backupbuddy::tip( 'Maximum number of characters to display from a post title before truncating and adding an elipses (...). This must be a number. Default: 60' ); ?> :
			<input class="widefat" id="<?php echo $this->get_field_id('truncate'); ?>" name="<?php echo $this->get_field_name('truncate'); ?>" type="text" value="<?php echo $instance['truncate']; ?>" />
		</label>
					
		<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
		<?php
	}
	
	
} // End extending WP_Widget class.
?>