import 'package:flutter/material.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import 'widgets/auth_step_scaffold.dart';

class BindMerchantScreen extends StatefulWidget {
  const BindMerchantScreen({
    super.key,
    required this.onSubmit,
    required this.onSkip,
  });

  /// Binds the merchant code, or throws an [ApiException].
  final Future<void> Function(String merchantCode) onSubmit;
  final VoidCallback onSkip;

  @override
  State<BindMerchantScreen> createState() => _BindMerchantScreenState();
}

class _BindMerchantScreenState extends State<BindMerchantScreen> {
  final _code = TextEditingController();
  bool _codeEmpty = false;
  ApiException? _failure;
  bool _busy = false;

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_busy) return;
    final code = _code.text.trim();
    if (code.isEmpty) {
      setState(() => _codeEmpty = true);
      return;
    }
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = true;
      _failure = null;
    });
    try {
      await widget.onSubmit(code);
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
    final inputTheme = theme.inputDecorationTheme;

    return AuthStepScaffold(
      showBack: false,
      trailing: TextButton(
        style: TextButton.styleFrom(foregroundColor: theme.colorScheme.onSurfaceVariant),
        onPressed: _busy ? null : widget.onSkip,
        child: Text(l10n.setupLater),
      ),
      children: [
        AuthStepHeader(
          title: l10n.bindMerchantTitle,
          subtitle: TextSpan(text: l10n.bindMerchantSubtitle),
        ),
        const SizedBox(height: AppSpacing.xxl),
        Text(l10n.bindMerchantCodeLabel, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        TextField(
          controller: _code,
          textInputAction: TextInputAction.done,
          onChanged: (_) {
            if (_codeEmpty || _failure != null) {
              setState(() {
                _codeEmpty = false;
                _failure = null;
              });
            }
          },
          onSubmitted: (_) => _submit(),
          decoration: InputDecoration(
            hintText: l10n.bindMerchantCodeHint,
            enabledBorder: _codeEmpty ? inputTheme.errorBorder : null,
            focusedBorder: _codeEmpty ? inputTheme.focusedErrorBorder : null,
          ),
        ),
        if (_codeEmpty) ...[
          const SizedBox(height: AppSpacing.sm),
          Text(
            l10n.bindMerchantCodeRequired,
            style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
          ),
        ],
        if (_failure != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(
            apiErrorMessage(context, _failure!),
            style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
          ),
        ],
        const SizedBox(height: AppSpacing.xl),
        FilledButton(
          onPressed: _busy ? null : _submit,
          child: _busy ? const ButtonSpinner() : Text(l10n.bindMerchantSubmit),
        ),
      ],
    );
  }
}
