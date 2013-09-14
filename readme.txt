=== Canalplan ===

Contributors: SteveAtty
Tags: crosspost, Canalplan AC
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 3.8

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

= Version 3.8 14/09/2013 =
- Wordpress 3.6.1 compatibility check
- Added new Route Summary Tag codes
- Recoded some of the admin pages to make string parsing more robust.

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

