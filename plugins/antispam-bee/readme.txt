=== Antispam Bee ===
Contributors: sergej.mueller
Tags: antispam, spam, comments, trackback
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5RDDW9FEHGLG6
Requires at least: 3.3
Tested up to: 3.4
Stable tag: trunk



Anonymous and independent antispam solution. Detect comment and trackback spam. Includes statistics and notifications.



== Description ==

= Kill spam =
Antispam Bee is simple to use, has many options and filters. Does not store data on remote servers. Really.

= Features =
* Very fast execution
* Spam counter on dashboard
* Anonymous and confidential
* Trackback and pingback check
* Saves no data on remote servers
* No need to adjust any templates
* Cleaning up after plugin removal
* Support for the Project Honey Pot
* Accordingly no outgoing connection
* Interactive statistics on dashboard
* Automatically cleanup the spam folder
* Allow comments only in certain language
* Spam may be marked or deleted immediately
* Email notifications about new spam comments
* Quick & Dirty: activate, set settings, done!
* Optional strict check for incomming comments
* Block comments and pings from specific countries
* WordPress 3.x ready: Design as well as technical
* Consider comments which are already marked as spam

= Counter =
`<?php do_action('antispam_bee_count') ?> spam comments blocked by
<a href="http://antispambee.com">Antispam Bee</a>`

= Requirements =
* PHP 5.1.2
* WordPress 3.3

= Documentation =
* [Antispam Bee: Antispam für WordPress](http://playground.ebiene.de/antispam-bee-wordpress-plugin/ "Antispam für WordPress") (DE)

= Author =
* [Google+](https://plus.google.com/110569673423509816572 "Google+")
* [Plugins](http://wpcoder.de "Plugins")
* [Portfolio](http://ebiene.de "Portfolio")



== Changelog ==

= 2.4.3 =
* Check for basic requirements
* Remove the sidebar plugin icon
* Set the Google API calls to SSL
* Compatibility with WordPress 3.4
* Add retina plugin icon on options
* Depending on WordPress settings: anonymous comments allowed

= 2.4.2 =
* New geo ip location service (without the api key)
* Code cleanup: Replacement of `@` characters by a function
* JS-Fallback for missing jQuery UI

= 2.4.1 =
* Add russian translation
* Fix for the textarea replace
* Detect and hide admin notices

= 2.4 =
* Support for IPv6
* Source code revision
* Delete spam by reason
* Changing the user interface
* Requirements: PHP 5.1.2 and WordPress 3.3

= 2.3 =
* Xmas Edition

= 2.2 =
* Interactive Dashboard Stats

= 2.1 =
* Remove Google Translate API support

= 2.0 =
* Allow comments only in certain language (English/German)
* Consider comments which are already marked as spam
* Dashboard Stats: Change from canvas to image format
* System requirements: WordPress 2.8
* Removal of the migration script
* Increase plugin security

= 1.9 =
* Dashboard History Stats (HTML5 Canvas)

= 1.8 =
* Support for the new IPInfoDB API (including API Key)

= 1.7 =
* Black and whitelisting for specific countries
* "Project Honey Pot" as a optional spammer source
* Spam reason in the notification email
* Visual refresh of the notification email
* Advanced GUI changes + Fold-out options

= 1.6 =
* Support for WordPress 3.0
* System requirements: WordPress 2.7
* Code optimization

= 1.5 =
* Compatibility with WPtouch
* Add support for do_action
* Translation to Portuguese of Brazil

= 1.4 =
* Enable stricter inspection for incomming comments
* Do not check if the author has already commented and approved

= 1.3 =
* New code structure
* Email notifications about new spam comments
* Novel Algorithm: Advanced spam checking

= 1.2 =
* Antispam Bee spam counter on dashboard

= 1.1 =
* Adds support for WordPress new changelog readme.txt standard
* Various changes for more speed, usability and security

= 1.0 =
* Adds WordPress 2.8 support

= 0.9 =
* Mark as spam only comments or only pings

= 0.8 =
* Optical adjustments of the settings page
* Translation for Simplified Chinese, Spanish and Catalan

= 0.7 =
* Spam folder cleanup after X days
* Optional hide the &quot;MARKED AS SPAM&quot; note
* Language support for Italian and Turkish

= 0.6 =
* Language support for English, German, Russian

= 0.5 =
* Workaround for empty comments

= 0.4 =
* Option for trackback and pingback protection

= 0.3 =
* Trackback and Pingback spam protection



== Screenshots ==

1. Antispam Bee settings