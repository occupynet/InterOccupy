<?php
class shareRail_Digg{
	function rail(){
		return '<a class="DiggThisButton DiggMedium"></a>';
	}
	function settings(){
    	$settings["digg-active"] = array("default"=>false, "label"=>"Show Digg", "type"=>"check", "description"=>"You can switch the Digg feed on and off here");
    	return $settings;
	}
	function footerScript(){
		return "(function() {
var s = document.createElement('SCRIPT'), s1 = document.getElementsByTagName('SCRIPT')[0];
s.type = 'text/javascript';
s.async = true;
s.src = 'http://widgets.digg.com/buttons.js';
s1.parentNode.insertBefore(s, s1);
})();";
	}
}




?>