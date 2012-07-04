<?php

function rcp_is_paid_content($post_id) {
	if($post_id == '' || !is_int($post_id))
		$post_id = get_the_ID();
		
	$is_paid = get_post_meta($post_id, '_is_paid', true);
	if($is_paid) {
		// this post is for paid users only
		return true;
	}
	return false;
}

function rcp_get_paid_posts() {
	$paid_ids = array();
	$paid_posts = get_posts('meta_key=_is_paid&meta_value=1&post_status=publish&posts_per_page=-1');
	if($paid_posts) {
		foreach($paid_posts as $p) {
			$paid_ids[] = $p->ID;
		}
	}
	// return an array of paid post IDs
	return $paid_ids;
}

function rcp_currency_filter( $price ) {
	global $rcp_options;
	$currency = $rcp_options['currency'];
	$position = $rcp_options['currency_position'];
	if(!isset($position) || $position == 'before') :
		switch ($currency) :
			case "GBP" : return '&pound;' . $price; break;
			case "USD" : 
			case "AUD" : 
			case "BRL" : 
			case "CAD" : 
			case "HKD" : 
			case "MXN" : 
			case "SGD" : 
				return '&#36;' . $price; 
			break;
			case "JPY" : return '&yen;' . $price; break;
			default : return $currency . ' ' . $price; break;
		endswitch;
	else :
		switch ($currency) :
			case "GBP" : return $price . '&pound;'; break;
			case "USD" : 
			case "AUD" : 
			case "BRL" : 
			case "CAD" : 
			case "HKD" : 
			case "MXN" : 
			case "SGD" : 
				return $price . '&#36;'; 
			break;
			case "JPY" : return $price . '&yen;'; break;
			default : return $price . ' ' . $currency; break;
		endswitch;	
	endif;
}

// reverse of strstr()
function rcp_rstrstr($haystack,$needle) {
	return substr($haystack, 0,strpos($haystack, $needle));
}

// checks whether an integer is odd
function rcp_is_odd( $int )
{
  return( $int & 1 );
}

/*
* Gets the excerpt of a specific post ID or object
* @param - $post - object/int - the ID or object of the post to get the excerpt of
* @param - $length - int - the length of the excerpt in words
* @param - $tags - string - the allowed HTML tags. These will not be stripped out
* @param - $extra - string - text to append to the end of the excerpt
*/
function rcp_excerpt_by_id($post, $length = 50, $tags = '<a><em><strong><blockquote><ul><ol><li><p>', $extra = ' . . .') {
	
	if(is_int($post)) {
		// get the post object of the passed ID
		$post = get_post($post);
	} elseif(!is_object($post)) {
		return false;
	}
	$more = false;
	if(has_excerpt($post->ID)) {
		$the_excerpt = $post->post_excerpt;
	} elseif(strstr($post->post_content,'<!--more-->')) {
		$more = true;
		$length = strpos($post->post_content, '<!--more-->');
		$the_excerpt = $post->post_content;
	} else {
		$the_excerpt = $post->post_content;
	}
	if($more) {
		$the_excerpt = strip_shortcodes( strip_tags( stripslashes( substr($the_excerpt, 0, $length) ), $tags) );
	} else {
		$the_excerpt = strip_shortcodes(strip_tags(stripslashes($the_excerpt), $tags));
		$the_excerpt = preg_split('/\b/', $the_excerpt, $length * 2+1);
		$excerpt_waste = array_pop($the_excerpt);
		$the_excerpt = implode($the_excerpt);
		$the_excerpt .= $extra;
	}
	
	return wpautop($the_excerpt);
}

// this is just a sample function to change the length of the excerpts
function rcp_excerpt_length($excerpt_length) {
	// the number of words to show in the excerpt
	return 100;
}
add_filter('rcp_filter_excerpt_length', 'rcp_excerpt_length');