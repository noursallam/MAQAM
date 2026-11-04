import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';

class DeductPointsScreen extends StatefulWidget {
  const DeductPointsScreen({
    super.key,
    required this.technicianName,
    required this.onSubmit,
  });

  final String technicianName;
  final Future<void> Function({required int amount, required String reason}) onSubmit;

  @override
  State<DeductPointsScreen> createState() => _DeductPointsScreenState();
}

class _DeductPointsScreenState extends State<DeductPointsScreen> {
  final _amount = TextEditingController();
  final _reason = TextEditingController();
  bool _amountEmpty = false;
  bool _reasonEmpty = false;
  ApiException? _failure;
  bool _busy = false;

  @override
  void dispose() {
    _amount.dispose();
    _reason.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_busy) return;
    final amountText = _amount.text.trim();
    final reason = _reason.text.trim();
    final amountEmpty = amountText.isEmpty || int.tryParse(amountText) == null || int.parse(amountText) <= 0;
    final reasonEmpty = reason.isEmpty;
    setState(() {
      _amountEmpty = amountEmpty;
      _reasonEmpty = reasonEmpty;
      _failure = null;
    });
    if (amountEmpty || reasonEmpty) return;

    FocusScope.of(context).unfocus();
    setState(() => _busy = true);
    try {
      await widget.onSubmit(amount: int.parse(amountText), reason: reason);
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
      appBar: AppBar(title: Text(l10n.merchantDeductPoints)),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                Text(
                  widget.technicianName,
                  style: theme.textTheme.titleLarge,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: AppSpacing.xxl),
                Text(l10n.merchantDeductAmount, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: _amount,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  textInputAction: TextInputAction.next,
                  onChanged: (_) {
                    if (_amountEmpty) setState(() => _amountEmpty = false);
                  },
                  decoration: InputDecoration(
                    hintText: l10n.merchantDeductAmountHint,
                    enabledBorder: _amountEmpty ? inputTheme.errorBorder : null,
                    focusedBorder: _amountEmpty ? inputTheme.focusedErrorBorder : null,
                  ),
                ),
                if (_amountEmpty) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    l10n.merchantDeductAmountRequired,
                    style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                  ),
                ],
                const SizedBox(height: AppSpacing.lg),
                Text(l10n.merchantDeductReason, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: _reason,
                  textInputAction: TextInputAction.done,
                  onChanged: (_) {
                    if (_reasonEmpty) setState(() => _reasonEmpty = false);
                  },
                  onSubmitted: (_) => _submit(),
                  decoration: InputDecoration(
                    hintText: l10n.merchantDeductReasonHint,
                    enabledBorder: _reasonEmpty ? inputTheme.errorBorder : null,
                    focusedBorder: _reasonEmpty ? inputTheme.focusedErrorBorder : null,
                  ),
                ),
                if (_reasonEmpty) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    l10n.merchantDeductReasonRequired,
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
                      : Text(l10n.merchantDeductSubmit),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
