<style type="text/css">
	.pb_backupbuddy_big_button {
		display: block;
		padding: 15px;
		width: 60%;
		
		height: 50px;
		line-height: 50px;
		font-size: 1.2em;
		
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
		
		background: #F5F5F5;
		border: 1px solid #DFDFDF;
		
		text-align: center;
		text-decoration: none;
		
		margin-bottom: 12px;
		margin-left: auto;
		margin-right: auto;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.pb_backupbuddy_remotetest').click(function(e) {
			jQuery( '.pb_backupbuddy_loading' ).show();
			jQuery.post( jQuery(this).attr( 'alt' ), { '#type': 'local', path: jQuery('#pb_backupbuddy_path').val(), url: jQuery('#pb_backupbuddy_url').val() }, 
				function(data) {
					jQuery( '.pb_backupbuddy_loading' ).hide();
					alert( data );
				}
			); //,"json");
			jQuery(this).html('Test these settings');
			return false;
		});
	});
</script>



<?php
if ( pb_backupbuddy::_GET( 'picker_type' ) == '' ) {
	?>
	<h3>Automated Migration</h3>
	
	<p>
		To begin the automated migration of a site backup you must select where you want to migrate your
		files to. 	Please select the location / method to send your site's backup archive & files below.
		After the file transfer is complete the migration will begin.
	</p>
	
	<a href="<?php echo pb_backupbuddy::ajax_url( 'destination_picker' ); ?>&callback_data=<?php echo htmlentities( pb_backupbuddy::_GET( 'callback_data' ) ); ?>&migrate=1" class="pb_backupbuddy_big_button">
		Send via existing or new remote FTP destination
	</a>
	
	<a href="<?php echo pb_backupbuddy::ajax_url( 'migration_picker' ); ?>&picker_type=local&callback_data=<?php echo htmlentities( pb_backupbuddy::_GET( 'callback_data' ) ); ?>&migrate=1" class="pb_backupbuddy_big_button">
		Copy to a local file path on this server
	</a>
<?php
} elseif ( pb_backupbuddy::_GET( 'picker_type' ) == 'local' ) {
	?>
	<h3>Automated Migration - Copy to local path</h3>
	<?php
	$settings_form = new pb_backupbuddy_settings( 'settings', false, 'action=pb_backupbuddy_migration_picker&picker_type=local&callback_data=' . htmlentities( pb_backupbuddy::_GET( 'callback_data' ) ) . '&migrate=1', 200 ); // form name, savepoint, additional query, custom title width
	$settings_form->add_setting( array(
		'type'		=>		'text',
		'name'		=>		'path',
		'title'		=>		__( 'Local file path', 'it-l10n-backupbuddy' ),
		'tip'		=>		__( 'Provide the full path to the location to migrate the site to. This must map to the web location for the destination URL.', 'it-l10n-backupbuddy' ),
		'default'	=>		ABSPATH,
		'css'		=>		'width: 375px;',
		'rules'		=>		'required|string[1-500]',
	) );
	$settings_form->add_setting( array(
		'type'		=>		'text',
		'name'		=>		'url',
		'title'		=>		__( 'Destination site URL*', 'it-l10n-backupbuddy' ),
		'tip'		=>		__( 'Enter the URL corresponding to the local destination selected on the previous page. This URL must lead to the location where files uploaded to this remote destination would end up. If the destination is in a subdirectory make sure to include it in the corresponding URL.', 'it-l10n-backupbuddy' ),
		'default'	=>		site_url(),
		'css'		=>		'width: 375px;',
		'rules'		=>		'',
		'after'		=>		'<p><a class="button button-secondary pb_backupbuddy_remotetest" id="pb_backupbuddy_remotetest_local" alt="' . pb_backupbuddy::ajax_url( 'remote_test' ) . '&service=local">' . __('Test these settings', 'it-l10n-backupbuddy' ) . '</a><span class="pb_backupbuddy_loading" style="display: none; margin-left: 10px;"><img src="' . pb_backupbuddy::plugin_url() . '/images/loading.gif" alt="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" title="' . __('Loading...', 'it-l10n-backupbuddy' ) . '" width="16" height="16" style="vertical-align: -3px;"></span></p>',
	) );
	$submitted_settings = $settings_form->process(); // Handles processing the submitted form (if applicable).
	if ( ( $submitted_settings != '' ) && ( count ( $submitted_settings['errors'] ) == 0 ) ) {
		
		$fail = false;
		if ( !file_exists( $submitted_settings['data']['path'] ) ) {
			pb_backupbuddy::$filesystem->mkdir( $submitted_settings['data']['path'] );
		}
		
		if ( !is_writable( $submitted_settings['data']['path'] ) === true ) {
			$fail .= 'The path does not allow writing. Please verify write file permissions.';
		}
		
		if ( $fail === false ) {
			$destination = pb_backupbuddy::settings( 'local_destination_defaults' );
			$destination['title'] = '(temporary migration destination)';
			$destination['path'] = $submitted_settings['data']['path'];
			$destination['url'] = $submitted_settings['data']['url'];
			$destination['type'] = 'local';
			$destination['created'] = time();
			
			$next_index = end( array_keys( pb_backupbuddy::$options['remote_destinations'] ) ) + 1;
			if ( empty( $next_index ) ) {
				// No index so set it to 0.
				$next_index = 0;
			}
			
			pb_backupbuddy::$options['remote_destinations'][$next_index] = $destination;
			pb_backupbuddy::save();
			
			?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					var win = window.dialogArguments || opener || parent || top;
					win.pb_backupbuddy_selectdestination( '<?php echo $next_index; ?>', '<?php echo $destination['title']; ?> (local)', '<?php if ( !empty( $_GET['callback_data'] ) ) { echo $_GET['callback_data']; } ?>' );
					win.tb_remove();
					//return false;
				});
			</script>
			<?php
		} else {
			pb_backupbuddy::alert( 'Invalid path. Unable to save. ' . $fail );
		}
	}
	
	$settings_form->display_settings( 'Select Local Destination' );
	
	?>
	
	<br><br><br>
	<div class="description">
		* If the URL is not provided you may enter it on the next screen. The URL will be attempted to be guessed.
	</div>
	<?php
	
} else {
	die( '{Unknown picker type.}' );
}