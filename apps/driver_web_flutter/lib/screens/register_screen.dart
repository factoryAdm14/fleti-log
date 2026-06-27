import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_flutter/shared_flutter.dart';

class RegisterScreen extends StatelessWidget {
  const RegisterScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cadastro motorista')),
      body: const Center(
        child: EmptyState(
          title: 'Cadastro + documentos',
          subtitle: 'POST /api/driver/auth/registration com upload multipart — Fase 3.',
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: SecondaryButton(label: 'Voltar', onPressed: () => context.go('/login')),
        ),
      ),
    );
  }
}
