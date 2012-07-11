<?php
/*
Plugin Name: Googlefonts
Plugin URI: http://www.pagelines.com
Description: Add all the Googlefonts to the font system.
Author: PageLines
PageLines: true
Version: 1.4.1
External: http://www.pagelines.com
Demo: http://www.google.com/webfonts
*/
class Google_Fonts {
	
	function __construct() {
		
		add_filter ( 'pagelines_foundry', array( &$this, 'google_fonts' ) );
	}
	
	function google_fonts( $thefoundry ) {
		
		if ( ! defined( 'PAGELINES_SETTINGS' ) )
			return;

		$fonts = $this->get_fonts();
			
		return array_merge( $thefoundry, $fonts );
		
	}

	function get_fonts( ) {
		
		$fonts = pl_file_get_contents( dirname(__FILE__) . '/fonts.json' );

		$fonts = json_decode( $fonts );

		$fonts = $fonts->items;

		$fonts = ( array ) $fonts;

		$out = array();

		foreach ( $fonts as $font ) {

			$out[ str_replace( ' ', '_', $font->family ) ] = array(
				'name'		=> $font->family,
				'family'	=> sprintf( '"%s"', $font->family ),
				'web_safe'	=> true,
				'google' 	=> $font->variants,
				'monospace' => ( preg_match( '/\sMono/', $font->family ) ) ? 'true' : 'false',
				'free'		=> true
			);
		}
		return $out;
	}
}

new Google_Fonts;
