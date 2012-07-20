<?php
class shareRail_Tumblr{
	function rail(){
		return '<a href="http://www.tumblr.com/share" title="Share on Tumblr" style="display:inline-block; text-indent:-9999px; overflow:hidden; width:61px; height:20px; background:url(\'http://platform.tumblr.com/v1/share_2.png\') top left no-repeat transparent;">Share on Tumblr</a>';
	}
	function settings(){
    	$settings["tumblr-active"] = array("default"=>false, "label"=>"Show Tumblr", "type"=>"check", "description"=>"You can switch the Tumblr feed on and off here");
    	return $settings;
	}
}
?>