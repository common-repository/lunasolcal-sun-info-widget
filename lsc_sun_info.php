<?php
/*
Plugin Name:  LunaSolcal - Sun Info Widget
Plugin URI:   https://www.vvse.com 
Description:  A simple widget showing the times of sunrise and sunset at a given location.
Version:      1.0
Author:       Volker Voecking Software Engineering 
Author URI:   https://www.vvse.com/products/en/lunasolcal.html 
License:      CC BY-SA 
License URI:  https://creativecommons.org/licenses/by-sa/4.0/legalcode
Text Domain:  sun_info
Domain Path:  /languages
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('LSC__PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LSC__PLUGIN_SLUG', 'sun_info');
define('LSC__SHORTCODE_TAG', 'lsc_sun_info_widget');

// Require LunaSolCal plugin widget class
require_once LSC__PLUGIN_DIR . 'classes/lsc_sun_info_widget.php';
require_once LSC__PLUGIN_DIR . 'classes/lsc_calculator.php';

// Load text domain
load_plugin_textdomain(LSC__PLUGIN_SLUG, false, basename(dirname(__FILE__)) . '/languages');


// Register the widget
function lsc_widget_register()
{
    register_widget('LSC_SunInfoWidget');
    register_uninstall_hook(__FILE__, 'lsc_uninstall');
}

function lsc_scripts_register()
{
}

function lsc_uninstall()
{
    delete_option('location');
    delete_option('latitude');
    delete_option('longitude');
    delete_option('timezone');
    delete_option('bgcolor');
    delete_option('fgcolor');
    delete_option('validLocation');
}

// Examples:
// [lsc-sun-info-widget]
// [lsc-sun-info-widget color="#ffffff"]
function lsc_tag_register($atts)
{
    $color = isset($atts['color']) ? $atts['color'] : '#ffffff';
    return "<div id='lunasolcal-sun-info-widget' data-color='$color'></div>";
}

// Init
add_shortcode(LSC__SHORTCODE_TAG, 'lsc_tag_register');

add_action('widgets_init', 'lsc_widget_register');
