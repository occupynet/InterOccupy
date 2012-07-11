=== Plugin Name ===
Google Custom Search
Contributors: edwinkwan
Donate link: http://littleHandyTips.com/support
Tags: google search, google custom search, search results, pop-up, dialog, jquery, multiple site search
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 1.3.4

This plugin uses Google's Search Engine to search your site's contents!
You can also configure it through google to search multiple sites/domains.

== Description ==


This plugin uses the power of Google to search the contents on your wordpress site.

Wordpress has a relatively good search functionality but tends to get sluggish and doesn't match keywords that well.
This is even more apparent when your site is quite large and you have many posts and/or pages.
In addition, wordpress search functionality display results ordered by date and not by its relevance to the keywords.

Google Custom Search Engine is not limited to just your site. You can configure it through google to search multiple sites/domains.

Or how about having a search Engine which searches all websites within your blog's niche?


Combining the power of Google's search engine along with the familiarity of their interface, this is a must have plugin for all websites.


The plugin is very **flexible** and you can configure both the search box and the search results.

Google Custom Search widget's search box can be displayed as either a widget or placed anywhere in the code (php and wordpress theme familiarity required to do this).

The search results can be displayed in one of three formats:

1. As a pop-up resizable dialog.
2. Within the widget, under the search box.
3. Displayed anywhere in the code (php and wordpress theme familiarity required to do this).


To display the search-box anywhere in the code, the following method should be used

`<?php display_search_box($display_results_option); ?>`

where $display_results_option is one of the following three options:

* **DISPLAY_RESULTS_AS_POP_UP**           - display the search results as a pop-up resizeable dialog
* **DISPLAY_RESULTS_IN_UNDER_SEARCH_BOX** - display the search results under the search box
* **DISPLAY_RESULTS_CUSTOM**		- display the search results in the place you have specified.

e.g: `<?php display_search_box(DISPLAY_RESULTS_AS_POP_UP); ?>`


To specify a location where the search result is to be displayed, the following method should be used.

`<?php display_gsc_results(); ?>`




Find out more at http://littlehandytips.com/plugins/google-custom-search/

A handy plugin from little Handy Tips :)

Support Us and make a donation at http://littlehandytips.com/support/

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the folder `google-custom-search` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Options and configure the Google Custom Search.
   -You might need to setup a Custom Search Engine Account with Google.
4. Add Google Custom Search Widget.

Find out more at http://littlehandytips.com/plugins/google-custom-search/

A handy plugin from little Handy Tips :)


== Frequently Asked Questions ==

= Does this plugin replaces the wordpress plugin? =

Yes and No.

The Google Custom Search plugin does not override the wordpress search functionality.
You can choose to have it replace wordpress's search by removing the wordpress search and putting this plugin instead.

= My current website search box is not in a widget. Can I replace wordpress search with this plugin? =

Yes. You sure can. 

You will need to modify the wordpress theme you are using and replace the wordpress search code with this plugin's API.

To display the search-box anywhere in the code, the following method should be used

`<?php display_search_box($display_results_option); ?>`

where $display_results_option is one of the following three options:

* **DISPLAY_RESULTS_AS_POP_UP**           - displays the search results as a pop-up resizeable dialog
* **DISPLAY_RESULTS_IN_UNDER_SEARCH_BOX** - displays the search results under the search box
* **DISPLAY_RESULTS_CUSTOM**              - displays the search results in the place you have specified.

e.g: `<?php display_search_box(DISPLAY_RESULTS_AS_POP_UP); ?>`

= How do I specify where the search results are to be displayed? =

The search results can be configured within the widget to display in one of three options:

* As a pop-up dialog.
* Within the widget.
* In an area you specify.

If you want to specify the location for the results to be displayed, you will need to do the following:

1. Make sure the setting on the widget for "Display Results" is set to "Custom"
2. Add the following line of code into your wordpress theme at where you want to results to be displayed.`<?php display_gsc_results(); ?>`


= Can Google Custom Search search other/multiple websites? =

Yes.

This configuration will need to be done with Google and documentation for that is provided by them.
Go to http://www.google.com/cse/manage/all to configure your Google Custom Search Engine.

= How do I create a Google Custom Search Engine? =

Go to http://www.google.com/cse/manage/create and follow the instructions.



Find out more at http://littlehandytips.com/plugins/google-custom-search/

A handy plugin from little Handy Tips :)


== Screenshots ==

1. Web Site (http://wollongongFitness.com) showing example of search results being displayed in a Custom location.
2. Search Results being displayed as a pop-up dialog.

== Changelog ==

= 1.3.4 =
* Fix compatibility issue with wordpress 3.4.
* Fix issue with pop-up box display not working on some browser.
* June 18th 2012

= 1.3 =
* Added multilingual support.
* Added option to have results displayed in current window instead of a new window.
* Added option to not display search button
* November 29th 2010.

= 1.2 =
* Fixes problem where plugin interferes with other wordpress drag and drop functionality.
* August 22st 2010.

= 1.1 =
* Added support for multiple custom search boxes in a single page.
* August 12th 2010.


= 1.0 =
* Initial release.
* August 5th 2010.

== Upgrade Notice == 

Replace the existing files with the newer version
