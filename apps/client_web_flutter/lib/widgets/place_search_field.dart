import 'dart:async';

import 'package:flutter/material.dart';
import 'package:shared_flutter/shared_flutter.dart';

class PlaceSearchField extends StatefulWidget {
  const PlaceSearchField({
    super.key,
    required this.label,
    required this.locationService,
    required this.onSelected,
    this.initialText = '',
  });

  final String label;
  final LocationService locationService;
  final void Function(GeoPoint point) onSelected;
  final String initialText;

  @override
  State<PlaceSearchField> createState() => _PlaceSearchFieldState();
}

class _PlaceSearchFieldState extends State<PlaceSearchField> {
  final _controller = TextEditingController();
  Timer? _debounce;
  List<PlaceSuggestion> _suggestions = [];
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _controller.text = widget.initialText;
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () async {
      setState(() => _loading = true);
      try {
        final results = await widget.locationService.searchPlaces(value);
        if (!mounted) return;
        setState(() {
          _suggestions = results;
          _loading = false;
        });
      } catch (_) {
        if (mounted) setState(() => _loading = false);
      }
    });
  }

  Future<void> _select(PlaceSuggestion suggestion) async {
    setState(() {
      _loading = true;
      _suggestions = [];
      _controller.text = suggestion.label;
    });
    try {
      final point = await widget.locationService.placeDetails(suggestion.placeId);
      if (point != null) widget.onSelected(point);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextField(
          controller: _controller,
          decoration: InputDecoration(
            labelText: widget.label,
            suffixIcon: _loading ? const Padding(
              padding: EdgeInsets.all(12),
              child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
            ) : const Icon(Icons.search),
          ),
          onChanged: _onChanged,
        ),
        if (_suggestions.isNotEmpty)
          ModernCard(
            padding: EdgeInsets.zero,
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _suggestions.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final item = _suggestions[index];
                return ListTile(
                  dense: true,
                  title: Text(item.label),
                  onTap: () => _select(item),
                );
              },
            ),
          ),
      ],
    );
  }
}
