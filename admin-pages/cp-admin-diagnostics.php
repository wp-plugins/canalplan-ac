<?php
/*
Extension Name: Canalplan Diagnstics
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 3.0
Description: Diagnostics for the Canalplan AC Plugin
Author: Steve Atty
*/

require_once('admin.php');
$parent_file = 'canalplan-manager.php';

echo "<h2>";
 _e('Diagnostics & Support') ;
echo "</h2>";
global $blog_id,$wpdb;
$active_plugins = get_option('active_plugins');
$plug_info=get_plugins();
$phpvers = phpversion();
$jsonvers=phpversion('json');
if (!phpversion('json')) { $jsonvers="Installed but version not being returned";}
$sxmlvers=phpversion('simplexml');
if (!phpversion('simplexml')) { $sxmlvers=" No version being returned";}

$fopenstat="file_get_contents is not available ";
if(function_exists("file_get_contents")){
	$fopenstat="file_get_contents is available ";
$x=CANALPLAN_URL.'api.cgi?mode=version ';
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
$fcheck=file_get_contents($x);
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
$cp_version=json_decode($fcheck,true);
$fopenstat2=' but cannot access Canalplan - This is a problem ';
if (strlen($cp_version['version'])>3) {$fopenstat2='and can acccess the Canalplan Website - All is OK ( <i> Response Time was : '.$totaltime.' seconds </i> )';}
}

$t=$wpdb->get_results("select version() as ve",ARRAY_A);
$mysqlvers =  $t[0]['ve'];
$info = array(
		'CanalPlan' => $plug_info['canalplan-ac/canalplan.php']['Version']." (".CANALPLAN_CODE_RELEASE.")",
		'File_open Status' => $fopenstat.$fopenstat2,
		'CanalPlan AC (Website)'=> $cp_version['version']." ( ".$cp_version['date'].' )',
		'WordPress' => $wp_version,
		 'PHP' => $phpvers,
		 'PHP Memory Limit' => ini_get('memory_limit'),
		 'PHP Memory Usage (MB)' => memory_get_usage(true)/1024/1024,
		'MySQL' => $mysqlvers
		);

	echo"<h3>";
	_e("Diagnostic Information");
	echo "</h3>";
	_e('Please provide the following information about your installation:<p>');
	echo "<ul>";

	foreach ($info as $key => $value) {
	$suffix = '';
	echo "<li>$key: <b>$value</b>$suffix</li>";
	}
	echo "<li> Server : <b>".$_SERVER['SERVER_SOFTWARE']."</b></li>";
	_e("<li> Active Plugins : <b></li>");
	foreach($active_plugins as $name) {
	if ( $plug_info[$name]['Title']!='Canalplan') {
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$plug_info[$name]['Title']." ( ".$plug_info[$name]['Version']." ) <br />";}
	}
	echo "</b></p>";
		echo "</b><br /><li> Canalplan Table Status :</li><b>";
	$table_array= array (CANALPLAN_OPTIONS,CANALPLAN_ALIASES,CANALPLAN_CODES,CANALPLAN_FAVOURITES,CANALPLAN_LINK,CANALPLAN_CANALS,CANALPLAN_ROUTES,CANALPLAN_POLYLINES,CANALPLAN_ROUTE_DAY);
	foreach ($table_array as $table) {
		$sql="select count(*) from ".$table;
		$result=$wpdb->get_results($sql,ARRAY_N);
		if (!$result)
	{
	$tstat_string= sprintf("ERROR : table </b>'%s'<b> is missing ! - Please Deactivate and Re-activate the plugin from the Plugin Options Page", $table);
	}
	else {
	$tstat_string= sprintf("&nbsp;&nbsp;&nbsp;Table </b>'%s'<b> is present and contains %s rows", $table,$result[0][0]);
	 }
	echo "&nbsp;&nbsp;&nbsp;".$tstat_string."<br />";
	}
	echo "</b></p><br /><br />";
	_e('For feature requests, bug reports, and general support :'); ?>
	<p><ul>
	<li><?php _e('Check the '); ?><a href="<?php echo plugins_url(); ?>/canalplan-ac/canalplan_ac_user_guide.pdf" target="wordpress"><?php _e('User Guide'); ?></a>.</li>
	<li><?php _e('Check the '); ?><a href="http://wordpress.org/extend/plugins/canalplan-ac/other_notes/" target="wordpress"><?php _e('WordPress.org Notes'); ?></a>.</li>
	<li><?php _e('Consider upgrading to the '); ?><a href="http://wordpress.org/download/"><?php _e('latest stable release'); ?></a> <?php _e(' of WordPress. '); ?></li>
	</ul></p>
	<br />
	 </b><br /><hr><h3>Donate</h3>
	<?php
	_e("If you've found this extension useful then please feel free to donate to its support and future development.<br ");
	  ?>
	 </h3><br />
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
		<input type="image" src="https://www.paypal.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
		<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
		</form><br /><br /><hr>
</b><p>Canalplan AC is released under the GNU General Public Licence V2 and comes with absolutely no warranty. Canalplan AC can be redistributed under certain circumstances. Please read the <a href='../wp-content/plugins/canalplan-ac/gpl.html' target='_new'> included copy of the GPL V2</a> for more information.</p>