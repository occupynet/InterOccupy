<?php
// TODO: The entire remote destination picking / editing system is still loving in the pre-framework architecture. Migrate it.


pb_backupbuddy::load_style( 'admin' );

pb_backupbuddy::load_script( 'jquery' );
pb_backupbuddy::load_script( 'admin.js', true ); // pbframework version due to second param.
pb_backupbuddy::load_script( 'admin.js' );
pb_backupbuddy::load_style( 'admin.css', true ); // pbframework version due to second param.
pb_backupbuddy::load_script( 'tooltip.js', true ); // pbframework version due to second param.
?>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#pluginbuddy-tabs").tabs();
		
		jQuery('.pb_backupbuddy_selectdestination').click(function(e) {
			var win = window.dialogArguments || opener || parent || top;
			win.pb_backupbuddy_selectdestination( jQuery(this).attr( 'href' ), jQuery(this).attr( 'alt' ), '<?php if ( !empty( $_GET['callback_data'] ) ) { echo $_GET['callback_data']; } ?>' );
			win.tb_remove();
			return false;
		});
	});
</script>

<?php
// Handle duplication.
if ( pb_backupbuddy::_GET( 'duplicate' ) != '' ) {
	if ( !isset( pb_backupbuddy::$options['remote_destinations'][pb_backupbuddy::_GET( 'duplicate' )] ) ) {
		pb_backupbuddy::alert( 'Invalid destination ID to duplicate.' );
	} else {
		$duplicate_destination = pb_backupbuddy::$options['remote_destinations'][pb_backupbuddy::_GET( 'duplicate' )];
		$duplicate_destination['title'] .= ' (copy)';
		
		pb_backupbuddy::$options['remote_destinations'][] = $duplicate_destination;
		
		pb_backupbuddy::alert( 'Duplicated destination to `' . $duplicate_destination['title'] . '`.' );
		pb_backupbuddy::save();
	}
}



if ( pb_backupbuddy::_GET( 'migrate' ) == '1' ) { // Migration, only show FTP destination.
	?>
	<div style="margin-top: 8px; margin-bottom: 45px;">
		<center>
			<span class="description">
				Select a destination below by clicking `Select this destination` from the list of destinations below.<br>
				You may also add new FTP destinations.
			</span>
		</center>
	</div>
	<?php
	$destination_tabs = array(
		array(
			'title'		=>		__( 'FTP', 'it-l10n-backupbuddy' ),
			'slug'		=>		'ftp',
		)
	);
} else { // Normal listing, show all tabs.
	?>
	<div style="margin-top: 8px; margin-bottom: 45px;">
		<center>
			<span class="description">
				BackupBuddy recommends Amazon S3, FTP, or Rackspace Cloud for the best offsite storage experience.
			</span>
		</center>
	</div>
	<?php
	$destination_tabs = array(
		array(
			'title'		=>		__( 'FTP', 'it-l10n-backupbuddy' ),
			'slug'		=>		'ftp',
		),
		array(
			'title'		=>		__( 'Amazon S3', 'it-l10n-backupbuddy' ),
			'slug'		=>		's3',
		),
		array(
			'title'		=>		__( 'Dropbox', 'it-l10n-backupbuddy' ),
			'slug'		=>		'dropbox',
		),
		array(
			'title'		=>		__( 'Rackspace', 'it-l10n-backupbuddy' ),
			'slug'		=>		'rackspace',
		),		
		array(
			'title'		=>		__( 'Email', 'it-l10n-backupbuddy' ),
			'slug'		=>		'email',
		),
	);
}


pb_backupbuddy::$ui->start_tabs(
	'remote_destinations',
	$destination_tabs,
	'min-width: 300px; width: 100%;'
);













// Used by savesettings().
function strip_tags_deep( $value ) {
  return is_array( $value ) ?
    array_map( 'strip_tags_deep', $value ) :
    strip_tags( $value );
}

