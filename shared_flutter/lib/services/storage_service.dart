import 'package:shared_preferences/shared_preferences.dart';

class StorageService {
  StorageService(this._prefs);

  static const tokenKey = 'fleti_token';
  static const zoneIdKey = 'fleti_zone_id';
  static const localeKey = 'fleti_locale';

  final SharedPreferences _prefs;

  static Future<StorageService> create() async {
    final prefs = await SharedPreferences.getInstance();
    return StorageService(prefs);
  }

  String? get token => _prefs.getString(tokenKey);
  String? get zoneId => _prefs.getString(zoneIdKey);
  String get locale => _prefs.getString(localeKey) ?? 'pt';

  Future<void> saveToken(String token) => _prefs.setString(tokenKey, token);
  Future<void> saveZoneId(String zoneId) => _prefs.setString(zoneIdKey, zoneId);
  Future<void> saveLocale(String locale) => _prefs.setString(localeKey, locale);

  Future<void> clearSession() async {
    await _prefs.remove(tokenKey);
    await _prefs.remove(zoneIdKey);
  }
}
