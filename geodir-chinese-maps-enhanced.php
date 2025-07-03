<?php
/**
 * Plugin Name: GeoDirectory Chinese Maps & Markers Fix (Enhanced)
 * Description: Enhanced fix for marker visibility and clustering for Chinese map providers
 * Version: 1.1.0
 * Author: GeoDirectory Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('GEODIR_CHINESE_MAPS_VERSION', '1.1.0');
define('GEODIR_CHINESE_MAPS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Initialize Chinese Maps functionality
 */
function geodir_chinese_maps_enhanced_init() {
    // Check if GeoDirectory is active
    if (!class_exists('GeoDir_Admin')) {
        return;
    }
    
    // Add Chinese map providers to GeoDirectory
    add_filter('geodir_map_providers', 'geodir_add_chinese_map_providers_enhanced');
    
    // Hook into admin head for backend maps
    add_action('admin_head', 'geodir_chinese_maps_enhanced_admin_scripts');
    
    // Hook into frontend head for frontend maps  
    add_action('wp_head', 'geodir_chinese_maps_enhanced_frontend_scripts');
    
    // Enhanced marker loading for Chinese maps
    add_action('wp_footer', 'geodir_chinese_maps_enhanced_marker_fix');
    
    // Add AJAX handler for marker requests
    add_action('wp_ajax_geodir_chinese_markers', 'geodir_chinese_maps_ajax_markers');
    add_action('wp_ajax_nopriv_geodir_chinese_markers', 'geodir_chinese_maps_ajax_markers');
}
add_action('plugins_loaded', 'geodir_chinese_maps_enhanced_init', 20);

/**
 * Add Chinese map providers to the list
 */
function geodir_add_chinese_map_providers_enhanced($providers) {
    $providers['baidu'] = __('Baidu Maps', 'geodir-chinese-maps');
    $providers['amap'] = __('AMap (AutoNavi)', 'geodir-chinese-maps');
    $providers['tencent'] = __('Tencent Maps', 'geodir-chinese-maps');
    $providers['tianditu'] = __('TianDiTu Maps', 'geodir-chinese-maps');
    
    return $providers;
}

/**
 * Add admin scripts for Chinese maps
 */
function geodir_chinese_maps_enhanced_admin_scripts() {
    $active_map = geodir_get_option('map_provider', 'osm');
    
    if (!in_array($active_map, array('amap', 'baidu', 'tencent', 'tianditu'))) {
        return;
    }
    
    // Include admin scripts
    if (file_exists(GEODIR_CHINESE_MAPS_PLUGIN_DIR . 'geodir-amap-admin.php')) {
        include GEODIR_CHINESE_MAPS_PLUGIN_DIR . 'geodir-amap-admin.php';
    }
}

/**
 * Add frontend scripts for Chinese maps
 */
function geodir_chinese_maps_enhanced_frontend_scripts() {
    $active_map = geodir_get_option('map_provider', 'osm');
    
    if (!in_array($active_map, array('amap', 'baidu', 'tencent', 'tianditu'))) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    // Chinese Maps Frontend Support
    window.geodir_chinese_map_provider = '<?php echo esc_js($active_map); ?>';
    window.geodir_needs_conversion = true;
    window.geodir_chinese_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    console.log('=== Chinese Maps Enhanced Frontend Init ===');
    console.log('Active map provider:', '<?php echo esc_js($active_map); ?>');
    
    /**
     * Coordinate conversion functions for Chinese maps
     */
    
    // WGS84 to GCJ02 conversion (for AMap, Tencent)
    function wgs84ToGcj02(lng, lat) {
        var dlat = transformLat(lng - 105.0, lat - 35.0);
        var dlng = transformLng(lng - 105.0, lat - 35.0);
        var radlat = lat / 180.0 * Math.PI;
        var magic = Math.sin(radlat);
        magic = 1 - 0.00669342162296594323 * magic * magic;
        var sqrtmagic = Math.sqrt(magic);
        dlat = (dlat * 180.0) / ((6378245.0 * (1 - 0.00669342162296594323)) / (magic * sqrtmagic) * Math.PI);
        dlng = (dlng * 180.0) / (6378245.0 / sqrtmagic * Math.cos(radlat) * Math.PI);
        var mglat = lat + dlat;
        var mglng = lng + dlng;
        return {lng: mglng, lat: mglat};
    }
    
    // GCJ02 to BD09 conversion (for Baidu)
    function gcj02ToBd09(lng, lat) {
        var z = Math.sqrt(lng * lng + lat * lat) + 0.00002 * Math.sin(lat * Math.PI * 3000.0 / 180.0);
        var theta = Math.atan2(lat, lng) + 0.000003 * Math.cos(lng * Math.PI * 3000.0 / 180.0);
        var bd_lng = z * Math.cos(theta) + 0.0065;
        var bd_lat = z * Math.sin(theta) + 0.006;
        return {lng: bd_lng, lat: bd_lat};
    }
    
    // WGS84 to BD09 (direct conversion for Baidu)
    function wgs84ToBd09(lng, lat) {
        var gcj = wgs84ToGcj02(lng, lat);
        return gcj02ToBd09(gcj.lng, gcj.lat);
    }
    
    // Helper functions for coordinate transformation
    function transformLat(lng, lat) {
        var ret = -100.0 + 2.0 * lng + 3.0 * lat + 0.2 * lat * lat + 0.1 * lng * lat + 0.2 * Math.sqrt(Math.abs(lng));
        ret += (20.0 * Math.sin(6.0 * lng * Math.PI) + 20.0 * Math.sin(2.0 * lng * Math.PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(lat * Math.PI) + 40.0 * Math.sin(lat / 3.0 * Math.PI)) * 2.0 / 3.0;
        ret += (160.0 * Math.sin(lat / 12.0 * Math.PI) + 320 * Math.sin(lat * Math.PI / 30.0)) * 2.0 / 3.0;
        return ret;
    }
    
    function transformLng(lng, lat) {
        var ret = 300.0 + lng + 2.0 * lat + 0.1 * lng * lng + 0.1 * lng * lat + 0.1 * Math.sqrt(Math.abs(lng));
        ret += (20.0 * Math.sin(6.0 * lng * Math.PI) + 20.0 * Math.sin(2.0 * lng * Math.PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(lng * Math.PI) + 40.0 * Math.sin(lng / 3.0 * Math.PI)) * 2.0 / 3.0;
        ret += (150.0 * Math.sin(lng / 12.0 * Math.PI) + 300.0 * Math.sin(lng / 30.0 * Math.PI)) * 2.0 / 3.0;
        return ret;
    }
    
    // Convert coordinates based on map provider
    window.convertCoordinatesForChineseMap = function(lng, lat, provider) {
        provider = provider || window.geodir_chinese_map_provider;
        
        console.log('🗺️ Converting coordinates:', lng, lat, 'for provider:', provider);
        
        var converted;
        if (provider === 'baidu') {
            converted = wgs84ToBd09(lng, lat);
        } else if (provider === 'amap' || provider === 'tencent') {
            converted = wgs84ToGcj02(lng, lat);
        } else {
            // TianDiTu uses WGS84, no conversion needed
            converted = {lng: lng, lat: lat};
        }
        
        console.log('🔄 Converted to:', converted.lng, converted.lat);
        return converted;
    };
    
    console.log('✅ Coordinate conversion functions loaded');
    </script>
    <?php
}

/**
 * AJAX handler for marker requests
 */
function geodir_chinese_maps_ajax_markers() {
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'gd_place';
    $map_id = isset($_POST['map_id']) ? sanitize_text_field($_POST['map_id']) : '';
    
    // Get markers using GeoDirectory's methods
    $markers = array();
    
    if (function_exists('geodir_get_map_markers')) {
        $markers = geodir_get_map_markers();
    } elseif (function_exists('geodir_get_markers')) {
        $markers = geodir_get_markers();
    }
    
    // If no markers found, try to get from posts
    if (empty($markers)) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => 50,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'latitude',
                    'value' => '',
                    'compare' => '!='
                ),
                array(
                    'key' => 'longitude', 
                    'value' => '',
                    'compare' => '!='
                )
            )
        ));
        
        foreach ($posts as $post) {
            $lat = get_post_meta($post->ID, 'latitude', true);
            $lng = get_post_meta($post->ID, 'longitude', true);
            
            if ($lat && $lng) {
                $markers[] = array(
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'title' => $post->post_title,
                    'content' => $post->post_excerpt ?: $post->post_title,
                    'post_id' => $post->ID
                );
            }
        }
    }
    
    wp_send_json_success(array(
        'markers' => $markers,
        'count' => count($markers),
        'map_id' => $map_id
    ));
}

