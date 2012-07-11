<?php
/*
Plugin Name: Global Site Tags
Plugin URI: http://premium.wpmudev.org/project/global-site-tags
Description: This powerful plugin allows you to simply display a global tag cloud for your entire WordPress Multisite network. How cool is that!
Author: Andrew Billits (Incsub)
Version: 2.1.2
Author URI: http://premium.wpmudev.org
WDP ID: 105
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

/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</p></div>';
  }
}
/* --------------------------------------------------------------------- */

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//

$global_site_tags_current_version = '2.1.1';
$global_site_tags_base = 'tags'; //domain.tld/BASE/ Ex: domain.tld/tags/

//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

if ($current_blog->domain . $current_blog->path == $current_site->domain . $current_site->path){
	add_filter('generate_rewrite_rules','global_site_tags_rewrite');
	add_action('admin_head', 'global_site_tags_make_current');
	add_filter('the_content', 'global_site_tags_output');
	add_filter('the_title', 'global_site_tags_title_output', 99, 2);
	add_action('admin_footer', 'global_site_tags_page_setup');
}

add_action('wpmu_options', 'global_site_tags_site_admin_options');
add_action('update_wpmu_options', 'global_site_tags_site_admin_options_process');

add_action( 'plugins_loaded', 'global_site_tags_internationalisation');

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function global_site_tags_internationalisation() {
	// Load the text-domain
	$locale = apply_filters( 'globalsitetags_locale', get_locale() );
	$mofile = dirname(__FILE__) . "/languages/globalsitetags-$locale.mo";

	if ( file_exists( $mofile ) )
		load_textdomain( 'globalsitetags', $mofile );
}

function global_site_tags_make_current() {
	global $wpdb, $post_indexer_current_version;
	if (get_site_option( "global_site_tags_version" ) == '') {
		add_site_option( 'global_site_tags_version', '0.0.0' );
	}

	if (get_site_option( "global_site_tags_version" ) == $global_site_tags_current_version) {
		// do nothing
	} else {
		//update to current version
		update_site_option( "global_site_tags_installed", "no" );
		update_site_option( "global_site_tags_version", $global_site_tags_current_version );
	}
	global_site_tags_global_install();
	//--------------------------------------------------//
	if (get_option( "global_site_tags_version" ) == '') {
		add_option( 'global_site_tags_version', '0.0.0' );
	}

	if (get_option( "global_site_tags_version" ) == $post_indexer_current_version) {
		// do nothing
	} else {
		//update to current version
		update_option( "global_site_tags_version", $post_indexer_current_version );
		global_site_tags_blog_install();
	}
}

function global_site_tags_blog_install() {
	global $wpdb, $post_indexer_current_version;
	//$post_indexer_table1 = "";
	//$wpdb->query( $post_indexer_table1 );
}

function global_site_tags_global_install() {
	global $wpdb, $post_indexer_current_version;
	if (get_site_option( "global_site_tags_installed" ) == '') {
		add_site_option( 'global_site_tags_installed', 'no' );
	}

	if (get_site_option( "global_site_tags_installed" ) == "yes") {
		// do nothing
	} else {

		$global_site_tags_table1 = "CREATE TABLE `" . $wpdb->base_prefix . "sitecategories` (
		  `cat_ID` bigint(20) NOT NULL auto_increment,
		  `cat_name` varchar(55) NOT NULL default '',
		  `category_nicename` varchar(200) NOT NULL default '',
		  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  PRIMARY KEY  (`cat_ID`),
		  KEY `category_nicename` (`category_nicename`),
		  KEY `last_updated` (`last_updated`)
		);";

		$wpdb->query( $global_site_tags_table1 );

		update_site_option( "global_site_tags_installed", "yes" );

		$global_site_tags_wp_rewrite = new WP_Rewrite;
		$global_site_tags_wp_rewrite->flush_rules();
	}
}

