import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import '../app_state.dart';

class TripChatScreenWrapper extends StatefulWidget {
  const TripChatScreenWrapper({super.key, required this.tripId});

  final String tripId;

  @override
  State<TripChatScreenWrapper> createState() => _TripChatScreenWrapperState();
}

class _TripChatScreenWrapperState extends State<TripChatScreenWrapper> {
  TripModel? _trip;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final trip = await context.read<AppState>().tripService.getDetails(widget.tripId);
      if (!mounted) return;
      setState(() {
        _trip = trip;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Scaffold(body: LoadingOverlay());
    final trip = _trip;
    final driver = trip?.driver;
    final user = context.read<AppState>().user;
    if (trip == null || driver == null || user == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Chat')),
        body: const EmptyState(title: 'Chat indisponível', subtitle: 'Aguarde o motorista aceitar.'),
      );
    }

    return TripChatScreen(
      tripId: widget.tripId,
      otherUserId: driver.id,
      otherUserName: driver.fullName,
      chatService: context.read<AppState>().chatService,
      currentUserId: user.id,
    );
  }
}
