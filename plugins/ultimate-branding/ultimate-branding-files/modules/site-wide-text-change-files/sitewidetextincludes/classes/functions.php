<?php

/**
 * Plugin directory URL
 **/
function set_swt_url( $base ) {
	global $swt_url;

	if( defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( $base ) ) ) {
		$swt_url = trailingslashit( WPMU_PLUGIN_URL );
	} elseif( defined( 'WP_PLUGIN_URL' ) && file_exists( WP_PLUGIN_DIR . '/site-wide-text-change/' . basename( $base ) ) ) {
		$swt_url = trailingslashit( WP_PLUGIN_URL . '/site-wide-text-change' );
	} else {
		$swt_url = trailingslashit( WP_PLUGIN_URL . '/site-wide-text-change' );
	}
}

/**
 * Plugin directory
 **/
function set_swt_dir( $base ) {
	global $swt_dir;

	if( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( $base ) ) ) {
		$swt_dir = trailingslashit( WPMU_PLUGIN_DIR );
	} elseif( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/site-wide-text-change/' . basename( $base ) ) ) {
		$swt_dir = trailingslashit( WP_PLUGIN_DIR . '/site-wide-text-change' );
	} else {
		$swt_dir = trailingslashit( WP_PLUGIN_DIR . '/site-wide-text-change' );
	}
}

/**
 * URL to a file/dir in the plugin directory
 **/
function swt_url( $extended = '' ) {
	global $swt_url;
	return $swt_url . $extended;
}

/**
 * Path to a file/dir in the plugin directory
 **/
function swt_dir( $extended = '' ) {
	global $swt_dir;
	return $swt_dir . $extended;
}
