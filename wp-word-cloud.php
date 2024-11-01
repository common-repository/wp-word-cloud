<?php
/*
Plugin Name: WordPress Word Cloud
Plugin URI: http://www.brechtvds.be
Description: Add custom word clouds to any page
Version: 0.1
Author: Brecht Vandersmissen
Author URI: http://www.brechtvds.be
License: GPL2
*/

if (!defined('WPWORDCLOUD_THEME_DIR'))
    define('WPWORDCLOUD_THEME_DIR', ABSPATH . 'wp-content/themes/' . get_template());

if (!defined('WPWORDCLOUD_PLUGIN_NAME'))
    define('WPWORDCLOUD_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (!defined('WPWORDCLOUD_PLUGIN_DIR'))
    define('WPWORDCLOUD_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WPWORDCLOUD_PLUGIN_NAME);

if (!defined('WPWORDCLOUD_PLUGIN_URL'))
    define('WPWORDCLOUD_PLUGIN_URL', WP_PLUGIN_URL . '/' . WPWORDCLOUD_PLUGIN_NAME);

if (!defined('WPWORDCLOUD_VERSION_KEY'))
    define('WPWORDCLOUD_VERSION_KEY', 'wpwordcloud_version');

if (!defined('WPWORDCLOUD_VERSION_NUM'))
    define('WPWORDCLOUD_VERSION_NUM', '0.0.1');

add_option(WPWORDCLOUD_VERSION_KEY, WPWORDCLOUD_VERSION_NUM);

add_shortcode("wp-word-cloud", "wpwordcloud_handler");

function wpwordcloud_handler($options, $content = null) {
    $options = shortcode_atts(array(
        'color' => 'blue',
        'levels' => '5'
    ), $options);

    $tags = wpwordcloud_get_tags($content);

    return wpwordcloud_function($options, $tags);
}

function wpwordcloud_get_tags($text) {
    $text = str_replace('&#8216;',"'",$text);
    $text = str_replace('&#8217;',"'",$text);
    $text = str_replace('&#8218;',"'",$text);
    $text = str_replace('&#8242;',"'",$text);
    $text = str_replace('&#8220;','"',$text);
    $text = str_replace('&#8221;','"',$text);
    $text = str_replace('&#8222;','"',$text);
    $text = str_replace('&#8243;','"',$text);
    $chars = str_split($text);

    $tags_text = array();
    $in_tag = false;
    $in_string = false;
    $in_double_string = false;
    $start_tag_i = 0;

    for($i=0; $i < count($chars); $i++)
    {
        $char = $chars[$i];

        if(!$in_tag)
        {
            if($char == '[') {
                $in_tag = true;
                $start_tag_i = $i+1;
            }
        }
        else
        {
            if(!$in_double_string && $char == "'") {
                $in_string = !$in_string;
            } else if(!$in_string && $char == '"') {
                $in_double_string = !$in_double_string;
            } else if($char == ']' && !$in_double_string && !$in_string) {
                $in_tag = false;
                $tags_text[] = substr($text, $start_tag_i, ($i-$start_tag_i));
            }
        }
    }

    $tags = array();
    foreach($tags_text as $tag_text)
    {
        $tag = wpwordcloud_get_tag($tag_text);
        if(!is_null($tag)) {
            $tags[] = $tag;
        }
    }

    return $tags;
}

function wpwordcloud_get_tag($text) {
    $tag = array();

    preg_match_all('/[a-z]+=\"[^\"]*\"/', $text, $double_quoted_options);
    preg_match_all('/[a-z]+=\'[^\']*\'/', $text, $single_quoted_options);

    $options = array_merge($double_quoted_options[0], $single_quoted_options[0]);

    foreach($options as $option)
    {
        list($param, $value) = explode('=', $option, 2);

        $tag[$param] = substr($value, 1, -1);
    }

    return $tag;
}

function wpwordcloud_function($options, $tags) {
    if(array_key_exists('color', $options)) {
        $color = $options['color'];
        if(!ctype_xdigit($color)) {
            switch ($color) {
                case 'green':
                    $color = '0C6200';
                    break;
                case 'red':
                    $color = 'A11C1C';
                    break;
                default:
                    $color = '204985'; // Blue
            }
        }
    } else {
        $color = '204985';
    }

    $output = '<ul class="wp-word-cloud-container" data-color="'.$color.'">';

    $weight_min = 0;
    $weight_max = 0;
    foreach($tags as $tag)
    {
        $weight = array_key_exists('weight', $tag) && is_numeric($tag['weight']) ? $tag['weight'] : 0;

        if($weight < $weight_min) $weight_min = $weight;
        if($weight > $weight_max) $weight_max = $weight;
    }

    $weight_modifier = 8/($weight_max - $weight_min);

    foreach($tags as $tag)
    {
        $weight = array_key_exists('weight', $tag) ? $tag['weight'] : 0;
        $weight = floor(($weight - $weight_min) * $weight_modifier);

        $link = array_key_exists('link', $tag) ? $tag['link'] : null;

        $output .= '<li data-weight="'.$weight.'">';
        if(!is_null($link)) $output .= '<a href="'.$link.'" target="_blank">';
        $output .= $tag['text'];
        if(!is_null($link)) $output .= '</a>';
        $output .= '</li>';
    }

    $output .= '</ul>';
    return $output;
}

function log_me($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

function wpwordcloud_init() {
    if (!is_admin()) {
        wp_enqueue_script('jquery');

        wp_register_script('wp-word-cloud-js', WPWORDCLOUD_PLUGIN_URL . '/wp-word-cloud.js');
        wp_enqueue_script('wp-word-cloud-js');

        wp_register_style('wp-word-cloud-css', WPWORDCLOUD_PLUGIN_URL . '/wp-word-cloud.css');
        wp_enqueue_style('wp-word-cloud-css');
    }
}

add_action('init', 'wpwordcloud_init');
?>