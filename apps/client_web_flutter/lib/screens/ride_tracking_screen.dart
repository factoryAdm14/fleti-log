import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';
import '../widgets/ride_map.dart';

class RideTrackingScreen extends StatefulWidget {
  const RideTrackingScreen({super.key, required this.tripId});

  final String tripId;

  @override
  State<RideTrackingScreen> createState() => _RideTrackingScreenState();
}

class _RideTrackingScreenState extends State<RideTrackingScreen> {
  TripModel? _trip;
  String? _error;
  bool _loading = true;
  bool _cancelling = false;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _load();
    _startPolling();
    _initWebSocket();
  }

  void _startPolling() {
    final appState = context.read<AppState>();
    final interval = appState.pusher.isConnected ? 30 : 8;
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(Duration(seconds: interval), (_) => _load(silent: true));
  }

  Future<void> _initWebSocket() async {
    final appState = context.read<AppState>();
    final config = appState.config;
    final token = appState.auth.token;
    if (config == null || token == null) return;

    await appState.pusher.connect(config: config, token: token);
    appState.pusher.watchCustomerTrip(widget.tripId, () {
      if (mounted) _load(silent: true);
    });
    if (mounted) _startPolling();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) setState(() => _loading = true);
    try {
      final trip = await context.read<AppState>().tripService.getDetails(widget.tripId);
      if (!mounted) return;
      setState(() {
        _trip = trip;
        _error = null;
        _loading = false;
      });
      if (!trip.isActive && !trip.needsPayment) _pollTimer?.cancel();
    } catch (e) {
      if (!mounted || silent) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _cancel() async {
    setState(() => _cancelling = true);
    try {
      await context.read<AppState>().tripService.cancel(widget.tripId);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  StatusBadgeTone _tone(String status) {
    return switch (status) {
      'completed' => StatusBadgeTone.success,
      'cancelled' => StatusBadgeTone.error,
      'ongoing' || 'out_for_pickup' || 'accepted' => StatusBadgeTone.warning,
      _ => StatusBadgeTone.neutral,
    };
  }

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final symbol = appState.config?.currencySymbol ?? 'R\$';
    final wsConnected = appState.pusher.isConnected;

    return Scaffold(
      appBar: AppBar(
        title: Text(_trip?.refId.isNotEmpty == true ? 'Corrida #${_trip!.refId}' : 'Acompanhamento'),
        leading: IconButton(icon: const Icon(Icons.arrow_back), onPressed: () => context.go('/home')),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: Center(
              child: StatusBadge(
                wsConnected ? 'Tempo real' : 'Polling',
                tone: wsConnected ? StatusBadgeTone.success : StatusBadgeTone.neutral,
              ),
            ),
          ),
        ],
      ),
      body: _loading && _trip == null
          ? const LoadingOverlay(message: 'Carregando corrida...')
          : _error != null && _trip == null
              ? Center(child: ErrorState(message: _error!, onRetry: _load))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: Align(
                    alignment: Alignment.topCenter,
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 720),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          if (_trip != null) ...[
                            ModernCard(
                              child: Row(
                                children: [
                                  StatusBadge(
                                    tripStatusLabel(_trip!.currentStatus),
                                    tone: _tone(_trip!.currentStatus),
                                  ),
                                  const Spacer(),
                                  if (_trip!.estimatedFare != null)
                                    Text('$symbol ${_trip!.estimatedFare!.toStringAsFixed(2)}',
                                        style: const TextStyle(fontWeight: FontWeight.bold)),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),
                            RideMap(
                              pickup: _trip!.pickup,
                              destination: _trip!.destination,
                              mapsReady: appState.mapsReady,
                            ),
                            const SizedBox(height: 16),
                            ModernCard(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('Origem', style: TextStyle(color: FletiColors.textMuted)),
                                  Text(_trip!.pickupAddress),
                                  const SizedBox(height: 12),
                                  const Text('Destino', style: TextStyle(color: FletiColors.textMuted)),
                                  Text(_trip!.destinationAddress),
                                  if (_trip!.estimatedTime != null) ...[
                                    const SizedBox(height: 12),
                                    Text('Tempo estimado: ${_trip!.estimatedTime}'),
                                  ],
                                ],
                              ),
                            ),
                            if (_trip!.driver != null) ...[
                              const SizedBox(height: 16),
                              DriverInfoCard(driver: _trip!.driver!),
                              if (_trip!.isActive) ...[
                                const SizedBox(height: 8),
                                SecondaryButton(
                                  label: 'Chat com motorista',
                                  onPressed: () => context.go('/ride/${widget.tripId}/chat'),
                                ),
                              ],
                            ] else if (_trip!.isActive) ...[
                              const SizedBox(height: 16),
                              EmptyState(
                                title: 'Procurando motorista',
                                subtitle: wsConnected
                                    ? 'Atualização em tempo real ativa.'
                                    : 'Atualizando automaticamente a cada 8 segundos.',
                              ),
                            ],
                            if (_trip!.needsPayment) ...[
                              const SizedBox(height: 16),
                              PrimaryButton(
                                label: 'Pagar corrida',
                                onPressed: () => context.go('/ride/${widget.tripId}/payment'),
                              ),
                            ],
                            if (_trip!.isCompleted && _trip!.paymentStatus == 'paid') ...[
                              const SizedBox(height: 16),
                              PrimaryButton(
                                label: 'Avaliar motorista',
                                onPressed: () => context.go('/ride/${widget.tripId}/review'),
                              ),
                            ],
                            if (_trip!.isActive) ...[
                              const SizedBox(height: 16),
                              PrimaryButton(
                                label: 'Cancelar corrida',
                                loading: _cancelling,
                                onPressed: _cancelling ? null : _cancel,
                              ),
                            ],
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
    );
  }
}
