import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:shared_flutter/shared_flutter.dart';

class AppState extends ChangeNotifier {
  AppState({
    required this.auth,
    required this.configService,
    required this.driverRideService,
    required this.pusher,
    required this.walletService,
    required this.planService,
    required this.chatService,
    required this.geoLocation,
    required this.storage,
    required this.api,
  }) : _loggedIn = auth.isLoggedIn;

  final AuthService auth;
  final ConfigService configService;
  final DriverRideService driverRideService;
  final FletiPusherService pusher;
  final DriverWalletService walletService;
  final DriverPlanService planService;
  final ChatService chatService;
  final GeoLocationService geoLocation;
  final StorageService storage;
  final ApiService api;

  bool _loggedIn;
  UserModel? _user;
  String? _error;
  AppConfig? _config;
  bool _isOnline = false;
  bool _mapsReady = false;
  List<TripModel> _pendingRides = [];
  TripModel? _activeTrip;
  Timer? _pollTimer;
  Timer? _locationTimer;

  bool get isLoggedIn => _loggedIn;
  UserModel? get user => _user;
  String? get error => _error;
  AppConfig? get config => _config;
  bool get isOnline => _isOnline;
  bool get mapsReady => _mapsReady;
  List<TripModel> get pendingRides => _pendingRides;
  TripModel? get activeTrip => _activeTrip;

  void refreshUser(UserModel user) {
    _user = user;
    notifyListeners();
  }

  Future<void> bootstrap() async {
    await _loadConfig();
    if (!auth.isLoggedIn) return;
    try {
      _user = await auth.fetchProfile();
      _loggedIn = true;
      _isOnline = _user?.isOnline == '1';
      if (_isOnline) {
        _startPolling();
        await _connectWebSocket();
        _startLocationUpdates();
      }
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
  }

  Future<bool> login(String phoneOrEmail, String password) async {
    _error = null;
    notifyListeners();
    try {
      await auth.login(phoneOrEmail: phoneOrEmail, password: password);
      _user = await auth.fetchProfile();
      _loggedIn = true;
      _isOnline = _user?.isOnline == '1';
      if (_isOnline) {
        _startPolling();
        await _connectWebSocket();
      }
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

  Future<void> setOnline(bool value) async {
    if (_isOnline == value) return;
    try {
      final ok = await driverRideService.toggleOnlineStatus();
      if (!ok) throw Exception('Não foi possível alterar o status');
      _user = await auth.fetchProfile();
      _isOnline = _user?.isOnline == '1';
      if (_isOnline) {
        _startPolling();
        _startLocationUpdates();
        await _connectWebSocket();
      } else {
        _stopPolling();
        _stopLocationUpdates();
        _pendingRides = [];
        pusher.disconnect();
      }
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
      rethrow;
    }
  }

  void _startPolling() {
    _pollTimer?.cancel();
    final seconds = pusher.isConnected ? 20 : 6;
    _pollTimer = Timer.periodic(Duration(seconds: seconds), (_) => refreshPendingRides());
    refreshPendingRides();
  }

  Future<void> _connectWebSocket() async {
    if (_user == null || _config == null) return;
    final token = auth.token;
    if (token == null) return;
    await pusher.connect(config: _config!, token: token);
    pusher.watchDriverRequests(_user!.id, (_) => refreshPendingRides());
    _startPolling();
  }

  void _stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  void _startLocationUpdates() {
    _locationTimer?.cancel();
    _locationTimer = Timer.periodic(const Duration(seconds: 15), (_) async {
      if (!_isOnline || _user == null) return;
      try {
        final point = await geoLocation.currentPosition();
        await driverRideService.sendLocation(
          userId: _user!.id,
          point: point,
          zoneId: storage.zoneId ?? '',
        );
      } catch (_) {}
    });
  }

  void _stopLocationUpdates() {
    _locationTimer?.cancel();
    _locationTimer = null;
  }

  Future<void> refreshPendingRides() async {
    if (!_isOnline || !_loggedIn) return;
    try {
      _pendingRides = await driverRideService.pendingRides(limit: 20);
      notifyListeners();
    } catch (_) {}
  }

  Future<TripModel> loadTrip(String tripId) => driverRideService.getDetails(tripId);

  Future<void> acceptTrip(String tripId) async {
    await driverRideService.accept(tripId);
    _activeTrip = await driverRideService.getDetails(tripId);
    _pendingRides.removeWhere((t) => t.id == tripId);
    notifyListeners();
  }

  Future<void> rejectTrip(String tripId) async {
    await driverRideService.reject(tripId);
    _pendingRides.removeWhere((t) => t.id == tripId);
    notifyListeners();
  }

  Future<void> completeTrip(String tripId) async {
    await driverRideService.updateStatus(tripId, 'completed');
    _activeTrip = await driverRideService.getDetails(tripId);
    notifyListeners();
  }

  Future<void> logout() async {
    _stopPolling();
    _stopLocationUpdates();
    pusher.disconnect();
    await auth.logout();
    _loggedIn = false;
    _user = null;
    _isOnline = false;
    _pendingRides = [];
    _activeTrip = null;
    notifyListeners();
  }

  @override
  void dispose() {
    _stopPolling();
    _stopLocationUpdates();
    super.dispose();
  }
}
