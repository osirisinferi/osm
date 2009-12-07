<?php
/*
Plugin Name: OSM
Plugin URI: http://www.Fotomobil.at/wp-osm-plugin
Description: Embeds <a href="http://www.OpenStreetMap.org">OpenStreetMap</a> maps in your blog and adds geo data to your posts. Get the latest version on the <a href="http://www.Fotomobil.at/wp-osm-plugin">OSM plugin page</a>. DO NOT "upgrade automatically" if you made any personal settings or if you stored GPX or TXT files in the plugin folder!!
Version: 0.8.6
Author: Michael Kang
Author URI: http://www.HanBlog.net
Minimum WordPress Version Required: 2.5.1
*/

/*  (c) Copyright 2009  Michael Kang

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* 
    Keep in mind, all changes you do by your own are lost whenever you update this plugin. If you need any general
    feature contact me to make a standard of OSM plugin!
  +--------+------------------------------------------------------------------------------------------------------------------------------
  | Ver.   |   Feature - Bugfixing - Notes - ...
  +--------+------------------------------------------------------------------------------------------------------------------------------
  | 0.8.6  | configureable loading of OSM libraries
  |        | control tag added
  |        | adding map by external link
  | 0.8.5  | HTML-marker for PopUps added; using WP_Error class; create Osm object, Osm_Openlayers classs
  |               wpgmg-pluginn support changed from marker_all_posts to import argument
  | 0.8.4  | correct plugin folder to "osm" (lower case!)
  | 0.8.3  | correct offset of indiv. marker
  | 0.8.1  | check whether gcstats is activated or not
  | 0.8.0  | separate file for option and import; gcstats support; add marker in option page
  | 0.7.0  | shortcode generator in option page added
  | 0.6.0  | options got prefix "osm_", therefore settings have to be made again at upgrade
  | 0.5.0  | added type at shortcode (Mapnik, Osmarender, CycleMap, All) ; overviewmap in shortcode
  | 0.4.0  | added KML support and colour interface for tracks
  | 0.3.0  | added "marker_all_posts" at shortcode to set a marker for all posts
  | 0.2.0  | loading GPX files with shortcode
  +--------+------------------------------------------------------------------------------------------------------------------------------
*/

load_plugin_textdomain('Osm');

define ("PLUGIN_VER", "V0.8.6");

// modify anything about the marker for tagged posts here
// instead of the coding.
define ("POST_MARKER_PNG", "marker_posts.png");
define (POST_MARKER_PNG_HEIGHT, 2);
define (POST_MARKER_PNG_WIDTH, 2);

define ("GCSTATS_MARKER_PNG", "geocache.png");
define (GCSTATS_MARKER_PNG_HEIGHT, 25);
define (GCSTATS_MARKER_PNG_WIDTH, 25);

define ("INDIV_MARKER", "marker_blue.png");
define (INDIV_MARKER_PNG_HEIGHT, 25);
define (INDIV_MARKER_PNG_WIDTH, 25);

// these defines are given by OpenStreetMap.org
define ("URL_INDEX", "http://www.openstreetmap.org/index.html?");
define ("URL_LAT","&mlat=");
define ("URL_LON","&mlon=");
define ("URL_ZOOM_01","&zoom=[");
define ("URL_ZOOM_02","]");
define (ZOOM_LEVEL_MAX,17);
define (ZOOM_LEVEL_MIN,1);

// other geo plugin defines
// google-maps-geocoder
define ("WPGMG_LAT", "lat");
define ("WPGMG_LON", "lng");

// some general defines
define (LAT_MIN,-90);
define (LAT_MAX,90);
define (LON_MIN,-180);
define (LON_MAX,180);

// tracelevels
define (DEBUG_OFF, 0);
define (DEBUG_ERROR, 1);
define (DEBUG_WARNING, 2);
define (DEBUG_INFO, 3);
define (HTML_COMMENT, 10);

// Load OSM library mode
define (SERVER_EMBEDDED, 1);
define (SERVER_WP_ENQUEUE, 2);



