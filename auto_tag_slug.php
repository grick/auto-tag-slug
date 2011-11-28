<?php
/*
Plugin Name: Auto Tag Slug
Plugin URI: http://www.grick.net
Description: Automatically generate English Words or Chinese Pinyin slug for your tags, especially helpful for non-English country users.
Version: 0.5
Author: Grick.C
Author URI: http://www.grick.net
License: GPL2
 */

require_once('admin_page.php');
require_once ('class.Chinese.php');
require_once('ms_translator.php');

$ats_options = get_option('ats_options');

function ats_pinyin($array) {
	global $ats_options;
	$table_dir = dirname(__FILE__).'/config/';
	$result_array = array();
	$array_slice = array_chunk($array, 1000);
	foreach ($array_slice as $array_1000) {
		foreach ($array_1000 as &$tag_slug) {
			$tag_slug = str_replace('|', '', $tag_slug);
		}
		$str = join('|', $array_1000);
		$encoding = ($ats_options['cnlang'] == 'Traditional Chinese') ? 'BIG5' : 'GB2312';
		$chs = new Chinese('UTF8', $encoding, $str, $table_dir);
		$str = $chs->ConvertIT();
		$chs = new Chinese($encoding, 'PinYin', $str, $table_dir);
		$long_str = $chs->ConvertIT();
		$items = explode('|', $long_str);
		foreach ($items as &$item) {
			$pinyin_array = explode(' ', trim($item));
			$str = '';
			// Exclude sigleton letter
			foreach ($pinyin_array as &$pinyin)
			{
				if ( strlen($pinyin) > 1 ) :
					$pinyin .= '-';
				elseif ( $pinyin == ' ' ) :
					$pinyin = '-';
				endif;
			}
			$str = join('', $pinyin_array);
			// Remove illegal character and last '-'
			$str = preg_replace('/-$/', '', preg_replace('/[^a-z0-9-]/i', '', $str));
			$item = strtolower($str);
		}
	}
	return $items;
}

function ats_slug_used_by_other_tag($current_tag, $slug) {
	$term_row[] = get_term_by('slug', $slug, 'post_tag');
	if ($term_row[0]) :
		return ($current_tag->term_id != $term_row[0]->term_id);
	else :
		return false;
	endif;
}

function ats_convert_tags($tags) {
	global $ats_options;
	$engine = $ats_options['engine'];
	$tags_array = array();
	foreach($tags as $tag){
		$tags_array[] = urldecode($tag->slug);
	}
	if ($engine == 'english') :
		$converted_tags = ats_bing_translate($ats_options['bing_key'], $tags_array);
	elseif ($engine == 'pinyin') :
		$converted_tags = ats_pinyin($tags_array);
	else :
		$converted_tags = $tags_array;
	endif;

	$i = 0;
	foreach($tags as &$tag){
		if ( preg_match('/[^a-z0-9- ]/', $tag->slug) && !empty($translated_array[$i]) ) {
			$tag->slug = $converted_tags[$i];
		}
		$i++;
	}
	

	$num = 0;
	foreach ($tags as $tag)
	{
		// Check if slug is used by other tag
		// HINT: Tag may be used by self, when update excuse
		$i = 1;
		$new_slug = urlencode($tag->slug);
		$new_slug_twist = $new_slug;
		while ( ats_slug_used_by_other_tag($tag, $new_slug_twist) )
		{
			$new_slug_twist = $new_slug .'-'. $i;
			$i = $i + 1;
		}
		if ($new_slug_twist) {
			wp_update_term($tag->term_id, 'post_tag', array('slug' => $new_slug_twist));
			$num += 1;
		}
	}
	return $num;
}

function ats_convert($post_ID) {
	$tags = wp_get_post_tags($post_ID);
	ats_convert_tags($tags);
}

function ats_convert_all() {
	$tags = get_terms('post_tag');
	return ats_convert_tags($tags);
}

function ats_recover_all() {
	$tags = get_terms('post_tag');
	$num = 0;
	foreach ($tags as $tag) {
		wp_update_term($tag->term_id, 'post_tag', array('slug' => sanitize_title($tag->name) ) );
		$num += 1;
	}
	return $num;
}

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	_e("Hi there!  I'm just a plugin, not much I can do when called directly.");
	exit;
}

if ( !wp_is_post_revision( $post_ID ) && $ats_options['switch'] ) {
	add_action('save_post', 'ats_convert');
}

?>
