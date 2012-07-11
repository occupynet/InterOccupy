=== Simple Page Ordering ===
Contributors: jakemgold, 10up, thinkoomph
Donate link: http://get10up.com/plugins/simple-page-ordering-wordpress/
Tags: order, re-order, ordering, pages, page, manage, menu_order, hierarchical, ajax, drag-and-drop, admin
Requires at least: 3.1
Tested up to: 3.2.1
Stable tag: 1.0

Order your pages and other hierarchical post types with simple drag and drop. Also adds a drop down to change items per page.

== Description ==

Order your pages, hierarchical custom post types, or custom post types with "page-attributes" with simple drag and drop on the usual page list screen. 

The following video is from an early build (0.7) that has been significantly refined, but still demonstrates the concept.

[youtube http://www.youtube.com/watch?v=wWEVW78VF30]

Simply drag and drop the page into your desired position! It's that simple. No new admin menus pages, no clunky user interfaces that feel bolted onto WordPress. Just drag and drop on the page or post-type list screen.

To facilitate menu order management on sites with many pages, the plug-in also adds a new drop down filter allowing you to customize the paging (pages per page) on the page admin screen. Your last choice will even be saved whenever you return (on a user to user basis and post type by post type basis)!

The plug-in is "capabilities smart" - only users with the ability to edit others' pages (i.e. editors and administrators) will be able to reorder pages.

Integrated help is included! Just click the "help" tab toward the top right of the screen; the help is below the standard help for the screen.

Note that this plug-in only allows drag and drop resort within the same "branch" in the page tree / hierarchy. You can still instantly change the hierarchy by using the Quick Edit feature built into WordPress and changing the "Parent". The intention is to avoid confusion about "where" the user is trying to put the page. For example, when moving a page after another page's last child, are you trying to make it a child of the other page, or position it after the other page? Ideas are welcome.

You must have JavaScript enabled for this plug-in to work. Please note that the plug-in is currently only minimally compatible with Internet Explorer 7 and earlier, due to limitations within those browsers.


== Installation ==

1. Install easily with the WordPress plugin control panel or manually download the plugin and upload the extracted
folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Start dragging and dropping by going to the "Page" admin menu (or custom post type equivalent)!


== Screenshots ==

1. Changing the paging (items per page)
1. Dragging the page to its new position
1. Processing indicator


== Changelog ==

= 1.0 =
* Fix unexpected page ordering results when pages have not been explictly ordered yet (sorts by menu_order, then title, not just menu_order)
* Support for ordering non-hierarchical post types that have "page-attributes" support
* New filter link for "Sort by Order" to restore (hierarchical) or set (non-hierarchical, page attributes support) post list sort to menu order
* Fix "per page" drop down filter selection not saving between page loads (was broken in 3.1)
* Users are now forced to wait for current sort operation to finish before they can sort another item
* Smarter about "not sortable" view states
* Localization ready! Rough Spanish translation included.
* Items are always ordered with positive integers (potential negative sort orders had some performance benefits in last version, but sometimes caused issues)
* Assorted other performance and code improvements

= 0.9.6 =
* Fix for broken inline editing (quick edit) fields in Firefox

= 0.9.5 =
* Smarter awareness of "sorted" modes in WordPress 3.1 (can only use when sorted by menu order)
* Smarter awareness of "quick edit" mode (can't drag)
* Generally simplified / better organized code

= 0.9 =
* Fix page count display always showing "0" on non-hierarchical post types (Showing 1-X of X)
* Fix hidden menu order not updating after sort (causing Quick Edit to reset order when used right after sorting)
* "Move" cursor only set if JavaScript enabled
* Added further directions in the plug-in description (some users were confused about how to use it)
* Basic compatibility with 3.1 RC (prevent clashes with post list sorting)

= 0.8.4 =
* Loosened constraints on drag and drop to ease dropping into top and bottom position
* Fixed row background staying "white" after dropping into a new position
* Fixed double border on the bottom of the row while dragging
* Improved some terminology (with custom post types in mind)

= 0.8.2 =
* Simplified code - consolidated hooks
* Updated version requirements