/**
 * Enhanced marker loading for Chinese maps
 */
function geodir_chinese_maps_enhanced_marker_fix() {
    global $post;
    $active_map = geodir_get_option('map_provider', 'osm');
    
    if (!in_array($active_map, array('amap', 'baidu', 'tencent', 'tianditu'))) {
        return;
    }
    
    // Skip single place pages to avoid duplicate markers
    if (is_singular() && isset($post) && geodir_is_gd_post_type($post->post_type)) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('🗺️ Chinese Maps Enhanced Marker Fix v1.3 Loading...');
        
        // Enhanced single page detection including sidebar exclusion
        var isSinglePlace = $('body').hasClass('single-gd_place') || 
                           $('body').hasClass('single-gd_event') || 
                           $('.geodir-single-post').length > 0 ||
                           $('article.gd_place').length > 0 ||
                           $('article.gd_event').length > 0 ||
                           $('article.type-gd_place').length > 0 ||
                           $('article.type-gd_event').length > 0;
        
        if (isSinglePlace) {
            console.log('ℹ️ Single place/event page detected, skipping enhanced marker fix to avoid duplicates');
            return;
        }
        
        // Additional check: if we find sidebar listings, exclude them from marker extraction
        var hasSidebar = $('.listing-right-sidebar').length > 0;
        if (hasSidebar) {
            console.log('📋 Sidebar detected, will exclude sidebar elements from marker extraction');
        }
        
        var markerFixAttempts = 0;
        var maxAttempts = 15;
        var markerCheckInterval;
        
        function startMarkerCheck() {
            markerCheckInterval = setInterval(function() {
                checkAndFixMarkers();
            }, 2000);
            
            // Stop after max attempts
            setTimeout(function() {
                if (markerCheckInterval) {
                    clearInterval(markerCheckInterval);
                    console.log('⏰ Marker check stopped after max attempts');
                }
            }, maxAttempts * 2000);
        }
        
        function checkAndFixMarkers() {
            markerFixAttempts++;
            console.log('🔍 Marker check attempt:', markerFixAttempts);
            
            var mapCanvases = $('.geodir-map-canvas');
            console.log('📍 Found', mapCanvases.length, 'map canvas(es)');
            
            if (mapCanvases.length === 0) {
                return;
            }
            
            var hasVisibleMarkers = false;
            
            mapCanvases.each(function() {
                var mapContainer = $(this);
                var mapId = mapContainer.attr('id');
                
                // Check for visible markers in this map
                var visibleMarkers = mapContainer.find('.leaflet-marker-icon, .marker-cluster').length;
                console.log('📊 Map', mapId, 'has', visibleMarkers, 'visible markers');
                
                if (visibleMarkers > 0) {
                    hasVisibleMarkers = true;
                } else {
                    console.log('⚠️ No markers visible in map:', mapId);
                    forceMarkerLoad(mapId, mapContainer);
                }
            });
            
            // If we found visible markers, we can stop checking
            if (hasVisibleMarkers) {
                console.log('✅ Markers found, stopping check');
                clearInterval(markerCheckInterval);
            }
        }
        
        function forceMarkerLoad(mapId, mapContainer) {
            console.log('🚀 Force loading markers for map:', mapId);
            
            // Method 1: Load via custom AJAX
            loadMarkersViaAjax(mapId);
            
            // Method 2: Force from existing map instance
            loadFromMapInstance(mapId);
            
            // Method 3: Load from global data
            loadFromGlobalData(mapId);
            
            // Method 4: Trigger map events
            triggerMapEvents(mapId);
        }
        
        function loadMarkersViaAjax(mapId) {
            console.log('📡 Loading markers via AJAX for:', mapId);
            
            if (!window.geodir_chinese_ajax_url) {
                console.log('❌ No AJAX URL available');
                return;
            }
            
            var ajaxData = {
                action: 'geodir_chinese_markers',
                map_id: mapId,
                post_type: getPostTypeFromMap(mapId)
            };
            
            $.post(window.geodir_chinese_ajax_url, ajaxData)
                .done(function(response) {
                    console.log('📡 AJAX response:', response);
                    if (response.success && response.data.markers) {
                        renderMarkersToMap(response.data.markers, mapId);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('❌ AJAX failed:', error);
                });
        }
        
        function loadFromMapInstance(mapId) {
            if (typeof window.gdMaps === 'undefined' || !window.gdMaps[mapId]) {
                return;
            }
            
            var mapInstance = window.gdMaps[mapId];
            console.log('📊 Checking map instance:', mapInstance);
            
            if (mapInstance.markers && mapInstance.markers.length > 0) {
                console.log('📍 Found', mapInstance.markers.length, 'markers in instance');
                renderMarkersToMap(mapInstance.markers, mapId);
            }
        }
        
        function loadFromGlobalData(mapId) {
            var globalMarkers = null;
            
            // Check various global sources
            if (typeof window.gdArchiveMapMarkers !== 'undefined') {
                globalMarkers = window.gdArchiveMapMarkers;
                console.log('📍 Using archive markers:', globalMarkers.length);
            } else if (typeof window.gdMapMarkers !== 'undefined') {
                globalMarkers = window.gdMapMarkers;
                console.log('📍 Using global markers:', globalMarkers.length);
            } else if (typeof window.geodir_map_markers !== 'undefined') {
                globalMarkers = window.geodir_map_markers;
                console.log('📍 Using geodir markers:', globalMarkers.length);
            }
            
            if (globalMarkers && globalMarkers.length > 0) {
                renderMarkersToMap(globalMarkers, mapId);
            }
        }
        
        function renderMarkersToMap(markers, mapId) {
            console.log('🖼️ Rendering', markers.length, 'markers to map:', mapId);
            
            var mapContainer = $('#' + mapId);
            if (!mapContainer.length) {
                console.log('❌ Map container not found:', mapId);
                return;
            }
            
            // Try to get the map instance
            var mapInstance = null;
            
            // Method 1: Check for global map variables
            if (window[mapId]) {
                mapInstance = window[mapId];
                console.log('✅ Found map instance via global variable');
            }
            
            // Method 2: Check for Leaflet map
            if (!mapInstance && window.L && mapContainer[0]._leaflet_id) {
                mapInstance = window.L.map(mapContainer[0]._leaflet_id);
                console.log('✅ Found map instance via Leaflet ID');
            }
            
            // Method 3: Try to find in global scope
            if (!mapInstance) {
                var possibleNames = [mapId, mapId + '_map', 'map_' + mapId, 'geodir_map', 'gd_map'];
                for (var i = 0; i < possibleNames.length; i++) {
                    if (window[possibleNames[i]] && typeof window[possibleNames[i]].addLayer === 'function') {
                        mapInstance = window[possibleNames[i]];
                        console.log('✅ Found map instance via', possibleNames[i]);
                        break;
                    }
                }
            }
            
            if (!mapInstance) {
                console.log('❌ No map instance found, trying direct marker injection');
                injectMarkersDirectly(markers, mapId);
                return;
            }
            
            // Clear existing markers (if possible)
            try {
                if (mapInstance.eachLayer) {
                    mapInstance.eachLayer(function(layer) {
                        if (layer instanceof L.Marker || (layer.options && layer.options.isClusterGroup)) {
                            mapInstance.removeLayer(layer);
                        }
                    });
                }
            } catch (e) {
                console.log('⚠️ Could not clear existing markers:', e.message);
            }
            
            // Create marker cluster group if available
            var markerGroup;
            if (window.L && window.L.markerClusterGroup) {
                markerGroup = window.L.markerClusterGroup({
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true
                });
                console.log('✅ Created marker cluster group');
            } else {
                markerGroup = window.L ? window.L.layerGroup() : null;
                console.log('⚠️ Using basic layer group (no clustering)');
            }
            
            if (!markerGroup) {
                console.log('❌ Could not create marker group');
                return;
            }
            
            // Add markers with coordinate conversion
            var bounds = [];
            markers.forEach(function(marker, index) {
                try {
                    // Convert coordinates for Chinese maps
                    var originalLat = marker.lat;
                    var originalLng = marker.lng;
                    var convertedCoords = window.convertCoordinatesForChineseMap(originalLng, originalLat);
                    
                    console.log('🔄 Marker', index + 1, 'conversion:', originalLng, originalLat, '→', convertedCoords.lng, convertedCoords.lat);
                    
                    var leafletMarker = window.L.marker([convertedCoords.lat, convertedCoords.lng]);
                    
                    if (marker.title || marker.content) {
                        var popupContent = marker.content || marker.title || 'Marker ' + (index + 1);
                        leafletMarker.bindPopup(popupContent);
                    }
                    
                    markerGroup.addLayer(leafletMarker);
                    bounds.push([convertedCoords.lat, convertedCoords.lng]);
                    
                    console.log('📍 Added marker', index + 1, 'at converted coords', convertedCoords.lat, convertedCoords.lng);
                } catch (e) {
                    console.error('❌ Error adding marker', index, ':', e.message);
                }
            });
            
            // Add marker group to map
            mapInstance.addLayer(markerGroup);
            
            // Fit bounds if we have markers
            if (bounds.length > 0) {
                try {
                    if (bounds.length === 1) {
                        mapInstance.setView(bounds[0], 15);
                    } else {
                        mapInstance.fitBounds(bounds, { padding: [20, 20] });
                    }
                    console.log('✅ Map bounds fitted to', bounds.length, 'markers');
                } catch (e) {
                    console.log('⚠️ Could not fit bounds:', e.message);
                }
            }
            
            console.log('✅ Successfully rendered', markers.length, 'markers to', mapId);
        }
        
        function injectMarkersDirectly(markers, mapId) {
            console.log('🔧 Attempting direct marker injection for', mapId);
            
            // Try to inject markers using GeoDirectory's own functions
            if (window.geodir_params && window.geodir_params.map_type !== 'openstreetmap') {
                console.log('🗺️ Chinese map detected, using enhanced injection');
                
                // Store markers globally for GeoDirectory to pick up
                window.gd_markers = window.gd_markers || [];
                window.gd_markers = window.gd_markers.concat(markers);
                
                // Try to trigger map redraw
                setTimeout(function() {
                    if (window.init_map_script && typeof window.init_map_script === 'function') {
                        console.log('🔄 Triggering map script reinit');
                        window.init_map_script();
                    }
                    
                    if (window.geodir_setup_map && typeof window.geodir_setup_map === 'function') {
                        console.log('🔄 Triggering GeoDirectory map setup');
                        window.geodir_setup_map();
                    }
                }, 1000);
            }
        }
        
        function triggerMapEvents(mapId) {
            console.log('🔄 Triggering map events for:', mapId);
            
            // Trigger various map events
            $(document).trigger('geodir_map_init', [mapId]);
            $(document).trigger('geodir_setup_map', [mapId]);
            $(document).trigger('geodir_map_loaded', [mapId]);
            $(document).trigger('geodir_markers_loaded', [mapId]);
            
            // Call initialization functions
            setTimeout(function() {
                if (typeof window.geodirLoadMap === 'function') {
                    window.geodirLoadMap();
                }
                if (typeof window.geodirMapInit === 'function') {
                    window.geodirMapInit();
                }
                if (typeof window.geodirMarkerCluster === 'function') {
                    window.geodirMarkerCluster();
                }
            }, 500);
        }
        
        function getPostTypeFromMap(mapId) {
            // Try to determine post type from map context
            var postType = 'gd_place'; // default
            
            // Check URL for event indicators
            var currentUrl = window.location.href.toLowerCase();
            if (currentUrl.indexOf('event') !== -1 || currentUrl.indexOf('gd_event') !== -1) {
                postType = 'gd_event';
            }
            
            // Check body classes
            if ($('body').hasClass('post-type-archive-gd_event') || 
                $('body').hasClass('events-archive') ||
                $('body').hasClass('gd-events')) {
                postType = 'gd_event';
            }
            
            // Check map ID for hints
            if (mapId && mapId.toLowerCase().indexOf('event') !== -1) {
                postType = 'gd_event';
            } else if (mapId && mapId.toLowerCase().indexOf('place') !== -1) {
                postType = 'gd_place';
            }
            
            console.log('🏷️ Detected post type:', postType, 'for map:', mapId);
            return postType;
        }
        
        // Start the enhanced marker check
        setTimeout(startMarkerCheck, 3000);
        
        // NEW: Add dedicated archive/listing page marker loader
        function loadArchivePageMarkers() {
            console.log('📋 Starting archive page marker loader...');
            
            // Check if we're on an archive/listing page
            var isArchivePage = $('body').hasClass('archive') || 
                               $('body').hasClass('search') ||
                               $('body').hasClass('post-type-archive-gd_place') ||
                               $('body').hasClass('post-type-archive-gd_event') ||
                               $('.geodir-listings').length > 0 ||
                               $('.geodir-category-list').length > 0 ||
                               $('.gd-listing-content').length > 0 ||
                               $('.geodir-events').length > 0 ||
                               $('.gd-event-list').length > 0;
            
            if (!isArchivePage) {
                console.log('ℹ️ Not an archive page, skipping archive marker loader');
                return;
            }
            
            console.log('📋 Archive page detected, scanning for listings...');
            
            // Find all visible listings with coordinates (including events) - EXCLUDE SIDEBAR
            var listings = $('.geodir-grid-item, .gd-listing-content, .gd-place-list-item, .gd-event-list-item, .geodir-event, .gd-event, [data-latitude][data-longitude]')
                          .not('.listing-right-sidebar *')  // Exclude anything inside sidebar
                          .not('.listing-right-sidebar');   // Exclude sidebar itself
            
            console.log('📋 Found', listings.length, 'potential listings (places & events) - excluding sidebar');
            
            var archiveMarkers = [];
            
            listings.each(function(index) {
                var listing = $(this);
                var lat = null;
                var lng = null;
                var title = '';
                
                // Try to extract coordinates from various sources
                lat = listing.attr('data-latitude') || listing.data('latitude');
                lng = listing.attr('data-longitude') || listing.data('longitude');
                
                // Try from nested elements
                if (!lat || !lng) {
                    var coordElement = listing.find('[data-latitude], [data-longitude]').first();
                    if (coordElement.length) {
                        lat = coordElement.attr('data-latitude') || coordElement.data('latitude');
                        lng = coordElement.attr('data-longitude') || coordElement.data('longitude');
                    }
                }
                
                // Try from hidden inputs (some themes use this)
                if (!lat || !lng) {
                    var latInput = listing.find('input[name*="latitude"], input[id*="latitude"]').first();
                    var lngInput = listing.find('input[name*="longitude"], input[id*="longitude"]').first();
                    if (latInput.length && lngInput.length) {
                        lat = latInput.val();
                        lng = lngInput.val();
                    }
                }
                
                // Get title from various sources
                title = listing.find('.geodir-entry-title, .gd-place-title, h3, h2, .listing-title').first().text() ||
                       listing.attr('data-title') ||
                       'Listing ' + (index + 1);
                
                if (lat && lng && !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng))) {
                    archiveMarkers.push({
                        lat: parseFloat(lat),
                        lng: parseFloat(lng),
                        title: title.trim(),
                        content: title.trim(),
                        listing_index: index
                    });
                    
                    console.log('📍 Found listing marker:', {
                        index: index,
                        lat: lat,
                        lng: lng,
                        title: title.trim()
                    });
                }
            });
            
            console.log('📋 Extracted', archiveMarkers.length, 'markers from listings');
            
            if (archiveMarkers.length > 0) {
                // Store for global access
                window.gdArchiveMarkers = archiveMarkers;
                
                // Find all maps and add markers
                $('.geodir-map-canvas').each(function() {
                    var mapId = $(this).attr('id');
                    if (mapId) {
                        console.log('🗺️ Adding archive markers to map:', mapId);
                        renderMarkersToMap(archiveMarkers, mapId);
                    }
                });
                
                // Also try AJAX method for additional markers
                loadArchiveMarkersViaAjax(archiveMarkers);
            } else {
                console.log('⚠️ No markers found in listings, trying AJAX fallback');
                loadArchiveMarkersViaAjax([]);
            }
        }
        
        function loadArchiveMarkersViaAjax(existingMarkers) {
            console.log('📡 Loading additional archive markers via AJAX...');
            
            if (!window.geodir_chinese_ajax_url) {
                console.log('❌ No AJAX URL available');
                return;
            }
            
            // Get current page context
            var currentUrl = window.location.href;
            var postType = 'gd_place'; // default
            
            // Try to determine post type from URL or body classes
            if (currentUrl.indexOf('gd_event') !== -1 || $('body').hasClass('post-type-archive-gd_event')) {
                postType = 'gd_event';
            }
            
            var ajaxData = {
                action: 'geodir_chinese_markers',
                post_type: postType,
                context: 'archive',
                existing_count: existingMarkers.length
            };
            
            $.post(window.geodir_chinese_ajax_url, ajaxData)
                .done(function(response) {
                    console.log('📡 Archive AJAX response:', response);
                    if (response.success && response.data.markers && response.data.markers.length > 0) {
                        var allMarkers = existingMarkers.concat(response.data.markers);
                        console.log('📍 Total markers after AJAX:', allMarkers.length);
                        
                        // Update global storage
                        window.gdArchiveMarkers = allMarkers;
                        
                        // Re-render to all maps
                        $('.geodir-map-canvas').each(function() {
                            var mapId = $(this).attr('id');
                            if (mapId) {
                                renderMarkersToMap(allMarkers, mapId);
                            }
                        });
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('❌ Archive AJAX failed:', error);
                });
        }
        
        // Start archive marker loader after a delay
        setTimeout(loadArchivePageMarkers, 5000);
        
        console.log('🚀 Chinese Maps Enhanced Marker Fix v1.2 initialized');
    });
    </script>
    <?php
}

