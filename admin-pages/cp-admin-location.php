<?php
/*
Extension Name: Canalplan Home Mooring
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 3.0
Description: Home Mooring admin page for the Canalplan AC Plugin
Author: Steve Atty
*/

require_once ('admin.php');
$title = __('Home Mooring');
$this_file =  'canalplan-home.php';
$parent_file = 'canalplan-manager.php';
$base_dir=dirname(__FILE__);
global $blog_id,$wpdb;
echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';

if(isset($_POST['_submit_check']))
{
	$sql=$wpdb->prepare("SELECT place_name,canalplan_id,lat,`long`,GLength(LineString(lat_lng_point, GeomFromText('Point(".$_POST["lati"]." ".$_POST["longi"].")'))) AS distance FROM ".CANALPLAN_CODES." where attributes != %s ORDER BY distance ASC LIMIT 1", 'm' );
	$res = $wpdb->get_results($sql,ARRAY_A);
	$row=$res[0];
	switch($_POST['location']) {
	case 'none':
        $dataset="None";
        break;
    case 'Browser':
        $dataset='Browser|'.$_POST["lati"].'|'.$_POST["longi"].'|'.current_time('timestamp').'|0|'.$row['canalplan_id'].'|'.$row['place_name'];
        break;
    case 'Canalplan':
		$dataset="None";
       if (strlen($_POST['dataset'])>4 )$dataset='Canalplan|'.$_POST['dataset'].'|'.current_time('timestamp').'|0';
        break;
   	case 'Backitude':
        $dataset="Backitude|".$_POST["lati2"].'|'.$_POST["longi2"].'|'.$_POST["time"] .'|'.$_POST["tz"].'|'.$row['canalplan_id'].'|'.$row['place_name'];
        break;
	default :
	$dataset="None";
	}
	$dataset.='|'.$_POST['Passthrough'].'|'.$_POST['CanalPlanuser'];
	parse_data($dataset,$blog_id);
}
	echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
	echo '<script type="text/javascript"> var wpcontent="'.plugins_url().'"</script>';

$radcp=" ";
$radbro=" ";
$radnon=" ";

$sql=$wpdb->prepare("select * from ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='Location'",$blog_id);
$res = $wpdb->get_results($sql,ARRAY_A);
$values=explode('|',$res[0]['pref_value']);
if (strlen($values[4])==0) $values[4]=0;
	switch($values[0]) {
	case 'none':
        $radnon='checked="checked"';
        $cploc=" ( No Location Set )";
        $lat="Not Set";
        $long="Not Set";
        break;
    case 'Browser':
        $radbro='checked="checked"';
        $cploc=" ( No Location Set )";
        $lat=$values[1];
        $long=$values[2];
        $cp_key=$values[8];
        $cp_pass=$values[7];
        break;
    case 'Canalplan':
        $radcp ='checked="checked"';
        $cploc=" (Currently Set to ".stripslashes($values[2])." )";
        $lat="Not Set";
        $long="Not Set";
        $cp_key=$values[6];
        $cp_pass=$values[5];
        break;
    case 'Backitude':
        $radback='checked="checked"';
        $cploc=" ( No Location Set )";
        $lat=$values[1];
        $long=$values[2];
        $cp_key=$values[8];
        $cp_pass=$values[7];

        break;
	default :
	$dataset="None";
	}
?>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/plan.js"></script>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplan_actb.js"></script>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplanfunctions.js" DEFER></script>
<script language="JavaScript" type="text/javascript"><!--

function getCanalPlan2(tag)
{
 code_id=Canalplan_Download_Code(tag);
 document.getElementById("CanalPlanText").value=tag
}

function GetLocation() {
	var options = {
  enableHighAccuracy: false,
  timeout: 2500,
  maximumAge: 10000
};
if("geolocation" in navigator) {
	document.getElementById("lati").value='';
	document.getElementById("longi").value='';
	navigator.geolocation.getCurrentPosition(function(position) {
		document.getElementById("lati").value=position.coords.latitude;
		document.getElementById("longi").value=position.coords.longitude;
		});
	}
 else {
	document.getElementById("geobut").disabled = true;
	document.getElementById("geobut").value="Get from Browser - Disabled";
 }
}
function showValue(cptext,cpid)
{
  document.getElementById("dataset").value=cpid+"|"+cptext;
}
</script>
<div class="wrap">

<h2><?php _e('Set Location') ?> </h2>
<br />
Current location
<?php
// Put in to fix some problems in multisite when some blogs got a different TZ even though they were all set the same
date_default_timezone_set('UTC');
echo " ( Set by ".$values[0]." at  ".date("l, j. M. Y, H:i:s", $values[3]+$values[4])." ) "; ?> is : <br /><br />
<b>Latitude : </b> <?php echo round($lat,6); ?> <br />
<b>Longitude :</b> <?php echo round($long,6); ?> <br />
<?php
$checked_flag=array('on'=>'checked','off'=>'');
$location_status=array('0'=>'Error','1'=>'Success ');
$status_colour=array(0=>'red',1=>'green');
if ($lat=='Not Set') {
	print "<b>Canalplan location is set to :</b> <a href='".CANALPLAN_GAZ_URL.$values[1]."' target='_new' > ".stripslashes($values[2])."</a> <br />";
} else {
		$sql=$wpdb->prepare("SELECT place_name,canalplan_id,lat,`long`,GLength(LineString(lat_lng_point, GeomFromText('Point(".$lat." ".$long.")'))) AS distance FROM ".CANALPLAN_CODES." where attributes != %s ORDER BY distance ASC LIMIT 1", 'm' );
		$res = $wpdb->get_results($sql,ARRAY_A);
		if(count($res)>0){
			$row=$res[0];
			print "<b>Nearest Canalplan location is :</b> <a href='".CANALPLAN_GAZ_URL.$row['canalplan_id']."' target='_new' > ".$row['place_name']."</a> <br />";
		}
			}
		$sql2 =$wpdb->prepare("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='canalkey'",$blog_id);
		$r = $wpdb->get_results($sql2);

		$url = get_bloginfo('url');
		$img_url=$url.'/wp-content/plugins/canalplan-ac/canalplan/';
		$url.='/wp-content/plugins/canalplan-ac/cp_location.php?config='.$r[0]->pref_value;
		$config_url=$url;
