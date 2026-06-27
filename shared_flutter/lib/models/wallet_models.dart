class DriverWallet {
  const DriverWallet({
    this.walletBalance = 0,
    this.receivableBalance = 0,
    this.payableBalance = 0,
    this.pendingBalance = 0,
    this.totalWithdrawn = 0,
  });

  factory DriverWallet.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const DriverWallet();
    return DriverWallet(
      walletBalance: double.tryParse('${json['wallet_balance']}') ?? 0,
      receivableBalance: double.tryParse('${json['receivable_balance']}') ?? 0,
      payableBalance: double.tryParse('${json['payable_balance']}') ?? 0,
      pendingBalance: double.tryParse('${json['pending_balance']}') ?? 0,
      totalWithdrawn: double.tryParse('${json['total_withdrawn']}') ?? 0,
    );
  }

  final double walletBalance;
  final double receivableBalance;
  final double payableBalance;
  final double pendingBalance;
  final double totalWithdrawn;

  double get withdrawableBalance {
    final net = receivableBalance - payableBalance;
    return net > 0 ? net : 0;
  }
}

class WalletTransaction {
  WalletTransaction({
    required this.id,
    required this.account,
    required this.debit,
    required this.credit,
    required this.createdAt,
  });

  factory WalletTransaction.fromJson(Map<String, dynamic> json) {
    return WalletTransaction(
      id: '${json['id'] ?? ''}',
      account: '${json['account'] ?? ''}',
      debit: double.tryParse('${json['debit']}') ?? 0,
      credit: double.tryParse('${json['credit']}') ?? 0,
      createdAt: '${json['created_at'] ?? ''}',
    );
  }

  final String id;
  final String account;
  final double debit;
  final double credit;
  final String createdAt;
}

class WithdrawMethodAccount {
  WithdrawMethodAccount({
    required this.id,
    required this.methodName,
    required this.methodId,
    required this.fields,
  });

  factory WithdrawMethodAccount.fromJson(Map<String, dynamic> json) {
    final fields = <String, String>{};
    final methodInfo = json['method_info'];
    if (methodInfo is List) {
      for (final item in methodInfo) {
        if (item is Map) {
          final key = item['key']?.toString();
          final value = item['value']?.toString();
          if (key != null && value != null) fields[key] = value;
        }
      }
    }
    return WithdrawMethodAccount(
      id: '${json['id'] ?? ''}',
      methodName: '${json['method_name'] ?? ''}',
      methodId: int.tryParse('${json['withdraw_method']?['id']}') ?? 0,
      fields: fields,
    );
  }

  final String id;
  final String methodName;
  final int methodId;
  final Map<String, String> fields;
}

class WithdrawRequestItem {
  WithdrawRequestItem({
    required this.id,
    required this.amount,
    required this.status,
    required this.createdAt,
  });

  factory WithdrawRequestItem.fromJson(Map<String, dynamic> json) {
    return WithdrawRequestItem(
      id: '${json['id'] ?? ''}',
      amount: double.tryParse('${json['amount']}') ?? 0,
      status: '${json['status'] ?? json['is_approved'] ?? ''}',
      createdAt: '${json['created_at'] ?? ''}',
    );
  }

  final String id;
  final double amount;
  final String status;
  final String createdAt;
}

class DailyIncome {
  DailyIncome({required this.income, required this.trips});

  factory DailyIncome.fromJson(Map<String, dynamic> json) {
    return DailyIncome(
      income: double.tryParse('${json['income'] ?? json['total_income']}') ?? 0,
      trips: int.tryParse('${json['trips'] ?? json['total_trips']}') ?? 0,
    );
  }

  final double income;
  final int trips;
}

class FinanceWallet {
  const FinanceWallet({
    this.availableBalance = 0,
    this.pendingBalance = 0,
    this.blockedBalance = 0,
    this.totalReceived = 0,
    this.totalWithdrawn = 0,
    this.withdrawableBalance = 0,
    this.minWithdrawAmount = 0,
    this.hasOpenWithdraw = false,
  });

