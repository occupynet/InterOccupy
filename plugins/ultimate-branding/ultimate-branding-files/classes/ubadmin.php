<?php
if(!class_exists('UltimateBrandingAdmin')) {

	class UltimateBrandingAdmin {

		var $build = 1;
		var $modules = array(	'login-image.php' => 'login-image/login-image.php',
								'custom-admin-bar.php' => 'custom-admin-bar/custom-admin-bar.php',
								'remove-wp-dashboard-widgets.php' => 'remove-wp-dashboard-widgets/remove-wp-dashboard-widgets.php',
								'admin-help-content.php' => 'admin-help-content/admin-help-content.php',
								'global-footer-content.php' => 'global-footer-content/global-footer-content.php',
								'admin-footer-text.php' => 'admin-footer-text/admin-footer-text.php',
								'rebranded-meta-widget.php' => 'rebranded-meta-widget/rebranded-meta-widget.php',
								'remove-permalinks-menu-item.php' => 'remove-permalinks-menu-item/remove-permalinks-menu-item.php',
								'site-generator-replacement.php' => 'site-generator-replacement/site-generator-replacement.php',
								'site-wide-text-change.php' => 'site-wide-text-change/site-wide-text-change.php',
								'favicons.php' => 'favicons.php',
								'custom-admin-css.php' => 'custom-admin-css.php',
								'custom-login-css.php' => 'custom-login-css.php',
								'custom-dashboard-welcome.php' => 'custom-dashboard-welcome.php'
							);

		var $plugin_msg = array();

		// Holder for the help class
		var $help;

		function __construct() {

			add_action( 'plugins_loaded', array(&$this, 'deactivate_existing_plugins' ) );	// Check other plugins

			if ( !is_multisite() ) {
				if(UB_HIDE_ADMIN_MENU != true) {
					add_action( 'admin_menu', array( &$this, 'network_admin_page' ) );
				}
			} else {
				add_action( 'network_admin_menu', array( &$this, 'network_admin_page' ) );
			}

			// Header actions
			add_action('load-toplevel_page_branding', array(&$this, 'add_admin_header_branding'));

			add_action( 'plugins_loaded', array(&$this, 'setup_translation') );

		}

		function UltimateBrandingAdmin() {
			$this->__construct();
		}

		function setup_translation() {
	    	// Load up the localization file if we're using WordPress in a different language
	  		// Place it in this plugin's "languages" folder and name it "mp-[value in wp-config].mo"

		    load_plugin_textdomain( 'ub', false, '/ultimate-branding/ultimate-branding-files/languages/' );

	  }

		function add_admin_header_core() {

			// Add in help pages
			$screen = get_current_screen();

			$this->help = new UB_Help( $screen );
			$this->help->attach();

			// Add in the core CSS file
			wp_enqueue_style( 'defaultadmincss', ub_files_url('css/defaultadmin.css'), array(), $this->build);
		}

		function add_admin_header_branding() {

			$this->add_admin_header_core();

			do_action('ultimatebranding_admin_header_global');

			$tab = (isset($_GET['tab'])) ? $_GET['tab'] : '';
			if(empty($tab)) {
				$tab = 'dashboard';
			}

			do_action('ultimatebranding_admin_header_' . $tab);

			$this->update_branding_page();

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

		/**
		 *	Warn admin if this is not multisite
		 */
		function not_multisite_msg() {
			echo '<div class="error"><p>' .
				__('<b>[Ultimate Branding]</b> Plugin only works in Multisite.', 'ub') .
				'</p></div>';
		}

		/**
		 *	Warn admin to deactivate the duplicate plugins
		 */
		function deactivate_plugin_msg() {
			echo '<div class="error"><p>' .
				sprintf(__('<b>[Ultimate Branding]</b> Please deactivate the following plugin(s) to make Ultimate Branding to work: %s', 'ub'), implode(', ', $this->plugin_msg) ) .
				'</p></div>';
		}

		/**
		 * Add pages
		 */
		function network_admin_page( ) {

			// Add in our menu page
			add_menu_page( __('Branding','ub'), __('Branding','ub'), 'manage_options', 'branding', array(&$this,'handle_main_page') );

			// Get the activated modules
			$modules = get_ub_activated_modules();

			// Add in the extensions
			foreach($modules as $key => $title) {
				switch( $key ) {
					case 'favicons.php':
					case 'login-image.php':						if(!ub_has_menu('branding&amp;tab=images')) add_submenu_page('branding', __('Images','ub'), __('Images','ub'), 'manage_options', "branding&amp;tab=images", array(&$this,'handle_images_panel'));
																break;

					case 'custom-admin-bar.php':				if(!ub_has_menu('branding&amp;tab=adminbar')) add_submenu_page('branding', __('Admin Bar','ub'), __('Admin Bar','ub'), 'manage_options', "branding&amp;tab=adminbar", array(&$this,'handle_adminbar_panel'));
																break;

					case 'admin-help-content.php':				if(!ub_has_menu('branding&amp;tab=help')) add_submenu_page('branding', __('Help Content','ub'), __('Help Content','ub'), 'manage_options', "branding&amp;tab=help", array(&$this,'handle_help_panel'));
																break;

					case 'global-footer-content.php':
					case 'admin-footer-text.php':				if(!ub_has_menu('branding&amp;tab=footer')) add_submenu_page('branding', __('Footer Content','ub'), __('Footer Content','ub'), 'manage_options', "branding&amp;tab=footer", array(&$this,'handle_footer_panel'));
																break;

					case 'custom-dashboard-welcome.php':
					case 'remove-wp-dashboard-widgets.php':
					case 'rebranded-meta-widget.php':			if(!ub_has_menu('branding&amp;tab=widgets')) add_submenu_page('branding', __('Widgets','ub'), __('Widgets','ub'), 'manage_options', "branding&amp;tab=widgets", array(&$this,'handle_widgets_panel'));
																break;

					case 'remove-permalinks-menu-item.php':		if(!ub_has_menu('branding&amp;tab=permalinks')) add_submenu_page('branding', __('Permalinks Menu','ub'), __('Permalinks Menu','ub'), 'manage_options', "branding&amp;tab=permalinks", array(&$this,'handle_permalinks_panel'));
																break;

					case 'site-generator-replacement.php':		if(!ub_has_menu('branding&amp;tab=sitegenerator')) add_submenu_page('branding', __('Site Generator','ub'), __('Site Generator','ub'), 'manage_options', "branding&amp;tab=sitegenerator", array(&$this,'handle_sitegenerator_panel'));
																break;

					case 'site-wide-text-change.php':			if(!ub_has_menu('branding&amp;tab=textchange')) add_submenu_page('branding', __('Text Change','ub'), __('Text Change','ub'), 'manage_options', "branding&amp;tab=textchange", array(&$this,'handle_textchange_panel'));
																break;

					case 'custom-login-css.php':
					case 'custom-admin-css.php':				if(!ub_has_menu('branding&amp;tab=css')) add_submenu_page('branding', __('CSS','ub'), __('CSS','ub'), 'manage_options', "branding&amp;tab=css", array(&$this,'handle_css_panel'));
																break;

				}
			}

			do_action( 'ultimate_branding_add_menu_pages' );

		}

		function activate_module( $module ) {

			$modules = get_ub_activated_modules();

			if(!isset($modules[$module])) {
				$modules[$module] = 'yes';
				update_ub_activated_modules( $modules );
			} else {
				return false;
			}

		}

		function deactivate_module( $module ) {

			$modules = get_ub_activated_modules();

			if(isset($modules[$module])) {
				unset($modules[$module]);
				update_ub_activated_modules( $modules );
			} else {
				return false;
			}

		}

		function update_branding_page() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			if(isset($_REQUEST['action'])) {
				$tab = (isset($_GET['tab'])) ? $_GET['tab'] : '';
				if(empty($tab)) {
					$tab = 'dashboard';
				}

				switch($tab) {

					case 'dashboard':		if(isset($_GET['action']) && isset($_GET['module'])) {
												switch($_GET['action']) {
													case 'enable':		check_admin_referer('enable-module-' . $_GET['module']);
																		if($this->activate_module( $_GET['module'])) {
																			wp_safe_redirect( remove_query_arg( array('module', '_wpnonce', 'action'), wp_get_referer() ) );
																		} else {
																			wp_safe_redirect( remove_query_arg( array('module', '_wpnonce', 'action'), wp_get_referer() ) );
																		}
																		break;
													case 'disable':		check_admin_referer('disable-module-' . $_GET['module']);
																		if($this->deactivate_module( $_GET['module'])) {
																			wp_safe_redirect( remove_query_arg( array('module', '_wpnonce', 'action'), wp_get_referer() ) );
																		} else {
																			wp_safe_redirect( remove_query_arg( array('module', '_wpnonce', 'action'), wp_get_referer() ) );
																		}
																		break;
												}
											}
											break;

					case 'images':			check_admin_referer('ultimatebranding_settings_menu_images');
											if( apply_filters( 'ultimatebranding_settings_menu_images_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'adminbar':		check_admin_referer('ultimatebranding_settings_menu_adminbar');
											if( apply_filters( 'ultimatebranding_settings_menu_adminbar_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'help':			check_admin_referer('ultimatebranding_settings_menu_help');
											if( apply_filters( 'ultimatebranding_settings_menu_help_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'footer':			check_admin_referer('ultimatebranding_settings_menu_footer');
											if( apply_filters( 'ultimatebranding_settings_menu_footer_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'widgets':			check_admin_referer('ultimatebranding_settings_menu_widgets');
											if( apply_filters( 'ultimatebranding_settings_menu_widgets_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'permalinks':		check_admin_referer('ultimatebranding_settings_menu_permalinks');
											if( apply_filters( 'ultimatebranding_settings_menu_permalinks_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'sitegenerator':	check_admin_referer('ultimatebranding_settings_menu_sitegenerator');
											if( apply_filters( 'ultimatebranding_settings_menu_sitegenerator_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'textchange':		check_admin_referer('ultimatebranding_settings_menu_textchange');
											if( apply_filters( 'ultimatebranding_settings_menu_textchange_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					case 'css':				check_admin_referer('ultimatebranding_settings_menu_css');
											if( apply_filters( 'ultimatebranding_settings_menu_css_process', true ) ) {
												wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
											} else {
												wp_safe_redirect( add_query_arg( 'msg', 2, wp_get_referer() ) );
											}
											break;

					default:				do_action('ultimatebranding_settings_update_' . $tab);
											break;


				}
			}

		}

		function handle_main_page() {

			global $action, $page;

			wp_reset_vars( array('action', 'page') );

			$tab = (isset($_GET['tab'])) ? $_GET['tab'] : '';
			if(empty($tab)) {
				$tab = 'dashboard';
			}

			// Get the activated modules
			$modules = get_ub_activated_modules();

			?>
			<div class='wrap nosubsub'>
				<?php
					$menus = array();
					$menus['dashboard'] = __('Dashboard', 'ub');

					foreach($modules as $key => $title) {
						switch( $key ) {
							case 'favicons.php':
							case 'login-image.php':						$menus['images'] = __('Images','ub');
																		break;
							case 'custom-admin-bar.php':				$menus['adminbar'] = __('Admin Bar','ub');
																		break;
							case 'admin-help-content.php':				$menus['help'] = __('Help Content','ub');
																		break;
							case 'global-footer-content.php':
							case 'admin-footer-text.php':				$menus['footer'] = __('Footer Content','ub');
																		break;

							case 'custom-dashboard-welcome.php':
							case 'remove-wp-dashboard-widgets.php':
							case 'rebranded-meta-widget.php':			$menus['widgets'] = __('Widgets','ub');
																		break;
							case 'remove-permalinks-menu-item.php':		$menus['permalinks'] = __('Permalinks Menu','ub');
																		break;
							case 'site-generator-replacement.php':		$menus['sitegenerator'] = __('Site Generator','ub');
																		break;
							case 'site-wide-text-change.php':			$menus['textchange'] = __('Text Change','ub');
																		break;
							case 'custom-login-css.php':
							case 'custom-admin-css.php':				$menus['css'] = __('CSS','ub');
																		break;

						}
					}

					$menus = apply_filters('ultimatebranding_settings_menus', $menus);
				?>

				<h3 class="nav-tab-wrapper">
					<?php
						foreach($menus as $key => $menu) {
							?>
							<a class="nav-tab<?php if($tab == $key) echo ' nav-tab-active'; ?>" href="admin.php?page=<?php echo $page; ?>&amp;tab=<?php echo $key; ?>"><?php echo $menu; ?></a>
							<?php
						}

					?>
				</h3>

				<?php

				switch($tab) {

					case 'dashboard':		$this->show_dashboard_page();
											break;

					case 'images':			$this->handle_images_panel();
											break;

					case 'adminbar':		$this->handle_adminbar_panel();
											break;

					case 'help':			$this->handle_help_panel();
											break;

					case 'footer':			$this->handle_footer_panel();
											break;

					case 'widgets':			$this->handle_widgets_panel();
											break;

					case 'permalinks':		$this->handle_permalinks_panel();
											break;

					case 'sitegenerator':	$this->handle_sitegenerator_panel();
											break;

					case 'textchange':		$this->handle_textchange_panel();
											break;

					case 'css':				$this->handle_css_panel();
											break;

					default:				do_action('ultimatebranding_settings_menu_' . $tab);
											break;


				}

				?>

			</div> <!-- wrap -->
			<?php

		}

		function show_dashboard_page() {

			global $action, $page;

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Branding','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>

					<div id="dashboard-widgets-wrap">

					<div class="metabox-holder" id="dashboard-widgets">
						<div style="width: 49%;" class="postbox-container">
							<div class="meta-box-sortables ui-sortable" id="normal-sortables">

								<div class="postbox " id="">
									<h3 class="hndle"><span><?php _e('Branding','ub'); ?></span></h3>
									<div class="inside">
										<?php
										include_once( ub_files_dir('help/dashboard.help.php')  );
										?>
										<br class="clear">
									</div>
								</div>

								<?php
								do_action( 'ultimatebranding_dashboard_page_left' );
								?>
							</div>
						</div>

						<div style="width: 49%;" class="postbox-container">
							<div class="meta-box-sortables ui-sortable" id="side-sortables">

								<?php
								do_action( 'ultimatebranding_dashboard_page_right_top' );
								?>

								<div class="postbox " id="dashboard_quick_press">
									<h3 class="hndle"><span><?php _e('Module Status','ub'); ?></span></h3>
									<div class="inside">
										<?php $this->show_module_status(); ?>
										<br class="clear">
									</div>
								</div>

								<?php
								do_action( 'ultimatebranding_dashboard_page_right' );
								?>

							</div>
						</div>

						<div style="display: none; width: 49%;" class="postbox-container">
							<div class="meta-box-sortables ui-sortable" id="column3-sortables" style="">
							</div>
						</div>

						<div style="display: none; width: 49%;" class="postbox-container">
							<div class="meta-box-sortables ui-sortable" id="column4-sortables" style="">
							</div>
						</div>
					</div>

					<div class="clear"></div>
					</div>

			<?php
		}

		function show_module_status() {

			global $action, $page;

			?>
			<table class='widefat'>
				<thead>
					<th><?php _e('Available Modules', 'ub'); ?></th>
					<th></th>
				</thead>
				<tfoot>
					<th><?php _e('Available Modules', 'ub'); ?></th>
					<th></th>
				</tfoot>
				<tbody>
				<?php
					if(!empty($this->modules)) {

						$default_headers = array(
							                'Name' => 'Plugin Name',
											'Author' => 'Author',
											'Description'	=>	'Description',
											'AuthorURI' => 'Author URI'
							        );

						foreach($this->modules as $module => $plugin) {

							$module_data = get_file_data( ub_files_dir('modules/' . $module), $default_headers, 'plugin' );

							if(ub_is_active_module( $module )) {
								?>
									<tr class='activemodule'>
										<td>
										<?php
											echo $module_data['Name'];
										?>
										</td>
										<td>
											<a href='<?php echo wp_nonce_url("?page=" . $page. "&amp;action=disable&amp;module=" . $module . "", 'disable-module-' . $module) ?>' class='disblelink'><?php _e('Disable', 'ub'); ?></a>
										</td>
									</tr>
								<?php
							} else {
								?>
									<tr class='inactivemodule'>
										<td>
										<?php
											echo $module_data['Name'];
										?>
										</td>
										<td>
											<a href='<?php echo wp_nonce_url("?page=" . $page. "&amp;action=enable&amp;module=" . $module . "", 'enable-module-' . $module) ?>' class='enablelink'><?php _e('Enable', 'ub'); ?></a>
										</td>
									</tr>
								<?php
							}

						}
					} else {
						?>
						<tr>
							<td colspan='2'><?php _e('No modules avaiable.', 'ub'); ?></td>
						</tr>
						<?php
					}
				?>
				</tbody>
			</table>

			<?php

		}

		function handle_images_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'There was an error uploading the file, please try again.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_images_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Custom Images','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_images');

						do_action('ultimatebranding_settings_menu_images');
					?>

						<?php
						if(has_filter('ultimatebranding_settings_menu_images_process')) {
						?>
							<p class="submit">
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
							</p>
						<?php
						}
						?>

				</form>
				</div>
			<?php

		}

		function handle_adminbar_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'There was an error uploading the file, please try again.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_adminbar_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Custom Admin Bar','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_adminbar');

						do_action('ultimatebranding_settings_menu_adminbar');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_adminbar_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				</div>
			<?php

		}

		function handle_help_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'Changes could not be saved.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_help_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Custom Help Content','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_help');

						do_action('ultimatebranding_settings_menu_help');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_help_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				</div>
			<?php

		}

		function handle_footer_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'Changes could not be saved.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_footer_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Custom Footer Content','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_footer');

						do_action('ultimatebranding_settings_menu_footer');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_footer_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				</div>
			<?php

		}

		function handle_widgets_panel() {

				global $action, $page;

				$messages = array();
				$messages[1] = __( 'Changes saved.', 'ub' );
				$messages[2] = __( 'There was an error uploading the file, please try again.', 'ub' );

				$messages = apply_filters( 'ultimatebranding_settings_menu_widgets_messages', $messages );

				?>
					<div class="icon32" id="icon-index"><br></div>
					<h2><?php _e('Custom Widgets','ub'); ?></h2>

					<?php
					if ( isset($_GET['msg']) ) {
						echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
						$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
					}
					?>
					<div id="poststuff" class="metabox-holder m-settings">
					<form action='' method="post" enctype="multipart/form-data">

						<input type='hidden' name='page' value='<?php echo $page; ?>' />
						<input type='hidden' name='action' value='process' />
						<?php
							wp_nonce_field('ultimatebranding_settings_menu_widgets');

							do_action('ultimatebranding_settings_menu_widgets');
						?>

						<?php
						if(has_filter('ultimatebranding_settings_menu_widgets_process')) {
						?>
							<p class="submit">
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
							</p>
						<?php
						}
						?>

					</form>
					</div>
				<?php

		}

		function handle_permalinks_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'There was an error uploading the file, please try again.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_permalinks_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Remove Permalinks Menu','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_permalinks');

						do_action('ultimatebranding_settings_menu_permalinks');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_permalinks_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				</div>
			<?php

		}

		function handle_sitegenerator_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'Changes could not be saved.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_sitegenerator_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Custom Site Generator Content','ub'); ?></h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_sitegenerator');

						do_action('ultimatebranding_settings_menu_sitegenerator');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_sitegenerator_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				</div>
			<?php

		}

		function handle_textchange_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'There was an error, please try again.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_textchange_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Network Wide Text Change','ub'); ?>
				<a class="add-new-h2" href="#addnew" id='addnewtextchange'><?php _e('Add New','ub'); ?></a>
				</h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_textchange');

						do_action('ultimatebranding_settings_menu_textchange');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_textchange_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				<div style='clear:both;'></div>
				</div>
			<?php

		}

		function handle_css_panel() {

			global $action, $page;

			$messages = array();
			$messages[1] = __( 'Changes saved.', 'ub' );
			$messages[2] = __( 'There was an error, please try again.', 'ub' );

			$messages = apply_filters( 'ultimatebranding_settings_menu_css_messages', $messages );

			?>
				<div class="icon32" id="icon-index"><br></div>
				<h2><?php _e('Custom CSS','ub'); ?>
				</h2>

				<?php
				if ( isset($_GET['msg']) ) {
					echo '<div id="message" class="updated fade"><p>' . $messages[(int) $_GET['msg']] . '</p></div>';
					$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
				}
				?>
				<div id="poststuff" class="metabox-holder m-settings">
				<form action='' method="post" enctype="multipart/form-data">

					<input type='hidden' name='page' value='<?php echo $page; ?>' />
					<input type='hidden' name='action' value='process' />
					<?php
						wp_nonce_field('ultimatebranding_settings_menu_css');

						do_action('ultimatebranding_settings_menu_css');
					?>

					<?php
					if(has_filter('ultimatebranding_settings_menu_css_process')) {
					?>
						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes','ub'); ?>" />
						</p>
					<?php
					}
					?>

				</form>
				</div>
			<?php

		}

	}

}

?>