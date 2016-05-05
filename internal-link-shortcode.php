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

load_plugin_textdomain('cf-internal-link-shortcode');

define('CFPLSC_SHORTCODE', 'link');

function cfplsc_shortcode($atts, $wrapped = null) {
	extract(shortcode_atts(array(
		'id' => null,
		'text' => null,
		'class' => null,
		'rel' => null
	), $atts));
	$wrapped = trim($wrapped);
	if (!empty($wrapped)) {
		$text = $wrapped;
	}
	$text = trim($text);
	$id = intval($id);
	if (empty($id)) {
		return $text;
	}
	$url = get_permalink($id);
	if (empty($text)) {
		$text = get_the_title($id);
	}
	empty($class) ? $class = '' : $class = ' class="'.esc_attr($class).'"';
	empty($rel) ? $rel = '' : $rel = ' class="'.esc_attr($class).'"';
	return '<a href="'.$url.'"'.$class.$rel.'>'.esc_html($text).'</a>';
}
add_shortcode(CFPLSC_SHORTCODE, 'cfplsc_shortcode');

function cfplsc_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
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
	$wild = '%'.$wpdb->escape($title).'%';
	$posts = $wpdb->get_results("
		SELECT *
		FROM $wpdb->posts
		WHERE (
			post_title LIKE '$wild'
			OR post_name LIKE '$wild'
		)
		AND post_status = 'publish'
		ORDER BY post_title
		LIMIT 25
	");
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

function cfplsc_admin_js() {
	header('Content-type: text/javascript');
?>
cfplsc_show_shortcode = function($elem) {
	if ($elem.find('input').size() == 0) {
		$elem.append('<input type="text" value="' + $elem.attr('title') + '" />').find('input').keydown(function(e) {
			switch (e.which) {
				case 13: // enter
					return false;
					break;
				case 27: // esc
					jQuery('#cfplsc_post_title').focus();
					break;
			}
		}).focus().select();
	}
};
jQuery(function($) {
	$('#cfplsc_meta_box a.cfplsc_help').click(function() {
		$('#cfplsc_meta_box div.cfplsc_readme').slideToggle(function() {
			$('#cfplsc_post_title').css('background', '#fff');
		});
		return false;
	});
	$('#cfplsc_search_box').click(function() {
		$('#cfplsc_post_title').focus().css('background', '#ffc');
		return false;
	});
	$('#cfplsc_post_title').keyup(function(e) {
		form = $('#cfplsc_meta_box');
		term = $(this).val();
// catch everything except up/down arrow
		switch (e.which) {
			case 27: // esc
				form.find('.live_search_results ul').remove();
				break;
			case 13: // enter
			case 38: // up
			case 40: // down
				break;
			default:
				if (term == '') {
					form.find('.live_search_results ul').remove();
				}
				if (term.length > 2) {
					$.get(
						'<?php echo admin_url('index.php'); ?>',
						{
							cf_action: 'cfplsc_id_lookup',
							post_title: term
						},
						function(response) {
							$('#cfplsc_meta_box div.live_search_results').html(response).find('li').click(function() {
								$('#cfplsc_meta_box .active').removeClass('active');
								$(this).addClass('active');
								cfplsc_show_shortcode($(this));
								return false;
							});
						},
						'html'
					);
				}
				break;
		}
	}).keydown(function(e) {
// catch arrow up/down here
		form = $('#cfplsc_meta_box');
		if (form.find('.live_search_results ul li').size()) {
			switch (e.which) {
				case 13: // enter
					active = form.find('.live_search_results ul li.active');
					if (active.size()) {
						cfplsc_show_shortcode(active);
					}
					return false;
					break;
				case 40: // down
					if (!form.find('.live_search_results ul li.active').size()) {
						form.find('.live_search_results ul li:first-child').addClass('active');
					}
					else {
						form.find('.live_search_results ul li.active').next('li').addClass('active').prev('li').removeClass('active');
					}
					return false;
					break;
				case 38: // up
					if (!form.find('.live_search_results ul li.active').size()) {
						form.find('.live_search_results ul li:last-child').addClass('active');
					}
					else {
						form.find('.live_search_results ul li.active').prev('li').addClass('active').next('li').removeClass('active');
					}
					return false;
					break;
			}
		}
	});
});
<?php
	die();
}
if (is_admin()) {
	wp_enqueue_script('cfplsc_admin_js', trailingslashit(get_bloginfo('url')).'?cf_action=cfplsc_admin_js', array('jquery'));
}

function cfplsc_admin_css() {
	header('Content-type: text/css');
?>
#cfplsc_meta_box fieldset a.cfplsc_help {
	background: #f5f5f5;
	border-radius: 6px;
	-moz-border-radius: 6px;
	-webkit-border-radius: 6px;
	color: #666;
	display: block;
	font-size: 11px;
	float: right;
	padding: 4px 6px;
	text-decoration: none;
}
#cfplsc_meta_box fieldset label {
	display: none;
}
#cfplsc_meta_box fieldset input {
	width: 235px;
}
#cfplsc_meta_box .live_search_results {
	position: relative;
	z-index: 500;
}	
#cfplsc_meta_box .live_search_results ul {
	background: #fff;
	list-style: none;
	margin: 0 0 0 1px;
	padding: 0 2px 3px;
	position: absolute;
	width: 230px;
}
#cfplsc_meta_box .live_search_results ul li {
	border: 1px solid #eee;
	border-top: 0;
	cursor: pointer;
	line-height: 100%;
	margin: 0;
	overflow: hidden;
	padding: 5px;
}
#cfplsc_meta_box .live_search_results ul li.active,
#cfplsc_meta_box .live_search_results ul li:hover {
	background: #e0edf5;
	font-weight: bold;
}
#cfplsc_meta_box .live_search_results input {
	width: 200px;
}
#cfplsc_meta_box div.cfplsc_readme {
	display: none;
}
#cfplsc_meta_box div.cfplsc_readme li {
	margin: 0 10px 10px;
}
<?php
	die();
}

function cfplsc_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'?cf_action=cfplsc_admin_css" />';
}
add_action('admin_print_styles', 'cfplsc_admin_head');

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