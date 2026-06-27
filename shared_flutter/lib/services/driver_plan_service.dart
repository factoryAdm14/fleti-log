import '../core/api_constants.dart';
import '../models/wallet_models.dart';
import 'api_service.dart';

class DriverPlanService {
  DriverPlanService(this._api);

  final ApiService _api;

  Future<({bool enabled, String? activeMode, List<DriverPlan> plans})> getPlans() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/driver/finance/plans');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      return (enabled: false, activeMode: null, plans: <DriverPlan>[]);
    }
    final enabled = data['plans_enabled'] == true;
    final plansRaw = data['plans'];
    final plans = plansRaw is List
        ? plansRaw.whereType<Map<String, dynamic>>().map(DriverPlan.fromJson).toList()
        : <DriverPlan>[];
    return (
      enabled: enabled,
      activeMode: data['active_mode']?.toString(),
      plans: plans,
    );
  }

  Future<({bool hasActive, DriverSubscription? subscription})> getSubscription() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/driver/finance/subscription');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      return (hasActive: false, subscription: null);
    }
    final sub = data['subscription'];
    return (
      hasActive: data['has_active_plan'] == true,
      subscription: sub is Map<String, dynamic> ? DriverSubscription.fromJson(sub) : null,
    );
  }

  Future<DriverSubscription?> getPendingSubscription() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/driver/finance/subscription/pending');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) return null;
    final sub = data['pending_subscription'];
    return sub is Map<String, dynamic> ? DriverSubscription.fromJson(sub) : null;
  }

  Future<List<FinancePaymentGateway>> getPaymentGateways() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/finance/payment-gateways');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) return [];
    final gateways = data['gateways'];
    if (gateways is! List) return [];
    return gateways.whereType<Map<String, dynamic>>().map(FinancePaymentGateway.fromJson).toList();
  }

  Future<String> checkout({required String planId, required String paymentMethod}) async {
    final response = await _api.post(
      '${ApiConstants.baseUrl}/api/driver/finance/plans/$planId/checkout',
      body: {'payment_method': paymentMethod},
    );
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) {
      final url = data['redirect_url']?.toString();
      if (url != null && url.isNotEmpty) return url;
    }
    throw Exception(response.message ?? 'Não foi possível iniciar o pagamento do plano.');
  }
}
