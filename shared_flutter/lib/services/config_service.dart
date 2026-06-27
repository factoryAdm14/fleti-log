import '../core/api_constants.dart';
import '../core/app_role.dart';
import '../models/app_config.dart';
import 'api_service.dart';

class ConfigService {
  ConfigService(this._api, this.role);

  final ApiService _api;
  final AppRole role;

  Future<AppConfig> fetch() async {
    final uri = role == AppRole.customer
        ? ApiConstants.customerConfig()
        : ApiConstants.driverConfig();
    final response = await _api.get(uri);
    final data = response.data?['data'] ?? response.data;
    if (data is! Map<String, dynamic>) {
      throw Exception('Configuração inválida');
    }
    return AppConfig.fromJson(data);
  }
}
