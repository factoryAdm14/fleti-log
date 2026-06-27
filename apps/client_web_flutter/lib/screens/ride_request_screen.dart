import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';
import '../widgets/place_search_field.dart';
import '../widgets/ride_map.dart';

class RideRequestScreen extends StatefulWidget {
  const RideRequestScreen({super.key, this.serviceType = 'ride'});

  final String serviceType;

  @override
  State<RideRequestScreen> createState() => _RideRequestScreenState();
}

class _RideRequestScreenState extends State<RideRequestScreen> {
  int _step = 0;
  GeoPoint? _pickup;
  GeoPoint? _destination;
  GeoPoint? _current;
  List<FareOption> _fares = [];
  List<ParcelCategory> _parcelCategories = [];
  int _selectedFare = 0;
  String _paymentMethod = 'cash';
  bool _loading = false;
  String? _error;
  String? _parcelCategoryId;
  final _parcelWeightController = TextEditingController(text: '1');
  final _senderNameController = TextEditingController();
  final _senderPhoneController = TextEditingController();
  final _receiverNameController = TextEditingController();
  final _receiverPhoneController = TextEditingController();

  bool get _isParcel => widget.serviceType == 'parcel';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _prefillParcelContacts());
    _initCurrentLocation();
  }

  @override
  void dispose() {
    _parcelWeightController.dispose();
    _senderNameController.dispose();
    _senderPhoneController.dispose();
    _receiverNameController.dispose();
    _receiverPhoneController.dispose();
    super.dispose();
  }

  void _prefillParcelContacts() {
    final user = context.read<AppState>().user;
    if (user == null) return;
    _senderNameController.text = user.fullName;
    _senderPhoneController.text = user.phone;
  }

  Future<void> _initCurrentLocation() async {
    setState(() => _loading = true);
    final appState = context.read<AppState>();
    final current = await appState.geoLocation.currentPosition();
    if (!mounted) return;
    setState(() {
      _current = current;
      _pickup = current;
      _loading = false;
    });
  }

  Future<void> _estimateFare() async {
    if (_pickup == null || _destination == null || _current == null) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    final appState = context.read<AppState>();
    try {
      final zoneId = await appState.locationService.resolveZoneId(
        _pickup!.latitude,
        _pickup!.longitude,
      );
      if (zoneId != null) await appState.applyZone(zoneId);

      if (_isParcel && _parcelCategories.isEmpty) {
        final categories = await appState.rideService.fetchParcelCategories();
        if (!mounted) return;
        setState(() {
          _parcelCategories = categories;
          _parcelCategoryId ??= categories.isNotEmpty ? categories.first.id : null;
        });
        if (categories.isEmpty) {
          setState(() {
            _error = 'Nenhuma categoria de entrega disponível nesta zona.';
            _loading = false;
          });
          return;
        }
      }

      final parcelWeight = double.tryParse(_parcelWeightController.text.trim()) ?? 1;
      final fares = await appState.rideService.estimateFare(
        pickup: _pickup!,
        destination: _destination!,
        current: _current!,
        type: _isParcel ? 'parcel' : 'ride_request',
        parcelCategoryId: _isParcel ? _parcelCategoryId : null,
        parcelWeight: _isParcel ? parcelWeight : null,
      );

      if (!mounted) return;
      if (fares.isEmpty) {
        setState(() {
          _error = 'Nenhuma tarifa disponível para esta rota.';
          _loading = false;
        });
        return;
      }

      setState(() {
        _fares = fares;
        _selectedFare = 0;
        _step = 1;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _confirmRide() async {
    if (_pickup == null || _destination == null || _current == null || _fares.isEmpty) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    final appState = context.read<AppState>();
    try {
      final tripId = await appState.rideService.createRide(
        pickup: _pickup!,
        destination: _destination!,
        current: _current!,
        fare: _fares[_selectedFare],
        paymentMethod: _paymentMethod,
        type: _isParcel ? 'parcel' : 'ride_request',
        parcelCategoryId: _isParcel ? (_parcelCategoryId ?? _fares[_selectedFare].parcelCategoryId) : null,
        parcelWeight: _isParcel ? (double.tryParse(_parcelWeightController.text.trim()) ?? 1) : null,
        senderName: _isParcel ? _senderNameController.text.trim() : null,
        senderPhone: _isParcel ? _senderPhoneController.text.trim() : null,
        receiverName: _isParcel ? _receiverNameController.text.trim() : null,
        receiverPhone: _isParcel ? _receiverPhoneController.text.trim() : null,
      );
      if (!mounted) return;
      context.go('/ride/$tripId');
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();
    final symbol = appState.config?.currencySymbol ?? 'R\$';

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.serviceType == 'parcel' ? 'Nova entrega' : 'Nova corrida'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => _step > 0 ? setState(() => _step--) : context.go('/home'),
        ),
      ),
      body: _loading
          ? const LoadingOverlay(message: 'Carregando...')
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Align(
                alignment: Alignment.topCenter,
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 720),
                  child: _step == 0 ? _buildAddressStep(appState) : _buildFareStep(symbol),
                ),
              ),
            ),
    );
  }

  Widget _buildAddressStep(AppState appState) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        RideMap(
          pickup: _pickup,
          destination: _destination,
          mapsReady: appState.mapsReady,
        ),
        const SizedBox(height: 16),
        PlaceSearchField(
          label: 'Origem',
          locationService: appState.locationService,
          initialText: _pickup?.address ?? '',
          onSelected: (p) => setState(() => _pickup = p),
        ),
        const SizedBox(height: 12),
        PlaceSearchField(
          label: 'Destino',
          locationService: appState.locationService,
          onSelected: (p) => setState(() => _destination = p),
        ),
        if (_isParcel) ...[
          const SizedBox(height: 16),
          ModernCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text('Dados da entrega', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 12),
                if (_parcelCategories.isNotEmpty)
                  DropdownButtonFormField<String>(
                    value: _parcelCategoryId,
                    decoration: const InputDecoration(labelText: 'Categoria', border: OutlineInputBorder()),
                    items: _parcelCategories
                        .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
                        .toList(),
                    onChanged: (v) => setState(() => _parcelCategoryId = v),
                  ),
                const SizedBox(height: 12),
                TextField(
                  controller: _parcelWeightController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: 'Peso (kg)', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _senderNameController,
                  decoration: const InputDecoration(labelText: 'Remetente', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _senderPhoneController,
                  decoration: const InputDecoration(labelText: 'Telefone remetente', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _receiverNameController,
                  decoration: const InputDecoration(labelText: 'Destinatário', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _receiverPhoneController,
                  decoration: const InputDecoration(labelText: 'Telefone destinatário', border: OutlineInputBorder()),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 12),
        SecondaryButton(
          label: 'Usar minha localização como origem',
          onPressed: () async {
            final current = await appState.geoLocation.currentPosition();
            setState(() {
              _current = current;
              _pickup = current;
            });
          },
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Text(_error!, style: const TextStyle(color: FletiColors.error)),
        ],
        const SizedBox(height: 20),
        PrimaryButton(
          label: 'Ver estimativa',
          onPressed: (_pickup != null && _destination != null && (!_isParcel || _parcelCategoryId != null))
              ? _estimateFare
              : null,
        ),
      ],
    );
  }

  Widget _buildFareStep(String symbol) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        ...List.generate(_fares.length, (index) {
          final fare = _fares[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: ModernCard(
              onTap: () => setState(() => _selectedFare = index),
              child: Row(
                children: [
                  Radio<int>(value: index, groupValue: _selectedFare, onChanged: (v) => setState(() => _selectedFare = v!)),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(fare.vehicleCategoryType, style: const TextStyle(fontWeight: FontWeight.w600)),
                        Text('${fare.estimatedDistance} · ${fare.estimatedDuration}'),
                      ],
                    ),
                  ),
                  Text(
                    '$symbol ${fare.estimatedFare.toStringAsFixed(2)}',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          );
        }),
        ModernCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Pagamento', style: TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(value: 'cash', label: Text('Dinheiro')),
                  ButtonSegment(value: 'wallet', label: Text('Carteira')),
                  ButtonSegment(value: 'digital', label: Text('Pix/Cartão')),
                ],
                selected: {_paymentMethod},
                onSelectionChanged: (s) => setState(() => _paymentMethod = s.first),
              ),
            ],
          ),
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Text(_error!, style: const TextStyle(color: FletiColors.error)),
        ],
        const SizedBox(height: 20),
        PrimaryButton(label: 'Confirmar pedido', onPressed: _confirmRide),
      ],
    );
  }
}
