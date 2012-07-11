<?php
if(!class_exists('UltimateBrandingPublic')) {

	class UltimateBrandingPublic {

		var $build = 1;
		// The modules in the public class are only those that need to be loaded on the public side of the site as well
		var $modules = array(	'login-image.php' => 'login-image/login-image.php',
								'custom-admin-bar.php' => 'custom-admin-bar/custom-admin-bar.php',
								'global-footer-content.php' => 'global-footer-content/global-footer-content.php',
								'rebranded-meta-widget.php' => 'rebranded-meta-widget/rebranded-meta-widget.php',
								'site-generator-replacement.php' => 'site-generator-replacement/site-generator-replacement.php',
								'site-wide-text-change.php' => 'site-wide-text-change/site-wide-text-change.php',
								'favicons.php' => 'favicons.php',
								'custom-login-css.php' => 'custom-login-css.php'
							);

		var $plugin_msg = array();

		function __construct() {

			add_action( 'plugins_loaded', array(&$this, 'deactivate_existing_plugins' ) );	// Check other plugins

		}

		function UltimateBrandingPublic() {
			$this->__construct();
		}

		/**
		 *	Check plugins those will be used if they are active or not
		 */
		function deactivate_existing_plugins() {
			// We may be calling this function before admin files loaded, therefore let's be sure required file is loaded
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			$plugins = get_plugins(); // All installed plugins

			foreach( $plugins as $plugin_file => $plugin_data ) {
				if ( is_plugin_active( $plugin_file ) && in_array( $plugin_file, $this->modules ) ) {
					// Add the title to the message
					$this->plugin_msg[] = $plugin_data['Title'];
					// Add a notice if there isn't one already
					if(!has_action('network_admin_notices', array( &$this, 'deactivate_plugin_msg' ))) {
						add_action( 'network_admin_notices', array( &$this, 'deactivate_plugin_msg' ) );
					}
					// remove the module from the ones we are going to activate
					$key = array_search($plugin_file, $this->modules);
					if($key !== false) {
						unset( $this->modules[$key] );
					}
				}
			}

			// Load our remaining modules here
			foreach( $this->modules as $module => $plugin ) {
				if(ub_is_active_module( $module )) {
					ub_load_single_module( $module );
				}
			}
		}

	}
}
?>