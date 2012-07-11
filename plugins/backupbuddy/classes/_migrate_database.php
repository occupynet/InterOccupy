<?php

// TODO: Convert this to use proper OOP practices... Sorry it evolved into this.

/**
 *	_migrate_database.php
 *
 *	Handles all SQL data migration for both importbuddy and multisite importing.
 *	Handles updating paths, URLs, etc.
 *	
 *	Version: 1.0.1
 *	Author: Dustin Bolton
 *	Author URI: http://dustinbolton.com/
 *
 *	REQUIREMENTS:
 *
 *	1) Set up the variable $destination_type to the destination type if non-standalone (default) or Multisite Network (auto-detected). Valid values: standalone, multisite_import, multisite_network
 *	2) Mysql should already be connected.
 *	3) pb_backupbuddy::$options['dat_file'] should be initialized with the DAT file array.
 *	4) If migrating a network -> network then set up the variable $multisite_network_db_prefix to be the database prefix of the network. Needed to access users tables.
 *	5) If multisite_import is DESTINATION then $wp_upload_dir (upload_path option), $wp_upload_url (fileupload_url option) must be set.
 *
 *	USED BY:
 *
 *	1) ImportBuddy
 *	2) Multisite Import / Restore
 *	3) Future: RepairBuddy?
 *
 */



// NOTE: dbreplace class intelligently ignores replacing values with identical values for performance.



if ( isset( $destination_type ) && ( $destination_type == 'multisite_import' ) ) {
} else { // Normal importbuddy.
	$destination_siteurl = pb_backupbuddy::$options['siteurl'];
	$destination_home = pb_backupbuddy::$options['home'];
	if ( $destination_home == '' ) { // If no home then we set it equal to site URL.
		$destination_home = $destination_siteurl;
	}
	$destination_db_prefix = pb_backupbuddy::$options['db_prefix'];
	$multisite_network_db_prefix = $destination_db_prefix; // non-ms prefix set network same as normal prefix.
	if ( isset( pb_backupbuddy::$options['domain'] ) ) { $destination_domain = pb_backupbuddy::$options['domain']; }
	
	// Currently we don't allow changing the Network path.
	if ( isset( pb_backupbuddy::$options['dat_file'][ 'path' ] ) ) {
		$destination_path = pb_backupbuddy::$options['dat_file'][ 'path' ]; // Set new Network path to equal old network path.
	}
}



pb_backupbuddy::status( 'message', 'Migrating database content...' );
pb_backupbuddy::status( 'details', 'Destination site table prefix: ' . $destination_db_prefix );




// ********** BEGIN VARIABLE SETUP **********
	// DESTINATION TYPE. Valid values: standalone, multisite_import, multisite_network
	if ( !isset( $destination_type ) || ( $destination_type == '' ) ) {
		$destination_type = 'standalone'; // Default; overridden if explicitly set or if a full Multisite Network migration.
	}
	// SOURCE TYPE. Valid values: network, multisite_export (single site exported from multisite network), standalone
	if ( isset( pb_backupbuddy::$options['dat_file'][ 'is_multisite' ] ) && ( ( pb_backupbuddy::$options['dat_file'][ 'is_multisite' ] === true ) || ( pb_backupbuddy::$options['dat_file'][ 'is_multisite' ] === 'true' ) ) ) {
		$source_type = 'multisite_network';
		$destination_type = 'multisite_network';
	} elseif ( isset( pb_backupbuddy::$options['dat_file'][ 'is_multisite_export' ] ) && ( ( pb_backupbuddy::$options['dat_file'][ 'is_multisite_export' ] === true ) || ( pb_backupbuddy::$options['dat_file'][ 'is_multisite_export' ] === 'true' ) ) ) {
		$source_type = 'multisite_export';
	} else {
		$source_type = 'standalone';
	}
	
	pb_backupbuddy::status( 'details', 'Migration type: ' . $source_type . ' to ' . $destination_type );
	
	$destination_siteurl = preg_replace( '|/*$|', '', $destination_siteurl );  // strips trailing slash(es).
	$destination_home = preg_replace( '|/*$|', '', $destination_home );  // strips trailing slash(es).
	pb_backupbuddy::status( 'details', 'Destination Site URL: ' . $destination_siteurl );
	pb_backupbuddy::status( 'details', 'Destination Home URL: ' . $destination_home );
	
	$count_tables_checked = 0;
	$count_items_checked = 0;
	$count_items_changed = 0;
