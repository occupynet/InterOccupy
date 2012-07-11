<p>For BackupBuddy Multisite documentation, please visit the <a href='http://ithemes.com/codex/page/BackupBuddy_Multisite'>BackupBuddy Multisite Codex</a>.</p>
<br>

<h3>Select plugins to include in Export</h3>


<form method="post" action="<?php echo pb_backupbuddy::page_url(); ?>&backupbuddy_backup=export">

<div id='plugin-list'>
<?php
?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<th><?php esc_html_e( 'Plugin', 'it-l10n-backupbuddy' ); ?></th>
				<th><?php esc_html_e( 'Description', 'it-l10n-backupbuddy' ); ?></th>
				<th><?php esc_html_e( 'Plugin Type', 'it-l10n-backupbuddy' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr class="thead">
				<th scope="col" class="check-column"><input type="checkbox" class="check-all-entries" /></th>
				<th><?php esc_html_e( 'Plugin', 'it-l10n-backupbuddy' ); ?></th>
				<th><?php esc_html_e( 'Description', 'it-l10n-backupbuddy' ); ?></th>
				<th><?php esc_html_e( 'Plugin Type', 'it-l10n-backupbuddy' ); ?></th>
			</tr>
		</tfoot>
		<tbody id="pb_reorder">
			<?php
			//Get MU Plugins
			foreach ( get_mu_plugins() as $file => $meta ) {
				$description = !empty( $meta[ 'Description' ] ) ? $meta[ 'Description' ] : '';
				$name = !empty( $meta[ 'Name' ] ) ? $meta[ 'Name' ] : $file;
				?>
			<tr>
				<th scope="row" class="check-column"><input type="checkbox" name="items[mu][]" class="entries" value="<?php echo esc_attr( $file ); ?>" /></th>
				<td><?php echo esc_html( $name ); ?></td>
				<td><?php echo esc_html( $description ); ?></td>
				<td><?php esc_html_e( 'Must Use', 'it-l10n-backupbuddy' ); ?></td>
			</tr>	
				<?php
			} //end foreach
			
			//Get Drop INs
			foreach ( get_dropins() as $file => $meta ) {
				$description = !empty( $meta[ 'Description' ] ) ? $meta[ 'Description' ] : '';
				$name = !empty( $meta[ 'Name' ] ) ? $meta[ 'Name' ] : $file;
				?>
			<tr>
				<th scope="row" class="check-column"><input type="checkbox" name="items[dropins][]" class="entries" value="<?php echo esc_attr( $file ); ?>" /></th>
				<td><?php echo esc_html( $name ); ?></td>
				<td><?php echo esc_html( $description ); ?></td>
				<td><?php esc_html_e( 'Drop In', 'it-l10n-backupbuddy' ); ?></td>
			</tr>	
				<?php
			} //end foreach drop ins
			
			//Get Network Activated
			foreach ( get_plugins() as $file => $meta ) {
				if ( !is_plugin_active_for_network( $file ) ) continue;
				$description = !empty( $meta[ 'Description' ] ) ? $meta[ 'Description' ] : '';
				$name = !empty( $meta[ 'Name' ] ) ? $meta[ 'Name' ] : $file;
				?>
			<tr>
				<th scope="row" class="check-column"><input type="checkbox" name="items[network][]" class="entries" value="<?php echo esc_attr( $file ); ?>" /></th>
				<td><?php echo esc_html( $name ); ?></td>
				<td><?php echo esc_html( $description ); ?></td>
				<td><?php esc_html_e( 'Network Activated', 'it-l10n-backupbuddy' ); ?></td>
			</tr>	
				<?php
			} //end foreach drop ins
			?>
		</tbody>
	</table>
</div><!-- #plugin-list-->
<input type="hidden" name="action" value="export" />
<?php wp_nonce_field( 'bb-plugins-export', '_bb_nonce' ); ?>
<?php submit_button( __( 'Begin Export', 'it-l10n-backupbuddy' ), 'primary', 'bb-plugins' ); ?>
</form>




<br><br>



<?php
echo '<h3>' . __( 'Previously Created Site Exports', 'it-l10n-backupbuddy' ) . '</h3>';
$listing_mode = 'default';
require_once( '_backup_listing.php' );
?>