jQuery(document).ready(
	function($) {
		/* Mini Plugin */
		$.fn.abManageOptions = function() {
			var $$ = this,
				obj = $$.parents('tr').nextAll('tr');
				
			obj.toggle(
				0,
				function() {
					obj.children().find(':input').attr('disabled', !$$.attr('checked'));
				}
			);
		}
		
		/* Tabs steuern */
		function abInitTabs() {
			$('#ab_main').tabs(
				{
					'select': function(event, ui) {
						$('#ab_tab_index').val(ui.index);
					},
					'selected': parseInt($('#ab_tab_index').val())
				}
			);
		}
		
		/* Event abwickeln */
		$('#ab_main .related tr:first-child :checkbox').click(
			function() {
				$(this).abManageOptions();
			}
		).filter(':checked').abManageOptions();
		
		/* jQuery UI geladen? */
		if ( jQuery.ui === undefined || jQuery.ui.tabs === undefined ) {
			$.getScript(
				'http://code.jquery.com/ui/1.8.18/jquery-ui.min.js',
				abInitTabs
			);
		} else {
			abInitTabs();
		}
		
		/* Alert ausblenden */
		if ( typeof $.fn.delay === 'function' ) {
			$('#setting-error-settings_updated').delay(5000).fadeOut();
		}
	}
);