// ********** END VARIABLE SETUP **********



// ********** BEGIN ALL **********
	
	pb_backupbuddy::status( 'details', 'Starting migration steps for `all` sites.' );
	
	// ABSPATH
	$old_abspath = pb_backupbuddy::$options['dat_file']['abspath'];
	//$old_abspath = preg_replace( '|/+$|', '', $old_abspath ); // Remove escaping of windows paths. This is wrong. strips trailing slashes. Why? It shouldnt! Removed Nov 4
	$new_abspath = ABSPATH;
	pb_backupbuddy::status( 'details', 'ABSPATH change for database. Old Path: ' . $old_abspath . ', New Path: ' . $new_abspath . '.' );
	
	
	
	$old_url = pb_backupbuddy::$options['dat_file']['siteurl'];  // the value you want to search for	
	
	// SITEURL
	if ( stristr( $old_url, 'http://www.' ) || stristr( $old_url, 'https://www.' ) ) { // If http://www.blah.... then also we will replace http://blah... and vice versa.
		$old_url_alt = str_ireplace( 'http://www.', 'http://', $old_url );
		$old_url_alt = str_ireplace( 'https://www.', 'https://', $old_url_alt );
	} else {
		$old_url_alt = str_ireplace( 'http://', 'http://www.', $old_url );
		$old_url_alt = str_ireplace( 'https://', 'https://www.', $old_url_alt );
	}
	$new_url = $destination_siteurl;
	pb_backupbuddy::status( 'details', 'Calculated site URL update. Previous URL: `' . $old_url . '`, New URL: `' . $new_url . '`.' );
	$old_fullreplace = array( $old_url, $old_url_alt, $old_abspath );
	$new_fullreplace = array( $new_url, $new_url, $new_abspath );
	
	// HOMEURL.
	if ( $destination_home != $destination_siteurl ) {
		
		if ( empty( pb_backupbuddy::$options['dat_file']['homeurl'] ) ) { // old BackupBuddy versions did not store the previous homeurl. Hang onto this for backwards compatibility for a while.
			pb_backupbuddy::status( 'error', 'Your current backup does not include a home URL. Home URLs will NOT be updated; site URL will be updated though.  Make a new backup with the latest BackupBuddy before migrating if you wish to fully update home URL configuration.' );
		} else {
			$old_urls = array( $old_url, $old_url_alt, pb_backupbuddy::$options['dat_file']['homeurl'] );
			$new_urls = array( $new_url, $new_url, $destination_home );
			
			$old_fullreplace[] = pb_backupbuddy::$options['dat_file']['homeurl'];
			$new_fullreplace[] = $destination_home;
			
			pb_backupbuddy::status( 'details', 'Calculated home URL update. Previous URL: `' . pb_backupbuddy::$options['dat_file']['homeurl'] . '`, New URL: `' . $destination_home . '`' );
		}
	} else { // Site URL updates only.
		$old_urls = array( $old_url, $old_url_alt );
		$new_urls = array( $new_url, $new_url );
	}
	
	if ( isset( $wp_upload_dir ) ) {
		$wp_upload_url_real = $new_url . '/' . str_replace( ABSPATH, '', $wp_upload_dir );
	}
	
	
	$bruteforce_excluded_tables = array(
		$destination_db_prefix . 'posts',
		$destination_db_prefix . 'users', // Imported users table will temporarily be here so this is fine for MS imports.
		$destination_db_prefix . 'usermeta', // Imported users table will temporarily be here so this is fine for MS imports.
		$destination_db_prefix . 'terms',
		$destination_db_prefix . 'term_taxonomy',
		$destination_db_prefix . 'term_relationships',
		$destination_db_prefix . 'postmeta',
		$destination_db_prefix . 'options',
		$destination_db_prefix . 'comments',
		$destination_db_prefix . 'commentmeta',
		$destination_db_prefix . 'links',
	);
	
	pb_backupbuddy::status( 'details', 'Finished migration steps for `all` sites.' );
	
