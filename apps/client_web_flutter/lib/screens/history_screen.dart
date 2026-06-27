import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<TripModel> _trips = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final trips = await context.read<AppState>().tripService.list();
      if (!mounted) return;
      setState(() {
        _trips = trips;
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

  @override
  Widget build(BuildContext context) {
    return ResponsiveShell(
      title: 'Histórico',
      selectedNavIndex: 1,
      onNavSelected: (i) {
        if (i == 0) context.go('/home');
        if (i == 1) context.go('/history');
        if (i == 2) context.go('/profile');
      },
      navItems: const [
        NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Início'),
        NavigationDestination(icon: Icon(Icons.history), label: 'Histórico'),
        NavigationDestination(icon: Icon(Icons.person_outline), label: 'Perfil'),
      ],
      body: _loading
          ? const LoadingOverlay()
          : _error != null
              ? ErrorState(message: _error!, onRetry: _load)
              : _trips.isEmpty
                  ? const EmptyState(title: 'Nenhuma corrida ainda', subtitle: 'Suas corridas aparecerão aqui.')
                  : Column(
                      children: _trips
                          .map(
                            (trip) => Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: ModernCard(
                                onTap: () => context.go('/ride/${trip.id}'),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        StatusBadge(tripStatusLabel(trip.currentStatus)),
                                        const Spacer(),
                                        Text('#${trip.refId}', style: const TextStyle(color: FletiColors.textMuted)),
                                      ],
                                    ),
                                    const SizedBox(height: 8),
                                    Text(trip.pickupAddress, maxLines: 1, overflow: TextOverflow.ellipsis),
                                    Text('→ ${trip.destinationAddress}', maxLines: 1, overflow: TextOverflow.ellipsis),
                                  ],
                                ),
                              ),
                            ),
                          )
                          .toList(),
                    ),
    );
  }
}
