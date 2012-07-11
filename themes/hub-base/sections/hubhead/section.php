<?php
/*
	Section: Hub Head
	Author: Greggsky
	Author URI: 
	Description: Shows the hub title and image
	Class Name: InteroccHubHead
	Cloning: true
	Workswith: templates, main, header, morefoot
*/

/**
 * Callout Section
 *
 * @package PageLines Framework
 * @author PageLines
 */
class InteroccHubHead extends PageLinesSection {

	var $tabID = 'callout_meta';

	/**
	* Section template.
	*/
 	function section_template() {
	 	
	 	$hubhome = get_bloginfo('url');
		$call_title = of_get_option ( 'hub-title' );
		$call_sub = of_get_option ( 'short-desc' );
		$call_img = of_get_option ( 'hub-image' );
		$call_action_text = (ploption('pagelines_callout_action_text', $this->oset)) ? ploption('pagelines_callout_action_text', $this->oset) : __('Start Here', 'pagelines');

		$styling_class = 'with-callsub';
		
		$alignment = ploption('pagelines_callout_align', $this->oset);

		//$call_align = 'rtimg';	

		if($call_title || $call_img){ ?>
			
<?php if($alignment == 'center'): ?>
<div class="callout-area fix callout-center <?php echo $styling_class;?>">
	<div class="callout_text">
		<div class="callout_text-pad">
			<?php $this->draw_text($call_title, $call_sub, $call_img); ?>
		</div>
	</div>
	<div class="callout_action <?php echo $call_align;?>">
		<?php $this->draw_action($call_link, $target, $call_img, $call_btheme, $call_btext); ?></a>
	</div>
	
</div>
<?php else: ?>
<div class="callout-area media fix <?php echo $styling_class;?>">
	<div class="callout_action img <?php echo $call_align;?>">
		<?php $this->draw_action($call_link, $target, $call_img, $call_btheme, $call_btext); ?>
	</div>
	<div class="callout_text bd">
		<div class="callout_text-pad">
			<a href="<?php echo $hubhome; ?>"><?php $this->draw_text($call_title, $call_sub, $call_img); ?></a>
		<div class="connect">
		<?php if ( of_get_option ( 'hub-website' ) ) echo '<a href="' . of_get_option ( 'hub-website' ) . '" class="button" target="_blank">website</a>'; ?>
		<?php if ( of_get_option ( 'hub-facebook' ) ) echo '<a href="' . of_get_option ( 'hub-facebook' ) . '" class="button" target="_blank">facebook page</a>'; ?>
		<?php if ( of_get_option ( 'hub-facebook-group' ) ) echo '<a href="' . of_get_option ( 'hub-facebook-group' ) . '" class="button" target="_blank">facebook group</a>'; ?>
		<?php if ( of_get_option ( 'hub-twitter' ) ) echo '<a href="http://www.twitter.com/' . of_get_option ( 'hub-twitter' ) . '" class="button" target="_blank">twitter</a>'; ?>
		<?php if ( of_get_option ( 'hub-forum' ) ) echo '<a href="' . of_get_option ( 'hub-forum' ) . '" class="button">forums</a>'; ?>
		<?php if ( of_get_option ( 'hub-classifieds' ) ) echo '<a href="' . of_get_option ( 'hub-classifieds' ) . '" class="button" target="_blank">classifieds</a>'; ?>
		<?php if ( of_get_option ( 'contact-email' ) ) echo '<a href="mailto:' . of_get_option ( 'contact-email' ) . '" class="button">Email</a>'; ?>
		</div>
		</div>
	</div>
</div>
<?php endif; ?>
<?php

		} else
			echo setup_section_notify($this, __('Set Callout page options to activate.', 'pagelines') );
			
	}
	
	function draw_action($call_link, $target, $call_img, $call_btheme, $call_btext){
		if( $call_img )
			printf('<div class="callout_image"><a %s href="%s" ><img src="%s" /></a></div>', $target, $call_link, $call_img);
		else 
			printf('<a %s class="btn btn-%s btn-large" href="%s">%s</a> ', $target, $call_btheme, $call_link, $call_btext);
		
	}
	
	function draw_text($call_title, $call_sub, $call_img){
		printf( '<h2 class="callout_head %s">%s</h2>', (!$call_img) ? 'noimage' : '', $call_title);
		
		if($call_sub)
			printf( '<p class="callout_sub subhead %s">%s</p>', (!$call_img) ? 'noimage' : '', $call_sub);
	}
	
}