// ********** END ALL **********



// ********** BEGIN MULTISITE NETWORK -> MULTISITE NETWORK **********
if ( ( $source_type == 'multisite_network' ) && ( $destination_type == 'multisite_network' ) ) {
	
	pb_backupbuddy::status( 'details', 'Starting migration steps for `Network -> Network` sites.' );
	
	//pb_update_domain_path( pb_backupbuddy::$options['dat_file']['domain'], $destination_domain, pb_backupbuddy::$options['dat_file']['path'], $destination_path ); // $old_domain, $new_domain, $old_path, $new_path
	$old_domain = pb_backupbuddy::$options['dat_file']['domain'];
	$new_domain = $destination_domain;
	$old_path = pb_backupbuddy::$options['dat_file']['path'];
	$new_path = $destination_path;
	
	// Update blog path ONLY in BLOGS table where the domain AND path match. (gets all rows with this path for THIS domain).
	mysql_query( "UPDATE `" . $destination_db_prefix . "blogs` SET domain='" . mysql_real_escape_string( $new_domain ) . "', path='" . mysql_real_escape_string( $new_path ) . "' WHERE domain='" . mysql_real_escape_string( $old_domain ) . "' AND path='" . mysql_real_escape_string( $old_path ) . "'" );
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating blog URL in blogs table to `' . mysql_real_escape_string( $new_domain ) . '`.' );
	// Update blog domain ONLY in BLOGS table where the domain matches. (gets all rows with this domain).
	mysql_query( "UPDATE `" . $destination_db_prefix . "blogs` SET domain='" . mysql_real_escape_string( $new_domain ) . "' WHERE domain='" . mysql_real_escape_string( $old_domain ) . "'" );
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating blog URL in blogs table to `' . mysql_real_escape_string( $new_domain ) . '`.' );
	// Update site domain & path in SITES table where domain and path match. LIMITED TO 1.
	mysql_query( "UPDATE `" . $destination_db_prefix . "site` SET domain='" . mysql_real_escape_string( $new_domain ) . "', path='" . mysql_real_escape_string( $new_path ) . "' WHERE domain='" . mysql_real_escape_string( $old_domain ) . "' AND path='" . mysql_real_escape_string( $old_path ) . "' LIMIT 1" );
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating site URL in site table `' . $destination_db_prefix . 'site` to `' . mysql_real_escape_string( $new_domain ) . '`.' );
	
	pb_backupbuddy::status( 'details', 'Finished migration steps for `Network -> Network` sites.' );
	
}
// ********** END MULTISITE NETWORK -> MULTISITE NETWORK **********



