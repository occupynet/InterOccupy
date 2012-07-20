<?php
class shareRail_Reddit{
	function settings(){
		$settings["reddit-active"] = array("default"=>false, "label"=>"Show Reddit", "type"=>"check", "description"=>"You can switch the Reddit feed on and off here");
		return $settings;
	}
	function rail($args=false){
		$output = '';
		$output .= '<script type="text/javascript" src="http://www.reddit.com/static/button/button2.js"></script>';
		return $output;
	}
}
?>