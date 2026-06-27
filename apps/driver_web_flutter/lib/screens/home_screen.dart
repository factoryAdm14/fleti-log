import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final wsConnected = appState.pusher.isConnected;

    return ResponsiveShell(
      title: 'Painel motorista',
      selectedNavIndex: 0,
      onNavSelected: (i) => _nav(context, i),
      navItems: _navItems,
      actions: [
        if (appState.isOnline)
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: StatusBadge(
              wsConnected ? 'Tempo real' : 'Polling',
              tone: wsConnected ? StatusBadgeTone.success : StatusBadgeTone.neutral,
            ),
          ),
      ],
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ModernCard(
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Status', style: TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 8),
                      StatusBadge(
                        appState.isOnline ? 'Online' : 'Offline',
                        tone: appState.isOnline ? StatusBadgeTone.success : StatusBadgeTone.neutral,
                      ),
                    ],
                  ),
                ),
                Switch(
                  value: appState.isOnline,
                  onChanged: (v) async {
                    try {
                      await appState.setOnline(v);
                    } catch (e) {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
                      }
                    }
                  },
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          if (!appState.isOnline)
            const EmptyState(
              title: 'Você está offline',
              subtitle: 'Ative o modo online para receber chamadas.',
            )
          else if (appState.pendingRides.isEmpty)
            const EmptyState(
              title: 'Nenhuma chamada',
              subtitle: 'Atualizando automaticamente a cada 6 segundos.',
            )
          else
            ...appState.pendingRides.map((trip) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: ModernCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Nova solicitação #${trip.refId}', style: const TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 8),
                        Text(trip.pickupAddress, maxLines: 1, overflow: TextOverflow.ellipsis),
                        Text('→ ${trip.destinationAddress}', maxLines: 1, overflow: TextOverflow.ellipsis),
                        if (trip.estimatedFare != null)
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text('Tarifa: R\$ ${trip.estimatedFare!.toStringAsFixed(2)}'),
                          ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: PrimaryButton(
                                label: 'Aceitar',
                                onPressed: () async {
                                  await appState.acceptTrip(trip.id);
                                  if (context.mounted) context.go('/trip/${trip.id}');
                                },
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: SecondaryButton(
                                label: 'Recusar',
                                onPressed: () => appState.rejectTrip(trip.id),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                )),
        ],
      ),
    );
  }
}

void _nav(BuildContext context, int i) {
  switch (i) {
    case 0:
      context.go('/home');
    case 1:
      context.go('/earnings');
    case 2:
      context.go('/wallet');
    case 3:
      context.go('/profile');
  }
}

const _navItems = [
  NavigationDestination(icon: Icon(Icons.drive_eta_outlined), label: 'Início'),
  NavigationDestination(icon: Icon(Icons.payments_outlined), label: 'Ganhos'),
  NavigationDestination(icon: Icon(Icons.account_balance_wallet_outlined), label: 'Carteira'),
  NavigationDestination(icon: Icon(Icons.person_outline), label: 'Perfil'),
];