// ********** BEGIN STANDALONE -> MULTISITE IMPORT  **********
if ( ( $source_type == 'standalone' ) && ( $destination_type == 'multisite_import' ) ) {
	
	pb_backupbuddy::status( 'details', 'Starting migration steps for `Standalone -> Multisite Import` sites.' );
	
	// Note for any destination of multisite_import: Users tables exist temporarily in their normal location so we replace them like a normal standalone site. The next import step will merge them into the multisite tables.
	
	// TODO: add code from ms_importbuddy.php into here for any updates if needed.
	
	// The old uploads URL. Standalone source like: http://getbackupbuddy.com/wp-content/uploads/. BB doesnt currently support moved uploads. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'Old uploads URL: ' . $old_url . '/wp-content/uploads' );
	array_unshift( $old_urls, $old_url . '/wp-content/uploads' );
	array_unshift( $old_fullreplace, $old_url . '/wp-content/uploads' );
	
	// The new standalone upload URL. Ex: http://pluginbuddy.com/wp-content/uploads/. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'New virtual upload URL to replace standalone upload URL: ' . $wp_upload_url );
	array_unshift( $new_urls, $wp_upload_url );
	array_unshift( $new_fullreplace, $wp_upload_url );
	
	// Update upload_path in options table.
	mysql_query( "UPDATE `" . $destination_db_prefix . "options` SET option_value='" . mysql_real_escape_string( str_replace( $new_url, '', $wp_upload_url_real ) ) . "' WHERE option_name='upload_path' LIMIT 1" );
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating uploads URL in options table. New value: ' . str_replace( $new_url, '', $wp_upload_url_real ) );
	
	// Update user roles option_name row.
	mysql_query( "UPDATE `" . $destination_db_prefix . "options` SET option_name='" . $destination_db_prefix . "user_roles' WHERE option_name LIKE '%\_user\_roles' LIMIT 1" );
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating user roles option_name to `' . $destination_db_prefix . 'user_roles`.' );
	
	// Update user level meta_key in user_meta table.
	// TODO: moved to bottom of this file.
	//mysql_query( "UPDATE `" . $destination_db_prefix . "options` SET option_name='" . $destination_db_prefix . "user_roles' WHERE option_name LIKE '%_user_roles' LIMIT 1" );
	//pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating user roles option_name to `' . $destination_db_prefix . 'user_roles`.' );
	
	pb_backupbuddy::status( 'details', 'Finished migration steps for `Standalone -> Multisite Import` sites.' );
	
}
// ********** END STANDALONE -> MULTISITE IMPORT **********



// ********** BEGIN MULTISITE EXPORT -> MULTISITE IMPORT **********
if ( ( $source_type == 'multisite_export' ) && ( $destination_type == 'multisite_import' ) ) {
	
	pb_backupbuddy::status( 'details', 'Starting migration steps for `Multisite Export -> Multisite Import` sites.' );
	
	// Note for any destination of multisite_import: Users tables exist temporarily in their normal location so we replace them like a normal standalone site. The next import step will merge them into the multisite tables.
	
	// The old virtual uploads URL. Standalone source like: http://getbackupbuddy.com/wp-content/uploads/. BB doesnt currently support moved uploads. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'Old virtual uploads URL: ' . pb_backupbuddy::$options['dat_file']['upload_url'] );
	array_unshift( $old_urls, pb_backupbuddy::$options['dat_file']['upload_url'] );
	array_unshift( $old_fullreplace, pb_backupbuddy::$options['dat_file']['upload_url'] );
	
	// The new virtual upload URL. Ex: http://pluginbuddy.com/wp-content/uploads/. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'New virtual upload URL to replace old virtual uploads URL: ' . $wp_upload_url );
	array_unshift( $new_urls, $wp_upload_url );
	array_unshift( $new_fullreplace, $wp_upload_url );
	
	// The old real direct uploads URL. Standalone source like: http://getbackupbuddy.com/wp-content/uploads/. BB doesnt currently support moved uploads. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'Old real direct uploads URL: ' . pb_backupbuddy::$options['dat_file']['upload_url_rewrite'] );
	array_unshift( $old_urls, pb_backupbuddy::$options['dat_file']['upload_url_rewrite'] );
	array_unshift( $old_fullreplace, pb_backupbuddy::$options['dat_file']['upload_url_rewrite'] );
	
	// The new real direct upload URL. Ex: http://pluginbuddy.com/wp-content/uploads/. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'New real direct upload URL to replace old virtual uploads URL: ' . $wp_upload_url_real );
	array_unshift( $new_urls, $wp_upload_url_real );
	array_unshift( $new_fullreplace, $wp_upload_url_real );
	
	// Update user roles option_name row.
	// TODO: moved to bottom of this file.
	//mysql_query( "UPDATE `" . $destination_db_prefix . "options` SET option_name='" . $destination_db_prefix . "user_roles' WHERE option_name LIKE '%_user_roles' LIMIT 1" );
	//pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating user roles option_name to `' . $destination_db_prefix . 'user_roles`.' );
	
	pb_backupbuddy::status( 'details', 'Finished migration steps for `Multisite Export -> Multisite Import` sites.' );
	
}
// ********** END MULTISITE EXPORT -> MULTISITE IMPORT **********



