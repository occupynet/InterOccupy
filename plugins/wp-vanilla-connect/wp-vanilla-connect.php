<?php
/*
Plugin Name: WP Vanilla Connect
Description: Authenticates a user in Vanilla Forums if they are already logged into WordPress for a seamless user experience.
Author: Nicholas P. Iler
Version: 1.1.1
Author URI: http://www.ilertech.com
Plugin URI: http://www.ilertech.com/plugins/wp-vanilla-connect
*/

class WP_Vanilla_Connect{
	
	private $options = array();
	
	// Get your client ID and secret here. These must match those in your jsConnect settings.
	private $clientID;
	private $secret;
	
	// Grab the current user from your session management system or database here.
	private $signedIn = false; // this is just a placeholder
	private $secure = true;
	
	private $vanilla_host;
	private $current_user_gravatar_url;
	
	function __construct(){
		
		// Setup the variables
		$options = get_option('wp_vanilla_connect_option');

		// Set WP options in the object for later
		$this->secret = $options['secret'];
		$this->clientID = $options['clientid'];
	}
	
	function admin_menu() {
		add_submenu_page('users.php', 'WP Vanilla Connect', 'WP Vanilla Connect', 'administrator', 'wp-vanilla-connect', array($this, 'admin_panel_general'));
	}
	
	function register_options(){
		register_setting('wp_vanilla_connect_group', 'wp_vanilla_connect_option');
	}
	
	/**
	* Options Page
	*/
	function admin_panel_general(){
		global $current_user;
		get_currentuserinfo(); // This is required for the plugin page
		require_once ( dirname(__FILE__) . '/wp-vanilla-connect-options.php');
	}
	
	function add_backend(){
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_init', array($this, 'register_options' ));
		
		// We only want these scripts/styles to load on our plugin page
		if( isset($_GET['page']) && $_GET['page'] == 'wp-vanilla-connect'){
		
			// Add the JS
			add_action('admin_print_scripts', array($this, 'add_scripts'), 1);
			
			// Add the CSS
			add_action('admin_print_styles', array($this, 'add_styles'), 1);
			
			// Add other stuff
			add_action('admin_head', array($this, 'add_head')); 
		}
	}
	
	function add_styles(){
		wp_enqueue_style('wpvc-style', plugins_url('/style.css', __FILE__));
	}
	
	function add_scripts(){
		wp_register_script('wpvc-scripts', plugins_url('/action.js', __FILE__), array('jquery'), '1.0');
		wp_enqueue_script('wpvc-scripts');
	}
	
	function add_head(){
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />';
	}
	
	/**
	 * Run
	 * The main program for generating the request. Only runs when proper query client_id is sent.
	 *
	 */
	function run(){
		global $current_user; $user = array();
		$options = get_option('wp_vanilla_connect_option');
		
		// If someone is logged on create the secret jsconnect string
		if($current_user){
			
			// Map the current user values for jsConnect
			$user = array(
				'uniqueid' 	=> $current_user->ID,
				'name'		=> $current_user->display_name,
				'email' 	=> $current_user->user_email
			);
			
			$gravatar_url = $this->get_gravatar_url($user['email'], $options);
			
			$user['photourl'] = $gravatar_url;
		}
		
		// Set to true if not in test mode
		if(!isset($options['test_mode'])) $this->secure = true;
		else $this->secure = false;
		
		WriteJsConnect($user, $_GET, $this->clientID, $this->secret, $this->secure);
	}
	
	function get_gravatar_url($email, &$options = null){
		// Are we going to serve Gravatar's with SSL
		if(isset($options['gravatar_ssl'])){
			$url = 'https://secure.gravatar.com/avatar/';
		}else{
			$url = 'http://gravatar.com/avatar/';
		}
		
		// Hash the email address and add it to array
		$hashed_email = md5( strtolower( trim( $email ) ) );
		$complete_url = $url . $hashed_email;
		$this->current_user_gravatar_url = $complete_url;
		return $complete_url;
	}
	
	function get_current_user_gravatar(){
		return $this->current_user_gravatar_url;
	}
	
	function getURL(){
		// Get the complete url
		$url = site_url() . WPVC_PLUGIN_PATH . plugin_basename(__FILE__);
		return $url;
	}
	
	function getRedirectURL(){
		$this->set_option();
		
		if(!empty($this->options['vanilla_url'])){
			$vanilla_host = $this->options['vanilla_url'];
			$vanilla_login_url 			= $vanilla_host . '/entry/jsconnect?client_id=' . $this->get_clientID() . '&Target=%2F';
			$this->vanilla_host	= $this->options['vanilla_url']; // set this for later
			
			// Get the complete url
			$wp_login_url	= site_url() . '/wp-login.php?redirect_to=';
			$url = $wp_login_url . $vanilla_login_url;
			return $url;
		}else{ 
			return 'Add the URL that points to the root of your Vanilla forums to generate the jsConnect URL.';
		}
	}

	function getRegisterURL(){
		$wp_register_url 			= site_url() . '/wp-signup.php?action=register';
		$url = $wp_register_url;
		return $url;
	}
	
	function getVanillaAutoLoginURL(){
		if(isset($this->options['embedded'])){
			$this->vanilla_host .= '/index.php?p=';
		}
		return $this->vanilla_host . '/entry/jsconnect?client_id=' . $this->get_clientID();
	}
	
