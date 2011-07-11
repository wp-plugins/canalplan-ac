<?php


/*
Extension Name: Canalplan General Settings
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 0.9
Description: General Settings for the Canalplan AC Plugin
Author: Steve Atty
*/

#require_once('admin.php');
$title = __('CanalPlan Options');
#include_once ("./admin-header.php");;
global $blog_id;
echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
echo '<script type="text/javascript" src="/wp-content/plugins/canalplan/canalplan/canalplanfunctions.js" DEFER></script>';
nocache_headers(); 

?>
<script language="JavaScript" type="text/javascript"><!--
        function set_value(param,listID)
        {
var x=document.getElementById("general_options");
x.options[param].text=listID;
        }


        function showValue(listID)
        {
    var list = document.getElementById(listID);
    var items = list.getElementsByTagName("option");
    var itemsString = "";
    var itemsString2 = "";
    for (var i = 0; i < items.length; i++) {
        if (itemsString.length > 0) itemsString += ":";
        itemsString += items[i].value;
 	itemsString += ',';
        itemsString += items[i].innerHTML;
    }
document.getElementById("dataset").value=itemsString;

        }
	//-->
	</script>
<?php
nocache_headers();

if(isset($_POST['_submit_check']))
	{
		parse_data($_POST['dataset'],$blog_id);
	}
?>

<div class="wrap">
<h2><?php _e('General CanalPlan Options') ?> </h2>
<br>
<h3><?php _e('CanalPlan Data') ?></h3>
<?php
if (isset($_POST["googleapi"])){
	$api=mysql_real_escape_string($_POST['apikey']);
	$r = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='apikey'");
	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".$api."' where blog_id=".$blog_id." and pref_code='apikey'";
	if (mysql_num_rows($r)==0) {
		$sql="insert into ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values ('".$api."',".$blog_id.",'apikey')";
	}
	mysql_query($sql);
}

if (isset($_POST["canalkey"]) && isset($_POST['SCK'])){
	$api=mysql_real_escape_string($_POST['canalkey']);
	$r = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='canalkey'");
	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".$api."' where blog_id=".$blog_id." and pref_code='canalkey'";
	if (mysql_num_rows($r)==0) {
		$sql="insert into ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values ('".$api."',".$blog_id.",'canalkey')";
	}
	mysql_query($sql);
}

if (isset($_POST["canalkey"]) && isset($_POST['RCK'])){
#$api=mysql_real_escape_string($_POST['canalkey']);
$r = mysql_query("Delete FROM ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='canalkey'");
#$sql="update ".CANALPLAN_OPTIONS." set pref_value='".$api."' where blog_id=".$blog_id." and pref_code='canalkey'";
if (mysql_num_rows($r)==0) {
#$sql="insert into ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values ('".$api."',".$blog_id.",'canalkey')";
}
#mysql_query($sql);
}

if (isset($_POST["routeslug"])){
	$routeslug=mysql_real_escape_string($_POST['routeslug']);
	$r = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='routeslug'");
	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".$routeslug."' where blog_id=".$blog_id." and pref_code='routeslug'";
	if (mysql_num_rows($r)==0) {
		$sql="insert into ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values ('".$routeslug."','.$blog_id.','routeslug')";
	}
#	var_dump($sql);
	mysql_query($sql);
}

