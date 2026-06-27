import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/common_widgets/modern/modern_badge.dart';

enum ModernStatusType { success, warning, error, info, neutral }

class ModernStatus extends StatelessWidget {
  final String label;
  final ModernStatusType type;

  const ModernStatus({
    super.key,
    required this.label,
    this.type = ModernStatusType.neutral,
  });

  @override
  Widget build(BuildContext context) {
    final colors = _colors(context);
    return ModernBadge(
      label: label,
      backgroundColor: colors.$1,
      textColor: colors.$2,
    );
  }

  (Color, Color) _colors(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return switch (type) {
      ModernStatusType.success => (
          scheme.tertiary.withValues(alpha: 0.12),
          const Color(0xFF30B877),
        ),
      ModernStatusType.warning => (
          const Color(0xFFFFF4E5),
          const Color(0xFFE67E22),
        ),
      ModernStatusType.error => (
          scheme.error.withValues(alpha: 0.12),
          scheme.error,
        ),
      ModernStatusType.info => (
          const Color(0xFFE8F4FD),
          const Color(0xFF0177CD),
        ),
      ModernStatusType.neutral => (
          Theme.of(context).dividerColor.withValues(alpha: 0.2),
          Theme.of(context).hintColor,
        ),
    };
  }
}
