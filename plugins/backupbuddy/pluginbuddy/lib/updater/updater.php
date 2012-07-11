<?php



/*	Class updaterpreloader
 *	
 *	1. Registers each plugin's updater version into a global variable.
 *	2. Selects the updater from all PB plugins that has the latest updater version.
 *	3. Loads the updater class of the most up to date updater and instantiates it.
 *	
 */
class pb_backupbuddy_updaterpreloader {
	
	private $_updater_version = '1.0.21';
	private $_slug = '';
	
	function __construct( $slug ) {
	
		$this->_slug = $slug;
		add_action( 'init', array( &$this, 'upgrader_register' ), 50 );
		add_action( 'init', array( &$this, 'upgrader_select' ), 100 );
		
	} // End __construct().
	
	
	/*	upgrader_register()
	 *	
	 *	Registers each plugin's upgrader (by namespace) into a global variable for later checking to run latest updater.
	 *	
	 *	@return		null
	 */
	function upgrader_register() {
		$GLOBALS['pluginbuddy_updater_versions'][$this->_slug] = $this->_updater_version;
	} // End upgrader_register().
	
	
	/*	upgrader_select()
	 *	
	 *	Select the most recent upgrader based on the version in the global variable.
	 *	
	 *	@return	null
	 */
	function upgrader_select() {
		// Fallback for older compatibility. TODO: Remove at some point.
		if ( !isset( $GLOBALS[ 'pluginbuddy_updater_versions' ] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/updater/updater.php' );
			return;
		}
		
		// Go through each global and find the highest updater version and the plugin slug.
		$updater_version = 0;
		$plugin_var = '';
		foreach ( $GLOBALS[ 'pluginbuddy_updater_versions' ] as $slug => $version) { // Loop through all versions and find the highest.
			if ( version_compare( $version, $updater_version, '>=' ) ) {
				$updater_version = $version;
				$plugin_slug = $slug;
			}
		}
		
		// Instantiate the upgrader of the newest version.
		$this->upgrader_instantiate( $plugin_slug );
	} // End upgrader_select().
	
	
	function upgrader_instantiate( $plugin_slug ) {
		$args = array(
			'parent' => $this, 
			'remote_url' => 'http://updater2.ithemes.com/index.php',
			'version' => pb_backupbuddy::settings( 'version' ),
			'plugin_slug' => pb_backupbuddy::settings( 'slug' ),
			'plugin_path' => plugin_basename( pb_backupbuddy::plugin_path() . '/' . pb_backupbuddy::settings( 'init' ) ),
			'plugin_url' => pb_backupbuddy::plugin_url(),
			'product' => pb_backupbuddy::settings( 'slug' ), // hmm? difference from slug?
			'time' => 43200,
			'return_format' => 'json',
			'method' => 'POST',
			'upgrade_action' => 'check' );
		
		// Instantiate object of latest updater (by namespace) and apply to this plugin's pluginbuddy class (in this namespace).
		//$class_name = $plugin_namespace . "\\updater";
		$target_plugin_class_name = 'pb_' . $plugin_slug . '_updater'; 
		$this_plugin_class_name = 'pb_' . $this->_slug;
		//$this_plugin_class_name::$_updater = new $target_plugin_class_name( $args );
		pb_backupbuddy::$_updater = new $target_plugin_class_name( $args );
	} //end upgrader_instantiate
}


















/*
 *	PluginBuddy.com
 *	Author: Ronald Huereca referencing Dustin Bolton's prior version < http://pluginbuddy.com >
 *
 *	Created:	February 20, 2010
 *	Updated:	January 2, 2012
 * 
 *	Upgrade system for PluginBuddy.com.
 */

//Plugin Upgrade Class
class pb_backupbuddy_updater {
	private $parent = false;
	private $plugin_url = false;
	private $remote_url = false;
	private $version = false;
	private $plugin_slug = false;
	private $plugin_path = false;
	private $product = false;
	private $return_format = 'json';
	private $upgrade_action = '';
	private $method = 'POST';
	private $time_upgrade_check = false;
	private $plugins = '';
	private $authenticated = false;
	
