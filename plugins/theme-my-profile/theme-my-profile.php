<?php
/*
Plugin Name: Theme My Profile
Plugin URI: http://www.jfarthing.com/extend/plugins/theme-my-profile
Description: Allows you to theme a user's profile based upon their role.
Version: 1.3.1
Author: Jeff Farthing
Author URI: http://www.jfarthing.com
Text Domain: theme-my-profile
*/


if ( !class_exists( 'Theme_My_Profile' ) ) {
    class Theme_My_Profile {

        var $options = array();
        var $errors;

		var $current_user;
		var $page_link;

        function Theme_My_Profile() {
            $this->__construct();
        }

        function __construct() {

            load_plugin_textdomain( 'theme-my-profile', '', 'theme-my-profile/language' );

			add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
            add_action( 'init', array( &$this, 'init' ) );
            add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1 );

            add_action( 'admin_init', array( &$this, 'admin_init' ) );
            add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

			add_filter( 'wp_list_pages_excludes', array( &$this, 'wp_list_pages_excludes' ) );

            add_shortcode( 'theme-my-profile', array( &$this, 'shortcode' ) );
        }

		function plugins_loaded() {
			/* Add this after everything has loaded, to avoided undefined core functions.
			 * Maybe I should start adding all actions and filters here?
			 */
			add_filter( 'site_url', array( &$this, 'site_url' ), 10, 3 );
			add_filter( 'admin_url', array( &$this, 'site_url' ), 10, 2 );
		}

        function init() {
            global $pagenow;

            $this->load_options();

			$this->page_link = $this->get_profile_page_link();

			$this->current_user = wp_get_current_user();

            if ( is_user_logged_in() ) {
				$user_role = $this->current_user->roles[0];
				if ( 'profile.php' == $pagenow ) {
                    if ( !empty( $this->options['theme_profile'][$user_role] ) && !isset( $_REQUEST['page'] ) ) {
                        $redirect_to = add_query_arg( $_GET, $this->page_link );
                        wp_redirect( $redirect_to );
                        exit;
                    }
                } else if ( is_admin() && $this->options['admin_lockout'][$user_role] ) {
                    wp_redirect( $this->page_link );
                    exit();
                }
            }
        }

        function template_redirect() {
			global $action, $redirect, $profile, $user_id, $wp_http_referer;

            if ( $this->is_profile_page() ) {

                if ( !is_user_logged_in() ) {
                    wp_redirect( wp_login_url() );
                    exit();
                }

                require_once( ABSPATH . 'wp-admin/includes/misc.php' );
                require_once( ABSPATH . 'wp-admin/includes/template.php' );
                require_once( ABSPATH . 'wp-admin/includes/user.php' );
                require_once( ABSPATH . WPINC . '/registration.php' );

				define( 'IS_PROFILE_PAGE', true );

                $this->errors = new WP_Error();

				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

				wp_enqueue_script( 'user-profile', admin_url( "js/user-profile$suffix.js" ), array( 'jquery' ), '', true );
				wp_enqueue_script( 'password-strength-meter', admin_url( "js/password-strength-meter$suffix.js" ), array( 'jquery' ), '', true );
				wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
					'empty' => __( 'Strength indicator' ),
					'short' => __( 'Very weak' ),
					'bad' => __( 'Weak' ),
					/* translators: password strength */
					'good' => _x( 'Medium', 'password strength' ),
					'strong' => __( 'Strong' ),
					'l10n_print_after' => 'try{convertEntities(pwsL10n);}catch(e){};'
				) );

                if ( $this->options['use_css'] ) {
                    if ( file_exists( get_stylesheet_directory() . '/theme-my-profile.css' ) )
                        $css_file = get_stylesheet_directory_uri() . '/theme-my-profile.css';
                    elseif ( file_exists( get_template_directory() . '/theme-my-profile.css' ) )
                        $css_file = get_template_directory_uri() . '/theme-my-profile.css';
                    else
                        $css_file = plugins_url( '/theme-my-profile/css/theme-my-profile.css' );

                    wp_enqueue_style( 'theme-my-profile', $css_file );
                }

                wp_reset_vars( array( 'action', 'redirect', 'profile', 'user_id', 'wp_http_referer' ) );

                $wp_http_referer = remove_query_arg( array( 'update', 'delete_count' ), stripslashes( $wp_http_referer ) );

				// Execute confirmed email change. See send_confirmation_on_profile_email().
				if ( function_exists( 'is_multisite' ) && is_multisite() && IS_PROFILE_PAGE && isset( $_GET[ 'newuseremail' ] ) && $this->current_user->ID ) {
					$new_email = get_option( $this->current_user->ID . '_new_email' );
					if ( $new_email[ 'hash' ] == $_GET[ 'newuseremail' ] ) {
						$user->ID = $this->current_user->ID;
						$user->user_email = esc_html( trim( $new_email[ 'newemail' ] ) );
						if ( $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM {$wpdb->signups} WHERE user_login = %s", $this->current_user->user_login ) ) )
							$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->signups} SET user_email = %s WHERE user_login = %s", $user->user_email, $this->current_user->user_login ) );
						wp_update_user( get_object_vars( $user ) );
						delete_option( $this->current_user->ID . '_new_email' );
						wp_redirect( add_query_arg( array( 'updated' => 'true' ), $this->page_link ) );
						die();
					}
				}

                $action = isset( $_POST['action'] ) ? $_POST['action'] : '';

                if ( 'update' == $action ) {
                    check_admin_referer( 'update-user_' . $this->current_user->ID );

                    if ( !current_user_can( 'edit_user', $this->current_user->ID ) )
                        wp_die( __( 'You do not have permission to edit this user.' ) );

                    do_action( 'personal_options_update', $this->current_user->ID );

					$this->errors = edit_user( $this->current_user->ID );

                    if ( !is_wp_error( $this->errors ) ) {
                        $redirect = add_query_arg( array( 'updated' => 'true', 'wp_http_referer' => urlencode( $wp_http_referer ) ), $this->page_link );
                        wp_redirect( $redirect );
                        exit;
                    }
                }
				if ( isset( $_GET['updated'] ) )
					$this->errors->add( 'updated', __( 'Profile updated.' ), 'message' );
            }
        }

        function display_profile( $args = '' ) {
			global $profileuser;

            ob_start();
			$this->print_header();
            $this->print_profile_form();
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }

        function print_header( $message = '' ) {
            global $error;

			if ( empty( $this->errors ) )
				$this->errors = new WP_Error();

            echo '<div id="profile">';

            $message = apply_filters( 'profile_message', $message );
            if ( !empty( $message ) ) $output .= $message . "\n";

            // Incase a plugin uses $error rather than the $errors object
            if ( !empty( $error ) ) {
                $this->errors->add( 'error', $error );
                unset( $error );
            }

            if ( $this->errors->get_error_code() ) {
                $errors = '';
                $messages = '';
                foreach ( $this->errors->get_error_codes() as $code ) {
                    $severity = $this->errors->get_error_data( $code );
                    foreach ( $this->errors->get_error_messages( $code ) as $error ) {
						if ( 'message' == $severity )
							$messages .= '	' . $error . "<br />\n";
						else
							$errors .= '	' . $error . "<br />\n";
                    }
                }
                if ( !empty( $errors ) )
                    echo '<p class="error">' . apply_filters( 'profile_errors', $errors ) . "</p>\n";
                if ( !empty( $messages ) )
                    echo '<p class="message">' . apply_filters( 'profile_messages', $messages ) . "</p>\n";
			}
        }

        function print_profile_form() {
			global $action, $redirect, $profile, $user_id, $wp_http_referer, $profileuser;

            wp_reset_vars( array( 'action', 'redirect', 'profile', 'user_id', 'wp_http_referer' ) );

            $wp_http_referer = remove_query_arg( array( 'update', 'delete_count' ), stripslashes( $wp_http_referer ) );

            $profileuser = get_user_to_edit( $this->current_user->ID ); ?>

            <form id="your-profile" action="<?php echo esc_url( $this->page_link ); ?>" method="post"<?php do_action( 'user_edit_form_tag' ); ?>>
            <?php wp_nonce_field( 'update-user_' . $this->current_user->ID ) ?>
            <?php if ( $wp_http_referer ) : ?>
                <input type="hidden" name="wp_http_referer" value="<?php echo esc_url( $wp_http_referer ); ?>" />
            <?php endif; ?>
            <p>
            <input type="hidden" name="from" value="profile" />
            <input type="hidden" name="checkuser_id" value="<?php echo $this->current_user->ID; ?>" />
            </p>

            <?php if ( has_action( 'personal_options' ) || has_filter( 'profile_personal_options' ) ) : ?>

            <h3><?php _e( 'Personal Options' ); ?></h3>

            <table class="form-table">
            <?php do_action( 'personal_options', $profileuser ); ?>
            </table>
            <?php do_action( 'profile_personal_options', $profileuser ); ?>

            <?php endif; ?>

            <h3><?php _e( 'Name' ) ?></h3>

            <table class="form-table">
            <tr>
                <th><label for="user_login"><?php _e( 'Username' ); ?></label></th>
                <td><input type="text" name="user_login" id="user_login" value="<?php echo esc_attr( $profileuser->user_login ); ?>" disabled="disabled" class="regular-text" /> <span class="description"><?php _e( 'Usernames cannot be changed.' ); ?></span></td>
            </tr>

            <tr>
                <th><label for="first_name"><?php _e( 'First name' ); ?></label></th>
                <td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $profileuser->first_name ); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="last_name"><?php _e( 'Last name' ); ?></label></th>
                <td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $profileuser->last_name ); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="nickname"><?php _e( 'Nickname' ); ?> <span class="description"><?php _e( '(required)' ); ?></span></label></th>
                <td><input type="text" name="nickname" id="nickname" value="<?php echo esc_attr( $profileuser->nickname ); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="display_name"><?php _e( 'Display name publicly as' ) ?></label></th>
                <td>
					<select name="display_name" id="display_name">
					<?php
						$public_display = array();
						$public_display['display_username']  = $profileuser->user_login;
						$public_display['display_nickname']  = $profileuser->nickname;
						if ( !empty( $profileuser->first_name ) )
							$public_display['display_firstname'] = $profileuser->first_name;
						if ( !empty( $profileuser->last_name ) )
							$public_display['display_lastname'] = $profileuser->last_name;
						if ( !empty( $profileuser->first_name ) && !empty( $profileuser->last_name ) ) {
							$public_display['display_firstlast'] = $profileuser->first_name . ' ' . $profileuser->last_name;
							$public_display['display_lastfirst'] = $profileuser->last_name . ' ' . $profileuser->first_name;
						}
						if ( !in_array( $profileuser->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
							$public_display = array( 'display_displayname' => $profileuser->display_name ) + $public_display;
						$public_display = array_map( 'trim', $public_display );
						$public_display = array_unique( $public_display );
						foreach ( $public_display as $id => $item ) {
					?>
						<option id="<?php echo $id; ?>" value="<?php echo esc_attr( $item ); ?>"<?php selected( $profileuser->display_name, $item ); ?>><?php echo $item; ?></option>
					<?php
						}
					?>
					</select>
                </td>
            </tr>
            </table>

            <h3><?php _e( 'Contact Info' ) ?></h3>

            <table class="form-table">
            <tr>
                <th><label for="email"><?php _e( 'E-mail' ); ?> <span class="description"><?php _e( '(required)' ); ?></span></label></th>
                <td><input type="text" name="email" id="email" value="<?php echo esc_attr( $profileuser->user_email ); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="url"><?php _e( 'Website' ); ?></label></th>
                <td><input type="text" name="url" id="url" value="<?php echo esc_attr( $profileuser->user_url ); ?>" class="regular-text code" /></td>
            </tr>

            <?php if ( function_exists( '_wp_get_user_contactmethods' ) ) :
                foreach ( _wp_get_user_contactmethods() as $name => $desc ) {
            ?>
            <tr>
                <th><label for="<?php echo $name; ?>"><?php echo apply_filters( 'user_'.$name.'_label', $desc ); ?></label></th>
                <td><input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr( $profileuser->$name ); ?>" class="regular-text" /></td>
            </tr>
            <?php
                }
				endif;
            ?>
            </table>

            <h3><?php _e( 'About Yourself' ); ?></h3>

            <table class="form-table">
            <tr>
                <th><label for="description"><?php _e( 'Biographical Info' ); ?></label></th>
                <td><textarea name="description" id="description" rows="5" cols="30"><?php echo esc_html( $profileuser->description ); ?></textarea><br />
                <span class="description"><?php _e( 'Share a little biographical information to fill out your profile. This may be shown publicly.' ); ?></span></td>
            </tr>

            <?php
            $show_password_fields = apply_filters( 'show_password_fields', true, $profileuser );
            if ( $show_password_fields ) :
            ?>
			<tr id="password">
				<th><label for="pass1"><?php _e( 'New Password' ); ?></label></th>
				<td><input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" /> <span class="description"><?php _e( 'If you would like to change the password type a new one. Otherwise leave this blank.' ); ?></span><br />
					<input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" /> <span class="description"><?php _e( 'Type your new password again.' ); ?></span><br />
					<div id="pass-strength-result"><?php _e( 'Strength indicator' ); ?></div>
					<p class="description indicator-hint"><?php _e( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).' ); ?></p>
				</td>
			</tr>
            <?php endif; ?>
            </table>

            <?php do_action( 'show_user_profile', $profileuser ); ?>

            <?php if ( count( $profileuser->caps ) > count( $profileuser->roles ) && apply_filters( 'additional_capabilities_display', true, $profileuser ) ) { ?>
            <br class="clear" />
                <table width="99%" style="border: none;" cellspacing="2" cellpadding="3" class="editform">
                    <tr>
                        <th scope="row"><?php _e( 'Additional Capabilities' ) ?></th>
                        <td><?php
						global $wp_roles;
                        $output = '';
                        foreach ( $profileuser->caps as $cap => $value ) {
                            if ( !$wp_roles->is_role( $cap ) ) {
                                if ( $output != '' )
                                    $output .= ', ';
                                $output .= $value ? $cap : "Denied: {$cap}";
                            }
                        }
                        echo $output;
                        ?></td>
                    </tr>
                </table>
            <?php } ?>

            <p class="submit">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $this->current_user->ID ); ?>" />
                <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Update Profile' ); ?>" name="submit" />
            </p>
            </form>
            </div>
            <?php
        }

		function is_profile_page( $page_id = '' ) {
			if ( empty( $page_id ) ) {
				global $wp_query;
				if ( $wp_query->is_page )
					$page_id = $wp_query->get_queried_object_id();
			}

			$is_profile_page = ( $page_id == $this->options['page_id'] );

			return apply_filters( 'tmp_is_profile_page', $is_profile_page );
		}

		function get_profile_page_link( $query = '' ) {
			$link = get_page_link( $this->options['page_id'] );
			if ( !empty( $query ) ) {
				$q = wp_parse_args( $query );
				$link = add_query_arg( $q, $link );
			}
			return apply_filters( 'tmp_page_link', $link, $query );
		}

        function shortcode( $atts = '' ) {
            $atts = shortcode_atts( array(), $atts );
            return $this->display_profile( $atts );
        }

        function site_url( $url, $path, $orig_scheme = '' ) {
            global $wp_rewrite;

            if ( is_user_logged_in() ) {
                $user_role = $this->current_user->roles[0];
                if ( $this->options['theme_profile'][$user_role] && preg_match( '/profile.php/', $url ) ) {
                    $parsed_url = parse_url( $url );
                    $url = $this->page_link;
					if ( isset( $parsed_url['query'] ) ) {
						wp_parse_str( $parsed_url['query'], $r );
						foreach ( $r as $k => $v ) {
							if ( strpos( $v, ' ' ) !== false )
								$r[$k] = rawurlencode( $v );
						}
						$url = add_query_arg( $r, $url );
					}
                } elseif ( $this->options['admin_lockout'][$user_role] && preg_match( '/wp-admin/', $url ) ) {
                    $url = $this->page_link;
                }
            }
            return $url;
        }

		function wp_list_pages_excludes( $excludes ) {
			$excludes = (array) $excludes;
			if ( 'never' == $this->options['show_page'] || ( 'logged' == $this->options['show_page'] && !is_user_logged_in() ) )
				$excludes[] = $this->options['page_id'];
			return $excludes;
		}

        function admin_init() {
            register_setting( 'theme_my_profile_settings', 'theme_my_profile', array( $this, 'admin_settings' ) );
        }

        function admin_settings( $settings ) {
            global $wp_roles;

            $settings['use_css'] = ( isset( $settings['use_css'] ) ) ? 1 : 0;

            foreach ( $wp_roles->get_names() as $role => $title ) {
                $settings['theme_profile'][$role] = ( isset( $settings['theme_profile'][$role] ) ) ? 1 : 0;
                $settings['admin_lockout'][$role] = ( isset( $settings['admin_lockout'][$role] ) ) ? 1 : 0;
            }
            return $settings;
        }

        function admin_menu(){
            add_options_page( __( 'Theme My Profile', 'theme-my-profile' ), __( 'Theme My Profile', 'theme-my-profile' ), 8, 'theme-my-profile/admin/admin.php' );
        }

		function load_options() {
			global $wp_roles;

			$options = get_option( 'theme_my_profile' );
			if ( $options && is_array( $options ) ) {
				foreach ( $options as $key => $value ) {
					$this->options[$key] = $value;
				}
			} else {
				if ( empty( $wp_roles ) )
					$wp_roles = new WP_Roles();

				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_data = get_plugin_data( __FILE__ );

				$profile_page = get_page_by_title( 'Profile' );

				$this->options['version'] = $plugin_data['Version'];
				$this->options['page_id'] = ( is_object( $profile_page ) ) ? $profile_page->ID : 0;
				$this->options['show_page'] = 'logged';
				$this->options['use_css'] = 1;
				foreach ( $wp_roles->get_names() as $role => $title ) {
					$this->options['theme_profile'][$role] = 1;
					$this->options['admin_lockout'][$role] = 0;
				}
				update_option( 'theme_my_profile', $this->options );
			}
		}

        function install() {
			if ( $page = get_page_by_title( 'Profile' ) ) {
				$page_id = $page->ID;
				if ( 'trash' == $page->post_status )
					wp_untrash_post( $page_id );
			} else {
				$insert = array(
					'post_title' => 'Profile',
					'post_status' => 'publish',
					'post_type' => 'page',
					'post_content' => '[theme-my-profile]',
					'comment_status' => 'closed',
					'ping_status' => 'closed'
					);
				$page_id = wp_insert_post( $insert );
			}
			$this->load_options();
			$this->options['page_id'] = (int) $page_id;
            update_option( 'theme_my_profile', $this->options );
        }

        function uninstall() {
			$this->load_options();
			if ( get_page( $this->options['page_id'] ) )
				wp_delete_post( $this->options['page_id'] );
            delete_option( 'theme_my_profile' );
        }
    }
}

//instantiate the class
if ( class_exists('Theme_My_Profile') ) {

	function theme_my_profile_install() {
		global $theme_my_profile;
		if ( is_object( $theme_my_profile ) )
			$theme_my_profile->install();
	}
	register_activation_hook( __FILE__, 'theme_my_profile_install' );

	function theme_my_profile_uninstall() {
		global $theme_my_profile;
		if ( is_object( $theme_my_profile ) )
			$theme_my_profile->uninstall();
	}
	register_uninstall_hook( __FILE__, 'theme_my_profile_uninstall' );

    $Theme_My_Profile =& new Theme_My_Profile();
}

?>