function global_site_tags_page_setup() {
	global $wpdb, $user_ID, $global_site_tags_base;
	if ( get_site_option('global_site_tags_page_setup') != 'complete' && is_site_admin() ) {
		$page_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_name = '" . $global_site_tags_base . "' AND post_type = 'page'");
		if ( $page_count < 1 ) {
			$wpdb->query( "INSERT INTO " . $wpdb->posts . " ( post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count ) VALUES ( '" . $user_ID . "', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', 'Tags', '', 'publish', 'closed', 'closed', '', '" . $global_site_tags_base . "', '', '', '" . current_time( 'mysql' ) . "', '" . current_time( 'mysql' ) . "', '', 0, '', 0, 'page', '', 0 )" );
		}
		update_site_option('global_site_tags_page_setup', 'complete');
	}
}

function global_site_tags_site_admin_options() {
	$global_site_tags_per_page = get_site_option('global_site_tags_per_page', '10');
	$global_site_tags_shown = get_site_option('global_site_tags_shown', '50');
	$global_site_tags_background_color = get_site_option('global_site_tags_background_color', '#F2F2EA');
	$global_site_tags_alternate_background_color = get_site_option('global_site_tags_alternate_background_color', '#FFFFFF');
	$global_site_tags_border_color = get_site_option('global_site_tags_border_color', '#CFD0CB');
	$global_site_tags_banned_tags = get_site_option('global_site_tags_banned_tags', 'uncategorized');
	$global_site_tags_tag_cloud_order = get_site_option('global_site_tags_tag_cloud_order', 'count');

	$global_site_tags_post_type = get_site_option('global_site_tags_post_type', 'post');

	?>
		<h3><?php _e('Site Tags', "globalsitetags") ?></h3>
		<table class="form-table">
			<tr valign="top">
                <th width="33%" scope="row"><?php _e('Tags Shown', "globalsitetags") ?></th>
                <td>
				<select name="global_site_tags_shown" id="global_site_tags_shown">
				   <option value="5" <?php if ( $global_site_tags_shown == '5' ) { echo 'selected="selected"'; } ?> ><?php _e('5', "globalsitetags"); ?></option>
				   <option value="10" <?php if ( $global_site_tags_shown == '10' ) { echo 'selected="selected"'; } ?> ><?php _e('10', "globalsitetags"); ?></option>
				   <option value="15" <?php if ( $global_site_tags_shown == '15' ) { echo 'selected="selected"'; } ?> ><?php _e('15', "globalsitetags"); ?></option>
				   <option value="20" <?php if ( $global_site_tags_shown == '20' ) { echo 'selected="selected"'; } ?> ><?php _e('20', "globalsitetags"); ?></option>
				   <option value="25" <?php if ( $global_site_tags_shown == '25' ) { echo 'selected="selected"'; } ?> ><?php _e('25', "globalsitetags"); ?></option>
				   <option value="30" <?php if ( $global_site_tags_shown == '30' ) { echo 'selected="selected"'; } ?> ><?php _e('30', "globalsitetags"); ?></option>
				   <option value="35" <?php if ( $global_site_tags_shown == '35' ) { echo 'selected="selected"'; } ?> ><?php _e('35', "globalsitetags"); ?></option>
				   <option value="40" <?php if ( $global_site_tags_shown == '40' ) { echo 'selected="selected"'; } ?> ><?php _e('40', "globalsitetags"); ?></option>
				   <option value="45" <?php if ( $global_site_tags_shown == '45' ) { echo 'selected="selected"'; } ?> ><?php _e('45', "globalsitetags"); ?></option>
				   <option value="50" <?php if ( $global_site_tags_shown == '50' ) { echo 'selected="selected"'; } ?> ><?php _e('50', "globalsitetags"); ?></option>
				</select>
                <br /><?php //_e('') ?></td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Listing Per Page', "globalsitetags") ?></th>
                <td>
				<select name="global_site_tags_per_page" id="global_site_tags_per_page">
				   <option value="5" <?php if ( $global_site_tags_per_page == '5' ) { echo 'selected="selected"'; } ?> ><?php _e('5', "globalsitetags"); ?></option>
				   <option value="10" <?php if ( $global_site_tags_per_page == '10' ) { echo 'selected="selected"'; } ?> ><?php _e('10', "globalsitetags"); ?></option>
				   <option value="15" <?php if ( $global_site_tags_per_page == '15' ) { echo 'selected="selected"'; } ?> ><?php _e('15', "globalsitetags"); ?></option>
				   <option value="20" <?php if ( $global_site_tags_per_page == '20' ) { echo 'selected="selected"'; } ?> ><?php _e('20', "globalsitetags"); ?></option>
				   <option value="25" <?php if ( $global_site_tags_per_page == '25' ) { echo 'selected="selected"'; } ?> ><?php _e('25', "globalsitetags"); ?></option>
				   <option value="30" <?php if ( $global_site_tags_per_page == '30' ) { echo 'selected="selected"'; } ?> ><?php _e('30', "globalsitetags"); ?></option>
				   <option value="35" <?php if ( $global_site_tags_per_page == '35' ) { echo 'selected="selected"'; } ?> ><?php _e('35', "globalsitetags"); ?></option>
				   <option value="40" <?php if ( $global_site_tags_per_page == '40' ) { echo 'selected="selected"'; } ?> ><?php _e('40', "globalsitetags"); ?></option>
				   <option value="45" <?php if ( $global_site_tags_per_page == '45' ) { echo 'selected="selected"'; } ?> ><?php _e('45', "globalsitetags"); ?></option>
				   <option value="50" <?php if ( $global_site_tags_per_page == '50' ) { echo 'selected="selected"'; } ?> ><?php _e('50', "globalsitetags"); ?></option>
				</select>
                <br /><?php //_e('') ?></td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Background Color', "globalsitetags") ?></th>
                <td><input name="global_site_tags_background_color" type="text" id="global_site_tags_background_color" value="<?php echo $global_site_tags_background_color; ?>" size="20" />
                <br /><?php _e('Default', "globalsitetags") ?>: #F2F2EA</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Alternate Background Color', "globalsitetags") ?></th>
                <td><input name="global_site_tags_alternate_background_color" type="text" id="global_site_tags_alternate_background_color" value="<?php echo $global_site_tags_alternate_background_color; ?>" size="20" />
                <br /><?php _e('Default', "globalsitetags") ?>: #FFFFFF</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Border Color', "globalsitetags") ?></th>
                <td><input name="global_site_tags_border_color" type="text" id="global_site_tags_border_color" value="<?php echo $global_site_tags_border_color; ?>" size="20" />
                <br /><?php _e('Default', "globalsitetags") ?>: #CFD0CB</td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Banned Tags', "globalsitetags") ?></th>
                <td><input name="global_site_tags_banned_tags" type="text" id="global_site_tags_banned_tags" value="<?php echo $global_site_tags_banned_tags; ?>" style="width: 95%;" />
                <br /><?php _e('Banned tags will not appear in tag clouds. Please separate tags with commas. Ex: tag1, tag2, tag3', "globalsitetags") ?></td>
            </tr>
            <tr valign="top">
                <th width="33%" scope="row"><?php _e('Tag Cloud Order', "globalsitetags") ?></th>
                <td>
				<select name="global_site_tags_tag_cloud_order" id="global_site_tags_tag_cloud_order">
				   <option value="count" <?php if ( $global_site_tags_tag_cloud_order == 'count' ) { echo 'selected="selected"'; } ?> ><?php _e('Tag Count', "globalsitetags"); ?></option>
				   <option value="most_recent" <?php if ( $global_site_tags_tag_cloud_order == 'most_recent' ) { echo 'selected="selected"'; } ?> ><?php _e('Most Recent', "globalsitetags"); ?></option>
				</select>
                <br /><?php //_e('') ?></td>
            </tr>

			<tr valign="top">
	                <th width="33%" scope="row"><?php _e('List Post Type', 'globalsitetags') ?></th>
	                <td>
					<select name="global_site_tags_post_type" id="global_site_tags_post_type">
					   <option value="all" <?php selected( $global_site_tags_post_type, 'all' ); ?> ><?php _e('all', 'globalsitetags'); ?></option>
						<?php
						$post_types = global_site_tags_get_post_types();
						if(!empty($post_types)) {
							foreach($post_types as $r) {
								?>
								<option value="<?php echo $r; ?>" <?php selected( $global_site_tags_post_type, $r ); ?> ><?php _e($r, 'globalsitetags'); ?></option>
								<?php
							}
						}
						?>
					</select></td>
	        </tr>

		</table>
	<?php
}

