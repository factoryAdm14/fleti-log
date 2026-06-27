import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _loading = true);
    final ok = await context.read<AppState>().login(
          _phoneController.text.trim(),
          _passwordController.text,
        );
    if (!mounted) return;
    setState(() => _loading = false);
    if (ok) context.go('/home');
  }

  @override
  Widget build(BuildContext context) {
    final error = context.watch<AppState>().error;

    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: ModernCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text('Fleti Cliente', style: Theme.of(context).textTheme.headlineSmall),
                  const SizedBox(height: 8),
                  const Text('Entre para solicitar corridas e entregas'),
                  const SizedBox(height: 24),
                  TextField(
                    controller: _phoneController,
                    decoration: const InputDecoration(labelText: 'Telefone ou e-mail'),
                    keyboardType: TextInputType.emailAddress,
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _passwordController,
                    decoration: const InputDecoration(labelText: 'Senha'),
                    obscureText: true,
                  ),
                  if (error != null) ...[
                    const SizedBox(height: 12),
                    Text(error, style: const TextStyle(color: FletiColors.error)),
                  ],
                  const SizedBox(height: 20),
                  PrimaryButton(label: 'Entrar', loading: _loading, onPressed: _submit),
                  const SizedBox(height: 12),
                  SecondaryButton(label: 'Criar conta', onPressed: () => context.go('/register')),
                  TextButton(
                    onPressed: () => context.go('/forgot-password'),
                    child: const Text('Esqueci minha senha'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
