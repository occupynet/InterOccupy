=== Theme My Profile ===
Contributors: jfarthing84
Donate link: http://www.jfarthing.com/donate
Tags: theme, profile, custom, user, role
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 1.3.1

Allows you to theme a user's profile based upon their role.


== Description ==

This plugin allows you to theme a user's profile based upon their role. It even includes custom roles if you have any.


== Installation ==

1. Upload the plugin to your 'wp-content/plugins' directory
1. Activate the plugin


== Frequently Asked Questions ==

Please visit http://www.jfarthing.com/support/forum/theme-my-profile for any support!


== Changelog ==

= 1.3.1 =
* Fix "Call to undefined function ..." error message for is_user_logged_in()
* Add Theme_My_Profile::is_profile_page() function
* Add Theme_My_Profile::get_profile_page_link() function
* Various code optimization, standardization and clean-up

= 1.3 =
* Another code rewrite (Update for WP 3.0)
* Revert to using page for profile
* Option to display profile in pagelist always, when logged in or never

= 1.2.1 =
* Check if '_wp_get_user_contactmethods' function exists (pre 2.9 compatability)

= 1.2 =
* Allow 'theme-my-profile.css' to be disabled
* Allow 'theme-my-profile.css' to be loaded from current theme directory
* Cleaned up and rewrote most code
* Drop support for WP versions below 2.8

= 1.1.8 =
* Replaced all calls to is_user_logged_in() with check of global $user_ID

= 1.1.7 =
* Fixed a bug that produced warnings when not logged in

= 1.1.6 =
* Included Spanish translation

= 1.1.5 =
* Fixed the load_plugin_textdomain() call
* Fixed all require() calls containing ABSPATH

= 1.1.4 =
* Fixed and updated many gettext calls for internationalization

= 1.1.3 =
* Fixed various CSS and XHTML validation bugs
* Fixed the method in which current user role is determined

= 1.1.2 =
* Fixed a bug that redirected to a bad URL upon successful profile update

= 1.1.1 =
* Fixed a bug that created a bad link for user profile

= 1.1 =
* Fixed a variable naming bug that caused an infinite redirect loop
* Added the option to lockout users from the admin area based upon their role

= 1.0.1 =
* Fixed a bug regarding additional user capabilities

= 1.0 =
* Initial release version