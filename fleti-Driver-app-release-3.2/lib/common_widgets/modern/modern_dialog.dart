import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/common_widgets/modern/modern_button.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ModernDialog {
  ModernDialog._();

  static Future<bool?> confirm({
    required BuildContext context,
    required String title,
    String? message,
    String confirmLabel = 'Confirmar',
    String cancelLabel = 'Cancelar',
  }) {
    return showDialog<bool>(
      context: context,
      builder: (context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(FletiDesignTokens.radiusLg),
          ),
          child: Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(title, style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
                if (message != null) ...[
                  const SizedBox(height: Dimensions.paddingSizeSmall),
                  Text(
                    message,
                    style: textRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: FletiDesignTokens.onSurfaceMuted(context),
                    ),
                  ),
                ],
                const SizedBox(height: Dimensions.paddingSizeLarge),
                Row(
                  children: [
                    Expanded(
                      child: ModernButton(
                        label: cancelLabel,
                        variant: ModernButtonVariant.outlined,
                        onPressed: () => Navigator.of(context).pop(false),
                      ),
                    ),
                    const SizedBox(width: Dimensions.paddingSizeSmall),
                    Expanded(
                      child: ModernButton(
                        label: confirmLabel,
                        onPressed: () => Navigator.of(context).pop(true),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
