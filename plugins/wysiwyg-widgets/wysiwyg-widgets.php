<?php
/*
Plugin Name: WYSIWYG Widgets
Plugin URI: http://DannyvanKooten.com/wordpress-plugins/wysiwyg-widgets/
Description: Adds a WYSIWYG Widget with a rich text editor and media upload functions.
Version: 1.2
Author: Danny van Kooten
Author URI: http://DannyvanKooten.com
License: GPL2
*/

/*  Copyright 2011  Danny van Kooten  (email : danny@vkimedia.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once('frontend/WYSIWYG_Widgets_Widget.php');
require_once('frontend/WYSIWYG_Widgets.php');
$WYSIWYG_Widgets = new WYSIWYG_Widgets();

if(is_admin()) {
	require_once('backend/WYSIWYG_Widgets_Admin.php');
	$WYSIWYG_Widgets_Admin = new WYSIWYG_Widgets_Admin();
}