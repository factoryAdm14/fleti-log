import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ModernChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback? onTap;
  final IconData? icon;

  const ModernChip({
    super.key,
    required this.label,
    this.selected = false,
    this.onTap,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    final primary = FletiDesignTokens.primary(context);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(FletiDesignTokens.radiusLg),
        child: Container(
          padding: const EdgeInsets.symmetric(
            horizontal: Dimensions.paddingSizeDefault,
            vertical: Dimensions.paddingSizeExtraSmall,
          ),
          decoration: BoxDecoration(
            color: selected ? primary.withValues(alpha: 0.12) : FletiDesignTokens.surface(context),
            borderRadius: BorderRadius.circular(FletiDesignTokens.radiusLg),
            border: Border.all(
              color: selected ? primary : FletiDesignTokens.border(context),
            ),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (icon != null) ...[
                Icon(icon, size: Dimensions.iconSizeSmall, color: selected ? primary : null),
                const SizedBox(width: Dimensions.paddingSizeExtraSmall),
              ],
              Text(
                label,
                style: textMedium.copyWith(
                  fontSize: Dimensions.fontSizeSmall,
                  color: selected ? primary : null,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
