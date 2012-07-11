<br>
<style type="text/css">
	.pb_backupbuddy_refresh_stats {
		cursor: pointer;
	}
</style>
<?php
/*
 *	IMPORTANT NOTE:
 *
 *	This file is shared between multiple projects / purposes:
 *		+ BackupBuddy (this plugin) Server Info page.
 *		+ ImportBuddy.php (BackupBuddy importer) Server Information button dropdown display.
 *		+ ServerBuddy (plugin)
 *
 *	Use caution when updated to prevent breaking other projects.
 *
 */


// ini_get_bool() credit: nicolas dot grekas+php at gmail dot com
function ini_get_bool( $a ) {
	$b = ini_get($a);
	switch (strtolower($b)) {
		case 'on':
		case 'yes':
		case 'true':
			return 'assert.active' !== $a;
			
		case 'stdout':
		case 'stderr':
			return 'display_errors' === $a;
			
		default:
			return (bool) (int) $b;
	}
}
	
	$tests = array();
	
	
	// Skip these tests in importbuddy.
	if ( !defined( 'PB_IMPORTBUDDY' ) ) {
		// WORDPRESS VERSION
		global $wp_version;
		$parent_class_test = array(
						'title'			=>		'WordPress Version',
						'suggestion'	=>		'>= ' . pb_backupbuddy::settings( 'wp_minimum' ) . ' (latest best)',
						'value'			=>		$wp_version,
						'tip'			=>		__('Version of WordPress currently running. It is important to keep your WordPress up to date for security & features.', 'it-l10n-backupbuddy' ),
					);
		if ( version_compare( $wp_version, pb_backupbuddy::settings( 'wp_minimum' ), '<=' ) ) {
			$parent_class_test['status'] = __('FAIL', 'it-l10n-backupbuddy' );
		} else {
			$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		}
		array_push( $tests, $parent_class_test );
	
		// MYSQL VERSION
		global $wpdb;
		$parent_class_test = array(
						'title'			=>		'MySQL Version',
						'suggestion'	=>		'>= 5.0.15',
						'value'			=>		$wpdb->db_version(),
						'tip'			=>		__('Version of your database server (mysql) as reported to this script by WordPress.', 'it-l10n-backupbuddy' ),
					);
		if ( version_compare( $wpdb->db_version(), '5.0.15', '<=' ) ) {
			$parent_class_test['status'] = __('FAIL', 'it-l10n-backupbuddy' );
		} else {
			$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		}
		array_push( $tests, $parent_class_test );
		
		
		// ADDHANDLER HTACCESS CHECK
		$parent_class_test = array(
						'title'			=>		'AddHandler in .htaccess',
						'suggestion'	=>		'host dependant (none best unless required)',
						'tip'			=>		__('If detected then you may have difficulty migrating your site to some hosts without first removing the AddHandler line. Some hosts will malfunction with this line in the .htaccess file.', 'it-l10n-backupbuddy' ),
					);
		if ( file_exists( ABSPATH . '.htaccess' ) ) {
			$addhandler_note = '';
			$htaccess_lines = file( ABSPATH . '.htaccess' );
			foreach ( $htaccess_lines as $htaccess_line ) {
				if ( preg_match( '/^(\s*)AddHandler(.*)/i', $htaccess_line, $matches ) > 0 ) {
					$addhandler_note = pb_backupbuddy::tip( htmlentities( $matches[0] ), __( 'AddHandler Value', 'it-l10n-backupbuddy' ), false );
				}
			}
			unset( $htaccess_lines );
			
			if ( $addhandler_note == '' ) {
				$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
				$parent_class_test['value'] = __('none, n/a', 'it-l10n-backupbuddy' );
			} else {
				$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
				$parent_class_test['value'] = __('exists', 'it-l10n-backupbuddy' ) . $addhandler_note;
			}
			unset( $htaccess_contents );
		} else {
			$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
			$parent_class_test['value'] = __('n/a', 'it-l10n-backupbuddy' );
		}
		array_push( $tests, $parent_class_test );
		
		
		// Set up ZipBuddy when within BackupBuddy
		require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
		pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		
		require_once( pb_backupbuddy::plugin_path() . '/lib/mysqlbuddy/mysqlbuddy.php' );
		global $wpdb;
		pb_backupbuddy::$classes['mysqlbuddy'] = new pb_backupbuddy_mysqlbuddy( DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, $wpdb->prefix ); // $database_host, $database_name, $database_user, $database_pass, $old_prefix, $force_method = array()
	}
	
	
	// PHP VERSION
	if ( !defined( 'pluginbuddy_importbuddy' ) ) {
		$php_minimum = pb_backupbuddy::settings( 'php_minimum' );
	} else { // importbuddy value.
		$php_minimum = pb_backupbuddy::settings( 'php_minimum' );
	}
	$parent_class_test = array(
					'title'			=>		'PHP Version',
					'suggestion'	=>		'>= ' . $php_minimum . ' (5.2.16+ best)',
					'value'			=>		phpversion(),
					'tip'			=>		__('Version of PHP currently running on this site.', 'it-l10n-backupbuddy' ),
				);
	if ( version_compare( PHP_VERSION, $php_minimum, '<=' ) ) {
		$parent_class_test['status'] = __('FAIL', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	
	// PHP max_execution_time
	$parent_class_test = array(
					'title'			=>		'PHP max_execution_time',
					'suggestion'	=>		'>= ' . '30 seconds (30+ best)',
					'value'			=>		ini_get( 'max_execution_time' ),
					'tip'			=>		__('Maximum amount of time that PHP allows scripts to run. After this limit is reached the script is killed. The more time available the better. 30 seconds is most common though 60 seconds is ideal.', 'it-l10n-backupbuddy' ),
				);
	if ( str_ireplace( 's', '', ini_get( 'max_execution_time' ) ) < 30 ) {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	
	
	// MEMORY LIMIT
	if ( !ini_get( 'memory_limit' ) ) {
		$parent_class_val = 'unknown';
	} else {
		$parent_class_val = ini_get( 'memory_limit' );
	}
	$parent_class_test = array(
					'title'			=>		'PHP Memory Limit',
					'suggestion'	=>		'>= 128M (256M+ best)',
					'value'			=>		$parent_class_val,
					'tip'			=>		__('The amount of memory this site is allowed to consume.', 'it-l10n-backupbuddy' ),
				);
	if ( preg_match( '/(\d+)(\w*)/', $parent_class_val, $matches ) ) {
		$parent_class_val = $matches[1];
		$unit = $matches[2];
		// Up memory limit if currently lower than 256M.
		if ( 'g' !== strtolower( $unit ) ) {
			if ( ( $parent_class_val < 128 ) || ( 'm' !== strtolower( $unit ) ) ) {
				$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
			} else {
				$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
			}
		}
	} else {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	
	if ( defined( 'PB_IMPORTBUDDY' ) ) {
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( ABSPATH );
		}
	}
	
	$parent_class_test = array(
					'title'			=>		'Zip Methods',
					'suggestion'	=>		'Command line (best) > ziparchive > PHP-based (worst)',
					'value'			=>		implode( ', ', pb_backupbuddy::$classes['zipbuddy']->_zip_methods ),
					'tip'			=>		__('Methods your server supports for creating ZIP files. These were tested & verified to operate. Command line is magnitudes better than other methods and operate via exec() or other execution functions. ZipArchive is a PHP extension. PHP-based ZIP compression/extraction is performed via a PHP script called pclzip but it is very slow and memory intensive and should only be used as a last effort.', 'it-l10n-backupbuddy' ),
				);
	if ( in_array( 'exec', pb_backupbuddy::$classes['zipbuddy']->_zip_methods ) ) {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	
	if ( !defined( 'PB_IMPORTBUDDY' ) ) {
		
		$parent_class_test = array(
						'title'			=>		'Database Dump Methods',
						'suggestion'	=>		'Command line (best) > PHP-based (slower)',
						'value'			=>		implode( ', ', pb_backupbuddy::$classes['mysqlbuddy']->get_methods() ),
						'tip'			=>		__('Methods your server supports for dumping (backing up) your mysql database. These were tested values unless compatibility / troubleshooting settings override.', 'it-l10n-backupbuddy' ),
					);
		if ( in_array( 'commandline', pb_backupbuddy::$classes['mysqlbuddy']->get_methods() ) ) {
			$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		} else {
			$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
		}
		array_push( $tests, $parent_class_test );
		
		
		
		// Site Size
		if ( pb_backupbuddy::$options['stats']['site_size'] > 0 ) {
			$site_size = pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['stats']['site_size'] );
		} else {
			$site_size = '<i>Unknown</i>';
		}
		$parent_class_test = array(
						'title'			=>		'Site Size',
						'suggestion'	=>		'N/A',
						'value'			=>		'<span id="pb_stats_refresh_site_size">' . $site_size . '</span> <a class="pb_backupbuddy_refresh_stats" rel="refresh_site_size" alt="' . pb_backupbuddy::ajax_url( 'refresh_site_size' ) . '" title="' . __('Refresh', 'it-l10n-backupbuddy' ) . '"><img src="' . pb_backupbuddy::plugin_url() . '/images/refresh_gray.gif" style="vertical-align: -1px;"> <span class="pb_backupbuddy_loading" style="display: none; margin-left: 10px;"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading.gif" alt="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" title="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" width="16" height="16" style="vertical-align: -3px;" /></span></a>',
						'tip'			=>		__('Total size of your site (starting in your WordPress main directory) INCLUDING any excluded directories / files.', 'it-l10n-backupbuddy' ),
					);
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		array_push( $tests, $parent_class_test );
		
		
		
		// Site size WITH EXCLUSIONS accounted for.
		if ( pb_backupbuddy::$options['stats']['site_size_excluded'] > 0 ) {
			$site_size_excluded = pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['stats']['site_size_excluded'] );
		} else {
			$site_size_excluded = '<i>Unknown</i>';
		}
		$parent_class_test = array(
						'title'			=>		'Site Size with Exclusions',
						'suggestion'	=>		'N/A',
						'value'			=>		'<span id="pb_stats_refresh_site_size_excluded">' . $site_size_excluded . '</span> <a class="pb_backupbuddy_refresh_stats" rel="refresh_site_size_excluded" alt="' . pb_backupbuddy::ajax_url( 'refresh_site_size_excluded' ) . '" title="' . __('Refresh', 'it-l10n-backupbuddy' ) . '"><img src="' . pb_backupbuddy::plugin_url() . '/images/refresh_gray.gif" style="vertical-align: -1px;"> <span class="pb_backupbuddy_loading" style="display: none; margin-left: 10px;"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading.gif" alt="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" title="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" width="16" height="16" style="vertical-align: -3px;" /></span></a>',
						'tip'			=>		__('Total size of your site (starting in your WordPress main directory) EXCLUDING any directories / files you have marked for exclusion.', 'it-l10n-backupbuddy' ),
					);
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		array_push( $tests, $parent_class_test );
		
		
		
		// Database Size
		$parent_class_test = array(
						'title'			=>		'Database Size',
						'suggestion'	=>		'N/A',
						'value'			=>		'<span id="pb_stats_refresh_database_size">' .pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['stats']['db_size'] ) . '</span> <a class="pb_backupbuddy_refresh_stats" rel="refresh_database_size" alt="' . pb_backupbuddy::ajax_url( 'refresh_database_size' ) . '" title="' . __('Refresh', 'it-l10n-backupbuddy' ) . '"><img src="' . pb_backupbuddy::plugin_url() . '/images/refresh_gray.gif" style="vertical-align: -1px;"> <span class="pb_backupbuddy_loading" style="display: none; margin-left: 10px;"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading.gif" alt="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" title="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" width="16" height="16" style="vertical-align: -3px;" /></span></a>',
						'tip'			=>		__('Total size of your database INCLUDING any excluded tables.', 'it-l10n-backupbuddy' ),
					);
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		array_push( $tests, $parent_class_test );
		
		
		
		// Database size WITH EXCLUSIONS accounted for.
		$parent_class_test = array(
						'title'			=>		'Database Size with Exclusions',
						'suggestion'	=>		'N/A',
						'value'			=>		'<span id="pb_stats_refresh_database_size_excluded">' . pb_backupbuddy::$format->file_size( pb_backupbuddy::$options['stats']['db_size_excluded'] ) . '</span> <a class="pb_backupbuddy_refresh_stats" rel="refresh_database_size_excluded" alt="' . pb_backupbuddy::ajax_url( 'refresh_database_size_excluded' ) . '" title="' . __('Refresh', 'it-l10n-backupbuddy' ) . '"><img src="' . pb_backupbuddy::plugin_url() . '/images/refresh_gray.gif" style="vertical-align: -1px;"> <span class="pb_backupbuddy_loading" style="display: none; margin-left: 10px;"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading.gif" alt="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" title="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" width="16" height="16" style="vertical-align: -3px;" /></span></a>',
						'tip'			=>		__('Total size of your database EXCLUDING any tables you have marked for exclusion.', 'it-l10n-backupbuddy' ),
					);
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		array_push( $tests, $parent_class_test );
		
		
		
		// Average write speed.
		$write_speed_samples = 0;
		$write_speed_sum = 0;
		foreach( pb_backupbuddy::$options['backups'] as $backup ) {
			if ( isset( $backup['integrity'] ) && isset( $backup['integrity']['size'] ) ) {
				$write_speed_samples++;
				
				$size = $backup['integrity']['size'];
				$time_taken = 0;
				if ( isset( $backup['steps'] ) ) {
					foreach( $backup['steps'] as $step ) {
						if ( $step['function'] == 'backup_zip_files' ) {
							$time_taken = $step['finish_time'] - $step['start_time'];
							break;
						}
					} // End foreach.
				} // End if steps isset.
				
				if ( $time_taken == 0 ) {
					//$write_speed_sum += 0;
					$write_speed_samples = $write_speed_samples - 1; // Ignore this sample since it's too small to count.
				} else {
					$write_speed_sum += $size / $time_taken; // Sum up write speeds.
				}
				
			}
		}
		
		if ( $write_speed_sum > 0 ) {
			$final_write_speed = pb_backupbuddy::$format->file_size( $write_speed_sum / $write_speed_samples ) . '/sec';
			$final_write_speed_guess = pb_backupbuddy::$format->file_size( ( $write_speed_sum / $write_speed_samples ) * ini_get( 'max_execution_time' ) );
		} else {
			$final_write_speed = '<i>Unknown</i>';
			$final_write_speed_guess = '<i>Unknown</i>';
		}
		
		$parent_class_test = array(
						'title'			=>		'Average Write Speed',
						'suggestion'	=>		'N/A',
						'value'			=>		 $final_write_speed,
						'tip'			=>		__('Average ZIP file creation write speed. Backup file sizes divided by the time taken to create each. Samples: `' . $write_speed_samples . '`.', 'it-l10n-backupbuddy' ),
					);
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		array_push( $tests, $parent_class_test );
		
		
		// Guess max site size to be able to backup.
		$parent_class_test = array(
						'title'			=>		'Guesstimate of max ZIP size',
						'suggestion'	=>		'N/A',
						'value'			=>		$final_write_speed_guess,
						'tip'			=>		__('Calculated estimate of the largest .ZIP backup file that may be created. As ZIP files are compressed the site size that may be backed up should be larger than this.', 'it-l10n-backupbuddy' ),
					);
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
		array_push( $tests, $parent_class_test );
		
		
		
		
		
	} // End non-importbuddy tests.
		
	
	
	
	




		
	
	// REGISTER GLOBALS
	$disabled_functions = ini_get( 'disable_functions' );
	if ( $disabled_functions == '' ) {
		$disabled_functions = '<i>(none)</i>';
	}
	$parent_class_test = array(
					'title'			=>		'Disabled PHP Functions',
					'suggestion'	=>		'N/A',
					'value'			=>		$disabled_functions,
					'tip'			=>		__('Some hosts block certain PHP functions for various reasons. Sometimes hosts block functions that are required for proper functioning of WordPress or plugins.', 'it-l10n-backupbuddy' ),
				);
	$disabled_functions_array = explode( ', ', $disabled_functions );
	$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	if (
		( true === in_array( 'exec', $disabled_functions_array ) )
		||
		( true === in_array( 'ini_set', $disabled_functions_array ) )
		) {
		$parent_class_test['status'] = __('FAIL', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	
	// REGISTER GLOBALS
	if ( ini_get_bool( 'register_globals' ) === true ) {
		$parent_class_val = 'enabled';
	} else {
		$parent_class_val = 'disabled';
	}
	$parent_class_test = array(
					'title'			=>		'PHP Register Globals',
					'suggestion'	=>		'disabled',
					'value'			=>		$parent_class_val,
					'tip'			=>		__('Automatically registers user input as variables. HIGHLY discouraged. Removed from PHP in PHP 6 for security.', 'it-l10n-backupbuddy' ),
				);
	if ( $parent_class_val != 'disabled' ) {
		$parent_class_test['status'] = __('FAIL', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	// MAGIC QUOTES GPC
	if ( ini_get_bool( 'magic_quotes_gpc' ) === true ) {
		$parent_class_val = 'enabled';
	} else {
		$parent_class_val = 'disabled';
	}
	$parent_class_test = array(
					'title'			=>		'PHP Magic Quotes GPC',
					'suggestion'	=>		'disabled',
					'value'			=>		$parent_class_val,
					'tip'			=>		__('Automatically escapes user inputted data. Not needed when using properly coded software.', 'it-l10n-backupbuddy' ),
				);
	if ( $parent_class_val != 'disabled' ) {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	// MAGIC QUOTES RUNTIME
	if ( ini_get_bool( 'magic_quotes_runtime' ) === true ) {
		$parent_class_val = 'enabled';
	} else {
		$parent_class_val = 'disabled';
	}
	$parent_class_test = array(
					'title'			=>		'PHP Magic Quotes Runtime',
					'suggestion'	=>		'disabled',
					'value'			=>		$parent_class_val,
					'tip'			=>		__('Automatically escapes user inputted data. Not needed when using properly coded software.', 'it-l10n-backupbuddy' ),
				);
	if ( $parent_class_val != 'disabled' ) {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	// SAFE MODE
	if ( ini_get_bool( 'safe_mode' ) === true ) {
		$parent_class_val = 'enabled';
	} else {
		$parent_class_val = 'disabled';
	}
	$parent_class_test = array(
					'title'			=>		'PHP Safe Mode',
					'suggestion'	=>		'disabled',
					'value'			=>		$parent_class_val,
					'tip'			=>		__('This mode is HIGHLY discouraged and is a sign of a poorly configured host.', 'it-l10n-backupbuddy' ),
				);
	if ( $parent_class_val != 'disabled' ) {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	// OS
	$parent_class_test = array(
					'title'			=>		'Operating System',
					'suggestion'	=>		'Linux',
					'value'			=>		PHP_OS,
					'tip'			=>		__('The server operating system running this site. Linux based systems are encouraged. Windows users may need to perform additional steps to get plugins to perform properly.', 'it-l10n-backupbuddy' ),
				);
	if ( PHP_OS == 'WINNT' ) {
		$parent_class_test['status'] = __('WARNING', 'it-l10n-backupbuddy' );
	} else {
		$parent_class_test['status'] = __('OK', 'it-l10n-backupbuddy' );
	}
	array_push( $tests, $parent_class_test );
	
	
	
?>


<table class="widefat">
	<thead>
		<tr class="thead">
			<th style="width: 15px;">&nbsp;</th>
			<?php
				echo '<th>', __('Parameter', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Suggestion', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Value', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Result', 'it-l10n-backupbuddy' ), '</th>',
					 '<th style="width: 60px;">', __('Status', 'it-l10n-backupbuddy' ), '</th>';
			?>
		</tr>
	</thead>
	<tfoot>
		<tr class="thead">
			<th style="width: 15px;">&nbsp;</th>
			<?php
				echo '<th>', __('Parameter', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Suggestion', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Value', 'it-l10n-backupbuddy' ), '</th>',
					 '<th>', __('Result', 'it-l10n-backupbuddy' ), '</th>',
					 '<th style="width: 15px;">', __('Status', 'it-l10n-backupbuddy' ), '</th>';
			?>
		</tr>
	</tfoot>
	<tbody>
		<?php
		foreach( $tests as $parent_class_test ) {
			echo '<tr class="entry-row alternate">';
			echo '	<td>' . pb_backupbuddy::tip( $parent_class_test['tip'], '', false ) . '</td>';
			echo '	<td>' . $parent_class_test['title'] . '</td>';
			echo '	<td>' . $parent_class_test['suggestion'] . '</td>';
			echo '	<td>' . $parent_class_test['value'] . '</td>';
			echo '	<td>' . $parent_class_test['status'] . '</td>';
			echo '	<td>';
			if ( $parent_class_test['status'] == __('OK', 'it-l10n-backupbuddy' ) ) {
				echo '<div style="background-color: #22EE5B; border: 1px solid #E2E2E2;">&nbsp;&nbsp;&nbsp;</div>';
			} elseif ( $parent_class_test['status'] == __('FAIL', 'it-l10n-backupbuddy' ) ) {
				echo '<div style="background-color: #CF3333; border: 1px solid #E2E2E2;">&nbsp;&nbsp;&nbsp;</div>';
			} elseif ( $parent_class_test['status'] == __('WARNING', 'it-l10n-backupbuddy' ) ) {
				echo '<div style="background-color: #FEFF7F; border: 1px solid #E2E2E2;">&nbsp;&nbsp;&nbsp;</div>';
			}
			echo '	</td>';
			echo '</tr>';
		}
		?>
	</tbody>
</table>
<?php
if ( isset( $_GET['phpinfo'] ) && $_GET['phpinfo'] == 'true' ) {
	if ( defined( 'PB_DEMO_MODE' ) ) {
		pb_backupbuddy::alert( 'Access denied in demo mode.', true );
	} else {
		echo '<br><h3>phpinfo() ', __('Response', 'it-l10n-backupbuddy' ), ':</h3>';
		
		echo '<div style="width: 100%; height: 600px; padding-top: 10px; padding-bottom: 10px; overflow: scroll; ">';
		ob_start();
		
		phpinfo();
		
		$info = ob_get_contents();
		ob_end_clean();
		$info = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
		echo $info;
		unset( $info );
		
		echo '</div>';
	}
} else {
	echo '<br>';
	echo '<center>';
	if ( !defined( 'PB_IMPORTBUDDY' ) ) {
		echo '<a href="' . pb_backupbuddy::ajax_url( 'phpinfo' ) . '&#038;TB_iframe=1&#038;width=640&#038;height=600" class="thickbox button secondary-button" style="margin-top: 3px;" title="' . __('Display Extended PHP Settings via phpinfo()', 'it-l10n-backupbuddy' ) . '">' . __('Display Extended PHP Settings via phpinfo()', 'it-l10n-backupbuddy' ) . '</a>';
	} else {
		if ( ( file_exists( ABSPATH . '/repairbuddy' ) ) && method_exists( $parent_class, 'page_link' ) ) {
			echo '<a href="' . $parent_class->page_link( 'server_info', 'phpinfo' ) . '" class="button-secondary" style="margin-top: 3px; text-decoration: none;">'. __('Display Extended PHP Settings via phpinfo()', 'it-l10n-backupbuddy' ) . '</a>';
		} else {
			//echo '<a href="?step=0&action=phpinfo&v=xv' . md5( $parent_class->_defaults['import_password'] . 'importbuddy' ) . '" class="button-secondary" style="margin-top: 3px; text-decoration: none;">'. __('Display Extended PHP Settings via phpinfo()', 'it-l10n-backupbuddy' ) . '</a>';
		}
	}
	echo '</center>';
	
	/*
	echo '<pre>';
	print_r( ini_get_all() );
	echo '</pre>';
	*/
}
?><br>