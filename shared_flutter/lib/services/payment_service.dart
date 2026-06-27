import '../core/api_constants.dart';
import '../models/payment_gateway.dart';
import 'api_service.dart';

class PaymentService {
  PaymentService(this._api);

  final ApiService _api;

  Future<List<PaymentGateway>> getGateways() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/customer/config/get-payment-methods');
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(PaymentGateway.fromJson).toList();
  }

  Future<void> submitPayment(String tripId, String paymentMethod) async {
    final uri =
        '${ApiConstants.baseUrl}/api/customer/ride/payment?trip_request_id=$tripId&payment_method=$paymentMethod';
    await _api.get(uri);
  }

  String digitalPaymentUrl(String tripId, String paymentMethod, {String tips = '0'}) {
    return '${ApiConstants.baseUrl}/api/customer/ride/digital-payment?trip_request_id=$tripId&payment_method=$paymentMethod&tips=$tips';
  }
}
