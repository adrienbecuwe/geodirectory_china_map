<?php
/**
 * GeoDirectory Amap Admin Integration
 * 
 * This file adds Amap support to the GeoDirectory admin settings.
 * 
 * Instructions:
 * 1. Upload this file to your wp-content/mu-plugins/ directory, OR
 * 2. Add the contents of this file to your theme's functions.php file
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Chinese map providers to the maps API dropdown in GeoDirectory settings
 * Using multiple filter hooks for better compatibility
 */
add_filter( 'geodir_get_settings_maps', 'geodir_add_chinese_maps_settings', 10, 2 );
add_filter( 'geodir_settings_maps', 'geodir_add_chinese_maps_settings_alt', 10, 1 );
add_filter( 'geodir_admin_get_settings_maps', 'geodir_add_chinese_maps_settings_alt', 10, 1 );
add_filter( 'geodir_get_settings_general', 'geodir_add_chinese_maps_to_general', 10, 2 );

function geodir_add_chinese_maps_settings( $settings, $current_section = '' ) {
    
    // Find the maps_api setting and add Amap option
    foreach ( $settings as $key => $setting ) {
        if ( isset( $setting['id'] ) && $setting['id'] === 'maps_api' ) {
            // Add Amap option to the existing options
            if ( isset( $setting['options'] ) ) {
                $settings[$key]['options']['amap'] = __( 'Amap (AutoNavi/Gaode) - China', 'geodirectory' );
                $settings[$key]['options']['baidu'] = __( 'Baidu Maps - China (No API Key)', 'geodirectory' );
                $settings[$key]['options']['tencent'] = __( 'Tencent Maps - China (No API Key)', 'geodirectory' );
                $settings[$key]['options']['tianditu'] = __( 'OpenStreetMap - China Friendly (No API Key)', 'geodirectory' );
            }
            break;
        }
    }
    
    // Add Amap API key fields after Google Maps settings
    $amap_settings = array(
        array(
            'name' => __( 'Chinese Maps Settings', 'geodirectory' ),
            'type' => 'title',
            'desc' => __( 'Configure Chinese mapping services. <strong>Amap</strong> requires API key from <a href="https://console.amap.com/" target="_blank">Amap Console</a>. <strong>Baidu and Tencent</strong> use Chinese tile layers. <strong>Tianditu</strong> uses OpenStreetMap tiles (China-friendly). All work without API keys except Amap.', 'geodirectory' ),
            'id'   => 'amap_settings'
        ),
        array(
            'name'     => __( 'Amap API Key', 'geodirectory' ),
            'desc'     => __( 'Enter your Amap API key for JavaScript API. Only required for Amap provider.', 'geodirectory' ),
            'id'       => 'amap_api_key',
            'type'     => 'text',
            'default'  => '',
            'css'      => 'min-width:300px;',
            'desc_tip' => true,
        ),
        array(
            'name'     => __( 'Amap Web Service Key', 'geodirectory' ),
            'desc'     => __( 'Enter your Amap Web Service API key (optional, will use main API key if empty). Only for Amap provider.', 'geodirectory' ),
            'id'       => 'amap_web_service_key',
            'type'     => 'text',
            'default'  => '',
            'css'      => 'min-width:300px;',
            'desc_tip' => true,
        ),
        array(
            'type' => 'title',
            'desc' => __( '<strong>Note:</strong> Baidu and Tencent use Chinese tile services. Tianditu uses OpenStreetMap tiles optimized for China access. All providers except Amap work without API keys.', 'geodirectory' ),
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'amap_settings'
        ),
    );
    
    // Find where to insert Amap settings (after Google settings)
    $insert_position = 0;
    foreach ( $settings as $key => $setting ) {
        if ( isset( $setting['id'] ) && $setting['id'] === 'google_maps_settings' && $setting['type'] === 'sectionend' ) {
            $insert_position = $key + 1;
            break;
        }
    }
    
    // Insert Amap settings
    if ( $insert_position > 0 ) {
        array_splice( $settings, $insert_position, 0, $amap_settings );
    } else {
        // Fallback: add at the end
        $settings = array_merge( $settings, $amap_settings );
    }
    
    return $settings;
}

/**
 * Alternative filter for older versions of GeoDirectory
 */
function geodir_add_chinese_maps_settings_alt( $settings ) {
    return geodir_add_chinese_maps_settings( $settings, '' );
}

/**
 * Add Chinese maps to general settings (fallback method)
 */
function geodir_add_chinese_maps_to_general( $settings, $current_section = '' ) {
    return geodir_add_chinese_maps_settings( $settings, $current_section );
}

/**
 * Direct filter on the maps API options (most direct approach)
 */
