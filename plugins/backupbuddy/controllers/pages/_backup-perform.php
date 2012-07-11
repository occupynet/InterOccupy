<?php
//pb_backupbuddy::$filesystem->recursive_copy( 
//pb_backupbuddy::load_script( 'admin.js' );

require_once( pb_backupbuddy::plugin_path() . '/classes/backup.php' );
pb_backupbuddy::$classes['backup'] = new pb_backupbuddy_backup();

// Set serial ahead of time so can be used by AJAX before backup procedure actually begins.
$serial_override = pb_backupbuddy::random_string( 10 );

pb_backupbuddy::$ui->title( 'Create Backup' );
?>

<script type="text/javascript">
	jQuery(document).ready(function() {
			// Wait 2 seconds before first poll.
			setTimeout( 'backupbuddy_poll()' , 2000 );
			setInterval( 'blink_ledz()' , 600 );
	});
	
	
	var stale_archive_time_trigger = 25; // If this time ellapses without archive size increasing warn user that something may have gone wrong.
	var stale_archive_time_trigger_increment = 1; // Number of times the popup has been shown.
	
	keep_polling = 1;
	pb_blink_status = 1;
	var last_archive_change = 0; // Time where archive size last changed.
	var last_archive_size = ''; // Last archive size string.
	
	function blink_ledz( this_status ) {
		if ( pb_blink_status == 1 ) {
			jQuery( '.pb_backupbuddy_blinkz' ).addClass( 'pb_backupbuddy_empty' );
			jQuery( '.pb_backupbuddy_blinkz' ).removeClass( 'pb_backupbuddy_glow' );
			pb_blink_status = 0;
		} else {
			jQuery( '.pb_backupbuddy_blinkz' ).addClass( 'pb_backupbuddy_glow' );
			jQuery( '.pb_backupbuddy_blinkz' ).removeClass( 'pb_backupbuddy_empty' );
			pb_blink_status = 1;
		}
	}
	
	function unix_timestamp() {
		return Math.round( ( new Date() ).getTime() / 1000 );
	}
	
	function backupbuddy_poll_altcron() {
		if ( keep_polling != 1 ) {
			return;
		}
		
		jQuery.get(
			'<?php echo admin_url('admin.php').'?page=pluginbuddy_backupbuddy&pb_backupbuddy_alt_cron=true'; ?>',
			function(data) {
			}
		);
	}
	
	function backupbuddy_poll() {
		if ( keep_polling != 1 ) {
			return;
		}
		
		//alert( 'string: ' + ( last_archive_size ) + '; timediff: ' + ( unix_timestamp() - last_archive_change ) );
		// Check to make sure archive size is increasing. Warn if it seems to hang.
		if ( ( last_archive_change != 0 ) && ( ( ( unix_timestamp() - last_archive_change ) > stale_archive_time_trigger ) ) ) {
			if ( stale_archive_time_trigger == 25 ) {
				alert( "Warning: The backup archive file size has not increased in " + stale_archive_time_trigger + " seconds. The backup may have failed.\nSubsequent notifications will be displayed in the status window." );
				jQuery( '#backupbuddy_messages' ).append( "\n* Warning: The backup archive file size has not increased in " + stale_archive_time_trigger + " seconds. The backup may have failed." );
			} else {
				jQuery( '#backupbuddy_messages' ).append( "\n* Warning: The backup archive file size has not increased in " + round( stale_archive_time_trigger / 60 ) + " minutes. The backup may have failed." );
			}
			textareaelem = document.getElementById( 'backupbuddy_messages' );
			textareaelem.scrollTop = textareaelem.scrollHeight;
			messages_output = '';
			
			stale_archive_time_trigger = 60 * 5 * stale_archive_time_trigger_increment;
			stale_archive_time_trigger_increment++;
		}
		
		jQuery('#pb_backupbuddy_loading').show();
		jQuery.ajax({
			url:	'<?php echo pb_backupbuddy::ajax_url( 'backup_status' ); ?>',
			type:	'post',
			data:	{ serial: '<?php echo $serial_override; //pb_backupbuddy::$classes['backup']->_backup['serial']; ?>', action: 'pb_backupbuddy_backup_status' },
			context: document.body,
			success: function( data ) {
						jQuery('#pb_backupbuddy_loading').hide();
						
						data = data.split( "\n" );
						for( var i = 0; i < data.length; i++ ) {
							messages_output = '';
							// 0      1         2             3      4
							// TIME|~|TIME_IN|~|PEAK_MEMORY|~|TYPE|~|MESSAGE
							
							if ( data[i].substring( 0, 1 ) == '!' ) { // Expected command since it begins with `!`.
								data[i] = data[i].substring(1); // Strip exclamation point.
								//alert( data[i] );
								line = data[i].split( "|~|" );
								
								//Convert timestamp to readable format. Server timestamp based on GMT so undo localization with offset.
								var date = new Date();
								var date = new Date(  ( line[0] * 1000 ) + date.getTimezoneOffset() * 60000 );
								var seconds = date.getSeconds();
								if ( seconds < 10 ) {
									seconds = '0' + seconds;
								}
								date = date.getHours() + ':' + date.getMinutes() + ':' + seconds;
								
								// Process commands.
								if ( line[3] == 'message' ) {
									messages_output = date + "\t" + line[1] + "sec\t" + line[2] + "MB\t" + line[3] + "\t" + line[4];
								} else if ( line[3] == 'error' ) { // Process errors.
									messages_output = date + "\t" + line[1] + "sec\t" + line[2] + "MB\t" + line[3] + "\t\t" + line[4];
								} else if ( line[3] == 'ping' ) { // Ping.
									messages_output = date + ': Ping. Waiting for server . . .';
								} else if ( line[3] == 'action' ) { // Process action commands.
									action_line = line[4].split( "^" );
									if ( action_line[0] == 'archive_size' ) { // Process action sub-commands.
										if ( last_archive_size != action_line[1] ) { // Track time archive size last changed.
											last_archive_size = action_line[1];
											last_archive_change = unix_timestamp();
										}
										jQuery( '.backupbuddy_archive_size' ).html( action_line[1] );
									} else if ( action_line[0] == 'finish_settings' ) {
										jQuery( '#pb_backupbuddy_slot1_led' ).removeClass( 'pb_backupbuddy_blinkz' ); // disable blinking
										jQuery( '#pb_backupbuddy_slot1_led' ).removeClass( 'pb_backupbuddy_empty' ); // Remove empty LED hole.
										jQuery( '#pb_backupbuddy_slot1_led' ).addClass( 'pb_backupbuddy_glow' ); // Solid LED.
										
									} else if ( action_line[0] == 'start_database' ) {
										jQuery( '#pb_backupbuddy_slot2_led' ).addClass( 'pb_backupbuddy_activate' ); // Light BG
										jQuery( '#pb_backupbuddy_slot2_step' ).addClass( 'pb_backupbuddy_activate' ); // Light BG
										//jQuery( '#pb_backupbuddy_slot2_led' ).addClass( 'pb_backupbuddy_glow' ); // enable blinking
										jQuery( '#pb_backupbuddy_slot2_led' ).addClass( 'pb_backupbuddy_blinkz' ); // enable blinking
									} else if ( action_line[0] == 'finish_database' ) {
										jQuery( '#pb_backupbuddy_slot2_led' ).removeClass( 'pb_backupbuddy_blinkz' ); // Remote blinkage.
										jQuery( '#pb_backupbuddy_slot2_led' ).removeClass( 'pb_backupbuddy_empty' ); // Remove empty LED hole.
										jQuery( '#pb_backupbuddy_slot2_led' ).addClass( 'pb_backupbuddy_glow' ); // Solid LED.
										
									} else if ( action_line[0] == 'start_files' ) {
										jQuery( '#pb_backupbuddy_slot3_led' ).addClass( 'pb_backupbuddy_blinkz' ); // Remote blinkage.
										jQuery( '#pb_backupbuddy_slot3_led' ).addClass( 'pb_backupbuddy_activate' ); // Light BG
										jQuery( '#pb_backupbuddy_slot3_step' ).addClass( 'pb_backupbuddy_activate' ); // Light BG
										jQuery( '#pb_backupbuddy_slot3' ).addClass( 'light' ); // lighten the bg
										jQuery( '#pb_backupbuddy_slot3_header' ).addClass( 'light' ); // use text made for lighter bg
									} else if ( action_line[0] == 'finish_backup' ) {
										jQuery( '#pb_backupbuddy_slot3_led' ).removeClass( 'pb_backupbuddy_blinkz' ); // Remote blinkage.
										jQuery( '#pb_backupbuddy_slot3_led' ).removeClass( 'pb_backupbuddy_empty' ); // Remove empty LED hole.
										jQuery( '#pb_backupbuddy_slot3_led' ).addClass( 'pb_backupbuddy_glow' ); // Solid LED.
										jQuery( '#pb_backupbuddy_slot4_led' ).removeClass( 'pb_backupbuddy_empty' ); // Remove empty LED hole.
										jQuery( '#pb_backupbuddy_slot4_led' ).addClass( 'pb_backupbuddy_win' ); // set checkmark
										keep_polling = 0; // Stop polling server for status updates.
									} else if ( action_line[0] == 'archive_url' ) {
										<?php if ( defined( 'PB_DEMO_MODE' ) ) { ?>
											jQuery( '#pb_backupbuddy_archive_download' ).slideDown();
										<?php } else { ?>
											jQuery( '#pb_backupbuddy_archive_url' ).attr( 'href', action_line[1] );
											jQuery( '#pb_backupbuddy_archive_download' ).slideDown();
										<?php } ?>
									} else if ( action_line[0] == 'halt_script' ) {
										jQuery( '.pb_backupbuddy_blinkz' ).css( 'background-position', 'top' ); // turn off led
										jQuery( '#pb_backupbuddy_slot1_led' ).removeClass( 'pb_backupbuddy_blinkz' ); // disable blinking
										jQuery( '#pb_backupbuddy_slot2_led' ).removeClass( 'pb_backupbuddy_blinkz' ); // disable blinking
										jQuery( '#pb_backupbuddy_slot3_led' ).removeClass( 'pb_backupbuddy_blinkz' ); // disable blinking
										jQuery( '#pb_backupbuddy_slot4_led' ).removeClass( 'pb_backupbuddy_empty' ); // Remove empty LED hole.
										jQuery( '#pb_backupbuddy_slot4_led' ).addClass( 'pb_backupbuddy_codered' ); // set checkmark
										keep_polling = 0; // Stop polling server for status updates.
										messages_output = '*** A fatal error has been encountered.  The backup has halted.';
										alert( '<?php _e('A fatal error has been encountered.  The backup has halted.', 'it-l10n-backupbuddy' );?>' );
									} else {
										messages_output = '<?php _e('Unknown action', 'it-l10n-backupbuddy' );?>: ' + action_line[0] + "\n";
									}
								} else { // Unknown command so send to details.
									messages_output = date + "\t" + line[1] + "sec\t" + line[2] + "MB\t" + line[3] + "\t" + line[4];
								}
								
								// Mirror mesages to details if no details have been set so far.
								if ( messages_output == '' ) {
									messages_output = messages_output;
								}
							} else { // Unrecognized command since it does not begin with `!`. Possible PHP error.
								messages_output = data[i];
							}
							
							
							// Display details.
							if ( messages_output != '' ) {
								jQuery( '#backupbuddy_messages' ).append( "\n" + messages_output );
								textareaelem = document.getElementById( 'backupbuddy_messages' );
								textareaelem.scrollTop = textareaelem.scrollHeight;
								messages_output = '';
							}
						}
						
						// Set the next server poll if applicable.
						setTimeout( 'backupbuddy_poll()' , 3000 );
						<?php // Handles alternate WP cron forcing.
						if ( defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ) {
							echo '	setTimeout( \'backupbuddy_poll_altcron()\' , 3000 );';
						}
						?>
					 },
			complete: function( jqXHR, status ) {
				if ( ( status != 'success' ) && ( status != 'notmodified' ) ) {
					jQuery('#pb_backupbuddy_loading').hide();
				}
			}
		});
	}
