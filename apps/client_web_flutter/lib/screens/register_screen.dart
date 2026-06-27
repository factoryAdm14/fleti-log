import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirmPassword = TextEditingController();
  bool _loading = false;
  bool _termsAccepted = false;
  bool _privacyAccepted = false;
  bool _locationAccepted = false;
  bool _marketingAccepted = false;

  @override
  void dispose() {
    _firstName.dispose();
    _lastName.dispose();
    _phone.dispose();
    _email.dispose();
    _password.dispose();
    _confirmPassword.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_password.text != _confirmPassword.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('As senhas não coincidem')),
      );
      return;
    }

    if (!_termsAccepted || !_privacyAccepted || !_locationAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Aceite os termos obrigatórios para continuar')),
      );
      return;
    }

    setState(() => _loading = true);
    final ok = await context.read<AppState>().register(
          firstName: _firstName.text.trim(),
          lastName: _lastName.text.trim(),
          phone: _phone.text.trim(),
          password: _password.text,
          email: _email.text.trim(),
          termsAccepted: _termsAccepted,
          privacyAccepted: _privacyAccepted,
          locationConsentAccepted: _locationAccepted,
          marketingConsentAccepted: _marketingAccepted,
        );
    if (!mounted) return;
    setState(() => _loading = false);
    if (ok) context.go('/home');
  }

  @override
  Widget build(BuildContext context) {
    final error = context.watch<AppState>().error;

    return Scaffold(
      appBar: AppBar(title: const Text('Criar conta')),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: ModernCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextField(controller: _firstName, decoration: const InputDecoration(labelText: 'Nome')),
                  const SizedBox(height: 12),
                  TextField(controller: _lastName, decoration: const InputDecoration(labelText: 'Sobrenome')),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _phone,
                    decoration: const InputDecoration(labelText: 'Telefone'),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _email,
                    decoration: const InputDecoration(labelText: 'E-mail (opcional)'),
                    keyboardType: TextInputType.emailAddress,
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _password,
                    decoration: const InputDecoration(labelText: 'Senha'),
                    obscureText: true,
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _confirmPassword,
                    decoration: const InputDecoration(labelText: 'Confirmar senha'),
                    obscureText: true,
                  ),
                  const SizedBox(height: 16),
                  LegalConsentFields(
                    termsAccepted: _termsAccepted,
                    privacyAccepted: _privacyAccepted,
                    locationAccepted: _locationAccepted,
                    marketingAccepted: _marketingAccepted,
                    onTermsChanged: (v) => setState(() => _termsAccepted = v ?? false),
                    onPrivacyChanged: (v) => setState(() => _privacyAccepted = v ?? false),
                    onLocationChanged: (v) => setState(() => _locationAccepted = v ?? false),
                    onMarketingChanged: (v) => setState(() => _marketingAccepted = v ?? false),
                  ),
                  if (error != null) ...[
                    const SizedBox(height: 12),
                    Text(error, style: const TextStyle(color: FletiColors.error)),
                  ],
                  const SizedBox(height: 20),
                  PrimaryButton(label: 'Cadastrar', loading: _loading, onPressed: _submit),
                  const SizedBox(height: 8),
                  SecondaryButton(label: 'Já tenho conta', onPressed: () => context.go('/login')),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
