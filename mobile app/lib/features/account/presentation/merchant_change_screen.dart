import 'package:flutter/material.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';

class MerchantChangeScreen extends StatefulWidget {
  const MerchantChangeScreen({super.key, required this.onSubmit});

  final Future<void> Function(String merchantCode) onSubmit;

  @override
  State<MerchantChangeScreen> createState() => _MerchantChangeScreenState();
}

class _MerchantChangeScreenState extends State<MerchantChangeScreen> {
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

    return Scaffold(
      appBar: AppBar(title: Text(l10n.merchantChangeTitle)),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                Text(
                  l10n.merchantChangeSubtitle,
                  style: theme.textTheme.bodyLarge?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
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
                    l10n.merchantChangeCodeRequired,
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
                  child: _busy
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(l10n.merchantChangeSubmit),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
