<?php
class shareRail_Twitter{
	function enqueue_scripts(){
        wp_deregister_script( 'twitter_api_core' );
        wp_register_script( 'twitter_api_core', 'http://platform.twitter.com/widgets.js');
        wp_enqueue_script( 'twitter_api_core' );
	}
	function settings(){
		$settings["twitter-active"] = array("default"=>false, "label"=>"Show Twitter", "type"=>"check", "description"=>"You can switch the Twitter feed on and off here");
        $settings["twitter-username"] = array("default"=>false, "label"=>"Twitter Username", "type"=>"text", "description"=>"The username is required to allow tweets");
        return $settings;
	}
	function rail($args=false){
		$output = '<a href="http://twitter.com/share"';

		if($args && isset($args["url"])){
			$output .= ' data-url="' . $args["url"] . '"';
			$output .= ' data-counturl="' . $args["url"] . '"';
		}
		$output .= ' data-count="vertical"';
		if($args && isset($args["username"])){
			$output .= ' data-via="' . $args["username"] . '"';
		}
		if($args && isset($args["text"])){
			$output .= ' data-text="' . $args["text"] . '"';
		}
		$output .= ' class="twitter-share-button">Tweet</a>';
		return $output;
	}
	function footerScript($args=false, $shareRail){
		$output = "";
		if($shareRail->getSetting("google-analytics-social")){
			$output = "	twttr.events.bind('tweet', function(event) {
			if (event) {
				_gaq.push(['_trackSocial', 'twitter', 'tweet', '" . $shareRail->getCurrentURL() . "']);
			}
		});";
		}
		return $output;
	}
}
?>