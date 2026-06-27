import '../core/api_constants.dart';
import '../core/api_exception.dart';
import '../core/app_role.dart';
import '../models/api_response.dart';
import '../models/user_model.dart';
import 'api_service.dart';
import 'storage_service.dart';

class AuthService {
  AuthService(this._api, this._storage, this.role);

  final ApiService _api;
  final StorageService _storage;
  final AppRole role;

  String? get token => _storage.token;
  bool get isLoggedIn => token != null && token!.isNotEmpty;

  Future<void> register({
    required String firstName,
    required String lastName,
    required String phone,
    required String password,
    String email = '',
    String referralCode = '',
    bool termsAccepted = false,
    bool privacyAccepted = false,
    bool locationConsentAccepted = false,
    bool marketingConsentAccepted = false,
  }) async {
    final response = await _api.postMultipart(
      ApiConstants.customerRegistration(),
      {
        'first_name': firstName,
        'last_name': lastName,
        'phone': phone,
        'password': password,
        'confirm_password': password,
        'email': email,
        'referral_code': referralCode,
        'terms_accepted': termsAccepted ? '1' : '0',
        'privacy_accepted': privacyAccepted ? '1' : '0',
        'location_consent_accepted': locationConsentAccepted ? '1' : '0',
        'marketing_consent_accepted': marketingConsentAccepted ? '1' : '0',
      },
    );

    if (response.statusCode != 200) {
      throw ApiException(response.message ?? 'Cadastro falhou', statusCode: response.statusCode);
    }

    await login(phoneOrEmail: phone, password: password);
  }

  Future<String> login({
    required String phoneOrEmail,
    required String password,
  }) async {
    final response = await _api.post(
      ApiConstants.loginUri(role),
      body: {
        'phone_or_email': phoneOrEmail,
        'password': password,
      },
    );

    final token = _extractToken(response.data);
    if (token == null || token.isEmpty) {
      throw Exception(response.message ?? 'Token não retornado');
    }

    await _storage.saveToken(token);
    _api.updateHeaders(token: token, locale: _storage.locale);
    return token;
  }

  Future<UserModel> fetchProfile() async {
    final response = await _api.get(ApiConstants.profileUri(role));
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      throw Exception('Perfil inválido');
    }
    return UserModel.fromJson(data);
  }

  Future<void> logout() async {
    try {
      await _api.post(ApiConstants.logoutUri(), body: {});
    } catch (_) {}
    await _storage.clearSession();
    _api.updateHeaders(token: '');
  }

  String? _extractToken(Map<String, dynamic>? data) {
    if (data == null) return null;
    return data['token']?.toString() ??
        data['access_token']?.toString() ??
        (data['data'] is Map ? (data['data'] as Map)['token']?.toString() : null);
  }
}
