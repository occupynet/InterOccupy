<?php
$php_minimum = '5.1'; // User's PHP must be equal or newer to this version.

if ( version_compare( PHP_VERSION, $php_minimum ) < 0 ) {
	die( 'ERROR #9013. See <a href="http://ithemes.com/codex/page/BackupBuddy:_Error_Codes#9013">this codex page for details</a>. Sorry! PHP version ' . $php_minimum . ' or newer is required for BackupBuddy to properly run. You are running PHP version ' . PHP_VERSION . '.' );
}

define( 'ABSPATH', dirname( __FILE__ ) . '/' );
define( 'PB_BB_VERSION', '#VERSION#' );
define( 'PB_PASSWORD', '#PASSWORD#' );

// Unpack importbuddy files into importbuddy directory.
if ( !file_exists( ABSPATH . 'importbuddy' ) ) {
	unpack_importbuddy();
}



date_default_timezone_set( @date_default_timezone_get() ); // Prevents date() from throwing a warning if the default timezone has not been set.


if ( isset( $_GET['api'] ) && ( $_GET['api'] != '' ) ) { // API ACCESS
	if ( $_GET['api'] == 'ping' ) {
		die( 'pong' );
	} else {
		die( 'Unknown API access action.' );
	}
} else { // NORMAL ACCESS.
	if ( !file_exists( ABSPATH . 'importbuddy/init.php' ) ) {
		die( 'Error: Unable to load importbuddy. Make sure that you downloaded this script from within BackupBuddy. Copying importbuddy files from inside the plugin directory is not sufficient as many file additions are made on demand.' );
	} else {
		require_once( ABSPATH . 'importbuddy/init.php' );
	}
}


/**
*	unpack_importbuddy()
*
*	Unpacks required files encoded in importbuddy.php into stand-alone files.
*
*	@return		null
*/
function unpack_importbuddy() {
	if ( !is_writable( ABSPATH ) ) {
		echo 'Error #224834. This directory is not write enabled. Please verify write permissions to continue.';
		die();
	} else {
		$unpack_file = '';
		
		// Make sure the file is complete and contains all the packed data to the end.
		if ( false === strpos( file_get_contents( ABSPATH . 'importbuddy.php' ), '###PACKDATA' . ',END' ) ) { // Concat here so we don't false positive on this line when searching.
			die( 'ERROR: It appears your importbuddy.php file is incomplete.  It may have not finished uploaded completely.  Please try re-downloading the script from within BackupBuddy in WordPress (do not just copy the file from the plugin directory) and re-uploading it.' );
		}
		
		$handle = @fopen( ABSPATH . 'importbuddy.php', 'r' );
		if ( $handle ) {
			while ( ( $buffer = fgets( $handle ) ) !== false ) {
				if ( substr( $buffer, 0, 11 ) == '###PACKDATA' ) {
					$packdata_commands = explode( ',', trim( $buffer ) );
					array_shift( $packdata_commands );
					
					if ( $packdata_commands[0] == 'BEGIN' ) {
						// Start packed data.
					} elseif ( $packdata_commands[0] == 'FILE_START' ) {
						$unpack_file = $packdata_commands[2];
					} elseif ( $packdata_commands[0] == 'FILE_END' ) {
						$unpack_file = '';
					} elseif ( $packdata_commands[0] == 'END' ) {
						return;
					}
				} else {
					if ( $unpack_file != '' ) {
						if ( !is_dir( dirname( ABSPATH . $unpack_file ) ) ) {
							mkdir( dirname( ABSPATH . $unpack_file ), 0777, true ); // second param makes recursive.
						}
						file_put_contents( ABSPATH . $unpack_file, trim( base64_decode( $buffer ) ) );
					}
				}
			}
			if ( !feof( $handle ) ) {
				echo "Error: unexpected fgets() fail\n";
			}
			fclose( $handle );
		}
	}
}
die();
?>