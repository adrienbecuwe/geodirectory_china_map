<?php
/**
 * Plugin Name: GeoDirectory Chinese Maps
 * Plugin URI: https://example.com
 * Description: Adds Chinese map provider support (Amap, Baidu, Tencent) to GeoDirectory plugin.
 * Version: 1.0.0
 * Author: Custom
 * License: GPL2
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * 
 * Text Domain: geodir-chinese-maps
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'GEODIR_CHINESE_MAPS_VERSION', '1.0.0' );
define( 'GEODIR_CHINESE_MAPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEODIR_CHINESE_MAPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if GeoDirectory is active before loading our plugin
 */
function geodir_chinese_maps_init() {
    // Check if GeoDirectory is active
    if ( ! class_exists( 'GeoDirectory' ) && ! function_exists( 'geodir_load_textdomain' ) ) {
        add_action( 'admin_notices', 'geodir_chinese_maps_missing_geodir_notice' );
        return;
    }
    
    // Map class is already loaded by GeoDirectory core plugin
    // require_once GEODIR_CHINESE_MAPS_PLUGIN_DIR . 'class-geodir-map.php';
    
    // Initialize our Chinese maps functionality
    global $geodir_chinese_maps;
    $geodir_chinese_maps = new GeoDir_Chinese_Maps();
}

/**
 * Show admin notice if GeoDirectory is not active
 */
function geodir_chinese_maps_missing_geodir_notice() {
    echo '<div class="error notice"><p>';
    echo __( 'GeoDirectory Chinese Maps requires the GeoDirectory plugin to be installed and activated.', 'geodir-chinese-maps' );
    echo '</p></div>';
}

/**
 * Load the plugin after all regular plugins are loaded
 * This ensures GeoDirectory's classes are available
 * Using priority 20 to load after GeoDirectory (which typically loads at priority 10)
 */
add_action( 'plugins_loaded', 'geodir_chinese_maps_init', 20 );

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'geodir_chinese_maps_activate' );

function geodir_chinese_maps_activate() {
    // Check if GeoDirectory is active
    if ( ! class_exists( 'GeoDirectory' ) && ! function_exists( 'geodir_load_textdomain' ) ) {
        wp_die( __( 'GeoDirectory Chinese Maps requires the GeoDirectory plugin to be installed and activated.', 'geodir-chinese-maps' ) );
    }
}

/**
 * Load admin integration if we're in admin area
 */
if ( is_admin() ) {
    require_once GEODIR_CHINESE_MAPS_PLUGIN_DIR . 'geodir-amap-admin.php';
}