// ********** BEGIN MULTISITE EXPORT -> STANDALONE  **********
if ( ( $source_type == 'multisite_export' ) && ( $destination_type == 'standalone' ) ) {
	
	pb_backupbuddy::status( 'details', 'Starting migration steps for `Multisite Export -> Standalone` sites.' );
	
	// IMPORTANT: Upload URLs _MUST_ be updated before doing a full URL replacement or else the first portion of the URL will be migrated so these will no longer match. array_unshift() is used to bump these to the top of the list to update.
	// These will handle both the REAL url http://.../wp-content/blogs.dir/##/files/ that the virtual path (http://..../wp-content/uploads/).
	
	// The old virtual upload URL. Ex: http://getbackupbuddy.com/files/. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'Old virtual upload URL: ' . pb_backupbuddy::$options['dat_file'][ 'upload_url' ] );
	array_unshift( $old_urls, pb_backupbuddy::$options['dat_file'][ 'upload_url' ] );
	array_unshift( $old_fullreplace, pb_backupbuddy::$options['dat_file'][ 'upload_url' ] );
	
	// The new standalone upload URL. Ex: http://pluginbuddy.com/wp-content/uploads/. Unshifted to place these replacements FIRST in the array of URLs to replace.
	pb_backupbuddy::status( 'details', 'New upload URL to replace virtual upload URL: ' . $new_url . '/wp-content/uploads/' );
	array_unshift( $new_urls, $new_url . '/wp-content/uploads/' );
	array_unshift( $new_fullreplace, $new_url . '/wp-content/uploads/' );
	
	// Only update another URL if it differs -- usually will. They will be the same if the virtual url doesn't exist for some reason (no htaccess availability so the virtual url would match the real url)
	if ( pb_backupbuddy::$options['dat_file'][ 'upload_url' ] != pb_backupbuddy::$options['dat_file'][ 'upload_url_rewrite' ] ) {
		// The old virtual upload URL. Ex: http://getbackupbuddy.com/files/. Unshifted to place these replacements FIRST in the array of URLs to replace.
		pb_backupbuddy::status( 'details', 'Old real upload URL: ' . pb_backupbuddy::$options['dat_file'][ 'upload_url_rewrite' ] );
		array_unshift( $old_urls, pb_backupbuddy::$options['dat_file'][ 'upload_url_rewrite' ] ); // The old real upload URL.
		array_unshift( $old_fullreplace, pb_backupbuddy::$options['dat_file'][ 'upload_url_rewrite' ] );
		
		// The new standalone upload URL. Ex: http://pluginbuddy.com/wp-content/uploads/. Unshifted to place these replacements FIRST in the array of URLs to replace.
		pb_backupbuddy::status( 'details', 'New upload URL to replace real upload URL: ' . $new_url . '/wp-content/uploads/' );
		array_unshift( $new_urls, $new_url . '/wp-content/uploads/' ); // The new standalone upload URL.
		array_unshift( $new_fullreplace, $new_url . '/wp-content/uploads/' ); // The new standalone upload URL.
	}
	
	pb_backupbuddy::status( 'details', 'Finished migration steps for `Multisite Export -> Standalone` sites.' );
	
}
// ********** END MULTISITE EXPORT -> STANDALONE **********









