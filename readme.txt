=== Search Engine Insights for Google Search Console ===
Contributors: deconf
Donate link: https://deconf.com/donate/
Tags: search console dashboard, google search console, search console widget, search console metatag, search console, seo
Requires at least: 3.5
Tested up to: 5.2
Stable tag: 1.2.1
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Verify site ownership on Google Search Console! Analyze the Google Search Console stats, to see your site's performance on Google Search.

== Description ==

### Google Search Console site verification

Search Engine Insights adds your website to Google Search Console and helps you verify site ownership:

- Detects your default WordPress Site URL and gives you the option to add it to Search Console. 
- Automatically insert Google Search Console metatag to verify site ownership.

### View Google Search Console stats on your WordPress dashboard

Search Engine Insights will help you understand your site's search engine traffic and performance, by displaying key stats in a widget, on your WordPress dashboard.

In addition to a set of general Search Console reports, per Page and Post statistics will allow further segmentation of your search engine data, providing search engine insights for your web pages.

#### Google Search Console stats on your admin dashboard

[youtube https://www.youtube.com/watch?v=8SAOtwRNsGk]

- Four reports with overview stats about Impressions, Clicks, Position and Click Through Rate 
- Location statistics with insights about your search engine performance by country   
- Page stats which will show you how your web pages are performing
- Keywords report useful for your keywords research and strategy on search engines

#### In-depth Google Search Console stats for your web pages

Individual reports for each web page on your site with per page details as:

- Impressions and Clicks stats
- Position and Click Through Rate reports
- Location, Pages, and Keywords statistics

In addition, you can control who can view specific search console reports by setting permissions based on user roles.

Fully compatible with WordPress Network installs (Multisite Mode).

== Installation ==

1. Upload the full search-engine-insights directory into your wp-content/plugins directory.
2. In WordPress select Plugins from your sidebar menu and activate the Search Engine Insights plugin.
3. Open the plugin configuration page, which is located under Search Engine Insights menu.
4. Authorize the plugin to connect to Google Search Console using the Authorize Plugin button.
5. Go back to the plugin configuration page, which is located under Search Engine Insights menu to update/set your settings.
6. Open the admin dashboard to view the Search Console reports on the newly added widget

== Frequently Asked Questions == 

= How can I suggest a new feature, contribute or report a bug? =

You can submit pull requests, feature requests and bug reports on [our GitHub repository](https://github.com/deconf/Search-Engine-Insights).

= Why "Processing data, please check again in a few days" is displayed in the admin dashboard widget? =

When new Properties are added to Google Search Console, it may take up to a few days until the search engine stats will be available in Search Console and your Search Engine Insights plugin.

== Screenshots ==

1. Impressions, clicks, position, and click through rate stats on your dashboard
2. Reports about the location of your users, retrieved from Google Search Console
3. Search Console stats about your Pages on WordPress dashboard
4. Keywords statistics and performance reports retrieved from Google Search Console
5. Site verification using the Search Console metatag

== License ==

Search Engine Insights it's released under the GPLv2, you can use it free of charge on your personal or commercial website.

== Upgrade Notice ==

== Changelog ==
= 1.2.1 (2019.06.05) =
* Enhancements: 
	* update Google Search Console API requests, according to latest API changes 
	
= 1.2 (2019.04.12) =
* Bug Fixes:
	* small CSS fixes on item reports
	* load Google Chart controls package when using a Maps API key 
	
= 1.1.1 (2019.03.17) =
* Bug Fixes:
	* remove screen flickering on report change
	* multiple CSS fixes
* Enhancements: 
	* search capability for table charts	

= 1.1 (2019.03.16) =
* Bug Fixes:
	* do not allow properties list refresh after locking to a property
	* do not allow site verification at site level when using a single account for the entire WordPress Network
	* remember the date range selection accros sessions

= 1.0 (2019.03.01) =
* Enhancements: 
	* better identify default search console property
	* introduces site verification feature for Google Search Console

= 0.4.2 (2019.02.16) =
* Bug Fixes:
	* small CSS fix on switch buttons
	* backend reports displaying wrong data (site-wide stats instead of individual stats) 
	
= 0.4.1 (2019.02.10) =
* Bug Fixes:
	* fixes icon on mobile devices
* Enhancements: 
	* EndPoint URL update
	
= 0.4 (2019.01.27) =
* Enhancements: 
	* multiple UI improvements
	* updated assets

= 0.3 (2019.01.08) =
* Enhancements: 
	* new design for backend and frontend reports
	* removed debugging lines, cleanup
	* updated screenshots

= 0.2 (2019.01.06) =
* Enhancements: 
	* detailed description of plugin stats and functionality
	* updated plugin banner
	* added screenshots

= 0.1.3 (2018.12.17) =
* Enhancements: 
	* first release
