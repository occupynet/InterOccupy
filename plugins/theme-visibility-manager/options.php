<?php

// stop everything if this user is trying to do something he or she is not supposed to
if ( ! current_user_can('manage_network_options') ) {
	wp_die(__('You do not have sufficient permissions to access this page.'));
}

if( isset( $_REQUEST['theme_path'] ) && is_array( $_REQUEST['theme_path'] ) ) {
	global $wpdb, $blog_id;
	
	if ( isset(  $_REQUEST['show_current'] ) || isset( $_REQUEST['hide_current'] ) ) {
		$blog_list = array( array( 'blog_id' => $blog_id ) );		
	} else {		
		$query = "SELECT * FROM {$wpdb->blogs} ORDER BY {$wpdb->blogs}.blog_id";
		$blog_list = $wpdb->get_results( $query, ARRAY_A );
	}	
	$all_theme_paths = $_REQUEST['theme_path'];
	
	foreach ( $blog_list as $blog ) {
		$cur_blog_id = $blog['blog_id'];
		
		$currently_visible_themes_for_blog = get_blog_option($cur_blog_id, 'visible_themes_array');
		$currently_visible_themes_for_blog = ( !is_array( $currently_visible_themes_for_blog ) ) ? array() : $currently_visible_themes_for_blog;
		$currently_visible_themes_for_blog_MODIFIED = $currently_visible_themes_for_blog;
	
		foreach ( $all_theme_paths as $theme_path ) {
			if( isset( $_REQUEST['hide_globally'] ) || isset( $_REQUEST['hide_current'] ) ) {						
				if( in_array($theme_path, $currently_visible_themes_for_blog) ) {	
					foreach($currently_visible_themes_for_blog as $key => $item) {
						if(	$item == $theme_path) {
							unset($currently_visible_themes_for_blog_MODIFIED[$key]);
						}
					}
					update_blog_option ($cur_blog_id, 'visible_themes_array', $currently_visible_themes_for_blog_MODIFIED);
				}
				$act = ( isset( $_REQUEST['hide_globally'] ) ) ? 'invisible' : 'invisible in current site';
			} else if( isset( $_REQUEST['show_globally'] ) || isset( $_REQUEST['show_current'] ) ) {
				if( !in_array($theme_path, $currently_visible_themes_for_blog_MODIFIED) ) {
					$currently_visible_themes_for_blog_MODIFIED[] = $theme_path;
					update_blog_option ( $cur_blog_id, 'visible_themes_array', $currently_visible_themes_for_blog_MODIFIED );
				} 
				$act = ( isset( $_REQUEST['show_globally'] ) ) ? 'visible' : 'visible in current site';
			} 
		}

	}
	$theme_count = count( $all_theme_paths );
	switch ( $theme_count ) {
		case 0:
			$message = 'No themes selected';
			break;
		case 1:
			$message = '1 theme made ' . $act;
			break;
		default:
			$message = $theme_count . ' themes made ' . $act;
	}
	?>
	<div id="message" class="updated"><p><?php _e( $message ); ?></p></div>
	<?php

}

$themes = get_themes();
//ksort( $themes );

//echo "<pre>"; print_r( $themes ); echo "</pre>";
$allowed_themes = get_site_allowed_themes();
?>
<div class="wrap">
		<h2><?php _e( 'Global theme visibility management' ) ?></h2>
		<p><?php _e( 'Themes must be enabled for your network before they will be available to individual sites, whether "shown" through this plugin or not. Go <a href="ms-themes.php">here</a> to network-enable themes.' ) ?></p>
		<form id="theme-vis-form" action="">
        <input type="hidden" name="page" value="themesvisibilitymanager-settings-handle" />
        <table class="widefat">
			<thead>
				<tr>
                	<th><input class="theme-vis-master-check" type="checkbox" /></th>
					<th style="width:20%;"><?php _e( 'Theme' ) ?></th>
					<th style="width:8%;"><?php _e( 'Version' ) ?></th>
					<th style="width:42%;"><?php _e( 'Description' ) ?></th>
                    <th style="width:12%;"><?php _e( 'Network-enabled?' ) ?></th>
                    <th style="width:9%;"><?php _e( 'Show' ) ?></th>
                    <th style="width:9%;"><?php _e( 'Hide' ) ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
                	<th><input class="theme-vis-master-check" type="checkbox" /></th>
					<th style="width:20%;"><?php _e( 'Theme' ) ?></th>
					<th style="width:8%;"><?php _e( 'Version' ) ?></th>
					<th style="width:42%;"><?php _e( 'Description' ) ?></th>
                    <th style="width:12%;"><?php _e( 'Network-enabled?' ) ?></th>
                    <th style="width:9%;"><?php _e( 'Show' ) ?></th>
                    <th style="width:9%;"><?php _e( 'Hide' ) ?></th>
				</tr>
			</tfoot>
			<tbody id="plugins">
			<?php
			$total_theme_count = $activated_themes_count = 0;
			$class = '';
			foreach ( (array) $themes as $key => $theme ) {
				$total_theme_count++;
				$theme_key = esc_html( $theme['Stylesheet'] );
				$class = ( 'alt' == $class ) ? '' : 'alt';
				$class1 = $enabled = $disabled = '';
				$enabled = $disabled = false;

				if ( isset( $allowed_themes[$theme_key] ) == true ) {
					$enabled = true;
					$activated_themes_count++;
					$class1 = 'active';
				} else {
					$disabled = true;
				}
				?>
				<tr valign="top" class="<?php echo $class . ' ' . $class1; ?>">
					
					<th><input class="theme-vis-check" type="checkbox" name="theme_path[]" value="<?php echo $theme_key?>" /></th>
                    <th scope="row" style="text-align:left;"><?php echo $key ?></th>
					<td><?php echo $theme['Version'] ?></td>
					<td><?php echo $theme['Description'] ?></td>
                    <td><?php echo ($enabled ? 'Yes' : 'No'); ?></td>
                    <td>
						<a href='?page=themesvisibilitymanager-settings-handle&show_globally=yes&theme_path[]=<?php echo $theme_key; ?>'>Globally Show</a>
					</td>
                    <td>
						<a href='?page=themesvisibilitymanager-settings-handle&hide_globally=yes&theme_path[]=<?php echo $theme_key; ?>'>Globally Hide</a>
					</td>
                    
				</tr>
			<?php } ?>
			</tbody>
		</table>
        <h3>WTF</h3>
        <input type="submit" name="show_globally" value="<?php _e( 'Show Globally' ); ?>" />
        <input type="submit" name="hide_globally" value="<?php _e( 'Hide Globally' ); ?>" />
        &nbsp; | &nbsp;
        <input type="submit" name="show_current" value="<?php _e( 'Show in Current Site' ); ?>" />
        <input type="submit" name="hide_current" value="<?php _e( 'Hide in Current Site' ); ?>" />
        </form>
</div>
