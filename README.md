# GeoDirectory Chinese Maps Integration

A WordPress plugin enhancement that adds robust support for Chinese map providers (Amap, Baidu, Tencent, Tianditu) to the GeoDirectory plugin with accurate coordinate conversion and HTTPS tile loading.

## Quick Start

1. **Backup** your existing GeoDirectory plugin files
2. **Replace** `class-geodir-map.php` in your GeoDirectory plugin directory 
3. **Add** `geodir-amap-admin.php` to your GeoDirectory plugin directory
4. **Configure** API keys in WordPress Admin → Maps Settings
5. **Select** your preferred Chinese map provider

## Features

- ✅ **4 Chinese Providers**: Amap, Baidu, Tencent, Tianditu
- ✅ **Accurate Coordinates**: Flutter-exact WGS84↔GCJ-02 conversion
- ✅ **Secure HTTPS**: All tile URLs use HTTPS
- ✅ **Complete Coverage**: Single maps, clusters, archives, admin
- ✅ **Debug Support**: Comprehensive logging for troubleshooting

## Files

- `class-geodir-map.php` - Main map class with coordinate conversion
- `geodir-amap-admin.php` - Admin UI and JavaScript logic  
- `CHINESE_MAPS_INTEGRATION_SUMMARY.md` - Detailed technical documentation

## Compatibility

- **WordPress**: 5.0+
- **GeoDirectory**: 2.0+
- **Browsers**: All modern browsers
- **Mobile**: iOS, Android, responsive

## Support

For detailed technical information, see [CHINESE_MAPS_INTEGRATION_SUMMARY.md](CHINESE_MAPS_INTEGRATION_SUMMARY.md)

## License

This project enhances the existing GeoDirectory plugin and maintains compatibility with its licensing terms.
