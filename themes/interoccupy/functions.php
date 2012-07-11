<?php
// Setup  -- Probably want to keep this stuff... 

/**
 * Hello and welcome to Base! First, lets load the PageLines core so we have access to the functions 
 */	
require_once( dirname(__FILE__) . '/setup.php' );
	
add_action('pagelines_head', 'add_less' );
add_action('pagelines_head', 'add_pause_js');

function add_less() {

	?>
	<link rel='stylesheet' id='less-css'  href='<?php bloginfo('stylesheet_directory'); ?>/style.less' type='text/css' media='all' />
	<?php 
}

function add_pause_js() {
?>	
	<script type="text/javascript">$("#email-3").click(function() {
        $("#cycle").cycle('pause');
    });
    </script>
<?php 
}