if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define ("OSM_PLUGIN_URL", WP_PLUGIN_URL."/osm/");
define ("OSM_PLUGIN_ICONS_URL", OSM_PLUGIN_URL."icons/");
define ("URL_POST_MARKER", OSM_PLUGIN_URL.POST_MARKER_PNG);

global $wp_version;
if (version_compare($wp_version,"2.5.1","<")){
  exit('[OSM plugin - ERROR]: At least Wordpress Version 2.5.1 is needed for this plugin!');
}
	
// get the configuratin by
// default or costumer settings
if (@(!include('osm-config.php'))){
  include ('osm-config-sample.php');
}

// do not edit this
define ("Osm_TraceLevel", DEBUG_ERROR); 

include('osm-openlayers.php');
    	
// let's be unique ... 
// with this namespace
class Osm
{ 
	function Osm() {
		$this->localizionName = 'Osm';
    //$this->TraceLevel = DEBUG_ERROR;
		$this->ErrorMsg = new WP_Error();
		$this->initErrorMsg();
    
    // add the WP action
    add_action('wp_head', array(&$this, 'wp_head'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('wp_print_scripts',array(&$this, 'show_enqueue_script'));

    // add the WP shortcode
    add_shortcode('osm_map',array(&$this, 'sc_showMap'));
	}
  
  function initErrorMsg()
  {
    include('osm-error-msg.php');	
  }

  function traceErrorMsg($e = '')
  {
   if ($this == null){
     return $e;
   }
   $EMsg = $this->ErrorMsg->get_error_message($e);
   if ($EMsg == null){
     return $e;
     //return__("Unknown errormessage",$this->localizionName); 
   }
   return $EMsg;
  }
  
  function traceText($a_Level, $a_String)
  {
    $TracePrefix = array(
      DEBUG_ERROR =>'[OSM-Plugin-Error]:',
      DEBUG_WARNING=>'[OSM-Plugin-Warning]:',
      DEBUG_INFO=>'[OSM-Plugin-Info]:');
      
    if ($a_Level == DEBUG_ERROR){     
      echo '<div class="osm_error_msg"><p><strong style="color:red">'.$TracePrefix[$a_Level].Osm::traceErrorMsg($a_String).'</strong></p></div>';
    }
    else if ($a_Level <= Osm_TraceLevel){
      echo $TracePrefix[$a_Level].$a_String.'<br>';
    }
    else if ($a_Level == HTML_COMMENT){
      echo "<!-- ".$a_String." --> \n";
    }
  }

	// add it to the Settings page
	function options_page_osm()
	{
		if(isset($_POST['Options'])){

      // 0 = no error; 
      // 1 = error occured
      $Option_Error = 0; 
			
      // get the zoomlevel for the external link
      // and inform the user if the level was out of range     
      update_option('osm_custom_field',$_POST['osm_custom_field']);
     
      if ($_POST['osm_zoom_level'] >= ZOOM_LEVEL_MIN && $_POST['osm_zoom_level'] <= ZOOM_LEVEL_MAX){
        update_option('osm_zoom_level',$_POST['osm_zoom_level']);
      }
      else { 
        $Option_Error = 1;
        Osm::traceText(DEBUG_ERROR, "e_zoomlevel_range");
      }
      // Let the user know whether all was fine or not
      if ($Option_Error  == 0){ 
        Osm::traceText(DEBUG_INFO, "i_options_updated");
      }
      else{
         Osm::traceText(DEBUG_ERROR, "e_options_not_updated");
      }
	
		}
		else{
			add_option('osm_custom_field', 0);
			add_option('osm_zoom_level', 0);
		}
	
    // name of the custom field to store Long and Lat
    // for the geodata of the post
		$osm_custom_field  = get_option('osm_custom_field');                                                  

    // zoomlevel for the link the OSM page
    $osm_zoom_level    = get_option('osm_zoom_level');
			
    include('osm-options.php');	
	}
	
  // put meta tags into the head section
	function wp_head($not_used)
	{
		global $wp_query;

		$CustomField = get_option('osm_custom_field');
		list($lat, $lon) = split(',', get_post_meta($wp_query->post->ID, $CustomField, true));
		if(is_single() && ($lat != '') && ($lon != '')){
			$title = convert_chars(strip_tags(get_bloginfo("name")))." - ".$wp_query->post->post_title;
      $this->traceText(HTML_COMMENT, 'OSM plugin '.PLUGIN_VER.': adding geo meta tags:');
		}
		else{
      $this->traceText(HTML_COMMENT, 'OSM plugin '.PLUGIN_VER.': did not add geo meta tags.');
			return;
		}

    // let's store geo data with W3 standard
		echo "<meta name=\"ICBM\" content=\"{$lat}, {$lon}\" />\n";
		echo "<meta name=\"DC.title\" content=\"{$wp_query->post->post_title}\" />\n";
    echo "<meta name=\"geo.placename\" content=\"{$wp_query->post->post_title}\"/>\n"; 
		echo "<meta name=\"geo.position\"  content=\"{$lat};{$lon}\" />\n";
	}
    
  function createMarkerList($a_import, $a_import_UserName, $a_Customfield)
  {
     $this->traceText(DEBUG_INFO, "createMarkerList(".$a_import.",".$a_import_UserName.",".$a_Customfield.")");
	   global $post;
     $post_org = $post;
      
     // make a dummymarker to you use icon.clone later
     if ($a_import == 'gcstats'){
       $this->traceText(DEBUG_INFO, "Requesting data from gcStats-plugin");
       include('osm-import.php');
     }
     else if ($a_import == 'ecf'){
       $this->traceText(DEBUG_INFO, "Requesting data from comments");
       include('osm-import.php');
     }
     else if ($a_import == 'osm'){
       // let's see which posts are using our geo data ...
       $this->traceText(DEBUG_INFO, "check all posts for osm geo custom fields");
       $CustomFieldName = get_settings('osm_custom_field');        
       $recentPosts = new WP_Query();
       $recentPosts->query('meta_key='.$CustomFieldName.'&post_status=publish'.'&showposts=-1');
//     $recentPosts->query('meta_key='.$CustomFieldName.'&post_status=publish'.'&post_type=page');
       while ($recentPosts->have_posts()) : $recentPosts->the_post();
  	     list($temp_lat, $temp_lon) = split(',', get_post_meta($post->ID, $CustomFieldName, true)); 
//         echo $post->ID.'Lat: '.$temp_lat.'Long '.$temp_lon.'<br>';
         if ($temp_lat != '' && $temp_lon != '') {
           list($temp_lat, $temp_lon) = $this->checkLatLongRange('$marker_all_posts',$temp_lat, $temp_lon);          
           $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'marker'=>$marker_name);
	        }  
       endwhile;
     }
     else if ($a_import == 'wpgmg'){
       // let's see which posts are using our geo data ...
       $this->traceText(DEBUG_INFO, "check all posts for wpgmg geo custom fields");
       $recentPosts = new WP_Query();
       $recentPosts->query('meta_key='.WPGMG_LAT.'&meta_key='.WPGMG_LON.'&showposts=-1');
       while ($recentPosts->have_posts()) : $recentPosts->the_post();
         include('osm-import.php');
         if ($temp_lat != '' && $temp_lon != '') {
           list($temp_lat, $temp_lon) = $this->checkLatLongRange('$marker_all_posts',$temp_lat, $temp_lon);          
           $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'marker'=>$marker_name);
        }  
       endwhile;
     }

     $post = $post_org;
     return $MarkerArray;
  }

