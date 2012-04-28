<?php

/*
Plugin Name: CanalPlan AC Integration
Plugin URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Description: Provides features to integrate your blog with <a href="http://www.canalplan.eu">Canalplan AC</a> - the Canal Route Planner.
Version: 1.0.1
Author: Steve Atty
Author URI: http://blogs.canalplan.org.uk/steve/
 *
 *
 * Copyright 2011 Steve Atty (email : posty@tty.org.uk)
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

@include("multisite.php");
define ('CANALPLAN_URL','http://www.canalplan.eu/cgi-bin/');
define ('CANALPLAN_GAZ_URL','http://www.canalplan.eu/gazetteer/');
define ('CANALPLAN_MAX_POST_PROCESS',20);
define('CANALPLAN_CODE_RELEASE','1.0.1 r00');

global $table_prefix, $wp_version,$wpdb,$db_prefix;
# Determine the right table prefix to use
$cp_table_prefix=$table_prefix;
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

	$furltext.=$wholefurls.$fracttext;
	if($short!=1) $dist_text="a distance of ";
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

/* Use the admin_menu action to define the custom boxes */
add_action('admin_menu', 'myplugin_add_custom_box');

/* Use the save_post action to do something with the data entered */
#add_action('save_post', 'myplugin_save_postdata');

/* Adds a custom section to the "advanced" Post and Page edit screens */
function myplugin_add_custom_box() {


    add_meta_box( 'myplugin_sectionid', __( 'CanalPlan Tags', 'myplugin_textdomain' ), 
                'myplugin_inner_custom_box', 'post', 'advanced' );
    add_meta_box( 'myplugin_sectionid', __( 'CanalPlan Tags', 'myplugin_textdomain' ), 
                'myplugin_inner_custom_box', 'page', 'advanced' );
}
   
/* Prints the inner fields for the custom post/page section */
function myplugin_inner_custom_box() {

  // Use nonce for verification

  echo '<input type="hidden" name="myplugin_noncename" id="myplugin_noncename" value="' . 
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  // The actual fields for data entry

global $wpdb,$blog_id;
#include("canalplan/canalplanfunct.js");
echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
echo '<script type="text/javascript" src="/wp-content/plugins/canalplan/canalplan/canalplanfunctions.js" DEFER></script>';
echo '<script type="text/javascript" src="/wp-content/plugins/canalplan/canalplan/canalplan_actb.js"></script>';
echo "Insert : ";
$blog_favourites = $wpdb->get_results("SELECT place_name FROM ".CANALPLAN_FAVOURITES." where blog_id=$blog_id order by place_order asc" );
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
print '<script>canalplan_actb(document.getElementById("CanalPlanID"),new Array());</script>';
}



class CanalPlanAutolinker {



	function canal_init() {
	//	add_action('admin_menu', array(__CLASS__, 'canal_add_options_page'));
	   add_filter('the_content', array(__CLASS__, 'canal_stats'));
	   add_filter('the_content', array(__CLASS__, 'canal_route_maps'));
	   add_filter('the_content', array(__CLASS__, 'canal_place_maps'));
	   add_filter('the_content', array(__CLASS__, 'canal_link_maps'));
	   add_filter('the_content', array(__CLASS__, 'canal_linkify'));
;
           global $dogooglemap;
                $dogooglemap=0;

	}


	function canal_add_options_page() {
		add_options_page(
			'CanalPlan Autolinker Options',
			'CanalPlan Autolinker',
			'manage_options',
			'canalplan-autolinker-options',
			array(__CLASS__, 'canal_options_page'));
	}

