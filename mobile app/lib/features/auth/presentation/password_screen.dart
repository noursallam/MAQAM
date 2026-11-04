import 'package:flutter/material.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../domain/egyptian_phone.dart';
import 'widgets/auth_step_scaffold.dart';
import 'widgets/password_field.dart';

/// Sign-in for a number that has a password.
class PasswordScreen extends StatefulWidget {
  const PasswordScreen({
    super.key,
    required this.phone,
    required this.onSubmit,
    required this.onForgot,
  });

  /// Normalized phone number (`01XXXXXXXXX`).
  final String phone;

  /// Signs in and moves on, or throws an [ApiException].
  final Future<void> Function(String password) onSubmit;

  /// Sign in with a WhatsApp code instead, then choose a new password.
  final VoidCallback onForgot;

  @override
  State<PasswordScreen> createState() => _PasswordScreenState();
}

class _PasswordScreenState extends State<PasswordScreen> {
  final _password = TextEditingController();
  bool _busy = false;
  bool _missing = false;
  ApiException? _failure;

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_busy) return;
    if (_password.text.isEmpty) {
      setState(() => _missing = true);
      return;
    }
    setState(() {
      _busy = true;
      _failure = null;
    });
    try {
      await widget.onSubmit(_password.text);
    } on ApiException catch (e) {
      if (!mounted) return;
      _password.clear();
      setState(() => _failure = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return AuthStepScaffold(
      children: [
        AuthStepHeader(
          title: l10n.passwordTitle,
          subtitle: TextSpan(
            text: l10n.passwordSubtitlePrefix,
            children: [
              TextSpan(
                // A left-to-right isolate keeps the digit groups in order inside Arabic text.
                text: '\u2066${formatEgyptianPhone(widget.phone)}\u2069',
                style: TextStyle(color: theme.colorScheme.onSurface, fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.xxl),
        PasswordField(
          controller: _password,
          label: l10n.passwordLabel,
          autofocus: true,
          error: _missing
              ? l10n.passwordRequired
              : _failure == null
                  ? null
                  : apiErrorMessage(context, _failure!),
          onChanged: (_) {
            if (_missing || _failure != null) {
              setState(() {
                _missing = false;
                _failure = null;
              });
            }
          },
          onSubmitted: _submit,
        ),
        const SizedBox(height: AppSpacing.xl),
        FilledButton(
          onPressed: _busy ? null : _submit,
          child: _busy ? const ButtonSpinner() : Text(l10n.passwordSubmit),
        ),
        const SizedBox(height: AppSpacing.sm),
        Center(child: AuthTextLink(label: l10n.passwordForgot, onPressed: widget.onForgot)),
      ],
    );
  }
}
