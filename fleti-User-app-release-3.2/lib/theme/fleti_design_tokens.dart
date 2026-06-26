import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

/// Fleti Enterprise v4 — shared visual tokens (FASE 007).
class FletiDesignTokens {
  FletiDesignTokens._();

  static const double radiusSm = Dimensions.radiusSmall;
  static const double radiusMd = Dimensions.radiusDefault;
  static const double radiusLg = Dimensions.radiusLarge;
  static const double radiusXl = Dimensions.radiusExtraLarge;

  static const double spaceXs = Dimensions.paddingSizeExtraSmall;
  static const double spaceSm = Dimensions.paddingSizeSmall;
  static const double spaceMd = Dimensions.paddingSizeDefault;
  static const double spaceLg = Dimensions.paddingSizeLarge;
  static const double spaceXl = Dimensions.paddingSizeExtraLarge;

  static const double borderWidth = 1;
  static const double elevation = 0;

  static Color surface(BuildContext context) =>
      Theme.of(context).colorScheme.surface;

  static Color border(BuildContext context) =>
      Theme.of(context).dividerColor.withValues(alpha: 0.35);

  static Color primary(BuildContext context) =>
      Theme.of(context).colorScheme.primary;

  static Color onSurfaceMuted(BuildContext context) =>
      Theme.of(context).hintColor;
}
