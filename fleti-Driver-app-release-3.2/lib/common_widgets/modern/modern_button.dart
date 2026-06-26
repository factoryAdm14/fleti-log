import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

enum ModernButtonVariant { filled, outlined, text }

class ModernButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final ModernButtonVariant variant;
  final IconData? icon;
  final bool isLoading;
  final double? width;
  final double height;

  const ModernButton({
    super.key,
    required this.label,
    this.onPressed,
    this.variant = ModernButtonVariant.filled,
    this.icon,
    this.isLoading = false,
    this.width,
    this.height = 48,
  });

  @override
  Widget build(BuildContext context) {
    final primary = FletiDesignTokens.primary(context);
    final disabled = onPressed == null || isLoading;

    final Widget labelWidget = isLoading
        ? SizedBox(
            height: 20,
            width: 20,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              color: variant == ModernButtonVariant.filled
                  ? Colors.white
                  : primary,
            ),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (icon != null) ...[
                Icon(icon, size: Dimensions.iconSizeMedium),
                const SizedBox(width: Dimensions.paddingSizeExtraSmall),
              ],
              Flexible(
                child: Text(
                  label,
                  textAlign: TextAlign.center,
                  overflow: TextOverflow.ellipsis,
                  style: textSemiBold.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: _textColor(context),
                  ),
                ),
              ),
            ],
          );

    final shape = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(FletiDesignTokens.radiusMd),
    );

    final buttonStyle = switch (variant) {
      ModernButtonVariant.filled => ElevatedButton.styleFrom(
          elevation: FletiDesignTokens.elevation,
          backgroundColor: disabled ? Theme.of(context).disabledColor : primary,
          foregroundColor: Colors.white,
          minimumSize: Size(width ?? double.infinity, height),
          shape: shape,
        ),
      ModernButtonVariant.outlined => OutlinedButton.styleFrom(
          foregroundColor: primary,
          minimumSize: Size(width ?? double.infinity, height),
          side: BorderSide(color: disabled ? Theme.of(context).disabledColor : primary),
          shape: shape,
        ),
      ModernButtonVariant.text => TextButton.styleFrom(
          foregroundColor: primary,
          minimumSize: Size(width ?? double.infinity, height),
          shape: shape,
        ),
    };

    return switch (variant) {
      ModernButtonVariant.filled => ElevatedButton(
          onPressed: disabled ? null : onPressed,
          style: buttonStyle,
          child: labelWidget,
        ),
      ModernButtonVariant.outlined => OutlinedButton(
          onPressed: disabled ? null : onPressed,
          style: buttonStyle,
          child: labelWidget,
        ),
      ModernButtonVariant.text => TextButton(
          onPressed: disabled ? null : onPressed,
          style: buttonStyle,
          child: labelWidget,
        ),
    };
  }

  Color _textColor(BuildContext context) {
    if (variant == ModernButtonVariant.filled) {
      return Colors.white;
    }
    return FletiDesignTokens.primary(context);
  }
}