  // if you miss a colour, just add it
  function checkStyleColour($a_colour){
    if ($a_colour != 'red' && $a_colour != 'blue' && $a_colour != 'black' && $a_colour != 'green'){
      return "blue";
    }
    return $a_colour;
  }

  // if you miss a colour, just add it
  function getImportLayer($a_import_type, $a_import_UserName, $a_marker_name, $a_marker_height, $a_marker_width){
    // import data from wpgmg
      $Icon[Name]   = $a_marker_name;
      $Icon[height] = $a_marker_height;
      $Icon[width]  = $a_marker_width;

    if ($a_import_type  == 'osm'){
      $LayerName = 'TaggedPosts';
      $PopUp = 'false';
    }
    // import data from wpgmg
    else if ($a_import_type  == 'wpgmg'){
      $LayerName = 'TaggedPosts';
      $PopUp = 'false';
    }
    // import data from gcstats
    else if ($a_import_type == 'gcstats'){
      $LayerName     = 'GeoCaches';
      $PopUp = 'true';
      $Icon = Osm::getIconsize(GCSTATS_MARKER_PNG);
      $Icon[Name] = GCSTATS_MARKER_PNG;
    }
    // import data from ecf
    else if ($a_import_type == 'ecf'){
      $LayerName = 'Comments';
      $PopUp = 'true';
      $Icon = Osm::getIconsize(INDIV_MARKER);
      $Icon[Name] = INDIV_MARKER;
    }
    else{
      $this->traceText(DEBUG_ERROR, "e_import_unknwon");
    }
    $MarkerArray = $this->createMarkerList($a_import_type, $a_import_UserName,'Empty');
    return Osm_OpenLayers::addMarkerListLayer($LayerName, $Icon[Name],$Icon[width], $Icon[height],$MarkerArray,-12,-12,$PopUp);
  }

