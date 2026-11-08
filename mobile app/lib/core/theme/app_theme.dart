import 'package:flutter/material.dart';

import 'app_colors.dart';
import 'app_spacing.dart';

abstract final class AppTheme {
  /// Bundled in `assets/fonts`; the same typeface as the website.
  static const fontFamily = 'IBM Plex Sans Arabic';

  /// A button's text style replaces the theme's instead of inheriting from it,
  /// so every button style must name the font itself. Use this for all of them.
  static TextStyle buttonText({required double fontSize, required FontWeight fontWeight}) =>
      TextStyle(fontFamily: fontFamily, fontSize: fontSize, fontWeight: fontWeight);

  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.gold,
      surface: AppColors.background,
    ).copyWith(
      primary: AppColors.navy,
      onPrimary: Colors.white,
      secondary: AppColors.goldDeep,
      onSurface: AppColors.textPrimary,
      onSurfaceVariant: AppColors.textSecondary,
      outline: AppColors.navy,
      outlineVariant: AppColors.border,
      surfaceContainerHighest: AppColors.surfaceMuted,
      error: AppColors.error,
    );

    OutlineInputBorder inputBorder(Color color, [double width = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radius),
          borderSide: BorderSide(color: color, width: width),
        );

    return ThemeData(
      fontFamily: fontFamily,
      colorScheme: scheme,
      scaffoldBackgroundColor: AppColors.background,
      textTheme: const TextTheme(
        headlineMedium: TextStyle(fontSize: 28, fontWeight: FontWeight.w700, height: 1.3),
        titleMedium: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
        bodyLarge: TextStyle(fontSize: 16, height: 1.6),
        bodyMedium: TextStyle(fontSize: 14, height: 1.5),
        bodySmall: TextStyle(fontSize: 13, height: 1.5),
      ).apply(bodyColor: AppColors.textPrimary, displayColor: AppColors.textPrimary),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.background,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: true,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(AppSpacing.controlHeight),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.radius)),
          textStyle: buttonText(fontSize: 17, fontWeight: FontWeight.w700),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.textPrimary,
          minimumSize: const Size(48, 48),
          textStyle: buttonText(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: 18),
        hintStyle: const TextStyle(color: AppColors.textSecondary),
        border: inputBorder(AppColors.navy),
        enabledBorder: inputBorder(AppColors.navy),
        focusedBorder: inputBorder(AppColors.gold, 2),
        errorBorder: inputBorder(AppColors.error),
        focusedErrorBorder: inputBorder(AppColors.error, 2),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radius),
          side: const BorderSide(color: AppColors.border),
        ),
      ),
      chipTheme: const ChipThemeData(showCheckmark: false),
    );
  }
}
