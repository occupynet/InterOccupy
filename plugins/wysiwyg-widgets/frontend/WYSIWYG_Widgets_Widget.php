<?php
if (!class_exists('WYSIWYG_Widgets_Widget')) {

    class WYSIWYG_Widgets_Widget extends WP_Widget {

        function __construct() {
            $widget_ops = array('classname' => 'wysiwyg_widget widget_text', 'description' => __('A widget with a WYSIWYG / Rich Text editor - supports media uploading'));
            $control_ops = array('width' => 560, 'height' => 400);
			$id_base = 'wysiwyg_widget';
            parent::__construct($id_base, 'WYSIWYG Widget', $widget_ops, $control_ops);
        }

        function widget($args, $instance) {
            extract($args);
            $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
            $text = apply_filters('widget_text', $instance['text'], $instance);
            echo $before_widget;
            
            if (!empty($title)) {
                echo $before_title . $title . $after_title;
            }
            
            ?>

            <div class="textwidget"><?php echo wpautop($text); ?></div>
            
            <?php
            echo $after_widget;
        }

        function update($new_instance, $old_instance) {
            $instance = $old_instance;

            $instance['title'] = strip_tags($new_instance['title']);
            $instance['type'] = strip_tags($new_instance['type']);
            
            if (current_user_can('unfiltered_html'))
                $instance['text'] = $new_instance['text'];
            else
                $instance['text'] = stripslashes(wp_filter_post_kses(addslashes($new_instance['text'])));

            return $instance;
        }

        function form($instance) {
            $instance = wp_parse_args((array) $instance, array('title' => '', 'text' => '', 'type' => 'visual'));
            
            $title = strip_tags($instance['title']);
            $text = $instance['text'];
            $type = esc_textarea($instance['type']);
            
            ?>
            <span class="wysiwyg_widget"></span>
            <input id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>" class="wwe_type" type="hidden" value="<?php echo esc_attr($type); ?>" />
           
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>


            <div class="wwe_container">
                <?php wp_editor($text, $this->get_field_id('text'), array( 'textarea_name' => $this->get_field_name('text'), 'textarea_rows' => 25 )) ?>
            </div>
            
            <?php
        }

    }

}