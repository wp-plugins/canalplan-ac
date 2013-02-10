<?php
/*
Extension Name: Canalplan Import Route
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 3.0
Description: Import Route page for the Canalplan AC Plugin
Author: Steve Atty
*/
require_once('admin.php');
$title = __('CanalPlan Import Route');
nocache_headers();
//global $wpdb,$blog_id;
?>
<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/calendar.js"></script>
<?php
if(isset($_POST['_submit_check']))
{
$i=$_POST['_submit_check'];
}
else {
$i=0;
}
if ($i<2) {
if (isset($_GET['cpsessionid'])) { $cpsessionid=$_GET['cpsessionid'];unset($_GET['cpsessionid']); $i=1;}
}
$startstring="";
switch ($i) {
    case 0:
        echo "<h3>Step 1 - Go to CanalPlan AC and Plan a Route</h3> This will open the CanalPlan AC webiste in this window. Once you've created your route (don't forget to set a title AND the correct start date!) you simply click on the blog this button and it will return you back to your blog and you can continue importing the route";
$sql = $wpdb->prepare("SELECT canalplan_id FROM ".CANALPLAN_FAVOURITES."  where blog_id=%d and place_order=0",$blog_id);
$r = $wpdb->get_results($sql,ARRAY_A);
$rw =$r[0];
$startstring=$rw['canalplan_id'];
?>
<form action="<? echo CANALPLAN_URL ; ?>api.cgi" method="get">
<input type="hidden" name="mode" value="blog"/>
 <?php if (isset($startstring)) { echo '<input type="hidden" name=startat value="'.$startstring.'" />'; } ?>
<p class="submit"> <input type="submit"  value="Go To CanalPlan AC" /> </p>
</form>
<?php
        break;
    case 1:
        echo "<h3>Step 2 - Edit basic details </h3>";
?>
<?php
$cptable='places';
$geturl=CANALPLAN_URL."api.cgi?session=".$cpsessionid."&mode=table&table=".$cptable;
$handle = fopen (CANALPLAN_URL."api.cgi?session=".$cpsessionid."&mode=table&table=".$cptable , 'r');
		while (($data = fgets($handle)) !== FALSE)
		{
$jdata=json_decode($data, true);

}
fclose($handle);
foreach ($jdata as $jsondata){
$places[$jsondata['name']]=$jsondata['value'];
}
#var_dump($places);
$sd=date('d-M-Y',strtotime($places['start_date']));
?>
<form action="" name="distform" id="dist_form" method="post">
<input type="hidden" name="_submit_check" value="2"/>
<table><tr><td> Category for this trip : </td><td>
<select name="category_select" >
 <option value=0>Select Category for this Trip  </option>

<?php
  $categories=  get_categories('hide_empty=0');
  foreach ($categories as $cat) {
        $option = '<option value='.get_cat_ID( $cat->cat_name ).'>';
        $option .= $cat->cat_name;
        $option .= '</option>';
        echo $option;
  }
 ?>
</select></td></tr><tr><td>Start Date for this trip : </td><td>
<?php
echo "<script>DateInput('startdate', true, 'DD-MON-YYYY','".$sd."')</script>";
?>
</td></tr><tr><td>Route title : </td><td>
 <?php echo '<input type="text" name="rtitle" value="'.$places['title'].'" size=100/>' ?> </td></tr>
<tr><td>Route Description : </td><td><input type="text" name="rdesc" value="" size=100></td></tr>
<?php
echo "<input type='hidden' name='cpsessid' value='".$cpsessionid."'/>";
unset($_GET['cpsessionid']);
 ?>
</table>
<p class="submit"> <input type="submit"  value="Import Route" /> </p>
</form>
<?php
        break;
    case 2:
        echo "<h3> Step 3 -Creating Draft Posts for each day of your trip</h3>";

$cpsession=$_POST['cpsessid'];
#cptable can be one of 'detail','durations','extremes','places','route' and 'stops');
$cptable='durations';


# for Durations we need to load the value of jdata['value'] into jdata['name']
$url=CANALPLAN_URL."api.cgi?session=".$cpsession."&mode=table&table=".$cptable;
$handle = fopen ($url , 'r');
		while (($data = fgets($handle)) !== FALSE)
		{
$jdata=json_decode($data, true);

}
fclose($handle);
foreach ($jdata as $jsondata){
$durations[$jsondata['name']]=$jsondata['value'];
}
# Stops we create an associative array with an entry which is the index of the stopping place for each day
$cptable='stops';
$handle = fopen (CANALPLAN_URL."api.cgi?session=".$cpsession."&mode=table&table=".$cptable , 'r');
		while (($data = fgets($handle)) !== FALSE)
		{
$stopdata=json_decode($data, true);
}
fclose($handle);
$stops[0]="1";
foreach ($stopdata as $jsondata){
$stops[$jsondata['idx']]=$jsondata['detail_link'];
$totaldistance=$jsondata['distance'];
$totallocks=$jsondata['locks'];
}

# Places contains all sorts of things so lets build an associative array
$cptable='places';
$handle = fopen (CANALPLAN_URL."api.cgi?session=".$cpsession."&mode=table&table=".$cptable , 'r');
		while (($data = fgets($handle)) !== FALSE)
		{
$jdata=json_decode($data, true);

}
fclose($handle);
foreach ($jdata as $jsondata){
$places[$jsondata['name']]=$jsondata['value'];
}

# For the route we get the place1 and build from that
$cptable='detail';

$handle = fopen (CANALPLAN_URL."api.cgi?session=".$cpsession."&mode=table&table=".$cptable , 'r');
		while (($data = fgets($handle)) !== FALSE)
		{

$jdata=json_decode($data, true);

}
fclose($handle);
foreach ($jdata as $jsondata){
if(!isset($route)) {$route[]=$jsondata['place1'];}
$route[]=$jsondata['place1'];
$lastplace=$jsondata['place2'];
}
$route[]=$lastplace;

$routestring=implode(",", $route);

# Get the start date from the places array
$sd=$places['start_date'];
$sd=$_POST['startdate'];
#Get the number of days from the stops array, removing 1 because we've forced a fake value into the start of it
$duration=count($stops)-1;

# OK We now have all the data we need so lets get to work.

# Step 1. Create the overall route.
# This is the cp_routes table
# Select the max route_id for the current blog and add 1 to it.
$sql=$wpdb->prepare("Select max(route_id) as mri from ".CANALPLAN_ROUTES." where blog_id=%d",$blog_id);
$r=$wpdb->get_results($sql,ARRAY_A);
$tr=$r[0];
$route_id=$tr['mri']+1;
# Insert the title from the places array $places['title']
$sql = $wpdb->prepare("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='distance_format' limit 1",$blog_id);
$r = $wpdb->get_results($sql,ARRAY_A);
if ($wpdb->num_rows==0) {
     $df="f";
}
else
{
  $df=$r[0]['pref_value'];
}
$sql=$wpdb->prepare("insert into ".CANALPLAN_ROUTES." set title=%s, description=%s, start_date=%s, duration=%d, totalroute=%s, total_distance=%d, total_locks=%d, blog_id=%d, route_id=%d, status=1, uom=%s",$_POST['rtitle'],$_POST['rdesc'],date('Y-m-d',strtotime($sd)),$duration,$routestring,$totaldistance,$totallocks,$blog_id,$route_id,$df);
#print "<br>".$sql."<br>";
$r=$wpdb->query($sql);

$offset=0;
$category[]=$_POST['category_select'];
for ( $dc = 0; $dc < $duration; $dc += 1) {
$dc2=$dc+1;
$date=date('Y-m-d H:i:s',strtotime("+ ".$dc." days",strtotime($sd)));
// Create post object
  $my_post = array();
  $my_post['post_title'] = 'Post for Day '.$dc2.' of Trip';
  $my_post['post_content'] = '[[CPRM:]] [[CPRS:]] ';
  $my_post['post_status'] = 'draft';
  $my_post['post_category'] = $category;
  $my_post['post_date']= $date;
  $my_post['post_date_gmt'] = $date;

// Insert the post into the database
$newpostid=wp_insert_post( $my_post );
print "Post for ".date('l jS \of F Y',strtotime($date))." created <br>";
$route=split(",",$routestring);
# We need the start and end ids putting in here
$first=$stops[$dc];
$last=$stops[$dc+1];
$first=$first+$offset;
$offset=0;
if ($stopdata[$dc]['detail_end']!= $route[$last]){$offset=1;}
$last=$last+$offset;
$dayroute=array_slice($route,$first,($last-$first)+1);
$newlocks=0;
$newdistance=0;
for ($placeindex=1;$placeindex<count($dayroute);$placeindex+=1){
$p1=$dayroute[$placeindex];
$p2=$dayroute[$placeindex-1];
$sql=$wpdb->prepare("select metres,locks from ".CANALPLAN_LINK." where (place1=%s and place2=%s ) or  (place1=%s and place2=%s )",$p1,$p2,$p2,$p1);
$r=$wpdb->get_results($sql,ARRAY_A);
$rw=$r[0];
$newlocks=$newlocks+$rw['locks'];
$newdistance=$newdistance+$rw['metres'];
}
for ($placeindex=0;$placeindex<count($dayroute);$placeindex+=1){
$x=$dayroute["$placeindex"];
$sql=$wpdb->prepare("select attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$x);
$r=$wpdb->get_results($sql,ARRAY_A);
$rw=$r[0];
if (strpos($rw['attributes'],'L') !== false) {
if ($placeindex==0) {$newlocks=$newlocks;} elseif ($placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+1;}
}
if (strpos($rw['attributes'],'2') !== false) {
if ($placeindex==0) {$newlocks=$newlocks;} elseif ($placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+2;}
}
$newlocks=$newlocks+$rw['locks'];
}
$sql=$wpdb->prepare("insert into ".CANALPLAN_ROUTE_DAY." set route_id=%d, day_id=%d, blog_id=%d, post_id=%d, route_date=%s,start_id=%d, end_id=%d, distance=%d, `locks`=%d",$route_id,$dc2,$blog_id,$newpostid,date('Y-m-d',strtotime($date)),$first,$last,$newdistance,$newlocks);
$r=mysql_query($sql);
}

print "<br><br>Draft Posts created. You can now go and <a href='/wp-admin/edit.php'>edit</a> the posts or <a href='?page=canalplan-ac/admin-pages/cp-manage_route.php'>change the daily subtotals</a>";
break;
}
if ($i>10){
?>
<form action="" name="distform" id="dist_form" method="post">
<input type="hidden" name="_submit_check" value="1"/>
<input type="hidden" name="dataset" id="dataset" value="" />
<p class="submit"> <input type="submit" onclick="showValue('general_options')"  value="Proceed to next step" /></p>

</form>
<?php
}
?>