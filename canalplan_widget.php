<?php
/*
	Copyright 2012, Steve Atty

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
class CanalPLanWidget extends WP_Widget {
	function CanalplanWidget() {
		parent::WP_Widget('Canalplan_widget', 'Canalplan Latitude ', array('description' =>'Allows you to have one or more Canalplan Latitude widgets in your sidebar. The widget displays your current Google Latitude Location and a link to the nearest location in Canalplan' , 'class' => 'CanalPlanWidget'));
	}
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		global  $wpdb, $user_ID,$table_prefix,$blog_id;
		$userid=$instance['snorl'];
		// Display the widget!
		echo $before_widget;
		echo "<!--Canalplan Latitude Start -->\n";
		echo $before_title;
		echo $instance['title'];
		echo $after_title;
		echo "<div align='center'>";
		$params = '?user='.$instance['google'].'&type=iframe&maptype='.$instance['mf'].'&z='.$instance['zl'];
		echo "<!-- Google Public Location Badge -->\n";
		echo "<iframe src=\"http://www.google.com/latitude/apps/badge/api".$params."\" width=\"".$instance['width']."\" height=\"".$instance['height']."\" frameborder=\"0\" ALLOWTRANSPARENCY=\"true\" >\n";
		echo "</iframe>";
		$latfile='https://www.google.com/latitude/apps/badge/api?user='.$instance['google'].'&type=json';
		#var_dump($latfile);
		$llines = file_get_contents($latfile);
		$lcontents=utf8_encode($llines);
		$Latitude = json_decode($lcontents, false);
		$x=$Latitude->features[0]->geometry->coordinates;
		$lng=$x[0];
		$lat=$x[1];
		$sql=$wpdb->prepare("SELECT place_name,canalplan_id,lat,`long`,GLength(LineString(lat_lng_point, GeomFromText('Point(".$lat." ".$lng.")'))) AS distance FROM ".CANALPLAN_CODES." where place_name not like %s ORDER BY distance ASC LIMIT 1", '%!%' );
		$res = $wpdb->get_results($sql,ARRAY_A);
		if(count($res)>0){
			$row=$res[0];
			$gazstring='http://www.canalplan.org.uk/cgi-bin/gazetteer.cgi?where=$';
			print "Nearest Canalplan location is : <br /> <a href='".CANALPLAN_GAZ_URL.$row['canalplan_id']."' target='_new' > ".$row['place_name']."</a> <br /></div>";
		}
		echo "</p>".$after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['snorl'] = $new_instance['snorl'];
		$instance['google'] = strip_tags($new_instance['google']);
		$instance['mf'] = strip_tags($new_instance['mf']);
		$instance['zl'] = strip_tags($new_instance['zl']);
		$instance['header'] = strip_tags($new_instance['header']);
		$instance['height'] = strip_tags($new_instance['height']);
		$instance['width'] = strip_tags($new_instance['width']);
		return $instance;
	}

	function form($instance) {
		global $user_ID;
		$default = array( 'title' =>'Where Am I', 'snorl'=>$user_ID, 'width'=>250, 'height'=>300,'maptype'=>'road','zoom'=>0);
		$instance = wp_parse_args( (array) $instance, $default );
		$title_id = $this->get_field_id('title');
		$title_name = $this->get_field_name('title');
		$google_id = $this->get_field_id('google');
		$google_name = $this->get_field_name('google');
		$snorl_id = $this->get_field_id('snorl');
		$snorl_name = $this->get_field_name('snorl');
		$width_id = $this->get_field_id('width');
		$width_name = $this->get_field_name('width');
		$height_id = $this->get_field_id('height');
		$height_name = $this->get_field_name('height');
		$mf_id = $this->get_field_id('mf');
		$mf_name = $this->get_field_name('mf');
		$zl_id = $this->get_field_id('zl');
		$zl_name = $this->get_field_name('zl');
		echo '<p><label for="'.$title_id.'">Title of Widget: </label> <input type="text" class="widefat" id="'.$title_id.'" name="'.$title_name.'" value="'.attribute_escape( $instance['title'] ).'" /></p>';
		echo '<p><label for="'.$google_id.'">Latitude ID: </label> <input type="text" class="widefat" id="'.$google_id.'" name="'.$google_name.'" value="'.attribute_escape( $instance['google'] ).'" /></p>';
		echo '<p><label for="'.$width_id.'">Widget Width : </label> <input type="text" size="7" id="'.$width_id.'" name="'.$width_name.'" value="'.attribute_escape( $instance['width'] ).'" /></p>';

		echo '<p><label for="'.$height_id.'">Widget Height: </label> <input type="text" size="7" id="'.$height_id.'" name="'.$height_name.'" value="'.attribute_escape( $instance['height'] ).'" /></p>';
		echo '<p><label for="'.$mf_id.'"> Map Format :  </label>';
		echo '<select id=id="'.$mf_id.'" name="'.$mf_name.'" >';

		$arr = array("roadmap"=>"Road","terrain"=>"Terrain","hybrid"=>"Hybrid","satellite"=>"Satellite");
		foreach ($arr as $i => $value) {
		if ($i==attribute_escape( $instance['mf'])){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
		else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}
		}
		echo '</select></p>';

		echo '<p><label for="'.$zl_id.'"> Zoom Level :  </label>';
		echo '<select id=id="'.$zl_id.'" name="'.$zl_name.'" >';

		$arr = array(0=>"Automatic",3=>"Continent",5=>"Country",7=>"Region",10=>"City",14=>"Street",19=>"House");
		foreach ($arr as $i => $value) {
		if ($i==attribute_escape( $instance['zl'])){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
		else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}
		}
		echo '</select></p>';
	}

}
/* register widget when loading the WP core */
add_action('widgets_init', 'canalplan_widgets');
$plugin_dir = basename(dirname(__FILE__));

function canalplan_widgets(){
	register_widget('CanalplanWidget');
}
?>