// Loop through the tables matching this prefix. Does NOT change data in other tables.
// This changes actual data on a column by column basis for very row in every table.
$tables = array();
$result = mysql_query( "SHOW TABLES LIKE '" . str_replace( '_', '\_', $destination_db_prefix ) . "%'" );
while ( $table = mysql_fetch_row( $result ) ) {
	$tables[] = $table[0];
}
unset( $table );
pb_backupbuddy::status( 'message', 'Found ' . mysql_num_rows( $result ) . ' WordPress tables. ' );
unset( $result );


$bruteforce_tables = pb_backupbuddy::array_remove( $tables, $bruteforce_excluded_tables ); // Removes all tables listed in $excluded_tables from $tables.
unset( $tables );


if ( $destination_type == 'multisite_import' ) {
	require_once( pb_backupbuddy::plugin_path() . '/lib/dbreplace/dbreplace.php' );
} else {
	require_once( 'importbuddy/lib/dbreplace/dbreplace.php' );
}
$dbreplace = new pluginbuddy_dbreplace( $this );









// ********** BEGIN MAKING OLD URLS UNIQUE AND TRIMMING CORRESPONDING NEW URLS **********
	
	// This entire section is in place to prevent duplicate replacements.
	
	/*	array_pairs_unique_first()
	 *	
	 *	Takes two arrays. Looks for any duplicate values in the first array. That item is removed. The corresponding item in the second array is removed also.
	 *	Resets indexes as a courtesy while maintaining order.
	 *	
	 *	@param		array		$a		First array to make unique.
	 *	@param		array		$b		Second array that has items removed that were in the same position as the removed duplicates found in $a.
	 *	@return		
	 */
	function array_pairs_unique_first( $a, $b ) {
		$a_uniques = array_unique( $a ); // Get unique values in $a. Keys are maintained.
		
		$result = array();
		$result[0] = $a_uniques;
		$result[1] = array_intersect_key( $b, $a_uniques ); // Get the part of the $b array that is missing from $a.
		
		$result[0] = array_merge( $result[0] );
		$result[1] = array_merge( $result[1] );
		return $result;
	}
	
	$unique_urls = array_pairs_unique_first( $old_urls, $new_urls );
	$old_urls = $unique_urls[0];
	$new_urls = $unique_urls[1];
	
	$unique_urls = array_pairs_unique_first( $old_fullreplace, $new_fullreplace );
	$old_fullreplace = $unique_urls[0];
	$new_fullreplace = $unique_urls[1];
	
// ********** END MAKING OLD URLS UNIQUE AND TRIMMING CORRESPONDING NEW URLS **********











pb_backupbuddy::status( 'details', 'Old URLs: ' . implode( ', ', $old_urls ) );
pb_backupbuddy::status( 'details', 'New URLs: ' . implode( ', ', $new_urls ) );
pb_backupbuddy::status( 'details', 'Old full replace: ' . implode( ', ', $old_fullreplace ) );
pb_backupbuddy::status( 'details', 'New full replace: ' . implode( ', ', $new_fullreplace ) );



// Update site URL strings in posts table for rows post_content, post_excerpt, and post_content_filtered. DO NOT update the guid even if it contains URL; per Learned BackupBuddy migrations have been updating the post GUID since it launched when it should not have been... http://codex.wordpress.org/Changing_The_Site_URL#Important_GUID_Note
pb_backupbuddy::status( 'message', 'Updating posts table site URLs.' );
$dbreplace->text( $destination_db_prefix . 'posts', $old_urls, $new_urls, array( 'post_content', 'post_excerpt', 'post_content_filtered' ) );
pb_backupbuddy::status( 'message', 'Site URLs updated in posts table.' );

// Misc string replacements
pb_backupbuddy::status( 'message', 'Replacing WordPress core database text data...' );
$dbreplace->text( $destination_db_prefix . 'users', $old_urls, $new_urls, array( 'user_url' ) );
$dbreplace->text( $destination_db_prefix . 'comments', $old_urls, $new_urls, array( 'comment_content', 'comment_author_url' ) );
$dbreplace->text( $destination_db_prefix . 'links', $old_urls, $new_urls, array( 'link_url', 'link_image', 'link_target', 'link_description', 'link_notes', 'link_rss' ) );
pb_backupbuddy::status( 'message', 'WordPress core database text replaced.' );

