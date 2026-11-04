import 'package:flutter/material.dart';

import '../../../core/security/biometric_authenticator.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import 'lock_method.dart';

/// Offer to lock the app, shown once after signing in
/// (design: "27 تفعيل البصمة أو الوجه"). A device with a fingerprint or face
/// sensor is offered that, with a PIN as the alternative; a device without one
/// is offered the PIN.
class BiometricSetupScreen extends StatefulWidget {
  const BiometricSetupScreen({
    super.key,
    required this.biometricsAvailable,
    this.kind = BiometricKind.other,
    required this.onEnable,
    required this.onCreatePin,
    required this.onSkip,
  });

  final bool biometricsAvailable;

  /// What the phone has, so the offer names it (Face ID, fingerprint).
  final BiometricKind kind;

  /// Confirms the device owner and turns the lock on. Resolves to false when
  /// they cancel or cannot be confirmed; on true the caller navigates away.
  final Future<bool> Function() onEnable;
  final VoidCallback onCreatePin;
  final VoidCallback onSkip;

  @override
  State<BiometricSetupScreen> createState() => _BiometricSetupScreenState();
}

class _BiometricSetupScreenState extends State<BiometricSetupScreen> {
  bool _busy = false;
  bool _failed = false;

  Future<void> _enable() async {
    setState(() {
      _busy = true;
      _failed = false;
    });
    final enabled = await widget.onEnable();
    if (!mounted) return;
    setState(() {
      _busy = false;
      _failed = !enabled;
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final mutedText = theme.colorScheme.onSurfaceVariant;
    final biometrics = widget.biometricsAvailable;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
            child: CustomScrollView(
              slivers: [
                SliverFillRemaining(
                  hasScrollBody: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(
                        AppSpacing.xl, AppSpacing.sm, AppSpacing.xl, AppSpacing.lg),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Align(
                          alignment: AlignmentDirectional.centerEnd,
                          child: TextButton(
                            style: TextButton.styleFrom(foregroundColor: mutedText),
                            onPressed: _busy ? null : widget.onSkip,
                            child: Text(l10n.skip),
                          ),
                        ),
                        const Spacer(),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [_IconTile(biometrics ? lockMethodIcon(widget.kind) : Icons.pin_outlined)],
                        ),
                        const SizedBox(height: AppSpacing.xl),
                        Text(
                          biometrics ? l10n.biometricSetupTitle : l10n.lockPinOfferTitle,
                          textAlign: TextAlign.center,
                          style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22),
                        ),
                        const SizedBox(height: AppSpacing.md),
                        Text(
                          biometrics
                              ? l10n.biometricSetupBodyWith(lockMethodPhrase(context, widget.kind))
                              : l10n.lockPinOfferBody,
                          textAlign: TextAlign.center,
                          style: theme.textTheme.bodyLarge?.copyWith(color: mutedText),
                        ),
                        const Spacer(),
                        const SizedBox(height: AppSpacing.xl),
                        Card(
                          margin: EdgeInsets.zero,
                          child: Padding(
                            padding: const EdgeInsets.all(AppSpacing.lg),
                            child: Column(
                              children: [
                                _Point(biometrics ? l10n.biometricSetupPoint1 : l10n.lockPinPoint1),
                                const SizedBox(height: AppSpacing.md),
                                _Point(l10n.biometricSetupPoint2),
                                const SizedBox(height: AppSpacing.md),
                                _Point(l10n.biometricSetupPoint3),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: AppSpacing.lg),
                        if (_failed) ...[
                          Semantics(
                            liveRegion: true,
                            child: Text(
                              l10n.biometricSetupFailed,
                              textAlign: TextAlign.center,
                              style: theme.textTheme.bodyMedium
                                  ?.copyWith(color: theme.colorScheme.error),
                            ),
                          ),
                          const SizedBox(height: AppSpacing.md),
                        ],
                        if (biometrics) ...[
                          FilledButton(
                            onPressed: _busy ? null : _enable,
                            child: Text(l10n.biometricSetupEnableWith(lockMethodPhrase(context, widget.kind))),
                          ),
                          const SizedBox(height: AppSpacing.sm),
                          TextButton(
                            style:
                                TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                            onPressed: _busy ? null : widget.onCreatePin,
                            child: Text(l10n.lockUsePinInstead),
                          ),
                        ] else
                          FilledButton(
                            onPressed: widget.onCreatePin,
                            child: Text(l10n.lockPinCreate),
                          ),
                        TextButton(
                          onPressed: _busy ? null : widget.onSkip,
                          child: Text(l10n.biometricSetupNotNow),
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
    );
  }
}

class _IconTile extends StatelessWidget {
  const _IconTile(this.icon);

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 64,
      height: 64,
      decoration: BoxDecoration(
        color: AppColors.navy,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Icon(icon, color: AppColors.goldBright, size: 28),
    );
  }
}

class _Point extends StatelessWidget {
  const _Point(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(top: 2),
          child: Icon(Icons.check, size: 18, color: AppColors.whatsapp),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(child: Text(text, style: Theme.of(context).textTheme.bodyMedium)),
      ],
    );
  }
}
