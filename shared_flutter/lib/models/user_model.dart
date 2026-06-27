import 'wallet_models.dart';

class UserModel {
  const UserModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.phone,
    this.email,
    this.profileImage,
    this.walletBalance,
    this.isOnline,
    this.driverWallet,
    this.tripIncome,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: '${json['id'] ?? ''}',
      firstName: '${json['first_name'] ?? ''}',
      lastName: '${json['last_name'] ?? ''}',
      phone: '${json['phone'] ?? json['phone_or_email'] ?? ''}',
      email: json['email'] as String?,
      profileImage: json['profile_image'] as String?,
      walletBalance: (json['wallet_balance'] as num?)?.toDouble() ??
          (json['wallet'] is Map ? (json['wallet']['wallet_balance'] as num?)?.toDouble() : null),
      isOnline: json['is_online']?.toString() ??
          (json['details'] is Map ? json['details']['is_online']?.toString() : null),
      driverWallet: json['wallet'] is Map
          ? DriverWallet.fromJson(json['wallet'] as Map<String, dynamic>)
          : null,
      tripIncome: double.tryParse('${json['trip_income']}'),
    );
  }

  final String id;
  final String firstName;
  final String lastName;
  final String phone;
  final String? email;
  final String? profileImage;
  final double? walletBalance;
  final String? isOnline;
  final DriverWallet? driverWallet;
  final double? tripIncome;

  String get fullName => '$firstName $lastName'.trim();
}
