<?php
/*
  Plugin Name: Ordering post by custom fields 
  Plugin URI: http://www.trafficjunction.co.uk/
  Description: CouponPress extend (set articles above advertisers in the post list)
 *              - ver.0.5 optimization old version of sql query
 *              - ver.0.6 added in first term post_type = 'page' for fetch all articles
 *              - ver 0.7 added additional term for check if post have 'link' castom field with empty value
 *              - ver 0.8 created inner sorting by each from separated sql query
 *              - ver 0.9 replace hardcode wp_posts.ID to $wpdb->posts.ID to work plugin for sites that have one general db which db's separated by prefix
 *              - ver 1.0 improved ordering (first- articles , then featured advertisers and ather posts (advertisers, placeholders)
 *              - ver 1.1 removed from first part advertisers with empty link
  Version: 1.1
  Author: Dmitry Surzhikov
  Author URI: http://dmitry-devstyle.pp.ua
 */

add_filter( 'posts_request', 'request_filter', 10, 2 );

function request_filter($sql, $query) {
    
    if(($query->is_home() || $query->is_category()) && $query->is_main_query()) { //is_search()
        
        global $wpdb;       
        
        // general variables 
        $posts_per_page = (get_option('posts_per_page')) ? get_option('posts_per_page') : 10;
        $page = (get_query_var('paged')) ? get_query_var('paged') : 0; 
        $start_page = $posts_per_page*(($page!=0)?$page-1:$page);
        $category_id = (get_query_var('cat')) ? get_query_var('cat') : false;        

        
        // set category conditional
        $category = '';
        $tables = ''; 
        if($category_id) {
            $tables .= " $wpdb->term_relationships, $wpdb->term_taxonomy, ";            
            $category .= " AND $wpdb->term_relationships.object_id = $wpdb->posts.ID "; 
            $category .= " AND $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id AND ($wpdb->term_taxonomy.term_id = {$category_id} "; 
            $category .= " OR $wpdb->term_taxonomy.parent = {$category_id} ) ";
        }

        // set general where clause
        $g_where .= " WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id ";
        $g_where .= " AND $wpdb->posts.post_status = 'publish' ";
        $g_where .= " AND $wpdb->posts.post_type = 'post' ";

        $request = ''; 
        $request .= " SELECT * FROM  (SELECT $wpdb->posts.*, $wpdb->postmeta.meta_value FROM $tables $wpdb->posts ";
        $request .= " LEFT JOIN $wpdb->postmeta ON  ($wpdb->postmeta.meta_key = 'role'  AND ($wpdb->postmeta.meta_value = 'article')) "; //OR $wpdb->postmeta.meta_value = 'placeholder'
        $request .= " OR (SELECT COUNT(*) AS is_link FROM $wpdb->postmeta AS meta1 WHERE meta1.meta_key = 'link'  AND $wpdb->posts.ID = meta1.post_id ) = 0 ";
	//$request .= " OR (SELECT COUNT(*) AS is_link FROM $wpdb->postmeta AS meta2 WHERE meta2.meta_key = 'link' AND meta2.meta_value = '' AND $wpdb->posts.ID = meta2.post_id ) <> 0 ";        
        //$request .= " OR $not_link";
        $request .= $g_where;
        $request .= $category; 
        $request .= " GROUP BY $wpdb->posts.ID ";
        $request .= " ORDER BY $wpdb->posts.menu_order ASC ) a ";
        
        $request .= " UNION ";
        
        $request .= " SELECT * FROM (SELECT $wpdb->posts.*, $wpdb->postmeta.meta_value FROM $tables $wpdb->posts ";
        $request .= " LEFT JOIN $wpdb->postmeta ON (($wpdb->postmeta.meta_key = 'role' AND ($wpdb->postmeta.meta_value <> 'article' ))) "; //AND $wpdb->postmeta.meta_value <> 'placeholder'
        $request .= " OR  ((SELECT COUNT(*) AS is_role FROM $wpdb->postmeta AS meta3 WHERE meta3.meta_key = 'role'  AND $wpdb->posts.ID = meta3.post_id ) = 0 ";
        //$request .= " OR  ($not_role ";
        $request .= " AND ((SELECT COUNT(*) AS is_link FROM $wpdb->postmeta AS meta4 WHERE meta4.meta_key = 'link'  AND $wpdb->posts.ID = meta4.post_id ) = 1 ";
        $request .= " OR (SELECT COUNT(*) AS is_link FROM $wpdb->postmeta AS meta5 WHERE meta5.meta_key = 'link' AND meta5.meta_value ='' AND $wpdb->posts.ID = meta5.post_id ) = 0)) ";
        //$request .= " AND $in_link) ";
        $request .= $g_where;
        $request .= $category;
	$request .= " GROUP BY $wpdb->posts.ID ";        
        $request .= " ORDER BY $wpdb->posts.menu_order ASC ) b LIMIT $start_page,$posts_per_page";        
        $sql = $request; 
        
//	if($_SERVER['REMOTE_ADDR'] == '195.69.134.114' ) {
//            die($sql);
//	}        
        
    }
    
    return $sql;
}
