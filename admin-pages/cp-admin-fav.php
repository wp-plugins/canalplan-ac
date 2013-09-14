<?php
/*
Extension Name: Canalplan Favourites
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 3.0
Description: Favourites admin for the Canalplan AC Plugin
Author: Steve Atty
*/

require_once('admin.php');
$parent_file = 'canalplan-manager.php';
$title = __('Favourites');
$this_file = 'canalplan-favour.php';
global $blog_id,$wpdb;
echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';

if(isset($_POST['_submit_check']))
{
	parse_data($_POST['dataset'],$blog_id);
}
	echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
	echo '<script type="text/javascript"> var wpcontent="'.plugins_url().'"</script>';
?>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/plan.js"></script>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplan_actb.js"></script>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplanfunctions.js"></script>
<script language="JavaScript" type="text/javascript">

function getCanalPlan2(tag)
{
 code_id=Canalplan_Download_Code(tag);
 document.getElementById("CanalPlanText").value=tag
}

function InsertCanalPlan(cptext)
{
	document.getElementById("place_list").options[document.getElementById("place_list").length]= new Option (cptext,code_id,false);
}
        function showValue(listID)
        {
    var list = document.getElementById(listID);
    var items = list.getElementsByTagName("option");
    var itemsString = "";
    var itemsString2 = "";
    for (var i = 0; i < items.length; i++) {
        if (itemsString.length > 0) itemsString += "^";
        itemsString += items[i].value;
 	itemsString += '|';
        itemsString += items[i].innerHTML;
    }
document.getElementById("dataset").value=itemsString;
        }
</script>
<div class="wrap">
<h2><?php _e('Manage Your Favourites') ?> </h2>
<br>
<h3>Stage 1 : Find a Canalplan Location</h3>
<br>
<form>
 <input type="text" name="CanalPlanID" ID="CanalPlanID" align="LEFT" size="40" maxlength="90"/>
<INPUT TYPE="button" name="CPsub" VALUE="Select Location"  onclick="getCanalPlan2(CanalPlanID.value);"/>
</form>
<br>
<h3>Stage 2 : Edit the Description</h3>
<form>
 <input type="text" name="CanalPlanText" ID="CanalPlanText" align="LEFT" size="40" maxlength="90"/>
<INPUT TYPE="button" name="CPTsub" VALUE="Add To Favourites "  onclick="InsertCanalPlan(CanalPlanText.value);"/>
</form>
<br>
<h3>Stage 3: Organise Favourites </h3>
<br>
<form action="" name="flid" id="fav_list" method="post">
<table><tr><th>Favourites</th><th></th></tr>
<tr><td>
<select id="place_list" name="plid" size="10" multiple="multiple" style="min-width:30em;
width:30em; height:auto;">
<?php
$sql=$wpdb->prepare("SELECT distinct canalplan_id,place_name FROM ".CANALPLAN_FAVOURITES." where blog_id=%d and place_order >0 ORDER BY `place_order` ASC",$blog_id);
$r = $wpdb->get_results($sql,ARRAY_A);
foreach($r as $rw)
{
  echo '<option value="'.$rw['canalplan_id'].'">'.stripslashes($rw['place_name']).'</option>';
}
?>
</select></td>
<td>
<ul class="plain-list">
<li><span onclick="ActOnList('top')">To top</span></li>
<li><span onclick="ActOnList('up')">Up</span></li>
<li><span onclick="ActOnList('dwn')">Down</span></li>
<li><span onclick="ActOnList('bot')">To bottom</span></li>
<li><span onclick="ActOnList('del')">Delete</span></li>
</ul>
<td>
<ul class="plain-list">
<li><span onclick="ActOnList('sel')">Select all</span></li>
<li><span onclick="ActOnList('clr')">Unselect all</span></li>
<li><span onclick="ActOnList('tog')">Toggle selection</span></li>
</ul>
</td></tr>
</table>
<br>
<input type="hidden" name="_submit_check" value="1"/>
<input type="hidden" name="dataset" id="dataset" value="" />
 <div align=right> <input type="submit" onclick="showValue('place_list')"  value="Save Favourites" /></div>
</form>
<script language="JavaScript" type="text/javascript">
canalplan_actb(document.getElementById("CanalPlanID"),new Array());
</script>
</div>

<?php
function parse_data($data,$blid)
{$i=1;
global $wpdb;
$sql=$wpdb->prepare("Delete from ".CANALPLAN_FAVOURITES." where blog_id=%d and place_order>0",$blid);
$res = $wpdb->query($sql);
  $containers = explode("^", $data);
  foreach($containers AS $container)
  {
      $values = explode("|", $container);
      $sql=$wpdb->prepare("insert into ".CANALPLAN_FAVOURITES." set blog_id=%s ,canalplan_id=%s, place_name=%s,place_order=%d",$blid,$values[0],$values[1],$i);
   	  $res = $wpdb->query($sql);
        $i ++;
  }
}
?>