	function get_vanilla_host(){
		return $this->vanilla_host;
	}
	
	function set_option(){
		$this->options = get_option('wp_vanilla_connect_option');
	}
	
	private function generate_salt(){
		// lets create th clientID and secret salts
		$this->clientID = substr(uniqid('', true), 15, 9);
		$this->secret = substr($this->guid(), 1, 36);
	}
	
	private function guid(){
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12)
			.chr(125);// "}"
			return $uuid;
		}
	}
	
	public function get_clientID(){
		return $this->clientID;
	}
	
	public function get_secret(){
		return $this->secret;
	}
	
	public function activate(){
		
		$wpvc = new WP_Vanilla_Connect();
		
		$wpvc->generate_salt();	
		
		if (is_multisite()) {
			global $wpdb;
			
			$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
			
			if ($blogs) {
				foreach($blogs as $blog) {
					switch_to_blog($blog['blog_id']);
		
					$wpvc->set_option();
						if(empty($wpvc->options['clientid'])){
							$wpvc->options['clientid'] = $wpvc->get_clientID();
							update_option('wp_vanilla_connect_option', $wpvc->options);
						}
						
						if(empty($wpvc->options['secret'])){
							$wpvc->options['secret'] = $wpvc->get_secret();
							update_option('wp_vanilla_connect_option', $wpvc->options);
						}
				}
				restore_current_blog();
			}
			
		}else{
			
			$wpvc->set_option();
			if(empty($wpvc->options['clientid'])){
				$wpvc->options['clientid'] = $wpvc->get_clientID();
				update_option('wp_vanilla_connect_option', $wpvc->options);
			}
			
			if(empty($wpvc->options['secret'])){
				$wpvc->options['secret'] = $wpvc->get_secret();
				update_option('wp_vanilla_connect_option', $wpvc->options);
			}
		}
	}

	public function regenerate_hashes(){
		$this->generate_salt();
		
		$this->set_option();
		$this->options['clientid'] = $this->get_clientID();		
		update_option('wp_vanilla_connect_option', $this->options);
		
		$this->options['secret'] = $this->get_secret();
		update_option('wp_vanilla_connect_option', $this->options);
	}
	
	public function vanilla_logout(){
		$this->set_option();
		
		// Get the custom settings if there are any otherwise use the defaults we know should work for everyone else
		$name = (!isset($this->options['vcname']) ? 'Vanilla' : $this->options['vcname']);
		$path = (!isset($this->options['vcpath']) ? '/' : $this->options['vcpath']);
		$domain = $this->options['vcdomain'];
		
		setcookie($name, "", time() - 3600, $path, $domain );
	}
}

// If this is not being run from WordPress include the needed files
if(!defined('ABSPATH')){
	require_once dirname(__FILE__) . '/functions.jsconnect.php';
	require_once( '../../../wp-load.php' );
	require_once( '../../../wp-admin/includes/plugin.php' );

}else{ // Include these for all other pages
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

// We only want this to run if the plugin is active
if (is_plugin_active('wp-vanilla-connect/wp-vanilla-connect.php')) {

	define('WPVC_PLUGIN_PATH', '/wp-content/plugins/');
	
	// if this is an admin page add the menu
	if(is_admin()){
	
		// Create the jsconnect object
		$wpvc = new WP_Vanilla_Connect();
		$wpvc->add_backend();
		
		if(isset($_POST['regen'])){
			// If this is a regeneration request, do it
			$wpvc->regenerate_hashes();
			
			function wpvc_admin_notice(){
				echo 	'<div class="updated">
			       		<p>You just regenerated the hashes. Now you need to paste them into your Vanilla. </p>
						</div>';
			}
			add_action('admin_notices', 'wpvc_admin_notice');
		}
		
	// Now we want to make sure this is the plugin page otherwise we just add the menu backend
	}elseif(basename($_SERVER['PHP_SELF']) == 'wp-vanilla-connect.php'){
	
		// Create the jsconnect object
		$wpvc = new WP_Vanilla_Connect();
	
		// Make sure the correct client_id is passed otherwise do nothing or // uncomment // header('Location:...');
		if( isset($_GET['client_id']) ){
			if($_GET['client_id'] == $wpvc->get_clientID() ){
				// The client_id is present and matches our saved value so "let's do this"
				$wpvc->run();
			}else{
				// You can put some logging code here if you think someone is trying to access this URL with bad client_id's
				// Actionable code can go here, otherwise a blank page will be shown
				// header('Location: ' . site_url() ); // uncomment this is you want to redirect to front page
			}
		}else{
			// do something if this file is called directly
			// header('Location: ' . site_url() ); // uncomment this is you want to redirect to front page
		}
	}
	
	// If this is a logout add the action to remove vanilla cookie
	if(basename($_SERVER['PHP_SELF']) == 'wp-login.php' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout'){
		$wpvc = new WP_Vanilla_Connect();
		add_action('wp_logout', array($wpvc, 'vanilla_logout'));
	}
	
}elseif(basename($_SERVER['PHP_SELF']) == 'plugins.php'){ // only runs from the plugin.php page
	// Activation hooks sets up the initial client_id and secret
	register_activation_hook( __FILE__, array('WP_Vanilla_Connect', 'activate') );
}