function global_site_tags_get_post_types() {
	global $wpdb;

	$sql = $wpdb->prepare( "SELECT post_type FROM " . $wpdb->base_prefix . "site_posts GROUP BY post_type" );

	$results = $wpdb->get_col( $sql );

	return $results;
}

function global_site_tags_site_admin_options_process() {

	update_site_option( 'global_site_tags_shown' , $_POST['global_site_tags_shown']);
	update_site_option( 'global_site_tags_per_page' , $_POST['global_site_tags_per_page']);
	update_site_option( 'global_site_tags_background_color' , trim( $_POST['global_site_tags_background_color'] ));
	update_site_option( 'global_site_tags_alternate_background_color' , trim( $_POST['global_site_tags_alternate_background_color'] ));
	update_site_option( 'global_site_tags_border_color' , trim( $_POST['global_site_tags_border_color'] ));
	update_site_option( 'global_site_tags_banned_tags' , trim( $_POST['global_site_tags_banned_tags'] ));
	update_site_option( 'global_site_tags_tag_cloud_order' , trim( $_POST['global_site_tags_tag_cloud_order'] ));

	update_site_option('global_site_tags_post_type', $_POST['global_site_tags_post_type'] );
}

function global_site_tags_rewrite($wp_rewrite){
	global $global_site_tags_base;
    $global_site_tags_rules = array(
        $global_site_tags_base . '/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $global_site_tags_base,
        $global_site_tags_base . '/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $global_site_tags_base,
        $global_site_tags_base . '/([^/]+)/([^/]+)/?$' => 'index.php?pagename=' . $global_site_tags_base,
        $global_site_tags_base . '/([^/]+)/?$' => 'index.php?pagename=' . $global_site_tags_base
    );
    $wp_rewrite->rules = $global_site_tags_rules + $wp_rewrite->rules;
	return $wp_rewrite;
}