if (isset($_POST["update_data"])){
	echo '<table border="1" cellpadding="10" ><tr><th>Table Name </th><th>Contained (Rows)</th><th>Now Contains (Rows)</th></tr>';
	#print "Updating Canalplan Aliases table<br>";
	$sql="select count(*) from ".CANALPLAN_ALIASES.";";
	$res = mysql_query($sql);
	$res2=mysql_fetch_array($res);
	#$dbhandle = new PDO("sqlite:../canalplan/canal.sqlite");
	#$dbhandle = new PDO("sqlite:http://www.canalplan.org.uk/canal.sqlite");
	$handle=fopen("http://www.canalplan.org.uk/stable_canal.sqlite","rb");
	$handle2=fopen("../wp-content/uploads/canalplan_data.sqlite","w");
	$contents = '';
	while (!feof($handle)) {
	  $contents = fread($handle, 8192);
	   fwrite($handle2,$contents);
	}
	fclose($handle);
	fclose($handle2);
	$dbhandle = new PDO("sqlite:../wp-content/uploads/canalplan_data.sqlite");
	$sqlGetView = 'SELECT placeid,name FROM place_aliases';
	$result = $dbhandle->query($sqlGetView);
	$sql= "truncate ".CANALPLAN_ALIASES.";";
	$res = mysql_query($sql);
	foreach ($result as $entry) {
	   $sql= "INSERT INTO ".CANALPLAN_ALIASES." (canalplan_id,place_name) VALUES ('".$entry['placeid']."','".mysql_real_escape_string($entry['name'])."');";
	   $res = mysql_query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_ALIASES.";";
	$res = mysql_query($sql);
	$res3=mysql_fetch_array($res);
	print "<tr><td>Canalplan Aliases</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	#print "<br>Updating Canalplan Places Table<br>";
	$sql="select count(*) from ".CANALPLAN_CODES.";";
	$res = mysql_query($sql);
	$res2=mysql_fetch_array($res);

	$sqlGetView = 'SELECT id,name,type,latitude,longitude,attributes FROM place';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	   $sql= "INSERT INTO ".CANALPLAN_CODES." (canalplan_id,place_name,size,lat,`long`,attributes,lat_lng_point) VALUES ('".$entry['id']."','".mysql_real_escape_string($entry['name'])."','".$entry['type']."','".$entry['latitude']."','".$entry['longitude']."','".$entry['attributes']."', GeomFromText('Point(".$entry['latitude']." ".$entry['longitude'].")')) ON DUPLICATE KEY UPDATE place_name='".mysql_real_escape_string($entry['name'])."', size='".$entry['type']."', lat='".$entry['latitude']."', `long`='".$entry['longitude']."', attributes='".$entry['attributes']."', lat_lng_point=GeomFromText('Point(".$entry['latitude']." ".$entry['longitude'].")'); ";
	   $res = mysql_query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_CODES.";";
	$res = mysql_query($sql);
	$res3=mysql_fetch_array($res);
	print "<tr><td>Canalplan Places</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";


	#print "<br>Updating Canalplan Link Table<br>";
	$sql="select count(*) from ".CANALPLAN_LINK.";";
	$res = mysql_query($sql);
	$res2=mysql_fetch_array($res);
	$sqlGetView = 'SELECT place1,place2,metres,locks,waterway FROM link';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	   $sql= "INSERT INTO ".CANALPLAN_LINK." (place1,place2,metres,locks,waterway) VALUES ('".$entry['place1']."','".$entry['place2']."','".$entry['metres']."','".$entry['locks']."','".$entry['waterway']."') ON DUPLICATE KEY UPDATE metres='".$entry['metres']."',locks='".$entry['locks']."', waterway='".$entry['waterway']."'; ";
	   $res = mysql_query($sql);

	}
	$sql="select count(*) from ".CANALPLAN_LINK.";";
	$res = mysql_query($sql);
	$res3=mysql_fetch_array($res);
	print "<tr><td>Canalplan Links</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	#print "<br>Updating Canalplan Waterways Table<br>";
	$sql="select count(*) from ".CANALPLAN_CANALS.";";
	$res = mysql_query($sql);
	$res2=mysql_fetch_array($res);
	$sqlGetView = 'SELECT id,parent,name,fullname FROM waterway';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	   $sql= "INSERT INTO ".CANALPLAN_CANALS." (id,parent,name,fullname) VALUES ('".$entry['id']."','".$entry['parent']."','".mysql_real_escape_string($entry['name'])."','".mysql_real_escape_string($entry['fullname'])."') ON DUPLICATE KEY UPDATE name='".mysql_real_escape_string($entry['name'])."',fullname='".mysql_real_escape_string($entry['fullname'])."', parent='".$entry['parent']."'; ";
	   $res = mysql_query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_CANALS.";";
	$res = mysql_query($sql);
	$res3=mysql_fetch_array($res);
	print "<tr><td>Canalplan Waterways</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	#print "<br>Updating Canalplan PolyLines<br>";
	$sql="select count(*) from ".CANALPLAN_POLYLINES.";";
	$res = mysql_query($sql);
	$res2=mysql_fetch_array($res);
	$sqlGetView = 'SELECT id,pline,weights FROM polyline';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	   $sql= "INSERT INTO ".CANALPLAN_POLYLINES." (id,pline,weights) VALUES ('".$entry['id']."','".mysql_real_escape_string(addslashes($entry['pline']))."','".mysql_real_escape_string($entry['weights'])."') ON DUPLICATE KEY UPDATE pline='".mysql_real_escape_string(addslashes($entry['pline']))."',weights='".mysql_real_escape_string($entry['weights'])."'; ";
	   $res = mysql_query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_POLYLINES.";";
	$res = mysql_query($sql);
	$res3=mysql_fetch_array($res);
	print "<tr><td>Canalplan Polylines</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".time()."' where blog_id=-1 and pref_code='update_date'";
	$res = mysql_query($sql);

	#mysql_free_result($res);

	print "</table><br>All Done<br/><br/>";
	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".time()."' where blog_id=-1 and pref_code='update_date'";
	$r = mysql_query("SELECT pref_value FROM  ".CANALPLAN_OPTIONS." where blog_id=-1 and pref_code='update_date'");
	if (mysql_num_rows($r)==0) {
	$sql="insert into  ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values ('".time()."',-1,'update_date')";
	}
	mysql_query($sql);
	sleep(2);
}

