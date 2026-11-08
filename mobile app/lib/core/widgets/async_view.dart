import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../l10n/l10n.dart';
import '../network/api_error_message.dart';
import '../network/api_exception.dart';
import '../theme/app_spacing.dart';

/// Renders the loading, error and data states of a provider the same way everywhere.
class AsyncView<T> extends StatelessWidget {
  const AsyncView({
    super.key,
    required this.value,
    required this.onRetry,
    required this.data,
    this.errorTitle,
  });

  final AsyncValue<T> value;
  final VoidCallback onRetry;
  final Widget Function(T data) data;

  /// Shown above the reason; defaults to the reason alone.
  final String? errorTitle;

  @override
  Widget build(BuildContext context) {
    return switch (value) {
      AsyncData(:final value) => data(value),
      AsyncError(:final error) => MessageView(
          title: errorTitle,
          message: error is ApiException
              ? apiErrorMessage(context, error)
              : context.l10n.errorGeneric,
          actionLabel: context.l10n.retry,
          onAction: onRetry,
        ),
      _ => const Padding(
          padding: EdgeInsets.all(AppSpacing.xxl),
          child: Center(child: CircularProgressIndicator()),
        ),
    };
  }
}

/// A centered message with an optional action: empty states, errors, sign-in prompts.
class MessageView extends StatelessWidget {
  const MessageView({
    super.key,
    this.icon,
    this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData? icon;
  final String? title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.all(AppSpacing.xl),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 40, color: theme.colorScheme.secondary),
            const SizedBox(height: AppSpacing.lg),
          ],
          if (title != null) ...[
            Text(title!, textAlign: TextAlign.center, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
          ],
          Text(
            message,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
          if (actionLabel != null) ...[
            const SizedBox(height: AppSpacing.lg),
            FilledButton(
              style: FilledButton.styleFrom(minimumSize: const Size(180, 48)),
              onPressed: onAction,
              child: Text(actionLabel!),
            ),
          ],
        ],
      ),
    );
  }
}
