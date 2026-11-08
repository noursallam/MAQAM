import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../l10n/l10n.dart';

/// A labelled password input with a show/hide toggle and an inline error.
class PasswordField extends StatefulWidget {
  const PasswordField({
    super.key,
    required this.controller,
    required this.label,
    this.hint,
    this.error,
    this.autofillHints = const [AutofillHints.password],
    this.textInputAction = TextInputAction.done,
    this.autofocus = false,
    this.onChanged,
    this.onSubmitted,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final String? error;
  final List<String> autofillHints;
  final TextInputAction textInputAction;
  final bool autofocus;
  final ValueChanged<String>? onChanged;
  final VoidCallback? onSubmitted;

  @override
  State<PasswordField> createState() => _PasswordFieldState();
}

class _PasswordFieldState extends State<PasswordField> {
  bool _hidden = true;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final inputTheme = theme.inputDecorationTheme;
    final hasError = widget.error != null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.label, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        // Passwords are typed left to right whatever the app language.
        Directionality(
          textDirection: TextDirection.ltr,
          child: Semantics(
            label: widget.label,
            child: TextField(
              controller: widget.controller,
              obscureText: _hidden,
              autofocus: widget.autofocus,
              enableSuggestions: false,
              autocorrect: false,
              keyboardType: TextInputType.visiblePassword,
              textInputAction: widget.textInputAction,
              autofillHints: widget.autofillHints,
              onChanged: widget.onChanged,
              onSubmitted: (_) => widget.onSubmitted?.call(),
              decoration: InputDecoration(
                hintText: widget.hint,
                hintTextDirection: Directionality.of(context),
                enabledBorder: hasError ? inputTheme.errorBorder : null,
                focusedBorder: hasError ? inputTheme.focusedErrorBorder : null,
                suffixIcon: IconButton(
                  tooltip: _hidden ? l10n.passwordShow : l10n.passwordHide,
                  onPressed: () => setState(() => _hidden = !_hidden),
                  icon: Icon(_hidden ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                ),
              ),
            ),
          ),
        ),
        if (hasError) ...[
          const SizedBox(height: AppSpacing.sm),
          Semantics(
            liveRegion: true,
            child: Text(
              widget.error!,
              style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
            ),
          ),
        ],
      ],
    );
  }
}
