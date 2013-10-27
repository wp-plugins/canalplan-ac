<?php
/*
Extension Name: Canalplan General Settings
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 3.0
Description: General Settings for the Canalplan AC Plugin
Author: Steve Atty
*/

$title = __('CanalPlan Options');
global $blog_id;
echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
echo '<script type="text/javascript" src="'.site_url().'/wp-content/plugins/canalplan-ac/canalplan/canalplanfunctions.js" DEFER></script>';
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
 	itemsString += '|';
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
if (isset($_POST["canalkey"]) && isset($_POST['SCK'])){
	$api=preg_replace("/[^a-zA-Z0-9|\s\p{P}]/", "", $_POST['canalkey']);
	$sql2 =$wpdb->prepare("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='canalkey'",$blog_id);
	$sql=$wpdb->prepare("update ".CANALPLAN_OPTIONS." set pref_value=%s where blog_id=%d and pref_code='canalkey'",$api,$blog_id);
	$r = $wpdb->get_results($sql2);
	if ($wpdb->num_rows==0) {
		$sql=$wpdb->prepare("insert into ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values (%s,%d,'canalkey')",$api,$blog_id);
	}
	$wpdb->query($sql);
}

if (isset($_POST["canalkey"]) && isset($_POST['RCK'])){
	$sql = $wpdb->prepare("Delete FROM ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='canalkey'",$blog_id);
	$r = $wpdb->query($sql);
}

if (isset($_POST["routeslug"])){
	$routeslug=preg_replace("/[^a-zA-Z0-9\s\p{P}]/", "", $_POST['routeslug']);
	$sql=$wpdb->prepare("update ".CANALPLAN_OPTIONS." set pref_value='".$routeslug."' where where blog_id=%d and pref_code='routeslug'",$blog_id);
	$sql2 =$wpdb->prepare("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where where blog_id=%d and pref_code='routeslug'",$blog_id);
	$r =$wpdb->get_results($sql2);
	$sql=$wpdb->prepare("update ".CANALPLAN_OPTIONS." set pref_value=%s where blog_id=%d and pref_code='routeslug'",$routeslug,$blog_id);
	if ($wpdb->num_rows==0) {
		$sql=$wpdb->prepare("insert into ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values (%s,%d,'routeslug')",$routeslug,$blog_id);
	}
	$wpdb->query($sql);
}

