import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../domain/auth_repository.dart';
import '../domain/whatsapp_challenge.dart';
import 'otp_controller.dart';
import 'widgets/auth_step_scaffold.dart';

const _otpLength = 6;

/// Step 3 of sign-in (design: "04 كود الدخول").
class OtpScreen extends StatefulWidget {
  const OtpScreen({
    super.key,
    required this.repository,
    required this.ticket,
    required this.onSignedIn,
    required this.onStartOver,
  });

  final AuthRepository repository;
  final OtpTicket ticket;
  final Future<void> Function(SignIn signIn) onSignedIn;
  final VoidCallback onStartOver;

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  late final OtpController _controller = OtpController(widget.repository, widget.ticket);
  final _otp = TextEditingController();
  final _focus = FocusNode();

  @override
  void dispose() {
    _controller.dispose();
    _otp.dispose();
    _focus.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_otp.text.length != _otpLength) return;
    final session = await _controller.verify(_otp.text);
    if (!mounted) return;
    if (session == null) {
      // Let the customer retype straight away after a wrong code.
      _otp.clear();
      _focus.requestFocus();
      return;
    }
    await widget.onSignedIn(session);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final mutedText = theme.colorScheme.onSurfaceVariant;

    return ListenableBuilder(
      listenable: Listenable.merge([_controller, _otp]),
      builder: (context, _) {
        final error = _controller.error;
        final expired = _controller.expired;
        final canSubmit = _otp.text.length == _otpLength && !expired && !_controller.submitting;

        return AuthStepScaffold(
          trailing: const AuthStepLabel(step: 3),
          footer: const _DoNotShareNotice(),
          children: [
            AuthStepHeader(title: l10n.otpTitle, subtitle: TextSpan(text: l10n.otpSubtitle)),
            const SizedBox(height: AppSpacing.xxl),
            _OtpField(
              controller: _otp,
              focusNode: _focus,
              enabled: !expired && !_controller.submitting,
              hasError: error != null,
              onChanged: (value) {
                _controller.clearError();
                if (value.length == _otpLength) _submit();
              },
            ),
            const SizedBox(height: AppSpacing.md),
            if (error != null)
              Semantics(
                liveRegion: true,
                child: Text(
                  apiErrorMessage(context, error),
                  style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
                ),
              )
            else if (expired)
              Text(l10n.otpExpired, style: theme.textTheme.bodyMedium)
            else
              Text.rich(
                TextSpan(
                  text: l10n.otpExpiresIn,
                  children: [
                    TextSpan(
                      text: _formatRemaining(_controller.remaining),
                      style: TextStyle(
                        color: theme.colorScheme.onSurface,
                        fontWeight: FontWeight.w700,
                        fontFeatures: const [FontFeature.tabularFigures()],
                      ),
                    ),
                  ],
                ),
                style: theme.textTheme.bodyMedium?.copyWith(color: mutedText),
              ),
            const SizedBox(height: AppSpacing.xl),
            FilledButton(
              onPressed: canSubmit ? _submit : null,
              child: _controller.submitting ? const ButtonSpinner() : Text(l10n.otpSubmit),
            ),
            const SizedBox(height: AppSpacing.md),
            Text.rich(
              TextSpan(
                text: l10n.otpNotReceivedPrefix,
                children: [
                  // A widget span keeps the link inline but still a real button.
                  WidgetSpan(
                    alignment: PlaceholderAlignment.middle,
                    child: AuthTextLink(label: l10n.startOver, onPressed: widget.onStartOver),
                  ),
                ],
              ),
              style: theme.textTheme.bodyMedium?.copyWith(color: mutedText),
            ),
          ],
        );
      },
    );
  }
}

String _formatRemaining(Duration d) {
  final minutes = d.inMinutes.toString().padLeft(2, '0');
  final seconds = (d.inSeconds % 60).toString().padLeft(2, '0');
  return '$minutes:$seconds';
}

/// Six boxes backed by one invisible text field, so paste and SMS-style
/// autofill work and the keyboard never jumps between fields.
class _OtpField extends StatelessWidget {
  const _OtpField({
    required this.controller,
    required this.focusNode,
    required this.enabled,
    required this.hasError,
    required this.onChanged,
  });

  final TextEditingController controller;
  final FocusNode focusNode;
  final bool enabled;
  final bool hasError;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final text = controller.text;

    // Codes read left to right in both languages.
    return Directionality(
      textDirection: TextDirection.ltr,
      child: SizedBox(
        height: 64,
        child: Stack(
          children: [
            ListenableBuilder(
              listenable: focusNode,
              builder: (context, _) => Row(
                children: [
                  for (var i = 0; i < _otpLength; i++) ...[
                    if (i > 0) const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: _OtpBox(
                        digit: i < text.length ? text[i] : null,
                        active: enabled && focusNode.hasFocus && i == text.length,
                        hasError: hasError,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            Positioned.fill(
              child: Opacity(
                opacity: 0,
                child: Semantics(
                  label: context.l10n.otpFieldLabel,
                  child: TextField(
                    controller: controller,
                    focusNode: focusNode,
                    enabled: enabled,
                    autofocus: true,
                    showCursor: false,
                    enableInteractiveSelection: false,
                    keyboardType: TextInputType.number,
                    textInputAction: TextInputAction.done,
                    autofillHints: const [AutofillHints.oneTimeCode],
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                      LengthLimitingTextInputFormatter(_otpLength),
                    ],
                    onChanged: onChanged,
                    decoration: const InputDecoration(
                      contentPadding: EdgeInsets.symmetric(vertical: 20),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OtpBox extends StatelessWidget {
  const _OtpBox({required this.digit, required this.active, required this.hasError});

  final String? digit;
  final bool active;
  final bool hasError;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final borderColor = hasError
        ? scheme.error
        : active
            ? AppColors.gold
            : digit != null
                ? scheme.outline
                : scheme.outlineVariant;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 150),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radius),
        border: Border.all(color: borderColor, width: active ? 2 : 1),
      ),
      child: ExcludeSemantics(
        child: Text(
          digit ?? '',
          style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}

class _DoNotShareNotice extends StatelessWidget {
  const _DoNotShareNotice();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return DecoratedBox(
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(AppSpacing.radius),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Icon(Icons.lock_outline, size: 20),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Text(context.l10n.otpDoNotShare, style: theme.textTheme.bodyMedium),
            ),
          ],
        ),
      ),
    );
  }
}
