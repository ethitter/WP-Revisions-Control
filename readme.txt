=== WP Revisions Control ===
Contributors: ethitter
Donate link: https://ethitter.com/donate/
Tags: revision, revisions, admin
Requires at least: 3.6
Tested up to: 5.4
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Control how many revisions are stored for each post type.

== Description ==

WordPress 3.6 allows users to control how many revisions are stored for each supported post type. No longer must you rely on the `WP_POST_REVISIONS` constant, which applied universally. This plugin provides an interface for this new functionality.

With this plugin enabled, simply visit **Settings > Writing** to specify the number of revisions retained for each post type.

Why is this helpful? Revisions are stored in the database, and if many are stored, can cause bloat. This bloat may lead to slower queries, which can have a noticeable performance impact. The value of these revisions also depends on what is being tracked. For example, I may want to store every revision of the posts I write, but only desire to keep the latest five versions of each page on my site. Starting in WordPress 3.6, this control is available. WordPress doesn’t provide a native interface to specify revisions quantities, so I wrote this quick plugin to do so.

Thanks to Maria Ramos at [WebHostingHub](http://www.webhostinghub.com/), the plugin is also available in Spanish. Many thanks to her for her efforts!

**Development is at https://git.ethitter.com/wp-plugins/wp-revisions-control.**

== Installation ==

1. Upload wp-revisions-control to /wp-content/plugins/.
2. Activate plugin through the WordPress Plugins menu.
3. Go to **Settings > Writing** and set the options under **WP Revisions Control**.

== Frequently Asked Questions ==

= Where do I change the plugin's settings? =
Navigate to **Settings > Writing** in your WordPress Dashboard, and look for the **WP Revisions Control** section.

== Changelog ==

= 1.3 =
* Add bulk actions to purge excess or all revisions.
* Introduce unit tests.
* Conform to coding standards.

= 1.2.1 =
* Introduce Spanish translation thanks to Maria Ramos at [WebHostingHub](http://www.webhostinghub.com/).

= 1.2 =
* Add post-level revision purging and limiting. For any post type that supports revisions, you can now limit the number of revisions retained at a post level.

= 1.0 =
* Initial public release

== Upgrade Notice ==

= 1.3 =
Introduces bulk actions for purging revisions, along with unit tests. The plugin also conforms to coding standards.

= 1.2.1 =
Introduces Spanish translation thanks to Maria Ramos at [WebHostingHub](http://www.webhostinghub.com/).

= 1.2 =
For any post type that supports revisions, you can now limit the number of revisions retained at a post level.

== Screenshots ==

1. The plugin's settings section, found under **Settings > Writing**.
2. The post-level controls provided in version 1.2.