 // check Lat and Long
  function getMapCenter($a_Lat, $a_Long, $a_import, $a_import_UserName){
    if ($a_import == 'wpgmg'){
      $a_Lat  = OSM_getCoordinateLat($a_import);
      $a_Long = OSM_getCoordinateLong($a_import);
    }
    else if ($a_import == 'gcstats'){
      if (function_exists('gcStats__getInterfaceVersion')) {
        $Val = gcStats__getMinMaxLat($a_import_UserName);
        $a_Lat = ($Val[min] + $Val[max]) / 2;
        $Val = gcStats__getMinMaxLon($a_import_UserName);
        $a_Long = ($Val[min] + $Val[max]) / 2;
      }
      else{
       $this->traceText(DEBUG_WARNING, "getMapCenter() could not connect to gcStats plugin");
       $a_Lat  = 0;$a_Long = 0;
      }
    }
    else if ($a_Lat == '' || $a_Long == ''){
      $a_Lat  = OSM_getCoordinateLat('osm');
      $a_Long = OSM_getCoordinateLong('osm');
    }
    return array($a_Lat,$a_Long);
  }
    
  // check Lat and Long
  function checkLatLongRange($a_CallingId, $a_Lat, $a_Long)
  {
    if ($a_Lat >= LAT_MIN && $a_Lat <= LAT_MAX && $a_Long >= LON_MIN && $a_Long <= LON_MAX &&
                    preg_match('!^[^0-9]+$!', $a_Lat) != 1 && preg_match('!^[^0-9]+$!', $a_Long) != 1){
      return array($a_Lat,$a_Long);              
    }
    else{
      $this->traceText(DEBUG_ERROR, "e_lat_lon_range");
      $this->traceText(DEBUG_INFO, "Error: ".$a_CallingId." Lat".$a_Lat." or Long".$a_Long);
      $a_Lat  = 0;$a_Long = 0;
    }
  }

 function isOsmIcon($a_IconName)
 {

   if ($a_IconName == "airport.png" || $a_IconName == "bicycling.png" ||
    $a_IconName == "bus.png" || $a_IconName == "camping.png" ||
    $a_IconName == "car.png" || $a_IconName == "friends.png" ||
    $a_IconName == "geocache.png" || $a_IconName == "guest_house.png" ||
    $a_IconName == "home.png" || $a_IconName == "hostel.png" ||
    $a_IconName == "hotel.png"|| $a_IconName == "marker_blue.png" ||
    $a_IconName == "motorbike.png" || $a_IconName == "restaurant.png" ||
    $a_IconName == "services.png" || $a_IconName == "styria_linux.png" ||
    $a_IconName == "marker_posts.png" || $a_IconName == "restaurant.png" ||
    $a_IconName == "toilets.png" || $a_IconName == "wpttemp-yellow.png" ||
    $a_IconName == "wpttemp-green.png" || $a_IconName == "wpttemp-red.png"){
    return 1;
   }
   else {
    return 0;
   }
 } 

