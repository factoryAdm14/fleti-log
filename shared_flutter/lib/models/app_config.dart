class AppConfig {
  AppConfig({
    this.businessName,
    this.mapApiKey,
    this.currencySymbol,
    this.countryCode,
    this.webSocketUrl,
    this.webSocketPort,
    this.webSocketKey,
    this.webSocketScheme,
  });

  factory AppConfig.fromJson(Map<String, dynamic> json) {
    return AppConfig(
      businessName: json['business_name']?.toString(),
      mapApiKey: json['map_api_key']?.toString(),
      currencySymbol: json['currency_symbol']?.toString() ?? 'R\$',
      countryCode: json['country_code']?.toString(),
      webSocketUrl: json['websocket_url']?.toString(),
      webSocketPort: json['websocket_port']?.toString(),
      webSocketKey: json['websocket_key']?.toString(),
      webSocketScheme: json['websocket_scheme']?.toString(),
    );
  }

  final String? businessName;
  final String? mapApiKey;
  final String? currencySymbol;
  final String? countryCode;
  final String? webSocketUrl;
  final String? webSocketPort;
  final String? webSocketKey;
  final String? webSocketScheme;
}
