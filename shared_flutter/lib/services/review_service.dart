import '../core/api_constants.dart';
import '../core/app_role.dart';
import 'api_service.dart';

class ReviewService {
  ReviewService(this._api, this._role);

  final ApiService _api;
  final AppRole _role;

  String get _prefix => _role == AppRole.customer ? 'customer' : 'driver';

  Future<bool> hasSubmitted(String tripId) async {
    final response = await _api.put(
      '${ApiConstants.baseUrl}/api/$_prefix/review/check-submission',
      body: {'trip_request_id': tripId},
    );
    final data = response.data?['data'];
    if (data is List && data.isNotEmpty) return true;
    return false;
  }

  Future<void> submit({
    required String tripId,
    required int rating,
    String feedback = '',
  }) async {
    await _api.post(
      '${ApiConstants.baseUrl}/api/$_prefix/review/store',
      body: {
        'ride_request_id': tripId,
        'rating': rating,
        if (feedback.isNotEmpty) 'feedback': feedback,
      },
    );
  }

  Future<List<Map<String, dynamic>>> listReceived({int limit = 10, int offset = 1}) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/$_prefix/review/list?limit=$limit&offset=$offset',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().toList();
  }
}
