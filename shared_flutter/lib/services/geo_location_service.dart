import 'package:geolocator/geolocator.dart';

import '../models/geo_point.dart';
import 'location_service.dart';

class GeoLocationService {
  GeoLocationService(this._location);

  final LocationService _location;

  static const defaultPoint = GeoPoint(-23.55052, -46.633308, address: 'São Paulo, SP');

  Future<GeoPoint> currentPosition() async {
    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled) return defaultPoint;

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      return defaultPoint;
    }

    final position = await Geolocator.getCurrentPosition();
    final address = await _location.reverseGeocode(position.latitude, position.longitude);
    return GeoPoint(position.latitude, position.longitude, address: address);
  }
}
