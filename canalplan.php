<?php
/*
Plugin Name: CanalPlan Integration
Plugin URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Description: Provides features to integrate your blog with <a href="http://www.canalplan.eu">Canalplan AC</a> - the Canal Route Planner.
Version: 2.8
Author: Steve Atty
Author URI: http://blogs.canalplan.org.uk/steve/
 *
 *
 * Copyright 2011 - 2012 Steve Atty (email : posty@tty.org.uk)
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
define ('CANALPLAN_BASE','http://www.canalplan.org.uk');
define ('CANALPLAN_URL',CANALPLAN_BASE.'/cgi-bin/');
define ('CANALPLAN_GAZ_URL',CANALPLAN_BASE.'/gazetteer/');
define ('CANALPLAN_MAX_POST_PROCESS',20);
define('CANALPLAN_CODE_RELEASE','2.8 r00');

global $table_prefix, $wp_version,$wpdb,$db_prefix;
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

function canalplan_add_custom_box() {
    add_meta_box( 'myplugin_sectionid', __( 'CanalPlan Tags', 'myplugin_textdomain' ), 
                'myplugin_inner_custom_box', 'post', 'advanced' );
    add_meta_box( 'myplugin_sectionid', __( 'CanalPlan Tags', 'myplugin_textdomain' ), 
                'myplugin_inner_custom_box', 'page', 'advanced' );
}
   
/* Prints the inner fields for the custom post/page section */
function myplugin_inner_custom_box() {
 	echo '<input type="hidden" name="myplugin_noncename" id="myplugin_noncename" value="' . 
    	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	global $wpdb,$blog_id;
	echo '<script type="text/javascript"> var linktype=1; cplogid='.$blog_id.'</script>';
	echo '<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplanfunctions.js" DEFER></script>';
	echo '<script type="text/javascript" src="../wp-content/plugins/canalplan-ac/canalplan/canalplan_actb.js"></script>';
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


function canal_init() {
	add_filter('the_content',  'canal_stats');
	add_filter('the_content',  'canal_route_maps');
	add_filter('the_content',  'canal_place_maps');
	add_filter('the_content',  'canal_link_maps');
	add_filter('the_content',  'canal_linkify');
	add_filter('the_excerpt',  'canal_stats');
	add_filter('the_excerpt',  'canal_route_maps');
	add_filter('the_excerpt',  'canal_place_maps');
	add_filter('the_excerpt',  'canal_link_maps');
	add_filter('the_excerpt',  'canal_linkify');
   	global $dogooglemap;
   	$dogooglemap=0;
}

function canal_route_maps($content,$mapblog_id=NULL,$post_id=NULL,$search=NULL) {
    	global $wpdb,$post,$blog_id,$google_map_code,$dogooglemap;
	// First we check the content for tags:
	if (preg_match_all('/' . preg_quote('[[CPRM') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[0]; }
	// If the array is empty then we've no maps so don't do anything!
	if (count($places_array)==0) {return $content;}
        if (isset($mapblog_id)) {} else { $mapblog_id=$blog_id;}
        if (isset($post_id)) {} else {$post_id=$post->ID;
        if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
	if ( get_query_var('feed') || $search=='Y' || is_feed() )  {
		$names = array();
		$links = array();
		foreach ($places_array as $place_code) {
		$words=split(":",$place_code);
		        $names[] = $place_code;
		        $links[] ="<b>[Google Route Map embedded here]</b>" ;
		}
		return str_replace($names,$links , $content);
	}
	$mapstuff="<br />";
	$dogooglemap=$dogooglemap+1;
	$canalplan_options = get_option('canalplan_options');
	$post_id=$post->ID;
	// if (!isset($post_id)) {return;}
 	$sql="select distance,`locks`,start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$mapblog_id." and  post_id=".$post_id;
	$res = mysql_query($sql);
	$row = mysql_fetch_array($res);
	$sql="select totalroute from ".CANALPLAN_ROUTES." cpr, ".CANALPLAN_ROUTE_DAY." crd where cpr.route_id= crd.route_id and cpr.blog_id=crd.blog_id and crd.blog_id=".$mapblog_id." and  crd.post_id=".$post_id;
	$res3 = mysql_query($sql) or trigger_error('Query failed: ' . $sql, E_USER_ERROR);
	$mid_point=round(mysql_num_rows($res3)/2,PHP_ROUND_HALF_UP);
	$place_count=0;
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
   	$maptype[S]="SATELLITE";
   	$maptype[R]="ROADMAP";
   	$maptype[T]="TERRAIN";
   	$maptype[H]="HYBRID";
	$options['zoom']=$canalplan_options["canalplan_rm_zoom"];
	$options['type']=$canalplan_options["canalplan_rm_type"];
	$options['lat']=53.4;
	$options['long']=-2.8;
	$options['height']=$canalplan_options["canalplan_rm_height"];
	$options['width']=$canalplan_options["canalplan_rm_width"];
	$options['rgb']=$canalplan_options["canalplan_rm_r_hex"].$canalplan_options["canalplan_rm_g_hex"].$canalplan_options["canalplan_rm_b_hex"];
	$options['brush']=$canalplan_options["canalplan_rm_weight"];
	$words=substr($matches[1][0],1);
	$opts=split(",",$words);
	foreach ($opts as $opt) {
		 $opcode=split("=",$opt);
		 $options[$opcode[0]]=strtoupper($opcode[1]);
	}
	$mapstuff.= '<div id="map_canvas_'.$dogooglemap.'" style="width: '.$options['width'].'px; height: '.$options['height'].'px"></div>';
	foreach ($dayroute as $place) {
		$sql2="set names 'utf8';";
		$zed = mysql_query($sql2);
		$sql="select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id='".$place."'";
		$res = mysql_query($sql);
		$row = mysql_fetch_array($res);
		if (count($row) > 2) {
		if($place_count==$mid_point) {
			$centre_lat=$row[lat];
			$centre_long=$row[long];
		}	
		$place_count=$place_count+1;
		if ($place==$firstid){
			$firstname=$row[place_name];
			$first_lat=$row[lat];
			$first_long=$row[long];
		}
		if ($place==$lastid){
			$lastname=$row[place_name];
			$last_lat=$row[lat];
			$last_long=$row[long];
		}
	//	echo count($row).' - '.$place." - ".$row[place_name]." : ".$row[lat]." - ".$row[long]."<br />";
		$points=$place.",".$row[lat].",".$row[long];
	     	$pointx = $row[lat];
	        $pointy = $row[long];;
	        $nlat = floor($pointx * 1e5);
	        $nlong = floor($pointy * 1e5);
	        $pointstring .= ascii_encode($nlat - $lat) . ascii_encode($nlong -  $long);
	        $zoomstring .= 'B';
	        $lat = $nlat;
	        $long = $nlong;
	        $cpoint=$row[place_name].",".$row[lat].",".$row[long];
	        if ($cpoint==$lpointb1) {
			$lpoints=split(",",$lpoint);
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
	$google_map_code.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$centre_lat.','.$centre_long.'),';
      	$google_map_code.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: false,';
      	$google_map_code.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
      	$google_map_code.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
      	$google_map_code.='  var polyOptions'.$dogooglemap.' = {strokeColor: "#'.$options['rgb'].'", strokeOpacity: 1.0,strokeWeight: '.$options['brush'].' }; ';
	$i=1;
	$google_map_code.=' var line'.$dogooglemap.'_'.$i.' = new google.maps.Polyline(polyOptions'.$dogooglemap.');';
 	$google_map_code.=' line'.$dogooglemap.'_'.$i.'.setPath(google.maps.geometry.encoding.decodePath("'.$pointstring.'"));';
 	$google_map_code.=' line'.$dogooglemap.'_'.$i.'.setMap(map'.$dogooglemap.');';
	$google_map_code.='var bounds'.$dogooglemap.' = new google.maps.LatLngBounds();';
	$google_map_code.='line'.$dogooglemap.'_'.$i.'.getPath().forEach(function(latLng) {bounds'.$dogooglemap.'.extend(latLng);});';
	$google_map_code.='map'.$dogooglemap.'.fitBounds(bounds'.$dogooglemap.');';
	$google_map_code.=$turnaround.$markertext;
	$names = array();
	$links = array();
	foreach ($places_array as $place_code) {
	$words=split(":",$place_code);
		$names[] = $place_code;
		$links[] =$mapstuff;
	}
	return str_replace($names,$links , $content);
}

function canal_link_maps($content) {
   	 global $wpdb,$post,$dogooglemap,$google_map_code;
	// First we check the content for tags:
        if (preg_match_all('/' . preg_quote('[[CPGMW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
	// If the array is empty then we've no maps so don't do anything!
	if (count($places_array)==0) {return $content;}
	$canalplan_options = get_option('canalplan_options');
	if ( get_query_var('feed') || $search=='Y' || is_feed() )  {
		$names = array();
		$links = array();
		 foreach ($places_array as $place_code) {
			$words=split("\|",$place_code);
		        $names[] = "[[CPGMW:" .$place_code . "]]";
		        $links[] = "<b>[Embedded Google Map for ".trim($words[0])."]</b>";
	    	}
		return str_replace($names,$links , $content);
	}
   		$maptype[S]="SATELLITE";
	   	$maptype[R]="ROADMAP";
	   	$maptype[T]="TERRAIN";
	   	$maptype[H]="HYBRID";
	foreach ($places_array as $place_code) {
	$options['zoom']=$canalplan_options["canalplan_rm_zoom"];
	$options['type']=$canalplan_options["canalplan_rm_type"];
	$options['lat']=53.4;
	$options['long']=-2.8;
	$options['height']=$canalplan_options["canalplan_rm_height"];
	$options['width']=$canalplan_options["canalplan_rm_width"];
	$options['rgb']=$canalplan_options["canalplan_rm_r_hex"].$canalplan_options["canalplan_rm_g_hex"].$canalplan_options["canalplan_rm_b_hex"];
	$options['brush']=$canalplan_options["canalplan_rm_weight"];
		$mapstuff="<br />";
		$words=split("\|",$place_code);
		$opts=split(",",$words[2]);
		foreach ($opts as $opt) {
			 $opcode=split("=",$opt);
			 $options[$opcode[0]]=strtoupper($opcode[1]);
		}
		$dogooglemap=$dogooglemap+1;
		$mapstuff.= '<div id="map_canvas_'.$dogooglemap.'"  style="width: '.$options['width'].'px; height: '.$options['height'].'px"></div>';
		$post_id=$post->ID;
		unset($missingpoly);
		unset($plines);
		unset($weights);
		unset($polylines);
		$missingpoly[]=$words[1];
		#$sql=" select lat,`long` from ".CANALPLAN_CODES.' where canalplan_id in (select place1 from '.CANALPLAN_LINK.' where waterway="'.$words[1].'") limit 1';
	$sql2=' select lat,`long` from '.CANALPLAN_CODES.' where canalplan_id in (select place1 from '.CANALPLAN_LINK.' where waterway in (select id from '.CANALPLAN_CANALS.' where parent="'.$words[1].'" or id="'.$words[1].'")) limit 1';
		$res = mysql_query($sql2);
		 $rw = mysql_fetch_row($res);
		$centre_lat=$rw[0];
		$centre_long=$rw[1];
		while ( count($missingpoly)>0 ) {
			reset($missingpoly);
			$sql="select 1 from ".CANALPLAN_POLYLINES." where id='".current($missingpoly)."';";
			$res = mysql_query($sql);
			 $rw = mysql_fetch_row($res);
			if ($rw[0]==1){$polylines[]=current($missingpoly);}
			$sql="select id from ".CANALPLAN_CANALS." where parent='".current($missingpoly)."';";
			unset($missingpoly2);
			$res = mysql_query($sql);
			while( $rw = mysql_fetch_row($res)){
				$missingpoly[]=$rw[0];
			}
	#Get the polyline. If there is one put it into the $polyline[].
	#Get a list of places where this id is the parent and put them into $missingpoly[]
	#Remove the element from the array
		$missingpoly=array_slice($missingpoly,1);
		}
		$markertext="";
		$i=1;
	      	$google_map_code.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$centre_lat.','.$centre_long.'),';
	      	$google_map_code.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: false,';
	      	$google_map_code.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
	      	$google_map_code.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
	      	$google_map_code.='  var polyOptions'.$dogooglemap.' = {strokeColor: "#'.$options['rgb'].'", strokeOpacity: 1.0, strokeWeight: '.$options['brush'].' }; ';
		$i=1;
		$google_map_code.='var bounds'.$dogooglemap.' = new google.maps.LatLngBounds();';
		foreach ($polylines as $polyline) {
			$sql="select pline from ".CANALPLAN_POLYLINES." where id='".$polyline."';";;
			$res = mysql_query($sql);
			 $rw = mysql_fetch_row($res);	
		      	$google_map_code.=' var line'.$dogooglemap.'_'.$i.' = new google.maps.Polyline(polyOptions'.$dogooglemap.');';
		 	$google_map_code.=' line'.$dogooglemap.'_'.$i.'.setPath(google.maps.geometry.encoding.decodePath("'.$rw[0].'"));';
		 	$google_map_code.=' line'.$dogooglemap.'_'.$i.'.setMap(map'.$dogooglemap.');';
	   		$google_map_code.='line'.$dogooglemap.'_'.$i.'.getPath().forEach(function(latLng) {bounds'.$dogooglemap.'.extend(latLng);});';
			$google_map_code.='map'.$dogooglemap.'.fitBounds(bounds'.$dogooglemap.');';
			$i=$i+1;
		}
      		$names[] = "[[CPGMW:" .$place_code . "]]";
      		$links[] = $mapstuff;	
      	}
	return str_ireplace($matches[0], $links, $content);
}

function canal_place_maps($content,$mapblog_id=NULL,$post_id=NULL) {
	$gazstring=CANALPLAN_URL.'gazetteer.cgi?id=';
	$canalplan_options = get_option('canalplan_options');
	#var_dump($canalplan_options);
    	if (preg_match_all('/' . preg_quote('[[CPGM:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[1]; }
    	// If the array is empty then we've no links so don't do anything!
   	if (count($places_array)==0) {return $content;}
	if (isset($mapblog_id)) {} else { $mapblog_id=$wpdb->blogid;}
	if (isset($post_id)) {} else {$post_id=$post->ID;
        if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
    	$names = array();
    	$links = array();
    	global $dogooglemap,$wpdb,$post,$google_map_code;
    	if ( get_query_var('feed') || is_feed()) {
    		foreach ($places_array as $place_code) {
    		$words=split("\|",$place_code);
	    	$names[] = "[[CPGM:" .$place_code . "]]";
	    	$links[] = "<b>[Embedded Google Map for ".trim($words[0])."]</b>";
	    }
    	return str_ireplace($names, $links, $content);
    	}
   		$maptype[S]="SATELLITE";
	   	$maptype[R]="ROADMAP";
	   	$maptype[T]="TERRAIN";
	   	$maptype[H]="HYBRID";
	foreach ($places_array as $place_code) {
		$words=split("\|",$place_code);
		$sql="select lat,`long` from ".CANALPLAN_CODES." where canalplan_id='".$words[1]."'";
		$res = mysql_query($sql);
	    	$row = mysql_fetch_array($res);
		$options['zoom']=$canalplan_options["canalplan_pm_zoom"];
		$options['type']=$canalplan_options["canalplan_pm_type"];
		$options['lat']=$row[lat];
		$options['long']=$row[long];
		$options['height']=$canalplan_options["canalplan_pm_height"];
		$options['width']=$canalplan_options["canalplan_pm_width"];
		$opts=split(",",$words[2]);
		foreach ($opts as $opt) {
			 $opcode=split("=",$opt);
			 $options[$opcode[0]]=strtoupper($opcode[1]);
		}
	    	$mapstuff="<br />";
	    	$dogooglemap=$dogooglemap+1;
		$mapstuff= '<div id="map_canvas_'.$dogooglemap.'" style="width: '.$options['width'].'px; height: '.$options['height'].'px"></div> ';
		$names[] = "[[CPGM:" .$place_code . "]]";
		$links[] = $mapstuff;
		$google_map_code.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$options['lat'].','.$options['long'].'),';
		$google_map_code.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: false,';
		$google_map_code.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
		$google_map_code.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
		$google_map_code.= 'var marker'.$dogooglemap.' = new google.maps.Marker({ position: new google.maps.LatLng('.$options['lat'].','.$options['long'].'), map: map'.$dogooglemap.', title: "'.$words[0].'"  });  ';
     	}
	return str_ireplace($matches[0], $links, $content);
}

function canal_stats($content,$mapblog_id=NULL,$post_id=NULL) {
	global $blog_id,$wpdb,$post;
	if (preg_match_all('/' . preg_quote('[[CPRS') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { $places_array=$matches[0]; }
	if (count($places_array)==0) {return $content;}
	if (isset($mapblog_id)) {} else { $mapblog_id=$blog_id;}
	if (isset($post_id)) {} else {$post_id=$post->ID;
	if (isset($post->blog_id)) {$mapblog_id=$post->blog_id;}}
	if (!isset($post_id)) {return;}
	$sql="select distance,`locks`,start_id,end_id from ".CANALPLAN_ROUTE_DAY." where blog_id=".$mapblog_id." and  post_id=".$post_id;
	$res = mysql_query($sql) ;
	$row = mysql_fetch_array($res);
	$sql="select totalroute,uom from ".CANALPLAN_ROUTES." cpr, ".CANALPLAN_ROUTE_DAY." crd where cpr.route_id= crd.route_id and cpr.blog_id=crd.blog_id and crd.blog_id=".$mapblog_id." and  crd.post_id=".$post_id;
	$res3 = mysql_query($sql);
	$row3 = mysql_fetch_array($res3);
	$dformat=$row3[uom];
	$places=split(",",$row3[totalroute]);
	$sql2="set names 'utf8';";
	$zed = mysql_query($sql2);
	$sql="select place_name from ".CANALPLAN_FAVOURITES." where canalplan_id='".$places[$row[start_id]]."' and blog_id=".$mapblog_id." union select place_name from ".CANALPLAN_CODES." where canalplan_id='".$places[$row[start_id]]."' and canalplan_id not in (select canalplan_id from ".CANALPLAN_FAVOURITES." where canalplan_id='".$places[$row[start_id]]."' and blog_id=".$mapblog_id.")";
	$res2 = mysql_query($sql);
	$row2 = mysql_fetch_array($res2);
	$start_name=$row2[place_name];
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
	if (preg_match_all('/' . preg_quote('[[CP:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { 
		$places_array=$matches[1]; 
		$gazstring=CANALPLAN_URL.'gazetteer.cgi?id=';
		$x="SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='canalkey'";
		$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='canalkey'");
		if (mysql_num_rows($r2)==0) {
		     $api="";
		}
		else
		{
			$rw = mysql_fetch_array($r2,MYSQL_ASSOC);
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
			}
			 else 
			{
				$links[] = "<a href='". CANALPLAN_GAZ_URL .$words[1]. "?blogkey=".$api[0]."&title=".$title."&blogid=".$api[1]."&date=".$date."&url=".$link."' target='gazetteer' title='Link to ".trim($words[0])."'>".trim($words[0])."</a>";
			}
		}
	}
	if (preg_match_all('/' . preg_quote('[[CPW:') . '(.*?)' . preg_quote(']]') .'/',$content,$matches)) { 
		$places_array=$matches[1]; 
		$gazstring=CANALPLAN_URL.'waterway.cgi?id=';
		foreach ($places_array as $place_code) {
		$words=split("\|",$place_code);
			$names[] = "[[CPW:" .$place_code . "]]";
			$links[] = "<a href='".$gazstring.$words[1]."' target='gazetteer'  title='Link to ".trim($words[0])."'>".trim($words[0])."</a>";
		}
	}
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
}

function blroute(){
	$routeid = $_GET['routeid'];
	$routeid = preg_replace('{/$}', '', $routeid);
	if (!isset($routeid)){$routeid=0;}
	if ($routeid<=0){$routeid=0;}
	global $wpdb,$blog_id,$google_map_code,$dogooglemap;
	$dogooglemap=1;
	$blroute="";
	$canalplan_options = get_option('canalplan_options');
	#var_dump($canalplan_options);
	if ($routeid==0){
		#$blroute .="<h2>Available Trip Reports</h2>";
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
		} 
		else {
			$routeslug=CANALPLAN_ROUTE_SLUG;
		}
		$res = mysql_query($sql);
		$blroute .="<ol>";
		while ($row = mysql_fetch_array($res)) {
			if ($wpdb->blogid==1) {$blroute .='<li><a href='.get_blog_option($row[blog_id],"siteurl").'/'.$routeslug.'/?routeid='.$row[route_id].' target=\"_new\">'.$row[title].'</a> ( from '. get_blog_option($row[blog_id],'blogname').' )  </li>';
			}
			else
			{
				$blroute .='<li><a href='.get_blog_option($row[blog_id],"siteurl").'/'.$routeslug.'/?routeid='.$row[route_id].' target=\"_new\">'.$row[title].'</a> ('.$row[description].')</li>';
			}
		}
		$blroute .="</ol><br><br>";
	}
	else
	{
		$sql2="set names 'utf8';";
		$zed = mysql_query($sql2);
		$sql="select description, totalroute from ".CANALPLAN_ROUTES." where route_id=".$routeid." and blog_id=".$wpdb->blogid;
		$res = mysql_query($sql);
		$mid_point=round(mysql_num_rows($res)/2,PHP_ROUND_HALF_UP);
		$place_count=0;
		$row = mysql_fetch_array($res);
		$blroute .="<h2>".$row[description]."</h2><br/>";
		$blroute.='<div id="map_canvas_'.$dogooglemap.'"  style="width: '.$canalplan_options["canalplan_rm_width"].'px; height: '.$canalplan_options["canalplan_rm_height"].'px"></div>';
		#$blroute.='<div id="map_canvas_'.$dogooglemap.'" style="width: 500px; height: 600px"></div> ';
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
  		$mapstuff='<div id="map_canvas_'.$dogooglemap.'"  style="width: '.$canalplan_options["canalplan_rm_width"].'px; height: '.$canalplan_options["canalplan_rm_height"].'px"></div>';
		foreach ($places as $place) {
			$sql="select `lat`,`long`,`place_name` from ".CANALPLAN_CODES." where canalplan_id='".$place."'";
			$res = mysql_query($sql);
			$row = mysql_fetch_array($res);
			if (count($row)> 2) {
			if ($place==$firstid){
				$firstname=$row[place_name];
				$first_lat=$row[lat];
				$first_long=$row[long];
			}
			if ($place==$lastid){
				$lastname=$row[place_name];
				$last_lat=$row[lat];
				$last_long=$row[long];
			}
			if($place_count==$mid_point) {
				$centre_lat=$row[lat];
				$centre_long=$row[long];
			}	
			$place_count=$place_count+1;
			$points=$place.",".$row[lat].",".$row[long];
		      	$pointx = $row[lat];
			$pointy = $row[long];
			$nlat = floor($pointx * 1e5);
			$nlong = floor($pointy * 1e5);
			$pointstring .= ascii_encode($nlat - $lat) . ascii_encode($nlong - $long);
			$zoomstring .= 'B';
			$lat = $nlat;
			$long = $nlong;
			$cpoint=$row[place_name].",".$row[lat].",".$row[long];
			if ($cpoint==$lpointb1) {
				$lpoints=split(",",$lpoint);
				$turnaround.='var marker_turn'.$dogooglemap.'_'.$x.' = new google.maps.Marker({ position: new google.maps.LatLng('.$lpoints[1].','.$lpoints[2].'), map: map'.$dogooglemap.',   title: "Turn Round here  : '.$lpoints[0].'" });';
				$turnaround.='iconFile = "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"; marker_turn'.$dogooglemap.'_'.$x.'.setIcon(iconFile) ; ';
			 	$x=$x+1;
			}
			$lpointb1=$lpoint;
			$y=$y+1;
			$lpoint=$cpoint;
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
		}}
		#$blroute .=$pointstring;
		$options['size']=200;
		$options['zoom']=$canalplan_options["canalplan_rm_zoom"];
		$options['type']=$canalplan_options["canalplan_rm_type"];
		$options['lat']=53.4;
		$options['long']=-2.8;	
		$options['rgb']=$canalplan_options["canalplan_rm_r_hex"].$canalplan_options["canalplan_rm_g_hex"].$canalplan_options["canalplan_rm_b_hex"];
	   	$maptype[S]="SATELLITE";
	   	$maptype[R]="ROADMAP";
	   	$maptype[T]="TERRAIN";
	   	$maptype[H]="HYBRID";
		$google_map_code.= 'var map_'.$dogooglemap.'_opts = { zoom: '.$options['zoom'].',center: new google.maps.LatLng('.$centre_lat.','.$centre_long.'),';
	      	$google_map_code.='  scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: false,';
	      	$google_map_code.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$options['type']].' };';
	      	$google_map_code.= 'var map'.$dogooglemap.' = new google.maps.Map(document.getElementById("map_canvas_'.$dogooglemap.'"),map_'.$dogooglemap.'_opts);';
	      	$google_map_code.='  var polyOptions'.$dogooglemap.' = {strokeColor: "#'.$options['rgb'].'", strokeOpacity: 1.0,strokeWeight: '.$canalplan_options["canalplan_rm_weight"].' }; ';
		$i=1;
		$google_map_code.=' var line'.$dogooglemap.'_'.$i.' = new google.maps.Polyline(polyOptions'.$dogooglemap.');';
	 	$google_map_code.=' line'.$dogooglemap.'_'.$i.'.setPath(google.maps.geometry.encoding.decodePath("'.$pointstring.'"));';
	 	$google_map_code.=' line'.$dogooglemap.'_'.$i.'.setMap(map'.$dogooglemap.');';
		$google_map_code.='var bounds'.$dogooglemap.' = new google.maps.LatLngBounds();';
		$google_map_code.='line'.$dogooglemap.'_'.$i.'.getPath().forEach(function(latLng) {bounds'.$dogooglemap.'.extend(latLng);});';
		$google_map_code.='map'.$dogooglemap.'.fitBounds(bounds'.$dogooglemap.');';
		$google_map_code.=$turnaround.$markertext;
		$blroute .= $page;
		$blroute .= $page2;
		$blroute .= $page3;
		$blroute .= "<p><h2>Blog Entries for this trip</h2>";
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

function wp_canalplan_admin_pages() {
	$base_dir=dirname(__FILE__).'/admin-pages/'; 
	$hook=add_menu_page('CanalPlan AC Overview', 'CanalPlan AC', 8,$base_dir.'cp-admin-menu.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: General Options', 'General Options',8,  $base_dir.'cp-admin-general.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Home Mooring', 'Home Mooring', 8, $base_dir.'cp-admin-home.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Favourites', 'Favourites', 8,  $base_dir.'cp-admin-fav.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Google Maps', 'Google Maps', 8,  $base_dir.'cp-admin-google.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Import Route', 'Import Routes', 8,  $base_dir.'cp-import_route.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Manage Routes', 'Manage Routes', 8,  $base_dir.'cp-manage_route.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Diagnostics', 'Diagnostics', 8,  $base_dir.'cp-admin-diagnostics.php');
	add_submenu_page($base_dir.'cp-admin-menu.php', 'CanalPlan Options: Bulk Notify', 'Bulk Notify', 8,  $base_dir.'cp-admin-update.php');
}

function canalplan_header($blah){
	global $blog_id,$wpdb,$google_map_code;
	$canalplan_options = get_option('canalplan_options');
	if (isset($canalplan_options['supress_google'])) {return;}
	$header = '<meta name="viewport" content="initial-scale=1.0, user-scalable=no" /> <script type="text/javascript" src="//maps.google.com/maps/api/js?libraries=geometry&amp;sensor=false"> </script> '; 
	echo $header;
	$google_map_code='<script type="text/javascript">  function initialize() {  ';
	return $blah;
}

function canalplan_footer($blah) {
	global $google_map_code;
	$google_map_code.='  } </script> ';
	echo $google_map_code;
	echo "\n<!-- Canalplan AC code revision : ".CANALPLAN_CODE_RELEASE." -->\n";
	$canalplan_options = get_option('canalplan_options');
	if (isset($canalplan_options['supress_google'])) {return;}
	#echo "<p style='font-size:0%'>Canalplan Interlinking provided by <a href='http://wordpress.org/extend/plugins/canalplan-ac/'> Canalplan AC Plugin </a></p>";
	echo "<script type='text/javascript'> google.maps.event.addDomListener(window, 'load', initialize); </script> ";
	return $blah;
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
if (!is_admin()){
add_filter('the_content','blogroute_insert');
add_filter('the_excerpt','blogroute_insert');
add_action('wp_head', 'canalplan_header');
add_action('wp_footer', 'canalplan_footer');
}
add_action('admin_menu', 'canalplan_add_custom_box');
add_action('init', 'canal_init');
register_activation_hook(__FILE__, 'canal_activate');
add_action('admin_menu', 'wp_canalplan_admin_pages');
include("canalplan_widget.php");

?>
