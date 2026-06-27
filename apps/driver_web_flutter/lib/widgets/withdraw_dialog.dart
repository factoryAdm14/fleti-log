import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class WithdrawDialog extends StatefulWidget {
  const WithdrawDialog({
    super.key,
    required this.maxAmount,
    this.minAmount = 0,
  });

  final double maxAmount;
  final double minAmount;

  @override
  State<WithdrawDialog> createState() => _WithdrawDialogState();
}

class _WithdrawDialogState extends State<WithdrawDialog> {
  final _amount = TextEditingController();
  final _note = TextEditingController();
  List<WithdrawMethodAccount> _accounts = [];
  WithdrawMethodAccount? _selected;
  bool _loading = true;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _amount.text = widget.maxAmount.toStringAsFixed(2);
    _loadAccounts();
  }

  @override
  void dispose() {
    _amount.dispose();
    _note.dispose();
    super.dispose();
  }

  Future<void> _loadAccounts() async {
    final accounts = await context.read<AppState>().walletService.getWithdrawAccounts();
    if (!mounted) return;
    setState(() {
      _accounts = accounts;
      _selected = accounts.isNotEmpty ? accounts.first : null;
      _loading = false;
    });
  }

  Future<void> _submit() async {
    if (_selected == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cadastre uma conta de saque no app mobile ou admin.')),
      );
      return;
    }
    final amount = double.tryParse(_amount.text.replaceAll(',', '.')) ?? 0;
    if (amount <= 0 || amount > widget.maxAmount) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Valor inválido')),
      );
      return;
    }
    if (widget.minAmount > 0 && amount < widget.minAmount) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Valor mínimo: R\$ ${widget.minAmount.toStringAsFixed(2)}')),
      );
      return;
    }

    setState(() => _submitting = true);
    try {
      await context.read<AppState>().walletService.requestWithdraw(
            withdrawMethodId: _selected!.methodId,
            withdrawMethodInfoId: _selected!.id,
            amount: amount,
            note: _note.text.trim(),
          );
      if (mounted) Navigator.pop(context, true);
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
    return AlertDialog(
      title: const Text('Solicitar saque'),
      content: _loading
          ? const SizedBox(height: 80, child: LoadingOverlay())
          : SizedBox(
              width: 400,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_accounts.isEmpty)
                    const Text('Nenhuma conta cadastrada. Use o app motorista para cadastrar PIX/conta bancária.')
                  else
                    DropdownButtonFormField<WithdrawMethodAccount>(
                      value: _selected,
                      decoration: const InputDecoration(labelText: 'Conta de saque'),
                      items: _accounts
                          .map((a) => DropdownMenuItem(value: a, child: Text(a.methodName)))
                          .toList(),
                      onChanged: (v) => setState(() => _selected = v),
                    ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _amount,
                    decoration: const InputDecoration(labelText: 'Valor (R\$)'),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _note,
                    decoration: const InputDecoration(labelText: 'Observação (opcional)'),
                  ),
                ],
              ),
            ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancelar')),
        PrimaryButton(
          label: 'Confirmar',
          loading: _submitting,
          onPressed: _submitting ? null : _submit,
        ),
      ],
    );
  }
}
