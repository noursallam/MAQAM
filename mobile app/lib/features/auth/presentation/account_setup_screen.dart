import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../data/governorates_provider.dart';
import 'widgets/auth_step_scaffold.dart';
import 'widgets/password_field.dart';

const _minPasswordLength = 8;

/// Shown after a WhatsApp sign-in when the account needs a password: either a
/// first-time customer (who also gives their name), or one who forgot theirs.
class AccountSetupScreen extends StatefulWidget {
  const AccountSetupScreen({
    super.key,
    required this.askForName,
    this.initialName,
    required this.onSubmit,
    required this.onSkip,
  });

  /// True for a first-time customer; false when only replacing a password.
  final bool askForName;
  final String? initialName;

  /// Saves and moves on, or throws an [ApiException]. [fullName] is null when
  /// the name was not asked for.
  final Future<void> Function({
    String? fullName,
    required String password,
    String? governorate,
    String? area,
  }) onSubmit;
  final VoidCallback onSkip;

  @override
  State<AccountSetupScreen> createState() => _AccountSetupScreenState();
}

enum _Problem { nameMissing, passwordTooShort, mismatch, areaMissing }

class _AccountSetupScreenState extends State<AccountSetupScreen> {
  late final _name = TextEditingController(text: widget.initialName);
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  final _area = TextEditingController();
  String? _selectedGovernorate;

  // Kept as a kind, not a string, so the message follows a language switch.
  _Problem? _problem;
  ApiException? _failure;
  bool _busy = false;

  @override
  void dispose() {
    _name.dispose();
    _password.dispose();
    _confirm.dispose();
    _area.dispose();
    super.dispose();
  }

  _Problem? _validate() {
    if (widget.askForName && _name.text.trim().isEmpty) return _Problem.nameMissing;
    if (_password.text.length < _minPasswordLength) return _Problem.passwordTooShort;
    if (_confirm.text != _password.text) return _Problem.mismatch;
    if (widget.askForName && _area.text.trim().isEmpty) return _Problem.areaMissing;
    return null;
  }

  Future<void> _submit() async {
    if (_busy) return;
    final problem = _validate();
    setState(() {
      _problem = problem;
      _failure = null;
    });
    if (problem != null) return;

    FocusScope.of(context).unfocus();
    setState(() => _busy = true);
    try {
      await widget.onSubmit(
        fullName: widget.askForName ? _name.text.trim() : null,
        password: _password.text,
        governorate: _selectedGovernorate,
        area: _area.text.trim(),
      );
    } on ApiException catch (e) {
      if (mounted) setState(() => _failure = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _clearProblem(_Problem fixed) {
    if (_problem == fixed || _failure != null) {
      setState(() {
        if (_problem == fixed) _problem = null;
        _failure = null;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final inputTheme = theme.inputDecorationTheme;
    final nameMissing = _problem == _Problem.nameMissing;

    return AuthStepScaffold(
      showBack: false,
      trailing: TextButton(
        style: TextButton.styleFrom(foregroundColor: theme.colorScheme.onSurfaceVariant),
        onPressed: _busy ? null : widget.onSkip,
        child: Text(l10n.setupLater),
      ),
      footer: Text(
        l10n.setupNote,
        textAlign: TextAlign.center,
        style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
      ),
      children: [
        AuthStepHeader(
          title: widget.askForName ? l10n.setupTitle : l10n.resetTitle,
          subtitle: TextSpan(text: widget.askForName ? l10n.setupSubtitle : l10n.resetSubtitle),
        ),
        const SizedBox(height: AppSpacing.xxl),
        AutofillGroup(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (widget.askForName) ...[
                Text(l10n.setupNameLabel, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                Semantics(
                  label: l10n.setupNameLabel,
                  child: TextField(
                    controller: _name,
                    textCapitalization: TextCapitalization.words,
                    textInputAction: TextInputAction.next,
                    autofillHints: const [AutofillHints.name],
                    onChanged: (_) => _clearProblem(_Problem.nameMissing),
                    decoration: InputDecoration(
                      hintText: l10n.setupNameHint,
                      enabledBorder: nameMissing ? inputTheme.errorBorder : null,
                      focusedBorder: nameMissing ? inputTheme.focusedErrorBorder : null,
                    ),
                  ),
                ),
                if (nameMissing) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Semantics(
                    liveRegion: true,
                    child: Text(
                      l10n.setupNameRequired,
                      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                    ),
                  ),
                ],
                const SizedBox(height: AppSpacing.lg),
              ],
              PasswordField(
                controller: _password,
                label: l10n.setupPasswordLabel,
                hint: l10n.setupPasswordHint,
                autofillHints: const [AutofillHints.newPassword],
                textInputAction: TextInputAction.next,
                error: _problem == _Problem.passwordTooShort ? l10n.setupPasswordTooShort : null,
                onChanged: (_) => _clearProblem(_Problem.passwordTooShort),
              ),
              const SizedBox(height: AppSpacing.lg),
              PasswordField(
                controller: _confirm,
                label: l10n.setupConfirmLabel,
                autofillHints: const [AutofillHints.newPassword],
                error: _problem == _Problem.mismatch ? l10n.setupConfirmMismatch : null,
                onChanged: (_) => _clearProblem(_Problem.mismatch),
                onSubmitted: _submit,
              ),
              if (widget.askForName) ...[
                const SizedBox(height: AppSpacing.lg),
                Text(l10n.setupGovernorateLabel, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                Consumer(
                  builder: (context, ref, _) {
                    final governorates = ref.watch(governoratesProvider);
                    return governorates.when(
                      data: (list) => DropdownButtonFormField<String>(
                        value: _selectedGovernorate,
                        decoration: InputDecoration(hintText: l10n.setupGovernorateHint),
                        isExpanded: true,
                        items: list
                            .map((g) => DropdownMenuItem(value: g, child: Text(g)))
                            .toList(),
                        onChanged: (value) => setState(() => _selectedGovernorate = value),
                      ),
                      loading: () => const Padding(
                        padding: EdgeInsets.symmetric(vertical: AppSpacing.md),
                        child: Center(child: CircularProgressIndicator()),
                      ),
                      error: (_, _) => GestureDetector(
                        onTap: () => ref.invalidate(governoratesProvider),
                        child: InputDecorator(
                          decoration: InputDecoration(
                            hintText: l10n.setupGovernorateHint,
                            suffixIcon: Icon(Icons.refresh, color: theme.colorScheme.error),
                          ),
                          child: Text(
                            l10n.errorGeneric,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.error,
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                Text(l10n.setupAreaLabel, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: _area,
                  textInputAction: TextInputAction.done,
                  onChanged: (_) => _clearProblem(_Problem.areaMissing),
                  decoration: InputDecoration(
                    hintText: l10n.setupAreaHint,
                    enabledBorder: _problem == _Problem.areaMissing ? inputTheme.errorBorder : null,
                    focusedBorder: _problem == _Problem.areaMissing ? inputTheme.focusedErrorBorder : null,
                  ),
                ),
              ],
            ],
          ),
        ),
        if (_failure != null) ...[
          const SizedBox(height: AppSpacing.md),
          Semantics(
            liveRegion: true,
            child: Text(
              apiErrorMessage(context, _failure!),
              style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
            ),
          ),
        ],
        const SizedBox(height: AppSpacing.xl),
        FilledButton(
          onPressed: _busy ? null : _submit,
          child: _busy ? const ButtonSpinner() : Text(l10n.setupSave),
        ),
      ],
    );
  }
}
