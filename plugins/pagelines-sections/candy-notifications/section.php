<?php
/*
Section: Candy Notifications Lite
Author: Enrique Chávez
Author URI: http://tmeister.net
Version: 1.0.0
Description: Add catching notifications on the top of your site and keep your users up to date with news or announcements.
Class Name: tmCandy
Cloning: false
Workswith: header

**/


class tmCandy extends PageLinesSection {

	/**
	 *
	 * Section Variable Glossary (Auto Generated)
	 * ------------------------------------------------
	 *  $this->id			- The unique section slug & folder name
	 *  $this->base_url 	- The root section URL
	 *  $this->base_dir 	- The root section directory path
	 *  $this->name 		- The section UI name
	 *  $this->description	- The section description
	 *  $this->images		- The root section images URL
	 *  $this->icon 		- The section icon url
	 *  $this->screen		- The section screenshot url 
	 *  $this->oset			- Option settings array... needed for 'ploption' (contains clone_id, post_id)
	 * 
	 * 	Advanced Variables
	 * 		$this->view				- If the section is viewed on a page, archive, or single post
	 * 		$this->template_type	- The PageLines template type
	 */

	var $tax_id         = "tm_candys";
	var $custom_post_id = 'tm_candys_post';
	var $domain         = 'tm_candys';

	function section_persistent(){
		$this->post_type_setup();
	} 


	function section_scripts() {  
	
		return array(
			'cycle' => array(
				'file' => $this->base_url . '/script.cycle.js',
				'dependancy' => array('jquery'), 
				'location' => 'footer', 
				'version' => '2.9994'
			)	
		);
	}

	function section_head($clone_id = null){
		global $post, $pagelines_ID;
		$oset           = array('post_id' => $pagelines_ID, 'clone_id' => $clone_id);
		$pause          = ( ploption('tm_candys_duration_pause', $oset) ) ? ploption('tm_candys_duration_pause', $oset) : '5000';
		$pause_on_hover = ( ploption('tm_candys_pause_on_hover', $oset) == 'on') ? 'true' : 'false';
		$show_at_start = ( ploption('tm_candy_open', $oset) == 'on') ? 'true' : 'false';
		$limit             = ( ploption('tm_candys_items', $oset) ) ? ploption('tm_candys_items', $oset) : '5';
		$set               = ( ploption('tm_candys_set', $oset) ) ? ploption('tm_candys_set', $oset) : null;
		$notifications     = $this->get_posts( $set, $limit );
		if( !count( $notifications ) ){
		?>
			<style>
				.candy-close{display: none;}
			</style>
		<?
			return;
		}
	?>
		<style>
			#<?php echo $this->id ?>{
				background:url(<?php echo $this->images ?>/candy_bg.png) repeat;
				left:0;
				position: fixed;
				<?php if( ! is_admin_bar_showing() ) :?>
				top:0;
				<?php else: ?>
				top:28px;
				<?php endif; ?>
				visibility: hidden;
				width:100%;
				z-index:996;
			}
			.candy-close{
				<?php if( ! is_admin_bar_showing() ) :?>
				top:0;
				<?php else: ?>
				top:28px;
				<?php endif; ?>
			}
		</style>
		<!--[if lt IE 9]>
		<style>.candys{background:#70c6ef;}</style>
		<![endif]-->

		<script>
			jQuery(function($){
				var $candy   = $('#<? echo $this->id?>');
				var $candys  = $('.candys')
				var $close   = $('.candy-close');
				var $btn     = $('.candy-close-btn');
				var hasCycle = false;
				var showAtStart = <?php echo $show_at_start ?>;
				
				function fixHeight(curr, next, opts, fwd){
			        var $ht = $(this).height();
			        $(this).parent().animate( { height: $ht }, 400 )
				}

				$close.on('click', function(){
					$close.slideUp(200, function(){
						$candy.css({'visibility': 'visible'});
						$candy.slideDown(400, function(){
							$('#header').animate({'margin-top': '+='+$candy.height()}, 250)	
							if( hasCycle ){$('.candys').cycle('resume');}
						});
					});
				});
				
				$btn.on('click', function(){
					$candy.slideUp(400, function(){
						$close.slideDown(200, function(){
							$('#header').animate({'margin-top': '-='+$candy.height()}, 250)	
							if( hasCycle ){$('.candys').cycle('pause');}
						});
						
					});
				});


				if( $('.candys').children().length > 1 ){
					hasCycle = true;
					$candys.cycle({
						fx: 'fade',
						fit:0,
						slideResize:0,
						containerResize:1,
						cleartype: 1,
						after:fixHeight,
						pause: <?php echo $pause_on_hover ?>,
						timeout:<?php echo $pause ?>
					});
					if( ! showAtStart ){
						$('.candys').cycle('pause');
					}
				}
				if (! showAtStart) {
					$candy.hide();
				}else{
					$close.hide();
					$candy.css({'visibility': 'visible'});
					$('#header').css({'margin-top': '+='+$candy.height()}, 400)	
				}
				
			});

		</script>
		


	<?
	} 

