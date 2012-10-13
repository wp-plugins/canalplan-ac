<?php
/*
Extension Name: Canalplan Manage Route
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 2.8
Description: Manage Route Page for the Canalplan AC Plugin
Author: Steve Atty
*/

require_once('admin.php');
$title = __('CanalPlan Manage Route');
nocache_headers();
?>

<div class="wrap">

<h2><?php _e('Manage Routes') ?> </h2>
<br>

<?php
global $blog_id;
if(isset($_POST['_submit_check']))
{
$subcheck=$_POST['_submit_check'];
#var_dump($_POST);
if ($_POST['NO_NO']){
unset($_POST["route_list"]);
}
if ($_POST['OK']){
unset($_POST["route_list"]);
unset($_POST["_submit_check"]);
}
if ($_POST['delete']){ 
echo "Only click on Confirm Delete if you really want to delete the route, and any associated posts";
echo '<form action="" name="distform" id="dist_form" method="post"> ';
echo '<input type="hidden" name="_submit_check" value="99"/>';
echo '<input type="hidden" name="route_list" value="'.$_POST["route_list"].'"/>';
echo '<p class="submit"> <input type="submit" name="def_delete" value="Yes Please, delete them all" />&nbsp;&nbsp;<input type="submit" name="NO_NO" value="No, get me out of here!" /></p></form>';
$_POST["route_list"]=0;
$_POST["_submit_check"]=99;
}
if ($_POST['def_delete']){ 
$sql="select post_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$blog_id." and route_id=".$_POST["route_list"].";";
$r=mysql_query($sql);
while($rid = mysql_fetch_array($r))
{
wp_delete_post($rid['post_id']);
}
$sql="delete from ".CANALPLAN_ROUTES." where blog_id=".$blog_id." and route_id=".$_POST["route_list"].";";
$r=mysql_query($sql);
$sql="delete from ".CANALPLAN_ROUTE_DAY." where blog_id=".$blog_id." and route_id=".$_POST["route_list"].";";
$r=mysql_query($sql);
unset($_POST["route_list"]);
unset($_POST["_submit_check"]);
}

if ($_POST['_submit_check']==2) {


$sql="Update ".CANALPLAN_ROUTES." set title='".$_POST['rtitle']."' , description='".$_POST['rdesc']."', uom='".$_POST['dfsel']."', status=".$_POST['routestatus']." where blog_id=".$blog_id." and route_id=".$_POST["route_list"].";";
$dformat=$_POST['dfsel'];
$res2 = mysql_query($sql);
$eplacelength=$_POST['duration'];
for ($eplacecount=1;$eplacecount<=$eplacelength;$eplacecount+=1){
$elockno=$eplacecount-1;
$elock='endlock'.$elockno;
$elockno2=$eplacecount;
$elock2='endlock'.$elockno2;
if ($eplacecount<$eplacelength) {$sql="Update ".CANALPLAN_ROUTE_DAY." set end_id=".$_POST[$eplacecount]." where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." and day_id=".$eplacecount.";"; 
#echo $sql."<br>";
$res2 = mysql_query($sql);
}
if ($eplacecount>1) {$sql="Update ".CANALPLAN_ROUTE_DAY." set start_id=".$_POST[$eplacecount-1]." where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." and day_id=".$eplacecount.";"; 
#echo $sql."<br>"; 
$res2 = mysql_query($sql);
}
if ($_POST[$elock2]=='on') {
$sql="update ".CANALPLAN_ROUTE_DAY." set flags='L' where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." and day_id=".$eplacecount.";";
#print $sql;
$res2 = mysql_query($sql);
}
else
{
$sql="update ".CANALPLAN_ROUTE_DAY." set flags='' where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." and day_id=".$eplacecount.";";
#print $sql;
$res2 = mysql_query($sql);
}

$sql="Select totalroute from ".CANALPLAN_ROUTES." where blog_id=".$blog_id." and route_id=".$_POST["route_list"].";"; 
#print $sql;
$r=mysql_query($sql);
$tr=mysql_fetch_array($r);
$totalroute=$tr["totalroute"];
#print $totalroute;
$sql="Select start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." and day_id=".$eplacecount.";"; 
#print $sql;
$r=mysql_query($sql);
$rw=mysql_fetch_array($r);
$route=split(",",$totalroute);
$dayroute=array_slice($route,$rw['start_id'],( $rw['end_id']-$rw['start_id'])+1);
$newlocks=0;
$newdistance=0;
for ($placeindex=1;$placeindex<count($dayroute);$placeindex+=1){
$p1=$dayroute[$placeindex];
$p2=$dayroute[$placeindex-1];
$sql="select metres,locks from ".CANALPLAN_LINK." where (place1='".$p1."' and place2='".$p2."' ) or  (place1='".$p2."' and place2='".$p1."' ) ;";
$r=mysql_query($sql);
$rw=mysql_fetch_array($r);
$newlocks=$newlocks+$rw['locks'];
$newdistance=$newdistance+$rw['metres'];
}
for ($placeindex=0;$placeindex<count($dayroute);$placeindex+=1){
$x=$dayroute["$placeindex"];
$sql="select attributes from ".CANALPLAN_CODES." where canalplan_id='".$x."'";
#print $sql."<br>";
$r=mysql_query($sql);
$rw=mysql_fetch_array($r);
#print $rw['attributes'];
#print $x." - ".$rw['attributes']." - ".stripos($rw['attributes'],'L')."<br>";
if (strpos($rw['attributes'],'L') !== false) {
#echo "we have a lock at ".$x." - ".$rw['attributes']."<br>";
if ($_POST[$elock]=='on' and $placeindex==0) {$newlocks=$newlocks;} elseif ($_POST[$elock2]!='on' and $placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+1;}
}
if (strpos($rw['attributes'],'2') !== false) {
#echo "we have 2 locks at ".$x." - ".$rw['attributes']."<br>";
if ($_POST[$elock]=='on' and $placeindex==0) {$newlocks=$newlocks;} elseif ($_POST[$elock2]!='on' and $placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+2;}
}
$newlocks=$newlocks+$rw['locks'];
}
$sql="update ".CANALPLAN_ROUTE_DAY." set distance=".$newdistance." , locks=".$newlocks." where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." and day_id=".$eplacecount.";";
$r=mysql_query($sql);
}
}

if ($_POST["route_list"] >=1 ) {

# we need to put a check in here to see if they are changing a specific route
?>
<h3>Step 2 : Change the route details</h3>

<?php


$r = "SELECT distinct title,description,totalroute,duration,uom,status FROM ".CANALPLAN_ROUTES." where blog_id=".$blog_id." and route_id=".$_POST["route_list"]."  ORDER BY `start_date` ASC";
$r=mysql_query($r);
$rw = mysql_fetch_array($r);
$duration=$rw['duration'];
$dformat=$rw['uom'];
$pstatus=$rw['status'];
echo '<form action="" name="distform" id="dist_form" method="post"> <table>
<tr><td> Route Title: </td><td><input type="text" name="rtitle" value="'.$rw['title'].'" size=100/> </td></tr><tr><td> Route Description :</td><td> <input type="text" name="rdesc" value="'.$rw['description'].'" size=100 </></td></tr><tr><td> Distance Format: </td><td><select id="DFSelect" name="dfsel">';
$arr = array(k=> "Decimal Kilometres (3.8 kilometres)", M => "Kilometres and Metres (3 kilometres and 798 metres) ", m=>"Decimal miles (2.3 miles)", y=>"Miles and Yards (2 miles and 634 yards) ",f=>"Miles and Furlongs (  2 miles , 2 &#190; flg )");
foreach ($arr as $i => $value) {
if ($i==$dformat){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}
}
echo '</select></td></tr><tr><td> Route Status: </td><td><select id="route_status" name="routestatus">';
$arr = array(1=> "Not Published ", 2=> "Posts in Draft", 3=>"Posts Published (route available for viewing)");
foreach ($arr as $i => $value) {
if ($i==$pstatus){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}
}
echo '</select></td></tr></table><br><table>';
echo "<tr><td><b>Date</b></td><td><b>From</b></td><td><b>To</b></td><td><b>Distance</b></td></tr>";
$total_route=explode(",",$rw['totalroute']);
$r = "SELECT distinct day_id,route_date,start_id,end_id,distance,locks,flags FROM ".CANALPLAN_ROUTE_DAY." where blog_id=".$blog_id." and route_id=".$_POST["route_list"]." ORDER BY `route_date` ASC";
$r=mysql_query($r);

while($rw = mysql_fetch_array($r))
{
$splace="SELECT place_name from ".CANALPLAN_CODES." where canalplan_id='".$total_route[$rw['start_id']]."'";
$splace=mysql_query($splace);
$splace=mysql_fetch_row($splace);
echo "<tr><td>".date('d-M-Y',strtotime($rw['route_date']))."&nbsp;&nbsp;</td><td>".$splace[0]."&nbsp;&nbsp</td><td>";
if ($rw['day_id']<$duration){
echo "<select name=".$rw['day_id'].">";
for ($eplacecount=-50;$eplacecount<=50;$eplacecount+=1){
$eplace="SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id='".$total_route[$rw['end_id']+$eplacecount]."'";
$eplace=mysql_query($eplace);
$eplace=mysql_fetch_row($eplace);
if (isset($eplace[0])) {
echo "<option value=";
echo $rw['end_id']+$eplacecount;
if ($eplacecount==0){echo ' selected="yes"';}
echo ">".$eplace[0]."</option>";}
}
echo "</select>";
}
else {
$eplace="SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id='".$total_route[$rw['end_id']]."'";
$eplace=mysql_query($eplace);
$eplace=mysql_fetch_row($eplace);
echo $eplace[0];
}
echo "&nbsp;&nbsp</td><td>".format_distance($rw['distance'],$rw['locks'],$dformat,1);
$endlock="";
$eplace="SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id='".$total_route[$rw['end_id']]."'";
$eplace=mysql_query($eplace);
$eplace=mysql_fetch_row($eplace);
if (strpos($eplace[1],'L') !== false) {
$endlock=1;
}
if (strpos($eplace[1],'1') !== false) {
$endlock=1;
}
if (strpos($rw['flags'],'L') !== false) {
$endlockcheck="checked";
}
if ($endlock==1) {
echo "&nbsp;&nbsp<input type=checkbox name='endlock".$rw['day_id']."' ".$endlockcheck." > Stop after passing through lock </td><tr>";
$endlock="";
}
}
echo "</table>";
?>
<input type="hidden" name="_submit_check" value="2"/>
<input type="hidden" name="route_list" id="route_list" value=<?php echo $_POST["route_list"]; ?> />
<input type="hidden" name="duration" id="duration" value=<?php echo $duration; ?> />
<p class="submit"> <input type="submit"  value="Recalculate and Save Changes" /> &nbsp;&nbsp;
<input type="submit" name="delete" value="Delete This Route (cannot be undone)" />
<input type="submit" name="OK" value="OK, I'm happy with that" /></p>

</form>

<?php
}
}

if (!isset($_POST["route_list"])) { 

?>

<h3>Step 1 : Select A route to Manage</h3>

<?php
$r = "SELECT distinct route_id,title,start_date FROM ".CANALPLAN_ROUTES." where blog_id=".$blog_id."  ORDER BY `start_date` ASC";
$r=mysql_query($r);

if (mysql_num_rows($r) > 0) {
?>

<br>
<form action="" name="flid" id="fav_list" method="post">
<table><tr><th>Available Routes</th><th></th></tr>
<tr><td></tr>
</table>

<?php
echo '<select id="route_list" name="route_list" >';
while($rw = mysql_fetch_array($r))
{
  echo '<option value="'.$rw['route_id'].'">'.$rw['title'].'  ( '.$rw['start_date'].' )</option>';
}
?>
</select>

<br>

<input type="hidden" name="_submit_check" value="1"/>
 <div align=left> <p class="submit"> <input type="submit"  value="Manage Route" /></p></div>
</form>

</div>

<?php

} else {
print "You don't seem to have any routes to manage. Please <a href='?page=canalplan-ac/admin-pages/cp-import_route.php'>import</a> a route first";
}
}
?>