?>
<br />
<form action="" name="flid" id="fav_list" method="post">
<input type="hidden" name="tagtypeID" value="ZED" />
<input type="radio" name="location" value="Browser" <?php echo $radbro; ?> >Manually Set :&nbsp;
Lat : <input type="text" name="lati" id="lati" value="<?php echo $lat; ?>" maxlength="12" size="12"> / Long : <input type="text" name="longi" id="longi" value="<?php echo $long; ?>" maxlength="12" size="12">
&nbsp;&nbsp;&nbsp;<input type="button" onclick="GetLocation();" id="geobut" value="Get from Browser"><br />
<input type="radio" name="location" value="Canalplan" <?php echo $radcp; ?> > Use Canalplan :&nbsp; <?php echo $cploc; ?> <input type="text" name="CanalPlanID" ID="CanalPlanID" align="LEFT" size="40" maxlength="90"/>
<INPUT TYPE="button" name="CPsub" VALUE="Set CanalPlan Location"  onclick="getCanalPlan2(CanalPlanID.value);showValue(CanalPlanText.value,code_id);"/><br />
<input type="radio" name="location" value="None" <?php echo $radnon; ?> >Don't Set a Location<br />
<input type="radio" name="location" value="Backitude" <?php echo $radback; ?> >Set Location from Backitude<br />
<input type="checkbox" name="Passthrough" <?php echo $checked_flag[$cp_pass]; ?> >Location Passthrough to Canalplan&nbsp;&nbsp;( Using Canalplan User Account Key : <input type="text" name="CanalPlanuser" ID="CanalPlanuser" align="LEFT" size="25" maxlength="90" value="<?php echo $cp_key;?>" /> &nbsp;)
<?php
if ($cp_pass=='on') {
	$sql=$wpdb->prepare("select pref_value from ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='location_error'",$blog_id);
	$res = $wpdb->get_results($sql);
	$update_result=explode('|',$res[0]->pref_value);
	echo "(<span style='color:".$status_colour[$update_result[0]]."';>".$location_status[$update_result[0]]." : ". $update_result[1].' -  '. date("l, j M Y, H:i:s", $update_result[2]).' </span>) ';
}
?>
<br />
<input type="hidden" name="_submit_check" value="1"/>
<input type="hidden" name="lati2" value="<?php echo $lat; ?>"/>
<input type="hidden" name="longi2" value="<?php echo $long; ?>"/>
<input type="hidden" name="tz" value="<?php echo $values[6]; ?>"/>
<input type="hidden" name="time" value="<?php echo $values[3]; ?>"/>
<input type="hidden" name="dataset" id="dataset" value="" />
<input type="hidden" name="CanalPlanText" ID="CanalPlanText" align="LEFT" size="40" maxlength="90"/>
<br />
<input type="submit"  value="Save Changes" />
</form>
<br />

<hr />
<h3>Backitude</h3>
<img src=<?php echo $img_url."backitude.png"; ?> alt="Backitude Logo" height="70" width="70">
<p><a href="http://www.downwindoutdoors.com/apps/backitude/faq.html" target="_blank">Backitude</a> is a light-weight and efficient Android 2.2+ application designed to capture the location of your device at configurable time intervals or whenever the position is served up by the operating system from another application. Keep your resources free and your battery full.</p>
<p><a href="http://www.downwindoutdoors.com/apps/backitude/faq.html" target="_blank">Backitude</a> is free - but <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BB8Y6KJVNKJVE">donations for the developer of Backitude</a> are very welcome. Thanks to Brian Gaugler for making such a neat utility freely available</p>
<a href="https://play.google.com/store/apps/details?id=gaugler.backitude" target="_blank"><img src= <?php echo $img_url."en_app_rgb_wo_60.png"; ?> alt="Backitude GPS Location Tracker" title="Backitude GPS Location Tracker" /></a> <br /><br />
<?php
 if (eregi('Android',  $_SERVER['HTTP_USER_AGENT']))
 {
  echo "To configure Backitude directly click on the Configure Backitude button below <br /><br />";
  $button_text='Configure Backitude';
  $configuration_url='com.gaugler.backitude://preferenceImport/?prefs='.$config_url;
} else  {
	echo "Download the Backitude configuration file below and then upload it to your mobile device <br /><br />";
	$configuration_url=$config_url;
	$button_text='Download Backitude Configuration';}

echo '<button onclick=\'location.href="'.$configuration_url.'"\'>'.$button_text.'</button>';

?>
<hr />
<script language="JavaScript" type="text/javascript">
canalplan_actb(document.getElementById("CanalPlanID"),new Array());
</script>
</div>
<?php

function parse_data($data,$blid) {
	$i=1;
	global $wpdb;
     $sql=$wpdb->prepare("Delete from ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='Location'",$blid);
	 $res = $wpdb->query($sql);
     $sql=$wpdb->prepare("insert into ".CANALPLAN_OPTIONS." set blog_id=%d ,pref_code='Location', pref_value=%s",$blid,$data);
      $res = $wpdb->query($sql);
}
?>