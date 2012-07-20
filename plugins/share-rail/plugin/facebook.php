<?php
class shareRail_Facebook{
	function enqueue_scripts(){
        wp_deregister_script( 'facebook_api_core' );
        wp_register_script( 'facebook_api_core', 'http://connect.facebook.net/en_US/all.js#xfbml=1');
        wp_enqueue_script( 'facebook_api_core' );
	}
	function settings(){
		$settings["facebook-active"] = array("default"=>false, "label"=>"Show Facebook", "type"=>"check", "description"=>"You can switch the Facebook feed on and off here");
		return $settings;
	}
	function rail($args=false){
		$output = '';
		$output .= '<fb:like layout="box_count"';
		if($args && isset($args["url"])){
			$output .= ' href="' . $args["url"] . '"';
		}
		$output .= '></fb:like>';
		return $output;
	}
	function footerScript($args=false, $shareRail){
		$output = "";
		if($shareRail->getSetting("google-analytics-social")){
			$output = "
	FB.Event.subscribe('edge.create', function(targetUrl) { _gaq.push(['_trackSocial', 'facebook', 'like', targetUrl]); });
	FB.Event.subscribe('message.send', function(targetUrl) { _gaq.push(['_trackSocial', 'facebook', 'send', targetUrl]); });
	FB.Event.subscribe('edge.remove', function(targetUrl) { _gaq.push(['_trackSocial', 'facebook', 'unlike', targetUrl]); });
	";
		}
		return $output;
	}
	function footer(){
		return '<div id="fb-root"></div>';
	}
}
?>