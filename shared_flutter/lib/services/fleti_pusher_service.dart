import 'dart:async';
import 'dart:convert';

import 'package:dart_pusher_channels/dart_pusher_channels.dart';
import 'package:flutter/foundation.dart';

import '../models/app_config.dart';

/// WebSocket via Laravel Reverb/Pusher. Fallback: polling nos apps.
class FletiPusherService extends ChangeNotifier {
  PusherChannelsClient? _client;
  String? _token;
  String? _host;
  final List<StreamSubscription<dynamic>> _subscriptions = [];

  WebSocketConnectionState state = WebSocketConnectionState.disconnected;

  bool get isConnected => state == WebSocketConnectionState.connected;

  Future<void> connect({required AppConfig config, required String token}) async {
    if (config.webSocketUrl == null || config.webSocketKey == null) return;
    if (_client != null && isConnected) return;

    _token = token;
    _host = config.webSocketUrl;
    state = WebSocketConnectionState.connecting;
    notifyListeners();

    final options = PusherChannelsOptions.fromHost(
      host: config.webSocketUrl!,
      scheme: config.webSocketScheme == 'https' ? 'wss' : 'ws',
      key: config.webSocketKey!,
      port: int.tryParse(config.webSocketPort ?? '6001') ?? 6001,
    );

    _client = PusherChannelsClient.websocket(
      options: options,
      connectionErrorHandler: (exception, trace, refresh) async {
        state = WebSocketConnectionState.disconnected;
        notifyListeners();
        refresh();
      },
    );

    await _client?.connect();
    final socketId = _client?.channelsManager.channelsConnectionDelegate.socketId;
    state = socketId != null
        ? WebSocketConnectionState.connected
        : WebSocketConnectionState.disconnected;
    notifyListeners();

    _client?.lifecycleStream.listen((_) {
      state = WebSocketConnectionState.disconnected;
      notifyListeners();
    });
  }

  void _bindPrivate(String channel, String event, void Function(Map<String, dynamic>) handler) {
    if (_client == null || _host == null || _token == null) return;
    final authorizationDelegate =
        EndpointAuthorizableChannelTokenAuthorizationDelegate.forPrivateChannel(
      authorizationEndpoint: Uri.parse('https://$_host/broadcasting/auth'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $_token',
      },
    );
    final privateChannel = _client!.privateChannel(
      channel,
      authorizationDelegate: authorizationDelegate,
    );
    privateChannel.subscribeIfNotUnsubscribed();
    _subscriptions.add(
      privateChannel.bind(event).listen((message) {
        if (message.data == null) return;
        try {
          final decoded = jsonDecode(message.data!);
          if (decoded is Map<String, dynamic>) handler(decoded);
        } catch (_) {}
      }),
    );
  }

  /// Eventos de corrida para o cliente (aceite, início, cancelamento, conclusão, pagamento).
  void watchCustomerTrip(String tripId, VoidCallback onChanged) {
    final pairs = <(String, String)>[
      ('private-driver-trip-accepted.$tripId', 'driver-trip-accepted.$tripId'),
      ('private-driver-trip-started.$tripId', 'driver-trip-started.$tripId'),
      ('private-driver-trip-cancelled.$tripId', 'driver-trip-cancelled.$tripId'),
      ('private-driver-trip-completed.$tripId', 'driver-trip-completed.$tripId'),
      ('private-driver-payment-received.$tripId', 'driver-payment-received.$tripId'),
    ];
    for (final pair in pairs) {
      _bindPrivate(pair.$1, pair.$2, (_) => onChanged());
    }
  }

  /// Novas solicitações para o motorista.
  void watchDriverRequests(String driverId, void Function(String tripId) onRequest) {
    _bindPrivate(
      'private-customer-trip-request.$driverId',
      'customer-trip-request.$driverId',
      (data) {
        final tripId = data['trip_id']?.toString() ?? data['id']?.toString() ?? '';
        if (tripId.isNotEmpty) onRequest(tripId);
      },
    );
    _bindPrivate(
      'private-customer-trip-cancelled-after-ongoing.$driverId',
      'customer-trip-cancelled-after-ongoing.$driverId',
      (_) => onRequest(''),
    );
  }

  void disconnect() {
    for (final sub in _subscriptions) {
      sub.cancel();
    }
    _subscriptions.clear();
    _client?.dispose();
    _client = null;
    state = WebSocketConnectionState.disconnected;
    notifyListeners();
  }

  @override
  void dispose() {
    disconnect();
    super.dispose();
  }
}

enum WebSocketConnectionState { disconnected, connecting, connected }
