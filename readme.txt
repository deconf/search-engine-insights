=== Search Engine Insights for Google Search Console ===
Contributors: deconf
Donate link: https://deconf.com/donate/
Tags: search console dashboard, google search console, search console widget, search console metatag, search console, seo
Requires at least: 3.5
Tested up to: 6.0
Stable tag: 2.1.3
Requires PHP: 5.6
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

= Further reading =

* [Search Engine Insights](https://wordpress.org/plugins/search-engine-insights/) - The perfect tool for viewing Google Search Console stats in your WordPress dashboard.
* [Analytics Insights](https://wordpress.org/plugins/analytics-insights/) - Connects Google Analytics with your WordPress site.

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

== Upgrade Notice ==

== Changelog ==

= 2.1.3 (2022.06.18) =
* Bug Fixes:
	* fixes on Google Console Client library

= 2.1.2 (2022.06.17) =
* Bug Fixes:
	* fixes PHP 5.6 compatibility issues

= 2.1.1 (2022.06.01) =
* Bug Fixes:
	* prefix namespaces to avoid autoloading collisions

= 2.0 (2022.05.30) =
* Update Notice:
	* Depending on your setup, it might require re-authorization after the upgrade!
* Enhancements:
	* replace text with dashicons on Posts List to save column space
	* simplify the Google Analytics API token revoke method
	* API Client library update 
* Bug Fixes:
	* small CSS fixes
	
= 1.6.5 (2022.04.23) =
* Enhancements:
	* token handling improvements between DeConf EndPoint and Google API Client, to avoid random token resets
	
= 1.6.4 (2022.04.21)
* Enhancements:
	* prevent autofocus of backend widget for a beter UX
	
= 1.6.3 (2022.04.17)
* Security:
	* moment.js library update
	
= 1.6.2 (2022.03.31)
* Security:
	* Google Search Console library update
	
= 1.6.1 (2021.12.28)
* Bug Fixes:
	* decrease token expiration time to avoid Google Search Console API random errors 
	
= 1.6 (2021.12.27)
* Bug Fixes:
	* fixing multiple notices and errors for PHP 8
	* Google Search Console API improvements
	* avoid setting unverified sites as defaults
	
= 1.5 (2021.10.27)
* Important Note:
	* Upgrading to 1.5 requires plugin re-authorization 
	
* Enhancements:
 	* Google Search Console library update to v2
	* Search Console API Endpoint update to v1
	* minimum requirements changed to PHP 5.6.0 or higher
	* automatically authorize users with Google Search Console, without copy/pasting the access codes
* Bug Fixes:
	* multiple bugfixes for network mode setup
	* admin page css fixes
		
= 1.4.2 (2021.10.02)
* Bug Fixes:
	* fix invalid links
	* use sprintf for plugin i18n
	
= 1.4.1 (2021.09.14) =
* Bug Fixes:
	* fixes multisite/network mode random token resets

= 1.4 (2021.08.24) =
* Bug Fixes:
	* fixes some deprecated messages on PHP 7.4 and later
		
= 1.3 (2020.11.30) =
* Enhancements:
	* the automatic update feature was removed
* Bug Fixes:
	* fixes per site search traffic displayed numbers

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
