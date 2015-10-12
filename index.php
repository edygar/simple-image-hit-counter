<?php
/*
	Plugin Name: Simple Image Hit Counter
	Version: 0.1-alpha
	Description: Provides a endpoint to count hits on a given post
	Author: Edygar de Lima Oliveira <edygardelima@gmail.com>
	Author URI: http://www.github.com/edygar
*/

if ( ! defined( 'ABSPATH' ) ) {
	define( 'WP_USE_THEMES', false );
	$rootpath = dirname(dirname(__DIR__));

	if (file_exists($rootpath.'/wp-config.php'))
		include($rootpath.'/wp-config.php');
	else
		include(dirname($rootpath).'/wp-config.php');

	$info = [];
	if (preg_match('@/(?P<post_id>[0-9]+)\..*?$@', $_SERVER['REQUEST_URI'], $info))
		hit_post($info['post_id']);

	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header("Expires: 0"); // Proxies.
	header('Content-Type: image/png');
	echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
}

function hit_counter_url($post_id = null) {
	if ($post_id === null) 
		$post_id = get_the_ID();

	return plugins_url("simple-image-hit-counter/index.php/$post_id.png");
}

function get_hit_counter ($id = null) {
	return "<img role='presentation' alt='' src='".hit_counter_url($id)."'/>";
}

function the_hit_counter ($id = null) {
	echo get_hit_counter();
}


function post_hit_score() {
	return (post_hits(get_the_ID())/(time()/strtotime(get_post()->post_date)));
}


function join_hit_count($statement, $wp_query) {
	global $wpdb;
	return "$statement
    LEFT JOIN {$wpdb->postmeta} AS hit_count
	         ON	(
				         		{$wpdb->posts}.ID = hit_count.post_id
			          AND hit_count.meta_key = 'post_views_count'
		          )
	";
}


function order_by_hit_count($statement, $wp_query) {
	global $wpdb;
	/*
	  Hackenews algorithm
	  http://amix.dk/blog/post/19574	
	*/
	return "
		( CAST(hit_count.meta_value as UNSIGNED)
			/ power(
				(
					(
						NOW() - post_date
					) / 60
				)  / 60
				, 1.8
			)
		) DESC
	".($statement? ", $statement": "");
}


function hit_post($post_id) {
	$count_key = 'post_views_count';
	$count = get_post_meta($post_id, $count_key, true);

	if($count == '') { 
		$count = 1;
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
	} else {
		$count++;
		update_post_meta($post_id, $count_key, $count);
	}
}

function post_hits($post_id){
	$count_key = 'post_views_count';
	$count = get_post_meta($post_id, $count_key, true);

	if($count=='') {
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
		return 0;
	}

	return intval($count);
}