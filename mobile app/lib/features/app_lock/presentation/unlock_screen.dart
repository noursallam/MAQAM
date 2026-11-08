import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/security/app_lock.dart';
import '../../../core/security/biometric_authenticator.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/maqam_logo.dart';
import '../../../l10n/l10n.dart';
import 'lock_method.dart';
import 'pin_pad.dart';

/// Shown instead of the app while it is locked (design: "28 فتح التطبيق بالبصمة").
class UnlockScreen extends StatefulWidget {
  const UnlockScreen({
    super.key,
    this.name,
    this.phone,
    required this.onUnlock,
    this.usesPin = false,
    this.onPin,
    this.pinAttemptsLeft,
    required this.onUseWhatsapp,
    required this.onDifferentAccount,
    this.kind = BiometricKind.other,
    this.onBrowseAsGuest,
    this.promptOnOpen = true,
  });

  /// Shows the system prompt as soon as the screen opens. Off right after the
  /// customer signed out, so they are not asked straight back in.
  final bool promptOnOpen;

  /// What the phone unlocks with, to name it and show its icon.
  final BiometricKind kind;

  /// Offered when signing back in, so a signed-out customer can still look around.
  final VoidCallback? onBrowseAsGuest;

  final String? name;

  /// Local format, `01XXXXXXXXX`.
  final String? phone;

  /// Shows the system prompt. Resolves to false when cancelled or failed; on
  /// true the caller navigates away.
  final Future<bool> Function() onUnlock;

  /// True when the app is locked with its own PIN instead of biometrics.
  final bool usesPin;

  /// Checks a typed PIN; on success the caller navigates away.
  final Future<PinAttempt> Function(String pin)? onPin;

  /// How many wrong PINs are still allowed, read when the screen opens.
  final Future<int> Function()? pinAttemptsLeft;
  final VoidCallback onUseWhatsapp;
  final VoidCallback onDifferentAccount;

  @override
  State<UnlockScreen> createState() => _UnlockScreenState();
}

class _UnlockScreenState extends State<UnlockScreen> {
  bool _busy = false;
  bool _failed = false;

  /// PIN mode: null until read from storage; zero once the PIN is blocked.
  int? _attemptsLeft;
  int _pinResetToken = 0;

  @override
  void initState() {
    super.initState();
    if (widget.usesPin) {
      widget.pinAttemptsLeft?.call().then((left) {
        if (mounted) setState(() => _attemptsLeft = left);
      });
    } else if (widget.promptOnOpen) {
      // Prompt straight away; the button is there for a second attempt.
      WidgetsBinding.instance.addPostFrameCallback((_) => _unlock());
    }
  }

  Future<void> _enterPin(String pin) async {
    if (_busy) return;
    setState(() => _busy = true);
    final attempt = await widget.onPin!(pin);
    if (!mounted) return;
    setState(() {
      _busy = false;
      _failed = !attempt.unlocked;
      _attemptsLeft = attempt.attemptsLeft;
      _pinResetToken++;
    });
  }

