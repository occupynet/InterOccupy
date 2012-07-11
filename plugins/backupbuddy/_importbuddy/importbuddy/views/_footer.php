	</div>
</div>



<?php if ( pb_backupbuddy::$options['display_mode'] == 'normal' ) { ?>
	<div class="footer"><br><br>
		<center>
			<?php
			echo '<a href="http://pluginbuddy.com"><img src="importbuddy/images/pb-logo.png"></a><br>';
			if ( pb_backupbuddy::$options['bb_version'] != '') {
				echo '<br><span class="footer_text">ImportBuddy v' . pb_backupbuddy::settings( 'version' ) . ' for BackupBuddy ' . pb_backupbuddy::$options['bb_version'] . '</span>';
			}
			?>
		</center>
	</div>
<?php } ?>



</body>
</html>