import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class EarningsScreen extends StatefulWidget {
  const EarningsScreen({super.key});

  @override
  State<EarningsScreen> createState() => _EarningsScreenState();
}

class _EarningsScreenState extends State<EarningsScreen> {
  DailyIncome? _daily;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final daily = await context.read<AppState>().walletService.getDailyIncome();
      if (!mounted) return;
      setState(() {
        _daily = daily;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tripIncome = context.watch<AppState>().user?.tripIncome;

    return ResponsiveShell(
      title: 'Ganhos do dia',
      selectedNavIndex: 1,
      onNavSelected: (i) => _nav(context, i),
      navItems: _navItems,
      body: _loading
          ? const LoadingOverlay()
          : Column(
              children: [
                ModernCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Ganhos hoje', style: TextStyle(color: FletiColors.textMuted)),
                      const SizedBox(height: 8),
                      Text(
                        'R\$ ${(_daily?.income ?? 0).toStringAsFixed(2)}',
                        style: Theme.of(context).textTheme.headlineMedium,
                      ),
                      const SizedBox(height: 8),
                      Text('Corridas: ${_daily?.trips ?? 0}'),
                      if (tripIncome != null) ...[
                        const SizedBox(height: 12),
                        Text('Total acumulado: R\$ ${tripIncome.toStringAsFixed(2)}'),
                      ],
                    ],
                  ),
                ),
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
