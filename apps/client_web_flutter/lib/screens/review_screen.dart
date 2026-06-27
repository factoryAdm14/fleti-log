import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class ReviewScreen extends StatefulWidget {
  const ReviewScreen({super.key, required this.tripId});

  final String tripId;

  @override
  State<ReviewScreen> createState() => _ReviewScreenState();
}

class _ReviewScreenState extends State<ReviewScreen> {
  int _rating = 5;
  final _feedback = TextEditingController();
  bool _loading = true;
  bool _submitting = false;
  bool _alreadySubmitted = false;

  @override
  void initState() {
    super.initState();
    _check();
  }

  @override
  void dispose() {
    _feedback.dispose();
    super.dispose();
  }

  Future<void> _check() async {
    try {
      final submitted = await context.read<AppState>().reviewService.hasSubmitted(widget.tripId);
      if (!mounted) return;
      setState(() {
        _alreadySubmitted = submitted;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    try {
      await context.read<AppState>().reviewService.submit(
            tripId: widget.tripId,
            rating: _rating,
            feedback: _feedback.text.trim(),
          );
      if (mounted) context.go('/home');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Avaliar motorista')),
      body: _loading
          ? const LoadingOverlay()
          : Padding(
              padding: const EdgeInsets.all(20),
              child: Align(
                alignment: Alignment.topCenter,
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 480),
                  child: _alreadySubmitted
                      ? const EmptyState(
                          title: 'Avaliação já enviada',
                          subtitle: 'Obrigado pelo feedback.',
                        )
                      : ModernCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const Text('Como foi sua corrida?', style: TextStyle(fontWeight: FontWeight.w600)),
                              const SizedBox(height: 12),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: List.generate(
                                  5,
                                  (i) => IconButton(
                                    onPressed: () => setState(() => _rating = i + 1),
                                    icon: Icon(
                                      i < _rating ? Icons.star : Icons.star_border,
                                      color: FletiColors.warning,
                                      size: 32,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 12),
                              TextField(
                                controller: _feedback,
                                maxLines: 4,
                                decoration: const InputDecoration(
                                  labelText: 'Comentário (opcional)',
                                  border: OutlineInputBorder(),
                                ),
                              ),
                              const SizedBox(height: 16),
                              PrimaryButton(
                                label: 'Enviar avaliação',
                                loading: _submitting,
                                onPressed: _submitting ? null : _submit,
                              ),
                              const SizedBox(height: 8),
                              SecondaryButton(
                                label: 'Pular',
                                onPressed: () => context.go('/home'),
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
