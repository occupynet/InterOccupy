<?php
/*
Plugin Name: Add New Users
Plugin URI: http://premium.wpmudev.org/project/add-new-users
Description: Allows you to bulk create new users on a site and add them to a blog, including the facility to set their role and password on the new site.
Author: Andrew Billits, Ulrich Sossou
Version: 1.0.7
Text Domain: add_new_users
Author URI: http://premium.wpmudev.org
WDP ID: 114
*/

/**
 * Main plugin class
 *
 **/
class Add_New_Users {

	/**
	 * Current version number
	 *
	 **/
	var $current_version = '1.0.7';

	/**
	 * For supporters only
	 *
	 **/
	var $supporter_only = 'no'; // Either 'yes' OR 'no'

	/**
	 * Number of field sets to display
	 *
	 **/
	var $fields = '';

	/**
	 * PHP4 Constructor
	 *
	 **/
	function Add_New_Users() {
		__construct();
	}

	/**
	 * PHP5 Constructor
	 *
	 **/
	function __construct() {

		// get number of field sets
		$this->fields = isset( $_GET['fields'] ) ? $_GET['fields'] : '';

		// default to 15 field sets
		if ( $this->fields == '' )
			$this->fields = 15;

		// no more than 50 fields sets
		if ( $this->fields > 50 )
			$this->fields = 50;

		// activate or upgrade
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'add-new-users' )
			$this->make_current();

		// add admin menu page
		add_action( 'admin_menu', array( &$this, 'plug_pages' ) );

