<?php
/**
 *  Admin Page 
 *  For configuring the plugin
 */
 
include_once(dirname(__FILE__).'/config.php');

global $gcs_plugin_name, $gsc_search_engine_id, $gsc_open_results_in_same_window, $gsc_hide_search_button;

function gsc_update_form(){
	global $gsc_search_engine_id, $gsc_open_results_in_same_window, $gsc_hide_search_button;
	
	if($_POST['gsc_search_engine_id']){
		$new_gsc_search_engine_id_value = $_POST['gsc_search_engine_id'];
		$new_gsc_search_engine_id_value = trim($new_gsc_search_engine_id_value);
		update_option($gsc_search_engine_id, $new_gsc_search_engine_id_value);
		
		$new_gsc_open_results_in_same_window = $_POST['gsc_open_results_in_same_window'];
		update_option($gsc_open_results_in_same_window, $new_gsc_open_results_in_same_window);

		$new_gsc_hide_search_button = $_POST['gsc_hide_search_button'];
		update_option($gsc_hide_search_button, $new_gsc_hide_search_button);
		return true;
	}
	return false;
}

function gsc_display_documentation_link(){
	$content = '<h3>Need Help?</h3>';
	$content .= '<a href="http://littlehandytips.com/plugins/google-custom-search/">Documentation</a> | ';
	$content .= '<a href="http://forum.littlehandytips.com">Support Forum</a>';
	$content .= '<h3>Feedback</h3>';
	$content .= 'If you have any feedback for this plugin, such as how we can make it better, <br/>';
	$content .= 'what features you like to see added or like to report any bugs, we\'ll love to hear from you.<br/><br/>';
	$content .= 'Please leave your feedback in the comments section at <a href="http://littlehandytips.com/plugins/google-custom-search/">http://littlehandytips.com/plugins/google-custom-search/</a>.<br/>';
	$content .= 'All feedback is greatly appreciated. Thank you!';
	
	return $content;
}

function gsc_display_donation_link(){
	global $gsc_plugin_dir_path;
	
	$content = '<h3>Donation</h3>';
	$content .= 'If you like our plugin and would like to support what we\'re doing, please make a donation via paypal using the link below.<br/>';
	$content .= '<a href="http://littlehandytips.com/support/">';
	$content .='<img title="Donation" src="' . $gsc_plugin_dir_path . '/paypal-donate-button.jpg" alt="Donation" border="0" /></a>';
	$content .= '</a><br/>';
	$content .= 'Thank You!';
	return $content;
}

function gsc_display_promo_for_other_plugins(){
	$content = "<h3>Have you checked out the other Little Handy Plugins?</h3>";
	
	//Google Custom Search
	$content .= '<a href="http://littlehandytips.com/plugins/custom-about-author/"><img src="http://littlehandytips.com/wp-content/uploads/custom-about-author-ad-300x125.png"></a>';
	
	return $content;
}

?>
<div class="wrap">

<h3>Google Custom Search Options</h3>
<?php
 if(gsc_update_form()){
?>
	 <div id="message" class="updated"><p>Google Custom Search Options <strong>updated</strong>.</p></div>
<?php
 }
?>

<form name="rssForm" method="post" action="admin.php?page=<?php echo $gcs_plugin_name ?>">
<?php settings_fields( 'gsc-settings-group' ); ?>
  <table class="form-table">

    <tr valign="top">
      <th scope="row">Search engine unique ID <a href="http://www.google.com/cse/" target="_blank">(create one with google)</a>:</th>
      <td><input type="text" name="gsc_search_engine_id" value="<?php echo get_option($gsc_search_engine_id); ?>" size=40/> (e.g: 095382442174838362754:hisuukncdfg )</td>
    </tr>
    <tr valign="top">
      <th scope="row">Display results in same window:</th>
      <td><input name="gsc_open_results_in_same_window" type="checkbox" value="yes"  <?php if( get_option($gsc_open_results_in_same_window) == "yes"){echo "checked";} ?> />
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (if unchecked results open in new window)</td>
    </tr>
    <tr valign="top">
      <th scope="row">Hide search button:</th>
      <td><input name="gsc_hide_search_button" type="checkbox" value="yes"  <?php if( get_option($gsc_hide_search_button) == "yes"){echo "checked";} ?> /></td>
    </tr>
  </table>
  <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
  </p>
</form>

<p>&nbsp;</p>
<p>&nbsp;</p>
<h4>Setup Instructions</h4>

<h5>1) Create Custom search engine with Google</h5>
Go to <a href="http://www.google.com/cse/manage/create" target="_blank">http://www.google.com/cse/manage/create</a> and follow the instructions.</a>
<h5>2) Retrieve Search Engine Unique ID from Google</h5>
Go to <a href="http://www.google.com/cse/manage/all" target="_blank">http://www.google.com/cse/manage/all</a> and click on the control panel for your search engine.<br/>
The search engine Unique ID is in under the Basic Information for your google custom search engine.<br/>
The id should look similar in format to 095382442174838362754:hisuukncdfg
<h5>3) Enter Search Engine Unique ID in Field Above</h5>
With the search engine unique id you retrieved in Step 2. Enter it in the form.

<h5>4) Add the Google Custom Search Widget</h5>
Add the Google Custom Search Widget to a sidebar to activate it.
<p>&nbsp;</p>
<p>
<b>For more information go to <a href="http://littlehandytips.com/plugins/google-custom-search/" target="_blank">Little Handy Tips</a>.</b>
<?php
		echo gsc_display_documentation_link();
		echo gsc_display_donation_link();
		echo gsc_display_promo_for_other_plugins();
?>
</p>
</div> 