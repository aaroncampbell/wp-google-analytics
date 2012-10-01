=== WP Google Analytics ===
Contributors: aaroncampbell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CLXNQ3DVK6M48
Tags: analytics, google, google analytics
Requires at least: 3.1
Tested up to: 3.5
Stable tag: 1.3.1

Lets you use <a href="http://analytics.google.com">Google Analytics</a> to track your WordPress site statistics

== Description ==

Lets you use <a href="http://analytics.google.com">Google Analytics</a> to track
your WordPress site statistics.  It is easily configurable to:

* Not log anything in the admin area
* Log 404 errors as events
* Log outgoing links as events
* Not log any user roles (administrators, editors, authors, etc)
* Use custom variables

== Installation ==

Use automatic installer.

== Frequently Asked Questions ==

= Where do I put my Google Analytics Code? =

WP Google Analytics has a config page under the settings tab in the admin area
of your site.  You can paste your tracking code from Google into the textarea on
this page.

= How do I track searches? =

WP Google Analytics used to help you track site searches before Google Analytics
started doing this natively.  While we still support tracking searches for
backwards compatibility, this feature has been deprecated and will eventually be
removed.  To track searches in Google Analytics follow this
<a href="http://support.google.com/analytics/bin/answer.py?hl=en&answer=1012264">Google support article</a>.
WordPress uses 's' as the query parameter.

= Can't I just paste the Google Analytics code into my template file? =

Absolutely, however in order to get a better idea of what is going on with your
site, it is often nice to have your own activities ignored, track 404s, searches
and even where users go when they leave your site.  WP Google Analytics lets you
easily do all these things.

== Upgrade Notice ==

= 1.3.1 =
Fix for custom variables not being tracked

= 1.3.0 =
Move to using events for tracking 404s and outgoing links and add support for custom variables

== Changelog ==

= 1.3.1 =
* Fixed custom variables not being tracked

= 1.3.0 =
* Refactored to use settings API - Props danielbachhuber
* Convert to singleton and instatiate class
* Convert tracking code field to ID, keeping backwards compat - Props danielbachhuber
* Custom variable support - Props danielbachhuber
* Track outgoing links and 404s as events
* Made the whole plugin translatable
* Deprecated tracking searches

= 1.2.5 =
* Fixed some notices. Props westi
* Update all links

= 1.2.4 =
* Removed the optional anonymous statistics collection.  Nothing is ever collected anymore.
* Changed & to &amp; in some more places to fix validation problems.

= 1.2.3 =
* Changed & to &amp; to fix validation problems.

= 1.2.2 =
* Fixed problem with code affecting Admin Javascript such as the TinyMCE editor

= 1.2.1 =
* Bug fix for the stats gathering

= 1.2.0 =
* No longer parses outgoing links in the admin section.
* Uses get_footer instead of wp_footer.  Too many themes aren't adding the wp_footer call.
* Options page updated
* Added optional anonymous statistics collection

= 1.1.0 =
* Major revamp to work better with the new Google Tracking Code.  It seems that outgoing links weren't being tracked properly.

= 1.0.0 =
* Added to wordpress.org repository

= 0.2 =
* Fixed problem with themes that do not call wp_footer().  If you are reading this and you are a theme developer, USE THE HOOKS!  That's what they're there for!
* Updated how the admin section is handled

= 0.1 =
* Original Version
