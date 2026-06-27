import 'package:flutter_test/flutter_test.dart';
import 'package:shared_flutter/shared_flutter.dart';

void main() {
  test('FinanceWallet parses API payload', () {
    final wallet = FinanceWallet.fromJson({
      'available_balance': 120.5,
      'withdrawable_balance': 100,
      'pending_balance': 20.5,
    });
    expect(wallet.availableBalance, 120.5);
    expect(wallet.withdrawableBalance, 100);
    expect(wallet.pendingBalance, 20.5);
  });

  test('DriverPlan duration label', () {
    const plan = DriverPlan(
      id: '1',
      name: 'Mensal',
      description: '',
      price: 99,
      durationDays: 30,
      commissionPercentage: 0,
      benefits: [],
    );
    expect(plan.durationLabel, 'Mensal');
  });
}
