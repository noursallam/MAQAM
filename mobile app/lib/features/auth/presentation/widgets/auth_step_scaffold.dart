import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../l10n/l10n.dart';

/// Shared frame for the sign-in steps: a back button, an optional trailing
/// widget in the top row, scrolling content, and an optional pinned footer.
class AuthStepScaffold extends StatelessWidget {
  const AuthStepScaffold({
    super.key,
    this.showBack = true,
    this.trailing,
    required this.children,
    this.footer,
  });

  /// False for a step that must be answered or skipped, not backed out of.
  final bool showBack;
  final Widget? trailing;
  final List<Widget> children;
  final Widget? footer;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsetsDirectional.only(
                    start: AppSpacing.md,
                    end: AppSpacing.xl,
                    top: AppSpacing.sm,
                    bottom: AppSpacing.sm,
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      if (showBack)
                        IconButton(
                          tooltip: context.l10n.back,
                          onPressed: () => Navigator.maybePop(context),
                          icon: const Icon(Icons.arrow_back),
                        )
                      else
                        const SizedBox(height: 48),
                      ?trailing,
                    ],
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(
                        AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, AppSpacing.xl),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: children,
                    ),
                  ),
                ),
                if (footer != null)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(
                        AppSpacing.xl, AppSpacing.sm, AppSpacing.xl, AppSpacing.lg),
                    child: footer,
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// "Step 2 of 3" label for the top row.
class AuthStepLabel extends StatelessWidget {
  const AuthStepLabel({super.key, required this.step});

  final int step;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Text(
      context.l10n.stepOf(step, 3),
      style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
    );
  }
}

/// Title and subtitle block at the top of a sign-in step.
class AuthStepHeader extends StatelessWidget {
  const AuthStepHeader({super.key, required this.title, required this.subtitle});

  final String title;
  final InlineSpan subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: theme.textTheme.headlineMedium),
        const SizedBox(height: AppSpacing.sm),
        Text.rich(
          subtitle,
          style: theme.textTheme.bodyLarge?.copyWith(color: theme.colorScheme.onSurfaceVariant),
        ),
      ],
    );
  }
}

/// Underlined gold text link that is still a full-size touch target.
class AuthTextLink extends StatelessWidget {
  const AuthTextLink({super.key, required this.label, required this.onPressed});

  final String label;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final color = Theme.of(context).colorScheme.secondary;
    return TextButton(
      style: TextButton.styleFrom(
        foregroundColor: color,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xs),
        textStyle: AppTheme.buttonText(fontSize: 14, fontWeight: FontWeight.w500).copyWith(
          decoration: TextDecoration.underline,
          decorationColor: color,
        ),
      ),
      onPressed: onPressed,
      child: Text(label),
    );
  }
}

/// The spinner shown inside a filled button while its request is in flight.
class ButtonSpinner extends StatelessWidget {
  const ButtonSpinner({super.key});

  @override
  Widget build(BuildContext context) {
    return const SizedBox.square(
      dimension: 22,
      child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
    );
  }
}
