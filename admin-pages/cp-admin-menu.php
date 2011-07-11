<?php

/*
Extension Name: Canalplan Menu
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 0.9
Description: Menu Page for the Canalplan AC Plugin
Author: Steve Atty
*/


#require_once('admin.php');
$title = __('CanalPlan AC Integration');

nocache_headers(); 
?>
<div class="wrap">

<h2><?php _e('CanalPlan AC Integration Overview') ?> </h2>
This plugin allows you to integrate your blog with <a href="http://www.canalplan.eu">Canalplan AC</a>

<p><a href="admin.php?page=canalplan/admin-pages/cp-admin-general.php"> General Options </a> <br />
This sets up various general options for the Canalplan plugin such as the Canalplan API Key and GoogleMaps API Key</p>
<p><a href="admin.php?page=canalplan/admin-pages/cp-admin-home.php"> Home Mooring </a><br />
This allows you to set your home mooring location and give it a customised name </p>
<p><a href="admin.php?page=canalplan/admin-pages/cp-admin-fav.php"> Favourite Locations </a><br />
This allows you to set up some favourite locations and give them customised names.</p>
<p><a href="admin.php?page=canalplan/admin-pages/cp-import_route.php"> Import a route from CanalPlan AC </a><br />
This is the starting point for importing a route from Canalplan AC and creating a set of blog posts for the imported route</p>
<p><a href="admin.php?page=canalplan/admin-pages/cp-manage_route.php"> Manage Imported Routes </a><br />
Once you've imported a route you might need to make adjustments to the daily totals - this page allows you to do that</p>
<p><a href="admin.php?page=canalplan/admin-pages/cp-admin-diagnostics.php"> Diagnostics / Version Information </a><br />
Provides information and diagnostics - You'll need to refer to this page if you've got problems.
</p>
<p><a href="admin.php?page=canalplan/admin-pages/cp-admin-update.php"> Bulk Link Notifier </a><br />
During normal use the Canalplan AC website will learn about links from you blog back to it's gazetteer. However if you've just added a set of posts you might want to let Canalplan AC know about all the links.
</p>


</div>
<?php
#include('admin-footer.php');
?>