 function getIconsize($a_IconName)
 {

  $Icons = array(
    "airport.png"        => array("height"=>32,"width"=>"31"),
    "bicycling.png"      => array("height"=>32,"width"=>"19"),
    "bus.png"            => array("height"=>32,"width"=>"26"),
    "camping.png"        => array("height"=>32,"width"=>"32"),
    "car.png"            => array("height"=>32,"width"=>"18"),
    "friends.png"        => array("height"=>32,"width"=>"32"),
    "geocache.png"       => array("height"=>25,"width"=>"25"),
    "guest_house.png"    => array("height"=>32,"width"=>"32"),
    "home.png"           => array("height"=>32,"width"=>"32"),
    "hostel.png"         => array("height"=>24,"width"=>"24"),
    "hotel.png"          => array("height"=>32,"width"=>"32"),
    "marker_blue.png"    => array("height"=>24,"width"=>"24"),
    "motorbike.png"      => array("height"=>23,"width"=>"32"),
    "restaurant.png"     => array("height"=>24,"width"=>"24"),
    "services.png"       => array("height"=>28,"width"=>"32"),
    "styria_linux.png"   => array("height"=>50,"width"=>"36"),
    "marker_posts.png"   => array("height"=>2,"width"=>"2"),
    "restaurant.png"     => array("height"=>24,"width"=>"24"),
    "toilets.png"        => array("height"=>32,"width"=>"32"),
    "wpttemp-yellow.png" => array("height"=>24,"width"=>"24"),
    "wpttemp-green.png"  => array("height"=>24,"width"=>"24"),
    "wpttemp-red.png"    => array("height"=>24,"width"=>"24"),
  );

  if ($Icons[$a_IconName][height] == ''){
    $Icon = array("height"=>24,"width"=>"24");
    $this->traceText(DEBUG_ERROR, "e_unknown_icon");
    $this->traceText(DEBUG_INFO, "Error: (marker_name: ".$a_IconName.")!"); 
  }
  else {
    $Icon = $Icons[$a_IconName];
  }
  return $Icon;
 }

