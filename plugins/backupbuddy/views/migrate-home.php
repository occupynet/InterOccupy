<script type="text/javascript">
	jQuery(document).ready(function() {
		
		jQuery( '.pb_backupbuddy_hoveraction_migrate' ).click( function(e) {
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'migration_picker' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&migrate=1&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		jQuery( '.pb_backupbuddy_hoveraction_hash' ).click( function(e) {
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'hash' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		jQuery( '.pb_backupbuddy_get_importbuddy' ).click( function(e) {
			<?php
			if ( pb_backupbuddy::$options['importbuddy_pass_hash'] == '' ) {
				echo 'alert(\'' . __( 'Please set an ImportBuddy password on the BackupBuddy Settings page to download this script. This is required to prevent unauthorized access to the script when in use.', 'it-l10n-backupbuddy' ) . '\');';
				echo 'return false;';
			} else {
				echo "window.location.href = '" . pb_backupbuddy::ajax_url( 'importbuddy' ) . "';";
				echo 'return false;';
			}
			?>
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
			jQuery.post( '<?php echo pb_backupbuddy::ajax_url( 'remote_send' ); ?>', { destination_id: destination_id, destination_title: destination_title, file: callback_data, trigger: 'migration', send_importbuddy: '1' }, 
				function(data) {
					data = jQuery.trim( data );
					if ( data != '1' ) {
						alert( '<?php _e('Error starting remote send of file to migrate', 'it-l10n-backupbuddy' ). ': ';?>' + data );
					} else {
						window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&destination=' + destination_id + '&destination_title=' + destination_title + '&callback_data=' + callback_data;
					}
				}
			);
			
			/* Try to ping server to nudge cron along since sometimes it doesnt trigger as expected. */
			jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>',
				function(data) {
				}
			);

		} else {
			window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&custom=remoteclient&destination_id=' + destination_id;
		}
	}
	
</script>



<style> 
	.graybutton {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/grays2.png) top repeat-x;
		min-width: 158px;
		height: 138px;
		display: block;
		float: left;
		-moz-border-radius: 6px;
		border-radius: 6px;
		border: 1px solid #c9c9c9;
		margin-bottom: 3px;
	}
	.graybutton:hover {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/grays2.png) bottom repeat-x;
		border: 1px solid #aaaaaa;
	}
	.graybutton:active {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/grays2.png) bottom repeat-x;
		border: 1px solid transparent;
	}
	.leftround {
		-moz-border-radius: 4px 0 0 4px;
		border-radius: 4px 0 0 4px;
		border-right: 1px solid #c9c9c9;
	}
	.rightround {
		-moz-border-radius: 0 4px 4px 0;
		border-radius: 0 4px 4px 0;
	}
	.dbonlyicon {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/dbonly-icon.png);
		width: 60px;
		height: 60px;
		margin: 15px auto 0 auto;
		display: block;
		float: center;
	}
	.allcontenticon {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/allcontent-icon.png);
		width: 60px;
		height: 60px;
		margin: 15px auto 0 auto;
		display: block;
		float: center;
	}
	.restoremigrateicon {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/restoremigrate-icon.png);
		width: 60px;
		height: 60px;
		margin: 15px auto 0 auto;
		display: block;
		float: center;
	}
	.repairbuddyicon {
		background: url(<?php echo pb_backupbuddy::plugin_url(); ?>/images/buttons/allcontent-icon.png);
		width: 60px;
		height: 60px;
		margin: 15px auto 0 auto;
		display: block;
		float: center;
	}
	.bbbutton-text {
		font-family: Georgia, Times, serif;
		font-size: 18px;
		font-style: italic;
		min-width: 158px;
		text-align: center;
		
		/* line-height: 60px; */
		padding: 13px;
		
		color: #666666;
		text-shadow: 1px 1px 1px #ffffff;
		clear: both;
		display: inline-block;
	}
	.bbbutton-smalltext {
		font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
		font-size: 9px;
		font-style: normal;
		text-shadow: 0;
		padding-top: 3px;
	}
