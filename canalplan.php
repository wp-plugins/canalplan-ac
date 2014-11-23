<?php
/*
Plugin Name: CanalPlan Integration
Plugin URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Description: Provides features to integrate your blog with <a href="http://www.canalplan.eu">Canalplan AC</a> - the Canal Route Planner.
Version: 3.17
Author: Steve Atty
Author URI: http://blogs.canalplan.org.uk/steve/
 *
 *
 * Copyright 2011 - 2014 Steve Atty (email : posty@tty.org.uk)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if (file_exists(WP_CONTENT_DIR."/uploads/canalplan_multisite.php")){
	@include(WP_CONTENT_DIR."/uploads/canalplan_multisite.php");
}
define ('CANALPLAN_BASE','http://canalplan.org.uk');
define ('CANALPLAN_URL',CANALPLAN_BASE.'/cgi-bin/');
define ('CANALPLAN_GAZ_URL',CANALPLAN_BASE.'/gazetteer/');
define ('CANALPLAN_WAT_URL',CANALPLAN_BASE.'/waterway/');
define ('CANALPLAN_FEA_URL',CANALPLAN_BASE.'/feature/');
define ('CANALPLAN_MAX_POST_PROCESS',100);
define('CANALPLAN_CODE_RELEASE','3.17 r00');
//error_reporting (E_ALL | E_NOTICE | E_STRICT | E_DEPRECATED);

global $table_prefix, $wp_version,$wpdb,$db_prefix,$canalplan_run_canal_link_maps,$canalplan_run_canal_route_maps,$canalplan_run_canal_place_maps;
$canalplan_run = array();
# Determine the right table prefix to use
$cp_table_prefix=$wpdb->base_prefix;
if (isset ($db_prefix) ) { $cp_table_prefix=$db_prefix;}

define ('CANALPLAN_OPTIONS',$cp_table_prefix.'canalplan_options');
define ('CANALPLAN_ALIASES',$cp_table_prefix.'canalplan_aliases');
define ('CANALPLAN_CODES',$cp_table_prefix.'canalplan_codes');
define ('CANALPLAN_FAVOURITES',$cp_table_prefix.'canalplan_favourites');
define ('CANALPLAN_LINK',$cp_table_prefix.'canalplan_link');
define ('CANALPLAN_CANALS',$cp_table_prefix.'canalplan_canals');
define ('CANALPLAN_ROUTES',$cp_table_prefix.'canalplan_routes');
define ('CANALPLAN_POLYLINES',$cp_table_prefix.'canalplan_polylines');
define ('CANALPLAN_ROUTE_DAY',$cp_table_prefix.'canalplan_route_day');

function ascii_encode($numb) {
        //echo $numb . "<br>";
        $numb = $numb << 1;
        if ($numb < 0) {
                $numb = ~$numb;
        }
        return ascii_encode_helper($numb);
}

function ascii_encode_helper($numb) {
        $string = "";
        $count = 0;
        while ($numb >= 0x20) {
                $count++;
                $string .= (pack("C",(0x20 | ($numb & 0x1f)) + 63));
                $numb = $numb >> 5;
        }
        $string .= pack("C", $numb+63);
        return str_replace("\\","\\\\",$string);
}

function format_distance($distance,$locks,$format,$short){
	$totalfeet=($distance * 3.2808399);
	$wholemiles=floor((($totalfeet) / 5280));
	$wholekm=floor($distance/1000);
	$decmiles=round((($distance * 3.2808399) / 5280),2);
	$remmeters=((($distance/1000) - floor($distance/1000))*1000);
	$remyards=ceil((((($totalfeet) / 5280) - $wholemiles)*1760));
	if ($remyards==1760){$remyards=0;}
	$remfurls=round((((($totalfeet) / 5280) - $wholemiles)*8),2);
	$wholefurls=floor($remfurls);
	$fractfurls=$remfurls-$wholefurls;
	$furltext=", ";
	$fracttext="";
	$miletext=" miles";
	$yfractext="";
	$ytext=" yards";
	$ktext=" kilometres";
	$mfractext="";
	$mtext=" metres";
	if ($wholemiles==1){$miletext=" mile";}
	if ($remyards>0) {$yfractext=", ";}
	if ($remyards==0) {$remyards=""; $ytext="";}
	if ($remyards==1) { $ytext=" yard";}

	if ($wholekm==1){$ktext=" kilometer";}
	if ($remmeters>0) {$mfractext=", ";}
	if ($remmeters==0) {$remmeters=""; $mtext="";}
	if ($remmeters==1) { $mtext=" meter";}

	if ($wholefurls==0){$wholefurls="";}
	if ($fractfurls<0.25) {$fractfurls=0;}
	if (($fractfurls >= 0.25) && ($fractfurls<0.5)) {$fracttext="&#188;";}
	if (($fractfurls >= 0.5) && ($fractfurls<0.75)) {$fracttext="&#189;";}
	if (($fractfurls >=0.75) && ($fractfurls<1)) {$fracttext="&#190;";}
	$fracttext.=" flg";
	if ($wholefurls==8) {$wholemiles=$wholemiles+1; $wholefurls=0;}
	if (($fractfurls==0) && ($wholefurls==0)){$fracttext=""; $furltext="";}
	if ($wholefurls==0) {$wholefurls="";}
    $dist_text="";
	$furltext.=$wholefurls.$fracttext;
	if($short!=1) $dist_text="a distance of ";
	if($short==3) $dist_text="A total distance of ";
	switch ($format) {
		case "k":
			$dist_text.= round($distance/1000,2).$ktext;
		break;
		case "M":
			$dist_text.= $wholekm.$ktext.$mfractext.$remmeters.$mtext;
		break;
		case "m":
			$dist_text.= $decmiles.$miletext;
		break;
		case "y":
			$dist_text.= $wholemiles.$miletext.$yfractext.$remyards.$ytext;
		break;
		default:
			$dist_text.= $wholemiles.$miletext.$furltext;
	}
	if ($locks ==1) {$dist_text.=" and 1 lock";}
	if ($locks >1) {$dist_text.=" and ".$locks." locks";}
	return $dist_text;
}


function recalculate_route_day ($blog_id,$route_id,$day_id) {
	global $wpdb;
	//echo "<br /> Doing $day_id <br />";
	$sql=$wpdb->prepare("Select totalroute from ".CANALPLAN_ROUTES." where blog_id=%d and route_id=%d",$blog_id,$route_id);
	$r=$wpdb->get_results($sql,ARRAY_A);
	$totalroute=$r[0]["totalroute"];
	$sql=$wpdb->prepare("Select start_id,end_id,flags from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and route_id=%d and day_id=%d",$blog_id,$route_id,$day_id);
	$r=$wpdb->get_results($sql,ARRAY_A);
	$rw=$r[0];
	$route=explode(",",$totalroute);
	$dayroute=array_slice($route,$rw['start_id'],( $rw['end_id']-$rw['start_id'])+1);
	$stopafterlocktoday=$rw['flags'];
	$stopafterlockyesterday='X';
	$newlocks=0;
	$newdistance=0;
	if ($day_id >1)
	{
		$sql=$wpdb->prepare("Select start_id,end_id,flags from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and route_id=%d and day_id=%d",$blog_id,$route_id,$day_id-1);
	$r=$wpdb->get_results($sql,ARRAY_A);
	$rw=$r[0];
	$stopafterlockyesterday=$rw['flags'];
	}
	// var_dump($stopafterlocktoday);
	// var_dump($stopafterlockyesterday);
	if(strlen($stopafterlocktoday)==0) $stopafterlocktoday='X';
	if(strlen($stopafterlockyesterday)==0) $stopafterlockyesterday='X';
	// var_dump($stopafterlocktoday);
	// var_dump($stopafterlockyesterday);
	for ($placeindex=1;$placeindex<count($dayroute);$placeindex+=1){
		$p1=$dayroute[$placeindex];
		$p2=$dayroute[$placeindex-1];
		 $sql=$wpdb->prepare("select distinct metres,locks from ".CANALPLAN_LINK." where (place1=%s and place2=%s) or  (place1=%s and place2=%s )",$p1,$p2,$p2,$p1);
		$r=$wpdb->get_results($sql,ARRAY_A);
		if(count($r)>0) $rw=$r[0];
		if (is_null($rw['locks'])) $rw['locks']=0;
		if (is_null($rw['metres'])) $rw['metres']=10;
		$newlocks=$newlocks+$rw['locks'];
		$newdistance=$newdistance+$rw['metres'];
	}
	for ($placeindex=0;$placeindex<count($dayroute);$placeindex+=1){
		$x=$dayroute["$placeindex"];
		$sql=$wpdb->prepare("select attributes from ".CANALPLAN_CODES." where canalplan_id=%s",$x);
		$r=$wpdb->get_results($sql,ARRAY_A);
		$rw=$r[0];
	//	if(!isset($_POST[$elock])){$_POST[$elock]=0;}
	//	if(!isset($_POST[$elock2])){$_POST[$elock2]=0;}
		if (strpos($rw['attributes'],'L') !== false) {
		//	echo "we have a lock at ".$x." - ".$rw['attributes']."<br>";
		//	if ($stopafterlockyesterday =='L' and $placeindex==0) echo "Did this lock last thing yesterday";
			if ($stopafterlockyesterday =='L' and $placeindex==0) $newlocks=$newlocks;
		//	if ($stopafterlockyesterday =='X' and $placeindex==0) echo "Doing this lock first thing today";
			if ($stopafterlockyesterday =='X' and $placeindex==0) $newlocks=$newlocks+1;
		//	if ($stopafterlocktoday=='X' and $placeindex==count($dayroute)-1) echo "Doing this lock first thing tomorrow";
			if ($stopafterlocktoday=='X' and $placeindex==count($dayroute)-1) $newlocks=$newlocks;
		//	if ($stopafterlocktoday=='L' and $placeindex==count($dayroute)-1) echo "Doing this lock last thing today";
			if ($stopafterlocktoday=='L' and $placeindex==count($dayroute)-1) $newlocks=$newlocks+1;
			if ($placeindex > 0 and $placeindex<count($dayroute)-1 )  $newlocks=$newlocks+1;
		}
		preg_match_all('!\d+!', $rw['attributes'], $matches);
		$lock_count=$matches[0][0];

		if (!is_null($lock_count)) {
		//	echo "we have an extra $lock_count locks at ".$x." - ".$rw['attributes']."<br>";
		//	if ($stopafterlockyesterday =='L' and $placeindex==0) echo "Did this lock last thing yesterday";
			if ($stopafterlockyesterday =='L' and $placeindex==0) $newlocks=$newlocks;
		//	if ($stopafterlockyesterday =='X' and $placeindex==0) echo "Doing this lock first thing today";
			if ($stopafterlockyesterday =='X' and $placeindex==0) $newlocks=$newlocks+$lock_count;
		//	if ($stopafterlocktoday=='X' and $placeindex==count($dayroute)-1) echo "Doing this lock first thing tomorrow";
			if ($stopafterlocktoday=='X' and $placeindex==count($dayroute)-1) $newlocks=$newlocks;
		//	if ($stopafterlocktoday=='L' and $placeindex==count($dayroute)-1) echo "Doing this lock last thing today";
			if ($stopafterlocktoday=='L' and $placeindex==count($dayroute)-1) $newlocks=$newlocks+$lock_count;
			if ($placeindex > 0 and $placeindex<count($dayroute)-1 )  $newlocks=$newlocks+$lock_count;
		}
	/*
		if (strpos($rw['attributes'],'3') !== false) {
		#echo "we have 2 locks at ".$x." - ".$rw['attributes']."<br>";
		if ($_POST[$elock]=='on' and $placeindex==0) {$newlocks=$newlocks;} elseif ($_POST[$elock2]!='on' and $placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+3;}
	}
		if (strpos($rw['attributes'],'4') !== false) {
		#echo "we have 2 locks at ".$x." - ".$rw['attributes']."<br>";
		if ($_POST[$elock]=='on' and $placeindex==0) {$newlocks=$newlocks;} elseif ($_POST[$elock2]!='on' and $placeindex==count($dayroute)-1) {$newlocks=$newlocks;}  else {$newlocks=$newlocks+4;}
	}
	*/

		if(!isset($rw['locks'])){$rw['locks']=0;}
		$newlocks=$newlocks+$rw['locks'];
	}
	$sql=$wpdb->prepare("update ".CANALPLAN_ROUTE_DAY." set distance=%d , locks=%d where blog_id=%d and route_id=%d and day_id=%d",$newdistance,$newlocks,$blog_id,$route_id,$day_id);
	//echo "Updating ...";
	$r=$wpdb->query($sql);
}

