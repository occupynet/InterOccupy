<?php
/*
	Section: Simple Nav
	Author: PageLines
	Author URI: http://www.pagelines.com
	Description: Creates footer navigation.
	Version: 1.0.0
	Class Name: SimpleNav
	Workswith: footer
*/

class SimpleNav extends PageLinesSection {

	function section_persistent(){
		register_nav_menus( array( 'simple_nav' => __( 'Simple Nav Section', 'pagelines' ) ) );
	
		add_filter('pagelines_css_group', array(&$this, 'section_selectors'), 10, 2);

	}

	function section_selectors($selectors, $group){

		$s['nav_standard'] = '.main-nav li:hover, .main-nav .current-page-ancestor a,  .main-nav li.current-page-ancestor ul a, .main-nav li.current_page_item a, .main-nav li.current-menu-item a, .sf-menu li li, .sf-menu li li li';

		$s['nav_standard_border'] = 'ul.sf-menu ul li';

		$s['nav_highlight'] = '.main-nav li a:hover, .main-nav .current-page-ancestor .current_page_item a, .main-nav li.current-page-ancestor ul a:hover';


		if($group == 'box_color_primary')
			$selectors .= ','.$s['nav_standard'];
		elseif($group == 'box_color_secondary')	
			$selectors .= ','.$s['nav_highlight'];
		elseif( $group == 'border_primary' )
			$selectors .= ','.$s['nav_standard_border'];

		return $selectors;
	}
	
	
   function section_template() { 

	if(function_exists('wp_nav_menu'))
		wp_nav_menu( array('menu_class'  => 'inline-list simplenav font-sub', 'theme_location'=>'simple_nav','depth' => 1,  'fallback_cb'=>'simple_nav_fallback') );
	else
		nav_fallback();
	}

}

if(!function_exists('simple_nav_fallback')){
	function simple_nav_fallback() {
		printf('<ul id="simple_nav_fallback" class="inline-list simplenav font-sub">%s</ul>', wp_list_pages( 'title_li=&sort_column=menu_order&depth=1&echo=0') );
	}
}
