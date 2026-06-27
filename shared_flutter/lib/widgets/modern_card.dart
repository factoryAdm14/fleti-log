import 'package:flutter/material.dart';

import '../theme/fleti_theme.dart';

class ModernCard extends StatelessWidget {
  const ModernCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(20),
    this.onTap,
  });

  final Widget child;
  final EdgeInsets padding;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final card = Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        color: FletiColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: FletiColors.border),
      ),
      child: child,
    );

    if (onTap == null) return card;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: card,
    );
  }
}