if (isset($_POST["update_data"])){
		$params = array(
			'redirection' => 0,
			'httpversion' => '1.1',
			'timeout' => 60,
			'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) . ';canalplan-' . CANALPLAN_CODE_RELEASE ),
			'headers' => array( 'Expect:' ),
			'sslverify' => false
	);
	$response = wp_remote_get(CANALPLAN_BASE."/data/canalplan_wp.sqlite" ,$params);
	$handle2=fopen("../wp-content/uploads/canalplan_data.sqlite","w");
	//var_dump($response['response']);
	if ($response['response']['code']==200) {
	//	echo "Retrieving data using remote get";
		$data = $response['body'];
		$handle2=fopen("../wp-content/uploads/canalplan_data.sqlite","w");
		fwrite($handle2, $data);
	} else {
	//	echo "Retrieving data using fopen";
		$handle=fopen(CANALPLAN_BASE."/data/canalplan_wp.sqlite","rb");;
		$contents = '';
		while (!feof($handle)) {
		  $contents = fread($handle, 8192);
		  fwrite($handle2,$contents);
		}
		fclose($handle);
	}
	fclose($handle2);
	$dbhandle = new PDO("sqlite:../wp-content/uploads/canalplan_data.sqlite");
	echo '<table border="1" cellpadding="10" ><tr><th>Table Name </th><th>Contained (Rows)</th><th>Now Contains (Rows)</th></tr>';
	$sqlGetView = 'SELECT placeid,name FROM place_aliases';
	$result = $dbhandle->query($sqlGetView);
	$sql="select count(*) from ".CANALPLAN_ALIASES." where substring(canalplan_id,1,1)!='!';";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res2=$res[0];
	$sql= "delete from ".CANALPLAN_ALIASES." where substring(canalplan_id,1,1)!='!';";
	$res = $wpdb->query($sql);
	foreach ($result as $entry) {
	   $sql= $wpdb->prepare("INSERT INTO ".CANALPLAN_ALIASES." (canalplan_id,place_name) VALUES (%s,%s)",$entry['placeid'],$entry['name']);
	   $res = $wpdb->query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_ALIASES." where substring(canalplan_id,1,1)!='!';";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res3=$res[0];
	print "<tr><td>Canalplan Aliases</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	$sqlGetView = 'SELECT key,name FROM structure';
	$result = $dbhandle->query($sqlGetView);
	$sql="select count(*) from ".CANALPLAN_ALIASES." where substring(canalplan_id,1,1)='!';";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res2=$res[0];
	$sql= "delete from ".CANALPLAN_ALIASES." where substring(canalplan_id,1,1)='!';";
	$res = $wpdb->query($sql);
	foreach ($result as $entry) {
	   $sql= $wpdb->prepare("INSERT INTO ".CANALPLAN_ALIASES." (canalplan_id,place_name) VALUES (%s,%s)",'!'.$entry['key'],$entry['name']);
	   $res = $wpdb->query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_ALIASES." where substring(canalplan_id,1,1)='!';";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res3=$res[0];
	print "<tr><td>Canalplan Features</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";


	$sql="select count(*) from ".CANALPLAN_CODES.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res2=$res[0];
	$sqlGetView = 'SELECT id,name,type,latitude,longitude,attributes FROM place';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	   $sql= $wpdb->prepare("INSERT INTO ".CANALPLAN_CODES." (canalplan_id,place_name,size,lat,`long`,attributes,lat_lng_point) VALUES (%s,%s,%d,%s,%s,%s, GeomFromText(%s)) ON DUPLICATE KEY UPDATE place_name=%s, size=%d, lat=%s, `long`=%s, attributes=%s, lat_lng_point=GeomFromText(%s)",$entry['id'],$entry['name'],$entry['type'],$entry['latitude'],$entry['longitude'],$entry['attributes'],"Point(".$entry['latitude'].' '.$entry['longitude'].")",$entry['name'],$entry['type'],$entry['latitude'],$entry['longitude'],$entry['attributes'],"Point(".$entry['latitude'].' '.$entry['longitude'].")");
	   $res = $wpdb->query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_CODES.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res3=$res[0];
	print "<tr><td>Canalplan Places</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";
	$sql="select count(*) from ".CANALPLAN_LINK.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res2=$res[0];
	$sqlGetView = 'SELECT place1,place2,metres,locks,waterway FROM link';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	   $sql= $wpdb->prepare("INSERT INTO ".CANALPLAN_LINK." (place1,place2,metres,locks,waterway) VALUES (%s,%s,%d,%d,%s) ON DUPLICATE KEY UPDATE metres=%d,locks=%d, waterway=%s",$entry['place1'],$entry['place2'],$entry['metres'],$entry['locks'],$entry['waterway'],$entry['metres'],$entry['locks'],$entry['waterway']);
	   $res = $wpdb->query($sql);

	}
	$sql="select count(*) from ".CANALPLAN_LINK.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res3=$res[0];
	print "<tr><td>Canalplan Links</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	$sql="select count(*) from ".CANALPLAN_CANALS.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res2=$res[0];
	$sqlGetView = 'SELECT id,parent,name,fullname FROM waterway';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	  $sql= $wpdb->prepare("INSERT INTO ".CANALPLAN_CANALS." (id,parent,name,fullname) VALUES (%s,%s,%s,%s) ON DUPLICATE KEY UPDATE name=%s,fullname=%s, parent=%s", $entry['id'],$entry['parent'],$entry['name'],$entry['fullname'],$entry['name'],$entry['fullname'],$entry['parent']);
	   $res = $wpdb->query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_CANALS.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res3=$res[0];
	print "<tr><td>Canalplan Waterways</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	$sql="select count(*) from ".CANALPLAN_POLYLINES.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res2=$res[0];
	$sqlGetView = 'SELECT id,pline,weights FROM polyline';
	$result = $dbhandle->query($sqlGetView);
	foreach ($result as $entry) {
	$sql= $wpdb->prepare("INSERT INTO ".CANALPLAN_POLYLINES." (id,pline,weights) VALUES (%s,%s,%s) ON DUPLICATE KEY UPDATE pline=%s,weights=%s", $entry['id'],addslashes($entry['pline']),$entry['weights'],addslashes($entry['pline']),$entry['weights']);
	   $res = $wpdb->query($sql);
	}
	$sql="select count(*) from ".CANALPLAN_POLYLINES.";";
	$res = $wpdb->get_results($sql,ARRAY_N);
	$res3=$res[0];
	print "<tr><td>Canalplan Polylines</td><td>".$res2[0]."</td><td>".$res3[0]."</td></tr>";

	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".time()."' where blog_id=-1 and pref_code='update_date'";
	$res = $wpdb->query($sql);

	print "</table><br>All Done<br/><br/>";
	$sql="update ".CANALPLAN_OPTIONS." set pref_value='".time()."' where blog_id=-1 and pref_code='update_date'";
	$r = $wpdb->query("SELECT pref_value FROM  ".CANALPLAN_OPTIONS." where blog_id=-1 and pref_code='update_date'");
	if ($wpdb->num_rows==0) {
	$sql="insert into  ".CANALPLAN_OPTIONS." (pref_value,blog_id,pref_code) values ('".time()."',-1,'update_date')";
	}
	$wpdb->query($sql);
	sleep(2);
}

$r2 = $wpdb->get_results("SELECT (".time()." - pref_value) as age FROM  ".CANALPLAN_OPTIONS." where blog_id=-1 and pref_code='update_date'",ARRAY_A);
$do_update="no button";
if ($wpdb->num_rows==0) {
 	    $updated="never";
}
else
{
	  $updated=$r2[0]['age']/(3600*24);
}
// Un comment the following line to force Canalplan to think it's data is rather old
//$updated=22;
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
<p> This may take several minutes to complete... please be patient</p>
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
$sql=$wpdb->prepare("SELECT pref_value FROM  ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='distance_format'",$blog_id);
$r = $wpdb->get_results($sql,ARRAY_A);
if ($wpdb->num_rows==0) {
     $df="f";
}
else
{

  $df=$r[0]['pref_value'];
}
?>
<form action="" name="distform" id="dist_form" method="post">
<select id="DFSelect" name="dfsel" onchange="set_value(0,DFSelect.value);" >

<?php
$arr = array('k'=> "Decimal Kilometres (3.8 kilometres)", 'M' => "Kilometres and Metres (3 kilometres and 798 metres) ", 'm'=>"Decimal miles (2.3 miles)", 'y'=>"Miles and Yards (2 miles and 634 yards) ",'f'=>"Miles and Furlongs (  2 miles , 2 &#190; flg )");
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
<hr>
<h3><?php _e('Canalplan Key') ?></h3>
This key allows Canalplan to link back to your blog posts.
<form action="" name="canalapi" id="canalapi" method="post">

<?php
$sql= $wpdb->prepare("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=%d and pref_code='canalkey'",$blog_id);
$r = $wpdb->get_results($sql,ARRAY_A);
if ($wpdb->num_rows==0) {
     $api="";
}
else
{
$api=$r[0]['pref_value'];
}
$url=get_home_url();
$sname=get_bloginfo('name');
if (strlen($api)<4) {
	$x=CANALPLAN_URL.'api.cgi?mode=register_blogger&domain='.$url.'&title='.urlencode($sname);
	$fcheck=file_get_contents($x);
	$cp_register=json_decode($fcheck,true);
	$api=$cp_register['key'];
	$uid=$cp_register['id'];
	echo "<br/>API Key has been set to : <i> ".$api." </i> and is valid for the blog titled:<b> '".$sname."' </b> on the following url : <b> ".$url.'</b><br />';
	echo '<p class="submit"> <input type="submit" name="SCK"  value="Save Canalplan Key" /></p>';
}

else {
	$api=explode("|",$api);
	$api=$api[0];
	$uid=$api[1];
	echo "<br/>API Key currently set to : <i> ".$api." </i> and is valid for the blog titled:<b> '".$sname."' </b> on the following url : <b> ".$url.'</b><br />';
	echo '<p class="submit"><input type="submit" name="RCK" value="Reset Canalplan Key" /></p>';
}

echo '<input type="hidden" name="canalkey" value="'.$api.'|'.$uid.'">';
?>

</form>
<hr>
<h3><?php _e('Route Page Slug') ?></h3>
 The Route Page Slug is the name of the page you are using for your Route Handling page. The page needs to contain the following code to work : {BLOGGEDROUTES}. <br/> <br/>
<?php
if (!defined('CANALPLAN_ROUTE_SLUG')) { ?>
<form action="" name="routeslug" id="routeslug" method="post">

<?php
$sql = $wpdb->prepare("SELECT pref_value FROM  ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='routeslug'",$blog_id);
$r = $wpdb->get_results($sql,ARRAY_A);
if ($wpdb->num_rows==0) {
     $routeslug="UNDEFINED!";
}
else
{
	$routeslug=$r[0]['pref_value'];
}
echo '<input type="text" name="routeslug" maxlength="20" size="20" value="'.$routeslug.'">';
?>
<input type="hidden" name="routes_slug" value="1"/>
<p class="submit"> <input type="submit"  value="Save Route Page Slug" /></p>

</form>
Your current page slug for blogged routes is
<?php
if ($routeslug=="UNDEFINED!") { echo " <b> currently not defined </b> so please set one";} else {

echo "'". $routeslug."' so you need to make sure that <a href='".get_home_url()."/".$routeslug."'>".get_home_url()."/".$routeslug."</a> exists";
}}
else {
?>
The Site Administrator has set the page slug for blogged routes to be  '
<?php
echo CANALPLAN_ROUTE_SLUG."' so you need to make sure that <a href='".get_home_url()."/".CANALPLAN_ROUTE_SLUG."'>".get_home_url()."/".CANALPLAN_ROUTE_SLUG."</a> exists ";
}


function parse_data($data,$blid)
{$i=1;
global $wpdb;
  $containers = explode(":", $data);
  foreach($containers AS $container)
  {
      $values = explode("|", $container);
      if ( strlen($values[1])> 0) {
       $sql=$wpdb->prepare("Delete from ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code=%s",$blid,$values[0]);
	 $res = $wpdb->query($sql);
     $sql=$wpdb->prepare("insert into ".CANALPLAN_OPTIONS." set blog_id=%d ,pref_code=%s, pref_value=%s",$blid,$values[0],$values[1]);
     $res = $wpdb->query($sql);
        }
  }
}

function canalplan_upgrade(){
//ALTER TABLE `wp_canalplan_codes` CHANGE `lat` `lat` VARCHAR( 20 ) NOT NULL DEFAULT '0'
//ALTER TABLE `wp_canalplan_codes` CHANGE `long` `long` VARCHAR( 20 ) NOT NULL DEFAULT '0'

}
?>