=== Plugin Name ===
Contributors: sirzooro
Tags: header, footer, headers, footers, content, wpmu, meta, meta tags
Requires at least: 2.7
Tested up to: 3.2.9
Stable tag: 1.2

This plugin adds custom header and footer for main page content.

== Description ==

As a blog owner you may want to insert some extra content above and/or below your posts, post lists and others. You can do this by editing template files directly, but this approach is a bit inconvenient - you have to edit files every time you want to change it. Additionally this may be not acceptable if you share template between multiple blogs (e.g. while using WPMU).

Therefore this is a place for plugin. Unlike other plugins, the Custom Headers and Footers plugin does not modify post contents on the fly - it uses WordPress loop directly to insert header when loop starts, and footer when it ends. With this approach you will avoid unexpected side effects, e.g. displaying header only (or part of it) as a post excerpt.

You can also add extra code to `<head>` section of blog (e.g. additional meta headers), and to blog footer.

You can use smiles and shortcodes in your custom post header/footer and to blog footer - plugin replaces them with corresponding images or HTML code.

Available translations:

* English
* Polish (pl_PL) - done by me
* Russian (ru_RU) - thanks Fat Cow
* Italian (it_IT) - thanks [Gianni](http://gidibao.net/)
* Dutch (nl_NL) - thanks [Rene](http://wordpresspluginguide.com/)
* French (fr_FR) - thanks [Frédéric](http://www.traducteurs.com/)

[Changelog](http://wordpress.org/extend/plugins/custom-headers-and-footers/changelog/)

== Installation ==

1. Upload `custom-headers-and-footers` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure plugin (go to the Settings/Custom Headers and Footers)
1. Enjoy :)

== Changelog ==

= 1.2 =
* Fix: do not show post header and footer in feeds;
* Enh: parse smiles and shortcodes in post header/footer and in blog footer;
* Marked as compatible with WP 3.2.x

= 1.1.5 =
* Updated Italian translation (thanks Gianni)

= 1.1.4 =
* Added French translation (thanks Frédéric)

= 1.1.3 =
* Marked as compatible with WP 3.0.x

= 1.1.2 =
* Code cleanup

= 1.1.1 =
* Added Dutch translation (thanks Rene)

= 1.1 =
* Added options to add entries in `<head>` section and in blog footer;
* Code cleanup

= 1.0.5 =
* Marked as compatible with WP 2.9.x

= 1.0.4 =
* Added Italian translation (thanks Gianni)

= 1.0.3 =
* Marked as compatible with WP 2.8.5

= 1.0.2 =
* Added Russian translation (thanks Fat Cow)

= 1.0.1 =
* Added Polish translation

= 1.0 =
* Initial version
