<?php
if(!current_user_can('manage_options')){
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
}
if(isset($_POST["crc"])){
	if($_POST["crc"]=="settings"){
		if( wp_verify_nonce($_POST[$shareRail->nonceField],'settings')){
			foreach($shareRail->editFields as $shareRailFieldsID=>$shareRailFields){
				foreach($shareRail->editFields[$shareRailFieldsID] as $editField=>$editValue){
					$val = $_POST[$editField];
					if($editValue["type"]=="check"){
						$val = isset($_POST[$editField])?true:false;
					}
					update_option($editField, $val);
				}
			}
			print $shareRail->messageInfo("Options updated");
		}else{
			print $shareRail->messageInfo("Nonce Failed", "error");
		}
	}
}
$random = rand(111111, 999999);

?><div class="wrap">
  <div class="icon32" id="icon-tools"><br></div><h2>Share Rail Settings</h2>
    <p>The settings here are fairly straight forward so have a play and find out what works best. If you are having any problems using this plugin, please do not rate this plugin as 1 star on wordpress, visit our site for help (<a href="http://studio.bloafer.com/wordpress-plugins/share-rail/" target="_blank">Bloafer</a>), you can post a comment and we will work on addressing the issue. If you need to change the look and feel of the Share Rail you visit our site and use our <a href="http://studio.bloafer.com/wordpress-plugins/share-rail/share-rail-custom-css-engine/" target="_blank">CSS engine</a> to produce custom CSS for the box below.</p>
    <form method="post" action="">
      <input type="hidden" name="crc" value="settings" />
      <?php wp_nonce_field('settings', $shareRail->nonceField); ?>
      <div class="settingsList">
      	<?php
foreach($shareRail->editFields as $shareRailFieldsID=>$shareRailFields){
?>
<h3><a href="#"><?php print ucfirst($shareRailFieldsID) ?></a></h3>
<div>
      <table class="form-table">
        <tbody>
<?php
	foreach($shareRail->editFields[$shareRailFieldsID] as $editField=>$editValue){
		$settingID = trim(str_replace($shareRail->settingNamespace, "", $editField), "-");
		$$editField = $shareRail->getSetting($settingID, $shareRailFieldsID);
?>
        <tr valign="top">
          <th scope="row"><label for="<?php print $editField ?>"><?php print $editValue["label"] ?></label></th>
          <td>
		  <?php if($editValue["type"]=="text"){ ?>
            <input type="text" name="<?php print $editField ?>" id="<?php print $editField ?>" value="<?php echo $$editField; ?>" class="regular-text" />
          <?php }elseif($editValue["type"]=="check"){ ?>
            <input type="checkbox" name="<?php print $editField ?>" id="<?php print $editField ?>"<?php if($$editField){ ?> checked="checked"<?php } ?> />
          <?php }elseif($editValue["type"]=="drop" && isset($editValue["data"])){ ?>
            <select name="<?php print $editField ?>" id="<?php print $editField ?>">
              <?php foreach($editValue["data"] as $k=>$v){
				  ?>  <option value="<?php print $k ?>" <?php if($$editField==$k){ ?> selected="selected"<?php } ?>><?php print $v ?></option><?php
			  }
			  ?>
            </select>
          <?php }elseif($editValue["type"]=="textarea"){ ?>
            <textarea cols="30" rows="5" id="<?php print $editField ?>" name="<?php print $editField ?>"><?php echo stripslashes($$editField); ?></textarea>
          <?php }elseif($editValue["type"]=="warn"){ ?>
            &nbsp;
          <?php }else{ ?>
            Incorrect settings field (<?php print $editField ?>)
          <?php } ?>
          <?php
          if($editField=="share-rail-class-attachment"){
				$args = array(
					'numberposts' => 1,
					'suppress_filters' => false
				);
			  $lastPost = wp_get_recent_posts( $args );
			  if($lastPost){
			  
		  ?>
          <a href="#" id="share-rail-class-attachment-button">Prediction</a>
          <style>
		  #share-rail-class-attachment-button{
			  text-indent:-1000px;
			  overflow:hidden;
			  display:inline-block;
			  *display:inline;
			  height:16px;
			  width:16px;
			  background:url(<?php print plugins_url('share-rail/img/wand.png') ?>) no-repeat top left;
		  }
		  </style>
          <script>
jQuery(document).ready(function(){
	jQuery("#share-rail-class-attachment-button").click(function(){
		jQuery.post('<?php print get_permalink($lastPost[0]["ID"]) ?>', function(data){
			var homePage = jQuery(data);
			if(jQuery("input[name=share-rail-class-attachment]").val()!="#" + homePage.find(".post").parent().attr("id")){
				jQuery("input[name=share-rail-class-attachment]").parent().find(".ajaxMessage").hide().html("We have scanned your site and think the attachment class should be <a href=\"#\" class=\"pushTo\">#" + homePage.find(".post").parent().attr("id") + "</a>, please click the link to use this.").slideDown("slow");
				jQuery(".pushTo").click(function(){
					jQuery("input[name=share-rail-class-attachment]").val("#" + homePage.find(".post").parent().attr("id"));
					return false;
				});
			}else{
				jQuery("input[name=share-rail-class-attachment]").parent().find(".ajaxMessage").hide().html("You are already using the Element Class attachment that we would use.").slideDown("slow");
			}
		});
		return false;
	});
});
		  </script>
		  <?php
			  }
          }
		  ?>
          <?php if(isset($editValue["description"])){ ?>
            <span class="description"><?php print $editValue["description"] ?></span>
          <?php } ?>
          <div class="ajaxMessage"></div>
          </td>
        </tr>
<?php
	}
?>
      </tbody>
    </table>
</div>
<?php
}
?>
</div>
    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery(".settingsList").accordion({
		autoHeight: false
	});
	<?php print shareRail_Google::footerScript(); ?>
});
</script>
  </form>
  <div class="icon32" id="icon-users"><br></div><h2>Do you like this? show your love :)</h2>
  <p>
<?php
$bloaferShareOptions["url"] = "http://studio.bloafer.com/wordpress-plugins/share-rail/";
$bloaferShareOptions["username"] = "Bloafer";
$bloaferShareOptions["text"] = "Im using Share Rail for Wordpress, its cool";
?>
  <table>
    <tr>
      <td valign="bottom"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B7NRW58F3CDBC" target="_blank"><img src="https://www.paypal.com/en_US/i/btn/x-click-but11.gif" alt="Donate" /></a></td>
      <td><?php print shareRail_Twitter::rail($bloaferShareOptions); ?></td>
      <td><?php print shareRail_Google::rail($bloaferShareOptions); ?></td>
      <td><?php print shareRail_Facebook::rail($bloaferShareOptions); ?></td>
      <td><?php print shareRail_Linkedin::rail($bloaferShareOptions); ?></td>
    </tr>
  </table>
  <div id="fb-root"></div>
  </p>

