import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

/// Shared modern decorations for User App screens (FASE 009).
class FletiModernDecorations {
  FletiModernDecorations._();

  static Color _border(BuildContext context) => FletiDesignTokens.border(context);

  static BoxDecoration card(
    BuildContext context, {
    double? radius,
    Color? color,
  }) {
    return BoxDecoration(
      color: color ?? Theme.of(context).cardColor,
      borderRadius: BorderRadius.circular(radius ?? FletiDesignTokens.radiusMd),
      border: Border.all(color: _border(context)),
    );
  }

  static BoxDecoration sheet(BuildContext context) {
    return BoxDecoration(
      color: Theme.of(context).cardColor,
      borderRadius: const BorderRadius.vertical(
        top: Radius.circular(FletiDesignTokens.radiusXl),
      ),
      border: Border.all(color: _border(context)),
    );
  }

  static BoxDecoration pill(
    BuildContext context, {
    Color? backgroundColor,
  }) {
    return BoxDecoration(
      color: backgroundColor ?? Theme.of(context).colorScheme.primary.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(100),
    );
  }

  static BoxDecoration bodyPanel(BuildContext context) {
    return BoxDecoration(
      borderRadius: const BorderRadius.only(
        topLeft: Radius.circular(Dimensions.radiusExtraLarge),
        topRight: Radius.circular(Dimensions.radiusExtraLarge),
      ),
      color: Theme.of(context).cardColor,
      border: Border(
        top: BorderSide(color: _border(context)),
        left: BorderSide(color: _border(context)),
        right: BorderSide(color: _border(context)),
      ),
    );
  }
}
