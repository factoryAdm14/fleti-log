import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

/// Applies Fleti Enterprise v4 modern visual tokens to [ThemeData] (FASE 010).
ThemeData applyFletiModernTheme(ThemeData base) {
  final isDark = base.brightness == Brightness.dark;
  final borderColor = isDark
      ? Colors.white.withValues(alpha: 0.12)
      : const Color(0x1A293231);
  final surfaceMuted = isDark ? const Color(0xFF9F9F9F) : const Color(0xFF6B7675);
  final radius = BorderRadius.circular(Dimensions.radiusDefault);

  return base.copyWith(
    shadowColor: Colors.transparent,
    cardTheme: CardThemeData(
      elevation: 0,
      color: base.cardColor,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: radius,
        side: BorderSide(color: borderColor),
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        elevation: 0,
        minimumSize: const Size(0, 48),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: radius),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size(0, 48),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: radius),
        side: BorderSide(color: base.colorScheme.primary),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: base.colorScheme.primary,
        textStyle: const TextStyle(fontWeight: FontWeight.w600),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: base.cardColor,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      border: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: borderColor)),
      enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: borderColor)),
      focusedBorder: OutlineInputBorder(
        borderRadius: radius,
        borderSide: BorderSide(color: base.colorScheme.primary, width: 1.5),
      ),
      hintStyle: TextStyle(color: surfaceMuted, fontWeight: FontWeight.w400),
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: base.cardColor,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(Dimensions.radiusLarge),
        side: BorderSide(color: borderColor),
      ),
    ),
    bottomSheetTheme: BottomSheetThemeData(
      backgroundColor: base.cardColor,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.radiusExtraLarge)),
        side: BorderSide(color: borderColor),
      ),
    ),
    dividerTheme: DividerThemeData(
      color: borderColor,
      thickness: 1,
      space: 1,
    ),
    appBarTheme: AppBarTheme(
      elevation: 0,
      scrolledUnderElevation: 0,
      backgroundColor: base.scaffoldBackgroundColor,
      foregroundColor: Colors.white,
      centerTitle: false,
      titleTextStyle: TextStyle(
        color: Colors.white,
        fontSize: 18,
        fontWeight: FontWeight.w700,
        fontFamily: 'SFProText',
      ),
    ),
    floatingActionButtonTheme: FloatingActionButtonThemeData(
      elevation: 0,
      highlightElevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    ),
    chipTheme: ChipThemeData(
      elevation: 0,
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      labelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
        side: BorderSide(color: borderColor),
      ),
    ),
    listTileTheme: ListTileThemeData(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      shape: RoundedRectangleBorder(borderRadius: radius),
    ),
  );
}
