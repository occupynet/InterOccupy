<?php
// Setup  -- Probably want to keep this stuff... 

/**
 * Hello and welcome to Base! First, lets load the PageLines core so we have access to the functions 
 */	
require_once( dirname(__FILE__) . '/setup.php' );

add_action('pagelines_head', 'add_less' );

function add_less() {
	?>
	<link rel='stylesheet' id='less-css'  href='<?php bloginfo('stylesheet_directory'); ?>/style.less' type='text/css' media='all' />
	<?php 
}	

if ( !function_exists( 'of_get_option' ) ) {
function of_get_option($name, $default = false) {

	$optionsframework_settings = get_option('optionsframework');

	// Gets the unique option id
	$option_name = $optionsframework_settings['id'];

	if ( get_option($option_name) ) {
		$options = get_option($option_name);
	}

	if ( isset($options[$name]) ) {
		return $options[$name];
	} else {
		return $default;
	}
}
}

add_action('admin_init','optionscheck_change_santiziation', 100);

function optionscheck_change_santiziation() {
	remove_filter( 'of_sanitize_text', 'sanitize_text_field' );
	add_filter( 'of_sanitize_text', 'of_sanitize_text_field' );
}

function of_sanitize_text_field($input) {
	global $allowedtags;
	$custom_allowedtags["p"] = array();
    $custom_allowedtags = array_merge($custom_allowedtags, $allowedposttags);
	$output = wp_kses( $input, $allowedtags);
	return $output;
}