</style>
<?php
pb_backupbuddy::$ui->start_metabox( 'Manual Migration' . ' ' . pb_backupbuddy::video( 'jvL1X9w-CUY', __('Manual migration', 'it-l10n-backupbuddy' ), false ), true, 'width: 100%;' );
	?>
	
	
	<div style="float: right; margin-left: 12px;">
		<div style="display: inline-block;">
			<a href="<?php echo pb_backupbuddy::ajax_url( 'importbuddy' ); ?>" style="text-decoration: none;" title="BETA! <?php _e('Download the restore & migration utility, importbuddy.php', 'it-l10n-backupbuddy' ); ?>">
				<div style="position: absolute; width: 382px;">
					<span style="position: absolute; z-index: 42; right: 0px; display: inline-block;">
						<img src="<?php echo pb_backupbuddy::plugin_url(); ?>/images/beta.png" title="Beta" width="60" height="60">
					</span>
				</div>
			</a>
			
			<?php
				if ( pb_backupbuddy::$options['importbuddy_pass_hash'] == '' ) {
					echo '<a onclick="alert(\'' . __( 'Please set an ImportBuddy password on the BackupBuddy Settings page to download this script. This is required to prevent unauthorized access to the script when in use.', 'it-l10n-backupbuddy' ) . '\'); return false;" href="" style="text-decoration: none;" title="' . __( 'Download the restore & migration utility, importbuddy.php', 'it-l10n-backupbuddy' ) . '">';
				} else {
					echo '<a href="' . pb_backupbuddy::ajax_url( 'importbuddy' ) . '" style="text-decoration: none;" title="' . __('Download the restore & migration utility, importbuddy.php', 'it-l10n-backupbuddy' ) . '">';
				}
			?>
				<div class="graybutton">
					<div class="restoremigrateicon"></div>
					<div class="bbbutton-text">
						<?php _e('ImportBuddy', 'it-l10n-backupbuddy' );?><br />
						<div class="bbbutton-smalltext"><?php _e('restoring & migration script', 'it-l10n-backupbuddy' );?></div>
					</div>
				</div>
			</a>
			
			<?php
				if ( pb_backupbuddy::$options['repairbuddy_pass_hash'] == '' ) {
					echo '<a onclick="alert(\'' . __( 'Please set a RepairBuddy password on the BackupBuddy Settings page to download this script. This is required to prevent unauthorized access to the script when in use.', 'it-l10n-backupbuddy' ) . '\'); return false;" href="" style="text-decoration: none;" title="' . __( 'Download the troubleshooting & repair script, repairbuddy.php', 'it-l10n-backupbuddy' ) . '">';
				} else {
					echo '<a href="' . admin_url( 'admin-ajax.php' ) . '?action=pb_backupbuddy_repairbuddy" style="text-decoration: none;" title="' . __('Download the troubleshooting & repair script, repairbuddy.php', 'it-l10n-backupbuddy' ) . '">';
				}
			?>
				<span class="graybutton" style="margin-left: 10px;">
					<span class="repairbuddyicon"></span>
					<span class="bbbutton-text">
						<?php _e('RepairBuddy', 'it-l10n-backupbuddy' );?><br />
						<span class="bbbutton-smalltext"><?php _e('troubleshooting & repair script', 'it-l10n-backupbuddy' );?></span>
					</span>
				</span>
			</a>
			
		</div>
		
	</div>
	
	
	
	Manually migrate or restore your site with the importbuddy.php script.
	This is a step-by-step process with instructions along the way.
	Keep a copy of this script with your backups for restoring sites directly from backups.
	For a more automated migration process you may select a backup from the "Automated Migration"
	section below.
	<ol>
		<li>Download the <a href="#" class="pb_backupbuddy_get_importbuddy">importbuddy.php script</a>.</li>
		<li>Upload importbuddy.php & backup ZIP file to the destination site location.</li>
		<li>Navigate to the uploaded importbuddy.php in your web browser.</li>
		<li>Follow the on-screen directions until the restore / migration is complete.</li>
	</ol>
	<br class="clearfix">
	<?php
pb_backupbuddy::$ui->end_metabox();




pb_backupbuddy::$ui->start_metabox( 'Automated Migration' . ' ' . pb_backupbuddy::video( 'uSBvBSfSjWM', __('Automated migration', 'it-l10n-backupbuddy' ), false ), true, 'width: 100%;' );
	?>
	Automated migration allows you to quickly <b>migrate full backups to another location</b> such as another server or another directory on this server.
	Your backup archive and the ImportBuddy script will automatically be transferred to the destination and run.
	This feature cannot be used to restore a site back to the same location over this site.
	<?php
	if ( count( $backups ) > 0 ) { // $backups set in the controller as view data.
		_e( 'Hover over the backup below you would like to migrate and select "Migrate this backup" to begin the automated migration process.', 'it-l10n-backupbuddy' );
		echo '<br><br>';
	} else {
		echo '<br>';
		_e( 'You must create a backup prior to migrating this site.', 'it-l10n-backupbuddy' );
		echo '<br>';
	}
	
	$listing_mode = 'migrate';
	require_once( '_backup_listing.php' );
	echo '<br>';
pb_backupbuddy::$ui->end_metabox();
?>