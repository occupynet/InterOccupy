<?php
class shareRail_Google{
	function enqueue_scripts(){
        //wp_deregister_script( 'google_plusone_api_core' );
        //wp_register_script( 'google_plusone_api_core', 'https://apis.google.com/js/plusone.js');
        //wp_enqueue_script( 'google_plusone_api_core' );
	}
	function settings(){
        $settings["google-active"] = array("default"=>false, "label"=>"Show Google +1", "type"=>"check", "description"=>"You can switch the Google +1 feed on and off here");
        $settings["google-load"] = array("default"=>true, "label"=>"Load Google +1 API", "type"=>"check", "description"=>"You can switch the Google +1 API on and off here, if you already have a Google +1 plugin running untick this");
        return $settings;
	}
	function footerScript(){
		$output = "    var gpo = document.createElement('script'); gpo.type = 'text/javascript'; gpo.async = true;
    gpo.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(gpo, s);
";
		return $output;
	}
	function rail($args=false){
		$output = '';
		$output .= '<g:plusone size="tall" count="true"';
		if($args && isset($args["url"])){
			$output .= ' href="' . $args["url"] . '"';
		}
		$output .= '></g:plusone>';
		return $output;
	}
}
?>