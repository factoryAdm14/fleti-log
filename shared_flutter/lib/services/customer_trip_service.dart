import '../core/api_constants.dart';
import '../models/trip_model.dart';
import 'api_service.dart';

class CustomerTripService {
  CustomerTripService(this._api);

  final ApiService _api;

  Future<TripModel> getDetails(String tripId) async {
    final response = await _api.get('${ApiConstants.customerTripDetails}$tripId');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      throw Exception('Detalhes da corrida indisponíveis');
    }
    return TripModel.fromJson(data);
  }

  Future<List<TripModel>> list({int offset = 1, String status = ''}) async {
    final response = await _api.get(ApiConstants.customerTripList(offset: offset, status: status));
    final data = response.data?['data'];
    if (data is! List) return [];
    return data
        .whereType<Map<String, dynamic>>()
        .map(TripModel.fromJson)
        .toList();
  }

  Future<void> cancel(String tripId, {String reason = 'Cancelado pelo cliente'}) async {
    await _api.post(
      ApiConstants.customerCancelTrip(tripId),
      body: {
        'status': 'cancelled',
        'cancel_reason': reason,
        '_method': 'put',
      },
    );
  }
}