function global_site_tags_url_parse(){
	global $wpdb, $current_site, $global_site_tags_base;
	$global_site_tags_url = $_SERVER['REQUEST_URI'];
	if ( $current_site->path != '/' ) {
		$global_site_tags_url = str_replace('/' . $current_site->path . '/', '', $global_site_tags_url);
		$global_site_tags_url = str_replace($current_site->path . '/', '', $global_site_tags_url);
		$global_site_tags_url = str_replace($current_site->path, '', $global_site_tags_url);
	}
	$global_site_tags_url = ltrim($global_site_tags_url, "/");
	$global_site_tags_url = rtrim($global_site_tags_url, "/");
	$global_site_tags_url = ltrim($global_site_tags_url, $global_site_tags_base);
	$global_site_tags_url = ltrim($global_site_tags_url, "/");

	list($global_site_tags_1, $global_site_tags_2, $global_site_tags_3, $global_site_tags_4) = explode("/", $global_site_tags_url);

	$page_type = '';
	$page_subtype = '';
	$page = '';
	$post = '';

	if ( empty( $global_site_tags_1 ) ) {
		//landing
		$page_type = 'landing';
	} else {
		//tag
		$tag = $global_site_tags_1;
		$page_type = 'tag';
		$page = $global_site_tags_2;
		if ( empty( $page ) ) {
			$page = 1;
		}
		$tag = urldecode( $tag );
	}

	$global_site_tags['page_type'] = $page_type;
	$global_site_tags['page'] = $page;
	$global_site_tags['tag'] = $tag;

	return $global_site_tags;
}