  factory FinanceWallet.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const FinanceWallet();
    return FinanceWallet(
      availableBalance: double.tryParse('${json['available_balance']}') ?? 0,
      pendingBalance: double.tryParse('${json['pending_balance']}') ?? 0,
      blockedBalance: double.tryParse('${json['blocked_balance']}') ?? 0,
      totalReceived: double.tryParse('${json['total_received']}') ?? 0,
      totalWithdrawn: double.tryParse('${json['total_withdrawn']}') ?? 0,
      withdrawableBalance: double.tryParse('${json['withdrawable_balance']}') ??
          double.tryParse('${json['available_balance']}') ??
          0,
      minWithdrawAmount: double.tryParse('${json['min_withdraw_amount']}') ?? 0,
      hasOpenWithdraw: json['has_open_withdraw'] == true || json['has_open_withdraw'] == 1,
    );
  }

  final double availableBalance;
  final double pendingBalance;
  final double blockedBalance;
  final double totalReceived;
  final double totalWithdrawn;
  final double withdrawableBalance;
  final double minWithdrawAmount;
  final bool hasOpenWithdraw;
}

class FinanceWalletTransaction {
  FinanceWalletTransaction({
    required this.id,
    required this.type,
    required this.amount,
    required this.description,
    required this.status,
    required this.createdAt,
  });

  factory FinanceWalletTransaction.fromJson(Map<String, dynamic> json) {
    return FinanceWalletTransaction(
      id: '${json['id'] ?? ''}',
      type: '${json['type'] ?? ''}',
      amount: double.tryParse('${json['amount']}') ?? 0,
      description: '${json['description'] ?? json['reference'] ?? ''}',
      status: '${json['status'] ?? ''}',
      createdAt: '${json['created_at'] ?? ''}',
    );
  }

  final String id;
  final String type;
  final double amount;
  final String description;
  final String status;
  final String createdAt;

  bool get isCredit => const {'credit', 'bonus', 'refund'}.contains(type);
}

class DriverPlan {
  const DriverPlan({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    required this.durationDays,
    required this.commissionPercentage,
    required this.benefits,
  });

  factory DriverPlan.fromJson(Map<String, dynamic> json) {
    final benefitsRaw = json['benefits'];
    final benefits = benefitsRaw is List ? benefitsRaw.map((e) => '$e').toList() : <String>[];
    return DriverPlan(
      id: '${json['id'] ?? ''}',
      name: '${json['name'] ?? ''}',
      description: '${json['description'] ?? ''}',
      price: double.tryParse('${json['price']}') ?? 0,
      durationDays: int.tryParse('${json['duration_days']}') ?? 0,
      commissionPercentage: double.tryParse('${json['commission_percentage']}') ?? 0,
      benefits: benefits,
    );
  }

  final String id;
  final String name;
  final String description;
  final double price;
  final int durationDays;
  final double commissionPercentage;
  final List<String> benefits;

  String get durationLabel {
    if (durationDays >= 360) return 'Anual';
    if (durationDays >= 28) return 'Mensal';
    return '$durationDays dias';
  }
}

class DriverSubscription {
  const DriverSubscription({
    required this.id,
    required this.status,
    required this.startsAt,
    required this.expiresAt,
    required this.daysRemaining,
    required this.isActive,
    this.plan,
  });

  factory DriverSubscription.fromJson(Map<String, dynamic> json) {
    return DriverSubscription(
      id: '${json['id'] ?? ''}',
      status: '${json['status'] ?? ''}',
      startsAt: '${json['starts_at'] ?? ''}',
      expiresAt: '${json['expires_at'] ?? ''}',
      daysRemaining: int.tryParse('${json['days_remaining']}') ?? 0,
      isActive: json['is_active'] == true || json['is_active'] == 1,
      plan: json['plan'] is Map<String, dynamic>
          ? DriverPlan.fromJson(json['plan'] as Map<String, dynamic>)
          : null,
    );
  }

  final String id;
  final String status;
  final String startsAt;
  final String expiresAt;
  final int daysRemaining;
  final bool isActive;
  final DriverPlan? plan;
}

class FinancePaymentGateway {
  const FinancePaymentGateway({
    required this.key,
    required this.name,
    required this.supportsPix,
    required this.supportsCard,
  });

  factory FinancePaymentGateway.fromJson(Map<String, dynamic> json) {
    return FinancePaymentGateway(
      key: '${json['key'] ?? ''}',
      name: '${json['name'] ?? json['key'] ?? ''}',
      supportsPix: json['supports_pix'] == true,
      supportsCard: json['supports_card'] == true,
    );
  }

  final String key;
  final String name;
  final bool supportsPix;
  final bool supportsCard;
}