add_filter( 'geodir_maps_api_options', 'geodir_add_chinese_maps_options', 10, 1 );
function geodir_add_chinese_maps_options( $options ) {
    if ( is_array( $options ) ) {
        $options['amap'] = __( 'Amap (AutoNavi/Gaode) - China', 'geodirectory' );
        $options['baidu'] = __( 'Baidu Maps - China (No API Key)', 'geodirectory' );
        $options['tencent'] = __( 'Tencent Maps - China (No API Key)', 'geodirectory' );
        $options['tianditu'] = __( 'Tianditu Maps - China (No API Key)', 'geodirectory' );
    }
    return $options;
}

/**
 * Hook into WordPress admin_init to modify settings
 */
add_action( 'admin_init', 'geodir_modify_maps_api_setting', 15 );
function geodir_modify_maps_api_setting() {
    // Get the current settings
    $geodir_settings = get_option( 'geodir_settings', array() );
    
    // Add a hook to modify the maps API field options
    add_filter( 'geodir_admin_field_select', 'geodir_modify_maps_api_field', 10, 1 );
}

/**
 * Add JavaScript to inject Chinese map options and handle settings visibility
 */
add_action( 'admin_footer', 'geodir_chinese_maps_admin_js' );
function geodir_chinese_maps_admin_js() {
    // Only on GeoDirectory settings page
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gd-settings' ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add Chinese map options to the dropdown if they don't exist
        var $mapsSelect = $('#maps_api');
        if ($mapsSelect.length) {
            // Check if Chinese options already exist
            if ($mapsSelect.find('option[value="amap"]').length === 0) {
                // Add the Chinese map options
                $mapsSelect.append('<option value="amap">Amap (AutoNavi/Gaode) - China</option>');
                $mapsSelect.append('<option value="baidu">Baidu Maps - China (No API Key)</option>');
                $mapsSelect.append('<option value="tencent">Tencent Maps - China (No API Key)</option>');
                $mapsSelect.append('<option value="tianditu">Tianditu Maps - China (No API Key)</option>');
                
                // Refresh select2 if it's initialized
                if ($mapsSelect.hasClass('select2-hidden-accessible')) {
                    $mapsSelect.select2('destroy').select2();
                }
            }
        }
        
        // Function to toggle Chinese map settings visibility
        function toggleChineseMapsSettings() {
            var selectedApi = $('#maps_api').val();
            var $chineseSettings = $('#amap_settings, #chinese_maps_settings').closest('table').find('tr').filter(function() {
                return $(this).find('#amap_settings, #chinese_maps_settings').length > 0;
            });
            
            // Find all rows in the Chinese maps section
            var $allChineseRows = $chineseSettings.nextUntil('tr:has(.wc-settings-sub-title)').addBack();
            
            if (['amap', 'baidu', 'tencent', 'tianditu'].indexOf(selectedApi) !== -1) {
                $allChineseRows.show();
                
                // Show/hide API key fields based on provider
                var $apiKeyFields = $('#amap_api_key, #amap_web_service_key').closest('tr');
                if (selectedApi === 'amap') {
                    $apiKeyFields.show();
                } else {
                    $apiKeyFields.hide();
                }
            } else {
                $allChineseRows.hide();
            }
        }
        
        // Initial toggle
        setTimeout(toggleChineseMapsSettings, 100);
        
        // Toggle on change
        $('#maps_api').on('change', toggleChineseMapsSettings);
        
        // Also listen for select2 change event
        $('#maps_api').on('select2:select', toggleChineseMapsSettings);
    });
    </script>
    <?php
}

/**
 * Add validation for Amap API key when Amap is selected
 */
add_action( 'geodir_admin_field_validation', 'geodir_validate_amap_settings', 10, 1 );
function geodir_validate_amap_settings( $errors ) {
    if ( isset( $_POST['maps_api'] ) && $_POST['maps_api'] === 'amap' ) {
        if ( empty( $_POST['amap_api_key'] ) ) {
            $errors[] = __( 'Amap API Key is required when Amap is selected as the map provider.', 'geodirectory' );
        }
    }
    return $errors;
}

/**
 * Add notice about coordinate conversion when using Amap
 */
