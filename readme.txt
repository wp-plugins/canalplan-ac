=== Canalplan ===

Contributors: SteveAtty
Tags: crosspost, Canalplan AC
Requires at least: 3.0
Tested up to: 4.0.1
Stable tag: 3.17

== Description ==

This plugin allows you link your self hosted Wordpress blog to the Canalplan AC website. You can import routes from the route planner and link your blog posts to the canalplan  gazetteer.


== IMPORTANT ==

This plugin creates 9 tables in your database which occupy about 6MB of space.

The plugin also uses the fopen and file_get_contents calls. Please ensure that these functions are available and that they can access external sites.

You also need to ensure that your PHP installation has the PDOs for SQLite V3 installed as the plugin reads from an SQLite DB to populate its tables.


== Installation ==

1. [Download] (http://wordpress.org/extend/plugins/canalplan-ac/) the latest version of the Canaplan AC Plugin.
1. Unzip the ZIP file.
1. Upload the `canalplan` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to `Options` &rarr; `Canalplan AC` for configuration and follow the on-screen prompts.


== Features ==

- Imports data from the Canalplan AC website so you have an up to date list of all canalplan locations (currently standing at 17471 places)
- Easy linking to Canalplan AC Gazetteer entries
- Easy inclusion of Googlemaps related to Canalplan AC locations
- Easy inclusion of maps of complete waterways or sections of waterways based on Canalplan AC Data
- Import Planned routes from Canalplan AC and create a "Cruising Log" of blog entries for the trip.
- Supports a "Trips" page which summarises all the sets of "Cruising Log" entries. This page also displays a map and a list of individual posts for a specific "Cruising Log"
- Bulk export of links to Canalplan AC Gazetteer entries for when you publish cruising log
- Canalplan AC website automatically links back to relevant blog entries from Gazetteer pages.
- Works in "Classic" (Single blog) and Networked Blogs mode. Can be Network activated
- Common set of Tables for Networked Blogs mode - so 1 set of tables per Networked Blogs install, not per blog.
- Global configuration for Networked blog installs can be done through a special multisite.php file.


== Screenshots ==

1. Canalplan Plugin : Top Level Trips screen
2. Canalplan Plugin : Trip Screen for a single trip


== Changelog ==

= Version 3.17 22/11/2014 =
- Confirmed 4.0.1 compatible
- Fixed a bug where Posts in the WPMU site-search results picked up the containing page url rather than the actual post url
- Changes to short codes to include summary post in list of available posts


= Version 3.16 17/08/2014 =
- Changes to route import to include the summary post (if created) in the posts for that trip
- Changes to short codes to include summary post in list of available posts
- Unpublished posts were shown in the short codes if a route was published. Now only published Posts are shown as links
- Marker Places hidden from route management - unless they were set as an overnight when Importing the route.
- Fixed long standing bug relating to lock counts where flights are involved
- Fixed long standing bugs relating to stopping before or after lock flights
- Changes to route recalculation code to handle missing links (caused by importing a route using data points not in the local copy) which caused skewed values


= Version 3.15 21/07/2014 =
- Changes to Location Widget to stop missing values from blowing up all the googlemaps on the page
- Fixes to the location setting page to handle odd time differences appearing in Multisite installs
- Fixes to the location settings page to try to stop odd results when selecting one update option but using another.


= Version 3.14 20/07/2014 =
- Minor bug fixes to resolve some issues with the WPMUDEV global search plugin
- Minor bug fixes to the location screen to get rid of rogue slash characters appearing when manually setting location
- Remove diagnostic log writes from location code.


= Version 3.13 19/07/2014 =
- Location Updating now links through to Canalplan. So if you've liked and marked a boat for tracking on there then you can update your blog and canalplan at the same time
- Added New Short Code to link to Trip blog posts
- Other minor tweaks made.


= Version 3.12 17/05/2014 =
- Introduces Location updating using Backitude (an Android App) - allows you to automatically update your location using your mobile phone.
- Caches location so that the "where am I widget" doesn't have to keep doing the intensive distance look ups.


= Version 3.11 25/01/2014 =
- Confirms Wordpress 3.8.1 Compatibility


= Version 3.10 15/12/2013 =
- Confirms Wordpress 3.8 Compatibility


= Version 3.9 27/10/2013 =
- Tidied up the links to use the Canalplan shorter urls (/waterway /gazetteer )
- Added code to support Features


= Version 3.8 05/10/2013 =
- Added better support for RSS feeds - CP Links now work in RSS feeds
- Added better support for WPMU's Global Posts / Network RSS Feed
- Added New Short Codes to support Trip Summaries and Trip Maps


= Version 3.7 17/08/2013 =
- Added Location Options screen to the page menu
- Added the ability to re-import a route from canalplan.


= Version 3.6 17/08/2013 =
- New Location Options screen
- Location Widget recoded to use Location Options Screen
- API Key was not being saved properly.
- Minor tweaks to the Day Stats code
- Minor changes to the format of draft posts created during route importing.


= Version 3.5 04/05/2013 =
- Restored favourites per blog which had got lost somewhere (only affects multiblog installs)
- Added new linkify function to just return raw text.


= Version 3.4 28/04/2013 =
- Fixed a couple of bugs in the Manage Route page.


= Version 3.3 28/04/2013 =
- Found a bug in the midpoint logic for maps.


= Version 3.2 10/02/2013 =
- Tidied up a lot of the array index references
- Found some more mysql_ calls
- Fixed obsolescent menu constructs
- Confirmed Wordpress 3.5.1 compatible


= Version 3.1 22/01/2013 =
- Missing ARRAY_A broke a setting


= Version 3.0 20/01/2013 =
- Recoded all DB calls to use $wpdb calls
- Recoded all functions using mysql_ functions
- Recoded Google Map functions to make them work with JetPack
- Removed a lot of old commented out code.


= Version 2.8 13/10/2012 =
- Changed a couple of fixed URLS to use constants to make url changes easier.
- Added a couple of conditional checks to make things tidy.
- Moved Data Pull from Canalplan to use wp_get - so fopen is not needed any more which makes things better.


= Version 2.7 21/07/2012 =
- Server work to reduce DB download size.
- Recode data loader to use smaller fetch requests
- Fixed a rogue 500 error in the place matching routine.
- Changed all javascript urls to be relative rather than absolute to fix issue with running blog in a subdirectory
- Changed Where Am I widget to need full Google ID (i.e including the -) rather than just assuming it.
- Added before and after widget calls to the Widget so that it picks up theme formatting for widgets.
- Changed more hard coded Canalplan URLs to use the constants defined in the main file.


= Version 2.6 14/07/2012 =
- Found a few incorrect urls which would have caused some unwanted 404s


= Version 2.5 03/07/2012 =
- Rebuilding the blog revealed a glitch when a place vanished from the database which messed up the maps. Now fixed.
- Database moved on live server - so local data wasn't updating.


= Version 2.4 17/06/2012 =
- Fixed a problem with blogged routes. Was coded to expect a .htaccess re-write rule. No-one seems to have noticed though!
- Checked 3.4 compatability


= Version 2.3 12/06/2012 =
- Fixed the paths for lots of script and http references. Serves me right for developing in a folder with the wrong name!


= Version 2.2 03/05/2012 =
- Fixed the paths in the admin page so that the menus worked properly. Serves me right for developing in a folder with the wrong name!


= Version 2.1 29/04/2012 =
- Fixed an incorrect header in the widget plugin which confused the Wordpress SVN which reported the plugin as Version 1.0 rather than 2.0
- Latitude Widget no longer shows ! places as nearest locations.


= Version 2.0 28/04/2012 =
- Checked for Wordpress 3.3.2 compatability
- Removed Google Map API key code
- Recoded Google Maps to use Version 3 of the Maps API
- Added a Latitude Widget which links to Canalplan locations
- Added a Maps options page
- Recoded tag handling code to incorporate map customisation options.
- Re-wrote the user guide.
- Removed lots of commented out code left over from original development.


= Version 1.0 22/12/2011 =
- Checked for WP 3.3 compatability.
- Minor tweaks in some of the refresh logic


= Version 0.9.1 12/07/2011 =
- Added PDF user guide and added links to it
- Moved Calendar javascript into its own file
- Removed a lot of commented out code that wasn't needed any more
- Added Code revision tag for version checking of live installations
- Changed Bulk load limit back to 20 from 2 (left in by accident during testing)


= Version 0.9  04/07/2011 =
- Initial Beta release

