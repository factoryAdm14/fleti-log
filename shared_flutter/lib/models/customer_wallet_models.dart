class CustomerWalletTransaction {
  const CustomerWalletTransaction({
    required this.id,
    required this.attribute,
    required this.debit,
    required this.credit,
    required this.balance,
    required this.createdAt,
  });

  factory CustomerWalletTransaction.fromJson(Map<String, dynamic> json) {
    return CustomerWalletTransaction(
      id: '${json['id']}',
      attribute: '${json['attribute'] ?? ''}',
      debit: double.tryParse('${json['debit']}') ?? 0,
      credit: double.tryParse('${json['credit']}') ?? 0,
      balance: double.tryParse('${json['balance']}') ?? 0,
      createdAt: json['created_at']?.toString() ?? '',
    );
  }

  final String id;
  final String attribute;
  final double debit;
  final double credit;
  final double balance;
  final String createdAt;

  bool get isCredit => credit > 0;
  double get amount => isCredit ? credit : debit;
}
