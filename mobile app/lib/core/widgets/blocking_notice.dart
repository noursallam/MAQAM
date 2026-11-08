import 'package:flutter/material.dart';

import '../theme/app_spacing.dart';
import 'maqam_logo.dart';

/// A full screen that stops the app with one message: the logo, an icon,
/// a title and explanation, and the way out at the bottom.
class BlockingNotice extends StatelessWidget {
  const BlockingNotice({
    super.key,
    required this.icon,
    required this.iconColor,
    required this.iconBackground,
    required this.title,
    required this.body,
    this.detail,
    required this.actionLabel,
    required this.onAction,
    this.secondaryLabel,
    this.onSecondary,
  });

  final IconData icon;
  final Color iconColor;
  final Color iconBackground;
  final String title;
  final String body;
  final String? detail;
  final String actionLabel;
  final VoidCallback onAction;
  final String? secondaryLabel;
  final VoidCallback? onSecondary;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.xl, AppSpacing.xxl, AppSpacing.xl, AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Center(child: MaqamLogo()),
                  Expanded(
                    child: SingleChildScrollView(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: AppSpacing.xxl),
                        child: Column(
                          children: [
                            DecoratedBox(
                              decoration: BoxDecoration(
                                color: iconBackground,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: SizedBox.square(
                                dimension: 64,
                                child: Icon(icon, color: iconColor, size: 28),
                              ),
                            ),
                            const SizedBox(height: AppSpacing.lg),
                            Text(
                              title,
                              textAlign: TextAlign.center,
                              style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22),
                            ),
                            const SizedBox(height: AppSpacing.sm),
                            Text(
                              body,
                              textAlign: TextAlign.center,
                              style: theme.textTheme.bodyLarge?.copyWith(color: muted),
                            ),
                            if (detail != null) ...[
                              const SizedBox(height: AppSpacing.md),
                              Text(
                                detail!,
                                textAlign: TextAlign.center,
                                style: theme.textTheme.bodySmall?.copyWith(color: muted),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
                  FilledButton(onPressed: onAction, child: Text(actionLabel)),
                  if (secondaryLabel != null) ...[
                    const SizedBox(height: AppSpacing.sm),
                    TextButton(onPressed: onSecondary, child: Text(secondaryLabel!)),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