  Future<void> _unlock() async {
    if (_busy || !mounted) return;
    setState(() {
      _busy = true;
      _failed = false;
    });
    final unlocked = await widget.onUnlock();
    if (!mounted) return;
    setState(() {
      _busy = false;
      _failed = !unlocked;
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final name = widget.name?.trim() ?? '';
    final firstName = name.split(RegExp(r'\s+')).first;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: AppColors.navy,
        body: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
              child: CustomScrollView(
                slivers: [
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: AppSpacing.xl, vertical: AppSpacing.lg),
                      child: Column(
                        children: [
                          const SizedBox(height: AppSpacing.xxl),
                          const MaqamLogo(color: Colors.white),
                          const Spacer(),
                          CircleAvatar(
                            radius: 24,
                            backgroundColor: Colors.white.withValues(alpha: 0.1),
                            foregroundColor: AppColors.goldBright,
                            child: name.isEmpty
                                ? const Icon(Icons.person_outline)
                                : Text(name.characters.first, style: const TextStyle(fontSize: 18)),
                          ),
                          const SizedBox(height: AppSpacing.lg),
                          Text(
                            name.isEmpty
                                ? l10n.unlockWelcomeBack
                                : l10n.unlockWelcomeBackName(firstName),
                            textAlign: TextAlign.center,
                            style: textTheme.headlineMedium?.copyWith(
                              color: Colors.white,
                              fontSize: 22,
                            ),
                          ),
                          if (widget.phone != null) ...[
                            const SizedBox(height: AppSpacing.sm),
                            Text(
                              _maskPhone(widget.phone!),
                              textDirection: TextDirection.ltr,
                              style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted),
                            ),
                          ],
                          const Spacer(),
                          if (widget.usesPin) ...[
                            Semantics(
                              liveRegion: _failed,
                              child: Text(
                                _attemptsLeft == 0
                                    ? l10n.pinBlocked
                                    : _failed
                                        ? l10n.pinWrong(_attemptsLeft ?? 0)
                                        : l10n.pinUnlockHint,
                                textAlign: TextAlign.center,
                                style: textTheme.bodyLarge?.copyWith(
                                  color: _failed || _attemptsLeft == 0
                                      ? const Color(0xFFFFB4AB)
                                      : AppColors.onNavyMuted,
                                ),
                              ),
                            ),
                            if (_attemptsLeft != 0) ...[
                              const SizedBox(height: AppSpacing.lg),
                              PinPad(
                                onDark: true,
                                enabled: !_busy,
                                resetToken: _pinResetToken,
                                onCompleted: _enterPin,
                              ),
                            ],
                            const SizedBox(height: AppSpacing.lg),
                          ] else ...[
                            _FingerprintButton(
                              label: l10n.unlockButtonWith(lockMethodPhrase(context, widget.kind)),
                              icon: lockMethodIcon(widget.kind),
                              onPressed: _busy ? null : _unlock,
                            ),
                            const SizedBox(height: AppSpacing.lg),
                            Semantics(
                              liveRegion: _failed,
                              child: Text(
                                _failed
                                    ? l10n.unlockFailed
                                    : widget.kind == BiometricKind.face
                                        ? l10n.unlockHintFace
                                        : l10n.unlockHintFingerprint,
                                textAlign: TextAlign.center,
                                style: textTheme.bodyLarge?.copyWith(color: AppColors.onNavyMuted),
                              ),
                            ),
                            const SizedBox(height: AppSpacing.xxl),
                          ],
                          TextButton(
                            style: TextButton.styleFrom(foregroundColor: AppColors.goldBright),
                            onPressed: widget.onUseWhatsapp,
                            child: Text(l10n.unlockUseWhatsapp),
                          ),
                          TextButton(
                            style: TextButton.styleFrom(
                              foregroundColor: AppColors.onNavyMuted,
                              textStyle: AppTheme.buttonText(fontSize: 14, fontWeight: FontWeight.w500),
                            ),
                            onPressed: widget.onDifferentAccount,
                            child: Text(l10n.unlockDifferentAccount),
                          ),
                          if (widget.onBrowseAsGuest != null)
                            TextButton(
                              style: TextButton.styleFrom(
                                foregroundColor: AppColors.onNavyMuted,
                                textStyle: AppTheme.buttonText(fontSize: 14, fontWeight: FontWeight.w500),
                              ),
                              onPressed: widget.onBrowseAsGuest,
                              child: Text(l10n.unlockBrowseAsGuest),
                            ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// `01012345678` -> `010 •••• 5678`.
String _maskPhone(String phone) {
  if (phone.length != 11) return phone;
  return '${phone.substring(0, 3)} •••• ${phone.substring(7)}';
}

class _FingerprintButton extends StatelessWidget {
  const _FingerprintButton({required this.label, required this.icon, required this.onPressed});

  final String label;
  final IconData icon;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      tooltip: label,
      onPressed: onPressed,
      iconSize: 40,
      style: IconButton.styleFrom(
        fixedSize: const Size.square(88),
        foregroundColor: AppColors.goldBright,
        disabledForegroundColor: AppColors.goldBright.withValues(alpha: 0.5),
        shape: const CircleBorder(side: BorderSide(color: AppColors.goldBright, width: 1.5)),
      ),
      icon: Icon(icon),
    );
  }
}
