<?php
/*
Plugin Name: Network Wide Text Change
Version: 2.0.3.1
Plugin URI: http://premium.wpmudev.org/project/site-wide-text-change
Description: Would you like to be able to change any wording, anywhere in the entire admin area on your whole site? Without a single hack? Well, if that's the case then this plugin is for you!
Author: Barry (Incsub), Ulrich Sossou (incsub)
Author URI: http://premium.wpmudev.org/
Network: true
Text Domain: sitewidetext
WDP ID: 94
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Un comment for full belt and braces replacements, warning:
// 1. TEST TEST TEST
// define( 'SWTC-BELTANDBRACES', 'yes' );

require_once( 'site-wide-text-change-files/sitewidetextincludes/classes/functions.php' );

/**
 * Plugin main class
 **/
class Site_Wide_Text_Change {

	/**
	 * Current version of the plugin
	 **/
	var $build = '2.0.2';

	/**
	 * Stores translation tables
	 **/
	var $translationtable = false;

	/**
	 * Stores translations
	 **/
	var $translationops = false;

	/**
	 * PHP 4 constructor
	 **/
	function Site_Wide_Text_Change() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 **/
	function __construct() {
		add_action('admin_init', array(&$this, 'add_admin_header_sitewide'));

		add_filter('gettext', array(&$this, 'replace_text'), 10, 3);

		if( defined('SWTC-BELTANDBRACES') ) {
			add_action('init', array(&$this, 'start_cache'), 1);
			add_action('admin_print_footer_scripts', array(&$this, 'end_cache'), 9999);
		}

		add_action('ultimatebranding_settings_menu_textchange', array(&$this, 'handle_admin_page') );
		add_filter('ultimatebranding_settings_menu_textchange_process', array(&$this, 'update_admin_page') );

	}

	/**
	 * Show admin warning
	 **/
	function warning() {
		echo '<div id="update-nag">' . __('Warning, this page is not loaded with the full replacements processed.','ub') . '</div>';
	}

	/**
	 * Run before admin page display
	 *
	 * Enqueue scripts, remove output buffer and save settings
	 **/
	function add_admin_header_sitewide() {
		global $plugin_page;

		if( 'branding' !== $plugin_page  )
			return;

		if((isset($_GET['tab']) && $_GET['tab'] == 'textchange')) {
			wp_enqueue_style('sitewidecss', ub_files_url('modules/site-wide-text-change-files/sitewidetextincludes/styles/sitewide.css'), array(), $this->build);
			wp_enqueue_script('sitewidejs', ub_files_url('modules/site-wide-text-change-files/sitewidetextincludes/js/sitewideadmin.js'), array('jquery', 'jquery-form', 'jquery-ui-sortable'), $this->build);

			$this->update_admin_page();
		}

		if(defined('SWTC-BELTANDBRACES')) {
			add_action('admin_notices', array(&$this, 'warning'));

			//remove other actions
			remove_action('init', array(&$this, 'start_cache'));
			remove_action('admin_print_footer_scripts', array(&$this, 'end_cache'));
		}

	}

	/**
	 * Individual replace table output
	 **/
	function show_table($key, $table) {

		echo '<div class="postbox " id="swtc-' . $key . '">';

		echo '<div title="Click to toggle" class="handlediv"><br/></div><h3 class="hndle"><input type="checkbox" name="deletecheck[]" class="deletecheck" value="' . $key . '" /><span>' . $table['title'] . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Find this text','ub');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[$key][find]' value='" . esc_attr(stripslashes($table['find'])) . "' class='long find' />";
		echo "<br/>";
		echo "<input type='checkbox' name='swtble[$key][ignorecase]' class='case' value='1' ";
		if($table['ignorecase'] == '1') echo "checked='checked' ";
		echo "/>&nbsp;<span>" . __('Ignore case when replacing text.','ub') . "</span>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('in this text domain','ub');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[$key][domain]' value='" . esc_attr(stripslashes($table['domain'])) . "' class='short domain' />";
		echo "&nbsp;<span>" . __('( leave blank for global changes )','ub') , '</span>';
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('and replace it with','ub');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[$key][replace]' value='" . esc_attr(stripslashes($table['replace'])) . "' class='long replace' />";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo '</div>';

		echo '</div>';

	}

	/**
	 * Individual replace table output for javascript use
	 **/
	function show_table_template( $dt = '') {

		if(!empty($dt)) {
			echo '<div class="postbox blanktable" id="swtc-' . $dt . '" style="display: block;">';
		} else {
			echo '<div class="postbox blanktable" id="blanktable" style="display: none;">';
		}

		echo '<div title="Click to toggle" class="handlediv"><br/></div><h3 class="hndle"><input type="checkbox" name="deletecheck[{$dt}]" class="deletecheck" value="" /><span>' . __('New Text Change Rule','ub') . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Find this text','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[{$dt}][find]' value='' class='long find' />";
		echo "<br/>";
		echo "<input type='checkbox' name='swtble[{$dt}][ignorecase]' class='case' value='1' ";
		echo "/>&nbsp;<span>" . __('Ignore case when finding text.','ub') . "</span>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('in this text <abbr title="A text domain is related to the internationisation of the text, you should leave this blank unless you know what it means.">domain</abbr>','ub');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[{$dt}][domain]' value='' class='short domain' />";
		echo "&nbsp;<span>" . __('( leave blank for global changes )','ub') , '</span>';
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('and replace it with','ub');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[{$dt}][replace]' value='' class='long replace' />";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo '</div>';

		echo '</div>';

	}