		// load text domain
		if ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/add-new-users.php' ) ) {
			load_muplugin_textdomain( 'add_new_users', 'add-new-users-files/languages' );
		} else {
			load_plugin_textdomain( 'add_new_users', false, dirname( plugin_basename( __FILE__ ) ) . '/add-new-users-files/languages' );
		}
	}

	/**
	 * Update database
	 *
	 **/
	function make_current() {
		// create global database table
		$this->global_install();

		if ( get_site_option( 'add_new_users_version' ) == '' )
			add_site_option( 'add_new_users_version', $this->current_version );

		if ( get_site_option( 'add_new_users_version' ) !== $this->current_version )
			update_site_option( 'add_new_users_version', $this->current_version );

		if ( get_option( 'add_new_users_version' ) == '' )
			add_option( 'add_new_users_version', $this->current_version );

		if ( get_option( 'add_new_users_version' ) !== $this->current_version )
			update_option( 'add_new_users_version', $this->current_version );

	}

	/**
	 * Create global database if it doesn't exist
	 *
	 **/
	function global_install() {
		global $wpdb;

		if( @is_file( ABSPATH . '/wp-admin/includes/upgrade.php' ) )
			include_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		else
			die( __( 'We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'', 'add_new_users' ) );

		// choose correct table charset and collation
		$charset_collate = '';
		if( $wpdb->supports_collation() ) {
			if( !empty( $wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if( !empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}
		}

		$table = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}add_new_users_queue` (
			`add_new_users_ID` bigint(20) unsigned NOT NULL auto_increment,
			`add_new_users_site_ID` bigint(20),
			`add_new_users_blog_ID` bigint(20),
			`add_new_users_batch_ID` varchar(255),
			`add_new_users_user_login` varchar(255),
			`add_new_users_user_email` varchar(255),
			`add_new_users_user_password` varchar(255),
			`add_new_users_user_role` varchar(255),
			PRIMARY KEY  (`add_new_users_ID`)
		) $charset_collate;";

		maybe_create_table( "{$wpdb->base_prefix}add_new_users_queue", $table );
	}

	/**
	 * Add admin menu
	 *
	 **/
	function plug_pages() {
		//add_submenu_page( 'users.php', 'Add New Users', 'Add New Users', 'edit_users', 'add-new-users', array( &$this, 'page_output' ) );
		add_submenu_page( 'users.php', 'Add New Users', 'Add New Users', 'add_users', 'add-new-users', array( &$this, 'page_output' ) );
	}

	/**
	 * Add one row of data to the queue
	 *
	 **/
	function queue_insert( $batch_ID, $user_login, $user_email, $user_password, $user_role ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}add_new_users_queue ( add_new_users_site_ID, add_new_users_blog_ID, add_new_users_batch_ID, add_new_users_user_email, add_new_users_user_role, add_new_users_user_login, add_new_users_user_password ) VALUES ( %d, %d, %d, %s, %s, %s, %s )", $wpdb->siteid, $wpdb->blogid, $batch_ID, $user_email, $user_role, $user_login, $user_password ) );
	}

	/**
	 * Process queue for one blog
	 *
	 **/
	function queue_process( $blog_ID, $site_ID ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}add_new_users_queue WHERE add_new_users_site_ID = '%d' AND add_new_users_blog_ID = '%d' LIMIT 1", $site_ID, $blog_ID );

		$users = $wpdb->get_results( $query, ARRAY_A );

		if( count( $users ) > 0 ) {
			foreach ( $users as $user ) {
				$user['add_new_users_user_password'] = ( 'empty' !== $user['add_new_users_user_password'] ) ? $user['add_new_users_user_password'] : wp_generate_password();

				if ( is_multisite() )
					$user_id = wpmu_create_user( $user['add_new_users_user_login'], $user['add_new_users_user_password'], $user['add_new_users_user_email'] );
				else
					$user_id = wp_create_user( $user['add_new_users_user_login'], $user['add_new_users_user_password'], $user['add_new_users_user_email'] );

				if ( $user_id && ! is_wp_error( $user_id ) ) {
					if ( is_multisite() ) {
						add_user_to_blog( $wpdb->blogid, $user_id, $user['add_new_users_user_role'] );
						wpmu_welcome_user_notification( $user_id, $user['add_new_users_user_password'], '' );
					} else {
						if ( isset( $user['add_new_users_user_role'] ) ) {
							$user_object = new WP_User( $user_id );
							$user_object->set_role( $user['add_new_users_user_role'] );
						}
						wp_new_user_notification( $user_id, $user['add_new_users_user_password'] );
					}

					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}add_new_users_queue WHERE add_new_users_blog_ID = '%d' AND add_new_users_site_ID = '%d' AND add_new_users_ID = '%d'", $wpdb->blogid, $wpdb->siteid, $user['add_new_users_ID'] ) );
				}
			}
		}
	}

	/**
	 * Check if current blog is a supporter blog
	 *
	 **/
	function is_supporter() {
		if ( function_exists( 'is_supporter' ) )
			return is_supporter();

		return false;
	}

	/**
	 * Validate user login and email
	 *
	 **/
	function validate_user_signup( $user_login, $user_email ) {
		if ( is_multisite() ) {
			return wpmu_validate_user_signup( $user_login, $user_email );
		} else {
			$errors = new WP_Error();

			$sanitized_user_login = sanitize_user( $user_login );
			$user_email = apply_filters( 'user_registration_email', $user_email );

			// Check the username
			if ( $sanitized_user_login == '' ) {
				$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.' ) );
			} elseif ( ! validate_username( $user_login ) ) {
				$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
				$sanitized_user_login = '';
			} elseif ( username_exists( $sanitized_user_login ) ) {
				$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.' ) );
			}

			// Check the e-mail address
			if ( $user_email == '' ) {
				$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.' ) );
			} elseif ( ! is_email( $user_email ) ) {
				$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.' ) );
				$user_email = '';
			} elseif ( email_exists( $user_email ) ) {
				$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.' ) );
			}

			do_action( 'register_post', $sanitized_user_login, $user_email, $errors );

			$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email );

			return array( 'user_name' => $sanitized_user_login, 'orig_username' => $user_login, 'user_email' => $user_email, 'errors' => $errors );
		}
	}

	/**
	 * Display plugin admin page
	 *
	 **/
	function page_output() {
		global $wpdb;

		// display error message if supporter only
		if ( !$this->is_supporter() && 'yes' == $this->supporter_only ) {
			supporter_feature_notice();
			return;
		}

		// display message when successful
		if( isset( $_GET['updated'] ) )
			echo '<div id="message" class="updated fade"><p>' . urldecode( $_GET['updatedmsg'] ) . '</p></div>';

		echo '<div class="wrap">';

		$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';
		switch( $action ) {

			case 'process_queue': // process queue from database
				check_admin_referer( 'add-new-users-process_queue_new_users' );

				echo '<p>' . __( 'Adding Users...', 'add_new_users' ) . '</p>';
				$this->queue_process( $wpdb->blogid, $wpdb->siteid );
				$queue_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->base_prefix}add_new_users_queue WHERE add_new_users_site_ID = '%d' AND add_new_users_blog_ID = '%d'", $wpdb->siteid, $wpdb->blogid ) );

				if ( $queue_count > 0 )
					echo '<script language=\'javascript\'>window.location=\'' . htmlspecialchars_decode( wp_nonce_url( 'users.php?page=add-new-users&action=process_queue', 'add-new-users-process_queue_new_users' ) ) . '\';</script>';
				else
					echo '<script language=\'javascript\'>window.location=\'users.php?page=add-new-users&updated=true&updatedmsg=' . urlencode( __( 'Users Added.', 'add_new_users' ) ) . '\';</script>';
			break;

			case 'process': // add entries to queue
				if ( isset( $_POST['Cancel'] ) )
					echo "<script language='javascript'>window.location='users.php?page=add-new-users';</script>";

				$batch_ID = md5( $wpdb->blogid . time() . '0420i203zm' );
				$errors = '';
				$error_fields = '';
				$error_messages = '';
				$global_errors = 0;
				$add_new_users_items = '';

				// validate users names, emails and passwords
				for ( $counter = 1; $counter <= $this->fields; $counter += 1 ) {
					$user_login = stripslashes( $_POST['user_login_' . $counter] );
					$user_email = stripslashes( $_POST['user_email_' . $counter] );
					$user_password = stripslashes( $_POST['user_password_' . $counter] );
					$user_role = stripslashes( $_POST['user_role_' . $counter] );
					$error = 0;
					$error_field = '';
					$error_msg = '';

					if ( !empty( $user_email ) || !empty( $user_login ) ) {

						// validate user email and login
						$validate_user = $this->validate_user_signup( $user_login, $user_email );
						if( is_wp_error( $validate_user_errors = $validate_user[ 'errors' ] ) && !empty( $validate_user[ 'errors' ]->errors ) ) {
							foreach( $validate_user_errors->get_error_codes() as $error_code ) {
								$error_field = $error_code;
								$error_msg = $validate_user_errors->errors[$error_code][0];
								$error = 1;
							}
						}

						// validate password
						if ( !empty( $user_password ) ) {
							if ( false !== strpos( stripslashes( $user_password ), ' ' ) ) {
								$error = 1;
								$error_field = 'user_password';
								$error_msg = __( 'Passwords cannot contain spaces', 'add_new_users' );
							}

							if ( false !== strpos( stripslashes( $user_password ), '\\' ) ) {
								$error = 1;
								$error_field = 'user_password';
								$error_msg = __( 'Passwords may not contain the character "\\".', 'add_new_users' );
							}
						} else {
							$user_password = 'empty';
						}

						$add_new_users_items[$counter]['user_login'] = $validate_user['user_name'];
						$add_new_users_items[$counter]['user_email'] = $validate_user['user_email'];
						$add_new_users_items[$counter]['user_password'] = $user_password;
						$add_new_users_items[$counter]['user_role'] = $user_role;

						$errors[$counter] = $error;
						$error_fields[$counter] = $error_field;
						$error_messages[$counter] = $error_msg;
						if ( 1 == $error )
							$global_errors = $global_errors + 1;

					}
				}

				// if there are errors, display them
				if ( $global_errors > 0 ) {

					echo '<h2>' . __( 'Add New Users', 'add_new_users' ) . '</h2>';
					echo '<div class="message error"><p>' . __( 'Errors were found. Please fix the errors and hit Next.', 'add_new_users' ) . '</p></div>';

					if ( !empty( $_GET['fields'] ) )
						echo "<form name='form1' method='POST' action='users.php?page=add-new-users&action=process&fields=$_GET[fields]'>";
					else
						echo '<form name="form1" method="POST" action="users.php?page=add-new-users&action=process">';

					wp_nonce_field( 'add-new-users-process_new_users' );

					$this->formfields( $errors, $error_messages );
					?>
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e( 'Next', 'add_new_users' ) ?>" />
					<input type="submit" name="Cancel" value="<?php _e( 'Cancel', 'add_new_users' ) ?>" />
					</p>
					<p style="text-align:right;"><?php _e( 'This may take some time so please be patient.', 'add_new_users' ) ?></p>
					</form>
					<?php

				// if names, emails and passwords are all goods, add them to queue
				} else {

					check_admin_referer( 'add-new-users-process_new_users' );

					// process
					if ( count( $add_new_users_items ) > 0 && is_array($add_new_users_items) ) {
						echo '<p>' . __( 'Adding Users...', 'add_new_users' ) . '</p>';
						foreach( $add_new_users_items as $add_new_users_item ) {
							$this->queue_insert( $batch_ID, $add_new_users_item['user_login'], $add_new_users_item['user_email'], $add_new_users_item['user_password'], $add_new_users_item['user_role'] );
						}
					}
					$queue_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->base_prefix}add_new_users_queue WHERE add_new_users_site_ID = '%d' AND add_new_users_blog_ID = '%d'", $wpdb->siteid, $wpdb->blogid ) );

					if ( $queue_count > 0 )
						echo '<script language=\'javascript\'>window.location=\'' . htmlspecialchars_decode( wp_nonce_url( 'users.php?page=add-new-users&action=process_queue', 'add-new-users-process_queue_new_users' ) ) . '\';</script>';
					else
						echo '<script language=\'javascript\'>window.location=\'users.php?page=add-new-users\';</script>';

				}
			break;

			default:
				echo '<h2>' . __( 'Add New Users', 'add_new_users'  ) . '</h2>';
				echo '<p>' . __( 'This tool allows you to create new users on this site and add them to this blog.', 'add_new_users'  ) . '</p>';
				echo class_exists( 'Add_Users' ) ? '<p>' . __( 'To add users that have already been created, please use the <a href="users.php?page=add-users">Add Users functionality here</a>.', 'add_new_users'  ) . '</p>' : '';

				echo '<p>' . __( 'To add the new users simply enter the username you\'d like to give them (please choose carefully as it cannot be changed), their email address and - should you so choose - a password for them.', 'add_new_users'  ) . '</p>';
				echo '<p>' . __( 'You may also select the level that you wish them to access to the site - you can find out more about different levels of access <a href="http://help.edublogs.org/2009/08/24/what-are-the-different-roles-of-users/">here</a>.', 'add_new_users'  ) . '</p>';
				echo '<p>' . __( 'If you do not enter a password a random one will be generated for them.', 'add_new_users'  ) . '</p>';
				echo '<p>' . __( 'All new users will receive an email containing their new username, password and login link.', 'add_new_users'  ) . '</p>';

				$fields = !empty( $_GET['fields'] ) ? "&fields=$_GET[fields]" : '';

				echo "<form name='form1' method='POST' action='users.php?page=add-new-users&action=process$fields'>";
				wp_nonce_field( 'add-new-users-process_new_users' );

				$this->formfields();
				?>
				<p class="submit">
				<input <?php if ( !$this->is_supporter() && $this->supporter_only == 'yes' ) { echo 'disabled="disabled"'; } ?> type="submit" name="Submit" value="<?php _e( 'Next', 'add_new_users' ) ?>" />
				</p>
				<p style="text-align:right;"><?php _e( 'This may take some time so please be patient.', 'add_new_users' ) ?></p>
				</form>
				<?php
			break;
		}
		echo '</div>';
	}

	function formfields( $errors = '', $error_messages = '' ) {
		global $wp_roles;

		for ( $counter = 1; $counter <= $this->fields; $counter += 1) {
			if( isset( $errors[$counter] ) && 1 == $errors[$counter] ) {
				?>
				<h3 style="background-color:#F79696; padding:5px 5px 5px 5px;"><?php echo $counter . ': ' ?><?php echo $error_messages[$counter]; ?></h3>
				<?php
			} else {
				?>
				<h3><?php echo $counter . ':' ?></h3>
				<?php
			}
			?>
				<table class="form-table">
				<tr valign="top">
				<th scope="row"><?php _e( 'Username', 'add_new_users' ) ?></th>
				<td><input <?php if ( !$this->is_supporter() && $this->supporter_only == 'yes' ) { echo 'disabled="disabled"'; } ?> type="text" name="user_login_<?php echo $counter; ?>" id="user_login_<?php echo $counter; ?>" style="width: 95%"  maxlength="200" value="<?php echo isset( $_POST['user_login_' . $counter] ) ? $_POST['user_login_' . $counter] : ''; ?>" />
				<br />
				<?php _e( 'Required', 'add_new_users' ) ?></td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e( 'User Email', 'add_new_users' ) ?></th>
				<td><input <?php if ( !$this->is_supporter() && $this->supporter_only == 'yes' ) { echo 'disabled="disabled"'; } ?> type="text" name="user_email_<?php echo $counter; ?>" id="user_email_<?php echo $counter; ?>" style="width: 95%"  maxlength="200" value="<?php echo isset( $_POST['user_email_' . $counter] ) ? $_POST['user_email_' . $counter] : ''; ?>" />
				<br />
				<?php _e( 'Required', 'add_new_users' ) ?></td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e( 'User Password', 'add_new_users' ) ?></th>
				<td><input <?php if ( !$this->is_supporter() && $this->supporter_only == 'yes' ) { echo 'disabled="disabled"'; } ?> type="text" name="user_password_<?php echo $counter; ?>" id="user_password_<?php echo $counter; ?>" style="width: 95%"  maxlength="200" value="<?php echo isset( $_POST['user_password_' . $counter] ) ? $_POST['user_password_' . $counter] : ''; ?>" />
				<br />
				<?php _e( 'If no password is entered here a random password will be generated and emailed to the user', 'add_new_users' ) ?></td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e( 'User Role', 'add_new_users' ) ?></th>
				<td><select <?php if ( !$this->is_supporter() && $this->supporter_only == 'yes' ) { echo 'disabled="disabled"'; } ?> name="user_role_<?php echo $counter; ?>" style="width: 25%;">
					<?php
					foreach( $wp_roles->role_names as $role => $name ) {
						$name = str_replace( '|User role', '', $name );
						$selected = '';
						if( isset( $_POST['user_role_' . $counter] ) && $_POST['user_role_' . $counter] == $role )
							$selected = 'selected="selected"';
						elseif( $role == 'subscriber' )
							$selected = 'selected="selected"';

						echo "<option {$selected} value=\"{$role}\">{$name}</option>";
					}
					?>
				</select>
				<br />
				</td>
				</tr>
				</table>
			<?php
		}
	}

}

$add_new_users =& new Add_New_Users;

/**
 * Show notification if WPMUDEV Update Notifications plugin is not installed
 *
 **/
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	}
}