</script>

<style type="text/css">
	.pb_backupbuddy_status {
		background: #636363 url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/status/bg_dark.png') top repeat-x;
		border: 1px solid #636363;
		min-width: 20px;
		height: 29px;
		float: left;
		padding-bottom: 8px;
		text-align: right;
		-moz-border-radius: 8px 0 0 8px;
		border-radius: 8px 0 0 8px;
		margin-top: 20px;
		z-index: 0;
		position: relative;
	}
	
	
	.pb_backupbuddy_progress {
		background: #ECECEC;
		margin: 0;
		display: inline-block;
		border-radius: 5px;
		padding: 0;
		border-radius: 5px;
		border: 1px solid #d6d6d6;
		border-top: 1px solid #ebebeb;
		box-shadow: 0px 3px 0px 0px #aaaaaa;
		box-shadow: 0px 2px 0px 0px #CFCFCF;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
		font-size: 1.3em;
	}
	.pb_backupbuddy_step {
	/*	background: url('blue.png') 0 5px no-repeat; */
		padding: 12px 30px 12px 45px;
		width: 120px;
		color: #464646;
		display: block;
		float: left;
	}
	.pb_backupbuddy_step.pb_backupbuddy_settings {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/settings.png') 6px 4px no-repeat;
	}
	.pb_backupbuddy_step.pb_backupbuddy_database {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/database.png') 6px 4px no-repeat;
	}
	.pb_backupbuddy_step.pb_backupbuddy_files {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/files.png') 6px 4px no-repeat;
	}
	.pb_backupbuddy_glow {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/blue.png') -4px 1px no-repeat;
		width: 35px;
		height: 40px;
		float: left;
		border-right: 1px solid #d6d6d6;
	}
	.pb_backupbuddy_step.pb_backupbuddy_end {
		width: 20px;
		height: 18px;
		padding: 11px;
		border: none;
		border-radius: 0 4px 4px 0;
	}
	.pb_backupbuddy_empty {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/empty.png') -4px 1px no-repeat;
		width: 35px;
		height: 40px;
		float: left;
		border-right: 1px solid #d6d6d6;
	}
	.pb_backupbuddy_end.pb_backupbuddy_empty {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/empty.png') 1px 1px no-repeat;
	}
	.pb_backupbuddy_step.pb_backupbuddy_end.pb_backupbuddy_win {
		background: #ffffff url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/green.png') 1px 1px no-repeat;
	}
	.pb_backupbuddy_step.pb_backupbuddy_end.pb_backupbuddy_fail {
		background: #ffffff url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/yellow.png') 1px 1px no-repeat;
	}
	.pb_backupbuddy_step.pb_backupbuddy_end.pb_backupbuddy_codered {
		background: #ffffff url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/status/red.png') 1px 1px no-repeat;
	}
	.pb_backupbuddy_activate {
		background-color: #fff !important;
	}
	.pb_backupbuddy_afterbackupoptionswp {
		margin: 10px 0;
	}
	.pb_backupbuddy_afterbackupoptions a {
		font-size: 16px;
		color: #21759B;
		margin-right: 20px;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
		text-decoration: none;
	}
	.pb_backupbuddy_afterbackupoptionswp a {
		background: #f5f5f5;
		text-shadow: rgba(255, 255, 255, 1) 0 1px 0;
		border: 1px solid #BBB;
		border-radius: 11px;
		color: #464646;
		text-decoration: none;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
		font-size: 12px;
		line-height: 13px;
		padding: 3px 8px;
		cursor: pointer;
	}
	
