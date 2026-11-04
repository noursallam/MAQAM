import 'package:flutter/material.dart';

/// Brand palette, sampled from the design files in `docs/MAQAM Mobile App-png`.
abstract final class AppColors {
  static const navy = Color(0xFF161B29);
  // Panels on a navy screen: the wheel's dark slices, the merchant code box.
  static const navyRaised = Color(0xFF232839);
  // The scanner's camera backdrop, a shade deeper than [navy].
  static const navyDeep = Color(0xFF0F131D);

  // The lucky wheel, sampled from the designer's file ("عجلة الحظ.ai").
  static const wheelRim = Color(0xFF1D2534);
  static const wheelRimShade = Color(0xFF151A27);
  static const wheelSlice = Color(0xFF2A3444);
  static const wheelSliceShade = Color(0xFF1D2433);
  static const wheelEdge = Color(0xFFCFA339);
  static const wheelEdgeLight = Color(0xFFF0D47A);
  static const wheelEdgeDark = Color(0xFFBB871A);
  static const wheelStud = Color(0xFFEBCB35);
  static const wheelGoldSlice = Color(0xFFBF9E5E);
  static const wheelGoldSliceLight = Color(0xFFD6B577);
  static const wheelPointer = Color(0xFFD0AB6E);
  static const gold = Color(0xFFC5A059);
  static const goldBright = Color(0xFFDCB479);
  // Darker gold for text and icons on light surfaces, where [gold] fails contrast.
  static const goldDeep = Color(0xFF8A6A2B);
  static const background = Color(0xFFF7F6F2);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceMuted = Color(0xFFEFEDE7);
  static const border = Color(0xFFE5E2DA);
  static const textPrimary = navy;
  static const textSecondary = Color(0xFF5F6368);
  static const onNavyMuted = Color(0xFFB4B9C6);
  static const whatsapp = Color(0xFF17753F);
  static const error = Color(0xFFB3261E);
  // Tinted backgrounds behind a warning (delete account, suspended) and a pending badge.
  static const errorSoft = Color(0xFFF5E1E0);
  static const goldSoft = Color(0xFFF3E7D2);
  static const success = Color(0xFF5FCB8A);

  // Leaderboard podium metals, light to dark.
  static const medalGoldLight = Color(0xFFF3D98B);
  static const medalGoldDark = Color(0xFFB8893A);
  static const medalSilverLight = Color(0xFFEEF1F5);
  static const medalSilverDark = Color(0xFF8E97A6);
  static const medalBronzeLight = Color(0xFFEDB48A);
  static const medalBronzeDark = Color(0xFF94542D);
}
