<?php
class shareRail_Stumble{
	function enqueue_scripts(){
        //wp_deregister_script( 'stumbleupon_api_core' );
        //wp_register_script( 'stumbleupon_api_core', 'http://www.stumbleupon.com/hostedbadge.php?s=5&a=1&d=shareRail_suhb', NULL, NULL, true);
        //wp_enqueue_script( 'stumbleupon_api_core' );
	}
	function settings(){
		$settings["stumble-active"] = array("default"=>false, "label"=>"Show Stumble Upon", "type"=>"check", "description"=>"You can switch the Stumble Upon feed on and off here");
		return $settings;
	}
	function rail(){
		return '<div id="shareRail_suhb"></div>';
	}
	function footer(){
		return '<script type="text/javascript" src="http://www.stumbleupon.com/hostedbadge.php?s=5&a=1&d=shareRail_suhb"></script>';
	}
}
?>