import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';

class TripDetailScreen extends StatefulWidget {
  const TripDetailScreen({super.key, required this.tripId});

  final String tripId;

  @override
  State<TripDetailScreen> createState() => _TripDetailScreenState();
}

class _TripDetailScreenState extends State<TripDetailScreen> {
  TripModel? _trip;
  bool _loading = true;
  bool _updating = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final trip = await context.read<AppState>().loadTrip(widget.tripId);
      if (!mounted) return;
      setState(() {
        _trip = trip;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _updateStatus(String status) async {
    setState(() => _updating = true);
    try {
      await context.read<AppState>().driverRideService.updateStatus(widget.tripId, status);
      await _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _updating = false);
    }
  }

  Future<void> _openNavigation(GeoPoint point) async {
    final origin = _trip?.pickup;
    final url = googleMapsDirectionsUrl(destination: point, origin: origin);
    await launchUrl(Uri.parse(url), webOnlyWindowName: '_blank');
  }

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final trip = _trip;
    final status = trip?.currentStatus ?? '';

    return Scaffold(
      appBar: AppBar(title: Text(trip?.refId.isNotEmpty == true ? 'Corrida #${trip!.refId}' : 'Detalhes da viagem')),
      body: _loading
          ? const LoadingOverlay()
          : _error != null
              ? ErrorState(message: _error!, onRetry: _load)
              : trip == null
                  ? const EmptyState(title: 'Corrida não encontrada', subtitle: '')
                  : SingleChildScrollView(
                      padding: const EdgeInsets.all(20),
                      child: Align(
                        alignment: Alignment.topCenter,
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(maxWidth: 720),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              StatusBadge(tripStatusLabel(status)),
                              const SizedBox(height: 16),
                              TripMap(
                                pickup: trip.pickup,
                                destination: trip.destination,
                                mapsReady: appState.mapsReady,
                              ),
                              const SizedBox(height: 16),
                              ModernCard(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text('Coleta: ${trip.pickupAddress}'),
                                    if (trip.pickup != null) ...[
                                      const SizedBox(height: 8),
                                      SecondaryButton(
                                        label: 'Navegar até coleta',
                                        onPressed: () => _openNavigation(trip.pickup!),
                                      ),
                                    ],
                                    const SizedBox(height: 12),
                                    Text('Destino: ${trip.destinationAddress}'),
                                    if (trip.destination != null) ...[
                                      const SizedBox(height: 8),
                                      SecondaryButton(
                                        label: 'Navegar até destino',
                                        onPressed: () => _openNavigation(trip.destination!),
                                      ),
                                    ],
                                    if (trip.customer != null) ...[
                                      const SizedBox(height: 12),
                                      Text('Cliente: ${trip.customer!.fullName}'),
                                      if (trip.customer!.phone != null) Text(trip.customer!.phone!),
                                    ],
                                  ],
                                ),
                              ),
                              if (trip.customer != null && trip.isActive) ...[
                                const SizedBox(height: 12),
                                SecondaryButton(
                                  label: 'Chat com cliente',
                                  onPressed: () => context.go('/trip/${widget.tripId}/chat'),
                                ),
                              ],
                              const SizedBox(height: 16),
                              if (status == 'accepted')
                                PrimaryButton(
                                  label: 'Sair para coleta',
                                  loading: _updating,
                                  onPressed: _updating ? null : () => _updateStatus('out_for_pickup'),
                                ),
                              if (status == 'out_for_pickup')
                                PrimaryButton(
                                  label: 'Iniciar corrida',
                                  loading: _updating,
                                  onPressed: _updating ? null : () => _updateStatus('ongoing'),
                                ),
                              if (status == 'accepted' || status == 'ongoing' || status == 'out_for_pickup')
                                Padding(
                                  padding: const EdgeInsets.only(top: 8),
                                  child: PrimaryButton(
                                    label: 'Finalizar corrida',
                                    loading: _updating,
                                    onPressed: _updating ? null : () => _updateStatus('completed'),
                                  ),
                                ),
                              const SizedBox(height: 8),
                              SecondaryButton(label: 'Voltar', onPressed: () => context.go('/home')),
                            ],
                          ),
                        ),
                      ),
                    ),
    );
  }
}
