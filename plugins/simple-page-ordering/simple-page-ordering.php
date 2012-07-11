<?php
/**
 Plugin Name: Simple Page Ordering
 Plugin URI: http://www.get10up.com/plugins/simple-page-ordering-wordpress/
 Description: Order your pages and hierarchical post types using drag and drop on the built in page list. Also adds a filter for items to show per page. For further instructions, open the "Help" tab on the Pages screen. 
 Version: 1.0
 Author: Jake Goldman (10up)
 Author URI: http://www.get10up.com

    Plugin: Copyright 2011 10up  (email : jake@get10up.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class simple_page_ordering {
	
	function simple_page_ordering() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'load-edit.php', array( $this, 'load_edit_screen' ) );
		add_action( 'wp_ajax_simple_page_ordering', array( $this, 'ajax_simple_page_ordering' ) );
	}
	
	function admin_init() {
		load_plugin_textdomain( 'simple-page-ordering', false, dirname( plugin_basename( __FILE__ ) ) . '/localization/' );
	}
	
	function load_edit_screen() { 
		add_action( 'wp', array( $this, 'wp_edit' ) );
	}
	
	function wp_edit() {
		global $post_type, $wp_query;
		
		if ( ! current_user_can('edit_others_pages') || ( ! post_type_supports( $post_type, 'page-attributes' ) && ! is_post_type_hierarchical( $post_type ) ) )		// check permission
			return;
		
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );					// posts per page drop down UI
		
		if ( isset( $_GET['per_page'] ) && ( $_GET['per_page'] == 99999 || ( $_GET['per_page']%10 ) == 0 ) )
			update_user_option( get_current_user_id(), 'edit_' . $post_type . '_per_page', (int) $_GET['per_page'] );
		
		add_filter( 'views_' . get_current_screen()->id, array( $this, 'sort_by_order_link' )  );		// add view by menu order to views
		add_filter( 'contextual_help', array( $this, 'contextual_help' ) );								// add contextual help to hierarchical post screens
		
	 	if ( $wp_query->query['orderby'] == 'menu_order title' ) {	// we can only sort if we're organized by menu order; WP 3.2 and 3.1 versions
		
			wp_enqueue_script( 'simple-page-ordering', plugin_dir_url( __FILE__ ) . 'simple-page-ordering.js', array('jquery-ui-sortable'), '0.9.7', true );
			$js_trans = array(
					'RepositionTree' => __("Items can only be repositioned within their current branch in the page tree / hierarchy (next to pages with the same parent).\n\nIf you want to move this item into a different part of the page tree, use the Quick Edit feature to change the parent before continuing.", 'simple-page-ordering')
		    	);
			wp_localize_script( 'simple-page-ordering', 'simple_page_ordering_l10n', $js_trans );
			
		}
	}
	
	function restrict_manage_posts()
	{
		global $per_page;
				
		$per_page = isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : $per_page;
	?>
		<select name="per_page" style="width: 110px;">
			<option <?php selected( $per_page, 99999 ); ?> value="99999"><?php _e( 'Show all', 'simple-page-ordering' ); ?></option>
			<?php for( $i=10;$i<=100;$i+=10 ) : ?>
			<option <?php selected( $per_page, $i ); ?> value="<?php echo $i; ?>"><?php echo $i; ?> <?php _e( 'per page', 'simple-page-ordering' ); ?></option>
			<?php endfor; ?>
			<?php if ( $per_page != -1 && $per_page != 99999 && ( $per_page%10 != 0 || $per_page > 100 ) ) : ?>
		 	<option <?php selected( true ); ?> value="<?php echo (int) $per_page; ?>"><?php echo (int) $per_page; ?> <?php _e( 'per page', 'simple-page-ordering' ); ?></option>
		 	<?php endif; ?>
		</select>
	<?php
	}
	
	function contextual_help( $help )
	{
		return $help . '
			<p><strong>'. __( 'Simple Page Ordering', 'simple_page_ordering' ) . '</strong></p>
			<p><a href="http://www.get10up.com/plugins/simple-page-ordering-wordpress/" target="_blank">' . __( 'Simple Page Ordering', 'simple_page_ordering' ) . '</a> ' . __( 'is a plug-in by', 'simple-page-ordering' ) . ' <a href="http://www.get10up.com" target="_blank">Jake Goldman (10up)</a> ' . __( 'that  allows you to order pages and other hierarchical post types with drag and drop.', 'simple-page-ordering' ) . '</p>
			<p>' . __( 'To reposition an item, simply drag and drop the row by "clicking and holding" it anywhere (outside of the links and form controls) and moving it to its new position.', 'simple-page-ordering' ) . '</p>
			<p>' . __( 'If you have a large number of pages, it may be helpful to adjust the new "items per page" filter located above the table and before the filter button.', 'simple-page-ordering' ) . '</p>
			<p>' . __( 'To keep things relatively simple, the current version only allows you to reposition items within their current tree / hierarchy (next to pages with the same parent). If you want to move an item into or out of a different part of the page tree, use the "quick edit" feature to change the parent.', 'simple-page-ordering' ) . '</p>  
		';
	}
	
	function ajax_simple_page_ordering() {
		// check permissions again and make sure we have what we need
		if ( ! current_user_can('edit_others_pages') || empty( $_POST['id'] ) || ( ! isset( $_POST['previd'] ) && ! isset( $_POST['nextid'] ) ) )
			die(-1);
		
		// real post?
		if ( ! $post = get_post( $_POST['id'] ) )
			die(-1);
		
		$previd = isset( $_POST['previd'] ) ? $_POST['previd'] : false;
		$nextid = isset( $_POST['nextid'] ) ? $_POST['nextid'] : false;
		$new_pos = array(); // store new positions for ajax
		
		$siblings = get_posts(array( 
			'depth' => 1, 
			'numberposts' => -1, 
			'post_type' => $post->post_type, 
			'post_status' => 'publish,pending,draft,future,private', 
			'post_parent' => $post->post_parent, 
			'orderby' => 'menu_order title', 
			'order' => 'ASC', 
			'exclude' => $post->ID 
		)); // fetch all the siblings (relative ordering)
		
		$menu_order = 0;
			
		foreach( $siblings as $sibling ) :
		
			// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
			if ( $nextid == $sibling->ID ) {
				wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order ));
				$new_pos[$post->ID] = $menu_order;
				$menu_order++;
			}
			
			// if repositioned post has been set, and new items are already in the right order, we can stop
			if ( isset( $new_pos[$post->ID] ) && $sibling->menu_order >= $menu_order )
				break;
			
			// set the menu order of the current sibling and increment the menu order
			wp_update_post(array( 'ID' => $sibling->ID, 'menu_order' => $menu_order ));
			$new_pos[$sibling->ID] = $menu_order;
			$menu_order++;
			
			if ( ! $nextid && $previd == $sibling->ID ) {
				wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order ));
				$new_pos[$post->ID] = $menu_order;
				$menu_order++;
			}
			
		endforeach;
		
		// if the moved post has children, we need to refresh the page
		$children = get_posts(array( 'depth' => 1, 'numberposts' => 1, 'post_type' => $post->post_type, 'post_status' => 'publish,pending,draft,future,private', 'post_parent' => $post->ID ));
		if ( ! empty( $children ) )
			die('children');
		
		die( json_encode($new_pos) );
	}
	
	function sort_by_order_link( $views ) {
		global $post_type, $wp_query;
		$class = ( $wp_query->query['orderby'] == 'menu_order title' ) ? 'current' : '';
		$query_string = remove_query_arg(array( 'orderby', 'order' ));
		$query_string = add_query_arg( 'orderby', urlencode('menu_order title'), $query_string );
		$views['byorder'] = '<a href="'. $query_string . '" class="' . $class . '">Sort by Order</a>';
		return $views;
	}
}

if ( is_admin() )
	$simple_page_ordering = new simple_page_ordering;