function recalculate_route($blog_id,$route_id){
	global $wpdb;
	$sql=$wpdb->prepare("select duration from ".CANALPLAN_ROUTES." where blog_id=%d and route_id=%d ",$blog_id,$route_id);
	$duration=$wpdb->get_row($sql,ARRAY_N);
  //  var_dump($duration);
	for ($daycount=1;$daycount<=$duration;$daycount+=1){
  //  recalculate_route_day ($blog_id,$route_id,$daycount);
	}
}
function canalplan_add_custom_box() {
    add_meta_box( 'canalplan_sectionid', __( 'CanalPlan Tags', 'canalplan_textdomain' ),
                'canalplan_inner_custom_box', 'post', 'advanced' );
    add_meta_box( 'canalplan_sectionid', __( 'CanalPlan Tags', 'canalplan_textdomain' ),
                'canalplan_inner_custom_box', 'page', 'advanced' );
}

/* Prints the inner fields for the custom post/page section */
function canalplan_inner_custom_box() {
 	echo '<input type="hidden" name="canalplan_noncename" id="canalplan_noncename" value="' .
    	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	global $wpdb,$blog_id;
	echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
	echo '<script type="text/javascript"> var wpcontent="'.plugins_url().'"</script>';
	echo '<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplanfunctions.js" DEFER></script>';
	echo '<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplan_actb.js"></script>';
	echo "Insert : ";
	$sql=$wpdb->prepare("SELECT place_name FROM ".CANALPLAN_FAVOURITES." where blog_id=%d order by place_order asc",$blog_id);
	$blog_favourites = $wpdb->get_results($sql);
	if (count($blog_favourites)>0 ){
		print '<select name="blogfav" onchange="CanalPlanID.value=blogfav.options[blogfav.selectedIndex].value">';
		print '<option value="" selected>Select Favourite</option>';
		foreach ($blog_favourites as $fav) {
			print '<option value="'.$fav->place_name.'">'.$fav->place_name.'</option>';
	  	}
		print "</select>";
	}
	print ' <input type="text" ID="CanalPlanID" align="LEFT" size="50" maxlength="100"/> as';
	print '  <select name="tagtype" ID="tagtypeID"> <option value="CP" selected>Gazetteer Tag</option> <option value="CPGM">Google Map Tag</option> </select>';
	print ' <INPUT TYPE="button" name="CPsub" VALUE="Insert tag"  onclick="getCanalPlan(CanalPlanID.value);"/>';
	echo "<br />Insert : ";
		$sql=$wpdb->prepare("SELECT route_id,title FROM ".CANALPLAN_ROUTES." where blog_id=%d order by route_id desc",$blog_id);
	$blog_routes = $wpdb->get_results($sql);
	if (count($blog_favourites)>0 ){
		print '<select name="blogroute" onchange="CanalRouteID.value=blogroute.options[blogroute.selectedIndex].text">';
		print '<option value="" selected>Select Route</option>';
		foreach ($blog_routes as $route) {
			print '<option value="'.$route->route_id.'" name="'.$route->title.'">'.$route->title.'</option>';
	  	}
		print "</select>";
	}
	print ' <input type="text" disabled="disabled" ID="CanalRouteID" align="LEFT" size="50" maxlength="100"/> as';
	print '  <select name="routetagtype" ID="routetagtypeID"> <option value="CPTS" selected>Trip Statistics </option> <option value="CPTD" selected>Trip Details (Overnight Stops) </option> <option value="CPTM">Trip Map</option> <option value="CPTO">Trip Map (Overnight Stops)</option> <option value="CPTL">List of Links to Trip Blog Posts</option></select>';
	print ' <INPUT TYPE="button" name="CPsub2" VALUE="Insert tag"  onclick="getCanalRoute(blogroute.options[blogroute.selectedIndex].value);"/>';
	print '<script>canalplan_actb(document.getElementById("CanalPlanID"),new Array());</script>';
}

function canal_init() {

	add_filter('the_content',  'canal_stats');
	add_filter('the_content',  'canal_trip_maps');
	add_filter('the_content',  'canal_trip_stats');
	add_filter('the_content',  'canal_route_maps');
	add_filter('the_content',  'canal_place_maps');
	add_filter('the_content',  'canal_link_maps');
	add_filter('the_content',  'canal_linkify');
	add_filter('the_content',  'canal_blogroute_insert');
	add_filter('network_the_content',  'canal_stats');
	add_filter('network_the_content',  'canal_trip_maps');
	add_filter('network_the_content',  'canal_trip_stats');
	add_filter('network_the_content',  'canal_route_maps');
	add_filter('network_the_content',  'canal_place_maps');
	add_filter('network_the_content',  'canal_link_maps');
	add_filter('network_the_content',  'canal_linkify');
	add_filter('network_the_content',  'canal_blogroute_insert');
    add_filter('the_content_feed',  'canal_stats');
	add_filter('the_content_feed',  'canal_trip_maps');
	add_filter('the_content_feed',  'canal_trip_stats');
	add_filter('the_content_feed',  'canal_route_maps');
	add_filter('the_content_feed',  'canal_place_maps');
	add_filter('the_content_feed',  'canal_link_maps');
	add_filter('the_content_feed',  'canal_linkify');
	add_filter('the_content_feed ',  'canal_blogroute_insert');
	add_filter('network_the_content_feed',  'canal_stats');
	add_filter('network_the_content_feed',  'canal_trip_maps');
	add_filter('network_the_content_feed',  'canal_trip_stats');
	add_filter('network_the_content_feed',  'canal_route_maps');
	add_filter('network_the_content_feed',  'canal_place_maps');
	add_filter('network_the_content_feed',  'canal_link_maps');
	add_filter('network_the_content_feed',  'canal_linkify');
	add_filter('network_the_content_feed ',  'canal_blogroute_insert');
	add_filter('the_excerpt',  'canal_stats');
	add_filter('the_excerpt',  'canal_trip_maps');
	add_filter('the_excerpt',  'canal_trip_stats');
	add_filter('the_excerpt',  'canal_route_maps');
	add_filter('the_excerpt',  'canal_place_maps');
	add_filter('the_excerpt',  'canal_link_maps');
	add_filter('the_excerpt',  'canal_linkify');
	add_filter('the_excerpt',  'canal_blogroute_insert');
	add_filter('the_excerpt_rss',  'canal_stats');
	add_filter('the_excerpt_rss',  'canal_trip_maps');
	add_filter('the_excerpt_rss',  'canal_trip_stats');
	add_filter('the_excerpt_rss',  'canal_route_maps');
	add_filter('the_excerpt_rss',  'canal_place_maps');
	add_filter('the_excerpt_rss',  'canal_link_maps');
	add_filter('the_excerpt_rss',  'canal_linkify');
	add_filter('the_excerpt_rss',  'canal_blogroute_insert');
	add_filter('network_the_excerpt_rss',  'canal_stats');
	add_filter('network_the_excerpt_rss',  'canal_trip_maps');
	add_filter('network_the_excerpt_rss',  'canal_trip_stats');
	add_filter('network_the_excerpt_rss',  'canal_route_maps');
	add_filter('network_the_excerpt_rss',  'canal_place_maps');
	add_filter('network_the_excerpt_rss',  'canal_link_maps');
	add_filter('network_the_excerpt_rss',  'canal_linkify');
	add_filter('network_the_excerpt_rss',  'canal_blogroute_insert');

	add_action('wp_head', 'canalplan_header');
	add_action('wp_footer', 'canalplan_footer');
   	global $dogooglemap;
   	$dogooglemap=0;
}

