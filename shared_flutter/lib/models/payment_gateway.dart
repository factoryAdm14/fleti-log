class PaymentGateway {
  PaymentGateway({
    required this.gateway,
    required this.title,
    this.image,
  });

  factory PaymentGateway.fromJson(Map<String, dynamic> json) {
    return PaymentGateway(
      gateway: '${json['gateway'] ?? ''}',
      title: '${json['gateway_title'] ?? json['gateway'] ?? ''}',
      image: json['gateway_image']?.toString(),
    );
  }

  final String gateway;
  final String title;
  final String? image;
}
