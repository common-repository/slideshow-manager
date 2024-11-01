=== Slideshow Manager ===
Contributors: rasmusjoh
Donate link: http://rasmus.pw/slideshow-manager
Tags: slideshow, slideshow manager, gallery, coin slider, nivo slider
Requires at least: 3.2.1
Tested up to: 3.7.1
Stable tag: 2.1.3
License: GPLv2

The Slideshow Manager provides an easy access to a Coin Slider within WordPress admin panel.

== Description ==

The Slideshow Manager provides an easy access to a jQuery-based Coin Slider. The admin panel makes it easy to tweak the most commonly used Coin Slider options from within WordPress admin panel.

Features:

*	Upload/Delete pictures
*	Add captions to slides
*	Add hyperlinks to slides
*	Change the order of slides
*	Change the coin-slider options
*	Create multiple slideshows
*	Internalization support

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin to the '/wp-content/plugins/ directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use as a shortcode: `[slideshow id="default"]` or use a function in a theme file `<?php if (function_exists('slideshow')) { slideshow('default', true); } ?>`

== Frequently Asked Questions ==

= Slideshow manager doesn't support something I want =

You can submit your new ideas here:

http://wordpress.org/support/plugin/slideshow-manager

Or meanwhile you can try out these alternatives:

http://wordpress.org/extend/plugins/meteor-slides/

http://wordpress.org/extend/plugins/wp-cycle/

== Screenshots ==

1. Backend
2. Frontend

== Changelog ==

= 2.1.3 =
 - Fixed mobile detection
 - Added Russian translation (Denis Larin)

= 2.1.2 =
 - Fixed escaping apostrophes and quotations marks

= 2.1 =
 - More options
 - Bug fixes

= 2.0.2 =
 - Fixed UTF8
 - After disabling and deleting the plugin, it will also clean up the created tables

= 2.0.1 =
 - Replaced hardcoded next and prev links with images
 - Fixed order of slideshow tabs when creating new tabs
 - Added possibility to add slideshow as a function in your theme file

= 2.0 =
 - Possibility to create multiple slideshows
 - Other minor bug fixes
 - Added Estonian translation

= 1.01 =
 - Fixed menu position
 - Fixed thumbnail error when scaling image

= 1.0 =
 - Initial release

== Upgrade Notice ==
 - Fixed mobile detection
 - Possibility to create multiple slideshows
 - Fixed escaping apostrophes and quotations marks