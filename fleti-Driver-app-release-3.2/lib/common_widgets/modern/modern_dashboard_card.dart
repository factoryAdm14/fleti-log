import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/common_widgets/modern/modern_card.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ModernDashboardCard extends StatelessWidget {
  final String title;
  final String value;
  final String? subtitle;
  final IconData? icon;
  final Color? iconColor;
  final VoidCallback? onTap;

  const ModernDashboardCard({
    super.key,
    required this.title,
    required this.value,
    this.subtitle,
    this.icon,
    this.iconColor,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ModernCard(
      onTap: onTap,
      padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
      child: Row(
        children: [
          if (icon != null)
            Container(
              width: 44,
              height: 44,
              margin: const EdgeInsets.only(right: Dimensions.paddingSizeDefault),
              decoration: BoxDecoration(
                color: (iconColor ?? FletiDesignTokens.primary(context))
                    .withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(FletiDesignTokens.radiusMd),
              ),
              child: Icon(
                icon,
                color: iconColor ?? FletiDesignTokens.primary(context),
                size: Dimensions.iconSizeLarge,
              ),
            ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: textRegular.copyWith(
                    fontSize: Dimensions.fontSizeSmall,
                    color: FletiDesignTokens.onSurfaceMuted(context),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                Text(
                  value,
                  style: textBold.copyWith(fontSize: Dimensions.fontSizeTwenty),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: Dimensions.paddingSizeThree),
                  Text(
                    subtitle!,
                    style: textRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: FletiDesignTokens.onSurfaceMuted(context),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
