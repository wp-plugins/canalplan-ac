<?php
/*
Extension Name: Canalplan Manage Route
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 3.0
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
global $blog_id,$wpdb,$reimported;
if (!isset($_POST["route_list"] )) $_POST["route_list"] =-1;
$cpsession="0";
if (isset($_GET['cpsessionid'])) $cpsession=$_GET['cpsessionid'];
if (isset($_GET['cpsessionid']) && !isset($_POST["route_list"])) { $cpsession=$_GET['cpsessionid'];unset($_GET['cpsessionid']); unset($_POST["_submit_check"]); $i=1;}
if ($cpsession!="0"){
	$reimported=0;
	# for Durations we need to load the value of jdata['value'] into jdata['name']
	$cptable='durations';
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

	$cptable='miscellaneous';
	$url=CANALPLAN_URL."api.cgi?session=".$cpsession."&mode=table&table=".$cptable;
	$handle = fopen ($url , 'r');
			while (($data = fgets($handle)) !== FALSE)
			{
	$miscdata=json_decode($data, true);;
	}
	fclose($handle);
	if($miscdata[0]['name']=='remote') {
		$reloaded= explode(',',$miscdata[0]['value']);
		$reimported=$reloaded[0];
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
	while (($data = fgets($handle)) !== FALSE) {
		$jdata=json_decode($data, true);
	}
	fclose($handle);
	foreach ($jdata as $jsondata){
		if(!isset($route)) {
			$route[]=$jsondata['place1'];
		}
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

	$sql=$wpdb->prepare("select count(*) as duration from ".CANALPLAN_ROUTE_DAY." where route_id=%d and blog_id=%d",$reimported,$blog_id);
	$r=$wpdb->get_results($sql,ARRAY_A);


	if ($duration!=$r[0]['duration']) {
		echo "<b> ERROR : </b>Route durations do not match. You are attempting to import a ".$duration." day trip into a ".$r[0]['duration']." day trip blog. <br /> Please try again <br />";
		$_POST["route_list"]=$reimported;
		$_POST['_submit_check']='yes';
		$reimported=0;
	}
	else
	{
	# OK route lengths match so what we need to do is replace the existing data for this route with the new route
	$sql=$wpdb->prepare("Update ".CANALPLAN_ROUTES." set totalroute=%s, total_distance=%d, total_locks=%d where blog_id=%d and route_id=%d",$routestring,$totaldistance,$totallocks,$blog_id,$reimported);
	//print $sql."<br />";
	$r=$wpdb->query($sql);
	# Now rebuild the day totals.
	for ( $dc = 0; $dc < $duration; $dc += 1) {
		$dc2=$dc+1;
		$route=explode(",",$routestring);
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
		$sql=$wpdb->prepare("update ".CANALPLAN_ROUTE_DAY." set start_id=%d, end_id=%d, distance=%d, `locks`=%d where route_id=%d and blog_id=%d and day_id=%d",$first,$last,$newdistance,$newlocks,$reimported,$blog_id,$dc2);
		//print $sql."<br />";
		$r=mysql_query($sql);
		}

	$_POST["route_list"]=$reimported;
	}
	$_POST['_submit_check']='yes';
}


if(isset($_POST['_submit_check'])) {
	$subcheck=$_POST['_submit_check'];
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
		//unset($_POST["route_list"]);
		$_POST["_submit_check"]=99;
	}
	if ($_POST['def_delete']){
		$sql =$wpdb->prepare("select post_id from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and route_id=%s",$blog_id,$_POST["route_list"]);
		$r=$wpdb->get_results($sql,ARRAY_N);
		foreach($r as $row) {
			echo "Deleting Post : ".$row[0]."<br />";
			wp_delete_post($row[0]);
		}
		echo "<b>NOTE : </b> If you created a route summary post for this trip you will need to manually delete it ";
		$sql=$wpdb->prepare("delete from ".CANALPLAN_ROUTES." where blog_id=%d and route_id=%d",$blog_id,$_POST["route_list"]);
		$r=$wpdb->query($sql);
		$sql=$wpdb->prepare("delete from ".CANALPLAN_ROUTE_DAY."  where blog_id=%d and route_id=%d",$blog_id,$_POST["route_list"]);
		$r=$wpdb->query($sql);
		unset($_POST["route_list"]);
		unset($_POST["_submit_check"]);
	}
	if ($_POST['_submit_check']==2) {
		$sql=$wpdb->prepare("Update ".CANALPLAN_ROUTES." set title=%s, description=%s, uom=%s, status=%s where blog_id=%d and route_id=%d",stripslashes($_POST['rtitle']),stripslashes($_POST['rdesc']),$_POST['dfsel'],$_POST['routestatus'],$blog_id,$_POST["route_list"]);
		$dformat=$_POST['dfsel'];
		$res2 = $wpdb->query($sql);
		$eplacelength=$_POST['duration'];
		for ($eplacecount=1;$eplacecount<=$eplacelength;$eplacecount+=1){
			$elockno=$eplacecount-1;
			$elock='endlock'.$elockno;
			$elockno2=$eplacecount;
			$elock2='endlock'.$elockno2;
			if(!isset($_POST[$elock])){$_POST[$elock]=0;}
			if(!isset($_POST[$elock2])){$_POST[$elock2]=0;}
			if ($eplacecount<$eplacelength) {
				$sql=$wpdb->prepare("Update ".CANALPLAN_ROUTE_DAY." set end_id=%d where blog_id=%d and route_id=%d and day_id=%d",$_POST[$eplacecount],$blog_id,$_POST["route_list"],$eplacecount);
				$res2 = $wpdb->query($sql);
			}
			if ($eplacecount>1) {
				$sql=$wpdb->prepare("Update ".CANALPLAN_ROUTE_DAY." set start_id=%d where blog_id=%d and route_id=%d and day_id=%d",$_POST[$eplacecount-1],$blog_id,$_POST["route_list"],$eplacecount);
				$res2 = $wpdb->query($sql);
			}
			if ($_POST[$elock2]=='on') {
				$sql=$wpdb->prepare("update ".CANALPLAN_ROUTE_DAY." set flags='L' where blog_id=%d and route_id=%d and day_id=%d",$blog_id,$_POST["route_list"],$eplacecount);
				$res2 = $wpdb->query($sql);
			}
			else
			{
				$sql=$wpdb->prepare("update ".CANALPLAN_ROUTE_DAY." set flags='' where blog_id=%d and route_id=%d and day_id=%d",$blog_id,$_POST["route_list"],$eplacecount);
				$res2 = $wpdb->query($sql);
			}
			$sql=$wpdb->prepare("Select totalroute from ".CANALPLAN_ROUTES." where blog_id=%d and route_id=%d",$blog_id,$_POST["route_list"]);
			$r=$wpdb->get_results($sql,ARRAY_A);
			$totalroute=$r[0]["totalroute"];
			$sql=$wpdb->prepare("Select start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and route_id=%d and day_id=%d",$blog_id,$_POST["route_list"],$eplacecount);
			$r=$wpdb->get_results($sql,ARRAY_A);
			$rw=$r[0];
			$route=explode(",",$totalroute);
			$dayroute=array_slice($route,$rw['start_id'],( $rw['end_id']-$rw['start_id'])+1);
			$newlocks=0;
			$newdistance=0;
			for ($placeindex=1;$placeindex<count($dayroute);$placeindex+=1){
				$p1=$dayroute[$placeindex];
				$p2=$dayroute[$placeindex-1];
				 $sql=$wpdb->prepare("select metres,locks from ".CANALPLAN_LINK." where (place1=%s and place2=%s) or  (place1=%s and place2=%s )",$p1,$p2,$p2,$p1);
				$r=$wpdb->get_results($sql,ARRAY_A);
				if(count($r)>0) $rw=$r[0];
				//echo $p1.' to '.$p2." : ".$rw['metres'].' - '.$rw['locks'],"<br />";
				$newlocks=$newlocks+$rw['locks'];
				$newdistance=$newdistance+$rw['metres'];
			}
			for ($placeindex=0;$placeindex<count($dayroute);$placeindex+=1){
				$x=$dayroute["$placeindex"];
				$sql=$wpdb->prepare("select attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$x);
				$r=$wpdb->get_results($sql,ARRAY_A);
				$rw=$r[0];
						if(!isset($_POST[$elock])){$_POST[$elock]=0;}
		if(!isset($_POST[$elock2])){$_POST[$elock2]=0;}
				if (strpos($rw['attributes'],'L') !== false) {
					#echo "we have a lock at ".$x." - ".$rw['attributes']."<br>";
					if ($_POST[$elock]=='on' and $placeindex==0) {
						$newlocks=$newlocks;} elseif ($_POST[$elock2]!='on' and $placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+1;}
				}
				if (strpos($rw['attributes'],'2') !== false) {
					#echo "we have 2 locks at ".$x." - ".$rw['attributes']."<br>";
					if ($_POST[$elock]=='on' and $placeindex==0) {$newlocks=$newlocks;} elseif ($_POST[$elock2]!='on' and $placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+2;}
				}
				if(!isset($rw['locks'])){$rw['locks']=0;}
				$newlocks=$newlocks+$rw['locks'];
			}
			$sql=$wpdb->prepare("update ".CANALPLAN_ROUTE_DAY." set distance=%d , locks=%d where blog_id=%d and route_id=%d and day_id=%d",$newdistance,$newlocks,$blog_id,$_POST["route_list"],$eplacecount);
			$r=$wpdb->query($sql);
		}
	}
if (!isset($_POST["route_list"] )) $_POST["route_list"] =-1;
if ($_POST["route_list"] >=1  && !isset($_POST['delete'])) {
?>
<h3>Step 2 : Change the route details</h3>
<?php
$r =  $wpdb->prepare("SELECT distinct title,description,totalroute,duration,uom,status FROM ".CANALPLAN_ROUTES." where blog_id=%d and route_id=%d ORDER BY `start_date` ASC",$blog_id,$_POST["route_list"]);
$r = $wpdb->get_results($r,ARRAY_A);
$rw=$r[0];
$duration=$rw['duration'];
$dformat=$rw['uom'];
$pstatus=$rw['status'];
echo '<form action="" name="distform" id="dist_form" method="post"> <table>
<tr><td> Route Title: </td><td><input type="text" name="rtitle" value="'.stripslashes($rw['title']).'" size=100/> </td></tr><tr><td> Route Description :</td><td> <input type="text" name="rdesc" value="'.stripslashes($rw['description']).'" size=100 </></td></tr><tr><td> Distance Format: </td><td><select id="DFSelect" name="dfsel">';
$arr = array('k'=> "Decimal Kilometres (3.8 kilometres)", 'M' => "Kilometres and Metres (3 kilometres and 798 metres) ", 'm'=>"Decimal miles (2.3 miles)", 'y'=>"Miles and Yards (2 miles and 634 yards) ",'f'=>"Miles and Furlongs (  2 miles , 2 &#190; flg )");
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
echo "<tr><td><b>Date</b></td><td><b>From</b></td><td><b>To</b></td><td><b>Distance</b></td>";
if ($reimported >=1) {
	echo "<td><b>&nbsp;&nbsp;&nbsp;Pre-Reload Stop location</b></td>"; }
echo"</tr>";
$total_route=explode(",",$rw['totalroute']);
$r = $wpdb->prepare("SELECT distinct day_id,route_date,start_id,end_id,distance,locks,flags FROM ".CANALPLAN_ROUTE_DAY." where blog_id=%d and route_id=%d ORDER BY `route_date` ASC",$blog_id,$_POST["route_list"]);
$r = $wpdb->get_results($r,ARRAY_A);
$stopplaces="";
foreach($r as $rw) {
	$stopplaces.=",".$total_route[$rw['end_id']];
	$splace=$wpdb->prepare("SELECT place_name from ".CANALPLAN_CODES." where canalplan_id=%s",$total_route[$rw['start_id']]);
	$splace=$wpdb->get_row($splace,ARRAY_N);
	echo "<tr><td>".date('d-M-Y',strtotime($rw['route_date']))."&nbsp;&nbsp;</td><td>".$splace[0]."&nbsp;&nbsp</td><td>";
	if ($rw['day_id']<$duration){
		echo "<select name=".$rw['day_id'].">";
		for ($eplacecount=-50;$eplacecount<=50;$eplacecount+=1){
			$eplace=$wpdb->prepare("SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$total_route[$rw['end_id']+$eplacecount]);
			$eplace=$wpdb->get_row($eplace,ARRAY_N);
			if (isset($eplace[0])) {
				echo "<option value=";
				echo $rw['end_id']+$eplacecount;
				if ($eplacecount==0){echo ' selected="yes"';}
				echo ">".$eplace[0]."</option>";
			}
		}
	}
	else {
		$eplace=$wpdb->prepare("SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$total_route[$rw['end_id']]);
		$eplace=$wpdb->get_row($eplace,ARRAY_N);
		echo $eplace[0];
	}
	echo "&nbsp;&nbsp</td><td>".format_distance($rw['distance'],$rw['locks'],$dformat,1);
	$endlock="";
	$eplace=$wpdb->prepare("SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$total_route[$rw['end_id']]);
	$eplace=$wpdb->get_row($eplace,ARRAY_N);

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
		echo "&nbsp;&nbsp<input type=checkbox name='endlock".$rw['day_id']."' ".$endlockcheck." > Stop after passing through lock </td>";
		$endlock="";
		}
	else {echo "</td>";}
	if ($reimported >=1) {
		$oldeplace=$wpdb->prepare("SELECT place_name,attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$reloaded[$rw['day_id']]);
		$oldeplace=$wpdb->get_row($oldeplace,ARRAY_N);
		echo "<td>&nbsp;&nbsp;&nbsp;".$oldeplace[0]."</td>";
	}
	echo"</tr>";
	echo "</tr>";
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
<form action="<? echo CANALPLAN_URL ; ?>api.cgi" method="get">
<input type="hidden" name="mode" value="blog"/>
<input type="hidden" name="set" id="set" value=<?php echo $_POST["route_list"].$stopplaces; ?> />
 <?php  echo '<input type="hidden" name=startat value="'.$total_route[0].'" />';  ?>
<p class="submit"> <input type="submit"  value="Go To CanalPlan AC And rebuild route" /> </p>
</form>
<?php
}
}

if ($_POST["route_list"] <=0 && !isset($_POST['delete']) ) {
?>
<h3>Step 1 : Select A route to Manage</h3>

<?php
$r = $wpdb->prepare("SELECT distinct route_id,title,start_date FROM ".CANALPLAN_ROUTES." where blog_id=%d  ORDER BY `start_date` ASC",$blog_id);
$r=$wpdb->get_results($r,ARRAY_A);
if (count($r) > 0) {
?>

<br>
<form action="" name="flid" id="fav_list" method="post">
<table><tr><th>Available Routes</th><th></th></tr>
<tr><td></tr>
</table>
<select id="route_list" name="route_list" >
<?php
foreach($r as $rw)
{
  echo '<option value="'.$rw['route_id'].'">'.stripslashes($rw['title']).'  ( '.$rw['start_date'].' )</option>';
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