/**
 *	savesettings()
 *	
 *	Saves a form into the _options array.
 *	
 *	Use savepoint to set the root array key path. Accepts variable depth, dividing array keys with pound signs.
 *	Ex:	$_POST['savepoint'] value something like array_key_name#subkey
 *		<input type="hidden" name="savepoint" value="files#exclusions" /> to set the root to be $this->_options['files']['exclusions']
 *	
 *	All inputs with the name beginning with pound will act as the array keys to be set in the _options with the associated posted value.
 *	Ex:	$_POST['#key_name'] or $_POST['#key_name#subarray_key_name'] value is the array value to set.
 *		<input type="text" name="#name" /> will save to $this->_options['name']
 *		<input type="text" name="#group#17#name" /> will save to $this->_options['groups'][17]['name']
 *
 *	$savepoint_root		string		Override the savepoint. Same format as the form savepoint.
 */
function savesettings( $savepoint_root = '' ) {
	//check_admin_referer( 'backupbuddy-nonce' );
	foreach( $_POST as $post_index => $post_value ) {
		$_POST[$post_index] = strip_tags_deep( $post_value ); // Do not use just strip_tags as it breaks array post vars.
	}
	if ( !empty( $savepoint_root ) ) { // Override savepoint.
		$_POST['savepoint'] = $savepoint_root;
	}
	
	if ( !empty( $_POST['savepoint'] ) ) {
		$savepoint_root = stripslashes( $_POST['savepoint'] ) . '#';
	} else {
		$savepoint_root = '';
	}
	
	$posted = stripslashes_deep( $_POST ); // Unescape all the stuff WordPress escaped. Sigh @ WordPress for being like PHP magic quotes.
	foreach( $posted as $index => $item ) {
		if ( substr( $index, 0, 1 ) == '#' ) {
			$savepoint_subsection = &pb_backupbuddy::$options;
			$savepoint_levels = explode( '#', $savepoint_root . substr( $index, 1 ) );
			foreach ( $savepoint_levels as $savepoint_level ) {
				$savepoint_subsection = &$savepoint_subsection{$savepoint_level};
			}
			$savepoint_subsection = $item;
		}
	}
	
	pb_backupbuddy::save();
}



if ( isset( $_POST['delete_destinations'] ) ) {
	if ( ! empty( $_POST['destinations'] ) && is_array( $_POST['destinations'] ) ) {
		$deleted_groups = '';
		
		foreach ( (array) $_POST['destinations'] as $id ) {
			$deleted_groups .= ' "' . stripslashes( pb_backupbuddy::$options['remote_destinations'][$id]['title'] ) . '",';
			unset( pb_backupbuddy::$options['remote_destinations'][$id] );
			
			// Remove this destination from all schedules using it.
			foreach( pb_backupbuddy::$options['schedules'] as $schedule_id => $schedule ) {
				$remote_list = '';
				$trimmed_destination = false;
				
				$remote_destinations = explode( '|', $schedule['remote_destinations'] );
				foreach( $remote_destinations as $remote_destination ) {
					if ( $remote_destination == $id ) {
						$trimmed_destination = true;
					} else {
						$remote_list .= $remote_destination . '|';
					}
				}
				
				if ( $trimmed_destination === true ) {
					pb_backupbuddy::$options['schedules'][$schedule_id]['remote_destinations'] = $remote_list;
				}
			}
		}
		
		pb_backupbuddy::save();
		pb_backupbuddy::alert( __('Deleted destination(s) ', 'it-l10n-backupbuddy' ) . trim( $deleted_groups, ',' ) . '.' );
	}
}

if ( isset( $_GET['edit'] ) && ( $_GET['edit'] != '' ) ) {
	$edit_id = $_GET['edit'];
} else {
	$edit_id = '';
}

if ( isset( $_GET['clear_dropboxtemptoken'] ) && ( $_GET['clear_dropboxtemptoken'] == 'true' ) ) {
	pb_backupbuddy::$options['dropboxtemptoken'] = ''; // Clear temp token.
	pb_backupbuddy::save();
}