// Misc serialized data replacements.
pb_backupbuddy::status( 'message', 'Replacing WordPress core database serialized data...' );
$dbreplace->serialized( $destination_db_prefix . 'options', $old_fullreplace, $new_fullreplace, array( 'option_value' ) );
$dbreplace->serialized( $multisite_network_db_prefix . 'usermeta', $old_fullreplace, $new_fullreplace, array( 'meta_value' ) );
$dbreplace->serialized( $destination_db_prefix . 'postmeta', $old_fullreplace, $new_fullreplace, array( 'meta_value' ) );
$dbreplace->serialized( $destination_db_prefix . 'commentmeta', $old_fullreplace, $new_fullreplace, array( 'meta_value' ) );
pb_backupbuddy::status( 'message', 'WordPress core database serialized data replaced.' );

foreach ( $bruteforce_tables as $bruteforce_table ) {
	$dbreplace->bruteforce_table( $bruteforce_table, $old_fullreplace, $new_fullreplace );
}



// Update table prefixes in some WordPress meta data. $multisite_network_db_prefix is set to the normal prefix in non-ms environment.
$old_prefix = pb_backupbuddy::$options['dat_file']['db_prefix'];
$new_prefix = mysql_real_escape_string( $destination_db_prefix );
pb_backupbuddy::status( 'details', 'Old DB prefix: `' . $old_prefix . '`; New DB prefix: `' . $new_prefix . '`. Network prefix: `' . $multisite_network_db_prefix . '`' );
if ($old_prefix != $new_prefix ) {
	mysql_query("UPDATE `".$new_prefix."usermeta` SET meta_key = REPLACE(meta_key, '".$old_prefix."', '".$new_prefix."' );"); // usermeta table temporarily is in the new subsite's prefix until next step.
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating meta_key\'s for DB prefix in subsite\'s temporary usermeta table from `' . mysql_real_escape_string( $old_prefix ) . '` to `' . mysql_real_escape_string( $new_prefix ) . '`.' );
	mysql_query("UPDATE `".$new_prefix."options` SET option_name = '".$new_prefix."user_roles' WHERE option_name ='".$old_prefix."user_roles' LIMIT 1");
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating option_name user_roles DB prefix in subsite\'s options table to `' . mysql_real_escape_string( $new_prefix ) . '`.' );
	pb_backupbuddy::status( 'message', 'Updated prefix META data.' );
}










// LASTLY UPDATE SITE/HOME URLS to prevent double replacement; just in case!

// Update SITEURL in options table. Usually mass replacement will cover this but set these here just in case.
mysql_query( "UPDATE `" . $destination_db_prefix . "options` SET option_value='" . mysql_real_escape_string( $destination_siteurl ) . "' WHERE option_name='siteurl' LIMIT 1" );
pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating Site URL in options table `' . $destination_db_prefix . 'options` to `' . $destination_siteurl . '`.' );

// Update HOME URL in options table. Usually mass replacement will cover this but set these here just in case.
if ( $destination_home != '' ) {
	mysql_query( "UPDATE `" . $destination_db_prefix . "options` SET option_value='" . mysql_real_escape_string( $destination_home ) . "' WHERE option_name='home' LIMIT 1" );
	pb_backupbuddy::status( 'details', 'Modified ' . mysql_affected_rows() . ' row(s) while updating Home URL in options table to `' . $destination_home . '`.' );
}










pb_backupbuddy::status( 'message', 'Migrated ' . count( $bruteforce_tables ) . ' tables via brute force.' );
pb_backupbuddy::status( 'message', 'Took ' . round( microtime( true ) - pb_backupbuddy::$start_time, 3 ) . ' seconds. Done.' );
pb_backupbuddy::status( 'message', 'Database content migrated.' );







$return = true; // Needed for importbuddy since the following return does not trigger since it's in an include.
return true;
?>