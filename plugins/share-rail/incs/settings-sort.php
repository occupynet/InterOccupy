<?php
if(!current_user_can('manage_options')){
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
}
global $shareRail;
if(isset($_POST["crc"])){
	if($_POST["crc"]=="settings-sort"){
		if( wp_verify_nonce($_POST["settings-sort-nonce"],'settings-sort')){
			$val = $_POST["itemorder"];
			update_option("itemorder", $val);
			print $shareRail->messageInfo("Options updated");
		}else{
			print $shareRail->messageInfo("Nonce Failed", "error");
		}
	}
}
$itemSavedOrder = stripslashes(get_option("itemorder"));
?><div class="wrap">
  <div class="icon32" id="icon-tools"><br></div><h2>Share Rail Settings - Sort</h2>
    <p>This screen can be used to re-order your rail</p>
    <form method="post" action="">
      <input type="hidden" name="crc" value="settings-sort" />
      <?php wp_nonce_field('settings-sort', 'settings-sort-nonce'); ?>
<style>
	#sortable { list-style-type: none; margin: 0; padding: 0; width: 60%; }
	#sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }
	#sortable li span { position: absolute; margin-left: -1.3em; }
	</style>
<ul id="sortable">

<?php
$pluginPath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "plugin" . DIRECTORY_SEPARATOR;
$plugins = array();

$itemOrder = get_option("itemorder");
if(trim($itemOrder)!=""){
    $savedOrder = json_decode(stripslashes($itemOrder), true);
	foreach($savedOrder as $key=>$val){
		$plugins[$val] = $key;	
	}
}
if($handle = opendir($pluginPath)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $pluginFile = $pluginPath . $entry;
            $filePathParts = explode(".", basename($pluginFile));
            if(count($filePathParts)>=2){
                $ext = array_pop($filePathParts);
                $driverName = ucfirst(implode(".", $filePathParts));
                $driverShortCode = strtolower($driverName) . "-active";
                $activePlugin = $shareRail->getSetting($driverShortCode, strtolower($driverName));
                if($activePlugin){
                	$plugins[strtolower($driverName)] = '<li class="ui-state-default" id="' . strtolower($driverName) . '"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' . ucfirst($driverName) . '</li>';
            	}
            }
        }
    }
}
print implode(PHP_EOL, $plugins);
?>
</ul>
<input type="hidden" name="itemorder">
<script>
	jQuery(function() {
		jQuery( "#sortable" ).sortable();
		jQuery( "#sortable" ).disableSelection();
		jQuery("input[name=itemorder]").val(JSON.stringify(jQuery("#sortable").sortable("toArray")));
		jQuery( "#sortable" ).bind( "sortstop", function(event, ui) {
			jQuery("input[name=itemorder]").val(JSON.stringify(jQuery(this).sortable("toArray")));
		});
	});
	</script>


    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>

    </form>
  </div>
<?php
?>