import '../core/api_constants.dart';
import '../models/geo_point.dart';
import '../models/trip_model.dart';
import 'api_service.dart';

class DriverRideService {
  DriverRideService(this._api);

  final ApiService _api;

  Future<bool> toggleOnlineStatus() async {
    final response = await _api.post(ApiConstants.driverOnlineStatus, body: {});
    return response.statusCode == 200;
  }

  Future<List<TripModel>> pendingRides({int limit = 20, int offset = 1}) async {
    final response = await _api.get(ApiConstants.driverPendingRides(limit: limit, offset: offset));
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(TripModel.fromJson).toList();
  }

  Future<TripModel> getDetails(String tripId, {bool overview = false}) async {
    final suffix = overview ? '?type=overview' : '';
    final response = await _api.get('${ApiConstants.driverTripDetails}$tripId$suffix');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      throw Exception('Detalhes indisponíveis');
    }
    return TripModel.fromJson(data);
  }

  Future<void> accept(String tripId) async {
    await _api.post(ApiConstants.driverTripAction, body: {
      'trip_request_id': tripId,
      'action': 'accepted',
    });
  }

  Future<void> reject(String tripId) async {
    await _api.post(ApiConstants.driverTripAction, body: {
      'trip_request_id': tripId,
      'action': 'rejected',
    });
    await _api.post(ApiConstants.driverTripAction, body: {
      'trip_request_id': tripId,
    });
  }

  Future<void> updateStatus(String tripId, String status, {String cancelReason = ''}) async {
    await _api.postMultipart(
      ApiConstants.driverUpdateStatus,
      {
        'trip_request_id': tripId,
        'status': status,
        '_method': 'put',
        if (cancelReason.isNotEmpty) 'cancel_reason': cancelReason,
        'return_time': '',
      },
    );
  }

  Future<void> sendLocation({
    required String userId,
    required GeoPoint point,
    String zoneId = '',
  }) async {
    await _api.post(ApiConstants.driverStoreLocation, body: {
      'user_id': userId,
      'type': 'driver',
      'latitude': point.latitude.toString(),
      'longitude': point.longitude.toString(),
      'zone_id': zoneId,
    });
  }
}
