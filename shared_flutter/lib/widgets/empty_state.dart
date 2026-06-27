import 'package:flutter/material.dart';

import '../theme/fleti_theme.dart';
import 'modern_card.dart';

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.title,
    this.subtitle,
    this.action,
  });

  final String title;
  final String? subtitle;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return ModernCard(
      child: Column(
        children: [
          Icon(Icons.inbox_outlined, size: 48, color: FletiColors.textMuted.withValues(alpha: 0.6)),
          const SizedBox(height: 12),
          Text(title, style: Theme.of(context).textTheme.titleMedium),
          if (subtitle != null) ...[
            const SizedBox(height: 8),
            Text(subtitle!, textAlign: TextAlign.center, style: const TextStyle(color: FletiColors.textMuted)),
          ],
          if (action != null) ...[const SizedBox(height: 16), action!],
        ],
      ),
    );
  }
}
