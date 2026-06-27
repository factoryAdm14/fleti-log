import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';

class PaymentScreen extends StatefulWidget {
  const PaymentScreen({super.key, required this.tripId});

  final String tripId;

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  List<PaymentGateway> _gateways = [];
  String _method = 'cash';
  bool _loading = true;
  bool _paying = false;
  String? _error;
  TripModel? _trip;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final appState = context.read<AppState>();
    try {
      final trip = await appState.tripService.getDetails(widget.tripId);
      final gateways = await appState.paymentService.getGateways();
      if (!mounted) return;
      setState(() {
        _trip = trip;
        _gateways = gateways;
        _method = trip.paymentMethod?.isNotEmpty == true ? trip.paymentMethod! : 'cash';
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

  Future<void> _pay() async {
    setState(() => _paying = true);
    final appState = context.read<AppState>();
    try {
      if (_method == 'digital' || _isGateway(_method)) {
        final gateway = _isGateway(_method) ? _method : (_gateways.isNotEmpty ? _gateways.first.gateway : '');
        final url = appState.paymentService.digitalPaymentUrl(widget.tripId, gateway);
        final uri = Uri.parse(url);
        await launchUrl(uri, webOnlyWindowName: '_blank');
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Complete o pagamento na nova aba. Voltaremos a verificar o status.')),
          );
        }
      } else {
        await appState.paymentService.submitPayment(widget.tripId, _method);
        if (mounted) context.go('/ride/${widget.tripId}');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _paying = false);
    }
  }

  bool _isGateway(String value) => _gateways.any((g) => g.gateway == value);

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final symbol = appState.config?.currencySymbol ?? 'R\$';
    final wallet = appState.user?.walletBalance ?? 0;

    return Scaffold(
      appBar: AppBar(title: const Text('Pagamento')),
      body: _loading
          ? const LoadingOverlay()
          : _error != null
              ? ErrorState(message: _error!, onRetry: _load)
              : Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (_trip != null)
                        ModernCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Corrida #${_trip!.refId}', style: const TextStyle(fontWeight: FontWeight.w600)),
                              const SizedBox(height: 8),
                              if (_trip!.estimatedFare != null)
                                Text(
                                  'Total: $symbol ${_trip!.estimatedFare!.toStringAsFixed(2)}',
                                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                                ),
                            ],
                          ),
                        ),
                      const SizedBox(height: 16),
                      ModernCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Forma de pagamento', style: TextStyle(fontWeight: FontWeight.w600)),
                            const SizedBox(height: 12),
                            RadioListTile<String>(
                              title: const Text('Dinheiro'),
                              value: 'cash',
                              groupValue: _method,
                              onChanged: (v) => setState(() => _method = v!),
                            ),
                            RadioListTile<String>(
                              title: Text('Carteira (saldo: $symbol ${wallet.toStringAsFixed(2)})'),
                              value: 'wallet',
                              groupValue: _method,
                              onChanged: wallet > 0 ? (v) => setState(() => _method = v!) : null,
                            ),
                            ..._gateways.map(
                              (g) => RadioListTile<String>(
                                title: Text(g.title),
                                subtitle: Text(_pixLabel(g.gateway)),
                                value: g.gateway,
                                groupValue: _method,
                                onChanged: (v) => setState(() => _method = v!),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Spacer(),
                      PrimaryButton(label: 'Pagar agora', loading: _paying, onPressed: _pay),
                    ],
                  ),
                ),
    );
  }

  String _pixLabel(String gateway) {
    final lower = gateway.toLowerCase();
    if (lower.contains('pix')) return 'Pix / checkout digital';
    return 'Cartão / checkout digital';
  }
}