/**
 * Enhanced archive page marker support for Chinese maps
 */
function geodir_chinese_maps_archive_marker_support() {
    global $post;
    $active_map = geodir_get_option('map_provider', 'osm');
    
    if (!in_array($active_map, array('amap', 'baidu', 'tencent', 'tianditu'))) {
        return;
    }
    
    // Skip single place pages to avoid duplicate markers
    if (is_singular() && isset($post) && geodir_is_gd_post_type($post->post_type)) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    // Archive page specific marker fix for Chinese maps
    jQuery(document).ready(function($) {
        console.log('📋 Chinese Maps Archive Page Enhancement Loading...');
        
        // Enhanced single page detection including sidebar exclusion
        var isSinglePlace = $('body').hasClass('single-gd_place') || 
                           $('body').hasClass('single-gd_event') || 
                           $('.geodir-single-post').length > 0 ||
                           $('article.gd_place').length > 0 ||
                           $('article.gd_event').length > 0 ||
                           $('article.type-gd_place').length > 0 ||
                           $('article.type-gd_event').length > 0;
        
        if (isSinglePlace) {
            console.log('ℹ️ Single place/event page detected, skipping archive enhancement');
            return;
        }
        
        // Check if this is an archive page by looking for typical archive elements
        var isArchivePage = $('.geodir-archive-map-wrap, .geodir-map-wrap, .geodir-archive-item, .geodir-category-list-view, .geodir-events-archive, .gd-events-wrap').length > 0;
        
        if (!isArchivePage) {
            console.log('ℹ️ Not an archive page, skipping archive marker enhancement');
            return;
        }
        
        // Specific handling for archive maps
        var archiveMapInterval = setInterval(function() {
            var archiveMaps = $('.geodir-archive-map-wrap, .geodir-map-wrap').find('.geodir-map-canvas');
            
            if (archiveMaps.length > 0) {
                console.log('🗂️ Found', archiveMaps.length, 'archive map(s)');
                clearInterval(archiveMapInterval);
                
                archiveMaps.each(function() {
                    var mapContainer = $(this);
                    var mapId = mapContainer.attr('id');
                    
                    if (mapId) {
                        console.log('🎯 Processing archive map:', mapId);
                        
                        // Wait for map to initialize, then force marker load
                        setTimeout(function() {
                            forceArchiveMarkerLoad(mapId, mapContainer);
                        }, 2000);
                    }
                });
            }
        }, 1000);
        
        // Stop checking after 20 seconds
        setTimeout(function() {
            clearInterval(archiveMapInterval);
        }, 20000);
        
        function forceArchiveMarkerLoad(mapId, mapContainer) {
            console.log('📋 Force loading archive markers for:', mapId);
            
            // Check if map instance exists
            if (typeof window.gdMaps === 'undefined' || !window.gdMaps[mapId]) {
                console.log('⏳ Waiting for map instance...');
                setTimeout(function() {
                    forceArchiveMarkerLoad(mapId, mapContainer);
                }, 1000);
                return;
            }
            
            var mapInstance = window.gdMaps[mapId];
            
            // Method 1: Load from current page posts
            loadMarkersFromCurrentPage(mapId, mapInstance.map);
            
            // Method 2: Force AJAX reload specifically for archive
            loadArchiveMarkersViaAjax(mapId);
            
            // Method 3: Check for global archive marker data
            checkArchiveGlobalData(mapId, mapInstance.map);
        }
        
        function loadMarkersFromCurrentPage(mapId, leafletMap) {
            console.log('📄 Loading markers from current page posts...');
            
            var pageMarkers = [];
            
            // Look for listings on the current page with coordinates (places and events) - EXCLUDE SIDEBAR
            $('.geodir-post, .geodir-listing-post, .geodir-event, .gd-event, [data-post-id]')
                .not('.listing-right-sidebar *')  // Exclude anything inside sidebar
                .not('.listing-right-sidebar')    // Exclude sidebar itself
                .each(function() {
                var $listing = $(this);
                var postId = $listing.data('post-id') || $listing.attr('data-post-id');
                
                // Try to find coordinates in various ways
                var lat = null, lng = null;
                
                // Check data attributes
                lat = $listing.data('latitude') || $listing.data('lat');
                lng = $listing.data('longitude') || $listing.data('lng');
                
                // Check for hidden fields
                if (!lat || !lng) {
                    var latField = $listing.find('input[name*="latitude"], [data-latitude]').first();
                    var lngField = $listing.find('input[name*="longitude"], [data-longitude]').first();
                    
                    if (latField.length) lat = latField.val() || latField.data('latitude');
                    if (lngField.length) lng = lngField.val() || lngField.data('longitude');
                }
                
                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    var title = $listing.find('.geodir-entry-title, .entry-title, h2, h3').first().text().trim();
                    if (!title) title = 'Location ' + postId;
                    
                    pageMarkers.push({
                        lat: parseFloat(lat),
                        lng: parseFloat(lng),
                        title: title,
                        post_id: postId
                    });
                    
                    console.log('📍 Found marker from page:', title, lat, lng);
                }
            });
            
            if (pageMarkers.length > 0) {
                console.log('🎨 Rendering', pageMarkers.length, 'markers from current page');
                renderMarkersDirectly(pageMarkers, leafletMap);
            } else {
                console.log('⚠️ No markers found on current page');
            }
        }
        
        function loadArchiveMarkersViaAjax(mapId) {
            console.log('📡 Loading archive markers via AJAX...');
            
            if (!window.geodir_chinese_ajax_url) {
                console.log('❌ No AJAX URL for archive markers');
                return;
            }
            
            // Get current page context
            var postType = getPostTypeFromPage();
            var currentCategory = getCurrentCategory();
            
            var ajaxData = {
                action: 'geodir_chinese_markers',
                map_id: mapId,
                post_type: postType,
                category: currentCategory,
                is_archive: true
            };
            
            $.post(window.geodir_chinese_ajax_url, ajaxData)
                .done(function(response) {
                    console.log('📡 Archive AJAX response:', response);
                    if (response.success && response.data.markers && response.data.markers.length > 0) {
                        if (window.gdMaps[mapId] && window.gdMaps[mapId].map) {
                            renderMarkersDirectly(response.data.markers, window.gdMaps[mapId].map);
                        }
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('❌ Archive AJAX failed:', error);
                });
        }
        
        function checkArchiveGlobalData(mapId, leafletMap) {
            console.log('🌐 Checking archive global data...');
            
            var globalData = null;
            
            // Check various global sources specific to archive maps
            if (typeof window.gd_map_canvas_archive !== 'undefined') {
                globalData = window.gd_map_canvas_archive;
            } else if (typeof window.gdArchiveMapMarkers !== 'undefined') {
                globalData = window.gdArchiveMapMarkers;
            } else if (typeof window.geodir_map_all_posts !== 'undefined') {
                globalData = window.geodir_map_all_posts;
            }
            
            if (globalData && globalData.length > 0) {
                console.log('🌐 Using global archive data:', globalData.length, 'markers');
                renderMarkersDirectly(globalData, leafletMap);
            }
        }
        
        function renderMarkersDirectly(markers, leafletMap) {
            if (!markers || markers.length === 0 || !leafletMap) {
                return;
            }
            
            console.log('🎨 Direct rendering', markers.length, 'markers to archive map');
            
            // Create marker group with clustering
            var markerGroup;
            if (typeof L.markerClusterGroup === 'function') {
                markerGroup = L.markerClusterGroup({
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    maxClusterRadius: 80
                });
            } else {
                markerGroup = L.featureGroup();
            }
            
            var successCount = 0;
            
            markers.forEach(function(markerData, index) {
                try {
                    var lat = parseFloat(markerData.lat || markerData.lt || markerData.latitude || 0);
                    var lng = parseFloat(markerData.lng || markerData.ln || markerData.longitude || 0);
                    
                    if (lat && lng && lat !== 0 && lng !== 0) {
                        // Convert coordinates for Chinese maps
                        var convertedCoords = window.convertCoordinatesForChineseMap(lng, lat);
                        
                        console.log('🔄 Archive marker', index + 1, 'conversion:', lng, lat, '→', convertedCoords.lng, convertedCoords.lat);
                        
                        // Create marker with converted coordinates
                        var marker = L.marker([convertedCoords.lat, convertedCoords.lng]);
                        
                        // Create popup content
                        var popupContent = markerData.title || markerData.content || 'Location';
                        if (markerData.post_id) {
                            popupContent = '<strong>' + popupContent + '</strong>';
                        }
                        
                        marker.bindPopup(popupContent);
                        markerGroup.addLayer(marker);
                        successCount++;
                        
                        console.log('✅ Archive marker added:', successCount, 'at converted coords', convertedCoords.lat, convertedCoords.lng);
                    }
                } catch (e) {
                    console.error('❌ Error adding archive marker:', e);
                }
            });
            
            if (successCount > 0) {
                // Clear any existing markers first
                leafletMap.eachLayer(function(layer) {
                    if (layer instanceof L.Marker || layer instanceof L.MarkerClusterGroup) {
                        leafletMap.removeLayer(layer);
                    }
                });
                
                // Add new marker group
                markerGroup.addTo(leafletMap);
                
                // Fit bounds to markers
                try {
                    if (markerGroup.getBounds && markerGroup.getBounds().isValid()) {
                        leafletMap.fitBounds(markerGroup.getBounds(), {padding: [20, 20]});
                    }
                } catch (e) {
                    console.log('ℹ️ Could not fit bounds for archive markers');
                }
                
                console.log('🎉 Successfully added', successCount, 'markers to archive map');
            }
        }
        
        function getPostTypeFromPage() {
            // Try to determine post type from page context
            var currentUrl = window.location.href.toLowerCase();
            
            // Check URL patterns
            if (currentUrl.indexOf('/events') !== -1 || 
                currentUrl.indexOf('/event') !== -1 || 
                currentUrl.indexOf('gd_event') !== -1) {
                return 'gd_event';
            } else if (currentUrl.indexOf('/places') !== -1 || 
                      currentUrl.indexOf('/place') !== -1) {
                return 'gd_place';
            }
            
            // Check body classes
            if ($('body').hasClass('post-type-archive-gd_event') ||
                $('body').hasClass('events-archive') ||
                $('body').hasClass('gd-events')) {
                return 'gd_event';
            } else if ($('body').hasClass('post-type-archive-gd_place') ||
                      $('body').hasClass('places-archive') ||
                      $('body').hasClass('gd-places')) {
                return 'gd_place';
            }
            
            // Check for event-specific elements on the page
            if ($('.geodir-event, .gd-event, .gd-event-list').length > 0) {
                return 'gd_event';
            }
            
            return 'gd_place'; // default
        }
        
        function getCurrentCategory() {
            // Try to get current category from URL or page context
            var urlPath = window.location.pathname;
            var matches = urlPath.match(/\/category\/([^\/]+)/);
            return matches ? matches[1] : '';
        }
        
        console.log('📋 Chinese Maps Archive Enhancement initialized');
    });
    </script>
    <?php
}
add_action('wp_footer', 'geodir_chinese_maps_archive_marker_support', 5);