$r2 = mysql_query("SELECT (".time()." - pref_value) as age FROM  ".CANALPLAN_OPTIONS." where blog_id=-1 and pref_code='update_date'");
$do_update="no button";
if (mysql_num_rows($r2)==0) {
 	    $updated="never";
}
else
{
	$rw = mysql_fetch_array($r2,MYSQL_ASSOC);
	  $updated=$rw['age']/(3600*24);
}

if ($updated> 14 && $do_update==0) {
	echo "CanalPlan data was last updated over two weeks ago so its probably very out of date. Click on the button to refresh it";
	$do_update="Get New Data";
}

if ($updated=="never") {
	echo "You've not got any CanalPlan data, click on the button below to connect to the CanalPlan Server and get the data";
	$do_update="Get Data";
}

if ( $do_update=="no button" && $updated>0.5) {
	echo "CanalPlan data was last updated ".round($updated,2)." days ago. Click on the button to refresh it";
	$do_update="Refresh Data";
}
if ($do_update!="no button"){
?>
<form action="" name="data_update" id="data_update" method="post">
<p class="submit"> <input type="submit"  value="<?php echo $do_update;?>" /></p>
<input type="hidden" name="update_data" value="1"/>
</form>
<?php } else { echo "<p>CanalPlan data was last updated ".round($updated*24,2) ." hours ago - you cannot refresh it yet</p>" ; }?>
<p><b>Note: </b> The data used by this plugin is pulled from a copy of the Canalplan Database rather than the live database. This means that the data being used by the plugin can be up to 24 hours old when it is pulled over. So if you've added a new place and want to blog about it (or include it in a imported route) then you need to wait until the next day before doing the import.</p>
<hr>
<h3><?php _e('Distance Format') ?></h3>
<p>This is the default format that will be used when importing routes from Canalplan AC. It can be overridden on a route by route basis:</p>

<?php
$r = mysql_query("SELECT pref_value FROM  ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='distance_format'");
if (mysql_num_rows($r)==0) {
     $df="f";
}
else
{
$rw = mysql_fetch_array($r,MYSQL_ASSOC);
  $df=$rw['pref_value'];
}
?>
<form action="" name="distform" id="dist_form" method="post">
<select id="DFSelect" name="dfsel" onchange="set_value(0,DFSelect.value);" >

<?php
$arr = array(k=> "Decimal Kilometres (3.8 kilometres)", M => "Kilometres and Metres (3 kilometres and 798 metres) ", m=>"Decimal miles (2.3 miles)", y=>"Miles and Yards (2 miles and 634 yards) ",f=>"Miles and Furlongs (  2 miles , 2 &#190; flg )");
foreach ($arr as $i => $value) {
if ($i==$df){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}

}
?>
</select>
<select id="general_options" style="display:none">
<option value="distance_format"></option>
<option value="cplogin"></option>
</select>
<input type="hidden" name="_submit_check" value="1"/>
<input type="hidden" name="dataset" id="dataset" value="" />
<p class="submit"> <input type="submit" onclick="showValue('general_options')"  value="Save Options" /></p>

</form>

