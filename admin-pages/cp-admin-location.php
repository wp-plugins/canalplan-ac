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
	switch($_POST['location']) {
	case 'none':
        $dataset="None";
        break;
    case 'Browser':
        $dataset='Browser|'.$_POST["lati"].'|'.$_POST["longi"];
        break;
    case 'Canalplan':
        $dataset='Canalplan|'.$_POST['dataset'];
        break;
	default :
	$dataset="None";
	}
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
        break;
    case 'Canalplan':
        $radcp ='checked="checked"';
        $cploc=" (Currently Set to ".stripslashes($values[2])." )";
        $lat="Not Set";
        $long="Not Set";
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
if("geolocation" in navigator) { } else {
 document.getElementById("geobut").disabled = true;
 document.getElementById("geobut").value="Get from Browser - Disabled";
 }
function GetLocation() {
document.getElementById("lati").value="-1";
document.getElementById("longi").value='0.343';
if("geolocation" in navigator) {
	navigator.geolocation.getCurrentPosition(function(position) {
		document.getElementById("lati").value=position.coords.latitude;
		document.getElementById("longi").value=position.coords.longitude;
	});
}
}

function showValue(cptext,cpid)
{
  document.getElementById("dataset").value=cpid+"|"+cptext;
 }

	//-->
</script>
<div class="wrap">

<h2><?php _e('Set Location') ?> </h2>
<br>
<form action="" name="flid" id="fav_list" method="post">
<input type="hidden" name="tagtypeID" value="ZED" />
<input type="radio" name="location" value="Browser" <?php echo $radbro; ?> >Manually Set :&nbsp;
Lat : <input type="text" name="lati" id="lati" value="<?php echo $lat; ?>" maxlength="12" size="12"> / Long : <input type="text" name="longi" id="longi" value="<?php echo $long; ?>" maxlength="12" size="12">
&nbsp;&nbsp;&nbsp;<input type="button" onclick="GetLocation();" id="geobut" value="Get from Browser"><br />
<input type="radio" name="location" value="Canalplan" <?php echo $radcp; ?> > Use Canalplan :&nbsp; <?php echo $cploc; ?> <input type="text" name="CanalPlanID" ID="CanalPlanID" align="LEFT" size="40" maxlength="90"/>
<INPUT TYPE="button" name="CPsub" VALUE="Set CanalPlan Location"  onclick="getCanalPlan2(CanalPlanID.value);showValue(CanalPlanText.value,code_id);"/><br />
<input type="radio" name="location" value="None" <?php echo $radnon; ?> >Don't Set a Location<br /><br />
<input type="hidden" name="_submit_check" value="1"/>
<input type="hidden" name="dataset" id="dataset" value="" />
<input type="hidden" name="CanalPlanText" ID="CanalPlanText" align="LEFT" size="40" maxlength="90"/>
<input type="submit"  value="Save Changes" />
</form>
<script language="JavaScript" type="text/javascript">
canalplan_actb(document.getElementById("CanalPlanID"),new Array());
</script>
</div>

<?php
function parse_data($data,$blid) {
	$i=1;
	global $wpdb;
    //  $values = explode("|", $data);
    //  var_dump($values);
    //
     $sql=$wpdb->prepare("Delete from ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='Location'",$blid);
	 $res = $wpdb->query($sql);
     $sql=$wpdb->prepare("insert into ".CANALPLAN_OPTIONS." set blog_id=%d ,pref_code='Location', pref_value=%s",$blid,$data);
     $res = $wpdb->query($sql);
   //     }
}
?>