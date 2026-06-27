import 'package:flutter/material.dart';

import '../theme/fleti_theme.dart';

enum StatusBadgeTone { success, warning, error, neutral }

class StatusBadge extends StatelessWidget {
  const StatusBadge(this.label, {super.key, this.tone = StatusBadgeTone.neutral});

  final String label;
  final StatusBadgeTone tone;

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = switch (tone) {
      StatusBadgeTone.success => (FletiColors.success.withValues(alpha: 0.12), FletiColors.success),
      StatusBadgeTone.warning => (FletiColors.warning.withValues(alpha: 0.15), FletiColors.warning),
      StatusBadgeTone.error => (FletiColors.error.withValues(alpha: 0.12), FletiColors.error),
      StatusBadgeTone.neutral => (FletiColors.border, FletiColors.textMuted),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(label, style: TextStyle(color: fg, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}