/**
 * Enhanced Chinese map marker injection script using REST API approach
 */
function geodir_chinese_maps_enhanced_marker_script() {
    global $post;
    $active_map = geodir_get_option('map_provider', 'osm');
    
    if (!in_array($active_map, array('amap', 'baidu', 'tencent', 'tianditu'))) {
        return;
    }
    
    // Skip single place pages to avoid duplicate markers
    if (is_singular() && isset($post) && geodir_is_gd_post_type($post->post_type)) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    // Enhanced single page detection including sidebar exclusion
    var isSinglePlace = document.body.classList.contains('single-gd_place') || 
                       document.body.classList.contains('single-gd_event') || 
                       document.querySelector('.geodir-single-post') ||
                       document.querySelector('article.gd_place') ||
                       document.querySelector('article.gd_event') ||
                       document.querySelector('article.type-gd_place') ||
                       document.querySelector('article.type-gd_event');
    
    if (isSinglePlace) {
        console.log('ℹ️ Single place/event page detected, skipping REST API marker enhancement');
    } else {
        console.log('🗺️ Archive/listing page detected, loading REST API marker enhancement');
    
    /**
     * Enhanced Chinese map marker injection using HTML parsing and REST API
     */
    function enhanceChineseMapMarkersViaAPI() {
        console.log('🇨🇳 Enhanced Chinese Maps: Starting marker extraction via API...');
        
        // Step 1: Extract post IDs from the HTML listing elements - EXCLUDE SIDEBAR
        var postIds = [];
        var postTypes = [];
        
        // Look for geodir-post elements in the listing container (places and events) - EXCLUDE SIDEBAR
        var listingElements = document.querySelectorAll('.geodir-loop-container .geodir-post:not(.listing-right-sidebar *), .geodir-loop-container .geodir-event:not(.listing-right-sidebar *)');
        if (listingElements.length === 0) {
            // Fallback: try other possible selectors for both places and events - EXCLUDE SIDEBAR
            listingElements = document.querySelectorAll('.geodir-post:not(.listing-right-sidebar *), .geodir-event:not(.listing-right-sidebar *), .gd-event:not(.listing-right-sidebar *), [data-post-id]:not(.listing-right-sidebar *), .gd-place-list-item:not(.listing-right-sidebar *), .gd-event-list-item:not(.listing-right-sidebar *), .gd-place-item:not(.listing-right-sidebar *), .gd-event-item:not(.listing-right-sidebar *)');
        }
        
        console.log('📋 Found', listingElements.length, 'listing elements (excluding sidebar)');
        
        if (listingElements.length === 0) {
            console.log('⚠️ No listing elements found, trying alternative approach...');
            // Try to find any elements with geodir in the class name - EXCLUDE SIDEBAR
            var allElements = document.querySelectorAll('[class*="geodir"]:not(.listing-right-sidebar *), [class*="gd-"]:not(.listing-right-sidebar *), [data-lat]:not(.listing-right-sidebar *), [data-lng]:not(.listing-right-sidebar *)');
            console.log('📋 Found', allElements.length, 'potential geodir elements (excluding sidebar)');
            listingElements = allElements;
        }
        
        listingElements.forEach(function(element, index) {
            // Skip if element is inside sidebar
            if (element.closest('.listing-right-sidebar')) {
                console.log('🚫 Skipping element', index + 1, '- inside sidebar:', element.className);
                return;
            }
            
            console.log('🔍 Processing element', index + 1, ':', element.className);
            
            // Extract post ID from data attribute
            var postId = element.getAttribute('data-post-id') || element.getAttribute('data-post_id');
            
            // If not found, try to extract from class names
            if (!postId) {
                var classList = element.className;
                var postMatch = classList.match(/post-(\d+)/);
                if (postMatch) {
                    postId = postMatch[1];
                    console.log('🏷️ Found post ID from class:', postId);
                }
            }
            
            // Try to get from ID attribute
            if (!postId && element.id) {
                var idMatch = element.id.match(/post-(\d+)/);
                if (idMatch) {
                    postId = idMatch[1];
                    console.log('🏷️ Found post ID from element ID:', postId);
                }
            }
            
            // Extract post type from class names and element context
            var postType = 'gd_place'; // default
            if (element.classList.contains('gd_event') || 
                element.classList.contains('type-gd_event') ||
                element.classList.contains('geodir-event') ||
                element.classList.contains('gd-event')) {
                postType = 'gd_event';
            } else if (element.classList.contains('gd_place') ||
                      element.classList.contains('type-gd_place') ||
                      element.classList.contains('geodir-place') ||
                      element.classList.contains('gd-place')) {
                postType = 'gd_place';
            } else if (element.classList.contains('gd_gd_properties') || 
                      element.classList.contains('type-gd_gd_properties')) {
                postType = 'gd_gd_properties';
            }
            
            // If still default, try to detect from page context
            if (postType === 'gd_place') {
                var currentUrl = window.location.href.toLowerCase();
                if (currentUrl.indexOf('event') !== -1 || 
                    document.body.classList.contains('post-type-archive-gd_event')) {
                    postType = 'gd_event';
                }
            }
            
            // Try to extract coordinates directly from element
            var lat = element.getAttribute('data-lat') || element.getAttribute('data-latitude');
            var lng = element.getAttribute('data-lng') || element.getAttribute('data-longitude');
            
            if (lat && lng) {
                console.log('📍 Found direct coordinates:', lat, lng, 'for element', index + 1);
                // Add marker directly without API call
                var marker = {
                    id: postId || ('direct_' + index),
                    post_id: postId || ('direct_' + index),
                    lat: parseFloat(lat),
                    lng: parseFloat(lng),
                    title: element.querySelector('.geodir-entry-title, .entry-title, h2, h3, h4')?.textContent?.trim() || 'Location ' + (index + 1),
                    post_type: postType
                };
                
                // Store in global for immediate use
                if (!window.gdDirectMarkers) window.gdDirectMarkers = [];
                window.gdDirectMarkers.push(marker);
            }
            
            if (postId) {
                postIds.push(postId);
                postTypes.push(postType);
                console.log('🏷️ Found post:', postType, 'ID:', postId, 'from element:', element.className);
            }
        });
        
        // If we found direct coordinates, use them immediately
        if (window.gdDirectMarkers && window.gdDirectMarkers.length > 0) {
            console.log('🎯 Using', window.gdDirectMarkers.length, 'direct coordinate markers');
            injectMarkersIntoChineseMaps(window.gdDirectMarkers);
        }
        
        if (postIds.length === 0) {
            console.log('ℹ️ No post IDs found in HTML elements, trying alternative data sources...');
            
            // Try to get markers from global JavaScript variables
            var globalMarkers = [];
            if (typeof window.gd_map_canvas_archive_markers !== 'undefined') {
                globalMarkers = window.gd_map_canvas_archive_markers;
                console.log('🌐 Found global archive markers:', globalMarkers.length);
            } else if (typeof window.geodir_map_markers !== 'undefined') {
                globalMarkers = window.geodir_map_markers;
                console.log('🌐 Found global geodir markers:', globalMarkers.length);
            }
            
            if (globalMarkers.length > 0) {
                injectMarkersIntoChineseMaps(globalMarkers);
                return;
            }
            
            console.log('❌ No markers found through any method');
            return;
        }
        
        console.log('🎯 Extracted', postIds.length, 'post IDs:', postIds);
        
        // Step 2: Fetch location data for these posts via REST API
        var apiPromises = [];
        var groupedByType = {};
        
        // Group posts by type for efficient API calls
        postIds.forEach(function(postId, index) {
            var postType = postTypes[index];
            if (!groupedByType[postType]) {
                groupedByType[postType] = [];
            }
            groupedByType[postType].push(postId);
        });
        
        // Create API requests for each post type
        Object.keys(groupedByType).forEach(function(postType) {
            var ids = groupedByType[postType];
            console.log('🌐 Fetching', ids.length, 'posts of type', postType);
            
            // GeoDirectory REST API endpoint
            var apiUrl = window.location.origin + '/wp-json/geodir/v2/places';
            if (postType === 'gd_event') {
                apiUrl = window.location.origin + '/wp-json/geodir/v2/events';
            } else if (postType === 'gd_gd_properties') {
                apiUrl = window.location.origin + '/wp-json/geodir/v2/properties';
            }
            
            console.log('🌐 API URL for', postType, ':', apiUrl);
            
            // Add post IDs as include parameter
            apiUrl += '?include=' + ids.join(',') + '&per_page=100';
            
            var promise = fetch(apiUrl)
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('API request failed: ' + response.status);
                    }
                    return response.json();
                })
                .then(function(posts) {
                    console.log('✅ Received', posts.length, 'posts from API for type', postType);
                    return posts.map(function(post) {
                        return {
                            id: post.id,
                            post_id: post.id,
                            lat: parseFloat(post.latitude || post.lat || 0),
                            lng: parseFloat(post.longitude || post.lng || 0),
                            title: post.title ? post.title.rendered : 'Post ' + post.id,
                            content: post.content ? post.content.rendered : '',
                            excerpt: post.excerpt ? post.excerpt.rendered : '',
                            post_type: postType,
                            url: post.link
                        };
                    });
                })
                .catch(function(error) {
                    console.error('❌ API error for', postType, ':', error);
                    return [];
                });
            
            apiPromises.push(promise);
        });
        
        // Step 3: Combine all results and add to map
        Promise.all(apiPromises)
            .then(function(results) {
                var allMarkers = [];
                results.forEach(function(markers) {
                    allMarkers = allMarkers.concat(markers);
                });
                
                // Filter out markers without valid coordinates
                var validMarkers = allMarkers.filter(function(marker) {
                    return marker.lat && marker.lng && marker.lat !== 0 && marker.lng !== 0;
                });
                
                console.log('🗺️ Total valid markers:', validMarkers.length, 'out of', allMarkers.length);
                
                if (validMarkers.length === 0) {
                    console.log('ℹ️ No valid coordinates found for markers');
                    return;
                }
                
                // Inject markers into map system
                injectMarkersIntoChineseMaps(validMarkers);
            })
            .catch(function(error) {
                console.error('❌ Failed to process markers:', error);
            });
    }
    
    /**
     * Inject markers into Chinese map systems
     */
    function injectMarkersIntoChineseMaps(markers) {
        console.log('� Injecting', markers.length, 'markers into Chinese maps');
        
        // Store markers in global variables
        window.gd_markers = markers;
        window.geodir_markers = markers;
        window.map_markers = markers;
        window.markers_data = markers;
        window.gdArchiveMarkers = markers;
        
        // Try to find and update existing maps
        setTimeout(function() {
            updateExistingChineseMaps(markers);
        }, 1000);
        
        // Trigger map refresh events
        setTimeout(function() {
            if (typeof window.geodir_refresh_map === 'function') {
                console.log('🔄 Calling geodir_refresh_map');
                window.geodir_refresh_map();
            }
            
            // Trigger custom events
            var event = new CustomEvent('geodir.markers.loaded', { detail: { markers: markers } });
            document.dispatchEvent(event);
            
            console.log('✅ Chinese map markers injection completed');
        }, 2000);
    }
    
    /**
     * Update existing Chinese map instances with new markers
     */
    function updateExistingChineseMaps(markers) {
        console.log('🔄 Updating existing Chinese map instances...');
        
        // Look for map canvas elements
        var mapCanvases = document.querySelectorAll('[id*="map_canvas"], .geodir-map-canvas, .leaflet-container');
        
        mapCanvases.forEach(function(canvas) {
            var mapId = canvas.id;
            console.log('🗺️ Processing map canvas:', mapId);
            
            // Try to find associated map instance
            var mapInstance = window['geodir_map_' + mapId] || window[mapId + '_map'] || window.map;
            
            if (mapInstance) {
                try {
                    updateMapWithMarkers(mapInstance, markers, mapId);
                } catch (e) {
                    console.error('❌ Error updating map', mapId, ':', e.message);
                }
            }
        });
    }
    
    /**
     * Update a specific map instance with markers
     */
    function updateMapWithMarkers(mapInstance, markers, mapId) {
        console.log('📍 Adding', markers.length, 'markers to map:', mapId);
        
        // For Leaflet maps (used by most Chinese providers in our setup)
        if (mapInstance.eachLayer && typeof mapInstance.eachLayer === 'function') {
            // Clear existing markers
            mapInstance.eachLayer(function(layer) {
                if (layer instanceof L.Marker) {
                    mapInstance.removeLayer(layer);
                }
            });
            
            // Add new markers with coordinate conversion
            var bounds = [];
            markers.forEach(function(marker) {
                try {
                    // Convert coordinates for Chinese maps
                    var convertedCoords = window.convertCoordinatesForChineseMap(marker.lng, marker.lat);
                    
                    console.log('🔄 REST API marker conversion:', marker.lng, marker.lat, '→', convertedCoords.lng, convertedCoords.lat);
                    
                    var leafletMarker = L.marker([convertedCoords.lat, convertedCoords.lng]);
                    if (marker.title) {
                        leafletMarker.bindPopup('<strong>' + marker.title + '</strong>');
                    }
                    leafletMarker.addTo(mapInstance);
                    bounds.push([convertedCoords.lat, convertedCoords.lng]);
                } catch (e) {
                    console.error('❌ Error adding marker:', e.message);
                }
            });
            
            // Fit map to markers
            if (bounds.length > 0) {
                try {
                    mapInstance.fitBounds(bounds, { padding: [10, 10] });
                } catch (e) {
                    console.log('ℹ️ Could not auto-fit map bounds');
                }
            }
            
            console.log('✅ Leaflet map updated with', markers.length, 'markers');
        }
    }

    // Initialize the enhanced marker system
    function initializeEnhancedChineseMarkers() {
        console.log('🚀 Initializing Enhanced Chinese Markers system...');
        
        // Wait for the page to be ready and geodir scripts to load
        if (document.readyState === 'loading') {
            console.log('📄 Page still loading, waiting for DOMContentLoaded...');
            document.addEventListener('DOMContentLoaded', function() {
                console.log('📄 DOMContentLoaded fired, starting marker extraction in 3 seconds...');
                setTimeout(enhanceChineseMapMarkersViaAPI, 3000);
            });
        } else {
            console.log('📄 Page already loaded, starting marker extraction in 3 seconds...');
            setTimeout(enhanceChineseMapMarkersViaAPI, 3000);
        }
        
        // Also run when maps are loaded
        document.addEventListener('geodir.map.loaded', function() {
            console.log('🗺️ Map loaded event fired, starting marker extraction in 1 second...');
            setTimeout(enhanceChineseMapMarkersViaAPI, 1000);
        });
        
        // Run after a longer delay in case other methods fail
        setTimeout(function() {
            console.log('⏰ Backup timer: Running marker extraction after 10 seconds...');
            enhanceChineseMapMarkersViaAPI();
        }, 10000);
    }

    // Start the initialization
    console.log('🔧 Starting Enhanced Chinese Maps system initialization...');
    
    // Make the function globally available for manual testing
    window.testChineseMarkerExtraction = enhanceChineseMapMarkersViaAPI;
    window.testMarkerInjection = injectMarkersIntoChineseMaps;
    
    // Run immediately to test
    setTimeout(function() {
        console.log('⚡ Running immediate marker extraction test...');
        enhanceChineseMapMarkersViaAPI();
    }, 1000);
    
    initializeEnhancedChineseMarkers();
    
    } // End conditional check for single place pages
    </script>
    <?php
}
add_action('wp_footer', 'geodir_chinese_maps_enhanced_marker_script', 10);