  // execute the java script to display 
  // the OpenStreetMap
  function sc_showMap($atts) {
    // let's get the shortcode arguments
  	extract(shortcode_atts(array(
    // size of the map
    'width'     => '450', 'height' => '300', 
    // address of the center in the map
		'lat'       => '', 'long'  => '',    
    // the zoomlevel of the map 
    'zoom'      => '7',     
    // Osmarender, Mapnik, CycleMap, ...           
    'type'      => 'All',
    // track info
    'gpx_file'  => 'NoFile',           // 'absolut address'          
    'gpx_colour'=> 'NoColour',
    'kml_file'  => 'NoFile',           // 'absolut address'          
    'kml_colour'=> 'NoColour',
    // are there markers in the map wished loaded from a file
    'marker_file'     => 'NoFile', // 'absolut address'
    // are there markers in the map wished loaded from post tags
    'marker_all_posts'=> 'n',      // 'y' or 'Y'
    'marker_name'     => 'NoName',
    'marker_height'   => '0',
    'marker_width'    => '0',
    'ov_map'          => '-1',         // zoomlevel of overviewmap
    'import'          => 'No',
    'marker'          => 'No',
    'msg_box'         => 'No',
    'custom_field'    => 'No',
	'control'		  => 'No',
	'extmap_type'     => 'No',
	'extmap_name'     => 'No',
	'extmap_address'  => 'No',
	'extmap_init'     => 'No',
	  ), $atts));
   
    if ($zoom < ZOOM_LEVEL_MIN || $zoom > ZOOM_LEVEL_MAX){
      $this->traceText(DEBUG_ERROR, "e_zoomlevel_range");
      $this->traceText(DEBUG_INFO, "Error: (Zoomlevel: ".$zoom.")!");
      $zoom = 0;   
    }
    if ($width < 1 || $height < 1){
      $this->traceText(DEBUG_ERROR, "e_map_size");
      $this->traceText(DEBUG_INFO, "Error: ($width: ".$width." $height: ".$height.")!");
      $width = 450; $height = 300;
    }

    if ($marker_name == 'NoName'){
      $marker_name   = POST_MARKER_PNG;
    }
    if (Osm::isOsmIcon($marker_name) == 1){
        $Icon = Osm::getIconsize($marker_name);
        $Icon[Name] = $marker_name;
        $marker_height = $Icon[height];
        $marker_width  = $Icon[width];
    }
    else  {
      $Icon[Name] = $marker_name;
      if ($marker_height == 0 || $marker_width == 0){
        $this->traceText(DEBUG_ERROR, "e_marker_size");
        $marker_height = 24;
        $marker_width  = 24;
      }
    }

	list($import_type, $import_UserName) = split(',', $import);
    if ($import_UserName == ''){
      $import_UserName = 'DummyName';
    }
    $import_type = strtolower($import_type);
	
	$array_control = split ( ',', $control);
    
    list($lat, $long) = Osm::getMapCenter($lat, $long, $import_type, $import_UserName);
    list($lat, $long) = Osm::checkLatLongRange('MapCenter',$lat, $long);
    $gpx_colour       = Osm::checkStyleColour($gpx_colour); 
    $kml_colour       = Osm::checkStyleColour($kml_colour);
    $type             = Osm_OpenLayers::checkMapType($type);
    $ov_map           = Osm_OpenLayers::checkOverviewMapZoomlevels($ov_map);
	$array_control    = Osm_OpenLayers::checkControlType($array_control);

    // to manage several maps on the same page
    // create names with index
    static  $MapCounter = 0;
    $MapCounter += 1;
    $MapName = 'map_'.$MapCounter;
    $GpxName = 'GPX_'.$MapCounter;
    $KmlName = 'KML_'.$MapCounter;
	
    // if we came up to here, let's load the map
    $output = '';	
	$output .= '<style type="text/css">';
	$output .= '#'.$MapName.' {padding: 0; margin: 0;}';
	$output .= '</style>';

    $output .= '<div id="'.$MapName.'" style="width:'.$width.'px; height:'.$height.'px; overflow:hidden; padding:0px;">';
   
	if (Osm_LoadLibraryMode == SERVER_EMBEDDED){
	  $output .= '<script type="text/javascript" src="'.Osm_OL_LibraryLocation.'"></script>';
	  $output .= '<script type="text/javascript" src="'.Osm_OSM_LibraryLocation.'"></script>';
	}
	elseif (Osm_LoadLibraryMode == SERVER_WP_ENQUEUE){
	  // registered and loaded by WordPress
	}
	else{
	  $this->traceText(DEBUG_ERROR, "e_library_config");
	}
    $output .= '<script type="text/javascript">';
    $output .= '/* <![CDATA[ */';
    $output .= 'jQuery(document).ready(';
    $output .= 'function($) {';

    $output .= Osm_OpenLayers::addOsmLayer($MapName, $type, $ov_map, $array_control, $extmap_type, $extmap_name, $extmap_address, $extmap_init);

    // add a clickhandler if needed
    $msg_box = strtolower($msg_box);
    if ( $msg_box == 'sc_gen' || $msg_box == 'lat_long'){
      $output .= Osm_OpenLayers::AddClickHandler($msg_box);
    }
    // set center and zoom of the map
    $output .= Osm_OpenLayers::setMapCenterAndZoom($lat, $long, $zoom);

    // Add the Layer with GPX Track
    if ($gpx_file != 'NoFile'){ 
      $output .= Osm_OpenLayers::addGmlLayer($GpxName, $gpx_file,$gpx_colour,'GPX');
    }

    // Add the Layer with KML Track
    if ($kml_file != 'NoFile'){ 
      $output .= Osm_OpenLayers::addGmlLayer($KmlName, $kml_file,$kml_colour,'KML');
    }

    // Add the marker here which we get from the file
    if ($marker_file != 'NoFile'){    
      $output .= Osm_OpenLayers::addTextLayer($marker_file);
    }  

    $marker_all_posts = strtolower($marker_all_posts);
    if ($marker_all_posts == 'y'){
      //$this->traceText(DEBUG_ERROR, "e_use_marker_all_posts");
      $import_type  = 'osm';
    }

    if ($import_type  != 'no'){
      $output .= Osm::getImportLayer($import_type, $import_UserName, $marker_name, $marker_height, $marker_width);
    }
  
   // just add single marker 
   if ($marker  != 'No'){  
     global $post;  
     list($temp_lat, $temp_lon, $temp_popup_custom_field) = split(',', $marker);
     $temp_popup_custom_field = trim($temp_popup_custom_field);
     $temp_popup = get_post_meta($post->ID, $temp_popup_custom_field, true); 
     list($temp_lat, $temp_lon) = Osm::checkLatLongRange('Marker',$temp_lat, $temp_lon); 
     $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'marker'=>$marker_name, 'text'=>$temp_popup);
     $output .= Osm_OpenLayers::addMarkerListLayer('Marker', $marker_name, $marker_width, $marker_height,$MarkerArray,-12,-12,'true');
    }
  
