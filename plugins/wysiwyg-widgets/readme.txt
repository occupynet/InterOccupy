=== Plugin Name ===
Contributors: DvanKooten
Donate link: http://dannyvankooten.com/donate/
Tags: widget,wysiwyg,wysiwyg widget,rich text,rich text widget,widget editor,text widget,visual widget,image widget,tinymce,fckeditor
Requires at least: 3.3
Tested up to: 3.3.1
Stable tag: 1.2

Adds a WYSIWYG Widget with a rich text editor and media upload functions.

== Description ==

= WYSIWYG Widgets / Rich text Widgets =

This plugin adds a widget of the type 'WYSIWYG Widget' to your widgets. This widget behaves exactly like the default WP Text Widget with the only difference being
the WYSIWYG / rich-text editor and the ability to insert media into your widget. Everything works the same as are used to from the default post editor.

**WP 3.2.x users and below, please use [version 1.1.1](http://downloads.wordpress.org/plugin/wysiwyg-widgets.1.1.1.zip). For WP 3.3+ users, use the latest version!**

**Features:**

* WYSIWYG / Rich-text / TinyMCE widget editor
* Insert media like images or video into your widgets
* Create stunning widget content without having to know any HTML
* Create easy lists in your widgets
* Use WP Links dialog to easily link to any of your pages or posts from a widget

**More info:**

* [WYSIWYG Widgets](http://dannyvankooten.com/wordpress-plugins/wysiwyg-widgets/)
* Check out more [WordPress plugins](http://dannyvankooten.com/wordpress-plugins/) by the same author
* Follow me on Twitter for lightning fast support and updates: [@DannyvanKooten](http://twitter.com/dannyvankooten)
* If you're a developer willing to contribute, [WYSIWYG Widgets is now on Github](http://github.com/dannyvankooten/WYSIWYG-Widgets-for-WP). Please help to improve WYSIWYG Widgets by forking. :)

== Installation ==

1. Upload the contents of wysiwyg-widgets.zip to your plugins directory.
1. Activate the plugin
1. Drag the widget to one of your widget areas, click the textarea and watch the WYSIWYG overlay fade in.
1. Play around!

== Frequently Asked Questions ==

= What does this plugin do? =

This plugin adds a widget of the type 'WYSIWYG Widget' to your widgets. This widget behaves exactly like the default WP Text Widget with the only difference being
the rich text editor and the ability to insert media like images or video. When clicking the textarea a thickbox screen fades in with the rich text editor where
you can edit the contents of your text widget without having to know HTML.

= What does WYSIWYG stand for? =

What You See Is What You Get

= Can I switch between 'Visual' and 'HTML' mode with this plugin? =

Yes, all the default options that you are used to from the post editor are available for the widget editor.

== Screenshots ==

1. The rich text / wysiwyg widget editor in action

== Changelog ==
= 1.2 =
* Updated the plugin for WP 3.3. Broke backwards compatibility (on purpose), so when running WP 3.2.x and below: stick with [version 1.1.1](http://downloads.wordpress.org/plugin/wysiwyg-widgets.1.1.1.zip).

= 1.1.2 =
* Temporary fix for WP 3.3+

= 1.1.1 =
* Fixed problem with link dialog reloading page upon submit

= 1.1 =
* Changed the way WYSIWYG Widget works, no more overlay, just a WYSIWYG editor in your widget form.
* Fixed full-screen mode
* Fixed link dialog for WP versions below 3.2
* Fixed strange browser compatibility bug
* Fixed inconstistent working
* Added the ability to use shortcodes in WYSIWYG Widget's text

= 1.0.7 =
* Fixed small bug that broke the WP link dialog for WP versions older then 3.2
* Fixed issue with lists and weird non-breaking spaces
* Added compatibility with Dean's FCKEditor for Wordpress plugin
* Improved JS

**NOTE**: In this version some things were changed regarding the auto-paragraphing. This is now being handled by TinyMCE instead of WordPress, so when updating please run trough your widgets to correct this. :) 

= 1.0.6 =
* Added backwards compatibility for WP installs below version 3.2 Sorry for the quick push!

= 1.0.5 =
* Fixed issue for WP3.2 installs, wp_tiny_mce_preload_dialogs is no valid callback. Function got renamed.

= 1.0.4 =
* Cleaned up code
* Improved loading of TinyMCE
* Fixed issue with RTL installs

= 1.0.3 =
* Bugfix: Hided the #wp-link block, was appearing in footer on widgets.php page.
* Improvement: Removed buttons added by external plugins, most likely causing issues. (eg Jetpack)
* Improvement: Increase textarea size after opening WYSIWYG overlay.
* Improvement: Use 'escape' key to close WYSIWYG editor overlay without saving changes.

= 1.0.2 =
* Bugfix: Fixed undefined index in dvk-plugin-admin.php
* Bugfix: Removed `esc_textarea` which caused TinyMCE to break
* Improvement: Minor CSS and JS improvements, 'Send to widget' button is now always visible
* Improvement: Added a widget description
* Improvement: Now using the correct way to set widget form width and height

= 1.0.1 =
* Bugfix: Fixed the default title, it's now an empty string. ('')

= 1.0 = 
* Initial release