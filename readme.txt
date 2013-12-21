=== Debug Bar Shortcodes ===
Contributors: jrf
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=995SSNDTCVBJG
Tags: debugbar, debug-bar, Debug Bar, Shortcodes, Debug Bar Shortcodes, shortcode list, list shortcodes, shortcodes reference
Requires at least: 3.3
Tested up to: 3.8
Stable tag: 1.0
Depends: debug-bar
License: GPLv2

Debug Bar Shortcodes adds a new panel to the Debug Bar that displays the registered shortcodes for the current request.

== Description ==

Debug Bar Shortcodes adds a new panel to the Debug Bar that displays the registered shortcodes for the current request.

Additionally it will show you:

* Which function/method is called by the shortcode
* Whether the shortcode is used in current the post/page/post type and how (only when on singular)
* Any additional information available about the shortcode - want to control the extra info about your own shortcodes ? You can! See the [FAQ](http://wordpress.org/plugins/debug-bar-shortcodes/faq/) for more info.
* Find out all pages/posts/etc on which a shortcode is used


= Why is it useful to have insight into the shortcodes ? =

There are a number of uses I can think of:

* If you are a blog author: to know which shortcodes you can use in your blogposts/pages.
* If you are a blog author/ web designer / web master: if you switch or remove plugins, to know in which posts/pages you  need to remove/replace old shortcodes.
* If you are a web designer/web master: to know which shortcodes you can use in theme files.
* If you are a web designer/web master: to avoid name conflicts for website specific shortcodes.
* If you are a developer: to avoid name conflicts with shortcodes registered by other plugins/themes.
* If you are a developer: to check whether your shortcode registers properly and whether the conditionals are applied correctly.


= Important =

This plugin requires the [Debug Bar](http://wordpress.org/extend/plugins/debug-bar/) plugin to be installed and activated.

= Credits =
* The additional information functionality is inspired by [LRH-Shortcode list](http://wordpress.org/plugins/lrh-shortcode-list/) and [Shortcode reference](http://wordpress.org/plugins/shortcode-reference/).
* The finding of shortcode uses throughout the site is inspired by [TR All Shortcodes](http://wordpress.org/plugins/tr-all-shortcodes/)


***********************************

If you like this plugin, please [rate and/or review](http://wordpress.org/support/view/plugin-reviews/debug-bar-shortcodes) it. If you have ideas on how to make the plugin even better or if you have found any bugs, please report these in the [Support Forum](http://wordpress.org/support/plugin/debug-bar-shortcodes) or in the [GitHub repository](https://github.com/jrfnl/Debug-Bar-Shortcodes/issues).



== Frequently Asked Questions ==

= Can it be used on a live site ? =
This plugin is only meant to be used for development purposes, but shouldn't cause any issues if run on a production site.


= What are shortcodes ? =
> A shortcode is a WordPress-specific code that lets you do nifty things with very little effort. Shortcodes can embed files or create objects that would normally require lots of complicated, ugly code in just one line. Shortcode = shortcut.
[Source](http://en.support.wordpress.com/shortcodes/)

For more information about using shortcodes in WordPress:
[WP Codex on shortcodes](http://codex.wordpress.org/Shortcode)
[WP Codex on the Shortcode API](http://codex.wordpress.org/Shortcode_API)


= Why is my shortcode not listed ? =

There are two possibilities here:
* Either your shortcode has [not been properly registered](http://codex.wordpress.org/Function_Reference/add_shortcode) using the ShortCode API.
* Or your shortcode might only be registered conditionally and the current page does not meet those conditions.


= The number of shortcodes differs depending on the requested page. How come ? =
See the previous answer.


= I'm using shortcode *abc* in page *xyz* and it doesn't show as used! =

To determine whether a shortcode is used in a page, only the *post content* is evaluated. If you add content to the page using shortcodes in other areas (for example: widgets), those uses will not be recognized.


= Can I use these shortcodes in the theme I'm building ? =
Generally speaking you can. However, don't forget to always [check whether the shortcode is registered](http://codex.wordpress.org/Function_Reference/shortcode_exists) before you use it (in a theme)! It may not be available on all pages and surely not on all WP installs.
`
if( shortcode_exists( 'shortcode' ) ) {
	// Your code here
	// do_shortcode( 'shortcode' );
}
`

= I'm a developer and would like to enrich the information displayed by this plugin about my shortcode. =
I've tried to make this as easy and painless as possible.

Just add a filter to enrich the information this plugin has about your shortcode. The easiest way is to use the `db_shortcodes_info_{shortcode}` filter which will only be applied to your shortcode.
`
add_filter( 'db_shortcodes_info_{your_shortcode}', 'filter_my_shortcode_info' );
function filter_my_shortcode_info( $info ) {
	// enrich the array
	return $info;
}
`

The $info array expects to receive (a selection of) the following information:
`array(
	'name'			=> (string) 'Friendly name for your shortcode',
	'description'	=> (string) 'Description of your shortcode',
	'self_closing'	=> (bool) true/bool, // whether the shortcode is self-closing
	'parameters'	=> array(
		'required'		=> array(
			'attribute'		=> (string) 'attribute description',
		),
		'optional'		=> array(
			'attribute'		=> (string) 'attribute description',
		),
	),
	'info_url'		=> '',
)
`


If you happen to already provide similar information for the [LHR-Shortcode list](http://wordpress.org/plugins/lrh-shortcode-list/) plugin, no need to do anything extra, that information will be picked up by this plugin.




= Why won't the plugin activate ? =
Have you read what it says in the beautifully red bar at the top of your plugins page ? As it says there, the Debug Bar plugin needs to be active for this plugin to work. If the Debug Bar plugin is not active, this plugin will automatically de-activate itself.


== Changelog ==

= 1.0 (2013-12-20) =
* Initial release


== Upgrade Notice ==

= 1.0 =
* Initial release


== Installation ==

1. Install Debug Bar if not already installed (http://wordpress.org/extend/plugins/debug-bar/)
1. Extract the .zip file for this plugin and upload its contents to the `/wp-content/plugins/` directory. Alternatively, you can install directly from the Plugin directory within your WordPress Install.
1. Activate the plugin through the "Plugins" menu in WordPress.

Be careful when you use this plugin on a live site. This plugin intended for development purposes.


== Screenshots ==
1. Debug Bar displaying Shortcodes