</style>


<br />






<div>

	<div style="margin-left: 20px;" class="pb_progress">
	
		
		
		<div class="pb_backupbuddy_progress">
			
			<span class="pb_backupbuddy_step pb_backupbuddy_settings pb_backupbuddy_activate" id="pb_backupbuddy_slot1_step"><?php _e('Settings Export', 'it-l10n-backupbuddy' );?></span>
			<span class="pb_backupbuddy_empty pb_backupbuddy_blinkz pb_backupbuddy_activate" id="pb_backupbuddy_slot1_led"></span>
			
			<span class="pb_backupbuddy_step pb_backupbuddy_database" id="pb_backupbuddy_slot2_step"><?php _e('Database Export', 'it-l10n-backupbuddy' );?></span>
			<span class="pb_backupbuddy_empty" id="pb_backupbuddy_slot2_led"></span>
			
			<span class="pb_backupbuddy_step pb_backupbuddy_files" id="pb_backupbuddy_slot3_step"><?php _e('Files Export', 'it-l10n-backupbuddy' );?></span>
			<span class="pb_backupbuddy_empty" id="pb_backupbuddy_slot3_led"></span>
			
			<span class="pb_backupbuddy_step pb_backupbuddy_end pb_backupbuddy_empty" id="pb_backupbuddy_slot4_led"></span>
			
		</div>
		
		
	
	</div>
	
	<div style="clear: both;"></div>
	<br />
	<div id="pb_backupbuddy_archive_download" style="display: none; width: 793px; text-align: center;">
		<a id="pb_backupbuddy_archive_url" href="#" class="button-primary"><?php _e('Download backup ZIP archive', 'it-l10n-backupbuddy' ); if ( defined( 'PB_DEMO_MODE' ) ) { echo ' [demo mode]'; } ?> (<span class="backupbuddy_archive_size">0 MB</span>)</a>
		&nbsp;&nbsp;&nbsp;
		<a href="<?php echo pb_backupbuddy::page_url(); ?>" class="button secondary-button">&larr; <?php _e( 'back to backups', 'it-l10n-backupbuddy' );?></a>
	</div>
	<br />
	
	<table width="793"><tr>
		<td><span style="font-size: 1.17em; font-weight: bold;"><?php _e('Status', 'it-l10n-backupbuddy' );?></span></td>
		<td width="16"><span id="pb_backupbuddy_loading" style="display: none; margin-left: 10px;"><img src="<?php echo pb_backupbuddy::plugin_url();; ?>/images/loading.gif" <?php echo 'alt="', __('Loading...', 'it-l10n-backupbuddy' ),'" title="',__('Loading...', 'it-l10n-backupbuddy' ),'"';?> width="16" height="16" style="vertical-align: -3px;" /></span></td>
		<td width="100%" align="right"><?php _e('Archive size', 'it-l10n-backupbuddy' );?>: <span class="backupbuddy_archive_size">0 MB</span> <?php pb_backupbuddy::tip( __('This is the current size of the backup archive as it is being generated. This size will grow until the backup is complete.','it-l10n-backupbuddy' ) ); ?></td>
	</tr></table>
	
	<textarea wrap="off" readonly="readonly" id="backupbuddy_messages" cols="75" rows="13" style="width: 793px;"><?php _e('Backing up with BackupBuddy', 'it-l10n-backupbuddy' );?> v<?php echo pb_backupbuddy::settings( 'version' ); ?>...</textarea>
	
	<br><br>
	<!-- <a href="#" class="button-secondary pb_backupbuddy_sendlog" rel="backupbuddy_messages">Send Log to Support</a><br><br> -->
	<br><br>
	
	<div class="description" style="width: 793px; text-align: center;">
		<?php
		if ( pb_backupbuddy::$options['backup_mode'] == '1' ) { // Classic mode (all in one page load).
			_e('Running in CLASSIC mode. Leaving this page before the backup completes will lead to a failed backup.', 'it-l10n-backupbuddy' );
		} else {
			_e('You may leave this page at any time and the backup will continue uninterrupted.', 'it-l10n-backupbuddy' );
		}
		?>
	</div>

</div>

<br /><br />


<?php
flush(); // Output HTML in case there is processing delay... Especially needed in classic mode since full backup must happen in page load.

$export_plugins = array(); // Default of no exported plugins. Used by MS export.
if ( pb_backupbuddy::_GET( 'backupbuddy_backup' ) == 'export' ) {
	$export_plugins = pb_backupbuddy::_POST( 'items' );
}

if ( pb_backupbuddy::$classes['backup']->start_backup_process( pb_backupbuddy::_GET( 'backupbuddy_backup' ), 'manual', array(), array(), '', $serial_override, $export_plugins ) !== true ) {
	echo __('Error #4344443: Backup failure', 'it-l10n-backupbuddy' );
	echo pb_backupbuddy::$classes['backup']->get_errors();
}
?>
