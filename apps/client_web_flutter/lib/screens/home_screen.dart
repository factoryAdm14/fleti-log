import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AppState>().user;

    return ResponsiveShell(
      title: 'Início',
      selectedNavIndex: 0,
      onNavSelected: (i) {
        switch (i) {
          case 0:
            context.go('/home');
          case 1:
            context.go('/history');
          case 2:
            context.go('/profile');
        }
      },
      navItems: const [
        NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Início'),
        NavigationDestination(icon: Icon(Icons.history), label: 'Histórico'),
        NavigationDestination(icon: Icon(Icons.person_outline), label: 'Perfil'),
      ],
      actions: [
        if (user != null)
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: Center(child: Text('Olá, ${user.firstName}')),
          ),
      ],
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ModernCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Solicitar serviço', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                const Text('Escolha origem e destino no mapa — integração na Fase 4.'),
                const SizedBox(height: 16),
                PrimaryButton(label: 'Nova corrida', onPressed: () => context.go('/ride/new')),
                const SizedBox(height: 8),
                SecondaryButton(label: 'Nova entrega', onPressed: () => context.go('/ride/new?type=parcel')),
              ],
            ),
          ),
          const SizedBox(height: 16),
          const EmptyState(
            title: 'Nenhuma corrida ativa',
            subtitle: 'Quando você solicitar, o acompanhamento em tempo real aparecerá aqui.',
          ),
        ],
      ),
    );
  }
}