	function __construct( $args = array() ) {
		//Load defaults
		extract( wp_parse_args( $args, array( 
			'parent' => false,
			'remote_url' => false,
			'version' => false,
			'plugin_slug' => false,
			'plugin_path' => false,
			'plugin_url' => false,
			'product' => false,
			'time' => 43200,
			'return_format' => 'json',
			'method' => 'POST',
			'upgrade_action' => 'check'
		) ) );
		$this->parent = $parent;
		$this->product = $product;
		$this->plugin_url = $plugin_url;
		$this->remote_url = $remote_url;
		$this->version = $version;
		$this->plugin_slug = $plugin_slug;
		$this->plugin_path = $plugin_path;
		$this->time_upgrade_check = apply_filters( "pluginbuddy_time_{$plugin_slug}", $time );
		$this->return_format = $return_format;
		$this->upgrade_action = $upgrade_action;
		$this->method = $method;
		
						
		//Get plugins for upgrading
		$this->plugins = $this->get_plugin_options();
						
		if ( !isset( $this->plugins[ $this->plugin_slug ] ) ) {
			$this->plugins[ $this->plugin_slug ] = $this->get_defaults();
		}
		
		//Double-check - If key_status isn't set for the plugin, try to remotely retrieve a key (should only run once per new plugin added)
		if ( $this->plugins[ $this->plugin_slug ]->key_status == 'not_set' ) {
			$body = array(
				'action' => 'licenses',
				'actionb' => 'maybe_license',
				'site' => str_replace( 'https://', 'http://', site_url() )
			);
			$response = $this->perform_remote_request( array( 'body' => $body ) );
			if ( is_object( $response ) && $response->key_status == 'ok' ) {
				$this->plugins[ $this->plugin_slug ]->key = $response->key;
				$this->plugins[ $this->plugin_slug ]->key_status = 'ok';
				//$this->plugins[ $this->plugin_slug ]->guid = $response->guid;
				$this->save_plugin_options();
			} 
		}
		
		add_action( 'admin_init', array( &$this, 'init' ), 1 );
		add_action( 'after_plugin_row_' . $this->plugin_path, array( &$this, 'plugin_row' ) );
		add_action( 'network_admin_plugin_action_links_'. $this->plugin_path, array( &$this, 'plugin_links' ) );
		
		add_action('plugin_action_links_'. $this->plugin_path, array( &$this, 'plugin_links' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_right_links' ), 10, 2 );
		
		add_action( 'wp_loaded', array( &$this, 'view_changelog' ) );
		add_action( 'admin_print_scripts-plugins.php', array( &$this, 'plugin_print_scripts' ) );
		add_action( 'admin_print_styles-plugins.php', array( &$this, 'plugin_print_styles' ) );
		
		//Ajax actions
		add_action( 'wp_ajax_' . $this->plugin_slug . 'licenses', array( &$this, 'view_licenses' ) );
		
		
		// Manage WP hooks.
		add_filter( 'mwp_premium_update_notification', array( &$this, 'mwp_update_notification' ) ); // Hook to this filter to provide the new version of your plugin if available.
		add_filter( 'mwp_premium_perform_update', array( &$this, 'mwp_perform_update' ) ); // Hook to this filter to instruct ManageWP How to upgrade your plugin.
		add_filter( 'mwp_premium_update_check', array( &$this, 'mwp_update_check' ) ); // Hook to this filter if you want ManageWP to be able to force check for updates. We call this when the user clicks "Refresh" in their ManageWP Dashboard.
		
	} // End __construct().
	
	
	
	public function init() {
		//Set up update checking and hook in the filter for automatic updates
		//Do upgrade stuff
		if (current_user_can("administrator")) {
			if ( isset( $_GET[ 'pluginbuddy_refresh' ] ) ) {
				$response = $this->check_for_updates( true );
			} else {
				$this->check_periodic_updates();
			}
			if ( isset( $this->plugins[ $this->plugin_slug ]->new_version ) ) {
				if( !version_compare( $this->version, $this->plugins[ $this->plugin_slug ]->new_version, '>=' ) ) {
					add_filter( 'site_transient_update_plugins', array( &$this, 'update_plugins_filter' ),1000 );
				}
			}
		} //end if user can admin
	} // End init().
	
	
	
	//Performs a periodic upgrade check to see if the plugin needs to be upgraded or not
	private function check_periodic_updates() {	
		//echo 'periodic';
		$last_update = isset( $this->plugins[ $this->plugin_slug ]->last_update ) ? $this->plugins[ $this->plugin_slug ]->last_update : false;
		
		if ( !$last_update ) { $last_update = $this->check_for_updates(); }
		$last_update = is_int( $last_update ) ? $last_update : time();
		
		if( ( time() - $last_update ) > $this->time_upgrade_check ){
				$this->check_for_updates();
		}
	} //end check_periodic_updates
	
	public function get_remote_version() {
		if ( isset( $this->plugins[ $this->plugin_slug ]->new_version ) ) {
			return $this->plugins[ $this->plugin_slug ]->new_version;
		}
		return false;
	} //end get_remote_version
	
	private function get_plugin_options() {
		//delete_option( 'pluginbuddy_plugins' );
		//die( 'test' );
		//Get plugin options
		if ( is_multisite() ) {
			$options = get_site_option( 'pluginbuddy_plugins', false, false );
		} else {
			$options = get_option( 'pluginbuddy_plugins' );
		}

		if ( !$options || !is_array( $options ) ) {
			$options = array();
		}
		
			

		return $options;
	} //end get_plugin_options
	
	private function save_plugin_options( $clearhash = false) {
		//echo 'saving';
		
		//Get plugin options
		$options = $this->get_plugin_options(); //Since multiple plugins are using the same class variable, make sure the class variable is up to date before updating it
		$options[ $this->plugin_slug ] = $this->plugins[ $this->plugin_slug ];
		if ( !empty( $this->plugins[ 'userhash' ] ) ) $options[ 'userhash' ] = $this->plugins[ 'userhash' ];
		if ( !empty( $this->plugins[ 'username' ] ) ) $options[ 'username' ] = $this->plugins[ 'username' ];
		if ( $clearhash == true ) {
			$this->plugins[ 'userhash' ] = $options[ 'userhash' ] = '';
			$this->plugins[ 'username' ] = $options[ 'username' ] = '';
		}
		if ( $this->plugin_slug == 'pluginbuddy_loopbuddy' ) {
			//die( '<pre>' . print_r( $options[ $this->plugin_slug ], true ) );
		}
		
		//echo '<pre>' . print_r( $options, true ) . '</pre>';
		
		if ( is_multisite() ) {
			$this->update_site_option( 'pluginbuddy_plugins', $options );
		} else {
			$this->update_option( 'pluginbuddy_plugins', $options );
		}
	} //end save_plugin_options
	
	private function get_defaults() {
		//Fill out defaults for the global variable
		if ( !isset( $this->plugins[ 'userhash' ] ) ) {
			$this->plugins[ 'userhash' ] = '';
			$this->plugins[ 'username' ] = '';
		}
		
		//Fill out defaults for the individual plugin
		$plugin_options = new stdClass;
		$plugin_options->url = $this->plugin_url;
		$plugin_options->slug = $this->plugin_slug;
		$plugin_options->package = '';
		$plugin_options->new_version = $this->version;
		$plugin_options->last_update = time();
		$plugin_options->id = "0";
		$plugin_options->key = false;
		$plugin_options->key_status = 'not_set';
		$plugin_options->guid = uniqid( '' );
		return $plugin_options;
	} //end get_defaults
	
	
	
	/*	force_check_for_updates()
	 *	
	 *	Forces checking for updates now. Used by managewp hooks as a shortcut to check_for_updates().
	 *	@see force_check_for_updates()
	 *	
	 *	@return		null
	 */
	public function force_check_for_updates() {
		
		$this->check_for_updates( true );
		
		
		
	} // End force_check_for_update().
	
	
	
	public function check_for_updates( $manual = false ) {
		//echo 'checking';
		
		if ( !is_array( $this->plugins ) ) return false;
		//Check to see that plugin options exist
//TODO		//$this->plugins = $this->get_plugin_options();
		
		if ( !isset( $this->plugins[ $this->plugin_slug ] ) ) {
			$this->plugins[ $this->plugin_slug ] = $this->get_defaults();
			
			
			$this->save_plugin_options();
		}
		
		
		$current_plugin = $this->plugins[ $this->plugin_slug ];
		if( ( time() - $current_plugin->last_update ) > $this->time_upgrade_check || $manual ) {
			//Check for updates
			//echo 'remote';
			
			$version_info = $this->perform_remote_request( array( 'action' => $this->upgrade_action, 'return_format' => $this->return_format, 'remote_url' => $this->remote_url ) );
			if ( is_wp_error( $version_info ) ) return false;					
			if ( ! is_object( $version_info ) ) return false;
			
			 
			 //Update a new version
			 if ( version_compare( $version_info->latest_version, $this->version, '>' ) ) {
			 	$current_plugin->new_version = $version_info->latest_version;
			 	$current_plugin->package = $version_info->download_url;
			 } else {
			 	$current_plugin->new_version = $this->version;
			 	$current_plugin->package = '';
			 }
			
			 
			 //Update key and license info
			 if ( $version_info->key_status != 'ok' ) {
			 	$current_plugin->key = '';
			 	$current_plugin->key_status = $version_info->key_status;
			 } elseif ( $version_info->key_status == 'ok' ) {
			 	$current_plugin->key_status = 'ok';
			 }
			 $current_plugin->last_update = time();
			 
			 $this->plugins[ $this->plugin_slug ] = $current_plugin;
			 $this->save_plugin_options();
			 
		}
		
		return $this->plugins[ $this->plugin_slug ];
	} //end check_for_updates
	
	private function output($content) {
		echo '</tr>';
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">' . $content . '</div></td>';
	} //end output
	
	public function perform_remote_request( $args ) {
	
		$defaults = array(
			'action' => false,
			'body' => array(),
			'headers' => array(),
			'return_format' => 'json',
			'remote_url' => false,
			'method' => false,
		);
		$args = wp_parse_args( $args, $defaults );
		
		
		extract( $args );
		
		$remote_url = $remote_url ? $remote_url : $this->remote_url;
		
		$body = wp_parse_args( $body, array( 
			'product' => $this->product,
			'key' => $this->plugins[ $this->plugin_slug ]->key,
			'guid' => $this->plugins[ $this->plugin_slug ]->guid,
			'userhash' => $this->plugins[ 'userhash' ],
			'username' => $this->plugins[ 'username' ],
			'action' => $action,
			'wp-version' => get_bloginfo( 'version' ),
			'referer' => str_replace( 'https://', 'http://', site_url() ),
			'site' => str_replace( 'https://', 'http://', site_url() ),
			'version' => $this->version,
		) ) ;
		
		$body = apply_filters( "pluginbuddy_remote_body_{$this->plugin_slug}", $body );
		$method = $method ? $method : $this->method;
		if ( $method == 'GET' ) {
			$remote_url = add_query_arg( $body, $remote_url );
		} else {
			$body = http_build_query( $body );
		}
		
		$headers = wp_parse_args( $headers, array( 
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Content-Length' => is_array( $body ) ? 0 : strlen( $body )
		) );
		$headers = apply_filters( "pluginbuddy_remote_headers_{$this->plugin_slug}", $headers );
		
		
		
		$post = apply_filters( "pluginbuddy_remote_args_{$this->plugin_slug}", array( 'headers' => $headers, 'body' => $body, 'timeout' => 20 ) );				
		
		//die( '<pre>' . print_r( $post, true ) );				
		//Retrieve response				
		if ( $method == 'GET' ) {
			$response = wp_remote_get( esc_url_raw( $remote_url ), $post );
		} else {
			$response = wp_remote_post( esc_url_raw( $remote_url ), $post );
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		//$current_plugin = $this->plugins[ 'pluginbuddy_loopbuddy' ];
		
		if ( $response_code != 200 || is_wp_error( $response_body ) ) {
			return false;
		}
		switch( $return_format ) {
			case 'json':
				return json_decode( $response_body );
				break;
			case 'serialized':
				return maybe_unserialize( $response_body );
				break;
			default:
				return $response_body;
				break;
		} //end switch
		return false;
	} //end perform_remote_request
	
	public function plugin_links($val) {
		$ajax_url = esc_url( add_query_arg( array( 'slug' => $this->plugin_slug, 'action' => $this->plugin_slug . 'licenses', '_ajax_nonce' => wp_create_nonce( $this->plugin_slug . 'licenses' ), 'TB_iframe' => true ), admin_url( 'admin-ajax.php' ) ) );
		
		$val[sizeof($val)] = sprintf( "<a href='%s' class='thickbox' title='PluginBuddy Licensing'><img src='%s' style='vertical-align: -3px' /> Licenses</a>", $ajax_url, $this->plugin_url . '/pluginbuddy/lib/updater/key.png' );
		return $val;
	} //end plugin_ilnks
	
	public function plugin_print_scripts() {
		wp_enqueue_script( 'thickbox' );
	} //end plugin_print_scripts
	
	public function plugin_print_styles() {
		wp_enqueue_style( 'thickbox' );
	} //end plugiln_print_styles
	
	public function plugin_right_links( $links, $plugin_name ) {
		if ( $this->plugin_path != $plugin_name ) {
			return $links;
		}
		$links[] = '<a href="?pluginbuddy_refresh=true" title="Check for updates.">Check for updates now</a>';
		return $links;
	} //end plugin_right_links
	
	public function plugin_row( $plugin_name ){
		$return = '<span style="border-right: 1px solid #DFDFDF; margin-right: 5px;">';
		$return .= '<a class="thickbox" title="PluginBuddy Licensing" href="' . esc_url( add_query_arg( array( 'slug' => $this->plugin_slug, 'action' => $this->plugin_slug . 'licenses', '_ajax_nonce' => wp_create_nonce( $this->plugin_slug . 'licenses' ), 'TB_iframe' => true ), admin_url( 'admin-ajax.php' ) ) ) . '"><img src="' . esc_url( $this->plugin_url . '/pluginbuddy/lib/updater/key.png' ) .'" style="vertical-align: -3px;"> Manage Licenses</a>';
		$return .= '</span>';
		
		$current_plugin = $this->plugins[ $this->plugin_slug ];
		
		//Output an error for invalid license status
		$message = '';
		if ( $current_plugin->key_status == 'invalid' ) {
			$message .= 'License Key is not set yet or invalid.  Manage your license for automatic upgrades. ';
		} elseif ( $current_plugin->key_status == 'expired' ) {
			$message .= 'The License Key associated with this site has expired. ';
		} elseif ( $current_plugin->key_status == 'bad_site' ) {
			$message .= "The License Key is associated with a different site.  Please generate a new License Key. ";
		} elseif( $current_plugin->key_status == 'ok' ) { // No message so don't display an additional box.
			$message = '';
			return;
		}
		
		//If there's a newer version, let's let the user know about it
		if ( version_compare( $current_plugin->new_version, $this->version, '>' ) ) {
			$message .= "There is a new version of this plugin available. ";
		} else {
			$message .= "Plugin up to date. ";
		}
		
		$this->output( $return . $message );
	} //end plugin_row

	//Return an updated version to WordPress when it runs its update checker
	public function update_plugins_filter( $value ) {
		if ( isset( $this->plugins[ $this->plugin_slug ] ) && $this->plugin_path ) {
			$value->response[ $this->plugin_path ] = $this->plugins[ $this->plugin_slug ];
		}
		
		return $value;
	} //end update_plugins_filter

	public function view_changelog() {
		if ( !isset( $_GET[ 'plugin' ] ) ) return;
		if( $_GET["plugin"] != strtolower( $this->plugin_slug ) ) {
			return;
		}
		
		
		$response = $this->perform_remote_request( 
			array( 'body' => array(
				'action' => 'changelog'					
			)
		) );
		
		if ( !is_wp_error( $response ) ) { 
			echo $response->message;
			exit;
			return;
		} else {
			echo "Could not retrieve the changelog.  Please try again later.";
			exit;
		}
		
	} //end view_changelog
	
	public function view_licenses() {
		check_ajax_referer( $this->plugin_slug . 'licenses', '_ajax_nonce' );
		$plugin_dir = rtrim( plugin_dir_path(__FILE__), '/' );
		require_once( $plugin_dir .  '/licenses.php' );
		
		die();
		/*
		$response = $this->perform_remote_request( 
			array( 'body' => array(
				'action' => 'licenses'					
			)
		) );
		
		die( '<pre>' . print_r( $response, true ) );*/
	} //end view_licenses
	
	// bypass WP caching
	function update_site_option( $option, $value ) {	
		global $wpdb;		
		if ( !is_multisite() ) {
			$result = $this->update_option( $option, $value );
			if ( $result ) return true;
		} else {
			
			if ( $value && !$wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $wpdb->siteid ) ) ) {
				$value = sanitize_option( $option, $value );
				$value = maybe_serialize($value);
				$wpdb->insert( $wpdb->sitemeta, array('site_id' => $wpdb->siteid, 'meta_key' => $option, 'meta_value' => $value ) );
				return true;
			} else {
				$value = sanitize_option( $option, $value );
				$value = maybe_serialize( $value );
				$result = $wpdb->update( $wpdb->sitemeta, array( 'meta_value' => $value ), array( 'site_id' => $wpdb->siteid, 'meta_key' => $option ) );
				return true;
			}
		}
		return false;
	} //end update_site_option
	
	// bypass WP caching
	function update_option( $option, $newvalue ) {
		global $wpdb;

		$option = trim($option);
		if ( empty($option) )
			return false;				
		
		$oldvalue = get_option( $option );
		if ( false === $oldvalue ) {
			return add_option( $option, $newvalue );
		} else {
			$newvalue = sanitize_option( $option, $newvalue );
			$newvalue = maybe_serialize( $newvalue );				
			$result = $wpdb->update( $wpdb->options, array( 'option_value' => $newvalue ), array( 'option_name' => $option ) );
			
			if ( $result ) return true;
		}
		
		return false;
	} //end update_option
	
	
	
	// ManageWP
	function mwp_update_notification( $premium_updates ) {
		
		if( !function_exists( 'get_plugin_data' ) )
			include_once( ABSPATH.'wp-admin/includes/plugin.php');
		
		// EDIT HERE
		$myplugin = get_plugin_data( ABSPATH . 'wp-content/plugins/' . $this->plugin_path );   // or path to your main plugin file, we expect it to have standard header with plugin info				
		$myplugin['new_version'] = $this->plugins[ $this->plugin_slug ]->new_version;  // edit your plugin's new version (extract this from your own options, etc..)
		$myplugin['type'] = 'plugin';
		
		array_push( $premium_updates, $myplugin );
		return $premium_updates;
		
	} // End mwp_update_notification().
	
	
	
	// ManageWP
	function mwp_perform_update( $premium_updates ){
		
		if( !function_exists( 'get_plugin_data' ) )
			include_once( ABSPATH.'wp-admin/includes/plugin.php');
		
		// EDIT HERE
		$my_addon = get_plugin_data( ABSPATH . 'wp-content/plugins/' . $this->plugin_path );   // or path to your main plugin file, we expect it to have standard header with plugin info		
		
		$my_addon['url'] = $this->plugins[$this->plugin_slug]->package;
		//$my_addon['url'] = 'http://mysite.com/file.zip'; 	// Provide URL to the archive file with the new version of your plugin (easiest)
		//$my_addon['callback'] = 'my_update_callback'; 		// *OR* Provide your own callback function that will perform the update when called (we will simply call when the user select to upgrade)
		//$my_addon['slug'] = $this->plugin_slug; 	// *OR* Provide the plugin slug if you hook on to WordPress upgrade transients for your plugin upgrade
		$my_addon['type'] = 'plugin';
		array_push( $premium_updates, $my_addon );
		
		return $premium_updates;
	} // End myplugin_mwp_perform_update().
	
	
	
	// ManageWP
	function mwp_update_check( $update ){
		
		if( !function_exists( 'get_plugin_data' ) )
			include_once( ABSPATH.'wp-admin/includes/plugin.php');
			
		// EDIT HERE
		$my_addon = get_plugin_data( ABSPATH . 'wp-content/plugins/' . $this->plugin_path );   // or path to your main plugin file, we expect it to have standard header with plugin info	
		$my_addon['callback'] = array( &$this, 'force_check_for_updates' ); // Provide the callback function which will force an update check for your plugin
		
		array_push( $update, $my_addon );
		
		return $update;
	} // End myplugin_mwp_update_check().
	
	
	
} //end class
?>