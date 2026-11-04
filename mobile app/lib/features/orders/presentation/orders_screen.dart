import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../loyalty/loyalty_providers.dart';
import '../data/api_orders_repository.dart';
import '../domain/order.dart';
import 'order_widgets.dart';

enum _Filter { all, active, completed }

/// The customer's orders (design: "18 طلباتي").
class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key, required this.onOpenOrder, required this.onBrowse});

  final ValueChanged<String> onOpenOrder;
  final VoidCallback onBrowse;

  @override
  ConsumerState<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends ConsumerState<OrdersScreen> {
  _Filter _filter = _Filter.all;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final orders = ref.watch(ordersProvider);

    return Scaffold(
      appBar: AppBar(
        centerTitle: false,
        title: Text(l10n.actionOrders, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20)),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(ordersProvider.future),
        child: AsyncView<List<Order>>(
          value: orders,
          errorTitle: l10n.ordersLoadFailed,
          onRetry: () => ref.invalidate(ordersProvider),
          data: (all) {
            if (all.isEmpty) {
              return Center(
                child: MessageView(
                  icon: Icons.inventory_2_outlined,
                  title: l10n.ordersEmpty,
                  message: l10n.ordersEmptyBody,
                  actionLabel: l10n.cartBrowse,
                  onAction: widget.onBrowse,
                ),
              );
            }
            final shown = switch (_filter) {
              _Filter.all => all,
              _Filter.active => all.where((o) => o.status.isActive).toList(),
              // Finished one way or the other: delivered or cancelled.
              _Filter.completed => all.where((o) => !o.status.isActive).toList(),
            };

            return ListView(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xl),
              children: [
                Wrap(
                  spacing: AppSpacing.sm,
                  children: [
                    for (final (filter, label) in [
                      (_Filter.all, l10n.catalogAll),
                      (_Filter.active, l10n.ordersFilterActive),
                      (_Filter.completed, l10n.ordersFilterCompleted),
                    ])
                      FilterPill(
                        label: label,
                        selected: _filter == filter,
                        onTap: () => setState(() => _filter = filter),
                      ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                if (shown.isEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xxl),
                    child: MessageView(message: l10n.ordersNoneInFilter),
                  ),
                for (final order in shown)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.md),
                    child: _OrderCard(order: order, onTap: () => widget.onOpenOrder(order.number)),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({required this.order, required this.onTap});

  final Order order;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final subtitle = [
      if (order.placedAt != null) dayLabel(context, order.placedAt!),
      paymentMethodLabel(l10n, order.paymentMethod),
    ].where((part) => part.isNotEmpty).join(' · ');

    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      order.number,
                      textDirection: TextDirection.ltr,
                      textAlign: TextAlign.end,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  OrderStatusPill(status: order.status, awaitingPayment: order.awaitingPayment),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      subtitle,
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                    ),
                  ),
                  Text(
                    formatEgp(context, order.total),
                    style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// One order: where it is, what is in it, and where it is going
/// (design: "19 تفاصيل الطلب").
class OrderDetailsScreen extends ConsumerStatefulWidget {
  const OrderDetailsScreen({super.key, required this.orderNumber});

  final String orderNumber;

  @override
  ConsumerState<OrderDetailsScreen> createState() => _OrderDetailsScreenState();
}

class _OrderDetailsScreenState extends ConsumerState<OrderDetailsScreen> {
  bool _cancelling = false;

  Future<void> _cancel() async {
    final l10n = context.l10n;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.orderCancelConfirmTitle),
        content: Text(l10n.orderCancelNote),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(l10n.orderCancelKeep)),
          TextButton(
            style: TextButton.styleFrom(foregroundColor: Theme.of(context).colorScheme.error),
            onPressed: () => Navigator.pop(context, true),
            child: Text(l10n.orderCancel),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _cancelling = true);
    try {
      await ref.read(ordersRepositoryProvider).cancel(widget.orderNumber);
      // The list, this order, and any points given back are all stale now.
      ref
        ..invalidate(orderDetailsProvider(widget.orderNumber))
        ..invalidate(ordersProvider)
        ..invalidate(walletProvider);
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final details = ref.watch(orderDetailsProvider(widget.orderNumber));
    final canCancel = details.value?.order.canCancel ?? false;

    return Scaffold(
      appBar: AppBar(
        centerTitle: false,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.orderDetailsTitle, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20)),
            Text(
              widget.orderNumber,
              textDirection: TextDirection.ltr,
              style: theme.textTheme.bodySmall?.copyWith(color: muted),
            ),
          ],
        ),
      ),
      body: AsyncView<OrderDetails>(
        value: details,
        errorTitle: l10n.ordersLoadFailed,
        onRetry: () => ref.invalidate(orderDetailsProvider(widget.orderNumber)),
        data: (details) {
          final order = details.order;
          return ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              Card(
                margin: EdgeInsets.zero,
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              orderStatusLabel(l10n, order.status),
                              style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20),
                            ),
                          ),
                          Text(
                            paymentMethodLabel(l10n, order.paymentMethod),
                            style: theme.textTheme.bodyMedium?.copyWith(color: muted),
                          ),
                        ],
                      ),
                      if (order.awaitingPayment) ...[
                        const SizedBox(height: AppSpacing.md),
                        Align(
                          alignment: AlignmentDirectional.centerStart,
                          child: Text(
                            l10n.orderAwaitingPaymentNote,
                            style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
                          ),
                        ),
                      ],
                      // A cancelled order has left the path, so no steps are shown.
                      if (order.status != OrderStatus.cancelled) ...[
                        const SizedBox(height: AppSpacing.lg),
                        OrderTimeline(status: order.status),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              Card(
                margin: EdgeInsets.zero,
                child: Column(
                  children: [
                    for (final (index, item) in details.items.indexed) ...[
                      if (index > 0) const Divider(height: 1, color: AppColors.border),
                      Padding(
                        padding: const EdgeInsets.all(AppSpacing.md),
                        child: Row(
                          children: [
                            ProductThumbnail(url: item.imageUrl),
                            const SizedBox(width: AppSpacing.md),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(item.name, maxLines: 2, overflow: TextOverflow.ellipsis),
                                  Text(
                                    [
                                      l10n.orderItemQuantity(item.quantity),
                                      if (item.optionLabel?.isNotEmpty ?? false) item.optionLabel!,
                                    ].join(' · '),
                                    style: theme.textTheme.bodySmall?.copyWith(color: muted),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: AppSpacing.sm),
                            Text(
                              item.lineTotal > 0 ? formatEgp(context, item.lineTotal) : l10n.orderItemFree,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                fontWeight: FontWeight.w700,
                                color: item.lineTotal > 0 ? null : AppColors.whatsapp,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              if (details.addressLine != null) ...[
                const SizedBox(height: AppSpacing.md),
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.location_on_outlined, size: 20, color: theme.colorScheme.secondary),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text.rich(
                                TextSpan(
                                  text: details.recipientName ?? '',
                                  style: theme.textTheme.titleMedium,
                                  children: [
                                    if (details.recipientPhone != null)
                                      TextSpan(
                                        // A left-to-right isolate keeps the phone digits in order.
                                        text: ' · \u2066${details.recipientPhone}\u2069',
                                        style: theme.textTheme.bodyMedium?.copyWith(color: muted),
                                      ),
                                  ],
                                ),
                              ),
                              Text(
                                details.addressLine!,
                                style: theme.textTheme.bodyMedium?.copyWith(color: muted),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
              const SizedBox(height: AppSpacing.md),
              DecoratedBox(
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(AppSpacing.radius),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: OrderSummary(
                    subtotalLabel: l10n.summarySubtotal,
                    subtotal: details.subtotal,
                    discount: details.discount,
                    shipping: details.shipping,
                    total: order.total,
                  ),
                ),
              ),
            ],
          );
        },
      ),
      bottomNavigationBar: canCancel
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.lg),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: theme.colorScheme.error,
                        side: BorderSide(color: theme.colorScheme.error),
                        minimumSize: const Size.fromHeight(AppSpacing.controlHeight),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(AppSpacing.radius)),
                      ),
                      onPressed: _cancelling ? null : _cancel,
                      child: Text(l10n.orderCancel),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      l10n.orderCancelNote,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodySmall?.copyWith(color: muted),
                    ),
                  ],
                ),
              ),
            )
          : null,
    );
  }
}
