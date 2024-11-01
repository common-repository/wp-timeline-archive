<?php
/*
Plugin Name: WP Timeline Archive
Plugin URI: http://www.dolcebita.com/wordpress/wp-timeline-archive
Description: Shows archive in a timeline.
Author: Marcos Esperon
Version: 1.0.1
Author URI: http://www.dolcebita.com
*/

/*  Copyright 2010  MARCOS ESPERÓN (email : marcosesperon@gmail.com)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('vNUM','1.0.1');

function timeline_archive($atts = '') { 
   
  global $wpdb;
  
  if($atts != '') {
    $excluded_categories = $atts[eid];
    $where = "INNER JOIN $wpdb->term_relationships AS tr ON (ID = tr.object_id) INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE tt.taxonomy = 'category' AND tt.term_taxonomy_id NOT IN ($excluded_categories)";
  } else {
    $where = 'WHERE 1=1 ';
  }
  
  $time_difference = get_settings('gmt_offset');
	$now = gmdate("Y-m-d H:i:s",(time()+($time_difference*3600)));  
  
  $year = $month = $day = $output = '';
  
  $num_posts = $wpdb->get_var("
		SELECT COUNT(ID) 
		  FROM $wpdb->posts
    $where
		   AND post_date <= '$now' 
       AND post_status = 'publish'
       AND post_type = 'post'");
  
  if($num_posts > 0) {
  
    $sql = "SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAY(post_date) AS `day`, post_date, ID, post_title
              FROM $wpdb->posts
            $where
               AND post_date <= '$now' 
               AND post_status = 'publish'
               AND post_type = 'post'
             ORDER BY 1 DESC, 2 DESC, 3 DESC";
    $posts = $wpdb->get_results($sql);
     
    if($posts) {
    
      $output .= '<ul id="wp-timeline-archive">';
      
      foreach ($posts as $post) {
        
        if($year != $post->year) {
          if($month !='' || $day != '') {
            $month = $day = '';
            $output .= '</ul>';
          }
          if($year != '') $output .= '</ul>';
          $year = $post->year;
          $output .= '<li class="box year"><a href="'.get_option('home').'/'.$year.'/">'.$year.'</a></li><ul>';
        }
        
        if($month != $post->month || $day != $post->day) {
          if($month !='' || $day != '') $output .= '</ul>';
          $month = $post->month;
          $day = $post->day;
          $output .= '<li class="box day"><a href="'.get_option('home').'/'.$year.'/'.$month.'/'.$day.'/">'.date_i18n( __( 'd'), strtotime($post->post_date)).'<span>'.date_i18n( __( 'M \'y'), strtotime($post->post_date)).'</span></a></li><ul>';
        }
        
        $title = stripslashes(apply_filters('the_title', $post->post_title));      
        $permalink = get_permalink($post->ID);

        $attachments = get_children(array('post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order'));
        if (is_array($attachments)) {
          $count = count($attachments);
          $first_attachment = array_shift($attachments);
          $image = wp_get_attachment_image_src($first_attachment->ID, 'thumbnail', false);
          if($image[0] != '') {
            $img = '<img src="'.$image[0].'" alt="'.$title.'" title="'.$title.'" />';
          } else {
            $img = '';
          }
        } else {
          $img = '';
        }
        
        $output .= '<li class="box post"><a href="'.$permalink.'" title="'.$title.'"><span>'.$title.'</span>'.$img.'</a></li>';
        
      }
      
      if($month !='' || $day != '') $output .= '</ul>';
      if($year != '') $output .= '</ul>';
      
      $output .= '  <div class="clear"></div>';
      $output .= '</ul>';
      
    }
      
  }
  
  return $output;
  
}

function add_tla_style() {
  
  $url = '';
  $file = 'wp-timeline-archive.css';    
  
  if(@file_exists(TEMPLATEPATH .'/'. $file)) {
    $url = get_stylesheet_directory_uri() .'/'. $file;
  } elseif (@file_exists(WP_PLUGIN_DIR .'/wp-timeline-archive/'. $file) ) {
    $url = WP_PLUGIN_URL .'/wp-timeline-archive/'. $file;
  }
  
  if($url != '') {
    wp_register_style('wp-timeline-archive', $url, false, vNUM, 'all');
    wp_enqueue_style( 'wp-timeline-archive');
  }
  
}


add_shortcode('tla', 'timeline_archive');

add_action('wp_print_styles', 'add_tla_style');

?>