import 'package:flutter/foundation.dart';
import 'package:shared_flutter/shared_flutter.dart';

class AppState extends ChangeNotifier {
  AppState({
    required this.auth,
    required this.configService,
    required this.locationService,
    required this.rideService,
    required this.tripService,
    required this.paymentService,
    required this.walletService,
    required this.chatService,
    required this.reviewService,
    required this.pusher,
    required this.geoLocation,
    required this.storage,
    required this.api,
  }) : _loggedIn = auth.isLoggedIn;

  final AuthService auth;
  final ConfigService configService;
  final LocationService locationService;
  final RideService rideService;
  final CustomerTripService tripService;
  final PaymentService paymentService;
  final CustomerWalletService walletService;
  final ChatService chatService;
  final ReviewService reviewService;
  final FletiPusherService pusher;
  final GeoLocationService geoLocation;
  final StorageService storage;
  final ApiService api;

  bool _loggedIn;
  UserModel? _user;
  String? _error;
  AppConfig? _config;
  bool _mapsReady = false;

  bool get isLoggedIn => _loggedIn;
  UserModel? get user => _user;
  String? get error => _error;
  AppConfig? get config => _config;
  bool get mapsReady => _mapsReady;

  Future<void> bootstrap() async {
    await _loadConfig();
    if (!auth.isLoggedIn) return;
    try {
      _user = await auth.fetchProfile();
      _loggedIn = true;
    } catch (_) {
      await auth.logout();
      _loggedIn = false;
    }
    notifyListeners();
  }

  Future<void> _loadConfig() async {
    try {
      _config = await configService.fetch();
      if (_config?.mapApiKey != null && _config!.mapApiKey!.isNotEmpty) {
        _mapsReady = true;
      }
    } catch (_) {}
    notifyListeners();
  }

  Future<bool> login(String phoneOrEmail, String password) async {
    _error = null;
    notifyListeners();
    try {
      await auth.login(phoneOrEmail: phoneOrEmail, password: password);
      _user = await auth.fetchProfile();
      _loggedIn = true;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString();
    }
    notifyListeners();
    return false;
  }

  Future<bool> register({
    required String firstName,
    required String lastName,
    required String phone,
    required String password,
    String email = '',
    bool termsAccepted = false,
    bool privacyAccepted = false,
    bool locationConsentAccepted = false,
    bool marketingConsentAccepted = false,
  }) async {
    _error = null;
    notifyListeners();
    try {
      await auth.register(
        firstName: firstName,
        lastName: lastName,
        phone: phone,
        password: password,
        email: email,
        termsAccepted: termsAccepted,
        privacyAccepted: privacyAccepted,
        locationConsentAccepted: locationConsentAccepted,
        marketingConsentAccepted: marketingConsentAccepted,
      );
      _user = await auth.fetchProfile();
      _loggedIn = true;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString();
    }
    notifyListeners();
    return false;
  }

  Future<void> applyZone(String zoneId) async {
    await storage.saveZoneId(zoneId);
    api.updateHeaders(zoneId: zoneId, token: auth.token, locale: storage.locale);
  }

  Future<void> refreshProfile() async {
    if (!auth.isLoggedIn) return;
    _user = await auth.fetchProfile();
    notifyListeners();
  }

  Future<void> logout() async {
    await auth.logout();
    _loggedIn = false;
    _user = null;
    notifyListeners();
  }
}
