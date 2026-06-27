import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';
import '../widgets/withdraw_dialog.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  FinanceWallet? _wallet;
  List<FinanceWalletTransaction> _transactions = [];
  List<WithdrawRequestItem> _pending = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final appState = context.read<AppState>();
    try {
      final wallet = await appState.walletService.getFinanceWallet();
      final txs = await appState.walletService.getFinanceTransactions();
      final pending = await appState.walletService.getPendingWithdrawals();
      if (!mounted) return;
      setState(() {
        _wallet = wallet;
        _transactions = txs;
        _pending = pending;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final wallet = _wallet ?? const FinanceWallet();
    final withdrawable = wallet.withdrawableBalance;

    return ResponsiveShell(
      title: 'Carteira',
      selectedNavIndex: 2,
      onNavSelected: (i) => _nav(context, i),
      navItems: _navItems,
      body: _loading
          ? const LoadingOverlay()
          : Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                ModernCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Saldo disponível', style: TextStyle(color: FletiColors.textMuted)),
                      const SizedBox(height: 8),
                      Text(
                        'R\$ ${withdrawable.toStringAsFixed(2)}',
                        style: Theme.of(context).textTheme.headlineMedium,
                      ),
                      const SizedBox(height: 8),
                      Text('Pendente: R\$ ${wallet.pendingBalance.toStringAsFixed(2)}'),
                      Text('Total recebido: R\$ ${wallet.totalReceived.toStringAsFixed(2)}'),
                      if (wallet.minWithdrawAmount > 0)
                        Text('Saque mínimo: R\$ ${wallet.minWithdrawAmount.toStringAsFixed(2)}'),
                      if (wallet.hasOpenWithdraw) ...[
                        const SizedBox(height: 8),
                        const StatusBadge('Saque em análise', tone: StatusBadgeTone.warning),
                      ],
                      const SizedBox(height: 16),
                      PrimaryButton(
                        label: 'Solicitar saque',
                        onPressed: wallet.hasOpenWithdraw
                            ? null
                            : () async {
                                final ok = await showDialog<bool>(
                                  context: context,
                                  builder: (_) => WithdrawDialog(
                                    maxAmount: withdrawable,
                                    minAmount: wallet.minWithdrawAmount,
                                  ),
                                );
                                if (ok == true) _load();
                              },
                      ),
                      const SizedBox(height: 8),
                      SecondaryButton(
                        label: 'Planos e assinatura',
                        onPressed: () => context.go('/plans'),
                      ),
                    ],
                  ),
                ),
                if (_pending.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text('Saques pendentes', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  ..._pending.map(
                    (w) => Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: ModernCard(
                        child: Row(
                          children: [
                            Text('R\$ ${w.amount.toStringAsFixed(2)}'),
                            const Spacer(),
                            StatusBadge(_statusLabel(w.status), tone: _statusTone(w.status)),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
                const SizedBox(height: 16),
                const Text('Transações recentes', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                if (_transactions.isEmpty)
                  const EmptyState(title: 'Sem transações', subtitle: '')
                else
                  ..._transactions.map(
                    (t) => Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: ModernCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(t.description.isNotEmpty ? t.description : t.type),
                                  if (t.createdAt.isNotEmpty)
                                    Text(
                                      t.createdAt,
                                      style: const TextStyle(fontSize: 12, color: FletiColors.textMuted),
                                    ),
                                ],
                              ),
                            ),
                            Text(
                              '${t.isCredit ? '+' : '-'} R\$ ${t.amount.toStringAsFixed(2)}',
                              style: TextStyle(
                                color: t.isCredit ? FletiColors.success : FletiColors.text,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

String _statusLabel(String status) {
  switch (status.toLowerCase()) {
    case 'approved':
      return 'Aprovado';
    case 'denied':
      return 'Recusado';
    case 'settled':
      return 'Pago';
    default:
      return 'Pendente';
  }
}

StatusBadgeTone _statusTone(String status) {
  switch (status.toLowerCase()) {
    case 'approved':
    case 'settled':
      return StatusBadgeTone.success;
    case 'denied':
      return StatusBadgeTone.error;
    default:
      return StatusBadgeTone.warning;
  }
}

void _nav(BuildContext context, int i) {
  switch (i) {
    case 0:
      context.go('/home');
    case 1:
      context.go('/earnings');
    case 2:
      context.go('/wallet');
    case 3:
      context.go('/profile');
  }
}

const _navItems = [
  NavigationDestination(icon: Icon(Icons.drive_eta_outlined), label: 'Início'),
  NavigationDestination(icon: Icon(Icons.payments_outlined), label: 'Ganhos'),
  NavigationDestination(icon: Icon(Icons.account_balance_wallet_outlined), label: 'Carteira'),
  NavigationDestination(icon: Icon(Icons.person_outline), label: 'Perfil'),
];
