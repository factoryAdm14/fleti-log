import '../core/api_constants.dart';
import '../models/geo_point.dart';
import '../models/place_suggestion.dart';
import 'api_service.dart';

class LocationService {
  LocationService(this._api);

  final ApiService _api;

  Future<String?> resolveZoneId(double lat, double lng) async {
    final uri = '${ApiConstants.customerZone}?lat=$lat&lng=$lng';
    final response = await _api.get(uri);
    return response.data?['data']?['id']?.toString();
  }

  Future<String> reverseGeocode(double lat, double lng) async {
    final uri = '${ApiConstants.customerGeocode}?lat=$lat&lng=$lng';
    final response = await _api.get(uri);
    final results = response.data?['data']?['results'];
    if (results is List && results.isNotEmpty) {
      return results.first['formatted_address']?.toString() ?? '';
    }
    return '';
  }

  Future<List<PlaceSuggestion>> searchPlaces(String query) async {
    if (query.trim().length < 3) return [];
    final encoded = Uri.encodeComponent(query.trim());
    final uri = '${ApiConstants.customerPlaceSearch}?search_text=$encoded';
    final response = await _api.get(uri);
    final suggestions = response.data?['data']?['suggestions'];
    if (suggestions is! List) return [];

    return suggestions.map<PlaceSuggestion>((item) {
      final prediction = item['placePrediction'];
      final placeId = prediction?['placeId']?.toString() ?? '';
      final text = prediction?['text']?['text']?.toString() ??
          prediction?['structuredFormat']?['mainText']?['text']?.toString() ??
          '';
      return PlaceSuggestion(placeId: placeId, label: text);
    }).where((s) => s.placeId.isNotEmpty).toList();
  }

  Future<GeoPoint?> placeDetails(String placeId) async {
    final uri = '${ApiConstants.customerPlaceDetails}?placeid=$placeId';
    final response = await _api.get(uri);
    final data = response.data?['data'];
    final location = data?['location'];
    if (location == null) return null;

    final lat = double.tryParse('${location['latitude']}');
    final lng = double.tryParse('${location['longitude']}');
    if (lat == null || lng == null) return null;

    final address = data['formattedAddress']?.toString() ??
        data['displayName']?['text']?.toString() ??
        '';
    return GeoPoint(lat, lng, address: address);
  }
}
