# GeoDirectory Chinese Maps & Markers Fix

WordPress MU Plugin to fix marker visibility and coordinate conversion for Chinese map providers in GeoDirectory.

## 🗺️ Supported Map Providers

- **Baidu Maps** (百度地图)
- **AMap/AutoNavi** (高德地图) 
- **Tencent Maps** (腾讯地图)
- **TianDiTu Maps** (天地图)

## ✨ Features

- **Automatic Coordinate Conversion**: WGS84 → GCJ02/BD09 for proper marker positioning
- **Enhanced Marker Loading**: Multiple fallback methods for reliable marker display
- **Smart Detection**: Automatically detects places and events with proper REST API endpoints
- **Sidebar Exclusion**: Prevents duplicate markers from sidebar content on single pages
- **Archive Support**: Full support for listing and archive pages
- **Marker Clustering**: Compatible with marker clustering plugins

## 📦 Installation

### Method 1: MU Plugin (Recommended)

1. Download `geodir-chinese-maps-enhanced.php`
2. Upload to `/wp-content/mu-plugins/` directory
3. Create the `mu-plugins` directory if it doesn't exist
4. The plugin activates automatically (no activation needed)

### Method 2: Regular Plugin

1. Upload to `/wp-content/plugins/geodir-chinese-maps/`
2. Activate through WordPress admin → Plugins

## ⚙️ Setup

1. **Install GeoDirectory** (required dependency)
2. **Select Chinese Map Provider**:
   - Go to GeoDirectory → Settings → Maps
   - Choose: Baidu, AMap, Tencent, or TianDiTu
3. **Add API Keys** (if required by your chosen provider)
4. **Test markers** on your places/events pages

## 🎯 How It Works

### Coordinate Conversion
```javascript
// Baidu Maps
WGS84 → GCJ02 → BD09

// AMap/Tencent  
WGS84 → GCJ02

// TianDiTu
WGS84 (no conversion)
```

### Marker Detection
- Scans for `.geodir-post`, `.geodir-event` elements
- Extracts coordinates from data attributes
- Uses REST API endpoints: `/wp-json/geodir/v2/places` and `/wp-json/geodir/v2/events`
- Excludes sidebar content (`.listing-right-sidebar`)

## 🔧 Troubleshooting

### No Markers Visible
1. Check browser console for debug messages
2. Verify posts have latitude/longitude data
3. Ensure correct map provider is selected
4. Check API keys are valid (if required)

### Duplicate Markers
- Plugin automatically prevents duplicates on single pages
- Sidebar content is excluded from marker extraction

### Console Debugging
Look for these messages:
```
🗺️ Chinese Maps Enhanced Frontend Init
📋 Found X listing elements (excluding sidebar)
🔄 Marker conversion: [coordinates]
✅ Successfully rendered X markers
```

## 📋 Requirements

- **WordPress** 5.0+
- **GeoDirectory** plugin
- **PHP** 7.4+
- Posts with latitude/longitude meta data

## 🌍 Compatibility

- **Post Types**: Places (`gd_place`), Events (`gd_event`)
- **Page Types**: Single pages, Archives, Category pages, Search results
- **Themes**: Any GeoDirectory-compatible theme
- **Clustering**: Works with marker clustering plugins

## 🚀 Quick Test

After installation:

1. Visit a places or events archive page
2. Open browser developer console
3. Look for Chinese Maps debug messages
4. Verify markers appear on the map with correct positioning

## 📝 License

GPL v2 or later

## 🆘 Support

For issues or questions:
1. Check browser console for error messages
2. Verify GeoDirectory posts have coordinate data
3. Test with different map providers
4. Ensure no JavaScript conflicts

---

**Note**: This plugin only works with GeoDirectory and Chinese map providers. Standard OpenStreetMap/Google Maps don't require this fix.
