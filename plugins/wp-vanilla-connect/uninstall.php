<?php 

if(basename($_SERVER['PHP_SELF']) == 'plugins.php' && defined('WP_UNINSTALL_PLUGIN') ){
	
	// Delete option from multi sites
	if (is_multisite()) {
		global $wpdb;
		$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
		if ($blogs) {
			foreach($blogs as $blog) {
				switch_to_blog($blog['blog_id']);
						
				// Delete option from site
				$options = get_option('wp_vanilla_connect_option');
				if(!isset($options['protect'])){
					delete_option('wp_vanilla_connect_option');
				}
			}
			restore_current_blog();
		}
	} else {
		// Delete option from main site
		$options = get_option('wp_vanilla_connect_option');
		if(!isset($options['protect'])){
			delete_option('wp_vanilla_connect_option');
		}
	}
	
}

?>