	function canal_route_maps($content,$mapblog_id=NULL,$post_id=NULL,$search=NULL) {
	    global $wpdb,$post,$blog_id;
		// First we check the content for tags:
		if (preg_match_all('/' . preg_quote('[[CPRM') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[0]; }
		// If the array is empty then we've no maps so don't do anything!
		if (count($places_array)==0) {return $content;}
                if (isset($mapblog_id)) {} else { $mapblog_id=$blog_id;}
                if (isset($post_id)) {} else {$post_id=$post->ID;
                if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
		
	if ( get_query_var('feed') || $search=='Y' )  {
                $names = array();
                $links = array();
                foreach ($places_array as $place_code) {
                $words=split(":",$place_code);
                        $names[] = $place_code;
                        $links[] ="<b>[Google Route Map embedded here]</b>" ;
                }
                return str_replace($names,$links , $content);
		}
		// Get the Googlemap global and if its zero (i.e. first time called on the page) put out the googlemap key
		global $dogooglemap;
		$mapstuff="<br />";
		if ($dogooglemap==0){

		}
		// Increment it
		$dogooglemap=$dogooglemap+1;
		// To allow multiple maps per page (for viewing posts by category) append the dogooglemap id to the end of the map div id
		$mapstuff.= '<div id="map'.$dogooglemap.'" style="width: 500px; height: 600px"></div><script type="text/javascript">';
		$post_id=$post->ID;
     		 $sql="select distance,`locks`,start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$mapblog_id." and  post_id=".$post_id;
		
		$res = mysql_query($sql);
		$row = mysql_fetch_array($res);
		$sql="select totalroute from ".CANALPLAN_ROUTES." cpr, ".CANALPLAN_ROUTE_DAY."  crd where cpr.route_id= crd.route_id and cpr.blog_id=crd.blog_id and crd.blog_id=".$mapblog_id." and  crd.post_id=".$post_id;
		$res3 = mysql_query($sql);
		$row3 = mysql_fetch_array($res3);
		$places=split(",",$row3[totalroute]);
		$dayroute=array_slice($places,$row[start_id], ($row[end_id] - $row[start_id])+1);
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
		foreach ($dayroute as $place) {
$sql2="set names 'utf8';";
$zed = mysql_query($sql2);
		$sql="select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id='".$place."'";
		$res = mysql_query($sql);
		$row = mysql_fetch_array($res);
		if ($place==$firstid){$firstname=$row[place_name];}
		if ($place==$lastid){$lastname=$row[place_name];}
		$points=$place.",".$row[lat].",".$row[long];
		      $pointx = $row[lat];
		        $pointy = $row[long];;
		        $nlat = floor($pointx * 1e5);
		        $nlong = floor($pointy * 1e5);
		        $pointstring .= ascii_encode($nlat - $lat) . ascii_encode($nlong -
		$long);
		        $zoomstring .= 'B';
		        $lat = $nlat;
		        $long = $nlong;
		        $cpoint=$row[place_name].",".$row[lat].",".$row[long];
		        if ($cpoint==$lpointb1) {
			$lpoints=split(",",$lpoint);
		$turnaround.='var marker'.$dogooglemap.$x.' = new GMarker(encodedPolyline.getVertex('.$y.'),{icon:icon2, draggable: false, title: "Turn Round here : '.$lpoints[0].'"});';
		$turnaround.='map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.$x.');';
		 $x=$x+1;
		}
		$lpointb1=$lpoint;
		$y=$y+1;
		$lpoint=$cpoint;
		}
		if ($firstid==$lastid) {
			$markertext='var marker'.$dogooglemap.'0 = new GMarker(encodedPolyline.getVertex(0), {icon:icon0, draggable: false, title: "Start/Finish : '.$firstname.'"});';
			$markertext.='map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.'0);';
		}
		else
		{
			$markertext='var marker'.$dogooglemap.'0 = new GMarker(encodedPolyline.getVertex(0), {icon:icon0, draggable: false, title: "Start : '.$firstname.'"});';
			$markertext.='var marker'.$dogooglemap.'1 = new GMarker(encodedPolyline.getVertex(encodedPolyline.getVertexCount()-1), {icon:icon1, draggable: false, title:"Finish : '.$lastname.'"});';
			$markertext.='map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.'0);';
			$markertext.='map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.'1);';
		}

		 $page2= ' if (GBrowserIsCompatible()) { 			if(document.implementation.hasFeature( "http://www.w3.org/TR/SVG11/feature#SVG","1.1")){  _mSvgEnabled = true;  _mSvgForced  = true;}';
		 $page2.= ' var map'.$dogooglemap.' = new GMap2(document.getElementById("map'.$dogooglemap.'")); map'.$dogooglemap.'.setCenter(new GLatLng(52.97035617,-2.508565798), 9); map'.$dogooglemap.'.addControl(new GSmallMapControl());	map'.$dogooglemap.'.addControl(new GMapTypeControl());  map'.$dogooglemap.'.setMapType(G_NORMAL_MAP); map'.$dogooglemap.'.setZoom(9);';
                 $page2.='var ads'.$dogooglemap.' = new GAdsManager(map'.$dogooglemap.',"pub-4296098225870941",{maxAdsOnMap : 10, style: "icon", channel: "5900391315" , minZoomLevel: 0}); ads'.$dogooglemap.'.enable();';
		 $page2.= ' var baseIcon = new GIcon(); baseIcon.shadow = "/wp-content/plugins/canalplan/canalplan/markers/shadow.png";	baseIcon.iconSize = new GSize(12, 20);	baseIcon.shadowSize = new GSize(22, 20);	baseIcon.iconAnchor = new GPoint(6, 20);	baseIcon.infoWindowAnchor = new GPoint(9, 2);	baseIcon.infoShadowAnchor = new GPoint(18, 25);';
		 $page2.= ' var icon0 = new GIcon(baseIcon); var icon1 = new GIcon(baseIcon); 		var icon2 = new GIcon(baseIcon); ';
		 $page2.= ' icon0.image = "/wp-content/plugins/canalplan/canalplan/markers/small_green.png"; icon1.image = "/wp-content/plugins/canalplan/canalplan/markers/small_red.png"; icon2.image = "/wp-content/plugins/canalplan/canalplan/markers/small_yellow.png";';
       $page2.= ' var encodedPolyline = new GPolyline.fromEncoded({    color: "#0000FF",   weight: 2, opacity: 0.8, points: "'.$pointstring.'",    levels: "'.$zoomstring.'",zoomFactor: 32, numLevels: 4 });';
		 $page2.= ' map'.$dogooglemap.'.addOverlay(encodedPolyline); 	var bounds = new GLatLngBounds(); for (var i=0; i<encodedPolyline.getVertexCount()-1; i++) {  bounds.extend(encodedPolyline.getVertex(i));}';
		 $page2.= '  var clat = (bounds.getNorthEast().lat() + bounds.getSouthWest().lat())/2; var clng = (bounds.getNorthEast().lng() + bounds.getSouthWest().lng())/2; 	map'.$dogooglemap.'.setCenter(new GLatLng(clat,clng)); map'.$dogooglemap.'.setZoom(map'.$dogooglemap.'.getBoundsZoomLevel(bounds));}';

