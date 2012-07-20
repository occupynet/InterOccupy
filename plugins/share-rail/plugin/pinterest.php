<?php
class shareRail_Pinterest{
	function rail(){
		return '<a href="http://pinterest.com/pin/create/button/?url=' . urlencode(shareRail::getCurrentURL()) . '" class="pin-it-button" count-layout="vertical"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>';
	}
	function settings(){
    	$settings["pinterest-active"] = array("default"=>false, "label"=>"Show Pinterest", "type"=>"check", "description"=>"You can switch the Pinterest feed on and off here");
    	return $settings;
	}
	function footer(){
		return '<script type="text/javascript" src="http://assets.pinterest.com/js/pinit.js"></script>';
	}
}
?>