import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  List<CustomerWalletTransaction> _transactions = [];
  List<PaymentGateway> _gateways = [];
  bool _loading = true;
  bool _adding = false;
  final _amountController = TextEditingController(text: '50');

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final appState = context.read<AppState>();
    try {
      await appState.refreshProfile();
      final txs = await appState.walletService.getTransactions();
      final gateways = await appState.walletService.getPaymentGateways();
      if (!mounted) return;
      setState(() {
        _transactions = txs;
        _gateways = gateways;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _addFunds() async {
    final appState = context.read<AppState>();
    final user = appState.user;
    if (user == null) return;

    final amount = double.tryParse(_amountController.text.replaceAll(',', '.')) ?? 0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Informe um valor válido.')),
      );
      return;
    }

    if (_gateways.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nenhum gateway de pagamento ativo no admin.')),
      );
      return;
    }

    final gateway = await showDialog<PaymentGateway>(
      context: context,
      builder: (ctx) => SimpleDialog(
        title: const Text('Forma de recarga'),
        children: _gateways
            .map(
              (g) => SimpleDialogOption(
                onPressed: () => Navigator.pop(ctx, g),
                child: Text(g.title),
              ),
            )
            .toList(),
      ),
    );
    if (gateway == null) return;

    setState(() => _adding = true);
    try {
      final url = appState.walletService.addFundUrl(
        userId: user.id,
        amount: amount,
        paymentMethod: gateway.gateway,
      );
      await launchUrl(Uri.parse(url), webOnlyWindowName: '_blank');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Complete o pagamento na nova aba. O saldo atualiza após confirmação.')),
      );
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final symbol = appState.config?.currencySymbol ?? 'R\$';
    final balance = appState.user?.walletBalance ?? 0;

    return ResponsiveShell(
      title: 'Carteira',
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
      body: _loading
          ? const LoadingOverlay()
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  ModernCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Saldo disponível', style: TextStyle(color: FletiColors.textMuted)),
                        const SizedBox(height: 8),
                        Text(
                          '$symbol ${balance.toStringAsFixed(2)}',
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _amountController,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: InputDecoration(
                            labelText: 'Valor da recarga',
                            prefixText: '$symbol ',
                            border: const OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: 12),
                        PrimaryButton(
                          label: 'Adicionar saldo (Pix/cartão)',
                          loading: _adding,
                          onPressed: _addFunds,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Histórico', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  if (_transactions.isEmpty)
                    const EmptyState(
                      title: 'Sem movimentações',
                      subtitle: 'Recargas e pagamentos aparecerão aqui.',
                    )
                  else
                    ..._transactions.map((tx) {
                      final sign = tx.isCredit ? '+' : '-';
                      final color = tx.isCredit ? FletiColors.success : FletiColors.error;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: ModernCard(
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(tx.attribute, style: const TextStyle(fontWeight: FontWeight.w600)),
                                    if (tx.createdAt.isNotEmpty)
                                      Text(tx.createdAt, style: const TextStyle(color: FletiColors.textMuted, fontSize: 12)),
                                  ],
                                ),
                              ),
                              Text(
                                '$sign$symbol ${tx.amount.toStringAsFixed(2)}',
                                style: TextStyle(color: color, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                ],
              ),
            ),
    );
  }
}
