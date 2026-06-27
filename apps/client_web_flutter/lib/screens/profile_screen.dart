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
      selectedNavIndex: 2,
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
      body: ModernCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(user?.fullName ?? '—', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Text(user?.phone ?? ''),
            if (user?.walletBalance != null) ...[
              const SizedBox(height: 16),
              Text('Carteira: R\$ ${user!.walletBalance!.toStringAsFixed(2)}'),
              const SizedBox(height: 12),
              SecondaryButton(
                label: 'Gerenciar carteira',
                onPressed: () => context.go('/wallet'),
              ),
            ],
            const SizedBox(height: 24),
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
