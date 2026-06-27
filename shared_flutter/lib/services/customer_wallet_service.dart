import '../core/api_constants.dart';
import '../models/customer_wallet_models.dart';
import '../models/payment_gateway.dart';
import 'api_service.dart';
import 'payment_service.dart';

class CustomerWalletService {
  CustomerWalletService(this._api, this._paymentService);

  final ApiService _api;
  final PaymentService _paymentService;

  Future<List<CustomerWalletTransaction>> getTransactions({
    int limit = 20,
    int offset = 1,
    String transactionType = 'both',
  }) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/customer/transaction/list'
      '?limit=$limit&offset=$offset&transaction_type=$transactionType',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data
        .whereType<Map<String, dynamic>>()
        .map(CustomerWalletTransaction.fromJson)
        .toList();
  }

  Future<List<PaymentGateway>> getPaymentGateways() => _paymentService.getGateways();

  String addFundUrl({
    required String userId,
    required double amount,
    required String paymentMethod,
  }) {
    final params = Uri(queryParameters: {
      'user_id': userId,
      'amount': amount.toStringAsFixed(2),
      'payment_method': paymentMethod,
    });
    return '${ApiConstants.baseUrl}/api/customer/wallet/add-fund-digitally$params';
  }
}
