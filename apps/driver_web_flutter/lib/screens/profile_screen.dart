import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final user = appState.user;

    return ResponsiveShell(
      title: 'Perfil',
      selectedNavIndex: 3,
      onNavSelected: (i) => _nav(context, i),
      navItems: _navItems,
      body: ModernCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(user?.fullName ?? '—', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Text(user?.phone ?? ''),
            const SizedBox(height: 16),
            SecondaryButton(label: 'Documentos', onPressed: () => context.go('/documents')),
            const SizedBox(height: 12),
            SecondaryButton(label: 'Planos e assinatura', onPressed: () => context.go('/plans')),
            const SizedBox(height: 12),
            PrimaryButton(
              label: 'Sair',
              onPressed: () async {
                await appState.logout();
                if (context.mounted) context.go('/login');
              },
            ),
          ],
        ),
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
