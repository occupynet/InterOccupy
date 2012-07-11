<?php
pb_backupbuddy::$ui->title( 'Backup Site' . ' ' . pb_backupbuddy::video( '9ZHWGjBr84s', __('Backups page tutorial', 'it-l10n-backupbuddy' ), false ) );


/*
wp_enqueue_style('dashboard');
wp_print_styles('dashboard');
wp_enqueue_script('dashboard');
wp_print_scripts('dashboard');
*/
wp_enqueue_script( 'thickbox' );
wp_print_scripts( 'thickbox' );
wp_print_styles( 'thickbox' );
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		
		jQuery( '.pb_backupbuddy_hoveraction_send' ).click( function(e) {
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'destination_picker' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&sending=1&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		jQuery( '.pb_backupbuddy_hoveraction_hash' ).click( function(e) {
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'hash' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		
		
		jQuery( '.pb_backupbuddy_hoveraction_note' ).click( function(e) {
			
			var existing_note = jQuery(this).parents( 'td' ).find('.pb_backupbuddy_notetext').text();
			if ( existing_note == '' ) {
				existing_note = 'My first backup';
			}
			
			var note_text = prompt( '<?php _e( 'Enter a short descriptive note to apply to this archive for your reference. (175 characters max)', 'it-l10n-backupbuddy' ); ?>', existing_note );
			if ( ( note_text == null ) || ( note_text == '' ) ) {
				// User cancelled.
			} else {
				jQuery( '.pb_backupbuddy_backuplist_loading' ).show();
				jQuery.post( '<?php echo pb_backupbuddy::ajax_url( 'set_backup_note' ); ?>', { backup_file: jQuery(this).attr('rel'), note: note_text }, 
					function(data) {
						data = jQuery.trim( data );
						jQuery( '.pb_backupbuddy_backuplist_loading' ).hide();
						if ( data != '1' ) {
							alert( '<?php _e('Error', 'it-l10n-backupbuddy' );?>: ' + data );
						}
						javascript:location.reload(true);
					}
				);
			}
			return false;
		});
		
		
		
	});
	
	function pb_backupbuddy_selectdestination( destination_id, destination_title, callback_data ) {
		if ( callback_data != '' ) {
			jQuery.post( '<?php echo pb_backupbuddy::ajax_url( 'remote_send' ); ?>', { destination_id: destination_id, destination_title: destination_title, file: callback_data, trigger: 'manual' }, 
				function(data) {
					data = jQuery.trim( data );
					if ( data != '1' ) {
						alert( '<?php _e('Error starting remote send', 'it-l10n-backupbuddy' ). ': ';?>' + data );
					} else {
						alert( '<?php _e('Your file has been scheduled to be sent now. It should arrive shortly.', 'it-l10n-backupbuddy' ); ?>' );
					}
				}
			);
			
			/* Try to ping server to nudge cron along since sometimes it doesnt trigger as expected. */
			jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>',
				function(data) {
				}
			);

		} else {
			//window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&custom=remoteclient&destination_id=' + destination_id;
			window.location.href = '<?php
			if ( is_network_admin() ) {
				echo network_admin_url( 'admin.php' );
			} else {
				echo admin_url( 'admin.php' );
			}
			?>?page=pb_backupbuddy_backup&custom=remoteclient&destination_id=' + destination_id;
		}
	}
	
	
	/*
	function pb_backupbuddy_selectdestination( destination_id, destination_title, callback_data ) {
		window.location.href = '<?php echo admin_url( 'admin.php' ); ?>?page=pb_backupbuddy_backup&custom=remoteclient&destination_id=' + destination_id;
	}
	*/
</script>

<style> 
	.therightspot {
		margin: 105px 0 0 158px;
		background: #fff;
		display: block;
		height: 160px;
		width: 985px;
	}
	
	.duo-button {
		background: #f5f5f5;
		background: #ECECEC;
		margin: 0;
		display: inline-block;
		border-radius: 5px;
		padding: 9px 10px;
		border-radius: 5px;
		border: 1px solid #d6d6d6;
		border-top: 1px solid #ebebeb;
		box-shadow: 0px 3px 0px 0px #aaaaaa;
		box-shadow: 0px 3px 0px 0px #CFCFCF;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
	}
	.duo-button .choose {
		font-size: 20px;
		font-family: "HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",sans-serif;
		padding: 5px 0 15px 5px;
		color: #464646;
	}
	.duo-button a {
		font-size: 18px;
		line-height: 21px;
		display: block;
		float: left;
		margin: 0;
		text-decoration: none;
		background: #fff;
		border: 1px solid #CFCFCF;
		border-top: 1px solid #ebebeb;
		border-bottom: 1px solid #c9c9c9;
		border-radius: 2px;
		padding: 20px 30px;
		color: #666;
	}
	.duo-button a:hover {
		box-shadow: inset 0 1px 8px #aaaaaa;
		background: #fff;
	}
	.duo-button a:active {
		color: #fff;
		background: #da2828;
		background: #da2828 url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/red-grad.png') top repeat-x;
		box-shadow: inset 0 1px 4px #561818;
		text-shadow: 0 -1px #561818;
	}
	.duo-button .left {
		border-radius: 4px 0 0 4px;
		border-right: 1px solid #d6d6d6;
	}
	.duo-button .right {
		border-radius: 0 4px 4px 0;
		border-left: none;
	}
	.backupbutton {
		background: url('<?php echo pb_backupbuddy::plugin_url();; ?>/images/press.png') top no-repeat;
		width: 400px;
		height: 32px;
		display: block;
		margin: 12px auto 0;
	}
	.backupbutton:active {
		background-position: bottom;
	}
	
	
	
	.step {
	/*	background: url('blue.png') 0 5px no-repeat; */
		padding: 11px 30px 11px 45px;
		width: 120px;
		color: #464646;
		display: block;
		float: left;
	}
	.step.settings {
		background: url('settings.png') 6px 4px no-repeat;
	}
	.step.database {
		background: url('database.png') 6px 4px no-repeat;
	}
	.step.files {
		background: url('files.png') 6px 4px no-repeat;
	}
	.glow {
		background: url('blue.png') -4px 1px no-repeat;
		width: 35px;
		height: 41px;
		float: left;
		border-right: 1px solid #d6d6d6;
	}
	.step.end {
		width: 20px;
		height: 19px;
		padding: 11px;
		border: none;
		border-radius: 0 4px 4px 0;
	}
	.empty {
		background: url('empty.png') -4px 1px no-repeat;
		width: 35px;
		height: 41px;
		float: left;
		border-right: 1px solid #d6d6d6;
	}
	.end.empty {
		background: url('empty.png') 1px 1px no-repeat;
	}
	.step.end.win {
		background: #ffffff url('green.png') 1px 1px no-repeat;
	}
	.step.end.fail {
		background: #ffffff url('yellow.png') 1px 1px no-repeat;
	}
	.step.end.codered {
		background: #ffffff url('red.png') 1px 1px no-repeat;
	}
	.activate {
		background-color: #fff !important;
	}
	.afterbackupoptionswp {
		margin: 10px 0;
	}
	.afterbackupoptions a {
		font-size: 16px;
		color: #21759B;
		margin-right: 20px;
		font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
		text-decoration: none;
	}
	.afterbackupoptionswp a {
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





<div class="duo-button">
	<div class="choose"><?php _e( 'Choose a backup type', 'it-l10n-backupbuddy' ) ?>:</div>
	<a class="left" title="<?php _e( 'Just your database. I like your minimalist style.', 'it-l10n-backupbuddy' ); ?>" href="<?php echo pb_backupbuddy::page_url(); ?>&backupbuddy_backup=db"><?php _e( 'Database Only', 'it-l10n-backupbuddy' ); ?></a>
	<a class="right" title="<?php _e( 'A complete backup. I\'ll spare no expense. The database, media library, photos, non-WP files, themes, plugins, the kitchen sink, all the marbles, and that sock you lost in 1996.', 'it-l10n-backupbuddy' ); ?>" href="<?php echo pb_backupbuddy::page_url(); ?>&backupbuddy_backup=full"><?php _e( 'Complete Backup', 'it-l10n-backupbuddy' ); ?></a>
	<div class="clearfix"></div>
</div>






<br style="clear: both;"><br><br><br>

<?php
flush();







/********** START TABS **********/


pb_backupbuddy::$ui->start_tabs(
	'backup_locations',
	array(
		array(
			'title'		=>		'Local Archives',
			'slug'		=>		'local',
		),
		array(
			'title'		=>		'Remote Archives',
			'slug'		=>		'remote',
		),
	),
	'width: 100%;'
);







pb_backupbuddy::$ui->start_tab( 'local' );
$listing_mode = 'default';
require_once( '_backup_listing.php' );
pb_backupbuddy::$ui->end_tab();








pb_backupbuddy::$ui->start_tab( 'remote' );
echo '<div style="padding-left: 12px; padding-right: 12px;">';
_e( 'You may view, delete, or download remote backups from your remote destinations by clicking on the button below. In the box that pops up select a remote destination by clicking "Select this destination" to manage its backup archives.', 'it-l10n-backupbuddy' );

echo '<br><br><br>';

echo '<a href="' . pb_backupbuddy::ajax_url( 'destination_picker' ) . '&#038;TB_iframe=1&#038;width=640&#038;height=600" class="thickbox button secondary-button" style="margin-top: 3px;" title="' . __( 'Manage Remote Destinations & Archives', 'it-l10n-backupbuddy' ) . '">' . __('Manage Remote Destinations & Archives', 'it-l10n-backupbuddy' ) . '</a>';
echo '&nbsp;&nbsp;&nbsp;';
_e('Manage Amazon S3, Rackspace Cloudfiles, Email, and FTP.', 'it-l10n-backupbuddy' );

echo '</div>';

echo '<br><br>';
pb_backupbuddy::$ui->end_tab();




pb_backupbuddy::$ui->end_tabs();


/********** END TABS **********/











echo '<br /><br />';
?>





<?php
// Handles thickbox auto-resizing. Keep at bottom of page to avoid issues.
if ( !wp_script_is( 'media-upload' ) ) {
	wp_enqueue_script( 'media-upload' );
	wp_print_scripts( 'media-upload' );
}
?>