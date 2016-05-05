<?php
/*
Plugin Name: CF Internal Link Shortcode
Plugin URI: http://crowdfavorite.com/wordpress/plugins/internal-link-shortcode
Description: Site reorganization-proof internal linking via shortcodes, referencing page/post IDs.
Version: 1.0.1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// Copyright (c) 2010-2011
//   Crowd Favorite, Ltd. - http://crowdfavorite.com
//   Alex King - http://alexking.org
// All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress - http://wordpress.org
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

load_plugin_textdomain( 'cf-internal-link-shortcode' );

define( 'CFPLSC_SHORTCODE', 'link' );

function cfplsc_shortcode( $atts, $wrapped = null ) {
	extract( shortcode_atts( array(
		'id' => null,
		'text' => null,
		'class' => null,
		'rel' => null
	), $atts ) );
	$wrapped = trim( $wrapped );
	if ( ! empty( $wrapped ) ) {
		$text = $wrapped;
	}
	$text = trim( $text );
	$id = intval( $id );
	if ( empty( $id ) ) {
		return $text;
	}
	$url = get_permalink( $id );
	if ( empty( $text ) ) {
		$text = get_the_title( $id );
	}
	empty( $class ) ? $class = '' : $class = ' class="' . esc_attr( $class ) . '"';
	empty( $rel ) ? $rel = '' : $rel = ' class="' . esc_attr( $class ) . '"';
	return '<a href="' . $url . '"' . $class . $rel . '>' . esc_html($text) . '</a>';
}
add_shortcode( CFPLSC_SHORTCODE, 'cfplsc_shortcode' );

function cfplsc_request_handler() {
	if ( ! empty( $_GET['cf_action'] ) ) {
		switch ( $_GET['cf_action'] ) {
			case 'cfplsc_id_lookup':
				cfplsc_id_lookup();
				break;
			case 'cfplsc_admin_js':
				cfplsc_admin_js();
				break;
			case 'cfplsc_admin_css':
				cfplsc_admin_css();
				die();
				break;
		}
	}
}
add_action('init', 'cfplsc_request_handler');

function cfplsc_id_lookup() {
	global $wpdb;
	$title = stripslashes($_GET['post_title']);
	$posts = $wpdb->get_results(
		$wpdb->prepare("
		SELECT *
		FROM $wpdb->posts
		WHERE (
			post_title LIKE '%s'
			OR post_name LIKE '%s'
		)
		AND post_status = 'publish'
		ORDER BY post_title
		LIMIT 25
	", $title ) );
	if (count($posts)) {
		$output = '<ul>';
		foreach ($posts as $post) {
			$output .= '<li class="'.$post->ID.'" title="['.CFPLSC_SHORTCODE.' id=\''.$post->ID.'\']">'.esc_html($post->post_title).'</li>';
		}
		$output .= '</ul>';
	}
	else {
		$output = '<ul><li>'.__('Sorry, no matches.', 'cf-internal-link-shortcode').'</li></ul>';
	}
	echo $output;
	die();
}

function cfplsc_admin_enqueue() {
	wp_enqueue_script( 'cfplsc_admin_js', plugin_dir_url( __FILE__ ) . '/script.js', array('jquery') );
	wp_localize_script( 'cfplsc_admin_js', 'Admin', array( 'index_url' => admin_url('index.php') ) );

	wp_enqueue_style( 'cfplsc_admin_css', plugin_dir_url( __FILE__ ) . '/style.css' );
}
add_action( 'admin_enqueue_scripts', 'cfplsc_admin_enqueue' );

function cfplsc_meta_box() {
?>
<fieldset>
	<a href="#" class="cfplsc_help"><?php _e('?', 'cf-internal-link-shortcode'); ?></a>
	<label for="cfplsc_post_title"><?php _e('Page / Post Title:', 'cf-internal-link-shortcode'); ?></label>
	<input type="text" name="cfplsc_post_title" id="cfplsc_post_title" autocomplete="off" />
	<div class="live_search_results"></div>
	<div class="cfplsc_readme">
		<h4><?php _e('Shortcode Syntax / Customization', 'cf-internal-link-shortcode'); ?></h4>
		<p><?php _e('There are several different ways that you can enter the shortcode:', 'cf-internal-link-shortcode'); ?></p>
		<ul>
			<li><code>[link id='123']</code> = <code>&lt;a href="{<?php _e('url of post/page #123', 'cf-internal-link-shortcode'); ?>}">{<?php _e('title of post/page #123', 'cf-internal-link-shortcode'); ?>}&lt;/a></code></li>
			<li><code>[link id='123' text='<b><?php _e('my link text', 'cf-internal-link-shortcode'); ?></b>']</code> = <code>&lt;a href="{<?php _e('url of post/page #123', 'cf-internal-link-shortcode'); ?>}"><b><?php _e('my link text', 'cf-internal-link-shortcode'); ?></b>&lt;/a></code></li>
		</ul>
		<p><?php _e('You can also add a <code>class</code> or <code>rel</code> attribute to the shortcode, and it will be included in the resulting <code>&lt;a></code> tag:', 'cf-internal-link-shortcode'); ?></p>
		<ul>
			<li><code>[link id='123' text='<?php _e('my link text', 'cf-internal-link-shortcode'); ?>' class='my-class' rel='external']</code> = <code>&lt;a href="{<?php _e('url of post/page #123', 'cf-internal-link-shortcode'); ?>}" class="my-class" rel="external"><?php _e('my link text', 'cf-internal-link-shortcode'); ?>&lt;/a></code></li>
		</ul>
		<h4><?php _e('Usage', 'cf-internal-link-shortcode'); ?></h4>
		<p><?php _e('Type into the <a href="#" id="cfplsc_search_box">search box</a> and posts whose title matches your search will be returned so that you can grab an internal link shortcode for them for use in the content of a post / page.', 'cf-internal-link-shortcode'); ?></p>
		<p><?php _e('The shortcode to link to a page looks something like this:', 'cf-internal-link-shortcode'); ?></p>
		<p><code>[link id='123']</code></p>
		<p><?php _e('Add this to the content of a post or page and when the post or page is displayed, this would be replaced with a link to the post or page with the id of 123.', 'cf-internal-link-shortcode'); ?></p>
		<p><?php _e('These internal links are site reorganization-proof, the links will change automatically to reflect the new location or name of a post or page when it is moved.', 'cf-internal-link-shortcode'); ?></p>
	</div>
</fieldset>
<?php
}
function cfplsc_add_meta_box() {
	add_meta_box('cfplsc_meta_box', __('Internal Link Shortcode Lookup', 'cf-internal-link-shortcode'), 'cfplsc_meta_box', 'post', 'side');
	add_meta_box('cfplsc_meta_box', __('Internal Link Shortcode Lookup', 'cf-internal-link-shortcode'), 'cfplsc_meta_box', 'page', 'side');

	// Public non built in post types
	$args=array(
		'public'   => true,
		'_builtin' => false
	);
	$output = 'names';
	$post_types = get_post_types($args,$output);
	if (count($post_types)) {
		foreach ($post_types as $post_type) {
			add_meta_box('cfplsc_meta_box', __('Internal Link Shortcode Lookup', 'cf-internal-link-shortcode'), 'cfplsc_meta_box', $post_type, 'side');
		}
	}
}
add_action('admin_init', 'cfplsc_add_meta_box');

?>
