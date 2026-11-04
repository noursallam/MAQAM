import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/maqam_logo.dart';
import '../../../l10n/l10n.dart';
import '../domain/egyptian_phone.dart';
import 'widgets/auth_step_scaffold.dart';

/// Phone entry, step 1 of sign-in (design: "02 تسجيل الدخول"). The same step
/// asks a signed-in customer for the new number when they change it.
class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    required this.onContinue,
    this.onToggleLanguage,
    this.onOpenTerms,
    this.onOpenPrivacy,
    this.changingPhone = false,
  });

  /// Called with the normalized phone number (`01XXXXXXXXX`). It looks up how
  /// the number signs in and moves on, or throws an [ApiException].
  final Future<void> Function(String phone) onContinue;
  final VoidCallback? onToggleLanguage;
  final VoidCallback? onOpenTerms;
  final VoidCallback? onOpenPrivacy;

  /// Asks for the account's new number instead of the number to sign in with.
  final bool changingPhone;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phone = TextEditingController();
  _PhoneError? _error;
  ApiException? _failure;
  bool _busy = false;

  @override
  void dispose() {
    _phone.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_busy) return;
    final phone = normalizeEgyptianPhone(_phone.text);
    if (phone == null) {
      setState(() {
        _failure = null;
        _error = _phone.text.trim().isEmpty ? _PhoneError.required : _PhoneError.invalid;
      });
      return;
    }
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = true;
      _failure = null;
    });
    try {
      await widget.onContinue(phone);
    } on ApiException catch (e) {
      if (mounted) setState(() => _failure = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final secondaryText = theme.colorScheme.onSurfaceVariant;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      IconButton(
                        tooltip: l10n.back,
                        onPressed: () => Navigator.maybePop(context),
                        icon: const Icon(Icons.arrow_back),
                      ),
                      if (widget.onToggleLanguage != null)
                        TextButton(
                          onPressed: widget.onToggleLanguage,
                          child: Text(l10n.switchLanguage),
                        ),
                    ],
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: AppSpacing.xl),
                        if (!widget.changingPhone) ...[
                          const MaqamLogo(),
                          const SizedBox(height: AppSpacing.xxl),
                        ],
                        Text(
                          widget.changingPhone ? l10n.changePhoneTitle : l10n.loginTitle,
                          style: theme.textTheme.headlineMedium,
                        ),
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          widget.changingPhone ? l10n.changePhoneSubtitle : l10n.loginSubtitle,
                          style: theme.textTheme.bodyLarge?.copyWith(color: secondaryText),
                        ),
                        const SizedBox(height: AppSpacing.xxl),
                        Text(
                          widget.changingPhone ? l10n.changePhoneLabel : l10n.loginPhoneLabel,
                          style: theme.textTheme.titleMedium,
                        ),
                        const SizedBox(height: AppSpacing.sm),
                        _PhoneField(
                          controller: _phone,
                          label: l10n.loginPhoneLabel,
                          hasError: _error != null,
                          onChanged: (_) {
                            if (_error != null || _failure != null) {
                              setState(() {
                                _error = null;
                                _failure = null;
                              });
                            }
                          },
                          onSubmitted: _submit,
                        ),
                        if (_error != null || _failure != null) ...[
                          const SizedBox(height: AppSpacing.sm),
                          Semantics(
                            liveRegion: true,
                            child: Text(
                              switch (_error) {
                                _PhoneError.required => l10n.loginPhoneRequired,
                                _PhoneError.invalid => l10n.loginPhoneInvalid,
                                null => apiErrorMessage(context, _failure!),
                              },
                              style: theme.textTheme.bodySmall
                                  ?.copyWith(color: theme.colorScheme.error),
                            ),
                          ),
                        ],
                        const SizedBox(height: AppSpacing.lg),
                        FilledButton(
                          onPressed: _busy ? null : _submit,
                          child: _busy ? const ButtonSpinner() : Text(l10n.loginContinue),
                        ),
                        const SizedBox(height: AppSpacing.lg),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.verified_user_outlined,
                                size: 20, color: AppColors.goldDeep),
                            const SizedBox(width: AppSpacing.sm),
                            Expanded(
                              child: Text(
                                l10n.loginNoSpam,
                                style: theme.textTheme.bodyMedium?.copyWith(color: secondaryText),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppSpacing.xl),
                      ],
                    ),
                  ),
                ),
                if (!widget.changingPhone)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(
                        AppSpacing.xl, AppSpacing.sm, AppSpacing.xl, AppSpacing.lg),
                    child: _LegalNotice(
                      onOpenTerms: widget.onOpenTerms,
                      onOpenPrivacy: widget.onOpenPrivacy,
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

// Kept as a kind, not a string, so the message follows a language switch.
enum _PhoneError { required, invalid }

class _PhoneField extends StatelessWidget {
  const _PhoneField({
    required this.controller,
    required this.label,
    required this.hasError,
    required this.onChanged,
    required this.onSubmitted,
  });

  final TextEditingController controller;
  final String label;
  final bool hasError;
  final ValueChanged<String> onChanged;
  final VoidCallback onSubmitted;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final inputTheme = theme.inputDecorationTheme;
    const digitStyle = TextStyle(fontSize: 18, letterSpacing: 0.5);

    // Phone numbers read left to right in both languages.
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Semantics(
        label: label,
        child: TextField(
          controller: controller,
          keyboardType: TextInputType.phone,
          textInputAction: TextInputAction.done,
          autofillHints: const [AutofillHints.telephoneNumberNational],
          // 11 digits covers a number typed with its leading zero.
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
            LengthLimitingTextInputFormatter(11),
          ],
          style: digitStyle,
          onChanged: onChanged,
          onSubmitted: (_) => onSubmitted(),
          decoration: InputDecoration(
            hintText: '10 1234 5678',
            enabledBorder: hasError ? inputTheme.errorBorder : null,
            focusedBorder: hasError ? inputTheme.focusedErrorBorder : null,
            prefixIcon: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const SizedBox(width: AppSpacing.lg),
                Text('+20', style: digitStyle.copyWith(color: theme.colorScheme.onSurfaceVariant)),
                const SizedBox(width: AppSpacing.md),
                const SizedBox(height: 24, child: VerticalDivider(width: 1, color: AppColors.border)),
                const SizedBox(width: AppSpacing.md),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _LegalNotice extends StatefulWidget {
  const _LegalNotice({this.onOpenTerms, this.onOpenPrivacy});

  final VoidCallback? onOpenTerms;
  final VoidCallback? onOpenPrivacy;

  @override
  State<_LegalNotice> createState() => _LegalNoticeState();
}

class _LegalNoticeState extends State<_LegalNotice> {
  late final _terms = TapGestureRecognizer()..onTap = () => widget.onOpenTerms?.call();
  late final _privacy = TapGestureRecognizer()..onTap = () => widget.onOpenPrivacy?.call();

  @override
  void dispose() {
    _terms.dispose();
    _privacy.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    const link = TextStyle(
      color: AppColors.goldDeep,
      decoration: TextDecoration.underline,
      decorationColor: AppColors.goldDeep,
    );

    return Text.rich(
      TextSpan(
        text: l10n.loginLegalPrefix,
        children: [
          TextSpan(text: l10n.loginLegalTerms, style: link, recognizer: _terms),
          TextSpan(text: l10n.loginLegalAnd),
          TextSpan(text: l10n.loginLegalPrivacy, style: link, recognizer: _privacy),
        ],
      ),
      textAlign: TextAlign.center,
      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
    );
  }
}
