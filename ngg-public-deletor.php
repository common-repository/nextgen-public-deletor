<?php

/*
Plugin Name: NextGEN Public Deletor
Plugin URI: http://blogs.swaind.com/NextGEN-Public-Deletor
Description: NextGEN Public Deletor is designed to delete the pictures in NextGEN Galleries on the wordpress website itself so the users don't need to login to admin to do that.
Version: 1.0
Author: Swaind
Author URI: http://blogs.swaind.com

Copyright 2011 Chris . (email : chris.chenxt@swaind.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

$plugins = get_option('active_plugins');
$required_plugin = 'nextgen-gallery/nggallery.php';
$debug_queries_on = FALSE;
define( 'NGD_PUBLIC_PLUGIN_INSERTCSS', plugin_dir_url( __FILE__ ));
define( 'NGD_NGG_PATH', NGGALLERY_ABSPATH);

//add css style
add_action( 'wp_print_styles', 'ngdpuaddHeaderStyle');
function ngdpuaddHeaderStyle() {
        wp_register_style( 'npdcss', NGD_PUBLIC_PLUGIN_INSERTCSS . 'ngdupstyle.css', '', '1.0' );
		wp_enqueue_style( 'npdcss' );
		}

add_shortcode('ngd_show', 'ngg_deletor_shortcode');
function ngg_deletor_shortcode($atts) {
	extract ( shortcode_atts(array(
			'id' => ''
            ), $atts));
	if (!$_POST['deleteimage']) {
    show_ngg_gallery_pd($id);	}
	else	{
	handleDelete($id);
	show_ngg_gallery_pd($id);	}
}


function show_ngg_gallery_pd($gid) {
	include_once (NGGALLERY_ABSPATH . "lib/ngg-db.php");
	$nggdb = new nggdb();
	$imagelist = npd_get_gallery_all($gid, 'imagedate' , 'ASC' , 'true');
	foreach ($imagelist as $image) {
		$i++ ;
		}
	$nnumrows = $i; // Number of rows returned from above query.
	$strOutput = "<div class='npd_gallery'>";
	if ($nnumrows == 0){
		$strOutput .= "No slide has been found."; // bah, modify the "Not Found" error for your needs. 
		exit();	}
	if (!is_user_logged_in()) {
		$strOutput .= "You must be registered and logged in to delete images from here</div>";
	}
	else {
	$strOutput .= "<div id=\"deleteimage\">";
	$thepath = plugin_dir_url(__FILE__);
	$strOutput .= "\n\t<form name=\"deleteimage\" id=\"deleteimage_form\" method=\"POST\" enctype=\"multipart/form-data\" accept-charset=\"utf-8\" >";
	$strOutput .= "\n\t<table class='npdtable'>";
	$strOutput .= "\n\t<thead><tr><td><strong>File Name</strong></td><td align='center'><strong>Delete</strong></td><tr></thead>";
	$ck = 1;
	foreach ($imagelist as $image) {
		$ck = $ck * (-1);
		if ($ck == 1) {$listbgcolor="#eeeeee";} else {$listbgcolor="#ffffff";}
		$strOutput .= "\n<tr style='background-color:{$listbgcolor};'>";
		$strOutput .= "\n<td><div  class='listrow'>{$image -> image_slug}</div></td><td align='center'><input type='checkbox' name='{$image -> pid}' id='{$image -> pid}' value='{$image -> pid}'></td>";
		$strOutput .= "\n</tr>";
		}
	$strOutput .= "\n\t</table>";
	$strOutput .= "\n\t\t<div align=\"center\" ><input class=\"button-primary\" type=\"submit\" name=\"deleteimage\" id=\"deleteimage_btn\" value=\"Delete\" /></div>";
    $strOutput .= "\n</form>";
	$strOutput .= "\n\t\t</div>";
    $strOutput .= "\n</div>";
	}
      echo $strOutput;
}

function handleDelete($gdid) {
        require_once(NGGALLERY_ABSPATH . "lib/meta.php");
		include_once (NGGALLERY_ABSPATH . "lib/ngg-db.php");
		$nggdb = new nggdb();
		$imagelist = $nggdb->get_gallery($gdid);
		if ($_POST['deleteimage']) 	{
			foreach ($imagelist as $image) {
				$imgid = $_POST[$image -> pid];
				if ($nggdb->delete_image($imgid)) {
					echo "<p>Slide: <strong>{$image -> image_slug}</strong> has been successfully deleted</p>";	}
				}
			}
}

function npd_get_gallery_all($id, $order_by = 'sortorder', $order_dir = 'ASC', $exclude = true, $limit = 0, $start = 0, $json = false) {
        global $wpdb;

        // init the gallery as empty array
        $gallery = array();
        $i = 0;
        
        // Check for the exclude setting
	
        // $exclude_clause = ($exclude) ? ' AND tt.exclude<>1 ' : ''; 
        
        // Say no to any other value
        $order_dir = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';
        $order_by  = ( empty($order_by) ) ? 'sortorder' : $order_by;
        
        // Should we limit this query ?
        $limit_by  = ( $limit > 0 ) ? 'LIMIT ' . intval($start) . ',' . intval($limit) : '';
        
        // Query database
        if( is_numeric($id) )
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS tt.*, t.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = %d ORDER BY tt.{$order_by} {$order_dir} {$limit_by}", $id ), OBJECT_K );
        else
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS tt.*, t.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.slug = %s ORDER BY tt.{$order_by} {$order_dir} {$limit_by}", $id ), OBJECT_K );

        // Count the number of images and calculate the pagination
        if ($limit > 0) {
            $this->paged['total_objects'] = intval ( $wpdb->get_var( "SELECT FOUND_ROWS()" ) );
            $this->paged['objects_per_page'] = max ( count( $result ), $limit );
            $this->paged['max_objects_per_page'] = ( $limit > 0 ) ? ceil( $this->paged['total_objects'] / intval($limit)) : 1;
        }
        
        // Build the object
        if ($result) {
                
            // Now added all image data
            foreach ($result as $key => $value) {
                // due to a browser bug we need to remove the key for associative array for json request 
                // (see http://code.google.com/p/chromium/issues/detail?id=883)
                if ($json) $key = $i++;               
                $gallery[$key] = new nggImage( $value ); // keep in mind each request require 8-16 kb memory usage
                
            }
        }
        
        // Could not add to cache, the structure is different to find_gallery() cache_add, need rework
        //wp_cache_add($id, $gallery, 'ngg_gallery');

        return $gallery;        
}
?>