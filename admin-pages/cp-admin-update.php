<?php

/*
Extension Name: Canalplan Bulk update
Extension URI: http://blogs.canalplan.org.uk/canalplanac/canalplan-plug-in/
Version: 0.9
Description: Bulk notifier page for the Canalplan AC Plugin
Author: Steve Atty
*/

$parent_file = 'canalplan-manager.php';
$title = __('Bulk Link notify');
$this_file = 'cp-admin-update.php';
global $blog_id,$wpdb;
?>
<div class="wrap">
<h2><?php _e('Bulk Link Notifier') ?> </h2>


<?php
if (isset($_POST["bulkprocess"])){
	$query="SELECT ID FROM $wpdb->posts WHERE post_status='publish' and (post_type='post' or post_type='page') order by ID desc limit ".$_POST['plselect'];;
	$res = mysql_query($query);
	$api=explode("|",$_POST["bulkprocess"]);
	$blog_url=get_bloginfo('url');
	while ( $rw = mysql_fetch_array($res,MYSQL_ASSOC)) {
		$bulkpost=get_post($rw['ID']);
		$date = date("Ymd",strtotime($bulkpost->post_date));
		$link=urlencode(str_replace($blog_url,"",get_permalink($rw['ID'])));
		echo "<br />Processing Post <i>".$bulkpost->post_title."</i><br />";
		if (preg_match_all('/' . preg_quote('[[CP:') . '(.*?)' . preg_quote(']]') .'/',$bulkpost->post_content,$matches)) {
			$places_array=$matches[1];
			foreach ($places_array as $place) {
				$placeinfo=explode('|',$place);
				$x=CANALPLAN_URL.'api.cgi?mode=add_bloglink&id='.$api[1].'&key='.$api[0].'&title='.urlencode($bulkpost->post_title).'&placeid='.$placeinfo[1]; 
				$x.='&url='.$link.'&date='.$date;
				$fcheck=file_get_contents($x);
				$cp_bulk=json_decode($fcheck,true);
				echo "&nbsp;&nbsp;&nbsp;Found link to <i>".$placeinfo[0]."</i>";
				if ($cp_bulk['status']=='OK') { 
					echo " and ",$cp_bulk['detail'].' the link ';
					echo ($cp_bulk['detail']=='added') ? "to" : "in";
 					echo' CanalPlan AC<br />';
				} else {
					echo "&nbsp;&nbsp;&nbsp;<b>A problem occurred : ".$cp_bulk['status']." - ".$cp_bulk['detail']."</b><br />";
				}
				#Sleep for 10ms just to stop us swamping the server.
				usleep(10000);
			}
		}
		else {
			echo "&nbsp;&nbsp;&nbsp;No Canalplan Links Found<br />"; }
		} 
echo "<br /> <b>All Done !</b><br /><br />";
	} 


$r2 = mysql_query("SELECT pref_value FROM ".CANALPLAN_OPTIONS." where  blog_id=".$blog_id." and pref_code='canalkey'");
	if (mysql_num_rows($r2)==0) {
	     $api="";
	}
	else
	{
	while($rw = mysql_fetch_array($r2))
	{
		$api=$rw['pref_value'];
	}
	}

 if (!isset($_POST["bulkprocess"])){
?>
<br>
Normally Canalplan AC will find out about links into its gazetteer entries from your blog automatically. However if you've just added posts with a lot of Canalplan Links in them you might want to push a list of these links to Canalplan.
<br />
<?php
if (strlen($api)>4 ) {

?>

<form action="" name="bulkform" id="bulk_form" method="post">
<p>Number of posts to Process : 
<select id="plselect" name="plselect">
<?php 
for ($i = 1; $i <= CANALPLAN_MAX_POST_PROCESS; $i++) {
echo '<option ';
echo ($i==10) ? 'selected="yes"' : '';
echo ' value="'.$i.'">'.$i.'</option>';
}
?>
</select>
<input type="hidden" name="bulkprocess" id="bulkprocess" value="<? echo $api ?>" />
</p><p class="submit"> <input type="submit"   value="Bulk Notify" /></p>
<?php
	} 
	else 
	{
		echo "<br><i>You have not obtained an API Key from Canalplan so you cannot use this option. Go to the General Settings page and obtain one.>/i>"; }
}
?>
