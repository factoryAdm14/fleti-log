import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_flutter/shared_flutter.dart';

class ForgotPasswordScreen extends StatelessWidget {
  const ForgotPasswordScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Recuperar senha')),
      body: const Center(
        child: EmptyState(
          title: 'Recuperação de senha',
          subtitle: 'Fluxo OTP via /api/customer/auth/send-otp — próxima fase.',
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