if ( !empty( $_POST['add_destination'] ) ) {
	if ( $_POST['#title'] == '' ) {
		$_POST['#title'] = '[no name]';
	}
	
	if ( $_POST['#type'] == 'dropbox' ) {
		$_POST['#token'] = pb_backupbuddy::$options['dropboxtemptoken'];
		pb_backupbuddy::$options['dropboxtemptoken'] = ''; // Clear temp token.
		pb_backupbuddy::save();
	}
	
	if ( $_POST['#type'] == 'ftp' ) {
		// Require leading slash for FTP path.
		if ( ( $_POST['#path'] != '' ) && ( substr( $_POST['#path'], 0, 1 ) != '/' ) ) {
			$_POST['#path'] = '/' . $_POST['#path'];
		}
	}
	
	if ( empty( pb_backupbuddy::$options['remote_destinations'] ) || !is_array( pb_backupbuddy::$options['remote_destinations'] ) ) {
		$next_index = 0; // Empty remote destination array or not an array so set index to 0.
	} else { // Remote destinations is an array so determine index.
		$next_index = end( array_keys( pb_backupbuddy::$options['remote_destinations'] ) ) + 1;
		if ( empty( $next_index ) ) {
			// No index so set it to 0.
			$next_index = 0;
		}
	}
	
	if ( !defined( 'PB_DEMO_MODE' ) ) {
		$missing_field = false;
		$required_fields = explode( ',', pb_backupbuddy::_POST( 'required_fields' ) );
		foreach( $required_fields as $required_field ) {
			if ( pb_backupbuddy::_POST( '#' . $required_field ) == '' ) {
				$missing_field = true;
			}
		}
		if ( $missing_field !== false ) {
			pb_backupbuddy::alert( 'One or more required fields were missing. Destination not created.' );
		} else {
			savesettings( 'remote_destinations#' . $next_index );
			pb_backupbuddy::alert( __('Destination created.', 'it-l10n-backupbuddy' ) );
		}
	} else {
		pb_backupbuddy::alert( 'Access denied in demo mode.', true );
	}
} elseif ( !empty( $_POST['edit_destination'] ) ) {
	if ( $_POST['#title'] == '' ) {
		$_POST['#title'] = '[no name]';
	}
	
	if ( $_POST['#type'] == 'ftp' ) {
		if ( ( $_POST['#path'] != '' ) && ( substr( $_POST['#path'], 0, 1 ) != '/' ) ) {
			// Require leading slash for FTP path.
			$_POST['#path'] = '/' . $_POST['#path'];
		}
	}
	
	if ( !defined( 'PB_DEMO_MODE' ) ) {
		$missing_field = false;
		$required_fields = explode( ',', pb_backupbuddy::_POST( 'required_fields' ) );
		foreach( $required_fields as $required_field ) {
			if ( pb_backupbuddy::_POST( '#' . $required_field ) == '' ) {
				$missing_field = true;
			}
		}
		if ( $missing_field !== false ) {
			pb_backupbuddy::alert( 'One or more required fields were missing. Destination settings not saved.' );
		} else {
			savesettings( $_POST['savepoint'] );
			pb_backupbuddy::alert( __('Destination settings saved.', 'it-l10n-backupbuddy' ) );
		}
	} else {
		pb_backupbuddy::alert( 'Access denied in demo mode.', true );
	}
}












pb_backupbuddy::$ui->start_tab( 'ftp' );
	require_once( '_destination_ftp.php' );
pb_backupbuddy::$ui->end_tab();

if ( pb_backupbuddy::_GET( 'migrate' ) != '1' ) { // Show all destinations if not a migration.
	
	pb_backupbuddy::$ui->start_tab( 'email' );
		require_once( '_destination_email.php' );
	pb_backupbuddy::$ui->end_tab();
	
	
	pb_backupbuddy::$ui->start_tab( 'rackspace' );
		require_once( '_destination_rackspace.php' );
	pb_backupbuddy::$ui->end_tab();
	
	
	pb_backupbuddy::$ui->start_tab( 's3' );
		require_once( '_destination_s3.php' );
	pb_backupbuddy::$ui->end_tab();
	
	
	pb_backupbuddy::$ui->start_tab( 'dropbox' );
		require_once( '_destination_dropbox.php' );
	pb_backupbuddy::$ui->end_tab();
	
} // End if not a migration.

?>