$page2.=$turnaround.$markertext."</script> <br />";


		$names = array();
		$links = array();
		foreach ($places_array as $place_code) {
		$words=split(":",$place_code);
			$names[] = $place_code;
			$links[] =$mapstuff.$page2 ;
		}
		return str_replace($names,$links , $content);
	}

// Link Maps

	function canal_link_maps($content) {
	    global $wpdb;
		global $post;
		// First we check the content for tags:
                if (preg_match_all('/' . preg_quote('[[CPGMW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
		// If the array is empty then we've no maps so don't do anything!
		if (count($places_array)==0) {return $content;}
		
	if ( get_query_var('feed') || $search=='Y' )  {
                $names = array();
                $links = array();
                 foreach ($places_array as $place_code) {
                $words=split("\|",$place_code);
	                $names[] = "[[CPGMW:" .$place_code . "]]";
	                $links[] = "<b>[Embedded Google Map for ".trim($words[0])."]</b>";
	    }
                return str_replace($names,$links , $content);
		}

		// Get the Googlemap global and if its zero (i.e. first time called on the page) put out the googlemap key
		global $dogooglemap;

        $maptype[s]="G_SATELLITE_MAP";
        $maptype[n]="G_NORMAL_MAP";
        $maptype[h]="G_HYBRID_MAP";
        foreach ($places_array as $place_code) {
		$mapstuff="<br />";
        $words=split("\|",$place_code);
		if ($dogooglemap==0){

		}
		// Increment it
		$dogooglemap=$dogooglemap+1;
		// To allow multiple maps per page (for viewing posts by category) append the dogooglemap id to the end of the map div id
		$mapstuff.= '<div id="map'.$dogooglemap.'" style="width: 500px; height: 600px"></div><script type="text/javascript">';
		$post_id=$post->ID;

unset($missingpoly);
unset($plines);
unset($weights);
unset($polylines);
$missingpoly[]=$words[1];
while ( count($missingpoly)>0 ) {
        reset($missingpoly);
#print "<br>".count($missingpoly)." - ";
#var_dump($missingpoly);
        $sql="select 1 from ".CANALPLAN_POLYLINES." where id='".current($missingpoly)."';";
        #print $sql."<br>";
        $res = mysql_query($sql);
         $rw = mysql_fetch_row($res);
       # print "!!".$rw[0]."H";
if ($rw[0]==1){$polylines[]=current($missingpoly);}
        $sql="select id from ".CANALPLAN_CANALS." where parent='".current($missingpoly)."';";
       # print $sql;
        unset($missingpoly2);
        $res = mysql_query($sql);
        while( $rw = mysql_fetch_row($res)){
        #print $rw[0]." - ";
        $missingpoly[]=$rw[0];
}
#Get the polyline. If there is one put it into the $polyline[].
#Get a list of places where this id is the parent and put them into $missingpoly[]
#Remove the element from the array
$missingpoly=array_slice($missingpoly,1);
#$missingpoly=array_merge($missingpoly,$missingpoly2);
}
$markertext="";
#var_dump($polylines);
foreach ($polylines as $polyline) {
        $sql="select pline,weights from ".CANALPLAN_POLYLINES." where id='".$polyline."';";
       # print $sql."<br>";
        $res = mysql_query($sql);
         $rw = mysql_fetch_row($res);
$plines[]=$rw[0];
$weights[]=$rw[1];
}
#var_dump($plines);


		$page2= ' if (GBrowserIsCompatible()) { if(document.implementation.hasFeature( "http://www.w3.org/TR/SVG11/feature#SVG","1.1")){  _mSvgEnabled = true;  _mSvgForced  = true;}';
		$page2.= ' var map'.$dogooglemap.' = new GMap2(document.getElementById("map'.$dogooglemap.'")); map'.$dogooglemap.'.setCenter(new GLatLng(52.97035617,-2.508565798), 9); map'.$dogooglemap.'.addControl(new GSmallMapControl());	map'.$dogooglemap.'.addControl(new GMapTypeControl());  map'.$dogooglemap.'.setMapType(G_NORMAL_MAP); map'.$dogooglemap.'.setZoom(9);';
                $page2.='var globalbounds = new GLatLngBounds(); var ads'.$dogooglemap.' = new GAdsManager(map'.$dogooglemap.',"pub-4296098225870941",{maxAdsOnMap : 10, style: "icon", channel: "5900391315", minZoomLevel: 0}); ads'.$dogooglemap.'.enable();';
		$page2.= ' var baseIcon = new GIcon(); baseIcon.shadow = "/wp-content/plugins/canalplan/canalplan/markers/shadow.png";	baseIcon.iconSize = new GSize(12, 20);	baseIcon.shadowSize = new GSize(22, 20);	baseIcon.iconAnchor = new GPoint(6, 20);	baseIcon.infoWindowAnchor = new GPoint(9, 2);	baseIcon.infoShadowAnchor = new GPoint(18, 25);';
		$page2.= ' var icon0 = new GIcon(baseIcon); var icon1 = new GIcon(baseIcon); 		var icon2 = new GIcon(baseIcon); ';
		$page2.= ' icon0.image = "/wp-content/plugins/canalplan/canalplan/markers/small_green.png"; icon1.image = "/wp-content/plugins/canalplan/canalplan/markers/small_red.png"; icon2.image = "/wp-content/plugins/canalplan/canalplan/markers/small_yellow.png";';
for ($i=0;$i<count($plines);$i++){
                $page2.= ' var encodedPolyline = new GPolyline.fromEncoded({    color: "#0000ff",   weight: 3, opacity: 0.8, points: "'.$plines[$i].'",    levels: "'.$weights[$i].'",zoomFactor: 32, numLevels: 4 });';
		$page2.= ' map'.$dogooglemap.'.addOverlay(encodedPolyline); ';
		$page2.=' var marker'.$dogooglemap.'0'.$i.'= new GMarker(encodedPolyline.getVertex(0), {icon:icon0, draggable: false});';
		$page2.=' var marker'.$dogooglemap.'1'.$i.' = new GMarker(encodedPolyline.getVertex(encodedPolyline.getVertexCount()-1), {icon:icon0, draggable: false});';
		$page2.=' map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.'0'.$i.');';
	        $page2.=' map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.'1'.$i.');';
		$page2.= 'for (var i=0; i<encodedPolyline.getVertexCount()-1; i++) {  globalbounds.extend(encodedPolyline.getVertex(i));} ';
}

                $page2.='var clat = (globalbounds.getNorthEast().lat() + globalbounds.getSouthWest().lat())/2; var clng = (globalbounds.getNorthEast().lng() + globalbounds.getSouthWest().lng())/2; 	map' .$dogooglemap.'.setCenter(new GLatLng(clat,clng)); map'.$dogooglemap.'.setZoom(map'.$dogooglemap.'.getBoundsZoomLevel(globalbounds));}';


$page2.=$turnaround.$markertext."</script> <br />";
      $names[] = "[[CPGMW:" .$place_code . "]]";
      $links[] = $mapstuff.$page2;
      }

return str_ireplace($matches[0], $links, $content);
	}


function canal_place_maps($content,$mapblog_id=NULL,$post_id=NULL) {
$gazstring=CANALPLAN_URL.'gazetteer.cgi?id=';
    // First we check the content for tags:
    if (preg_match_all('/' . preg_quote('[[CPGM:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }

    // If the array is empty then we've no links so don't do anything!
    if (count($places_array)==0) {return $content;}

if (isset($mapblog_id)) {} else { $mapblog_id=$wpdb->blogid;}
if (isset($post_id)) {} else {$post_id=$post->ID;
                if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
    $names = array();
    $links = array();
    // Get the Googlemap global and if its zero (i.e. first time called on the page) put out the googlemap key
    global $dogooglemap;
    global $wpdb;
    global $post;
    #$mapblog_id=$wpdb->blogid;
	#if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}
    if ( get_query_var('feed')) {

    foreach ($places_array as $place_code) {
    $words=split("\|",$place_code);
	    $names[] = "[[CPGM:" .$place_code . "]]";
	    $links[] = "<b>[Embedded Google Map for ".trim($words[0])."]</b>";
	    }
    return str_ireplace($names, $links, $content);
    }
    $maptype[s]="G_SATELLITE_MAP";
    $maptype[n]="G_NORMAL_MAP";
    $maptype[h]="G_HYBRID_MAP";
    foreach ($places_array as $place_code) {
    $words=split("\|",$place_code);
    $sql="select lat,`long` from ".CANALPLAN_CODES." where canalplan_id='".$words[1]."'";
	 $res = mysql_query($sql);
    $row = mysql_fetch_array($res);
    // set defaults
	$options[size]=300;
	$options[zoom]=18;
	$options[type]=s;
	$options[lat]=$row[lat];
	$options[long]=$row[long];
$opts=split(",",$words[2]);
 foreach ($opts as $opt) {
	 $opcode=split("=",$opt);
	 $options[$opcode[0]]=$opcode[1];
 }
    $mapstuff="<br />";
    if ($dogooglemap==0){

$mapstuff.= <<< EOGS
      <script>
      var baseIcon = new GIcon();
      baseIcon.shadow = "/wp-content/plugins/canalplan/canalplan/markers/shadow.png";
      baseIcon.iconSize = new GSize(19, 33);
      baseIcon.shadowSize = new GSize(36, 33);
      baseIcon.iconAnchor = new GPoint(9, 33);
      baseIcon.infoWindowAnchor = new GPoint(9, 2);
      baseIcon.infoShadowAnchor = new GPoint(18, 25);
      var icon0 = new GIcon(baseIcon);
      icon0.iconSize = new GSize(12, 20);
      icon0.shadowSize = new GSize(22, 20);
      icon0.iconAnchor = new GPoint(6, 20);
      icon0.image = "/wp-content/plugins/canalplan/canalplan/markers/small_green.png";
      </script>
EOGS;
#$mapstuff.=$mapstuff2;
    }
    // Increment it
    $dogooglemap=$dogooglemap+1;
    // To allow multiple maps per page/entry append the dogooglemap id to the end of the map div id

    $mapstuff.= '<div id="map'.$dogooglemap.'" style="width: '.$options[size].'px; height: '.$options[size].'px"></div><script type="text/javascript" defer="defer"> ';
    $mapstuff.='var point = new Array(); var map'.$dogooglemap.' = new GMap2(document.getElementById("map'.$dogooglemap.'"));';
    $mapstuff.='map'.$dogooglemap.'.addControl(new GSmallMapControl()); map'.$dogooglemap.'.addControl(new GMapTypeControl()); map'.$dogooglemap.'.setCenter(new GLatLng('.$options[lat].','.$options[long].'), '.$options[zoom].', '.$maptype[$options[type]].');';
#$mapstuff.=' var adsManagerOptions = { maxAdsOnMap : 5, style: G_ADSMANAGER_STYLE_ICON", channel: "5900391315" };';
    $mapstuff.='var ads'.$dogooglemap.' = new GAdsManager(map'.$dogooglemap.',"pub-4296098225870941",{maxAdsOnMap : 10, style: G_ADSMANAGER_STYLE_ICON, channel: "5900391315", minZoomLevel: 0}); ads'.$dogooglemap.'.enable();';
   $mapstuff.='point['.$dogooglemap.'] = new GLatLng('.$options[lat].','.$options[long].'); var marker'.$dogooglemap.' = new GMarker(point['.$dogooglemap.'], {icon:icon0, title:"'.trim($words[0]).'"});';
   $mapstuff.='map'.$dogooglemap.'.addOverlay(marker'.$dogooglemap.');';
   $mapstuff.='GEvent.addListener(marker'.$dogooglemap.', "click", function(){marker'.$dogooglemap.'.openInfoWindowHtml("'.trim($words[0]).'"); });';
   $mapstuff.='map'.$dogooglemap.'.addOverlay(new Textbox(new GLatLng('.$options[lat].','.$options[long].'),"<b>'.trim($words[0]).'</b>"));';
    $mapstuff.='</script>';
      $names[] = "[[CPGM:" .$place_code . "]]";
      $links[] = $mapstuff;
      }
return str_ireplace($matches[0], $links, $content);
	}

        function canal_stats($content,$mapblog_id=NULL,$post_id=NULL) {
                // First we check the content for tags:
		global $blog_id,$wpdb,$post;
                if (preg_match_all('/' . preg_quote('[[CPRS') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[0]; }

                // If the array is empty then we've no links so don't do anything!
                if (count($places_array)==0) {return $content;}
	if (isset($mapblog_id)) {} else { $mapblog_id=$blog_id;}
	if (isset($post_id)) {} else {$post_id=$post->ID;
                if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
      $sql="select distance,`locks`,start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$mapblog_id." and  post_id=".$post_id;
		$res = mysql_query($sql);
		$row = mysql_fetch_array($res);
		$sql="select totalroute,uom from ".CANALPLAN_ROUTES." cpr, ".CANALPLAN_ROUTE_DAY." crd where cpr.route_id= crd.route_id and cpr.blog_id=crd.blog_id and crd.blog_id=".$mapblog_id." and  crd.post_id=".$post_id;
		$res3 = mysql_query($sql);
		$row3 = mysql_fetch_array($res3);
                $dformat=$row3[uom];
		$places=split(",",$row3[totalroute]);
		#print "!!!".$places[$row[start_id]-1]."££££".$places[$row[end_id]];
#		$sql="select place_name from canalplan_codes where canalplan_id='".$places[$row[start_id]]."'";
$sql2="set names 'utf8';";
$zed = mysql_query($sql2);
$sql="select place_name from ".CANALPLAN_FAVOURITES." where canalplan_id='".$places[$row[start_id]]."' and blog_id=".$mapblog_id." union select place_name from ".CANALPLAN_CODES." where canalplan_id='".$places[$row[start_id]]."' and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id='".$places[$row[start_id]]."' and blog_id=".$mapblog_id.")";
#print $sql;
		$res2 = mysql_query($sql);
		$row2 = mysql_fetch_array($res2);
		$start_name=$row2[place_name];
#		$sql="select place_name from canalplan_codes where canalplan_id='".$places[$row[end_id]]."'";
$sql="select place_name from ".CANALPLAN_FAVOURITES." where canalplan_id='".$places[$row[end_id]]."' and blog_id=".$mapblog_id." union select place_name from ".CANALPLAN_CODES." where canalplan_id='".$places[$row[end_id]]."' and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id='".$places[$row[end_id]]."' and blog_id=".$mapblog_id.")";

		$res2 = mysql_query($sql);
		$row2 = mysql_fetch_array($res2);
		$end_name=$row2[place_name];

		$names = array();
		$links = array();
		foreach ($places_array as $place_code) {
		$words=split(":",$place_code);
			$names[] = $place_code;
			$links[] = "From [[CP:".$start_name."|".$places[$row[start_id]]."]] to [[CP:".$end_name."|".$places[$row[end_id]]."]], ".format_distance($row[distance],$row[locks],$dformat,2);
		}
		return str_ireplace($names, $links, $content);
	}



	function canal_linkify($content) {
		global $post,$blog_id;
		// First we check the content for tags:
		if (preg_match_all('/' . preg_quote('[[CP') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
		// If the array is empty then we've no links so don't do anything!
		#if (count($places_array)==0) {return $content;}
                $names = array();
		$links = array();
                if (preg_match_all('/' . preg_quote('[[CP:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; 
                $gazstring=CANALPLAN_URL.'gazetteer.cgi?id=';
		$x="SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='canalkey'";
	#	var_dump($x);
		$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='canalkey'");
		if (mysql_num_rows($r2)==0) {
		     $api="";
		}
		else
		{
			$rw = mysql_fetch_array($r2,MYSQL_ASSOC);
		#	var_dump($rw);
			$api=explode("|",$rw['pref_value']);
			$blog_url=get_bloginfo('url');
			$date = date("Ymd",strtotime($post->post_date));
			$link=urlencode(str_replace($blog_url,"",get_permalink($post->ID)));
			$title=urlencode($post->post_title);
	
		} 
		foreach ($places_array as $place_code) {
		$words=split("\|",$place_code);
			$names[] = "[[CP:" .$place_code . "]]";
			if ($api[0]=="") {
				$links[] = "<a href='".CANALPLAN_GAZ_URL.$words[1]."' target='gazetteer'  title='Link to ".trim($words[0])."'>".trim($words[0])."</a>";
			} else 
			{
				$links[] = "<a href='". CANALPLAN_GAZ_URL .$words[1]. "?blogkey=".$api[0]."&title=".$title."&blogid=".$api[1]."&date=".$date."&url=".$link."' target='gazetteer' title='Link to ".trim($words[0])."'>".trim($words[0])."</a>";
			}
		}
                }
                if (preg_match_all('/' . preg_quote('[[CPW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; 
                $gazstring=CANALPLAN_URL.'waterway.cgi?id=';
		foreach ($places_array as $place_code) {
		$words=split("\|",$place_code);
			$names[] = "[[CPW:" .$place_code . "]]";
			$links[] = "<a href='".$gazstring.$words[1]."' target='gazetteer'  title='Link to ".trim($words[0])."'>".trim($words[0])."</a>";
		}}
		return str_ireplace($names, $links, $content);
	}

	function canal_options_page() {
		global $wpdb;
		if (!current_user_can('manage_options')) {
			die('You don&#8217;t have sufficient permission to access this file.');
		}
		if (isset($_POST['update'])) {
			check_admin_referer('canalplan-autolinker-update-options');
			update_option('canalplan-autolinker-begin', $wpdb->escape($_POST['begin']));
			update_option('canalplan-autolinker-end', $wpdb->escape($_POST['end']));
			echo '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
		}
?>

<?php
	}
}



function wp_canalplan_admin_pages() {

	$base_dir=dirname(__FILE__).'/admin-pages/'; 
	$hook=add_menu_page('CanalPlan AC Overview', 'CanalPlan AC', 8,$base_dir.'cp-admin-menu.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: General Options', 'General Options',8,  $base_dir.'cp-admin-general.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Home Mooring', 'Home Mooring', 8, $base_dir.'cp-admin-home.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Favourites', 'Favourites', 8,  $base_dir.'cp-admin-fav.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Import Route', 'Import Routes', 8,  $base_dir.'cp-import_route.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Manage Routes', 'Manage Routes', 8,  $base_dir.'cp-manage_route.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Diagnostics', 'Diagnostics', 8,  $base_dir.'cp-admin-diagnostics.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Bulk Notify', 'Bulk Notify', 8,  $base_dir.'cp-admin-update.php');
}


function canalplan_header($blah){
global $blog_id,$wpdb;
if (!defined('CANALPLAN_GMAP_KEY')) {
$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=".$blog_id." and pref_code='apikey'");
if (mysql_num_rows($r2)==0) {
     $api="";
}
else
{
	$rw = mysql_fetch_array($r,MYSQL_ASSOC);
	$api=$rw['pref_value'];
} 
} else {$api=CANALPLAN_GMAP_KEY;}
echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$api.'" type="text/javascript"></script>';
#echo '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"> </script>';
echo '<meta name="verify-v1" content="gh8YjrQxNNQP2cet22ZdfXucI+py1YHC/6eczI1ljHc=" />';
return $blah;
}

function canalplan_footer($blah) {
	echo "\n<!-- Canalplan AC code revision : ".CANALPLAN_CODE_RELEASE." -->\n";
	echo "<p style='font-size:80%'>Canalplan Interlinking provided by <a href='http://wordpress.org/extend/plugins/canalplan-ac/'> Canalplan AC Plugin </a></p>";
	return $blah;
}

function blroute (){

$routeid = $_REQUEST['routeid'];
$routeid = preg_replace('{/$}', '', $routeid);

if (!isset($routeid)){$routeid=0;}
if ($routeid<=0){$routeid=0;}
global $wpdb,$blog_id;


$blroute="";
if ($routeid==0){
#	$blroute .="<h2>Available Trip Reports</h2>";
$sql2="set names 'utf8';";
$zed = mysql_query($sql2);
if ($wpdb->blogid==1) {
$sql="select route_id,title,blog_id from ".CANALPLAN_ROUTES." where status=3";
}
else
{
$sql="select route_id, title,description,blog_id from ".CANALPLAN_ROUTES." where status=3 and blog_id=$blog_id";
}
if (!defined('CANALPLAN_ROUTE_SLUG')){
$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where blog_id=-1 and pref_code='routeslug'");
if (mysql_num_rows($r2)==0) {
     $routeslug="UNDEFINED!";
}
else
{
while($rw = mysql_fetch_array($r2))
{
$routeslug=$rw['pref_value'];
}
} 
} else {$routeslug=CANALPLAN_ROUTE_SLUG;}
$res = mysql_query($sql);

$blroute .="<ol>";
while ($row = mysql_fetch_array($res)) {;
if ($wpdb->blogid==1) {$blroute .='<li><a href='.get_blog_option($row[blog_id],"siteurl").'/'.$routeslug.'/'.$row[route_id].' target=\"_new\">'.$row[title].'</a> ( from '. get_blog_option($row[blog_id],'blogname').' )  </li>';}
else
{
$blroute .='<li><a href='.get_blog_option($row[blog_id],"siteurl").'/'.$routeslug.'/'.$row[route_id].' target=\"_new\">'.$row[title].'</a> ('.$row[description].')</li>';}
}
$blroute .="</ol><br><br>";

}

else

{
$sql2="set names 'utf8';";
$zed = mysql_query($sql2);
$sql="select description, totalroute from ".CANALPLAN_ROUTES." where route_id=".$routeid." and blog_id=".$wpdb->blogid;
$res = mysql_query($sql);
$row = mysql_fetch_array($res);
$blroute .="<h2>".$row[description]."</h2>";
$pointstring = "";
$zoomstring = "";
$lat = 0;
$long = 0;
$lpoint="";
$lpointb1="";
$x=3;
$y=-1;
$places=split(",",$row[totalroute]);
$lastid=end($places);
$firstid=reset($places);
$turnaround="";
foreach ($places as $place) {
$sql="select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id='".$place."'";
$res = mysql_query($sql);
$row = mysql_fetch_array($res);
if ($place==$firstid){$firstname=$row[place_name];}
if ($place==$lastid){$lastname=$row[place_name];}
$points=$place.",".$row[lat].",".$row[long];
      $pointx = $row[lat];
        $pointy = $row[long];
#	$blroute .="!".$pointx."!".$pointy."!<br>";
        $nlat = floor($pointx * 1e5);
        $nlong = floor($pointy * 1e5);
        $pointstring .= ascii_encode($nlat - $lat) . ascii_encode($nlong -
$long);
        $zoomstring .= 'B';
        $lat = $nlat;
        $long = $nlong;
        $cpoint=$row[place_name].",".$row[lat].",".$row[long];
      # $blroute .="$$$$".$cpoint."!!!".$lpointb1."£££<br>";
        if ($cpoint==$lpointb1) {
	$lpoints=split(",",$lpoint);
#	$blroute .="Turn round at ".$lpoints[0];
$turnaround.='var marker'.$x.' = new GMarker(encodedPolyline.getVertex('.$y.'),{icon:icon2, draggable: false, title: "Turn Round here : '.$lpoints[0].'"});';
$turnaround.='map.addOverlay(marker'.$x.');';
 $x=$x+1;
}
$lpointb1=$lpoint;
$y=$y+1;
$lpoint=$cpoint;
}
if ($firstid==$lastid) {
	$markertext='var marker0 = new GMarker(encodedPolyline.getVertex(0), {icon:icon0, draggable: false, title: "Start/Finish : '.$firstname.'"});';
	$markertext.='map.addOverlay(marker0);';
}
else
{
	$markertext='var marker0 = new GMarker(encodedPolyline.getVertex(0), {icon:icon0, draggable: false, title: "Start : '.$firstname.'"});';
	$markertext.='var marker1 = new GMarker(encodedPolyline.getVertex(encodedPolyline.getVertexCount()-1), {icon:icon1, draggable: false, title:"Finish : '.$lastname.'"});';
	$markertext.='map.addOverlay(marker0);';
	$markertext.='map.addOverlay(marker1);';
}
#$blroute .=$pointstring;


$page= <<< EOGS
	    <div id="map" style="width: 500px; height: 600px"></div>

	  <script type="text/javascript">


  if (GBrowserIsCompatible()) {
	// Make sure that SVG is on.
	if(document.implementation.hasFeature(
		"http://www.w3.org/TR/SVG11/feature#SVG","1.1")){
	  _mSvgEnabled = true;
	  _mSvgForced  = true;
	}
	// Set up the map.
	        var map = new GMap2(document.getElementById("map"));
	  map.setCenter(new GLatLng(52.97035617,-2.508565798), 9);
	    map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl());
	    map.setMapType(G_NORMAL_MAP);
	    map.setZoom(9);
var publisher_id = 'pub-4296098225870941';

var adsManagerOptions = {
  maxAdsOnMap : 10,
  style: 'icon',
  // The channel field is optional - replace this field with a channel number 
  // for Google AdSense tracking
  channel: '5900391315'  
};
adsManager = new GAdsManager(map, publisher_id, adsManagerOptions);
adsManager.enable();
var baseIcon = new GIcon();
baseIcon.shadow = "/wp-content/plugins/canalplan/canalplan/markers/shadow.png";
baseIcon.iconSize = new GSize(12, 20);
baseIcon.shadowSize = new GSize(22, 20);
baseIcon.iconAnchor = new GPoint(6, 20);
baseIcon.infoWindowAnchor = new GPoint(9, 2);
baseIcon.infoShadowAnchor = new GPoint(18, 25);
var icon0 = new GIcon(baseIcon);
var icon1 = new GIcon(baseIcon);
var icon2 = new GIcon(baseIcon);
icon0.image = "/wp-content/plugins/canalplan/canalplan/markers/small_green.png";
icon1.image = "/wp-content/plugins/canalplan/canalplan/markers/small_red.png";
icon2.image = "/wp-content/plugins/canalplan/canalplan/markers/small_yellow.png";
var encodedPolyline = new GPolyline.fromEncoded({
    color: "#0000FF",
    weight: 3,
EOGS;
$page.='points: "'.$pointstring.'",    levels: "'.$zoomstring.'",';
$page2= <<< BARF2
    zoomFactor: 32,
    numLevels: 4, opacity: 0.8
});

				map.addOverlay(encodedPolyline);
				var bounds = new GLatLngBounds();
for (var i=0; i<encodedPolyline.getVertexCount()-1; i++) {
//	alert(encodedPolyline.getVertex(i));
  bounds.extend(encodedPolyline.getVertex(i));
					}
//alert(map.getBoundsZoomLevel(bounds));
					var clat = (bounds.getNorthEast().lat() + bounds.getSouthWest().lat())/2;
					var clng = (bounds.getNorthEast().lng() + bounds.getSouthWest().lng())/2;
					map.setCenter(new GLatLng(clat,clng));
map.setZoom(map.getBoundsZoomLevel(bounds));
}
BARF2;

$page2.=$turnaround.$markertext;
$page3=<<< BARF3
	    </script> <br />
BARF3;
$blroute .= $page;
$blroute .= $page2;
$blroute .= $page3;
$blroute .= "<h2>Blog Entries for this trip</h2>";
$sql2="set names 'utf8';";
$zed = mysql_query($sql2);

$sql="select id, post_title from ".$wpdb->posts." where id in (select post_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$wpdb->blogid." and  route_id=".$routeid." ) order by post_date";
$res = mysql_query($sql);
$blroute .="<ol>";

while ($row = mysql_fetch_array($res)) {
$link = get_blog_permalink( $blog_id, $row[id] ) ;
$blroute .="<li><a href=\"$link\" target=\"_new\">$row[post_title]</a> </li>";
}
$blroute .="</ol>";

}

return $blroute ;
}

function blogroute_insert($content)
{
  if (preg_match('{BLOGGEDROUTES}',$content))
    {
      $content = str_replace('{BLOGGEDROUTES}',blroute(),$content);
    }
  return $content;
}


function canal_activate() {
#add_option('canalplan-autolinker-begin', '[');
	#add_option('canalplan-autolinker-end', ']');
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
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
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
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_LINK ;


	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_OPTIONS.' (
	  `blog_id` bigint(20) NOT NULL default 0,
	  `pref_code` varchar(20) NOT NULL ,
	  `pref_value` varchar(240) NOT NULL ,
	  PRIMARY KEY  (`blog_id`,`pref_code`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
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
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_ROUTES ;


	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_CANALS.' (
	  `id` varchar(4) NOT NULL,
	  `parent` varchar(4) default NULL,
	  `name` varchar(40) NOT NULL,
	  `fullname` varchar(120) NOT NULL,
	  PRIMARY KEY  (`id`),
	  KEY `parent` (`parent`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_CANALS ;


	$sql='CREATE TABLE IF NOT EXISTS '.CANALPLAN_POLYLINES.' (
	  `id` varchar(5) NOT NULL,
	  `pline` longtext,
	  `weights` longtext,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
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
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';

	$result = $wpdb->query( $sql );
	if ($result === false) $errors[] = __('Failed to create ') . CANALPLAN_POLYLINES ;

	if ($errors) {
		foreach ($errors as $errormsg) {
			_e("$errormsg<br />\n");
		}
		return;
	}

}

add_action('activated_plugin','save_error');
function save_error(){
    update_option('plugin_error',  ob_get_contents());
}

add_filter('the_content','blogroute_insert');
add_action('wp_head', 'canalplan_header');
add_action('wp_footer', 'canalplan_footer');
add_action('init', array('CanalPlanAutolinker', 'canal_init'));
register_activation_hook(__FILE__, 'canal_activate');
add_action('admin_menu', 'wp_canalplan_admin_pages');





?>
