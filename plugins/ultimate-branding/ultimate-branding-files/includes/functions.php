<?php

function set_ub_url($base) {

	global $UB_url;

	if(defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		$UB_url = trailingslashit(WPMU_PLUGIN_URL);
	} elseif(defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/ultimate-branding/' . basename($base))) {
		$UB_url = trailingslashit(WP_PLUGIN_URL . '/ultimate-branding');
	} else {
		$UB_url = trailingslashit(WP_PLUGIN_URL . '/ultimate-branding');
	}

}

function set_ub_dir($base) {

	global $UB_dir;

	if(defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		$UB_dir = trailingslashit(WPMU_PLUGIN_DIR);
	} elseif(defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/ultimate-branding/' . basename($base))) {
		$UB_dir = trailingslashit(WP_PLUGIN_DIR . '/ultimate-branding');
	} else {
		$UB_dir = trailingslashit(WP_PLUGIN_DIR . '/ultimate-branding');
	}


}

function ub_url($extended) {

	global $UB_url;

	return $UB_url . $extended;

}

function ub_dir($extended) {

	global $UB_dir;

	return $UB_dir . $extended;

}

function ub_files_url($extended) {
	return ub_url( 'ultimate-branding-files/' . $extended );
}

function ub_files_dir($extended) {
	return ub_dir( 'ultimate-branding-files/' . $extended );
}

// modules loading code

function ub_is_active_module( $module ) {

	$modules = get_ub_activated_modules();

	if(in_array( $module, array_keys($modules) )) {
		return true;
	} else {
		return false;
	}

}

function ub_get_option( $option, $default = false ) {
	if(is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ultimate-branding/ultimate-branding.php')) {
		return get_site_option( $option, $default);
	} else {
		return get_option( $option, $default);
	}
}

function ub_update_option( $option, $value = null ) {
	if(is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ultimate-branding/ultimate-branding.php')) {
		return update_site_option( $option, $value);
	} else {
		return update_option( $option, $value);
	}
}

function ub_delete_option( $option ) {
	if(is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('ultimate-branding/ultimate-branding.php')) {
		return delete_site_option( $option );
	} else {
		return delete_option( $option );
	}
}

function get_ub_activated_modules() {
	return ub_get_option('ultimatebranding_activated_modules', array());
}

function update_ub_activated_modules( $data ) {
	ub_update_option('ultimatebranding_activated_modules', $data);
}

function ub_load_single_module( $module ) {

	$modules = get_ub_modules();

	if(in_array($module, $modules)) {
		include_once( ub_files_dir('modules/' . $module) );
	}

}

function get_ub_modules() {
	if ( is_dir( ub_files_dir('modules') ) ) {
		if ( $dh = opendir( ub_files_dir('modules') ) ) {
			$mub_modules = array();
			while ( ( $module = readdir( $dh ) ) !== false )
				if ( substr( $module, -4 ) == '.php' )
					$ub_modules[] = $module;
			closedir( $dh );
			sort( $ub_modules );

			return apply_filters('ultimatebranding_available_modules', $ub_modules);
		}
	}

	return false;

}

function load_ub_modules() {

	$modules = get_ub_activated_modules();

	if ( is_dir( ub_files_dir('modules') ) ) {
		if ( $dh = opendir( ub_files_dir('modules') ) ) {
			$ub_modules = array();
			while ( ( $module = readdir( $dh ) ) !== false )
				if ( substr( $module, -4 ) == '.php' )
					$ub_modules[] = $module;
			closedir( $dh );
			sort( $ub_modules );

			$ub_modules = apply_filters('ultimatebranding_available_modules', $ub_modules);

			foreach( $ub_modules as $ub_module ) {
				if(in_array($ub_module, $modules)) {
					include_once( ub_files_dir('modules/' . $ub_module) );
				}
			}
		}
	}

	do_action( 'ultimatebranding_modules_loaded' );
}

function load_all_ub_modules() {
	if ( is_dir( ub_files_dir('modules') ) ) {
		if ( $dh = opendir( ub_files_dir('modules') ) ) {
			$ub_modules = array();
			while ( ( $module = readdir( $dh ) ) !== false )
				if ( substr( $module, -4 ) == '.php' )
					$ub_modules[] = $module;
			closedir( $dh );
			sort( $ub_modules );

			$ub_modules = apply_filters('ultimatebranding_available_modules', $ub_modules);

			foreach( $ub_modules as $ub_module )
				include_once( ub_files_dir('modules/' . $ub_module) );
		}
	}

	do_action( 'ultimatebranding_modules_loaded' );
}

function ub_has_menu( $menuhook ) {
	global $submenu;

	$menu = (isset($submenu['branding'])) ? $submenu['branding'] : false;

	if(is_array($menu)) {
		foreach($menu as $key => $m) {
			if($m[2] == $menuhook) {
				return true;
			}
		}
	}

	// if we are still here then we didn't find anything
	return false;
}

/*
Function based on the function wp_upload_dir, which we can't use here because it insists on creating a directory at the end.
*/
function ub_wp_upload_url() {
	global $switched;

	$siteurl = get_option( 'siteurl' );
	$upload_path = get_option( 'upload_path' );
	$upload_path = trim($upload_path);

	$main_override = is_multisite() && defined( 'MULTISITE' ) && is_main_site();

	if ( empty($upload_path) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;
		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos($dir, ABSPATH) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}

	if ( !$url = get_option( 'upload_url_path' ) ) {
		if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined('UPLOADS') && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( is_multisite() && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		if ( defined( 'BLOGUPLOADDIR' ) )
			$dir = untrailingslashit(BLOGUPLOADDIR);
		$url = str_replace( UPLOADS, 'files', $url );
	}

	$bdir = $dir;
	$burl = $url;

	return $burl;
}

function ub_wp_upload_dir() {
	global $switched;

	$siteurl = get_option( 'siteurl' );
	$upload_path = get_option( 'upload_path' );
	$upload_path = trim($upload_path);

	$main_override = is_multisite() && defined( 'MULTISITE' ) && is_main_site();

	if ( empty($upload_path) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;
		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos($dir, ABSPATH) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}

	if ( !$url = get_option( 'upload_url_path' ) ) {
		if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined('UPLOADS') && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( is_multisite() && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		if ( defined( 'BLOGUPLOADDIR' ) )
			$dir = untrailingslashit(BLOGUPLOADDIR);
		$url = str_replace( UPLOADS, 'files', $url );
	}

	$bdir = $dir;
	$burl = $url;

	return $bdir;
}

?>