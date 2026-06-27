import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ModernBadge extends StatelessWidget {
  final String label;
  final Color? backgroundColor;
  final Color? textColor;

  const ModernBadge({
    super.key,
    required this.label,
    this.backgroundColor,
    this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    final bg = backgroundColor ??
        FletiDesignTokens.primary(context).withValues(alpha: 0.12);
    final fg = textColor ?? FletiDesignTokens.primary(context);

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSizeSmall,
        vertical: Dimensions.paddingSizeThree,
      ),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(FletiDesignTokens.radiusSm),
      ),
      child: Text(
        label,
        style: textMedium.copyWith(
          fontSize: Dimensions.fontSizeSmall,
          color: fg,
        ),
      ),
    );
  }
}
