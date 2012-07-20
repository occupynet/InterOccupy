<?php
/*
	Section: Hub Dashboard 2
	Author: Greggsky
	Author URI: 
	Description: Dashboard for Hubs
	Class Name: InteroccHubDash	
	Workswith: templates, main, header, morefoot, content
*/

/*
 * Main section class
 *
 * @package PageLines Framework
 * @author PageLines
 */
class InteroccHubDash extends PageLinesSection {

	/**
	* Section template.
	*/
	function section_template() {

		$tabs = '[pl_tabs]';
		$tabs .= '[pl_tabtitlesection type="tabs"]';
		$tabs .= '[pl_tabtitle active="yes" number="1"]Info[/pl_tabtitle]';
		$tabs .= '[pl_tabtitle number="2"]Updates[/pl_tabtitle]';
		$tabs .= '[pl_tabtitle number="3"]Minutes[/pl_tabtitle]';
		if ( of_get_option ( 'hub-wiki' ) ) $tabs .= '[pl_tabtitle number="4"]Wiki[/pl_tabtitle]';
		if (( of_get_option('social-tab')) == 1 ) $tabs .= '[pl_tabtitle number="6"]Social[/pl_tabtitle]';
		$tabs .= '[/pl_tabtitlesection]';
		 
		$tabs .= '[pl_tabcontentsection]';
		
		$tabs .= '[pl_tabcontent active="yes" number="1"]';
		if ( of_get_option ( 'call-info') ) $tabs .= '<h4>Call Time: ' . of_get_option ('call-info') . '</h4>'; 
		if ( of_get_option ( 'hub-handle') ) $tabs .= '<h5>@' . of_get_option ('hub-handle') . '</h5>'; 
		$tabs .= of_get_option( 'full-desc' );
		$tabs .= '<p><a href="' . of_get_option( 'hub-website' ) . '">' . of_get_option( 'hub-website') . '</a> | <a href="mailto:' . of_get_option( 'contact-email' ) . '">' . of_get_option( 'contact-email') . '</a>';
		if ( of_get_option ( 'contact-phone' ) ) $tabs .= ' | ' . of_get_option('contact-phone') . '</p>';
		$tabs .= '[/pl_tabcontent]';
		$tabs .= '[pl_tabcontent number="2"]';
		$tabs .= render_view(array("id"=>"14"));
		$tabs .= '[/pl_tabcontent]';
		$tabs .= '[pl_tabcontent number="3"]';
		$tabs .= render_view(array("id"=>"13"));
		$tabs .= '[/pl_tabcontent]';
		if ( of_get_option ( 'hub-wiki' ) ) { 
			$tabs .= '[pl_tabcontent number="4"]';
			$tabs .= '<blockquote>The information here is from the Occupy.net wiki.  Use the wiki to document everything pertaining to your Hub.  Wikis are a powerful way to share content and document the processes for the work you are engaged in.</blockquote>';
			$tabs .= '[wiki-embed url="' . of_get_option( 'hub-wiki' ) . '" tabs]';
			$tabs .= '[/pl_tabcontent]';
		}
		if (( of_get_option('social-tab')) == 1 ) {
		$tabs .= '[pl_tabcontent number="6"]';
		$tabs .= '<div class="row">';
		if ( of_get_option('hub-facebook')) $tabs .= '<div class="span5"><iframe src="//www.facebook.com/plugins/likebox.php?href=' . of_get_option( 'hub-facebook' ) . '&amp;width=292&amp;height=390&amp;colorscheme=light&amp;show_faces=true&amp;border_color&amp;stream=true&amp;header=true" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:292px; height:410px;" allowTransparency="true"></iframe></div>';
		if ( of_get_option('hub-twitter'))  $tabs .= '<div class="span5"><script charset="utf-8" src="http://widgets.twimg.com/j/2/widget.js"></script><script>
new TWTR.Widget({
  version: 2,
  type: \'profile\',
  rpp: 5,
  interval: 30000,
  width: 290,
  height: 300,
  theme: {
    shell: {
      background: \'#ffd942\',
      color: \'#252525\'
    },
    tweets: {
      background: \'#ffffff\',
      color: \'#252525\',
      links: \'#335e99\'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: true,
    behavior: \'all\'
  }
}).render().setUser(\'' . of_get_option('hub-twitter') . '\').start();
</script></div>';
		$tabs .= '<div class="span2">';
		if ( of_get_option ( 'hub-facebook' ) ) $tabs .= '<a href="' . of_get_option ( 'hub-facebook' ) . '" class="button" target="_blank">facebook</a>'; 
		if ( of_get_option ( 'hub-facebook-group' ) ) $tabs .= '<a href="' . of_get_option ( 'hub-facebook-group' ) . '" class="button" target="_blank">facebook group</a>';
		if ( of_get_option ( 'hub-twitter' ) ) $tabs .= '<a href="http://www.twitter.com/' . of_get_option ( 'hub-twitter' ) . '" class="button" target="_blank">twitter</a>';
		$tabs .= '</div>';

		$tabs .= '[/pl_tabcontent]';
		}
		
		
		$tabs .= '[/pl_tabcontentsection]';
		$tabs .= '[/pl_tabs]';	
		?> 
		<?php
		echo do_shortcode($tabs); 
		echo do_shortcode('[ai1ec view="agenda"]');
	}
	


} 