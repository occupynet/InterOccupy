=== Plugin Name ===

Contributors: nickiler
Tags: vanilla, authentication, sso, cross-domain, login, forum
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.1.1

This plugin allows your WordPress users to authenticate and login to Vanilla forums like Facebook Connect. 

== Description ==

WordPress users logon seamlessly via jsConnect and SSO into your Vanilla Forums. Just install [jsConnect](http://vanillaforums.org/addon/jsconnect-plugin) addon in Vanilla, then copy links and codes from WP to jsConnect and your linked up. Takes about 5 minutes to download and install.

I built this for myself and decided to polish it up for open source release. None of the current methods for linking Vanilla and WordPress work for my server and DNS setup. I will follow the jsConnect project
and incorporate updates as I get notified.

This plugin relies completely on jsConnect and cannot modify any parts of Vanilla what-so-ever. If you need special features
in Vanilla than this plugin will not work for you. However, if your looking for a simpler way to get your wordpress users
logged into a Vanilla forums without hacking up your code files, this is the right plugin for you.

jsConnect Features:

*	Authenticate WordPress Users in Vanilla forums similar to Facebook Connect.
*	Cross-domain authentication and can be used on different servers.
*	Easy to install and setup.

WP Vanilla Connect Features:

*	Seamless WordPress integration with autologin via login URL.
*	Automatically generates URL's for jsConnect (Vanilla's recommended method in place of ProxyConnect).
*	Attach Gravatar's to users instead of the default image if their email address is registered on Gravatar.com. Has an option to serve over SSL and Non-SSL.
*	Easy to install and setup, takes less than two minutes.
*	Compatible with WordPress Standard and Muti sites.
* 	Don't have to hack up WordPress and Vanilla config and template filee, which are bound to break on any future platform updates.

== Installation ==

In order to install this plugin you will need to either download the file and manually upload it from the WordPress dashboard or
you can search for it in the WordPress repository directly from WordPress plugins.

1. Upload `vanilla-connect` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the URL that points to the root of your Vanilla Forums
4. Copy the `client_id` and `secret` from WP Vanilla Connect and paste these values into jsConnect and test it using jsConnect test url.
5. Copy your generated jsConnect Login and Redirection URL to enable logins and redirection back to forums after WordPress login.

== Frequently Asked Questions ==

= Does this plugin work with the <embed> addon that comes with Vanilla? =

No, not offically. I have tested this under my own server configuration and everything works just fine. I can't however garantee it works for everyone becuase the VanillaForums <Embedded> plugin is pretty old and not updated for a long time. If you have issues just paste the javascript embed code directly into a page. This also works just fine for my setup. There are ways to hook into WP using filters and actions if you need something more advanced.

= Can I disable the other login methods in Vanilla? =

Yes, you can set Vanilla to only use SSO plugins and disable all other methods. However, you cannot remove the login button
which pop's up the SSO WordPress login link as well as HTML that will NOT work if a user types there username and password. 
They MUST use the jsConnect login URL.

= Will my users be auto logged into Vanilla forums? =

Yes and no, If someone logs on from the forums they will be redirected and logged in automatically. If they are already logged in to WordPress you will need to send them thru the special jsConnect URL, otherwise they will not be logged in just by hitting the landing page of Vanilla. 

= Will Vanilla share my theme settings files? =

No, This plugin is similar to Facebook Connect. It only authenticates WordPress users in Vanilla. That's it.

= Who is developing jsConnect and how do I request features? =

jsConnect is maintained by a Vanilla co-founder by the name of Todd and in now favored over the older ProxyConnect addon. 

= I have users connecting with WP Vanilla Connect, can I change my hash codes? =

You can, but I would not recommend it. Each Vanilla user that was created with the old hashes will be required to re-link their accounts. You should enable the new "Protect Data" option and copy and paste them in a safe place in case you need to restore your settings.

== Screenshots ==

1. When logged into WordPress but not Vanilla you will see this screen. If you forward them using the jsConnect URL they will instead be auto logged on.
2. A shot of the back-end.

== Changelog ==

= 1.1.1 =

* Added feature that removes the Vanilla logged on cookie when you log out of WordPress. 

= 1.1 =

* Moved plugin activation hook into the else of the if plugin activated for efficiency
* Changed the test url to use ajax if javascript is enabled, otherwise will fallback on href a tag
* Added a protect option data to save on plugin removal
* Added Method to remove option on uninstall for neatness if not protected
* Added new style and cleaned up CSS
* Added Gravatar URL to test section
* Added Vanilla login URL for already logged on WordPress users

= 1.0b =

Released beta

== Upgrade Notice ==

= 1.1.1 =

* Added feature to remove Vanilla cookie when user logs out of WP.