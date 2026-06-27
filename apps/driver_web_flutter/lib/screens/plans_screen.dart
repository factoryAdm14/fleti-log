import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';

class PlansScreen extends StatefulWidget {
  const PlansScreen({super.key});

  @override
  State<PlansScreen> createState() => _PlansScreenState();
}

class _PlansScreenState extends State<PlansScreen> {
  bool _loading = true;
  bool _checkingOut = false;
  String? _error;
  bool _plansEnabled = false;
  String? _activeMode;
  List<DriverPlan> _plans = [];
  DriverSubscription? _subscription;
  DriverSubscription? _pendingSubscription;
  List<FinancePaymentGateway> _gateways = [];
  String? _selectedGateway;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final appState = context.read<AppState>();
    try {
      final plansResult = await appState.planService.getPlans();
      final subResult = await appState.planService.getSubscription();
      final pending = await appState.planService.getPendingSubscription();
      final gateways = await appState.planService.getPaymentGateways();
      if (!mounted) return;
      setState(() {
        _plansEnabled = plansResult.enabled;
        _activeMode = plansResult.activeMode;
        _plans = plansResult.plans;
        _subscription = subResult.subscription;
        _pendingSubscription = pending;
        _gateways = gateways.where((g) => g.supportsPix || g.supportsCard).toList();
        _selectedGateway = _gateways.isNotEmpty ? _gateways.first.key : null;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _checkout(DriverPlan plan) async {
    final gateway = _selectedGateway;
    if (gateway == null || gateway.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nenhum gateway de pagamento disponível.')),
      );
      return;
    }

    setState(() => _checkingOut = true);
    try {
      final url = await context.read<AppState>().planService.checkout(
            planId: plan.id,
            paymentMethod: gateway,
          );
      final uri = Uri.parse(url);
      await launchUrl(uri, webOnlyWindowName: '_blank');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Complete o pagamento na nova aba. O plano será ativado após confirmação.'),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _checkingOut = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ResponsiveShell(
      title: 'Planos',
      navItems: const [],
      body: _loading
          ? const LoadingOverlay()
          : _error != null
              ? ErrorState(message: _error!, onRetry: _load)
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_subscription != null && _subscription!.isActive) ...[
                      ModernCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Plano ativo', style: TextStyle(fontWeight: FontWeight.w600)),
                            const SizedBox(height: 8),
                            Text(
                              _subscription!.plan?.name ?? 'Assinatura',
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            if (_subscription!.daysRemaining > 0)
                              Text('${_subscription!.daysRemaining} dias restantes'),
                            if (_subscription!.expiresAt.isNotEmpty)
                              Text(
                                'Válido até ${_subscription!.expiresAt}',
                                style: const TextStyle(color: FletiColors.textMuted, fontSize: 13),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                    if (_pendingSubscription != null) ...[
                      ModernCard(
                        child: Row(
                          children: [
                            const Expanded(
                              child: Text('Pagamento do plano pendente. Aguarde a confirmação.'),
                            ),
                            StatusBadge('Pendente', tone: StatusBadgeTone.warning),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                    if (!_plansEnabled) ...[
                      const EmptyState(
                        title: 'Planos indisponíveis',
                        subtitle: 'O modo assinatura não está habilitado no sistema.',
                      ),
                    ] else ...[
                      if (_activeMode != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Text(
                            'Modo: $_activeMode',
                            style: const TextStyle(color: FletiColors.textMuted),
                          ),
                        ),
                      if (_gateways.isNotEmpty) ...[
                        DropdownButtonFormField<String>(
                          value: _selectedGateway,
                          decoration: const InputDecoration(labelText: 'Forma de pagamento'),
                          items: _gateways
                              .map((g) => DropdownMenuItem(value: g.key, child: Text(g.name)))
                              .toList(),
                          onChanged: _checkingOut ? null : (v) => setState(() => _selectedGateway = v),
                        ),
                        const SizedBox(height: 16),
                      ],
                      if (_plans.isEmpty)
                        const EmptyState(title: 'Nenhum plano cadastrado', subtitle: '')
                      else
                        ..._plans.map((plan) => Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: ModernCard(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            plan.name,
                                            style: const TextStyle(
                                              fontSize: 18,
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                        StatusBadge(plan.durationLabel, tone: StatusBadgeTone.neutral),
                                      ],
                                    ),
                                    if (plan.description.isNotEmpty) ...[
                                      const SizedBox(height: 8),
                                      Text(plan.description),
                                    ],
                                    const SizedBox(height: 8),
                                    Text(
                                      'R\$ ${plan.price.toStringAsFixed(2)}',
                                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                            color: FletiColors.primary,
                                          ),
                                    ),
                                    if (plan.commissionPercentage > 0)
                                      Text('Comissão: ${plan.commissionPercentage.toStringAsFixed(0)}%'),
                                    if (plan.benefits.isNotEmpty) ...[
                                      const SizedBox(height: 8),
                                      ...plan.benefits.map(
                                        (b) => Padding(
                                          padding: const EdgeInsets.only(bottom: 4),
                                          child: Row(
                                            children: [
                                              const Icon(Icons.check_circle_outline,
                                                  size: 16, color: FletiColors.primary),
                                              const SizedBox(width: 6),
                                              Expanded(child: Text(b)),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                    const SizedBox(height: 12),
                                    PrimaryButton(
                                      label: 'Assinar plano',
                                      loading: _checkingOut,
                                      onPressed: _checkingOut ? null : () => _checkout(plan),
                                    ),
                                  ],
                                ),
                              ),
                            )),
                    ],
                    const SizedBox(height: 16),
                    SecondaryButton(label: 'Voltar à carteira', onPressed: () => context.go('/wallet')),
                  ],
                ),
    );
  }
}
