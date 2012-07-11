var $j = jQuery.noConflict();

$j(document).ready(function(){
	var test_link = $j('#test_link');
	var url = test_link.attr('href');	
	test_link.removeAttr('href').click(function(){
		
		test_link.empty().append('loading...');
		$j.ajax({
			  url: url,
			  cache: false,
			  dataType: 'html',
			  success: function(data){
			    $j("#test_response").empty().hide().html(data).addClass('test_success').fadeIn();
			  }
		});
		
	});

});