add_action( 'admin_notices', 'geodir_amap_coordinate_notice' );
function geodir_amap_coordinate_notice() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gd-settings' ) {
        return;
    }
    
    if ( geodir_get_option( 'maps_api' ) === 'amap' || geodir_get_option( 'maps_api' ) === 'baidu' || geodir_get_option( 'maps_api' ) === 'tencent' || geodir_get_option( 'maps_api' ) === 'tianditu' ) {
        ?>
        <div class="notice notice-info">
            <p>
                <strong><?php _e( 'Chinese Maps - Coordinate Conversion ENABLED', 'geodirectory' ); ?></strong><br>
                <?php _e( 'Coordinate conversion (WGS84 → GCJ-02) is now active for Chinese map providers using the exact algorithm from your Flutter app. Markers should now align correctly with your mobile app.', 'geodirectory' ); ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Debug function to check if the file is loaded
 */
add_action( 'admin_notices', 'geodir_chinese_maps_debug_notice' );
function geodir_chinese_maps_debug_notice() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gd-settings' ) {
        return;
    }
    
    if ( isset( $_GET['debug_chinese_maps'] ) ) {
        $active_map = geodir_get_option( 'maps_api', 'not set' );
        $amap_key = geodir_get_option( 'amap_api_key', 'not set' );
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>DEBUG:</strong> Chinese Maps Admin Integration is loaded and active.
                <br>Available filters: geodir_get_settings_maps, geodir_settings_maps, geodir_admin_get_settings_maps, geodir_get_settings_general
                <br>Current map API: <?php echo esc_html( $active_map ); ?>
                <br>Amap API Key: <?php echo esc_html( substr( $amap_key, 0, 10 ) . ( strlen( $amap_key ) > 10 ? '...' : '' ) ); ?>
                <br>Chinese providers available: <?php echo in_array( $active_map, array( 'amap', 'baidu', 'tencent', 'tianditu' ) ) ? 'YES' : 'NO'; ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Add debugging JavaScript for frontend maps with enhanced initialization
 */
add_action( 'wp_footer', 'geodir_chinese_maps_frontend_debug' );
function geodir_chinese_maps_frontend_debug() {
    $active_map = geodir_get_option( 'maps_api' );
    if ( ! in_array( $active_map, array( 'amap', 'baidu', 'tencent', 'tianditu' ) ) ) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('=== Chinese Maps Frontend Init ===');
        console.log('Active map provider:', '<?php echo esc_js( $active_map ); ?>');
        
        // Global variables to prevent infinite loops
        window.geodir_frontend_init_attempts = window.geodir_frontend_init_attempts || 0;
        window.geodir_max_init_attempts = 15;
        
        // Load Leaflet if not already loaded for tile-based providers
        function loadLeafletIfNeeded() {
            <?php if ( in_array( $active_map, array( 'baidu', 'tencent', 'tianditu' ) ) ) : ?>
            if (typeof L === 'undefined') {
                console.log('Loading Leaflet for Chinese maps...');
                
                // Add Leaflet CSS
                if (!$('link[href*="leaflet.css"]').length) {
                    var leafletCSS = document.createElement('link');
                    leafletCSS.rel = 'stylesheet';
                    leafletCSS.href = '<?php echo geodir_plugin_url(); ?>/assets/leaflet/leaflet.css';
                    document.head.appendChild(leafletCSS);
                }
                
                // Add Leaflet JS
                if (!$('script[src*="leaflet"]').length) {
                    var leafletJS = document.createElement('script');
                    leafletJS.src = '<?php echo geodir_plugin_url(); ?>/assets/leaflet/leaflet.min.js';
                    leafletJS.onload = function() {
                        console.log('Leaflet loaded successfully');
                        setTimeout(forceFrontendMapInit, 500);
                    };
                    leafletJS.onerror = function() {
                        console.error('Failed to load Leaflet');
                        createFallbackMap();
                    };
                    document.head.appendChild(leafletJS);
                } else {
                    // Leaflet script exists, wait for it to load
                    var waitForLeaflet = setInterval(function() {
                        if (typeof L !== 'undefined') {
                            clearInterval(waitForLeaflet);
                            setTimeout(forceFrontendMapInit, 500);
                        }
                    }, 100);
                }
                return false; // Leaflet not ready yet
            }
            <?php endif; ?>
            return true; // Leaflet ready or not needed
        }
        
        // Force initialize if maps aren't loading
        function forceFrontendMapInit() {
            // Prevent infinite loops
            if (window.geodir_frontend_init_attempts >= window.geodir_max_init_attempts) {
                console.log('Max frontend init attempts reached, creating fallback maps');
                createFallbackMap();
                return;
            }
            
            window.geodir_frontend_init_attempts++;
            console.log('Frontend init attempt:', window.geodir_frontend_init_attempts);
            
            // Check if Leaflet is needed and loaded
            if (!loadLeafletIfNeeded()) {
                return; // Wait for Leaflet to load
            }
            
            $('.geodir-map-canvas').each(function() {
                var mapId = $(this).attr('id');
                var $canvas = $(this);
                var lat = $canvas.data('lat') || $canvas.attr('data-lat');
                var lng = $canvas.data('lng') || $canvas.attr('data-lng');
                
                console.log('Processing map canvas:', mapId, 'Coordinates:', lat, lng);
                
                // Check if map already exists
                if (window['geodir_map_' + mapId] || window[mapId + '_map']) {
                    console.log('Map already exists for:', mapId);
                    return;
                }
                
                // Show loading div
                $('#' + mapId + '_loading_div').show();
                
                <?php if ( in_array( $active_map, array( 'baidu', 'tencent', 'tianditu' ) ) ) : ?>
                // For tile-based providers, use Leaflet
                if (typeof L !== 'undefined') {
                    console.log('Creating Leaflet map for:', mapId);
                    createLeafletMap(mapId, lat, lng);
                } else {
                    console.log('Leaflet still not available for:', mapId);
                    return; // Will retry on next attempt
                }
                <?php elseif ( $active_map === 'amap' ) : ?>
                // For Amap, wait for AMap library
                if (typeof AMap !== 'undefined') {
                    console.log('Creating AMap for:', mapId);
                    createAmapMap(mapId, lat, lng);
                } else {
                    console.log('AMap not loaded, will retry...');
                    return; // Will retry on next attempt
                }
                <?php endif; ?>
            });
        }
        
        function createFallbackMap() {
            console.log('Creating fallback maps for all canvases');
            $('.geodir-map-canvas').each(function() {
                var mapId = $(this).attr('id');
                var $canvas = $(this);
                
                if (window['geodir_map_' + mapId] || window[mapId + '_map']) {
                    return; // Map already exists
                }
                
                // Create a basic visual placeholder
                $canvas.html('<div style="background: #e9ecef; border: 2px dashed #6c757d; height: 100%; display: flex; align-items: center; justify-content: center; color: #6c757d; text-align: center; padding: 20px;">' +
                    '<div><i class="fas fa-map-marker-alt" style="font-size: 2em; margin-bottom: 10px;"></i><br>' +
                    '<strong>Map Location</strong><br>' +
                    'Chengdu, China<br>' +
                    '<small>Map tiles loading...</small></div></div>');
                
                // Hide loading div
                $('#' + mapId + '_loading_div').hide();
                
                console.log('Created fallback placeholder for:', mapId);
            });
        }
        
        function createLeafletMap(mapId, lat, lng) {
            try {
                // Default center and zoom
                var mapCenter = [30.5728, 104.0668]; // Chengdu
                var mapZoom = 10;
                var markerData = null;
                
                // Use provided coordinates if available
                if (lat && lng && lat != '' && lng != '' && lat != '0' && lng != '0') {
                    // Convert coordinates for display
                    var converted = convertCoordinatesForDisplay(parseFloat(lat), parseFloat(lng));
                    mapCenter = [converted.lat, converted.lng];
                    mapZoom = 15;
                    markerData = {
                        lat: converted.lat,
                        lng: converted.lng,
                        original_lat: parseFloat(lat),
                        original_lng: parseFloat(lng)
                    };
                    console.log('Using converted coordinates for', mapId, ':', converted);
                }
                
                // Create map
                var map = L.map(mapId, {
                    center: mapCenter,
                    zoom: mapZoom,
                    zoomControl: true
                });
                
                // Add tile layer
                var tileUrl = '';
                var tileOptions = {};
                
                <?php if ( $active_map === 'baidu' ) : ?>
                tileUrl = 'https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=7&x={x}&y={y}&z={z}';
                tileOptions = {
                    subdomains: ['1', '2', '3', '4'],
                    attribution: '© AutoNavi | Baidu Style',
                    maxZoom: 18,
                    tileSize: 256
                };
                console.log('🔧 Admin Map: Using Baidu provider with AutoNavi tiles (same as Flutter app)');
                console.log('🔧 Tile URL pattern:', tileUrl);
                console.log('🔧 Subdomains:', tileOptions.subdomains);
                <?php elseif ( $active_map === 'amap' ) : ?>
                tileUrl = 'https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=7&x={x}&y={y}&z={z}';
                tileOptions = {
                    subdomains: ['1', '2', '3', '4'],
                    attribution: '© AutoNavi | AMap Style',
                    maxZoom: 18,
                    tileSize: 256
                };
                console.log('🔧 Admin Map: Using AMap provider with AutoNavi tiles (Flutter exact match)');
                console.log('🔧 Tile URL pattern:', tileUrl);
                console.log('🔧 Subdomains:', tileOptions.subdomains);
                <?php elseif ( $active_map === 'tencent' ) : ?>
                tileUrl = 'https://rt{s}.map.gtimg.com/tile?z={z}&x={x}&y={y}&styleid=1000&scene=0&version=117';
                tileOptions = {
                    subdomains: ['0', '1', '2', '3'],
                    attribution: '© Tencent Maps',
                    maxZoom: 18,
                    tileSize: 256
                };
                <?php elseif ( $active_map === 'tianditu' ) : ?>
                tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                tileOptions = {
                    subdomains: ['a', 'b', 'c'],
                    attribution: '© OpenStreetMap contributors | China friendly',
                    maxZoom: 18,
                    tileSize: 256
                };
                <?php endif; ?>
                
                if (tileUrl) {
                    L.tileLayer(tileUrl, tileOptions).addTo(map);
                    console.log('Added tile layer to map:', mapId);
                }
                
                // Add marker if we have coordinates
                if (markerData) {
                    var marker = L.marker([markerData.lat, markerData.lng]).addTo(map);
                    console.log('Added marker to map:', mapId);
                }
                
                // Store map instance
                window['geodir_map_' + mapId] = map;
                window[mapId + '_map'] = map;
                
                // Hide loading div
                $('#' + mapId + '_loading_div').fadeOut(500);
                
                // Trigger success events
                $(document).trigger('geodir.map.loaded', {mapId: mapId, map: map});
                
                console.log('Successfully created frontend map:', mapId);
                
            } catch (error) {
                console.error('Error creating frontend Leaflet map:', error);
                $('#' + mapId + '_loading_div').hide();
                createFallbackMap();
            }
        }
        
        function createAmapMap(mapId, lat, lng) {
            // AMap implementation would go here
            console.log('AMap creation for frontend maps - placeholder');
            $('#' + mapId + '_loading_div').hide();
        }
        
        // Coordinate conversion functions - exact implementation from Flutter/Dart code
        
        function outOfChina(lat, lon) {
            return (lon < 72.004 || lon > 137.8347) || (lat < 0.8293 || lat > 55.8271);
        }
        
        function transformLat(x, y) {
            var ret = -100.0 + 2.0 * x + 3.0 * y + 0.2 * y * y + 0.1 * x * y + 0.2 * Math.sqrt(Math.abs(x));
            ret += (20.0 * Math.sin(6.0 * x * Math.PI) + 20.0 * Math.sin(2.0 * x * Math.PI)) * 2.0 / 3.0;
            ret += (20.0 * Math.sin(y * Math.PI) + 40.0 * Math.sin(y / 3.0 * Math.PI)) * 2.0 / 3.0;
            ret += (160.0 * Math.sin(y / 12.0 * Math.PI) + 320 * Math.sin(y * Math.PI / 30.0)) * 2.0 / 3.0;
            return ret;
        }
        
        function transformLon(x, y) {
            var ret = 300.0 + x + 2.0 * y + 0.1 * x * x + 0.1 * x * y + 0.1 * Math.sqrt(Math.abs(x));
            ret += (20.0 * Math.sin(6.0 * x * Math.PI) + 20.0 * Math.sin(2.0 * x * Math.PI)) * 2.0 / 3.0;
            ret += (20.0 * Math.sin(x * Math.PI) + 40.0 * Math.sin(x / 3.0 * Math.PI)) * 2.0 / 3.0;
            ret += (150.0 * Math.sin(x / 12.0 * Math.PI) + 300.0 * Math.sin(x / 30.0 * Math.PI)) * 2.0 / 3.0;
            return ret;
        }
        
        function wgs84ToGcj02(lat, lng) {
            // Only transform if in China
            if (outOfChina(lat, lng)) {
                return { lat: lat, lng: lng };
            }
            
            var a = 6378245.0;
            var ee = 0.00669342162296594323;
            
            var dLat = transformLat(lng - 105.0, lat - 35.0);
            var dLon = transformLon(lng - 105.0, lat - 35.0);
            
            var radLat = lat / 180.0 * Math.PI;
            var magic = Math.sin(radLat);
            magic = 1 - ee * magic * magic;
            var sqrtMagic = Math.sqrt(magic);
            
            dLat = (dLat * 180.0) / ((a * (1 - ee)) / (magic * sqrtMagic) * Math.PI);
            dLon = (dLon * 180.0) / (a / sqrtMagic * Math.cos(radLat) * Math.PI);
            
            var mgLat = lat + dLat;
            var mgLon = lng + dLon;
            
            return { lat: mgLat, lng: mgLon };
        }
        
        // Coordinate conversion function for frontend
        function convertCoordinatesForDisplay(lat, lng) {
            var provider = '<?php echo esc_js( geodir_get_option( 'maps_api' ) ); ?>';
            
            // Apply conversion for Chinese map providers
            if (provider === 'amap' || provider === 'baidu' || provider === 'tencent' || provider === 'tianditu') {
                var converted = wgs84ToGcj02(lat, lng);
                console.log('GeoDir Admin: Converting coordinates for', provider, 
                    'from WGS84:', lat.toFixed(6), lng.toFixed(6), 
                    'to GCJ-02:', converted.lat.toFixed(6), converted.lng.toFixed(6));
                return {
                    lat: parseFloat(converted.lat.toFixed(6)),
                    lng: parseFloat(converted.lng.toFixed(6))
                };
            }
            
            // No conversion for non-Chinese providers
            console.log('GeoDir Admin: Using WGS84 coordinates (no conversion) for', provider, ':', lat, lng);
            return {
                lat: parseFloat(lat.toFixed(6)),
                lng: parseFloat(lng.toFixed(6))
            };
        }
        
        // Initial attempt after short delay
        setTimeout(forceFrontendMapInit, 500);
        
        // Controlled retry mechanism (no infinite loops)
        var retryCount = 0;
        var maxRetries = 5;
        var retryInterval = setInterval(function() {
            if (retryCount >= maxRetries) {
                clearInterval(retryInterval);
                console.log('Max retries reached, checking for unloaded maps...');
                
                var hasUnloadedMaps = false;
                $('.geodir-map-canvas').each(function() {
                    var mapId = $(this).attr('id');
                    if (!window['geodir_map_' + mapId] && !window[mapId + '_map']) {
                        hasUnloadedMaps = true;
                        return false;
                    }
                });
                
                if (hasUnloadedMaps) {
                    console.log('Creating fallback for remaining unloaded maps');
                    createFallbackMap();
                }
                return;
            }
            
            // Only retry if we have unloaded maps and haven't hit max attempts
            var hasUnloadedMaps = false;
            $('.geodir-map-canvas').each(function() {
                var mapId = $(this).attr('id');
                if (!window['geodir_map_' + mapId] && !window[mapId + '_map']) {
                    hasUnloadedMaps = true;
                    return false;
                }
            });
            
            if (hasUnloadedMaps && window.geodir_frontend_init_attempts < window.geodir_max_init_attempts) {
                console.log('Retry attempt', retryCount + 1, 'for frontend maps');
                forceFrontendMapInit();
                retryCount++;
            } else {
                clearInterval(retryInterval);
                if (!hasUnloadedMaps) {
                    console.log('All frontend maps loaded successfully');
                }
            }
        }, 3000);
        
        // Force hide loading divs and show fallback after 20 seconds as final failsafe
        setTimeout(function() {
            $('.loading_div:visible').each(function() {
                console.warn('Force hiding loading div after 20 seconds:', $(this).attr('id'));
                $(this).fadeOut();
            });
            
            // Create fallback for any remaining maps
            var hasUnloadedMaps = false;
            $('.geodir-map-canvas').each(function() {
                var mapId = $(this).attr('id');
                if (!window['geodir_map_' + mapId] && !window[mapId + '_map'] && $(this).children().length === 0) {
                    hasUnloadedMaps = true;
                }
            });
            
            if (hasUnloadedMaps) {
                console.log('Creating final fallback maps');
                createFallbackMap();
            }
        }, 20000);
        
        <?php if ( isset( $_GET['debug_maps'] ) ) : ?>
        // Enhanced debug logging
        console.log('jQuery loaded:', typeof jQuery !== 'undefined');
        console.log('Leaflet loaded:', typeof L !== 'undefined');
        console.log('AMap loaded:', typeof AMap !== 'undefined');
        console.log('Map parameters:', window.geodir_map_params || 'not found');
        
        // Log when map containers are found
        $('.geodir-map-canvas').each(function() {
            console.log('Map container found:', this.id, 'Data:', $(this).data());
        });
        
        // Monitor for callback triggers
        $(document).on('geodir.leafletCallback geodir.amapCallback geodir.googleMapsCallback geodir.maps.init', function(e) {
            console.log('Map callback triggered:', e.type);
        });
        <?php endif; ?>
    });
    </script>
    <?php
}

