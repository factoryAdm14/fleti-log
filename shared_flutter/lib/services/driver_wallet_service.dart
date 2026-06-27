import '../core/api_constants.dart';
import '../models/wallet_models.dart';
import 'api_service.dart';

class DriverWalletService {
  DriverWalletService(this._api);

  final ApiService _api;

  Future<FinanceWallet> getFinanceWallet() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/driver/finance/wallet');
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) return FinanceWallet.fromJson(data);
    return const FinanceWallet();
  }

  Future<List<FinanceWalletTransaction>> getFinanceTransactions({int limit = 20, int offset = 1}) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/driver/finance/wallet/transactions?limit=$limit&offset=$offset',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(FinanceWalletTransaction.fromJson).toList();
  }

  Future<List<WithdrawMethodAccount>> getWithdrawAccounts({int offset = 1}) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/driver/withdraw-method-info/list?limit=10&offset=$offset',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(WithdrawMethodAccount.fromJson).toList();
  }

  Future<List<WithdrawRequestItem>> getPendingWithdrawals({int limit = 10, int offset = 1}) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/driver/finance/withdraw/pending?limit=$limit&offset=$offset',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(WithdrawRequestItem.fromJson).toList();
  }

  Future<List<WithdrawRequestItem>> getSettledWithdrawals({int limit = 10, int offset = 1}) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/driver/finance/withdraw/settled?limit=$limit&offset=$offset',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(WithdrawRequestItem.fromJson).toList();
  }

  Future<DailyIncome> getDailyIncome() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/driver/activity/daily-income');
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) return DailyIncome.fromJson(data);
    return DailyIncome(income: 0, trips: 0);
  }

  Future<void> requestWithdraw({
    required int withdrawMethodId,
    required double amount,
    String? withdrawMethodInfoId,
    String note = '',
  }) async {
    final body = <String, dynamic>{
      'withdraw_method': withdrawMethodId,
      'amount': amount,
      if (note.isNotEmpty) 'note': note,
      if (withdrawMethodInfoId != null && withdrawMethodInfoId.isNotEmpty)
        'withdraw_method_info_id': withdrawMethodInfoId,
    };
    await _api.post(
      '${ApiConstants.baseUrl}/api/driver/finance/withdraw/request',
      body: body,
    );
  }
}
