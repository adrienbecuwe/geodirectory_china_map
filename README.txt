# GeoDirectory Chinese Maps

Contributors: adrienperso
Tags: geodirectory, chinese maps, baidu, amap, markers, coordinate conversion
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

WordPress plugin to fix marker visibility and coordinate conversion for Chinese map providers in GeoDirectory.

## Description

This plugin enhances GeoDirectory to work properly with Chinese map providers by:

* Converting coordinates from WGS84 to GCJ02/BD09 coordinate systems
* Ensuring markers display correctly on Baidu, AMap, Tencent, and TianDiTu maps  
* Supporting both places and events post types
* Preventing duplicate markers on single pages
* Providing enhanced marker loading for archive pages

## Installation

### As MU Plugin (Recommended)

1. Download `geodir-chinese-maps-enhanced.php`
2. Upload to `/wp-content/mu-plugins/` directory
3. Create the `mu-plugins` directory if it doesn't exist
4. Plugin activates automatically

### As Regular Plugin

1. Upload the plugin files to `/wp-content/plugins/geodir-chinese-maps/`
2. Activate the plugin through WordPress admin

## Frequently Asked Questions

### Does this work with all Chinese map providers?

Yes, it supports:
- Baidu Maps (百度地图)
- AMap/AutoNavi (高德地图)
- Tencent Maps (腾讯地图)  
- TianDiTu Maps (天地图)

### Why do I need coordinate conversion?

Chinese maps use different coordinate systems than international standards. This plugin automatically converts coordinates so markers appear in the correct locations.

### Will this affect my existing OpenStreetMap/Google Maps setup?

No, the plugin only activates when Chinese map providers are selected in GeoDirectory settings.

## Changelog

### 1.1.0
* Enhanced single page detection
* Added sidebar exclusion to prevent duplicate markers
* Improved event support with proper REST API endpoints
* Added comprehensive coordinate conversion for all Chinese providers
* Enhanced debugging and error handling

### 1.0.0
* Initial release
* Basic coordinate conversion support
* Marker loading fixes for Chinese maps