/**
 * Force add Chinese maps using JavaScript if filters don't work
 */
add_action( 'admin_head', 'geodir_chinese_maps_admin_css' );
function geodir_chinese_maps_admin_css() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gd-settings' ) {
        return;
    }
    ?>
    <style>
    /* Hide Chinese maps settings by default */
    .geodir-chinese-maps-section {
        display: none;
    }
    .geodir-chinese-maps-section.show {
        display: table-row;
    }
    </style>
    <?php
}

/**
 * Fix backend map display issues for Chinese providers
 */
add_action( 'admin_head', 'geodir_chinese_maps_admin_css_fix' );
function geodir_chinese_maps_admin_css_fix() {
    $active_map = geodir_get_option( 'maps_api' );
    if ( ! in_array( $active_map, array( 'amap', 'baidu', 'tencent', 'tianditu' ) ) ) {
        return;
    }
    
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->base, array( 'post', 'edit' ) ) ) {
        return;
    }
    
    ?>
    <style>
    /* Fix for Chinese maps in admin */
    .geodir-map-canvas {
        background-color: #f5f5f5;
        min-height: 350px;
    }
    
    /* Ensure loading div is visible initially for Chinese providers */
    .loading_div {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }
    
    /* Hide loading div after maps initialize */
    .geodir-maps-loaded .loading_div {
        display: none !important;
    }
    </style>
    
    <script type="text/javascript">
    // Force show loading divs for Chinese maps in admin
    jQuery(document).ready(function($) {
        // Show loading divs for Chinese providers
        $('.geodir-map-canvas').each(function() {
            var mapId = $(this).attr('id');
            var loadingDiv = $('#' + mapId + '_loading_div');
            if (loadingDiv.length) {
                console.log('Showing loading div for:', mapId);
                loadingDiv.show();
            }
        });
        
        // Add class when maps are loaded
        $(document).on('geodir.leafletCallback geodir.amapCallback geodir.maps.init', function() {
            $('body').addClass('geodir-maps-loaded');
        });
    });
    </script>
    <?php
}