function global_site_tags_tag_cloud($content,$number,$order_by = '',$low_font_size = 14,$high_font_size = 52,$class,$cloud_banned_tags = '', $global_site_tags_post_type = 'post') {
	global $wpdb, $current_site, $global_site_tags_base;

	$global_site_tags_banned_tags = get_site_option('global_site_tags_banned_tags', 'uncategorized');
	$global_site_tags_tag_cloud_order = get_site_option('global_site_tags_tag_cloud_order', 'count');

	//$global_site_tags_post_type = get_site_option('global_site_tags_post_type', 'post');

	$global_site_tags_banned_tags = str_replace(' , ', ',', $global_site_tags_banned_tags);
	$global_site_tags_banned_tags = str_replace(' ,', ',', $global_site_tags_banned_tags);
	$global_site_tags_banned_tags = str_replace(', ', ',', $global_site_tags_banned_tags);
	$global_site_tags_banned_tags .= ',';

	$global_site_tags_banned_tags_list = explode(',', $global_site_tags_banned_tags);

	if ( is_array( $cloud_banned_tags ) ) {
		$global_site_tags_banned_tags_list = array_merge($cloud_banned_tags, $global_site_tags_banned_tags_list);
	}

	if($global_site_tags_post_type == 'all') {
		$query = "SELECT count(*) as term_count, t.term_id FROM " . $wpdb->base_prefix . "site_terms as t INNER JOIN " . $wpdb->base_prefix . "site_term_relationships AS tr ON t.term_id = tr.term_id WHERE t.type = 'post_tag' GROUP BY t.term_id";
	} else {
		$query = "SELECT count(*) as term_count, t.term_id FROM " . $wpdb->base_prefix . "site_terms as t INNER JOIN " . $wpdb->base_prefix . "site_term_relationships AS tr ON t.term_id = tr.term_id INNER JOIN " . $wpdb->base_prefix . "site_posts AS sp ON sp.site_post_id = tr.site_post_id WHERE t.type = 'post_tag' AND sp.post_type = '" . $global_site_tags_post_type . "' GROUP BY t.term_id";
	}

	if ( empty($order_by) ) {
		$order_by = $global_site_tags_tag_cloud_order;
	}

	if ($order_by == 'count'){
		$query = $query . ' ORDER BY term_count DESC ';
	} else if ($order_by == 'most_recent'){
		$query = $query . ' ORDER BY term_count DESC ';
	}
	$query = $query . ' LIMIT ' . $number;
	$tags_array = $wpdb->get_results( $query, ARRAY_A );

	if (count($tags_array) > 0){
		//insert term names
		$tags_array_add = array();
		$loop_count = 0;
		foreach ($tags_array as $tag){
			$loop_count = $loop_count + 1;
			$tag_name = $wpdb->get_var("SELECT name FROM " . $wpdb->base_prefix . "site_terms WHERE term_id = '" . $tag['term_id'] . "'");
			$tag_nicename = $wpdb->get_var("SELECT slug FROM " . $wpdb->base_prefix . "site_terms WHERE term_id = '" . $tag['term_id'] . "'");
			$tags_array_add[$loop_count]['term_name'] = $tag_name;
			$tags_array_add[$loop_count]['term_nicename'] = $tag_nicename;
			//$tags_array_add[$loop_count]['term_count_updated'] = $tag['term_count_updated'];
			$tags_array_add[$loop_count]['term_count'] = $tag['term_count'];
			$tags_array_add[$loop_count]['term_id'] = $tag['term_id'];
		}
		$tags_array = $tags_array_add;

		//get min/max counts
		$term_min_count = 99999999999;
		$term_max_count = 0;
		foreach ($tags_array as $tag){
			$hide_tag = 'false';
			foreach ($global_site_tags_banned_tags_list as $blacklist_tag) {
				if (strtolower($tag['term_name']) == strtolower($blacklist_tag)){
					$hide_tag = 'true';
				}
			}
			if ($hide_tag != 'true'){
				if ($tag['term_count'] > $term_max_count){
					$term_max_count = $tag['term_count'];
				}
				if ($tag['term_count'] < $term_min_count){
					$term_min_count = $tag['term_count'];
				}
			}
		}

		$term_count = count($tags_array);
		//adjust term count
		foreach ($tags_array as $tag){
			foreach ($global_site_tags_banned_tags_list as $blacklist_tag) {
				if (strtolower($tag['term_name']) == strtolower($blacklist_tag)){
					$term_count = $term_count - 1;
				}
			}
		}
		//math fun... heh
		$font_difference = $high_font_size - $low_font_size;
		$term_difference = $term_max_count - $term_min_count;
		$term_difference = $term_difference + 1;
		if ($term_difference > 0){
			$font_unit = $font_difference / $term_difference;
		} else {
			$font_unit = $low_font_size;
		}

		//loop through and toss out the tag cloud
		$counter = 1;

		//print_r($tags_array);
		$content .= '<div>';
		foreach ($tags_array as $tag){
			$hide_tag = 'false';
			foreach ($global_site_tags_banned_tags_list as $blacklist_tag) {
				if (strtolower($tag['term_name']) == strtolower($blacklist_tag)){
					$hide_tag = 'true';
				}
			}
			if ($hide_tag != 'true'){
				//font size
				if ($tag['term_count'] == $term_max_count){
					$font_size = $high_font_size;
				} else if ($tag['term_count'] == $term_min_count){
					$font_size = $low_font_size;
				} else {
					$font_size = $tag['term_count'] * $font_unit;
					$font_size = $font_size + $low_font_size;
				}
				//output
				if ($class != ''){
					$content .= '<a class="' . $class . '" href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . $tag['term_nicename'] . '/" title="' . __('recent post(s)') . '" style="font-size: ' . $font_size . 'px;" id="cat-' . $tag['term_id'] . '">' . $tag['term_name'] . '</a>' . "\n";
				} else {
					$content .= '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . $tag['term_nicename'] . '/" title="' . __('recent post(s)', "globalsitetags") . '" style="float:left;padding-bottom:20px;padding-right:2px;text-decoration:none;font-size: ' . $font_size . 'px;" id="cat-' . $tag['term_id'] . '">' . $tag['term_name'] . '</a>' . "\n";
				}
				$counter = $counter + 1;
			}
		}
		$content .= '</div>';
	} else {
		$content .= '<p><center>' . __("There are no tags to display.", "globalsitetags") . '</center></p>';
	}
	return $content;
}