	/**
	 * Save admin settings
	 **/
	function update_admin_page( $status = false ) {

		if(!empty($_POST['delete'])) {
			$deletekeys = (array) $_POST['deletecheck'];
		} else {
			$deletekeys = array();
		}

		if(!empty($_POST['swtble'])) {
			$save = array();
			$op = array();
			foreach($_POST['swtble'] as $key => $table) {
				if(!in_array($key, $deletekeys) && !empty($table['find'])) {
					$save[addslashes($key)]['title'] = 'Text Change : ' . htmlentities($table['find'],ENT_QUOTES, 'UTF-8');
					$save[addslashes($key)]['find'] = $table['find'];
					$save[addslashes($key)]['ignorecase'] = $table['ignorecase'];
					$save[addslashes($key)]['domain'] = $table['domain'];
					$save[addslashes($key)]['replace'] = $table['replace'];

					if($table['ignorecase'] == '1') {
						$op['domain-' . $table['domain']]['find'][] = '/' . stripslashes($table['find']) . '/i';
					} else {
						$op['domain-' . $table['domain']]['find'][] = '/' . stripslashes($table['find']) . '/';
					}
					$op['domain-' . $table['domain']]['replace'][] = stripslashes($table['replace']);

				}

			}

			if(!empty($op)) {
				ub_update_option('translation_ops',$op);
				ub_update_option('translation_table',$save);
			} else {
				ub_update_option('translation_ops', 'none');
				ub_update_option('translation_table', 'none');
			}
		}

		if($status === false) {
			return $status;
		} else {
			return true;
		}

	}

	/**
	 * Admin page output
	 **/
	function handle_admin_page() {

		$translations = $this->get_translation_table(true);

		echo '<div class="tablenav">';
			echo '<div class="alignleft">';
			echo '<input class="button-secondary del" type="submit" name="delete" value="' . __('Delete selected', 'ub') . '" />';
			echo '</div>';

			echo '<div class="alignright">';
			echo '</div>';

		echo '</div>';

		echo "<div id='entryholder'>";

		if($translations && is_array($translations)) {

			foreach($translations as $key => $table) {

				$this->show_table($key, $table);

			}

		} else {

			$this->show_table_template( time() );

		}

		echo "</div>";	// Entry holder

		echo '<div class="tablenav">';
			echo '<div class="alignleft">';
			echo '<input class="button-secondary del" type="submit" name="delete" value="' . __('Delete selected', 'ub') . '" />';
			echo '</div>';

			echo '<div class="alignright">';
			echo '</div>';
		echo '</div>';

		$this->show_table_template();


	}

	/**
	 * Cache translation tables
	 **/
	function get_translation_table($reload = false) {

		if($this->translationtable && !$reload) {
			return $this->translationtable;
		} else {
			$this->translationtable = ub_get_option('translation_table', array());
			return $this->translationtable;
		}

	}

	/**
	 * Cache translations
	 **/
	function get_translation_ops($reload = false) {

		if($this->translationops && !$reload) {
			return $this->translationops;
		} else {
			$this->translationops = ub_get_option( 'translation_ops', array() );
			return $this->translationops;
		}

	}

	/**
	 * Replace text
	 **/
	function replace_text( $transtext, $normtext, $domain ) {

		$tt = $this->get_translation_ops();

		if( !is_array( $tt ) )
			return $transtext;

		$toprocess = array();
		if( isset( $tt['domain-' . $domain]['find'] ) && isset( $tt['domain-']['find'] ) )
			$toprocess =  (array) $tt['domain-' . $domain]['find'] + (array) $tt['domain-']['find'];
		elseif( isset( $tt['domain-' . $domain]['find'] ) )
			$toprocess =  (array) $tt['domain-' . $domain]['find'];
		elseif( isset( $tt['domain-']['find'] ) )
			$toprocess =  (array) $tt['domain-']['find'];

		$toreplace = array();
		if( isset( $tt['domain-' . $domain]['replace'] ) && isset( $tt['domain-']['replace'] ) )
			$toreplace =  (array) $tt['domain-' . $domain]['replace'] + (array) $tt['domain-']['replace'];
		elseif( isset( $tt['domain-' . $domain]['replace'] ) )
			$toreplace =  (array) $tt['domain-' . $domain]['replace'];
		elseif( isset( $tt['domain-']['replace'] ) )
			$toreplace =  (array) $tt['domain-']['replace'];

		$transtext = preg_replace( $toprocess, $toreplace, $transtext );

		return $transtext;
	}

	/**
	 * Start output buffer
	 **/
	function start_cache() {
		ob_start();
	}

	/**
	 * End output buffer
	 **/
	function end_cache() {
		$tt = $this->get_translation_ops();

		if( !is_array( $tt ) ) {
			ob_end_flush();
		} else {
			$content = ob_get_contents();

			$toprocess = (array) $tt['domain-']['find'];
			$toreplace = (array) $tt['domain-']['replace'];

			$content = preg_replace( $toprocess, $toreplace, $content );

			ob_end_clean();
			echo $content;
		}
	}

}

$swtc = new Site_Wide_Text_Change();