function canal_trip_maps($content,$mapblog_id=NULL,$post_id=NULL,$search='N') {
    global $wpdb,$post,$blog_id,$google_map_code,$dogooglemap,$canalplan_run_canal_route_maps;
    $tripdetail='N';
    $tripsumm='N';
    $pid=$post->ID;
    if (is_null($pid)) return $content;
    if (preg_match_all('/' . preg_quote('[[CPTM:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; $tripsumm='Y' ;}
    if (preg_match_all('/' . preg_quote('[[CPTO:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches2)) { $places_array2=$matches2[1]; $tripdetail='Y'; }
    if (preg_match_all('/' . preg_quote('[[CPTL:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches2)) { $places_array3=$matches2[1]; $triplink='Y'; }
	if($tripsumm=='Y'){
		$names = array();
		$links = array();
	//	$place_code=$places_array[0];
		foreach ($places_array as $place_code) {
			$names[] = "[[CPTM:" .$place_code . "]]";
			$links[] = canal_bloggedroute($place_code,"N");
		}
		$content = str_ireplace($names,$links , $content);
	}
	if($tripdetail=='Y'){
		$names = array();
		$links = array();
		//$place_code=$places_array2[0];
		foreach ($places_array2 as $place_code) {
			$names[] = "[[CPTO:" .$place_code . "]]";
			$links[] = canal_bloggedroute($place_code,"Y");
	}
		$content = str_ireplace($names,$links , $content);
	}
	if($triplink=='Y'){
       $format_type=array('B'=>'ul','N'=>'ol');
       foreach ($places_array3 as $place_code) {
		$x=explode(':',$place_code);
		$routeid=addslashes($x[0]);
		$format=strtoupper($x[1]);
		if (!in_array($format,array("B", "N"))) $format='N';
		$list_type=$format_type[$format];
		$sql="select id, post_title from ".$wpdb->posts." where id in (select post_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$wpdb->blogid." and  route_id=".$routeid." and post_id <> $pid order by day_id asc ) and post_status='publish' order by id asc";
		$res = $wpdb->get_results($sql,ARRAY_A);
		$blroute ="<$list_type>";
		foreach ($res as $row) {
			$link = get_blog_permalink( $blog_id, $row['id'] ) ;
			$blroute .="<li><a href=\"$link\" target=\"_new\">$row[post_title]</a> </li>";
		}
		$blroute .="</$list_type>";
		$links[]=$blroute;
		$names[] = "[[CPTL:" .$place_code . "]]";
	}
		$content = str_ireplace($names,$links , $content);
	}
	return $content;
}

function canal_trip_stats($content,$mapblog_id=NULL,$post_id=NULL,$search='N') {
    global $wpdb,$post,$blog_id,$google_map_code,$dogooglemap,$canalplan_run_canal_route_maps,$network_post;
    if (isset($network_post)) {
		$post_id=$network_post->ID;
		$mapblog_id=$network_post->BLOG_ID;
	}
	if (!isset($mapblog_id)){
		$mapblog_id=$blog_id;
	}
    $tripdetail='N';
    $tripsumm='N';
    if (preg_match_all('/' . preg_quote('[[CPTS:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1];  $tripsumm='Y';}
    if (preg_match_all('/' . preg_quote('[[CPTD:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches2)) { $places_array2=$matches2[1]; $tripdetail='Y'; }
	if($tripsumm=='Y'){
		$names = array();
		$links = array();
		foreach ($places_array as $place_code) {
			$sql=$wpdb->prepare("select totalroute,uom,total_distance,total_locks from ".CANALPLAN_ROUTES." cpr where cpr.route_id=%d and cpr.blog_id=%d",$place_code,$mapblog_id);
			$res1 = $wpdb->get_results($sql,ARRAY_A);
			$row1 = $res1[0];
			$dformat=$row1['uom'];
			$troute=explode(',',$row1['totalroute']);
			$startp=$troute[0];
			$endp=array_pop($troute);
			$sql=$wpdb->prepare("select distinct place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$startp,$mapblog_id,$startp,$startp,$mapblog_id);;
			$res2 = $wpdb->get_results($sql,ARRAY_A);
			$row2 = $res2[0];
			$sql=$wpdb->prepare("select distinct place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$endp,$mapblog_id,$endp,$endp,$mapblog_id);
			$res3 = $wpdb->get_results($sql,ARRAY_A);
			$row3 = $res3[0];
			$names[] = "[[CPTS:" .$place_code . "]]";
			$links[] = "From [[CP:".$row2['place_name']."|".$startp."]] to [[CP:".$row3['place_name']."|".$endp."]], ".format_distance($row1['total_distance'],$row1['total_locks'],$dformat,3).".";
		}
		$content = str_ireplace($names,$links , $content);
	}
	if($tripdetail=='Y'){
	$names2 = array();
	$links2 = array();
	foreach ($places_array2 as $place_code) {
		$sql=$wpdb->prepare("select totalroute,uom,total_distance,total_locks from ".CANALPLAN_ROUTES." cpr where cpr.route_id=%d and cpr.blog_id=%d",$place_code,$mapblog_id);
		$res1 = $wpdb->get_results($sql,ARRAY_A);
		$row1 = $res1[0];
		$dformat=$row1['uom'];
		$troute=explode(',',$row1['totalroute']);
		$sql=$wpdb->prepare("select distance,`locks`,start_id,end_id, day_id from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and  route_id=%d",$mapblog_id,$place_code);
		$res = $wpdb->get_results($sql,ARRAY_A);
		foreach($res as $dayresult){
			$startp=$troute[$dayresult['start_id']];
			$endp=$troute[$dayresult['end_id']];
			$sql=$wpdb->prepare("select distinct canalplan_id, place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select canalplan_id, place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$startp,$mapblog_id,$startp,$startp,$mapblog_id);
			$res2 = $wpdb->get_results($sql,ARRAY_A);
			$startplaces[] = $res2[0];
			$sql=$wpdb->prepare("select distinct canalplan_id, place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select canalplan_id, place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$endp,$mapblog_id,$endp,$endp,$mapblog_id);
			$res3 = $wpdb->get_results($sql,ARRAY_A);
			if ($dayresult['day_id']>=1 ) $endplaces[]  = $res3[0];
		}
		$endplace=array_pop($endplaces);
		$penultimateplace=array_pop($endplaces);
		$names[] = "[[CPTS:" .$place_code . "]]";
		$stat_text = "Starting at [[CP:".$startplaces[0]['place_name']."|".$startplaces[0]['canalplan_id']."]] and finishing at [[CP:".$endplace['place_name']."|".$endplace['canalplan_id']." ]] with overnight stops at :";
	//	var_dump($endplaces);
		foreach ($endplaces as $nightplace) {
			$stat_text.=" [[CP:".$nightplace['place_name']."|".$nightplace['canalplan_id']."]],";
		}
		rtrim($stat_text, ",");
		$stat_text.=" and [[CP:".$penultimateplace['place_name']."|".$penultimateplace['canalplan_id']."]].";
		$stat_text.= " ".format_distance($row1['total_distance'],$row1['total_locks'],$dformat,3).".";
		$names2[] = "[[CPTD:" .$place_code . "]]";
		$links2[]= $stat_text;
	}
	$content = str_ireplace($names2,$links2 , $content);
	}
	return $content;
}

function canal_route_maps($content,$mapblog_id=NULL,$post_id=NULL,$search='N') {
    global $wpdb,$post,$blog_id,$google_map_code,$dogooglemap,$canalplan_run_canal_route_maps;
	// First we check the content for tags:
	if (preg_match_all('/' . preg_quote('[[CPRM') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[0]; }
	// If the array is empty then we've no maps so don't do anything!
	if (!isset($places_array)) {return $content;}
	if (count($places_array)==0) {return $content;}
	if(!isset($canalplan_run_canal_route_maps[$post->ID])) {$canalplan_run_canal_route_maps[$post->ID]=1;} else {
		$canalplan_run_canal_route_maps[$post->ID]=$canalplan_run_canal_route_maps[$post->ID]+1;
	}
    if (isset($mapblog_id)) {} else { $mapblog_id=$blog_id;}
    if (isset($post_id)) {} else {$post_id=$post->ID;
    if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
	if ( get_query_var('feed') || $search=='Y' || is_feed() )  {
		$names = array();
		$links = array();
		foreach ($places_array as $place_code) {
			$words=explode(":",$place_code);
			$names[] = $place_code;
			$links[] ="<b>[ Google Route Map embedded here ]</b>" ;
		}
	return str_replace($names,$links , $content);
	}
	$google_map_code2='';
	//$mapstuff="<br />";
	$mapstuff="";
	if($canalplan_run_canal_route_maps[$post->ID]==1) {$dogooglemap=$dogooglemap+1;}
	$dogooglemap='CPRM'.$mapblog_id.'_'.$post->ID;
	$canalplan_options = get_option('canalplan_options');
	$post_id=$post->ID;
 	$sql=$wpdb->prepare("select distance,`locks`,start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and  post_id=%d",$mapblog_id,$post_id);
	$res = $wpdb->get_results($sql,ARRAY_A);
	$row = $res[0];
	$sql=$wpdb->prepare("select totalroute from ".CANALPLAN_ROUTES." cpr, ".CANALPLAN_ROUTE_DAY." crd where cpr.route_id= crd.route_id and cpr.blog_id=crd.blog_id and crd.blog_id=%d and  crd.post_id=%d",$mapblog_id,$post_id);
	//$res3 = $wpdb->get_results($sql,ARRAY_A) or trigger_error('Query failed: ' . $sql, E_USER_ERROR);
	$res3 = $wpdb->get_results($sql,ARRAY_A);
	$place_count=0;
	$row3 = $res3[0];
	$places=explode(",",$row3['totalroute']);
	$dayroute=array_slice($places,$row['start_id'], ($row['end_id'] - $row['start_id'])+1);
	$mid_point=round(count($dayroute)/2,0,PHP_ROUND_HALF_UP);
	$pointstring = "";
	$zoomstring = "";
	$lat = 0;
	$long = 0;
	$lpoint="";
	$lpointb1="";
	$x=3;
	$y=-1;
	$lastid=end($dayroute);
	$firstid=reset($dayroute);
	$turnaround="";
   	$maptype['S']="SATELLITE";
   	$maptype['R']="ROADMAP";
   	$maptype['T']="TERRAIN";
   	$maptype['H']="HYBRID";
	$options['zoom']=$canalplan_options["canalplan_rm_zoom"];
	$options['type']=$canalplan_options["canalplan_rm_type"];
	if (!isset($options['type'])) {$options['type']='H';}
	if (!isset($options['zoom'])) {$options['zoom']=9;}
	$options['lat']=53.4;
	$options['long']=-2.8;
	$options['height']=$canalplan_options["canalplan_rm_height"];
	$options['width']=$canalplan_options["canalplan_rm_width"];
	$options['rgb']=$canalplan_options["canalplan_rm_r_hex"].$canalplan_options["canalplan_rm_g_hex"].$canalplan_options["canalplan_rm_b_hex"];
	$options['brush']=$canalplan_options["canalplan_rm_weight"];
	$words=substr($matches[1][0],1);
	$opts=explode(",",$words);
	foreach ($opts as $opt) {
		 $opcode=explode("=",$opt);
		 if (count($opcode)>1) {$options[$opcode[0]]=strtoupper($opcode[1]);}
	}
	$mapstuff.= '<div id="map_canvas_'.$dogooglemap.'" style="width: '.$options['width'].'px; height: '.$options['height'].'px"></div>';
	foreach ($dayroute as $place) {
		$sql=$wpdb->prepare("select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id=%s",$place);
		$res =  $wpdb->get_results($sql,ARRAY_A);
		$row='';
		if (count($res)>0) {
			$row = $res[0];
		}
		if (count($row) > 2) {
		if($place_count==$mid_point) {
			$centre_lat=$row['lat'];
			$centre_long=$row['long'];
		}
		$place_count=$place_count+1;
		if ($place==$firstid){
			$firstname=addslashes($row['place_name']);
			$first_lat=$row['lat'];
			$first_long=$row['long'];
		}
		if ($place==$lastid){
			$lastname=addslashes($row['place_name']);
			$last_lat=$row['lat'];
			$last_long=$row['long'];
		}
		$points=$place.",".$row['lat'].",".$row['long'];
	     	$pointx = $row['lat'];
	        $pointy = $row['long'];;
	        $nlat = floor($pointx * 1e5);
	        $nlong = floor($pointy * 1e5);
	        $pointstring .= ascii_encode($nlat - $lat) . ascii_encode($nlong -  $long);
	        $zoomstring .= 'B';
	        $lat = $nlat;
	        $long = $nlong;
	        $cpoint=$row['place_name'].",".$row['lat'].",".$row['long'];
	        if ($cpoint==$lpointb1) {
			$lpoints=explode(",",$lpoint);
			$turnaround.='var marker_turn'.$dogooglemap.'_'.$x.' = new google.maps.Marker({ position: new google.maps.LatLng('.$lpoints[1].','.$lpoints[2].'), map: map'.$dogooglemap.',   title: "Turn Round here  : '.$lpoints[0].'" });';
			$turnaround.='iconFile = "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"; marker_turn'.$dogooglemap.'_'.$x.'.setIcon(iconFile) ; ';
		 	$x=$x+1;
		}
		$lpointb1=$lpoint;
		$y=$y+1;
		$lpoint=$cpoint;
		}
	}

	if ($firstid==$lastid) {
		$markertext='var marker_start'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$first_lat.','.$first_long.'), map: map'.$dogooglemap.',   title: "Start / Finish : '.$firstname.'"});';
		$markertext.='iconFile = "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png"; marker_start'.$dogooglemap.'.setIcon(iconFile) ; ';
	}
	else
	{
		$markertext='var marker_start'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$first_lat.','.$first_long.'), map: map'.$dogooglemap.',   title: "Start : '.$firstname.'" });';
		$markertext.='var marker_stop'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$last_lat.','.$last_long.'), map: map'.$dogooglemap.',  title: "Stop : '.$lastname.'" });';
		$markertext.='iconFile = "http://maps.google.com/mapfiles/ms/icons/green-dot.png"; marker_start'.$dogooglemap.'.setIcon(iconFile) ; ';
		$markertext.='iconFile = "http://maps.google.com/mapfiles/ms/icons/red-dot.png"; marker_stop'.$dogooglemap.'.setIcon(iconFile) ; ';
	}
	$google_map_code2.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$centre_lat.','.$centre_long.'),';
	$google_map_code2.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: true,';
	$google_map_code2.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
	$google_map_code2.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
	$google_map_code2.='  var polyOptions'.$dogooglemap.' = {strokeColor: "#'.$options['rgb'].'", strokeOpacity: 1.0,strokeWeight: '.$options['brush'].' }; ';
	$i=1;
	$google_map_code2.=' var line'.$dogooglemap.'_'.$i.' = new google.maps.Polyline(polyOptions'.$dogooglemap.');';
 	$google_map_code2.=' line'.$dogooglemap.'_'.$i.'.setPath(google.maps.geometry.encoding.decodePath("'.$pointstring.'"));';
 	$google_map_code2.=' line'.$dogooglemap.'_'.$i.'.setMap(map'.$dogooglemap.');';
	$google_map_code2.='var bounds'.$dogooglemap.' = new google.maps.LatLngBounds();';
	$google_map_code2.='line'.$dogooglemap.'_'.$i.'.getPath().forEach(function(latLng) {bounds'.$dogooglemap.'.extend(latLng);});';
	$google_map_code2.='map'.$dogooglemap.'.fitBounds(bounds'.$dogooglemap.');';
	$google_map_code2.='var resizer'.$dogooglemap.' = new CPResizeControl(map'.$dogooglemap.'); ';
	$google_map_code2.=$turnaround.$markertext;
	$names = array();
	$links = array();
	foreach ($places_array as $place_code) {
		$words=explode(":",$place_code);
		$names[] = $place_code;
		$links[] =$mapstuff;
	}
	if($canalplan_run_canal_route_maps[$post->ID]==1) {$google_map_code.=$google_map_code2;}
	return str_replace($names,$links , $content);
}

function canal_link_maps($content) {
   	 global $wpdb,$post,$dogooglemap,$google_map_code,$canalplan_run_canal_link_maps;
	// First we check the content for tags:
        if (preg_match_all('/' . preg_quote('[[CPGMW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
	// If the array is empty then we've no maps so don't do anything!
	if (!isset($places_array)) {return $content;}
	if (count($places_array)==0) {return $content;}
	if(!isset($canalplan_run_canal_link_maps[$post->ID])) {$canalplan_run_canal_link_maps[$post->ID]=1;} else {
	$canalplan_run_canal_link_maps[$post->ID]=$canalplan_run_canal_link_maps[$post->ID]+1;}
	$canalplan_options = get_option('canalplan_options');
	if ( get_query_var('feed') ||  is_feed() )  {
		$names = array();
		$links = array();
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
		    $names[] = "[[CPGMW:" .$place_code . "]]";
		    $links[] = "<b>[Embedded Google Map for ".trim($words[0])."]</b>";
	    	}
		return str_replace($names,$links , $content);
	}
	$maptype['S']="SATELLITE";
   	$maptype['R']="ROADMAP";
   	$maptype['T']="TERRAIN";
   	$maptype['H']="HYBRID";
   	$google_map_code2='';
   	$mapc=0;
	foreach ($places_array as $place_code) {
	$mapc=$mapc+1;
	$options['zoom']=$canalplan_options["canalplan_rm_zoom"];
	$options['type']=$canalplan_options["canalplan_rm_type"];
	if (!isset($options['type'])) {$options['type']='H';}
	if (!isset($options['zoom'])) {$options['zoom']=9;}
	$options['lat']=53.4;
	$options['long']=-2.8;
	$options['height']=$canalplan_options["canalplan_rm_height"];
	$options['width']=$canalplan_options["canalplan_rm_width"];
	$options['rgb']=$canalplan_options["canalplan_rm_r_hex"].$canalplan_options["canalplan_rm_g_hex"].$canalplan_options["canalplan_rm_b_hex"];
	$options['brush']=$canalplan_options["canalplan_rm_weight"];
		$mapstuff="<br />";
		$words=explode("|",$place_code);
		$opts=explode(",",$words[2]);
		foreach ($opts as $opt) {
			 $opcode=explode("=",$opt);
			if (count($opcode)>1) { $options[$opcode[0]]=strtoupper($opcode[1]);}
		}
		if($canalplan_run_canal_link_maps[$post->ID]==1) {$dogooglemap=$dogooglemap+1;}
		$dogooglemap='CPGMW'.$words[1].'_'.$post->ID.'_'.$mapc;
		$mapstuff.= '<div id="map_canvas_'.$dogooglemap.'"  style="width: '.$options['width'].'px; height: '.$options['height'].'px"></div>';
		$post_id=$post->ID;
		unset($missingpoly);
		unset($plines);
		unset($weights);
		unset($polylines);
		$missingpoly[]=$words[1];
		$sql2=$wpdb->prepare(' select lat,`long` from '.CANALPLAN_CODES.' where canalplan_id in (select place1 from '.CANALPLAN_LINK.' where waterway in (select id from '.CANALPLAN_CANALS.' where parent=%s or id=%s)) limit 1',$words[1],$words[1]);
		 $res = $wpdb->get_results($sql2,ARRAY_N);
		$rw = $res[0];
		$centre_lat=(float)$rw[0];
		$centre_long=(float)$rw[1];

		while ( count($missingpoly)>0 ) {
			reset($missingpoly);
			$sql=$wpdb->prepare("select 1 from ".CANALPLAN_POLYLINES." where id=%s",current($missingpoly));
			$res = $wpdb->get_results($sql,ARRAY_A);
			if ($wpdb->num_rows==1){$polylines[]=current($missingpoly);}
			$sql=$wpdb->prepare("select id from ".CANALPLAN_CANALS." where parent=%s",current($missingpoly));
			unset($missingpoly2);
			$res = $wpdb->get_results($sql,ARRAY_N);
			foreach($res as $rw) {
				$missingpoly[]=$rw[0];
			}
		$missingpoly=array_slice($missingpoly,1);
		}
		$markertext="";
		$i=1;
		$google_map_code2.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$centre_lat.','.$centre_long.'),';
		$google_map_code2.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: true,';
		$google_map_code2.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
		$google_map_code2.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
		$google_map_code2.='  var polyOptions'.$dogooglemap.' = {strokeColor: "#'.$options['rgb'].'", strokeOpacity: 1.0, strokeWeight: '.$options['brush'].' }; ';
		$i=1;
		$google_map_code2.='var bounds'.$dogooglemap.' = new google.maps.LatLngBounds();';
		foreach ($polylines as $polyline) {
			$sql=$wpdb->prepare("select pline from ".CANALPLAN_POLYLINES." where id=%s",$polyline);
			$res=$wpdb->get_results($sql,ARRAY_N);
			$rw = $res[0];
		    $google_map_code2.=' var line'.$dogooglemap.'_'.$i.' = new google.maps.Polyline(polyOptions'.$dogooglemap.');';
		 	$google_map_code2.=' line'.$dogooglemap.'_'.$i.'.setPath(google.maps.geometry.encoding.decodePath("'.$rw[0].'"));';
		 	$google_map_code2.=' line'.$dogooglemap.'_'.$i.'.setMap(map'.$dogooglemap.');';
	   		$google_map_code2.='line'.$dogooglemap.'_'.$i.'.getPath().forEach(function(latLng) {bounds'.$dogooglemap.'.extend(latLng);});';
			$google_map_code2.='map'.$dogooglemap.'.fitBounds(bounds'.$dogooglemap.');';
			$i=$i+1;
		}
      		$names[] = "[[CPGMW:" .$place_code . "]]";
      		$links[] = $mapstuff;
      	}
    if($canalplan_run_canal_link_maps[$post->ID]==1) {$google_map_code.=$google_map_code2;}
	return str_ireplace($matches[0], $links, $content);
}

function canal_place_maps($content,$mapblog_id=NULL,$post_id=NULL) {
	global $dogooglemap,$wpdb,$post,$google_map_code,$canalplan_run_canal_place_maps;
	$gazstring=CANALPLAN_URL.'gazetteer.cgi?id=';
	$canalplan_options = get_option('canalplan_options');

	// We don't support maps for features so lets just clean it from the content and return;
	if (preg_match_all('/' . preg_quote('[[CPGMF:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches2)) { $null_link[]=''; return str_ireplace($matches2[0], $null_link, $content);}

	if (preg_match_all('/' . preg_quote('[[CPGM:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
	// If the array is empty then we've no links so don't do anything!
	if (!isset($places_array)) {return $content;}

   	if (count($places_array)==0) {return $content;}
   	if(!isset($canalplan_run_canal_place_maps[$post->ID])) {$canalplan_run_canal_place_maps[$post->ID]=1;} else {
   	$canalplan_run_canal_place_maps[$post->ID]=$canalplan_run_canal_place_maps[$post->ID]+1;}
	if (isset($mapblog_id)) {} else { $mapblog_id=$wpdb->blogid;}
	if (isset($post_id)) {} else {$post_id=$post->ID;
        if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
    	$names = array();
    	$links = array();

    	if ( get_query_var('feed') || is_feed()) {
    		foreach ($places_array as $place_code) {
    		$words=explode("|",$place_code);
	    	$names[] = "[[CPGM:" .$place_code . "]]";
	    	$links[] = "<b>[Embedded Google Map for ".trim($words[0])."]</b>";
	    }
    	return str_ireplace($names, $links, $content);
    	}
   		$maptype['S']="SATELLITE";
	   	$maptype['R']="ROADMAP";
	   	$maptype['T']="TERRAIN";
	   	$maptype['H']="HYBRID";
	   	$google_map_code2='';
	   	$mapc=0;
	foreach ($places_array as $place_code) {
		$words=explode("|",$place_code);
		$mapc=$mapc+1;
		$sql=$wpdb->prepare("select lat,`long` from ".CANALPLAN_CODES." where canalplan_id=%s",$words[1]);;
		$res = $wpdb->get_results($sql,ARRAY_A);
	    if (count($res)>0) {
			$row = $res[0];
			$options['lat']=$row['lat'];
			$options['long']=$row['long'];
		}
	    $options['height']=$canalplan_options["canalplan_pm_height"];
		$options['width']=$canalplan_options["canalplan_pm_width"];
		$options['zoom']=$canalplan_options["canalplan_pm_zoom"];
		$options['type']=$canalplan_options["canalplan_pm_type"];
		if (!isset($options['type'])) {$options['type']='H';}
		if (!isset($options['zoom'])) {$options['zoom']=9;}
		if (count($words)>=3) {
			$opts=explode(",",$words[2]);
			foreach ($opts as $opt) {
				 $opcode=explode("=",$opt);
				if (count($opcode)>1) { $options[$opcode[0]]=strtoupper($opcode[1]);}
			}
		}
		$mapstuff="<br />";
		if($canalplan_run_canal_place_maps[$post->ID]==1) {$dogooglemap=$dogooglemap+1;}
		$dogooglemap='CPGM'.$words[1].'_'.$post->ID.'_'.$mapc;
		$mapstuff= '<div id="map_canvas_'.$dogooglemap.'" style="width: '.$options['width'].'px; height: '.$options['height'].'px"></div> ';
		$names[] = "[[CPGM:" .$place_code . "]]";
		$links[] = $mapstuff;
		$google_map_code2.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$options['lat'].','.$options['long'].'),';
		$google_map_code2.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: false,';
		$google_map_code2.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
		$google_map_code2.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
		$google_map_code2.= 'var marker'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$options['lat'].','.$options['long'].'), map: map'.$dogooglemap.', title: "'.$words[0].'"  });  ';
     }
    if($canalplan_run_canal_place_maps[$post->ID]==1) {$google_map_code.=$google_map_code2;}
	return str_ireplace($matches[0], $links, $content);
}

function canal_stats($content,$mapblog_id=NULL,$post_id=NULL) {
	global $blog_id,$wpdb,$post,$network_post;
//	var_dump($network_post);
	if (preg_match_all('/' . preg_quote('[[CPRS') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[0]; }
	if (!isset($places_array)) {return $content;}
	if (count($places_array)==0) {return $content;}
	if (isset($mapblog_id)) {} else { $mapblog_id=$blog_id;}
	if (isset($post_id)) {} else {$post_id=$post->ID;
	if ($post_id<=1) {$post_id=$post->ID;}
	if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
	//if ( get_query_var('feed') || $search=='Y' || is_feed() )  {
	if (isset($network_post)) {
		$post_id=$network_post->ID;
		$mapblog_id=$network_post->BLOG_ID;
		//echo "Setting";
	}
	//}
	if (!isset($post_id)) {return;}
	if (!isset($mapblog_id)) {return;}
	$sql=$wpdb->prepare("select distance,`locks`,start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and  post_id=%d",$mapblog_id,$post_id);
	$res = $wpdb->get_results($sql,ARRAY_A);
	$row = $res[0];
	$sql=$wpdb->prepare("select totalroute,uom from ".CANALPLAN_ROUTES." cpr, ".CANALPLAN_ROUTE_DAY." crd where cpr.route_id= crd.route_id and cpr.blog_id=crd.blog_id and crd.blog_id=%d and  crd.post_id=%d",$mapblog_id,$post_id);
	$res3 = $wpdb->get_results($sql,ARRAY_A);
	$row3 = $res3[0];
	$dformat=$row3['uom'];
	$places=explode(",",$row3['totalroute']);
	$sql=$wpdb->prepare("select distinct place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$places[$row['start_id']],$mapblog_id,$places[$row['start_id']],$places[$row['start_id']],$mapblog_id);
	$res2 = $wpdb->get_results($sql,ARRAY_A);
	$row2 = $res2[0];
	$start_name=$row2['place_name'];
	$sql=$wpdb->prepare("select distinct place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$places[$row['end_id']],$mapblog_id,$places[$row['end_id']],$places[$row['end_id']],$mapblog_id);
	$res2 = $wpdb->get_results($sql,ARRAY_A);
	$row2 = $res2[0];
	$end_name=$row2['place_name'];
	$names = array();
	$links = array();
	foreach ($places_array as $place_code) {
		$words=explode(":",$place_code);
		$names[] = $place_code;
		$links[] = "From [[CP:".$start_name."|".$places[$row['start_id']]."]] to [[CP:".$end_name."|".$places[$row['end_id']]."]], ".format_distance($row['distance'],$row['locks'],$dformat,2).".";
	}
	return str_ireplace($names, $links, $content);
}

function canal_linkify($content) {
	global $post,$blog_id,$wpdb,$network_post;
	/*if ( get_query_var('feed') || $search=='Y' || is_feed() )  {
		if (isset($network_post)) {
			$post_id=$network_post->ID;
			$mapblog_id=$network_post->BLOG_ID;
			$date=date("Ymd",strtotime($network_post->post_date));
			$title=urlencode($network_post->post_title);
			switch_to_blog( $network_post->BLOG_ID );
			$blog_url=get_bloginfo('url');
			$link=urlencode(str_replace($blog_url,"",get_permalink($network_post->ID)));
			restore_current_blog();
		}
	}*/
	if (isset($network_post)) {
		$post_id=$network_post->ID;
		$mapblog_id=$network_post->BLOG_ID;
		$date=date("Ymd",strtotime($network_post->post_date));
		$title=urlencode($network_post->post_title);
		switch_to_blog( $network_post->BLOG_ID );
		$blog_url=get_bloginfo('url');
		$link=urlencode(str_replace($blog_url,"",get_permalink($network_post->ID)));
		restore_current_blog();
	}
	if (!isset($mapblog_id)){
		$blog_url=get_bloginfo('url');
		$date = date("Ymd",strtotime($post->post_date));
		$link=urlencode(str_replace($blog_url,"",get_permalink($post->ID)));
		$title=urlencode($post->post_title);
		$mapblog_id=$blog_id;
	}
	// First we check the content for tags:
	if (preg_match_all('/' . preg_quote('[[CP') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
	// If the array is empty then we've no links so don't do anything!
	#if (count($places_array)==0) {return $content;}
	$names = array();
	$links = array();
	if (preg_match_all('/' . preg_quote('[[CP:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) {
		$places_array=$matches[1];
		$gazstring=CANALPLAN_GAZ_URL;
		$sql=$wpdb->prepare("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='canalkey'",$mapblog_id);
		$r2 = $wpdb->get_results($sql,ARRAY_A);
		if ($wpdb->num_rows==0) {
		     $api="";
		}
		else
		{
			$rw = $r2[0];
			$api=explode("|",$rw['pref_value']);

		}
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
			$names[] = "[[CP:" .$place_code . "]]";
			if ($api[0]=="") {
				$links[] = "<a href='".CANALPLAN_GAZ_URL.$words[1]."' target='gazetteer'  title=\"Link to ".trim($words[0])." on Canalplan \">".htmlspecialchars(trim($words[0]))."</a>";
			}
			 else
			{
				$links[] ="<a href='". CANALPLAN_GAZ_URL .$words[1]. "?blogkey=".$api[0]."&title=".$title."&blogid=".$api[1]."&date=".$date."&url=".$link."' target='gazetteer' title=\"Link to ".trim($words[0])." on Canalplan\">".htmlspecialchars(trim($words[0]))."</a>";
			}
		}
	}
	if (preg_match_all('/' . preg_quote('[[CPW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) {
		$places_array=$matches[1];
		$gazstring=CANALPLAN_WAT_URL;
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
			$names[] = "[[CPW:" .$place_code . "]]";
			$links[] ="<a href='".$gazstring.$words[1]."' target='gazetteer'  title='Link to ".trim($words[0])."'>".trim($words[0])."</a>";
		}
	}

		if (preg_match_all('/' . preg_quote('[[CPF:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) {
		$places_array=$matches[1];
		$gazstring=CANALPLAN_FEA_URL;
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
			$names[] = "[[CPF:" .$place_code . "]]";
			if ($api[0]=="") {
			$links[] ="<a href='".$gazstring.$words[1]."' target='gazetteer'  title=\"Link to ".trim($words[0])." on Canalplan \">".htmlspecialchars(trim($words[0]))."</a>";
		 }
			 else
			{
				$links[] ="<a href='". $gazstring.$words[1]. "?blogkey=".$api[0]."&title=".$title."&blogid=".$api[1]."&date=".$date."&url=".$link."' target='gazetteer' title=\"Link to ".trim($words[0])." on Canalplan\">".htmlspecialchars(trim($words[0]))."</a>";
			}
		}
	}
	return str_ireplace($names, $links, $content);
}

function canal_linkify_name($content) {
	global $post,$blog_id,$wpdb;
	// First we check the content for tags:
	if (preg_match_all('/' . preg_quote('[[CP') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
	// If the array is empty then we've no links so don't do anything!
	#if (count($places_array)==0) {return $content;}
	$names = array();
	$links = array();
	if (preg_match_all('/' . preg_quote('[[CP:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) {
		$places_array=$matches[1];
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
			$names[] = "[[CP:" .$place_code . "]]";
			$links[] = trim($words[0]);
		}
	}
	if (preg_match_all('/' . preg_quote('[[CPW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) {
		$places_array=$matches[1];
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
			$names[] = "[[CPW:" .$place_code . "]]";
			$links[] = trim($words[0]);
		}
	}
	if (preg_match_all('/' . preg_quote('[[CPF:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) {
		$places_array=$matches[1];
		foreach ($places_array as $place_code) {
			$words=explode("|",$place_code);
			$names[] = "[[CPF:" .$place_code . "]]";
			$links[] = trim($words[0]);
		}
	}
	return str_ireplace($names, $links, $content);
}


function canal_bloggedroute($embed=0,$overnight="N"){
	if (!isset($_GET['routeid'])){$_GET['routeid']=0;}
	$routeid = $_GET['routeid'];
	$routeid = preg_replace('{/$}', '', $routeid);
	$blroute='';
	if (!isset($routeid)){$routeid=0;}
	if ($routeid<=0){$routeid=0;}
	if ($embed>0) {$routeid=$embed;}
	global $wpdb,$blog_id,$google_map_code,$dogooglemap;
	$dogooglemap=1;
	if ( get_query_var('feed') || $search=='Y' || is_feed() )  {
			$links[] ="<b>[ Google Route Map embedded here ]</b>" ;
		} else {
	$canalplan_options = get_option('canalplan_options');
	if ($embed>=1) {$routeid=$embed;$dogooglemap=$embed;}
	if ($routeid==0){
		if ($wpdb->blogid==1) {
			$sql="select route_id,title,blog_id from ".CANALPLAN_ROUTES." where status=3 order by route_id desc";
		}
		else
		{
			$sql=$wpdb->prepare("select route_id, title,description,blog_id from ".CANALPLAN_ROUTES." where status=3 and blog_id=%d order by route_id desc",$blog_id);
		}
		if (!defined('CANALPLAN_ROUTE_SLUG')){
			$r2 = $wpdb->get_results("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=-1 and pref_code='routeslug'",ARRAY_A);
			if ($wpdb->num_rows==0) {
		    		 $routeslug="UNDEFINED!";
			}
			else
			{
					$routeslug=$r2[0]['pref_value'];
			}
		}
		else {
			$routeslug=CANALPLAN_ROUTE_SLUG;
		}
		$res = $wpdb->get_results($sql,ARRAY_A);
		$blroute .="<ol>";
		foreach ($res as $row) {
			if ($wpdb->blogid==1) {$blroute .='<li><a href='.get_blog_option($row['blog_id'],"siteurl").'/'.$routeslug.'/?routeid='.$row['route_id'].' target=\"_new\">'.$row['title'].'</a> ( from '. get_blog_option($row['blog_id'],'blogname').' )  </li>';
			}
			else
			{
				$blroute .='<li><a href='.get_blog_option($row['blog_id'],"siteurl").'/'.$routeslug.'/?routeid='.$row['route_id'].' target=\"_new\">'.$row['title'].'</a> ('.$row['description'].')</li>';
			}
		}
		$blroute .="</ol><br><br>";
	}
	else
	{
		$sql=$wpdb->prepare("select description, totalroute from ".CANALPLAN_ROUTES." where route_id=%d and blog_id=%d",$routeid,$wpdb->blogid);
		$res = $wpdb->get_results($sql,ARRAY_A);
//		$mid_point=round($wpdb->num_rows/2,0,PHP_ROUND_HALF_UP);
		$mid_point=round(count($row['totalroute'])/2,0,PHP_ROUND_HALF_UP);
		$place_count=0;
		$row = $res[0];
		if($embed==0) { $blroute .="<h2>".$row['description']."</h2><br/>"; }
		$blroute.='<div id="map_canvas_'.$overnight.'_'.$dogooglemap.'"  style="width: '.$canalplan_options["canalplan_rm_width"].'px; height: '.$canalplan_options["canalplan_rm_height"].'px"></div>';
		$pointstring = "";
		$zoomstring = "";
		$lat = 0;
		$long = 0;
		$lpoint="";
		$lpointb1="";
		$x=3;
		$y=-1;
		$places=explode(",",$row['totalroute']);
		$lastid=end($places);
		$firstid=reset($places);
		$turnaround="";
		$firstname="";
		$first_lat="";
		$first_long="";
  		$mapstuff='<div id="map_canvas_'.$overnight.'_'.$dogooglemap.'"  style="width: '.$canalplan_options["canalplan_rm_width"].'px; height: '.$canalplan_options["canalplan_rm_height"].'px"></div>';
		foreach ($places as $place) {
			$sql=$wpdb->prepare("select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id=%s",$place);
			$res =  $wpdb->get_results($sql,ARRAY_A);
			$row='';
			if (count($res)>0) {
			$row = $res[0];}
			if (count($row)> 2) {
			if ($place==$firstid){
				$firstname=addslashes($row['place_name']);
				$first_lat=$row['lat'];
				$first_long=$row['long'];
			}
			if ($place==$lastid){
				$lastname=addslashes($row['place_name']);
				$last_lat=$row['lat'];
				$last_long=$row['long'];
			}
			if($place_count==$mid_point) {
				$centre_lat=$row['lat'];
				$centre_long=$row['long'];
			}
			$place_count=$place_count+1;
			$points=$place.",".$row['lat'].",".$row['long'];
		      	$pointx = $row['lat'];
			$pointy = $row['long'];
			$nlat = floor($pointx * 1e5);
			$nlong = floor($pointy * 1e5);
			$pointstring .= ascii_encode($nlat - $lat) . ascii_encode($nlong - $long);
			$zoomstring .= 'B';
			$lat = $nlat;
			$long = $nlong;
			$cpoint=addslashes($row['place_name']).",".$row['lat'].",".$row['long'];
			if ($cpoint==$lpointb1) {
				$lpoints=explode(",",$lpoint);
				$turnaround.='var marker_turn_'.$overnight.'_'.$dogooglemap.'_'.$x.' = new google.maps.Marker({ position: new google.maps.LatLng('.$lpoints[1].','.$lpoints[2].'), map: map_'.$overnight.'_'.$dogooglemap.',   title: "Turn Round here  : '.$lpoints[0].'" });';
				$turnaround.='iconFile = "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"; marker_turn_'.$overnight.'_'.$dogooglemap.'_'.$x.'.setIcon(iconFile) ; ';
			 	$x=$x+1;
			}
			$lpointb1=$lpoint;
			$y=$y+1;
			$lpoint=$cpoint;
			}
		if ($firstid==$lastid) {
			$markertext='var marker_start_'.$overnight.'_'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$first_lat.','.$first_long.'), map: map_'.$overnight.'_'.$dogooglemap.',   title: "Start / Finish : '.$firstname.'"});';
			$markertext.='iconFile = "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png"; marker_start_'.$overnight.'_'.$dogooglemap.'.setIcon(iconFile) ; ';
		}
		else
		{
			$markertext='var marker_start_'.$overnight.'_'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$first_lat.','.$first_long.'), map: map'.$overnight.'_'.$dogooglemap.',   title: "Start : '.$firstname.'" });';
			$markertext.='var marker_stop_'.$overnight.'_'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$last_lat.','.$last_long.'), map: map'.$overnight.'_'.$dogooglemap.',  title: "Stop : '.$lastname.'" });';
			$markertext.='iconFile = "http://maps.google.com/mapfiles/ms/icons/green-dot.png"; marker_start_'.$overnight.'_'.$dogooglemap.'.setIcon(iconFile) ; ';
			$markertext.='iconFile = "http://maps.google.com/mapfiles/ms/icons/red-dot.png"; marker_stop_'.$overnight.'_'.$dogooglemap.'.setIcon(iconFile) ; ';
		}}
		#$blroute .=$pointstring;
		$options['size']=200;
		$options['zoom']=$canalplan_options["canalplan_rm_zoom"];
		$options['type']=$canalplan_options["canalplan_rm_type"];
		if (!isset($options['type'])) {$options['type']='H';}
		if (!isset($options['zoom'])) {$options['zoom']=9;}
		$options['lat']=53.4;
		$options['long']=-2.8;
		$options['rgb']=$canalplan_options["canalplan_rm_r_hex"].$canalplan_options["canalplan_rm_g_hex"].$canalplan_options["canalplan_rm_b_hex"];
	   	$maptype['S']="SATELLITE";
	   	$maptype['R']="ROADMAP";
	   	$maptype['T']="TERRAIN";
	   	$maptype['H']="HYBRID";

		$google_map_code.= 'var map_'.$overnight.'_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$centre_lat.','.$centre_long.'),';
	    $google_map_code.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: true,';
	    $google_map_code.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
	    $google_map_code.= 'var map_'.$overnight.'_'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$overnight.'_'.$dogooglemap.'"),map_'.$overnight.'_'.$dogooglemap.'_opts);';
	    $google_map_code.='  var polyOptions_'.$overnight.'_'.$dogooglemap.' = {strokeColor: "#'.$options['rgb'].'", strokeOpacity: 1.0,strokeWeight: '.$canalplan_options["canalplan_rm_weight"].' }; ';
		$i=1;
		$google_map_code.=' var line_'.$overnight.'_'.$dogooglemap.'_'.$i.' = new google.maps.Polyline(polyOptions_'.$overnight.'_'.$dogooglemap.');';
	 	$google_map_code.=' line_'.$overnight.'_'.$dogooglemap.'_'.$i.'.setPath(google.maps.geometry.encoding.decodePath("'.$pointstring.'"));';
	 	$google_map_code.=' line_'.$overnight.'_'.$dogooglemap.'_'.$i.'.setMap(map_'.$overnight.'_'.$dogooglemap.');';
		$google_map_code.='var bounds_'.$overnight.'_'.$dogooglemap.' = new google.maps.LatLngBounds();';
		$google_map_code.='line_'.$overnight.'_'.$dogooglemap.'_'.$i.'.getPath().forEach(function(latLng) {bounds_'.$overnight.'_'.$dogooglemap.'.extend(latLng);});';
		$google_map_code.='map_'.$overnight.'_'.$dogooglemap.'.fitBounds(bounds_'.$overnight.'_'.$dogooglemap.');';
		$google_map_code.=$turnaround.$markertext;
	}
	if ($overnight=='Y'){
		$sql=$wpdb->prepare("select day_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=%d and  route_id=%d",$blog_id,$routeid);
		$res = $wpdb->get_results($sql,ARRAY_A);
		$markertext2='';
		foreach($res as $dayresult){

		$endp=$places[$dayresult['end_id']];
		$sql=$wpdb->prepare("select distinct canalplan_id, place_name from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d union select canalplan_id, place_name from ".CANALPLAN_CODES." where canalplan_id=%s and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id=%s and blog_id=%d)",$endp,$blog_id,$endp,$endp,$blog_id);
			$res3 = $wpdb->get_results($sql,ARRAY_A);
			$endplaces[]  = $res3[0];
		}
		$endplace=array_pop($endplaces);
		foreach ($endplaces as $dayid=>$onplace) {
			$sql=$wpdb->prepare("select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id=%s",$onplace);
			$res =  $wpdb->get_results($sql,ARRAY_A);
			$row='';
			if (count($res)>0) {
				$row = $res[0];
			}
			$markertext2.='var marker_onight'.($dayid+1).'_'.$overnight.'_'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$row['lat'].','.$row['long'].'), map: map_'.$overnight.'_'.$dogooglemap.',   title: "Overnight at : '.$row['place_name'].'"});';
			$markertext2.='iconFile = "http:/wp-content/plugins/canalplan-ac/canalplan/markers/cp_'.($dayid+1).'.png"; marker_onight'.($dayid+1).'_'.$overnight.'_'.$dogooglemap.'.setIcon(iconFile) ; ';
		}
		$google_map_code.=$markertext2;
	}
}
	if($embed==0 && $routeid>0) {
		$blroute .= "<p><h2>Blog Entries for this trip</h2>";
	//	$sql="select id, post_title from ".$wpdb->posts." where id in (select post_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$wpdb->blogid." and  route_id=".$routeid."  order by day_id asc ) and post_status='publish' order by id asc";
		$sql="select id, post_title, crd.day_id from ".$wpdb->posts." bp, ".CANALPLAN_ROUTE_DAY." crd where bp.id = crd.post_id and crd.blog_id=".$wpdb->blogid." and  crd.route_id=".$routeid." and post_status='publish' order by crd.day_id asc";
		$res = $wpdb->get_results($sql,ARRAY_A);
		$blroute .="<ol>";
		foreach ($res as $row) {
			$link = get_blog_permalink( $blog_id, $row['id'] ) ;
			$extra='';
			if ($row['day_id']==0) $extra='( Trip Summary )';
			$blroute .="<li><a href=\"$link\" target=\"_new\">$row[post_title] $extra</a> </li>";
		}
		$blroute .="</ol>";
	}
	return $blroute ;
}

function wp_canalplan_admin_pages() {
	$base_dir=dirname(__FILE__).'/admin-pages/';
	$hook=add_menu_page('CanalPlan AC Overview', 'CanalPlan AC', 8,$base_dir.'cp-admin-menu.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: General Options', 'General Options','activate_plugins',  $base_dir.'cp-admin-general.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Home Mooring', 'Home Mooring', 'activate_plugins', $base_dir.'cp-admin-home.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Favourites', 'Favourites', 'activate_plugins',  $base_dir.'cp-admin-fav.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Google Maps', 'Google Maps', 'activate_plugins',  $base_dir.'cp-admin-google.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Import Route', 'Import Routes', 'activate_plugins',  $base_dir.'cp-import_route.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Manage Routes', 'Manage Routes', 'activate_plugins',  $base_dir.'cp-manage_route.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Set Location', 'Set Location', 'activate_plugins',  $base_dir.'cp-admin-location.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Diagnostics', 'Diagnostics', 'activate_plugins',  $base_dir.'cp-admin-diagnostics.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Bulk Notify', 'Bulk Notify', 'activate_plugins',  $base_dir.'cp-admin-update.php');
}

function canalplan_header($blah){
	global $blog_id,$wpdb,$google_map_code;
	$canalplan_options = get_option('canalplan_options');
	if (isset($canalplan_options['supress_google'])) {return;}
	$header = '<meta name="viewport" content="initial-scale=1.0, user-scalable=no" /> <script type="text/javascript" src="//maps.google.com/maps/api/js?libraries=geometry&amp;sensor=false"> </script> ';
	echo $header;
	$google_map_code='<script type="text/javascript"> google.maps.visualRefresh = true; function initialize() {  ';
	return $blah;
}

function canalplan_footer($blah) {
	global $google_map_code;
	$google_map_code.='  } </script> ';
	echo $google_map_code;
	echo "\n<!-- Canalplan AC code revision : ".CANALPLAN_CODE_RELEASE." -->\n";
	$canalplan_options = get_option('canalplan_options');
	if (isset($canalplan_options['supress_google'])) {return;}
	echo "<script type='text/javascript'> google.maps.event.addDomListener(window, 'load', initialize); </script> ";
?>
<script type='text/javascript'>
function CPResizeControl(e){this.startUp(e)}CPResizeControl.RESIZE_BOTH=0;CPResizeControl.RESIZE_WIDTH=1;CPResizeControl.RESIZE_HEIGHT=2;CPResizeControl.prototype.startUp=function(e){var t=this;this._map=e;this.resizing=false;this.mode=CPResizeControl.RESIZE_BOTH;this.minWidth=150;this.minHeight=150;this.maxWidth=0;this.maxHeight=0;this.diffX=0;this.diffY=0;google.maps.event.addListenerOnce(e,"tilesloaded",function(){var n=new CPResizeControl.ResizeControl(t,e);n.index=1})};CPResizeControl.ResizeControl=function(e,t){var n=document.createElement("div");n.style.width="20px";n.style.height="20px";n.style.backgroundImage="url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUBAMAAAB/pwA+AAAAAXNSR0IArs4c6QAAAA9QTFRFMBg0f39/0dDN7eri/v7+XsdLVAAAAAF0Uk5TAEDm2GYAAABNSURBVAjXRcpBDcAwDEPRKAymImghuCUw/qTWJI7nk/X0zXquZ+tH6E5df3TngPBA+ELY7UW2gWwDq02sNjHbwmwLoyVGS7ytbw62tA8zTA85AeAv2wAAAABJRU5ErkJggg%3D%3D)";n.style.position="absolute";n.style.right="0px";n.style.bottom="0px";google.maps.event.addDomListener(n,"mousedown",function(){e.resizing=true});google.maps.event.addDomListener(document,"mouseup",function(){if(e.resizing){e.resizing=false;if(typeof e.doneCallBack=="function")e.doneCallBack(e._map)}});google.maps.event.addDomListener(document,"mousemove",function(t){e.mouseMoving(t)});var r=t.getDiv();r.appendChild(n);var i=r.firstChild.childNodes[2];i.style.marginRight="25px";return n};CPResizeControl.prototype.changeMapSize=function(e,t){var n=this._map.getDiv().style;var r=parseInt(n.width);var i=parseInt(n.height);var s=r,o=i;r+=e;i+=t;if(this.minWidth){r=Math.max(this.minWidth,r)}if(this.maxWidth){r=Math.min(this.maxWidth,r)}if(this.minHeight){i=Math.max(this.minHeight,i)}if(this.maxHeight){i=Math.min(this.maxHeight,i)}var u=false;if(this.mode!=CPResizeControl.RESIZE_HEIGHT){n.width=r+"px";u=true}if(this.mode!=CPResizeControl.RESIZE_WIDTH){n.height=i+"px";u=true}if(u){if(typeof this.changeCallBack=="function")this.changeCallBack(this._map,r,i,r-s,i-o);google.maps.event.trigger(this._map,"resize")}};CPResizeControl.prototype.mouseMoving=function(e){var t=window.scrollX||document.documentElement.scrollLeft||0;var n=window.scrollY||document.documentElement.scrollTop||0;if(!e)e=window.event;var r=e.clientX+t;var i=e.clientY+n;if(this.resizing){this.changeMapSize(r-this.diffX,i-this.diffY)}this.diffX=r;this.diffY=i;return false}
</script>
<?php
	return $blah;
}

function canal_blogroute_insert($content)
{
  if (preg_match('{BLOGGEDROUTES}',$content))
    {
      $content = str_replace('{BLOGGEDROUTES}',canal_bloggedroute(0),$content);
    }
  return $content;
}

function canal_activate() {
	global $wpdb, $table_prefix;
	wp_cache_flush();
	$errors = array();
	$sql='CREATE TABLE IF NOT EXISTS '. CANALPLAN_ALIASES. ' (
			  `canalplan_id` varchar(10) NOT NULL,
			  `place_name` varchar(250) NOT NULL,
			  UNIQUE KEY `ALIAS_IDX` (`canalplan_id`,`place_name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_ALIASES ;
	$sql= 'CREATE TABLE IF NOT EXISTS '.CANALPLAN_CODES.' (
	  `canalplan_id` varchar(10) NOT NULL ,
	  `place_name` varchar(250) NOT NULL ,
	  `size` tinyint(1) default NULL,
	  `lat` float NOT NULL default 0,
	  `long` float NOT NULL default 0,
	  `type` tinyint(2) unsigned default NULL,
	  `attributes` varchar(20) default NULL,
	  `lat_lng_point` point NOT NULL,
	  PRIMARY KEY  (`canalplan_id`),
	  KEY `place_name` (`place_name`),
	  SPATIAL KEY `places_spatial_idx` (`lat_lng_point`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_CODES ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_FAVOURITES.' (
	  `blog_id` bigint(20) NOT NULL default 0,
	  `place_order` int(4) NOT NULL default 0,
	  `canalplan_id` varchar(10) NOT NULL ,
	  `place_name` varchar(250) NOT NULL ,
	  PRIMARY KEY  (`blog_id`,`canalplan_id`,`place_order`),
	  KEY `canalplan_idx` (`canalplan_id`)
	)  DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_FAVOURITES ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_LINK.' (
	  `place1` varchar(4) NOT NULL ,
	  `place2` varchar(4) NOT NULL ,
	  `metres` bigint(10) default NULL,
	  `locks` bigint(10) default NULL,
	  `waterway` varchar(4) default NULL,
	  PRIMARY KEY  (`place1`,`place2`),
	  KEY `waterway` (`waterway`)
	)  DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_LINK ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_OPTIONS.' (
	  `blog_id` bigint(20) NOT NULL default 0,
	  `pref_code` varchar(20) NOT NULL ,
	  `pref_value` varchar(240) NOT NULL ,
	  PRIMARY KEY  (`blog_id`,`pref_code`)
	) DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_OPTIONS ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_ROUTES.' (
		  `route_id` bigint(10) NOT NULL default 0,
		  `blog_id` bigint(20) NOT NULL default 0,
		  `cp_route_id` varchar(20) default NULL,
		  `title` varchar(100) default NULL,
		  `description` varchar(240) default NULL,
		  `start_date` date default NULL,
		  `duration` int(3) default NULL,
		  `UOM` char(1) default NULL,
		  `total_distance` float default NULL,
		  `status` int(1) default NULL,
		  `total_locks` bigint(10) default NULL,
		  `totalroute` text NOT NULL,
		  `Intermediate_places` varchar(240) default NULL,
		  PRIMARY KEY  (`route_id`,`blog_id`),
		  KEY `status` (`status`)
		)  DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_ROUTES ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_CANALS.' (
	  `id` varchar(4) NOT NULL,
	  `parent` varchar(4) default NULL,
	  `name` varchar(40) NOT NULL,
	  `fullname` varchar(120) NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `parent` (`parent`)
	)  DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_CANALS ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_POLYLINES.' (
	  `id` varchar(5) NOT NULL,
	  `pline` longtext,
	  `weights` longtext,
	  PRIMARY KEY  (`id`)
	) DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_POLYLINES ;
	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_ROUTE_DAY.' (
	  `route_id` bigint(10) NOT NULL default 0,
	  `day_id` int(3) NOT NULL default 0,
	  `blog_id` bigint(20) NOT NULL default 0,
	  `post_id` bigint(20) default NULL,
	  `route_date` date default NULL,
	  `start_id` int(4) default NULL,
	  `end_id` int(4) default NULL,
	  `distance` int(10) default NULL,
	  `locks` int(4) default NULL,
	  `flags` varchar(10) default NULL ,
	  PRIMARY KEY  (`route_id`,`day_id`,`blog_id`),
	  KEY `post_blog_idx` (`blog_id`,`post_id`)
	) DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_POLYLINES ;

	if ($errors) {
		foreach ($errors as $errormsg) {
			_e("$errormsg<br />\n");
		}
		return;
	}
}

function canalplan_option_init(){
	register_setting( 'canalplan_options', 'canalplan_options','canalplan_validate_options');
}

function canalplan_validate_options($options) {
	# Do they want to reset? If so we reset the options and let WordPress do the business for us!
	if (isset( $_POST["RSD"] ))  {
		$options["canalplan_pm_type"]='H';
		$options["canalplan_pm_zoom"]=14;
		$options["canalplan_pm_height"]=200;
		$options["canalplan_pm_width"]=200;
		$options["canalplan_rm_type"]='H';
		$options["canalplan_rm_zoom"]=9;
		$options["canalplan_rm_height"]=600;
		$options["canalplan_rm_width"]=500;
		$options["canalplan_rm_r_hex"]="00";
		$options["canalplan_rm_g_hex"]="00";
		$options["canalplan_rm_b_hex"]="ff";
		$options["canalplan_rm_weight"]=4;
	}
	return $options;
}

add_action('admin_init', 'canalplan_option_init' );

function save_error(){
    update_option('plugin_error',  ob_get_contents());
}

add_action('activated_plugin','save_error');
add_action('admin_menu', 'canalplan_add_custom_box');
add_action('init', 'canal_init');
register_activation_hook(__FILE__, 'canal_activate');
add_action('admin_menu', 'wp_canalplan_admin_pages');
include("canalplan_widget.php");
?>