import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../domain/order.dart';

String orderStatusLabel(AppLocalizations l10n, OrderStatus status) => switch (status) {
      OrderStatus.received => l10n.orderStatusReceived,
      OrderStatus.preparing => l10n.orderStatusPreparing,
      OrderStatus.shipped => l10n.orderStatusShipped,
      OrderStatus.delivered => l10n.orderStatusDelivered,
      OrderStatus.cancelled => l10n.orderStatusCancelled,
    };

String paymentMethodLabel(AppLocalizations l10n, String? method) => switch (method) {
      'cod' => l10n.orderPaymentCod,
      'kashier' => l10n.orderPaymentCard,
      'wallet' => l10n.orderPaymentPoints,
      _ => '',
    };

/// The coloured status tag on an order.
class OrderStatusPill extends StatelessWidget {
  const OrderStatusPill({super.key, required this.status, this.awaitingPayment = false});

  static const _unpaid = (Color(0xFFFBE9E7), Color(0xFFB3261E));

  final OrderStatus status;

  /// Says so instead of the status: an unpaid order is not on its way yet.
  final bool awaitingPayment;

  @override
  Widget build(BuildContext context) {
    final (background, foreground) = awaitingPayment ? _unpaid : switch (status) {
      OrderStatus.received || OrderStatus.preparing => (const Color(0xFFF3E7D3), AppColors.goldDeep),
      OrderStatus.shipped => (const Color(0xFFDDE5F5), const Color(0xFF27407A)),
      OrderStatus.delivered => (const Color(0xFFDDEFE3), AppColors.whatsapp),
      OrderStatus.cancelled => (AppColors.surfaceMuted, AppColors.textSecondary),
    };

    return DecoratedBox(
      decoration: ShapeDecoration(color: background, shape: const StadiumBorder()),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 2),
        child: Text(
          awaitingPayment ? context.l10n.orderAwaitingPayment : orderStatusLabel(context.l10n, status),
          style: TextStyle(color: foreground, fontSize: 13, fontWeight: FontWeight.w500),
        ),
      ),
    );
  }
}

/// The four steps from order to doorstep, with the reached ones filled in.
class OrderTimeline extends StatelessWidget {
  const OrderTimeline({super.key, required this.status});

  final OrderStatus status;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    const steps = [
      OrderStatus.received,
      OrderStatus.preparing,
      OrderStatus.shipped,
      OrderStatus.delivered,
    ];
    final labels = [
      l10n.orderStepReceived,
      l10n.orderStepPreparing,
      l10n.orderStepShipping,
      l10n.orderStepDelivery,
    ];
    final current = steps.indexOf(status);

    return Semantics(
      label: orderStatusLabel(l10n, status),
      child: ExcludeSemantics(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (var i = 0; i < steps.length; i++)
              Expanded(
                child: Column(
                  children: [
                    Row(
                      children: [
                        // Half a connector on each side joins neighbouring dots.
                        Expanded(child: _Line(visible: i > 0, done: i <= current)),
                        _Dot(done: i < current || status == OrderStatus.delivered, current: i == current),
                        Expanded(child: _Line(visible: i < steps.length - 1, done: i < current)),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      labels[i],
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodySmall?.copyWith(
                        fontWeight: i == current ? FontWeight.w700 : FontWeight.w400,
                        color: i <= current
                            ? theme.colorScheme.onSurface
                            : theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line({required this.visible, required this.done});

  final bool visible;
  final bool done;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 2,
      color: !visible
          ? Colors.transparent
          : done
              ? AppColors.navy
              : AppColors.border,
    );
  }
}

class _Dot extends StatelessWidget {
  const _Dot({required this.done, required this.current});

  final bool done;
  final bool current;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 22,
      height: 22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: done
            ? AppColors.navy
            : current
                ? AppColors.gold
                : AppColors.surface,
        border: Border.all(color: done || current ? Colors.transparent : AppColors.border, width: 2),
      ),
      child: done
          ? const Icon(Icons.check, size: 13, color: Colors.white)
          : current
              ? const Icon(Icons.circle, size: 8, color: Colors.white)
              : null,
    );
  }
}
