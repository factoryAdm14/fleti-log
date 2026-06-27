import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_flutter/shared_flutter.dart';

class DocumentsScreen extends StatelessWidget {
  const DocumentsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Documentos')),
      body: const EmptyState(
        title: 'Envio de documentos',
        subtitle: 'Upload CNH, CRLV e outros via multipart — Fase 3.',
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: SecondaryButton(label: 'Voltar', onPressed: () => context.go('/profile')),
        ),
      ),
    );
  }
}