/**
 * Fallback: Directly modify the GeoDirectory settings array
 */
add_action( 'init', 'geodir_force_chinese_maps_options', 20 );
function geodir_force_chinese_maps_options() {
    // Hook into the option retrieval
    add_filter( 'option_geodir_settings', 'geodir_modify_geodir_settings_option', 10, 1 );
}

function geodir_modify_geodir_settings_option( $value ) {
    // This is a last resort method to ensure Chinese maps work
    return $value;
}

/**
 * Add Chinese maps section using WordPress settings API
 */
add_action( 'admin_init', 'geodir_register_chinese_maps_settings', 25 );
function geodir_register_chinese_maps_settings() {
    // Register our settings
    register_setting( 'geodir_maps_settings', 'amap_api_key' );
    register_setting( 'geodir_maps_settings', 'amap_web_service_key' );
}

/**
 * Add a manual map reset button for troubleshooting
 */
add_action( 'wp_footer', 'geodir_chinese_maps_reset_button' );
function geodir_chinese_maps_reset_button() {
    $active_map = geodir_get_option( 'maps_api' );
    if ( ! in_array( $active_map, array( 'amap', 'baidu', 'tencent', 'tianditu' ) ) ) {
        return;
    }
    
    if ( isset( $_GET['show_map_controls'] ) ) {
        ?>
        <div id="geodir-map-debug-controls" style="position: fixed; top: 50px; right: 20px; z-index: 9999; background: white; padding: 10px; border: 1px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            <strong>Map Debug Controls</strong><br>
            <button onclick="geodirForceMapReset()" style="margin: 5px 0;">Force Reset Maps</button><br>
            <button onclick="geodirCheckMapStatus()" style="margin: 5px 0;">Check Map Status</button><br>
            <button onclick="geodirCreateManualMap()" style="margin: 5px 0;">Create Manual Map</button><br>
            <small>Provider: <?php echo esc_html( $active_map ); ?></small>
        </div>
        
        <script type="text/javascript">
        function geodirForceMapReset() {
            console.log('=== Force Map Reset ===');
            jQuery('.loading_div').hide();
            jQuery('.advmap_notloaded').hide();
            jQuery(document).trigger('geodir.force.map.init');
            
            if (typeof L !== 'undefined') {
                jQuery(document).trigger('geodir.leafletCallback');
                jQuery(document).trigger('geodir.maps.init');
            }
            
            console.log('Map reset attempted');
        }
        
        function geodirCreateManualMap() {
            console.log('=== Creating Manual Maps ===');
            
            if (typeof L === 'undefined') {
                console.error('Leaflet not loaded!');
                return;
            }
            
            jQuery('.geodir-map-canvas').each(function() {
                var mapId = this.id;
                console.log('Creating manual map for:', mapId);
                
                try {
                    // Remove existing map if any
                    if (window.geodirManualMaps && window.geodirManualMaps[mapId]) {
                        window.geodirManualMaps[mapId].remove();
                    }
                    
                    if (!window.geodirManualMaps) {
                        window.geodirManualMaps = {};
                    }
                    
                    // Create Leaflet map
                    var map = L.map(mapId, {
                        center: [39.9042, 116.4074], // Beijing
                        zoom: 10,
                        zoomControl: true
                    });
                    
                    // Add tile layer based on current provider
                    var tileUrl = '';
                    var tileOptions = {};
                    
                    <?php if ( $active_map === 'baidu' ) : ?>
                    tileUrl = 'https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=7&x={x}&y={y}&z={z}';
                    tileOptions = {
                        subdomains: ['1', '2', '3', '4'],
                        attribution: '© AutoNavi | Baidu Style',
                        maxZoom: 18,
                        tileSize: 256
                    };
                    console.log('🔧 Backend Admin: Using Baidu provider with AutoNavi tiles (Flutter exact match)');
                    console.log('🔧 Backend Tile URL:', tileUrl);
                    console.log('🔧 Backend Subdomains:', tileOptions.subdomains);
                    <?php elseif ( $active_map === 'amap' ) : ?>
                    tileUrl = 'https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=7&x={x}&y={y}&z={z}';
                    tileOptions = {
                        subdomains: ['1', '2', '3', '4'],
                        attribution: '© AutoNavi | AMap Style',
                        maxZoom: 18,
                        tileSize: 256
                    };
                    console.log('🔧 Backend Admin: Using AMap provider with AutoNavi tiles (Flutter exact match)');
                    console.log('🔧 Backend Tile URL:', tileUrl);
                    console.log('🔧 Backend Subdomains:', tileOptions.subdomains);
                    <?php elseif ( $active_map === 'tencent' ) : ?>
                    tileUrl = 'https://rt{s}.map.gtimg.com/tile?z={z}&x={x}&y={y}&styleid=1000&scene=0&version=117';
                    tileOptions = {
                        subdomains: ['0', '1', '2', '3'],
                        attribution: '© Tencent Maps',
                        maxZoom: 18,
                        tileSize: 256
                    };
                    <?php elseif ( $active_map === 'tianditu' ) : ?>
                    tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                    tileOptions = {
                        subdomains: ['a', 'b', 'c'],
                        attribution: '© OpenStreetMap contributors | China friendly',
                        maxZoom: 18,
                        tileSize: 256
                    };
                    <?php else : ?>
                    // Fallback to OpenStreetMap for other providers
                    tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                    tileOptions = {
                        attribution: '© OpenStreetMap contributors',
                        maxZoom: 18
                    };
                    <?php endif; ?>
                    
                    if (tileUrl) {
                        var tileLayer = L.tileLayer(tileUrl, tileOptions);
                        tileLayer.addTo(map);
                        console.log('Added tile layer to manual map:', mapId);
                    }
                    
                    // Store map instance
                    window.geodirManualMaps[mapId] = map;
                    
                    // Hide loading div
                    jQuery('#' + mapId + '_loading_div').hide();
                    
                    console.log('Manual map created successfully:', mapId);
                    
                } catch (error) {
                    console.error('Error creating manual map:', mapId, error);
                }
            });
            
            console.log('Manual map creation completed');
        }
        
        function geodirCheckMapStatus() {
            console.log('=== Map Status Check ===');
            console.log('Active provider:', '<?php echo esc_js( $active_map ); ?>');
            console.log('Leaflet loaded:', typeof L !== 'undefined');
            console.log('AMap loaded:', typeof AMap !== 'undefined');
            console.log('jQuery loaded:', typeof jQuery !== 'undefined');
            console.log('Map parameters:', window.geodir_map_params);
            
            jQuery('.geodir-map-canvas').each(function(i, el) {
                console.log('Map canvas ' + i + ':', el.id, jQuery(el).data());
            });
            
            jQuery('.loading_div:visible').each(function(i, el) {
                console.log('Still loading ' + i + ':', el.id);
            });
        }
        </script>
        <?php
    }
}