    $output .= '}';
    $output .= ');';
    $output .= '/* ]]> */';
    $output .= ' </script>';
	$output .= '</div>';
    return $output;
	}

	
	// add OSM-config page to Settings
	function admin_menu($not_used){
    // place the info in the plugin settings page
		add_options_page(__('OpenStreetMap Manager', 'Osm'), __('OSM', 'Osm'), 5, basename(__FILE__), array('Osm', 'options_page_osm'));
	}
  
  // ask WP to handle the loading of scripts
  // if it is not admin area
  function show_enqueue_script() {
    wp_enqueue_script(array ('jquery'));
	
	if (Osm_LoadLibraryMode == SERVER_EMBEDDED){
      // it is loaded when the map is displayed
	}
	elseif (Osm_LoadLibraryMode == SERVER_WP_ENQUEUE){
      //wp_enqueue_script('OlScript', 'http://www.openlayers.org/api/OpenLayers.js');
      //wp_enqueue_script('OsnScript', 'http://www.openstreetmap.org/openlayers/OpenStreetMap.js');
	  wp_enqueue_script('OlScript',Osm_OL_LibraryLocation);
      wp_enqueue_script('OsnScript',Osm_OSM_LibraryLocation);
	}
	else{
	  // Errormsg is traced at another place
	}	
  }
}	// End class Osm

$pOsm = new Osm();

// This is meant to be the interface used
// in your WP-template
// returns Lat data of coordination
function OSM_getCoordinateLat($a_import)
{
	global $post;

  $a_import = strtolower($a_import);
  if ($a_import == 'osm'){
	  list($lat, $lon) = split(',', get_post_meta($post->ID, get_settings('osm_custom_field'), true));
  }
  else if ($a_import == 'wpgmg'){
	  $lat = get_post_meta($post->ID, WPGMG_LAT, true);
  }
  else {
    $this->traceText(DEBUG_ERROR, "e_php_getlat_missing_arg");
    $lat = 0;
  }
  if ($lat != '') {
	  return trim($lat);
  } 
  return '';
}

// returns Lon data
function OSM_getCoordinateLong($a_import)
{
	global $post;
  
  $a_import = strtolower($a_import);
  if ($a_import == 'osm'){
	  list($lat, $lon) = split(',', get_post_meta($post->ID, get_settings('osm_custom_field'), true));
  }
  else if ($a_import == 'wpgmg'){
	  list($lon) = get_post_meta($post->ID,WPGMG_LON, true);
  }
  else {
    $this->traceText(DEBUG_ERROR, "e_php_getlon_missing_arg");
    $lon = 0;
  }
  if ($lon != '') {
	  return trim($lon);
  } 
  return '';
}

function OSM_getOpenStreetMapUrl() {
  $zoom_level = get_settings('osm_zoom_level');  
	$lat = $lat == ''? OSM_getCoordinateLat('osm') : $lat;
	$lon = $lon == ''? OSM_getCoordinateLong('osm'): $lon;
  return URL_INDEX.URL_LAT.$lat.URL_LON.$lon.URL_ZOOM_01.$zoom_level.URL_ZOOM_02;
}

function OSM_echoOpenStreetMapUrl(){
  echo OSM_getOpenStreetMapUrl() ;
}
?>
