<?php

include_once(dirname(__FILE__).'/config.php');

global $gcs_plugin_name;

/**
 * Displays the Google Custom Search box
 */
function display_search_box($display_results_option){
	global $gsc_search_engine_id, $number_of_widgets_using_pop_up_displays, $gsc_open_results_in_same_window;

	$gsc_search_engine_id = get_option($gsc_search_engine_id);
	$gsc_open_results_in_same_window = get_option($gsc_open_results_in_same_window);
	
	$random_number = rand(0, 99);
	$search_div_id = "cse-search-form".$random_number;
?>
	<div id="<?php echo  $search_div_id; ?>" style="width: 100%;">Loading</div>
	<script type="text/javascript">
      google.load('search', '1', {language : '<?php echo get_locale() ?>'});
      google.setOnLoadCallback(function() {
        var customSearchControl = new google.search.CustomSearchControl('<?php echo $gsc_search_engine_id ?>');
        customSearchControl.setResultSetSize(google.search.Search.FILTERED_CSE_RESULTSET);
        customSearchControl.enableAds('2496164227');
		<?php if($gsc_open_results_in_same_window == "yes"){ ?>
		customSearchControl.setLinkTarget(google.search.Search.LINK_TARGET_SELF);
		<?php } ?>		
        var options = new google.search.DrawOptions();
        options.setSearchFormRoot('<?php echo  $search_div_id; ?>');
        options.setAutoComplete(true);
<?php
	if($display_results_option == DISPLAY_RESULTS_AS_POP_UP){
?>    
        customSearchControl.draw('dialog', options);
        customSearchControl.setSearchCompleteCallback(this, CallBackDisplayDialog);	
<?php
	}else if($display_results_option == DISPLAY_RESULTS_IN_UNDER_SEARCH_BOX){
?>
        customSearchControl.draw('gcs-widget', options);
<?php
	}else{
?>
        customSearchControl.draw('gcs', options);
<?php
	}
?>
      }, true);
     
      // establish a keep callback
		function CallBackDisplayDialog(result) {
			jQuery('#dialog').dialog('open');
//			$('#dialog').dialog('open');
		}
    </script>
<?php
	if($display_results_option == DISPLAY_RESULTS_IN_UNDER_SEARCH_BOX){
		echo '<div id="gcs-widget" style="width:100%;"></div>';
	}else if($display_results_option == DISPLAY_RESULTS_AS_POP_UP){
		if($number_of_widgets_using_pop_up_displays==0){
?>
<!-- open dialog. For debug purposes
        <p><a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Open Dialog</a></p>
-->
        <!-- ui-dialog -->

        <div id="dialog" title="<?php _e('Search Results') ?>">
            <p></p>
        </div>
    
<?php	
		}
		$number_of_widgets_using_pop_up_displays++;
	}
}

/**
 * Place the div tag where the google custom search results will be displayed
 */
function display_gsc_results(){
	echo '<div id="gcs" style="width:100%;"></div>';
}

?>
