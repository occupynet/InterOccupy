<?php
/**
 * A unique identifier is defined to store the options in the database and reference them from the theme.
 * By default it uses the theme name, in lowercase and without spaces, but this can be changed if needed.
 * If the identifier changes, it'll appear as if the options have been reset.
 * Reference: http://wptheming.com/options-framework-plugin/
 */

function optionsframework_option_name() {

	// This gets the theme name from the stylesheet (lowercase and without spaces)
	$themename = get_option( 'stylesheet' );
	$themename = preg_replace("/\W/", "_", strtolower($themename) );

	$optionsframework_settings = get_option('optionsframework');
	$optionsframework_settings['id'] = $themename;
	update_option('optionsframework', $optionsframework_settings);

	// echo $themename;
}

/**
 * Defines an array of options that will be used to generate the settings page and be saved in the database.
 * When creating the 'id' fields, make sure to use all lowercase and no spaces.
 *
 */

function optionsframework_options() {
	
	// Pull all the categories into an array
	$options_categories = array();
	$options_categories_obj = get_categories();
	foreach ($options_categories_obj as $category) {
		$options_categories[$category->cat_ID] = $category->cat_name;
	}

	// Pull all tags into an array
	$options_tags = array();
	$options_tags_obj = get_tags();
	foreach ( $options_tags_obj as $tag ) {
		$options_tags[$tag->term_id] = $tag->name;
	}

	// Pull all the pages into an array
	$options_pages = array();
	$options_pages_obj = get_pages('sort_column=post_parent,menu_order');
	$options_pages[''] = 'Select a page:';
	foreach ($options_pages_obj as $page) {
		$options_pages[$page->ID] = $page->post_title;
	}

	// If using image radio buttons, define a directory path
	$imagepath =  get_template_directory_uri() . '/images/';

	$options = array();

	$options[] = array(
		'name' => __('Hub Settings', 'options_check'),
		'type' => 'heading');

	$options[] = array(
		'name' => __('Hub Title', 'options_check'),
		'desc' => __('Enter title for Hub, full name', 'options_check'),
		'id' => 'hub-title',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Hub Handle', 'options_check'),
		'desc' => __('Enter the handle for the hub: no spaces. ex. bankjustice', 'options_check'),
		'id' => 'hub-handle',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Contact Person', 'options_check'),
		'desc' => __('Point person for Hub', 'options_check'),
		'id' => 'contact-person',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Admin Email', 'options_check'),
		'desc' => __('Admin Email', 'options_check'),
		'id' => 'contact-email',
		'std' => '',
		'type' => 'text');
	$options[] = array(
		'name' => __('Short Description', 'options_check'),
		'desc' => __('In 30 words or less...', 'options_check'),
		'id' => 'short-desc',
		'std' => '',
		'type' => 'textarea');

	$options[] = array(
		'name' => __('Hub Image', 'options_check'),
		'desc' => __('Upload an image for your hub', 'options_check'),
		'id' => 'hub-image',
		'type' => 'upload');

	/**
	 * For $settings options see:
	 * http://codex.wordpress.org/Function_Reference/wp_editor
	 *
	 * 'media_buttons' are not supported as there is no post to attach items to
	 * 'textarea_name' is set by the 'id' you choose
	 */

	$wp_editor_settings = array(
		'wpautop' => true, // Default
		'textarea_rows' => 5,
		'tinymce' => array( 'plugins' => 'wordpress' )
	);

	$options[] = array(
		'name' => __('Full hub description', 'options_check'),
		'desc' => __( '', 'options_check' ),
		'id' => 'full-desc',
		'type' => 'editor',
		'settings' => $wp_editor_settings );

	$options[] = array(
		'name' => __('Colorpicker', 'options_check'),
		'desc' => __('Color for your hub', 'options_check'),
		'id' => 'hub-color',
		'std' => '',
		'type' => 'color' );

	$options[] = array(
		'name' => __('Input Checkbox', 'options_check'),
		'desc' => __('Regular call?', 'options_check'),
		'id' => 'regular-call',
		'std' => '1',
		'type' => 'checkbox');

	$options[] = array(
		'name' => __('Hub Tools', 'options_check'),
		'type' => 'heading');

	$options[] = array(
		'name' => __('Call Information', 'options_check'),
		'desc' => __('Basics about call dates and times', 'options_check'),
		'id' => 'call-info',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Hub Web Page', 'options_check'),
		'desc' => __('Enter full URL, including http://', 'options_check'),
		'id' => 'hub-website',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Hub Facebook Page', 'options_check'),
		'desc' => __('Enter full URL, including http://', 'options_check'),
		'id' => 'hub-facebook',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Hub Facebook Group', 'options_check'),
		'desc' => __('Enter full URL, including http://', 'options_check'),
		'id' => 'hub-facebook-group',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Hub Twitter', 'options_check'),
		'desc' => __('Enter Twitter handle ONLY', 'options_check'),
		'id' => 'hub-twitter',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('Input Checkbox', 'options_check'),
		'desc' => __('Works best with Facebook Page (rather than group) and twitter handle.', 'options_check'),
		'id' => 'social-tab',
		'std' => '0',
		'type' => 'checkbox');

	$options[] = array(
		'name' => __('O.NET Forum', 'options_check'),
		'desc' => __('Enter full URL, including http://', 'options_check'),
		'id' => 'hub-forum',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('O.NET Wiki', 'options_check'),
		'desc' => __('Enter full URL, including http://', 'options_check'),
		'id' => 'hub-wiki',
		'std' => '',
		'type' => 'text');

	$options[] = array(
		'name' => __('O.NET Classifieds', 'options_check'),
		'desc' => __('Enter full URL, including http://', 'options_check'),
		'id' => 'hub-classifieds',
		'std' => '',
		'type' => 'text');

	return $options;
}