<?php
class shareRail_Linkedin{
	function enqueue_scripts(){
        wp_deregister_script( 'linkedin_api_core' );
        wp_register_script( 'linkedin_api_core', 'http://platform.linkedin.com/in.js');
        wp_enqueue_script( 'linkedin_api_core' );
	}
	function settings(){
	    $settings["linkedin-active"] = array("default"=>false, "label"=>"Show LinkedIn", "type"=>"check", "description"=>"You can switch the LinkedIn feed on and off here");
	    return $settings;
	}
	function rail($args=false){
		$output = '';
		$output .= '<script type="in/share"';
		if($args && isset($args["url"])){
			$output .= ' data-url="' . $args["url"] . '"';
		}
		$output .= ' data-counter="top"></script>';
		return $output;
	}
}
?>