	function section_template( $clone_id = null ) {
		global $post, $pagelines_ID;
		$oset              = array('post_id' => $pagelines_ID, 'clone_id' => $clone_id);
		$limit             = ( ploption('tm_candys_items', $oset) ) ? ploption('tm_candys_items', $oset) : '5';
		$set               = ( ploption('tm_candys_set', $oset) ) ? ploption('tm_candys_set', $oset) : null;
		$current_page_post = $post;
		$notifications     = $this->get_posts( $set, $limit );
		if( !count($notifications) ){
			echo setup_section_notify($this, __('There is no messages to display.', $this->domain), get_admin_url().'edit.php?post_type='.$this->custom_post_id, 'Please add at less one' );
		}

 	?>
 		<div class="candy-wrapper">
 			
 			<div class="searchform"><?php get_search_form(); ?></div>
 			<div class="candys">
 				<?php foreach ($notifications as $post): setup_postdata($post);  ?>
 					<div class="candy" id="candy-<?php echo $post->ID ?>" >
 						<?php the_content() ?>
 					</div>		
 				<?php endforeach; $post = $current_page_post; ?>
 				
 			</div>
 			<div class="candy-close-btn"></div>
 		</div>
 	<?
	}

	function before_section_template( $clone_id = null ){}
	function after_section_template( $clone_id = null ){echo "<div class='candy-close'></div>";}


	function post_type_setup(){
		$args = array(
			'label'          => 'Notifications',
			'singular_label' => 'Notification',
			'taxonomies'     => array( $this->tax_id ),
			'menu_icon'      => $this->icon,
		);
		$taxonomies = array(
			$this->tax_id => array(
				'label'          => 'Notifications Sets',
				'singular_label' => 'Notification Set',
			)
		);
		$columns = array(
			"cb"          => "<input type=\"checkbox\" />",
			"title"       => "Title",
			"date"        => "Date",
			$this->tax_id => "Sets"
		);
		$this->post_type = new PageLinesPostType( $this->custom_post_id, $args, $taxonomies, $columns, array(&$this, 'column_display') );
	}


	function column_display($column){
		global $post;
		
		switch ($column){
			case $this->tax_id:
				echo get_the_term_list($post->ID, $this->tax_id, '', ', ','');
			break;
		}
		
	}

	function get_posts( $set = null, $limit = null){
		$query                 = array();
		$query['orderby']      = 'ID';
		$query['post_type']    = $this->custom_post_id;
		$query[ $this->tax_id ] = $set;
		
		if(isset($limit)){
			$query['showposts'] = $limit;
		}
		
		$q = new WP_Query($query);

		if(is_array($q->posts))
			return $q->posts;
		else
			return array();
	}


 	
	/**
	 *
	 * Section Page Options
	 * 
	 * Section optionator is designed to handle section options.
	 */
	function section_optionator( $settings ){
		$settings = wp_parse_args($settings, $this->optionator_default);
		$opt_array = array(
			'tm_candy_open' => array(
				'title'			=> 'Show at the start',
				'type'         	=> 'check',
				'inputlabel'   	=> __( 'Show at the start', $this->domain ),
				'shortexp' 		=> 'Default: Hidden',
				'exp'      		=> 'Check if you want to show the notification when the page is loaded, by default the notification area is hidden and it show the ribbon to open it.'
			),
			'tm_candys_set' 	=> array(
				'type' 			=> 'select_taxonomy',
				'taxonomy_id'	=> $this->tax_id,
				'title' 		=> __('Select notiofication set to show', $this->domain),
				'shortexp'		=> __('The set to show', $this->domain),
				'inputlabel'	=> __('Select a set', $this->domain),
				'exp' 			=> __('if don\'t select a set it will show all notification entries', $this->domain)
			),
			'tm_candys_items' => array(
				'type' 			=> 'count_select',
				'inputlabel'	=> __('Number of notifications to show', $this->domain),
				'title' 		=> __('Number of notifications', $this->domain),
				'shortexp'		=> __('Default value is 1', $this->domain),
				'count_start'	=> 1, 
 				'count_number'	=> 5,
			),
			'tm_candys_pause_on_hover' => array(
				'type'			=> 'check',
				'title'			=> __('Pause on hover', $this->domain),
				'inputlabel'	=> __('Pause on hover', $this->domain),
				'shortexp'		=> __('', $this->domain),
				'exp'			=> __('Determines whether the timeout between transitions should be paused "onMouseOver"', $this->domain)
			),
			'tm_candys_duration_pause' 	=> array(
				'type'			=> 'text',
				'inputlabel'	=> __('Pause Duration', $this->domain),
				'title' 		=> __('Pause Duration', $this->domain),
				'shortexp'		=> '',
				'exp'			=> __('The amount of milliseconds the carousel will pause. 1000 = 1 second', $this->domain),
			),

				
		);

		$settings = array(
			'id' 		=> $this->id.'_meta',
			'name' 		=> $this->name,
			'icon' 		=> $this->icon, 
			'clone_id'	=> $settings['clone_id'], 
			'active'	=> $settings['active']
		);

		register_metatab($settings, $opt_array);
		
	}
} /* End of section class - No closing php tag needed */