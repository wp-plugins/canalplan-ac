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
		parent::WP_Widget('Canalplan_widget', 'Canalplan Location', array('description' =>'The widget displays your current location (if set from the Locations Options page) and a link to the nearest location in Canalplan' , 'class' => 'CanalPlanWidget'));
	}
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		global  $wpdb, $user_ID,$table_prefix,$blog_id,$google_map_code;
		$userid=$instance['snorl'];
		// Display the widget!
		echo $before_widget;
		echo "<!--Canalplan Location Start -->\n";
		echo $before_title;
		echo $instance['title'];
		echo $after_title;
		$sql=$wpdb->prepare("select * from ".CANALPLAN_OPTIONS." where blog_id=%d and pref_code='Location'",$blog_id);
		$res = $wpdb->get_results($sql,ARRAY_A);
		$values=explode("|","none|none");
		if (count($res)>0) {
		$values=explode('|',$res[0]['pref_value']);}
	//	var_dump($values);
		switch($values[0]) {
	case 'none':
        unset($values);
        break;
    case 'Browser':
        $values=explode('|',$res[0]['pref_value']);
        $cp_lat=$values[1];
		$cp_long=$values[2];
		$cp_id=$values[5];
		$cp_name=$values[6];
        break;
        case 'Backitude':
        $values=explode('|',$res[0]['pref_value']);
        $cp_lat=$values[1];
		$cp_long=$values[2];
		$cp_id=$values[5];
		$cp_name=$values[6];
        break;
    case 'Canalplan':
        $sql=$wpdb->prepare("select lat,`long` from ".CANALPLAN_CODES." where canalplan_id=%s",$values[1]);
		$res = $wpdb->get_results($sql,ARRAY_A);
	    $row = $res[0];
	    $cp_lat=$row['lat'];
		$cp_long=$row['long'];
		$cp_id=$values[1];
		$cp_name=$values[2];
        break;
	default :
		unset($values);
	}
		echo "<div align='center'>";
		if (!isset($values)) {
			echo "<br /> No Location Set <br />";
		} else {
		$maptype['S']="SATELLITE";
	   	$maptype['R']="ROADMAP";
	   	$maptype['T']="TERRAIN";
	   	$maptype['H']="HYBRID";
		echo '<div id="map_canvas_widget_'.$blog_id.'"  style="width: '.$instance['width'].'px; height: '.$instance['height'].'px"></div>';
		$google_map_code.= 'var map_widget_'.$blog_id.'_opts = { zoom: '.$instance['zl'].',center: new google.maps.LatLng('.$cp_lat.','.$cp_long.'),';
		$google_map_code.=' scrollwheel: false, navigationControl: true, mapTypeControl: true, scaleControl: false, draggable: false,';
		$google_map_code.= ' mapTypeId: google.maps.MapTypeId.'.$maptype[$instance['mf']].' };';
		$google_map_code.= 'var map_widget_'.$blog_id.' = new google.maps.Map(document.getElementById("map_canvas_widget_'.$blog_id.'"),map_widget_'.$blog_id.'_opts);';
		$google_map_code.= 'var marker_widget_'.$blog_id.' = new google.maps.Marker({ position: new google.maps.LatLng('.$cp_lat.','.$cp_long.'), map: map_widget_'.$blog_id.', title: "'.$instance['pin_title'].'"  });  ';
			print "Nearest Canalplan location is : <br /> <a href='".CANALPLAN_GAZ_URL.$cp_id."' target='_new' > ".$cp_name."</a> <br />";
		//}
	}
		echo "</div></p>".$after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['snorl'] = $new_instance['snorl'];
		$instance['pin_title'] = strip_tags($new_instance['pin_title']);
		$instance['mf'] = strip_tags($new_instance['mf']);
		$instance['zl'] = strip_tags($new_instance['zl']);
		$instance['height'] = strip_tags($new_instance['height']);
		$instance['width'] = strip_tags($new_instance['width']);
		return $instance;
	}

	function form($instance) {
		global $user_ID;
		$default = array( 'title' =>'Where Am I', 'snorl'=>$user_ID, 'width'=>250, 'height'=>300,'maptype'=>'road','zoom'=>0,'pin_title'=>"I'm Here",'mf'=>"S",'zl'=>'17');
		$instance = wp_parse_args( (array) $instance, $default );
		$title_id = $this->get_field_id('title');
		$title_name = $this->get_field_name('title');
		$pin_title_id = $this->get_field_id('pin_title');
		$pin_title_name = $this->get_field_name('pin_title');
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
		$zoom_id = $this->get_field_id('zoom');
		$zoom_name = $this->get_field_name('zoom');
		echo '<input type="hidden" class="widefat" id="'.$snorl_id.'" name="'.$snorl_name.'" value="'.attribute_escape( $instance['snorl'] ).'" /></p>';
		echo '<p><label for="'.$title_id.'">Title of Widget: </label> <input type="text" class="widefat" id="'.$title_id.'" name="'.$title_name.'" value="'.attribute_escape( $instance['title'] ).'" /></p>';
		echo '<p><label for="'.$pin_title_id.'">Pin Title: </label> <input type="text" class="widefat" id="'.$pin_title_id.'" name="'.$pin_title_name.'" value="'.attribute_escape( $instance['pin_title'] ).'" /></p>';
		echo '<p><label for="'.$width_id.'">Widget Width : </label> <input type="text" size="7" id="'.$width_id.'" name="'.$width_name.'" value="'.attribute_escape( $instance['width'] ).'" /></p>';

		echo '<p><label for="'.$height_id.'">Widget Height: </label> <input type="text" size="7" id="'.$height_id.'" name="'.$height_name.'" value="'.attribute_escape( $instance['height'] ).'" /></p>';
		echo '<p><label for="'.$mf_id.'"> Map Format :  </label>';
		echo '<select id=id="'.$mf_id.'" name="'.$mf_name.'" >';

		$arr = array("R"=>"Road","T"=>"Terrain","H"=>"Hybrid","S"=>"Satellite");

		foreach ($arr as $i => $value) {
		if ($i==attribute_escape( $instance['mf'])){ print '<option selected="yes" value="'.$i.'" >'.$arr[$i].'</option>';}
		else {print '<option value="'.$i.'" >'.$arr[$i].'</option>';}
		}
		echo '</select></p>';

		echo '<p><label for="'.$zl_id.'"> Zoom Level :  </label>';
		echo '<select id=id="'.$zl_id.'" name="'.$zl_name.'" >';

		$arr = array(0=>"Automatic",3=>"Continent",5=>"Country",7=>"Region",11=>"City",15=>"Neighbourhood",17=>"Street",21=>"House");
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