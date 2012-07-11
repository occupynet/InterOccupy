<?php
/*
Plugin Name: Site Generator Replacement
Plugin URI: http://premium.wpmudev.org/project/site-generator-replacement
Description: Easily customize ALL "Site Generator" text and links. Edit under Site Admin "Options" menu.
Author: Barry (Incsub), S H Mohanjith (Incsub), Andrew Billits (Incsub)
Version: 1.0.2
Author URI: http://premium.wpmudev.org
WDP ID: 18
Network: true
*/

/*
Copyright 2007-2009 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class site_generator_replacement {

	var $site_generator_replacement_settings_page;
	var $site_generator_replacement_settings_page_long;

	function __construct() {

		add_action( 'ultimatebranding_settings_menu_sitegenerator', array(&$this, 'site_generator_replacement_site_admin_options') );
		add_filter( 'ultimatebranding_settings_menu_sitegenerator_process', array(&$this, 'update_site_generator_replacement_site_admin_options'), 10, 1 );

		add_filter("get_the_generator_html", array(&$this, "site_generator_replacement_content"), 99, 2);
		add_filter("get_the_generator_xhtml", array(&$this, "site_generator_replacement_content"), 99, 2);
		add_filter("get_the_generator_atom", array(&$this, "site_generator_replacement_content"), 99, 2);
		add_filter("get_the_generator_rss2", array(&$this, "site_generator_replacement_content"), 99, 2);
		add_filter("get_the_generator_rdf", array(&$this, "site_generator_replacement_content"), 99, 2);
		add_filter("get_the_generator_comment", array(&$this, "site_generator_replacement_content"), 99, 2);
		add_filter("get_the_generator_export", array(&$this, "site_generator_replacement_content"), 99, 2);
	}

	function site_generator_replacement() {
		$this->__construct();
	}

	function site_generator_replacement_content($gen, $type) {

		$global_site_generator = ub_get_option("site_generator_replacement");
		$global_site_link = ub_get_option("site_generator_replacement_link");

		if ( empty($global_site_generator) ) {
			global $current_site;
			$global_site_generator = $current_site->site_name;
		}
		if ( empty($global_site_link) ) {
			global $current_site;
			$global_site_link = "http://". $current_site->domain . $current_site->path;
		}

		switch($type) {
			case 'html':
				$gen = '<meta name="generator" content="' . $global_site_generator . '">' . "\n";
				break;
			case 'xhtml':
				$gen = '<meta name="generator" content="' . $global_site_generator . '" />' . "\n";
				break;
			case 'atom':
				$gen = '<generator uri="' . $global_site_link . '" version="' . get_bloginfo_rss( 'version' ) . '">' . $global_site_generator . '</generator>';
				break;
			case 'rss2':
				$gen = '<generator>' . $global_site_link . '?v=' . get_bloginfo_rss( 'version' ) . '</generator>';
				break;
			case 'rdf':
				$gen = '<admin:generatorAgent rdf:resource="' . $global_site_link . '?v=' . get_bloginfo_rss( 'version' ) . '" />';
				break;
			case 'comment':
				$gen = '<!-- generator="' . $global_site_generator . '/' . get_bloginfo( 'version' ) . '" -->';
				break;
			case 'export':
				$gen = '<!-- generator="' . $global_site_generator . '/' . get_bloginfo_rss('version') . '" created="'. date('Y-m-d H:i') . '"-->';
				break;
		}
		return $gen;
	}

	function update_site_generator_replacement_site_admin_options( $status ) {

		ub_update_option( "site_generator_replacement", $_POST['site_generator_replacement'] );
		ub_update_option( "site_generator_replacement_link", $_POST['site_generator_replacement_link'] );

		if($status === false) {
			return $status;
		} else {
			return true;
		}

	}

	function site_generator_replacement_site_admin_options() {

		global $wpdb, $wp_roles, $current_user;

		$global_site_generator = ub_get_option("site_generator_replacement");
		$global_site_link = ub_get_option("site_generator_replacement_link");
		if ( empty($global_site_generator) ) {
			global $current_site;
			$global_site_generator = $current_site->site_name;
		}
		if ( empty($global_site_link) ) {
			global $current_site;
			$global_site_link = "http://". $current_site->domain . $current_site->path;
		}

		?>
			<div class="postbox">
			<h3 class="hndle" style='cursor:auto;'><span><?php _e( 'Site Generator Options', 'ub' ) ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e('Generator Text', 'ub') ?>
						</th>
						<td>
							<input type="text" name="site_generator_replacement" id="site_generator_replacement" style="width: 95%" value="<?php echo stripslashes($global_site_generator); ?>" />
							<?php _e('<br /><small>Change the "generator" information from WordPress to something you prefer.</small>', 'ub'); ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Generator Link', 'ub') ?>
						</th>
						<td>
							<input type="text" name="site_generator_replacement_link" id="site_generator_replacement_link" style="width: 95%" value="<?php echo stripslashes($global_site_link); ?>" />
							<?php _e('<br /><small>Change the "generator link" from WordPress to something you prefer.</small>', 'ub'); ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

}

$ub_site_generator_replacement = new site_generator_replacement();