</div>
<?php  if (!defined('CANALPLAN_GMAP_KEY')) { ?>
<hr>
<h3><?php _e('Google Maps API Key') ?></h3>
<form action="" name="googleapi" id="googleapi" method="post">

<?php
$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='apikey'");
if (mysql_num_rows($r2)==0) {
     $api="";
}
else
{
	$rw = mysql_fetch_array($r2,MYSQL_ASSOC);
	$api=$rw['pref_value'];
} 
echo '<input type="text" name="apikey" maxlength="100" size="100" value="'.$api.'">';
?>
<input type="hidden" name="googleapi" value="1"/>
<p class="submit"> <input type="submit"  value="Save API Key" /></p>
</form>

<p>You can obtain a Google Map API Key by <a href='http://code.google.com/apis/maps/signup.html'> Signing up for one at Google </a></p>

<?php
}
?>
<hr>

<h3><?php _e('Canalplan Key') ?></h3>
This key allows Canalplan to link back to your blog posts.
<form action="" name="canalapi" id="canalapi" method="post">

<?php
$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='canalkey'");
if (mysql_num_rows($r2)==0) {
     $api="";
}
else
{
$rw = mysql_fetch_array($r2,MYSQL_ASSOC);
$api=$rw['pref_value'];
} 

$url=get_bloginfo('url');
$sname=get_bloginfo('name');
if (strlen($api)<4) {
	$x=CANALPLAN_URL.'api.cgi?mode=register_blogger&domain='.$url.'&title='.urlencode($sname); 
	#var_dump($x);
	$fcheck=file_get_contents($x);
	$cp_register=json_decode($fcheck,true);
	$api=$cp_register['key'];
	$uid=$cp_register['id'];
	echo "<br/>API Key has been set to : <i> ".$api." </i> and is valid for the blog titled:<b> '".$sname."' </b> on the following url : <b> ".$url.'</b><br/>';
	echo '<p class="submit"> <input type="submit" name="SCK"  value="Save Canalplan Key" /></p>';
}

else {
$api=explode("|",$api);
echo "<br/>API Key currently set to : <i> ".$api[0]." </i> and is valid for the blog titled:<b> '".$sname."' </b> on the following url : <b> ".$url.'</b><br/>';
echo '<p class="submit"><input type="submit" name="RCK" value="Reset Canalplan Key" /></p>';
}

echo '<input type="hidden" name="canalkey" value="'.$api.'|'.$uid.'">';
?>
<input type="hidden" name="camalapi" value="1"/>
</form>
<hr>
<h3><?php _e('Route Page Slug') ?></h3>
 The Route Page Slug is the name of the page you are using for your Route Handling page. The page needs to contain the following code to work : {BLOGGEDROUTES}. <br/> <br/>
<?php
if (!defined('CANALPLAN_ROUTE_SLUG')) { ?>
<form action="" name="routeslug" id="routeslug" method="post">

<?php
$r2 = mysql_query("SELECT pref_value FROM  ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='routeslug'");
if (mysql_num_rows($r2)==0) {
     $routeslug="UNDEFINED!";
}
else
{
	$rw = mysql_fetch_array($r2,MYSQL_ASSOC);
	$routeslug=$rw['pref_value'];
} 
echo '<input type="text" name="routeslug" maxlength="20" size="20" value="'.$routeslug.'">';
?>
<input type="hidden" name="routes_slug" value="1"/>
<p class="submit"> <input type="submit"  value="Save Route Page Slug" /></p>

</form>
Your current page slug for blogged routes is 
<?php
if ($routeslug=="UNDEFINED!") { echo " <b> currently not defined </b> so please set one";} else {

echo "'". $routeslug."' so you need to make sure that <a href='".get_option("siteurl")."/".$routeslug."'>".get_option("siteurl")."/".$routeslug."</a> exists";
}}
else { 
?>
The Site Administrator has set the page slug for blogged routes to be  '
<?php
echo CANALPLAN_ROUTE_SLUG."' so you need to make sure that <a href='".get_option("siteurl")."/".CANALPLAN_ROUTE_SLUG."'>".get_option("siteurl")."/".CANALPLAN_ROUTE_SLUG."</a> exists ";
}


function parse_data($data,$blid)
{$i=1;
  $containers = explode(":", $data);
  foreach($containers AS $container)
  {
      $values = explode(",", $container);
      if ( strlen($values[1])> 0) {
       $sql="Delete from ".CANALPLAN_OPTIONS." where blog_id=".$blid." and pref_code='".$values[0]."'";
	 $res = mysql_query($sql);
     $sql="insert into ".CANALPLAN_OPTIONS." set blog_id=".$blid." ,pref_code='".$values[0]."', pref_value='".$values[1]."';";
     $res = mysql_query($sql);
        }
  }
}
?>
