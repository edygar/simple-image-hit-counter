<?php
/*
	Plugin Name: Simple Image Hit Counter
	Version: 0.1
	Description: Provides a endpoint to count hits on a given post
	Author: Edygar de Lima Oliveira <edygardelima@gmail.com>
	Author URI: http://www.github.com/edygar
*/

/**
 * When directly accessed, this script counts the hit on given post ID and then
 * responds the user with a transparent pixel
 */
if ( ! defined( 'ABSPATH' ) ) {
	/* Includes the wordpress funcionality */
	define( 'WP_USE_THEMES', false );
	$rootpath = dirname(dirname(__DIR__));

	if (file_exists($rootpath.'/wp-config.php'))
		include($rootpath.'/wp-config.php');
	else
		include(dirname($rootpath).'/wp-config.php');

	// Retrieves the Post ID from the URL and increments its counter
	$info = [];
	if (preg_match('@/(?P<post_id>[0-9]+)\..*?$@', $_SERVER['REQUEST_URI'], $info))
		hit_post($info['post_id']);

	// Avoids caching and responds with a transparent png pixel
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header("Expires: 0"); // Proxies.
	header('Content-Type: image/png');
	echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
}

/**
 * Provides the hit count endpoint url
 */
function hit_counter_url($post_id = null) {
	if ($post_id === null)
		$post_id = get_the_ID();

	return plugins_url("simple-image-hit-counter/index.php/$post_id.png");
}

/**
 * Generates the image tag with the hit count endpoint url
 */
function get_hit_counter ($id = null) {
	return "<img role='presentation' alt='' src='".hit_counter_url($id)."'/>";
}

/**
 * Prints the image Tag with the hit count endpoint url
 */
function the_hit_counter ($id = null) {
	echo get_hit_counter();
}

/**
 * Calculates the the post hit score
 */
function post_hit_score($post_id = null) {
	return (post_hits($post_id)/(time()/strtotime(get_post($post_id)->post_date)));
}

/**
 * filter to be applied on queries in order to expose the meta with the count
 */
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


/**
 * Order the posts accordingly to both its score and its date, using the Hacker
 * News formula on ordering. Requires the `join_hit_count` filter to work.
 *
 * Hackenews algorithm
 * http://amix.dk/blog/post/19574
 */
function order_by_hit_count($statement, $wp_query) {
	global $wpdb;
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


/**
 * Increments the hit counter for a given post
 */
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

/**
 * Provides the current counting for a given post
 */
function post_hits($post_id = null){
	if ($post_id === null)
		$post_id = get_the_ID();

	$count_key = 'post_views_count';
	$count = get_post_meta($post_id, $count_key, true);

	if($count=='') {
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
		return 0;
	}

	return intval($count);
}
