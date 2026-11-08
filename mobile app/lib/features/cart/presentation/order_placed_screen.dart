import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../domain/cart.dart';

/// Confirmation after checkout (design: "17 تم الطلب").
class OrderPlacedScreen extends StatelessWidget {
  const OrderPlacedScreen({
    super.key,
    required this.orderNumber,
    required this.payment,
    required this.onTrack,
    required this.onHome,
  });

  final String orderNumber;

  /// How an online payment went; null when the order needs none (cash or points).
  final PaymentState? payment;
  final VoidCallback onTrack;
  final VoidCallback onHome;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final failed = payment == PaymentState.failed;

    final (title, body) = switch (payment) {
      null => (l10n.orderPlacedTitle, l10n.orderPlacedBody),
      PaymentState.paid => (l10n.orderPaidTitle, l10n.orderPlacedBody),
      PaymentState.pending => (l10n.orderPlacedTitle, l10n.orderPaymentPendingBody),
      PaymentState.failed => (l10n.orderPaymentFailedTitle, l10n.orderPaymentFailedBody),
    };

    // There is nothing to go back to: the cart is empty and the order exists.
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) onHome();
      },
      child: Scaffold(
        body: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
              child: CustomScrollView(
                slivers: [
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Padding(
                      padding: const EdgeInsets.all(AppSpacing.xl),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Spacer(),
                          Center(
                            child: CircleAvatar(
                              radius: 32,
                              backgroundColor: failed ? theme.colorScheme.error : AppColors.navy,
                              child: Icon(
                                failed ? Icons.priority_high : Icons.check,
                                color: failed ? Colors.white : AppColors.goldBright,
                                size: 28,
                              ),
                            ),
                          ),
                          const SizedBox(height: AppSpacing.xl),
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
                          const SizedBox(height: AppSpacing.lg),
                          Center(
                            child: DecoratedBox(
                              decoration: BoxDecoration(
                                color: theme.colorScheme.surfaceContainerHighest,
                                borderRadius: BorderRadius.circular(AppSpacing.md),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: AppSpacing.md, vertical: AppSpacing.sm),
                                child: SelectableText(
                                  orderNumber,
                                  textDirection: TextDirection.ltr,
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w600, letterSpacing: 0.5),
                                ),
                              ),
                            ),
                          ),
                          const Spacer(),
                          const SizedBox(height: AppSpacing.xl),
                          if (!failed) ...[
                            Card(
                              margin: EdgeInsets.zero,
                              child: Padding(
                                padding: const EdgeInsets.all(AppSpacing.lg),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Icon(Icons.qr_code_scanner,
                                        size: 20, color: theme.colorScheme.secondary),
                                    const SizedBox(width: AppSpacing.md),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            l10n.orderPlacedPointsTitle,
                                            style: theme.textTheme.titleMedium,
                                          ),
                                          Text(
                                            l10n.orderPlacedPointsBody,
                                            style:
                                                theme.textTheme.bodyMedium?.copyWith(color: muted),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: AppSpacing.lg),
                          ],
                          FilledButton(onPressed: onTrack, child: Text(l10n.orderPlacedTrack)),
                          const SizedBox(height: AppSpacing.sm),
                          TextButton(onPressed: onHome, child: Text(l10n.orderPlacedHome)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
