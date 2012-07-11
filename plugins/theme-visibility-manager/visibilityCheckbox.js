// JavaScript Document
jQuery(document).ready( function ($) {
	$('.theme-vis-master-check').click( function() {
		$('.theme-vis-check, .theme-vis-master-check').attr('checked', $(this).attr('checked') ) ;
	} );
});