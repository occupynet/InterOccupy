<?php
/**
 * @package Adminimize
 * @subpackage Menu on settings page
 * @author Frank Bültge
 */
if ( ! function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a part of plugin, not much I can do when called directly.";
	exit;
}
?>

		<?php screen_icon('tools'); ?>
		<h2><?php _e('Adminimize', FB_ADMINIMIZE_TEXTDOMAIN ); ?></h2>
		<br class="clear" />
		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div id="minimeu" class="postbox ">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="menu"><?php _e('MiniMenu', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
				<div class="inside">
					<table class="widefat" cellspacing="0">
						<tr>
							<td class="row-title"><a href="#about"><?php _e('About the plugin', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr class="alternate">
							<td class="row-title"><a href="#backend_options"><?php _e('Backend Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr>
							<td class="row-title"><a href="#global_options"><?php _e('Global options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr>
							<td class="row-title"><a href="#dashboard_options"><?php _e('Dashboard options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr class="alternate">
							<td class="row-title"><a href="#config_menu"><?php _e('Menu Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr>
							<td class="row-title"><a href="#config_edit_post"><?php _e('Write options - Post', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr class="alternate">
							<td class="row-title"><a href="#config_edit_page"><?php _e('Write options - Page', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<?php 
						if ( function_exists( 'get_post_types' ) ) {
							$args = array( 'public' => TRUE, '_builtin' => FALSE );
							foreach ( get_post_types( $args ) as $post_type) {
								$post_type_object = get_post_type_object($post_type);
								?>
								<tr class="form-invalid">
									<td class="row-title">
										<a href="#config_edit_<?php echo $post_type; ?>">
										<?php _e('Write options', FB_ADMINIMIZE_TEXTDOMAIN ); echo ' - ' . $post_type_object->label ?>
										</a>
									</td>
								</tr>
								<?php
							}
						}
						?>
						<tr>
							<td class="row-title"><a href="#links_options"><?php _e('Links options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr class="alternate">
							<td class="row-title"><a href="#nav_menu_options"><?php _e('WP Nav Menu', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr>
							<td class="row-title"><a href="#set_theme"><?php _e('Set Theme', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr class="alternate">
							<td class="row-title"><a href="#import"><?php _e('Export/Import Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
						<tr>
							<td class="row-title"><a href="#uninstall"><?php _e('Deinstall Options', FB_ADMINIMIZE_TEXTDOMAIN ); ?></a></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		
		<div id="poststuff" class="ui-sortable meta-box-sortables">
			<div id="about" class="postbox ">
				<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br/></div>
				<h3 class="hndle" id="about-sidebar"><?php _e('About the plugin', FB_ADMINIMIZE_TEXTDOMAIN ) ?></h3>
				<div class="inside">
					<p><?php echo _mw_adminimize_get_plugin_data( 'Title' ); echo ' '; _e( 'Version', FB_ADMINIMIZE_TEXTDOMAIN ); echo ' '; echo _mw_adminimize_get_plugin_data( 'Version' ) ?></p>
					<p><?php echo _mw_adminimize_get_plugin_data( 'Description' ) ?></p>
					<p><?php _e('Further information: Visit the <a href="http://wordpress.org/extend/plugins/adminimize/">plugin homepage</a> for further information or to grab the latest version of this plugin.', FB_ADMINIMIZE_TEXTDOMAIN); ?></p>
					<p>
						<?php _e('You want to thank me? Visit my <a href="http://bueltge.de/wunschliste/">wishlist</a> or donate.', FB_ADMINIMIZE_TEXTDOMAIN); ?>
						<span>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
							<input type="hidden" name="cmd" value="_s-xclick">
							<input type="hidden" name="hosted_button_id" value="4578111">
							<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="<?php _e('PayPal - The safer, easier way to pay online!', FB_ADMINIMIZE_TEXTDOMAIN); ?>">
							<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
						</form>
					</p>
					<p>&copy; Copyright 2008 - <?php echo date('Y'); ?> <a href="http://bueltge.de">Frank B&uuml;ltge</a></p>
					<p><a class="alignright button" href="javascript:void(0);" onclick="window.scrollTo(0,0);" style="margin:3px 0 0 30px;"><?php _e('scroll to top', FB_ADMINIMIZE_TEXTDOMAIN); ?></a><br class="clear" /></p>
				</div>
			</div>
		</div>
		