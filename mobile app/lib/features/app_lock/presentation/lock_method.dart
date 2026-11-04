import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../../../core/security/biometric_authenticator.dart';
import '../../../l10n/l10n.dart';

/// "with Face ID", "with your fingerprint", "with face unlock": the way this
/// phone names what it unlocks with.
String lockMethodPhrase(BuildContext context, BiometricKind kind) {
  final l10n = context.l10n;
  return switch (kind) {
    BiometricKind.face when defaultTargetPlatform == TargetPlatform.iOS => l10n.lockWithFaceId,
    BiometricKind.face => l10n.lockWithFace,
    _ => l10n.lockWithFingerprint,
  };
}

IconData lockMethodIcon(BiometricKind kind) =>
    kind == BiometricKind.face ? Icons.face_unlock_outlined : Icons.fingerprint;