//------------------------------------------------------------------------//
//---Output Functions-----------------------------------------------------//
//------------------------------------------------------------------------//

function global_site_tags_title_output($title, $post_ID = '') {
	global $wpdb, $current_site, $post, $global_site_tags_base;
	if ( $post->post_name == $global_site_tags_base && $post_ID == $post->ID) {
		$global_site_tags = global_site_tags_url_parse();
		if ( $global_site_tags['page_type'] == 'landing' ) {
			$title = '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/">' . __('Tags') . '</a>';
		} else {
			$tag_name = $wpdb->get_var("SELECT name FROM " . $wpdb->base_prefix . "site_terms WHERE slug = '" . $global_site_tags['tag'] . "'");
			if ( $global_site_tags['page'] > 1 ) {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/">' . __('Tags') . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . $global_site_tags['tag'] . '/">' . $tag_name . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . $global_site_tags['tag'] .  '/' . $global_site_tags['page'] . '/">' . $global_site_tags['page'] . '</a>';
			} else {
				$title = '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/">' . __('Tags') . '</a> &raquo; ' . '<a href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . $global_site_tags['tag'] . '/">' . $tag_name . '</a>';
			}
		}
	}
	return $title;
}

function global_site_tags_output($content) {
	global $wpdb, $current_site, $post, $global_site_tags_base, $members_directory_base;
	if ( $post->post_name == $global_site_tags_base ) {
		$global_site_tags_shown = get_site_option('global_site_tags_shown', '50');
		$global_site_tags_per_page = get_site_option('global_site_tags_per_page', '10');
		$global_site_tags_background_color = get_site_option('global_site_tags_background_color', '#F2F2EA');
		$global_site_tags_alternate_background_color = get_site_option('global_site_tags_alternate_background_color', '#FFFFFF');
		$global_site_tags_border_color = get_site_option('global_site_tags_border_color', '#CFD0CB');
		$global_site_tags_banned_tags = get_site_option('global_site_tags_banned_tags', 'uncategorized');
		$global_site_tags_tag_cloud_order = get_site_option('global_site_tags_tag_cloud_order', 'count');

		$global_site_tags_post_type = get_site_option('global_site_tags_post_type', 'post');

		$global_site_tags = global_site_tags_url_parse();
		if ( $global_site_tags['page_type'] == 'landing' ) {
			//=====================================//
			$content = global_site_tags_tag_cloud($content, $global_site_tags_shown, $global_site_tags_tag_cloud_order, 14, 52, '' ,'', $global_site_tags_post_type);
			//=====================================//
		} else if ( $global_site_tags['page_type'] == 'tag' ) {
			//=====================================//
			$tag_name = $wpdb->get_var("SELECT name FROM " . $wpdb->base_prefix . "site_terms WHERE slug = '" . $global_site_tags['tag'] . "'");
			if ( empty( $tag_name ) ) {
				$tag_name = $wpdb->get_var("SELECT name FROM " . $wpdb->base_prefix . "site_terms WHERE slug = '" . urlencode($global_site_tags['tag']) . "'");
			}
			$tag_id = $wpdb->get_var("SELECT term_id FROM " . $wpdb->base_prefix . "site_terms WHERE slug = '" . $global_site_tags['tag'] . "'");
			if ( empty( $tag_id ) ) {
				$tag_id = $wpdb->get_var("SELECT term_id FROM " . $wpdb->base_prefix . "site_terms WHERE slug = '" . urlencode($global_site_tags['tag']) . "'");
			}
			if ($global_site_tags['page'] == 1){
				$start = 0;
			} else {
				$math = $global_site_tags['page'] - 1;
				$math = $global_site_tags_per_page * $math;
				$start = $math;
			}

			if($global_site_tags_post_type == 'all') {
				$query = "SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE post_terms LIKE '%|" . $tag_id . "|%' AND blog_public = 1 ORDER BY site_post_id DESC";
			} else {
				$query = "SELECT * FROM " . $wpdb->base_prefix . "site_posts WHERE post_terms LIKE '%|" . $tag_id . "|%' AND blog_public = 1 AND post_type = '" . $global_site_tags_post_type . "' ORDER BY site_post_id DESC";
			}
			$query .= " LIMIT " . intval( $start ) . ", " . intval( $global_site_tags_per_page );
			if ( !empty( $global_site_tags['tag'] ) ) {
				$posts = $wpdb->get_results( $query, ARRAY_A );
			}

			//=====================================//
			if ( count( $posts ) > 0 ) {
				if ( count( $posts ) < $global_site_tags_per_page ) {
					$next = 'no';
				} else {
					$next = 'yes';
				}
				$navigation_content = global_site_tags_navigation_output('', $global_site_tags_per_page, $global_site_tags['page'], $global_site_tags['tag'], $next);
			}
			if ( count( $posts ) > 0 ) {
				$content .= $navigation_content;
			}
			if ( count( $posts ) > 0 ) {
				$content .= '<div style="float:left; width:100%">';
				$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
					$content .= '<tr>';
						$content .= '<td style="background-color:' . $global_site_tags_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_tags_border_color . '; border-bottom-width:1px; font-size:12px;" width="10%"> </td>';
						$content .= '<td style="background-color:' . $global_site_tags_background_color . '; border-bottom-style:solid; border-bottom-color:' . $global_site_tags_border_color . '; border-bottom-width:1px; font-size:12px;" width="90%"><center><strong>' .  __('Posts', "globalsitetags") . '</strong></center></td>';
					$content .= '</tr>';
			}
				//=================================//
				$avatar_default = get_option('avatar_default');
				$tic_toc = 'toc';
				//=================================//
				if ( count( $posts ) > 0 ) {
					foreach ($posts as $post){
						//=============================//
						$post_author_display_name = $wpdb->get_var("SELECT display_name FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $post['post_author'] . "'");
						if ($tic_toc == 'toc'){
							$tic_toc = 'tic';
						} else {
							$tic_toc = 'toc';
						}
						if ($tic_toc == 'tic'){
							$bg_color = $global_site_tags_alternate_background_color;
						} else {
							$bg_color = $global_site_tags_background_color;
						}
						//=============================//
						$content .= '<tr>';
							$content .= '<td style="background-color:' . $bg_color . '; padding-top:10px;" valign="top" width="10%"><center><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . get_avatar($post['post_author'], 32, $avatar_default) . '</a></center></td>';
							$content .= '<td style="background-color:' . $bg_color . ';" width="90%">';
							if ( function_exists('members_directory_site_admin_options') ) {
								$post_author_nicename = $wpdb->get_var("SELECT user_nicename FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $post['post_author'] . "'");
								$content .= '<strong><a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $members_directory_base . '/' . $post_author_nicename . '/">' . $post_author_display_name . '</a> ' . __('Wrote', "globalsitetags") . ': </strong> ';
							} else {
								$content .= '<strong' . $post_author_display_name . ' ' . __('wrote') . ': </strong> ';
							}
							$content .= '<strong><a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . $post['post_title'] . '</a></strong><br />';
							$content .= mb_substr(strip_tags($post['post_content'],'<a>'),0, 250) . ' (<a style="text-decoration:none;" href="' . $post['post_permalink'] . '">' . __('More', "globalsitetags") . '</a>)';
							$content .= '</td>';
						$content .= '</tr>';
					}
				}
				//=================================//
			if ( count( $posts ) > 0 ) {
				$content .= '</table>';
				$content .= '</div>';
				$content .= $navigation_content;
			}
		} else {
			$content = __('Invalid page.', "globalsitetags");
		}
	}
	return $content;
}

function global_site_tags_navigation_output($content, $per_page, $page, $tag, $next){
	global $wpdb, $current_site, $global_site_tags_base;

	$global_site_tags_post_type = get_site_option('global_site_tags_post_type', 'post');

	$tag_id = $wpdb->get_var("SELECT term_id FROM " . $wpdb->base_prefix . "site_terms WHERE slug = '" . $tag . "'");


	if($global_site_tags_post_type == 'all') {
		$post_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_posts WHERE post_terms LIKE '%|" . $tag_id . "|%' AND blog_public = 1 ORDER BY site_post_id DESC");
	} else {
		$post_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->base_prefix . "site_posts WHERE post_terms LIKE '%|" . $tag_id . "|%' AND blog_public = 1 AND post_type = '" . $global_site_tags_post_type . "' ORDER BY site_post_id DESC");
	}

	//$post_count = $post_count - 1;

	//generate page div
	//============================================================================//
	$total_pages = global_site_tags_roundup($post_count / $per_page, 0);
	$content .= '<table border="0" border="0" cellpadding="2px" cellspacing="2px" width="100%" bgcolor="">';
	$content .= '<tr>';
	$showing_low = ($page * $per_page) - ($per_page - 1);
	if ($total_pages == $page){
		//last page...
		//$showing_high = $post_count - (($total_pages - 1) * $per_page);
		$showing_high = $post_count;
	} else {
		$showing_high = $page * $per_page;
	}

    $content .= '<td style="font-size:12px; text-align:left;" width="50%">';
	if ($post_count > $per_page){
	//============================================================================//
		if ($page == '' || $page == '1'){
			//$content .= __('Previous');
		} else {
		$previous_page = $page - 1;
		$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . urlencode( $tag ) . '/' . $previous_page . '/">&laquo; ' . __('Previous', "globalsitetags") . '</a>';
		}
	//============================================================================//
	}
	$content .= '</td>';
    $content .= '<td style="font-size:12px; text-align:right;" width="50%">';
	if ($post_count > $per_page){
	//============================================================================//
		if ( $next != 'no' ) {
			if ($page == $total_pages){
				//$content .= __('Next');
			} else {
				if ($total_pages == 1){
					//$content .= __('Next');
				} else {
					$next_page = $page + 1;
				$content .= '<a style="text-decoration:none;" href="http://' . $current_site->domain . $current_site->path . $global_site_tags_base . '/' . urlencode( $tag ) . '/' . $next_page . '/">' . __('Next', "globalsitetags") . ' &raquo;</a>';
				}
			}
		}
	//============================================================================//
	}
    $content .= '</td>';
	$content .= '</tr>';
    $content .= '</table>';
	return $content;
}

//------------------------------------------------------------------------//
//---Page Output Functions------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Support Functions----------------------------------------------------//
//------------------------------------------------------------------------//

function global_site_tags_roundup($value, $dp){
    return ceil($value*pow(10, $dp))/pow(10, $dp);
}

?>