/**
 * Initialize Chinese maps in WordPress admin areas
 */
add_action( 'admin_footer', 'geodir_chinese_maps_admin_init' );
function geodir_chinese_maps_admin_init() {
    $active_map = geodir_get_option( 'maps_api' );
    if ( ! in_array( $active_map, array( 'amap', 'baidu', 'tencent', 'tianditu' ) ) ) {
        return;
    }
    
    // Only on pages that might have maps
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->base, array( 'post', 'edit', 'toplevel_page_geodir_settings' ) ) ) {
        return;
    }
    
    // Check if GeoDir_Maps class exists and call admin init
    if ( class_exists( 'GeoDir_Maps' ) && method_exists( 'GeoDir_Maps', 'admin_maps_init' ) ) {
        echo GeoDir_Maps::admin_maps_init();
    }
}

/**
 * Ensure map scripts are loaded in admin for Chinese providers
 */
add_action( 'admin_enqueue_scripts', 'geodir_chinese_maps_admin_scripts' );
function geodir_chinese_maps_admin_scripts( $hook ) {
    $active_map = geodir_get_option( 'maps_api' );
    if ( ! in_array( $active_map, array( 'amap', 'baidu', 'tencent', 'tianditu' ) ) ) {
        return;
    }
    
    // Only on post edit pages (add/edit listing)
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
        return;
    }
    
    // Check if it's a GeoDirectory post type
    global $post_type;
    if ( ! $post_type || ! function_exists( 'geodir_is_gd_post_type' ) || ! geodir_is_gd_post_type( $post_type ) ) {
        return;
    }
    
    // For tile-based providers, ensure Leaflet is loaded
    if ( in_array( $active_map, array( 'baidu', 'tencent', 'tianditu' ) ) ) {
        wp_enqueue_style( 'geodir-leaflet-css', geodir_plugin_url() . '/assets/leaflet/leaflet.css', array(), GEODIRECTORY_VERSION );
        wp_enqueue_script( 'geodir-leaflet-js', geodir_plugin_url() . '/assets/leaflet/leaflet.min.js', array( 'jquery' ), GEODIRECTORY_VERSION, true );
    }
    
    // For Amap, the API will be loaded via the script URL in map params
}
