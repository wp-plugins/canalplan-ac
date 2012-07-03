<?php


require("../../../../wp-config.php");
if ($cp_blog_id=="undefined") {unset($cp_blog_id);}
if (strlen($match)>0){
$match=mysql_real_escape_string($_GET['match']);
$place=mysql_real_escape_string(_GET['place']);
$cp_blog_id=mysql_real_escape_string($_GET['blogid']);

$sql="set names 'utf8';";
$zed = mysql_query($sql);
	//	$canalplaces = mysql_query($sql);
//	while ($canalplace = mysql_fetch_array($canalplaces)) { print trim($canalplace[place_name]).'#'; }
	$sql = "select canalplan_id, place_name from ".CANALPLAN_CODES." where substr(place_name,1,".strlen($match).")='".$match."'";
	$canalplaces = mysql_query($sql);
	while ($canalplace = mysql_fetch_array($canalplaces)) { print trim($canalplace[place_name]).'#'; }
        $sql = "select canalplan_id, place_name from ".CANALPLAN_ALIASES." where substr(place_name,1,".strlen($match).")='".$match."'";
        $canalplaces = mysql_query($sql);
        while ($canalplace = mysql_fetch_array($canalplaces)) { print trim($canalplace[place_name]).'#'; }
        $sql = "select id, fullname from ".CANALPLAN_CANALS." where parent!='' and substr(fullname,1,".strlen($match).")='".$match."'";
        $canalplaces = mysql_query($sql);
        while ($canalplace = mysql_fetch_array($canalplaces)) { print trim($canalplace[fullname]).'#'; }

}
if (strlen($place)>0){
$place=trim(urldecode($place));
// If we have a blog_id passed in then we need to get back the favourites and then the main rows but remove any common names
  if (isset($cp_blog_id)) {
$sql="set names 'utf8';";
$zed = mysql_query($sql);
	  // Get Check Favourites
		$sql = 'select  canalplan_id from '.CANALPLAN_FAVOURITES.' where place_name="'.$place.'" and blog_id='.$cp_blog_id;
		$canalplaces = mysql_query($sql);
		while ($canalplace = mysql_fetch_array($canalplaces)) { print "X".trim($canalplace[canalplan_id]); }
		// Now check the rest
		$sql = 'select  cc.canalplan_id from '.CANALPLAN_CODES.' cc where  cc.place_name="'.$place.'" and  cc.canalplan_id not in (select cf.canalplan_id from '.CANALPLAN_FAVOURITES.' cf where cf.blog_id='.$cp_blog_id.' and cf.place_name="'.$place.'")';
		$canalplaces = mysql_query($sql);
		while ($canalplace = mysql_fetch_array($canalplaces)) { print "X".trim($canalplace[canalplan_id]); }
                $sql = 'select  cc.canalplan_id from '.CANALPLAN_ALIASES.' cc where  cc.place_name="'.$place.'" and  cc.canalplan_id not in (select cf.canalplan_id from '.CANALPLAN_FAVOURITES.' cf where cf.blog_id='.$cp_blog_id.' and cf.place_name="'.$place.'")';
                $canalplaces = mysql_query($sql);
                while ($canalplace = mysql_fetch_array($canalplaces)) { print "X".trim($canalplace[canalplan_id]); }
	        $sql = "select id from ".CANALPLAN_CANALS." where fullname='".$place."'";
	        $canalplaces = mysql_query($sql);
	        while ($canalplace = mysql_fetch_array($canalplaces)) { print "W".trim($canalplace[id]); }

	}
	else {
$sql="set names 'utf8';";
$zed = mysql_query($sql);
	$sql = "select canalplan_id from ".CANALPLAN_CODES." where place_name='".$place."'";
	$canalplaces = mysql_query($sql);
	while ($canalplace = mysql_fetch_array($canalplaces)) { print "".trim($canalplace[canalplan_id]); }
	$sql = "select canalplan_id from ".CANALPLAN_ALIASES." where place_name='".$place."'";
	$canalplaces = mysql_query($sql);
	while ($canalplace = mysql_fetch_array($canalplaces)) { print "".trim($canalplace[canalplan_id]); }
	// We Really don't want people adding canals to their favorites.... well not yet
	#$sql = "select id from canals where fullname='".$place."'";
	#$canalplaces = mysql_query($sql);
	#while ($canalplace = mysql_fetch_array($canalplaces)) { print "".trim($canalplace[id]); }
}
}
#}
?>
