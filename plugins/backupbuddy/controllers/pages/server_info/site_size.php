<?php
pb_backupbuddy::load_script( 'icicle.js' );
pb_backupbuddy::load_script( 'icicle_setup.js' );
pb_backupbuddy::load_style( 'jit_base.css' );
pb_backupbuddy::load_style( 'jit_icicle.css' );
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#pb_iciclelaunch').click(function(e) {
			jQuery('#pb_infovis_container').slideToggle();
			jQuery.post( '<?php echo pb_backupbuddy::ajax_url( 'icicle' ); ?>', 
				function( data ) {
					jQuery('#infovis').html('');
					icicle_init( data );
				}
			);
		});
		
	});
</script>


<?php echo '<div class="pb_htitle">' . __( 'Directory Size Map', 'it-l10n-backupbuddy' ) . '</div><br>';?>
<?php _e('This option displays an interactive graphical representation of directories and the corresponding size of all contents within, including subdirectories.
This is useful for finding where space is being used. Directory boxes are scaled based on size. Click on a directory box to move around. Note that this
is a CPU intensive process and may take a while to load and even time out on some servers. Slower computers may have trouble navigating the interactive map.', 'it-l10n-backupbuddy' );
?>
<p><a id="pb_iciclelaunch" class="button secondary-button" style="margin-top: 3px;"><?php _e('Display Directory Size Map', 'it-l10n-backupbuddy' );?></a></p>

<link type="text/css" href="<?php echo pb_backupbuddy::plugin_url(); ?>/css/jit_base.css" rel="stylesheet" />
<link type="text/css" href="<?php echo pb_backupbuddy::plugin_url(); ?>/css/jit_icicle.css" rel="stylesheet" />


<div style="display: none;" id="pb_infovis_container">
	<div style="background: #1A1A1A;">
		<div id="infovis">
			<br /><br />
			<div style="margin: 30px;">
				<h4 style="color: #FFFFFF;"><img src="<?php echo pb_backupbuddy::plugin_url(); ?>/images/loading_large_darkbg.gif" style="vertical-align: -9px;" /> <?php _e('Loading ... Please wait ...', 'it-l10n-backupbuddy' );?></h4>
			</div>
		</div>
	</div>
	
	<label for="s-orientation"><?php _e('Orientation', 'it-l10n-backupbuddy' );?>: </label>
	<select name="s-orientation" id="s-orientation">
		<option value="h" selected><?php _e('horizontal', 'it-l10n-backupbuddy' );?></option>
		<option value="v"><?php _e('vertical', 'it-l10n-backupbuddy' );?></option>
	</select>
	
	<label for="i-levels-to-show"><?php _e('Max levels', 'it-l10n-backupbuddy' );?>: </label>
	<select  id="i-levels-to-show" name="i-levels-to-show" style="width: 50px">
		<option>all</option>
		<option>1</option>
		<option>2</option>
		<option selected="selected">3</option>
		<option>4</option>
		<option>5</option>
	</select>

	<a id="update" class="theme button white"><?php _e('Go Up', 'it-l10n-backupbuddy' );?></a>
</div>



<?php
$dir_array = array();
$icicle_array = array();
$time_start = microtime(true);

//echo '<pre>' . $this->build_icicle( ABSPATH, ABSPATH, '' ) . '</pre>';











echo '<br>';
echo '<div class="pb_htitle">' . __('Directory Size Listing', 'it-l10n-backupbuddy' ) . '</div><br>';
echo '<a name="pb_backupbuddy_dir_size_listing">&nbsp;</a>';

if ( empty( $_GET['site_size'] ) ) {
	echo __('This option displays a comprehensive listing of directories and the corresponding size of all contents within, including subdirectories.  This is useful for finding where space is being used. Note that this is a CPU intensive process and may take a while to load and even time out on some servers.', 'it-l10n-backupbuddy' );
	echo '<br /><br /><a href="' . pb_backupbuddy::page_url() . '&site_size=true#pb_backupbuddy_dir_size_listing" class="button secondary-button" style="margin-top: 3px;">', __('Display Directory Size Listing', 'it-l10n-backupbuddy' ),'</a>';
} else {

	$exclusions = pb_backupbuddy_core::get_directory_exclusions();
	
	$result = pb_backupbuddy::$filesystem->dir_size_map( ABSPATH, ABSPATH, $exclusions, $dir_array );
	$total_size = pb_backupbuddy::$options['stats']['site_size'] = $result[0];
	$total_size_excluded = pb_backupbuddy::$options['stats']['site_size_excluded'] = $result[1];
	pb_backupbuddy::$options['stats']['site_size_updated'] = time();
	pb_backupbuddy::save();
	
	arsort( $dir_array );
	
	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<?php
					echo '<th>', __('Directory', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Size with Children', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Size with Exclusions', 'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</thead>
		<tfoot>
			<tr class="thead">
				<?php
					echo '<th>', __('Directory', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Size with Children', 'it-l10n-backupbuddy' ), '</th>',
						 '<th>', __('Size with Exclusions', 'it-l10n-backupbuddy' ), '</th>';
				?>
			</tr>
		</tfoot>
		<tbody>
	<?php
	echo '<tr><td align="right"><b>' . __( 'TOTALS', 'it-l10n-backupbuddy' ) . ':</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size ) . '</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size_excluded ) . '</b></td></tr>';
	$item_count = 0;
	foreach ( $dir_array as $id => $item ) { // Each $item is in format array( TOTAL_SIZE, TOTAL_SIZE_TAKING_EXCLUSIONS_INTO_ACCOUNT );
		$item_count++;
		if ( $item_count > 100 ) {
			flush();
			$item_count = 0;
		}
		if ( $item[1] === false ) {
			$excluded_size = '<i>Excluded</i>';
			echo '<tr style="background: #F9B6B6;">';
		} else {
			$excluded_size = pb_backupbuddy::$format->file_size( $item[1] );
			echo '<tr>';
		}
		echo '<td>' . $id . '/</td><td>' . pb_backupbuddy::$format->file_size( $item[0] ) . '</td><td>' . $excluded_size . '</td></tr>';
	}
	echo '<tr><td align="right"><b>' . __( 'TOTALS', 'it-l10n-backupbuddy' ) . ':</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size ) . '</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size_excluded ) . '</b></td></tr>';
	echo '</tbody>';
	echo '</table><br>';
	
	echo 'Excluded Directories';
	pb_backupbuddy::tip( 'List of directories that will be excluded in an actual backup. This includes user-defined directories and BackupBuddy directories such as the archive directory and temporary directories.' );
	//echo '<textarea disabled style="float: right; height: 50px; width: 70%; min-width: 400px;">' . implode( "\n", $exclusions ) . '</textarea><br>';
	echo '<div style="background-color: #EEEEEE; padding: 4px; float: right; white-space: nowrap; height: 50px; width: 70%; min-width: 400px; overflow: auto;"><i>' . implode( "<br>", $exclusions ) . '</i></div>';
	
	echo '<br><span class="description"><i>' . __('Time taken', 'it-l10n-backupbuddy' ) , ': ' . round( ( microtime( true ) - $time_start ), 3 ) . ' ',__('seconds', 'it-l10n-backupbuddy' ),'.</i></span>';
	
	echo '</div>';
} // End showing site size listing.
?><br><br>
