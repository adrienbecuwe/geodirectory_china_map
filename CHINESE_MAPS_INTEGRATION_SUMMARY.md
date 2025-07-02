# Chinese Map Providers Integration for GeoDirectory WordPress Plugin

## Overview
This project adds robust support for Chinese map providers (Amap, Baidu, Tencent, Tianditu) to the GeoDirectory WordPress plugin, ensuring both frontend and backend maps work reliably with proper coordinate conversion and HTTPS tile loading.

## Key Features Added

### 1. Chinese Map Provider Support
- **Providers**: Amap, Baidu, Tencent, Tianditu/OpenStreetMap
- **Admin UI**: API key fields, provider selection, debugging controls
- **Unified Experience**: All Chinese providers now use Leaflet + AutoNavi tiles (no complex SDK dependencies)

### 2. Coordinate Conversion (WGS84 ↔ GCJ-02)
- **Algorithm**: Exact Flutter/Dart implementation for consistency
- **Coverage**: Both PHP (backend) and JavaScript (frontend)
- **Scope**: Single maps, cluster maps, archive maps, admin maps
- **Accuracy**: Iterative reverse conversion for precise marker placement

### 3. HTTPS Tile Loading
- **Fixed**: All AutoNavi tile URLs updated to HTTPS
- **Security**: Eliminates mixed content warnings
- **CDN Fallback**: Robust script loading with retry mechanisms

### 4. Comprehensive Marker Support
- **PHP Hooks**: 10+ filter hooks to catch all marker data sources
- **JavaScript Interception**: Real-time AJAX marker conversion
- **Multiple Formats**: Handles lat/lng, latitude/longitude, position objects
- **Debug Logging**: Detailed console output for troubleshooting

## Technical Implementation

### Backend Changes (class-geodir-map.php)
```php
// Enhanced coordinate conversion with exact Flutter algorithm
private function wgs84_to_gcj02($lat, $lng) {
    // Exact port of Flutter coordinate conversion
}

// Comprehensive marker filtering hooks
add_filter('geodir_ajax_map_markers', array($this, 'filter_markers_for_amap'), 10, 1);
add_filter('geodir_cluster_markers', array($this, 'filter_markers_for_amap'), 10, 1);
// ... 8 more hooks for complete coverage

// Improved admin marker drag with iterative reverse conversion
private function gcj02_to_wgs84_iterative($gcj_lat, $gcj_lng) {
    // High-precision reverse conversion
}
```

### Frontend Changes (geodir-amap-admin.php)
```javascript
// Dynamic script loading with CDN fallback
function loadLeafletForChinese() {
    // HTTPS AutoNavi tile configuration
    // Robust error handling and retry logic
}

// Real-time marker interception
window.geodir_intercept_markers = function(markers) {
    // Convert coordinates for all marker formats
    // Comprehensive debug logging
}

// AJAX response interception
$(document).ajaxSuccess(function(event, xhr, settings) {
    // Convert cluster/archive markers in real-time
});
```

### Provider Configuration
- **Amap & Baidu**: Both use AutoNavi tiles (identical experience)
- **Tencent**: Leaflet + Tencent tiles
- **Tianditu**: Leaflet + Tianditu tiles
- **All**: HTTPS secure connections, proper API key handling

## Files Modified

### Core Files
- `class-geodir-map.php` - Main map class with coordinate conversion and hooks
- `geodir-amap-admin.php` - Admin UI, JavaScript logic, and map initialization

### Key Improvements
1. **Coordinate Accuracy**: Flutter-exact algorithm ensures perfect marker positioning
2. **Script Loading**: Replaced document.write() with dynamic injection + CDN fallback
3. **Debug System**: Comprehensive logging for troubleshooting
4. **Security**: All tile URLs use HTTPS
5. **Coverage**: Works for single maps, clusters, archives, and admin interfaces

## Usage
1. Install the modified plugin files
2. Configure API keys in WordPress admin (Maps settings)
3. Select Chinese provider (Amap, Baidu, Tencent, or Tianditu)
4. Maps automatically handle coordinate conversion and display correctly

## Verification
- ✅ Single event maps work perfectly
- ✅ Backend admin maps work perfectly  
- ✅ Cluster/archive maps show all markers correctly
- ✅ Coordinates match Flutter app exactly
- ✅ HTTPS loading eliminates security warnings
- ✅ Debug logging shows successful conversion

## Result
Chinese map providers now work identically to the iOS Flutter app, with accurate marker positioning, secure HTTPS loading, and comprehensive debugging support across all map types (single, cluster, archive, admin).
