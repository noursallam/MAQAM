import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../data/api_notifications_repository.dart';
import '../domain/app_notification.dart';

/// The customer's notifications (design: "22 الإشعارات").
class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key, required this.onOpenOrder, required this.onOpenSeminar});

  final ValueChanged<String> onOpenOrder;
  final ValueChanged<int> onOpenSeminar;

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    // The feed is loaded once per session; a push would refresh it, but one may never arrive
    Future.microtask(() => ref.invalidate(notificationsProvider));
  }

  Future<void> _open(BuildContext context, WidgetRef ref, AppNotification notification) async {
    final orderNumber = notification.orderNumber;
    final seminarId = notification.seminarId;
    if (orderNumber != null) {
      widget.onOpenOrder(orderNumber);
    } else if (seminarId != null) {
      widget.onOpenSeminar(seminarId);
    }
    try {
      await ref.read(notificationsProvider.notifier).markRead(notification);
    } on ApiException {
      // It already shows as read here; the server will be told on the next attempt.
    }
  }

  Future<void> _markAllRead(BuildContext context, WidgetRef ref) async {
    try {
      await ref.read(notificationsProvider.notifier).markAllRead();
    } on ApiException catch (e) {
      if (context.mounted) showApiError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final feed = ref.watch(notificationsProvider);
    final hasUnread = (feed.value?.unreadCount ?? 0) > 0;

    return Scaffold(
      appBar: AppBar(
        centerTitle: false,
        title: Text(l10n.notifications, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20)),
        actions: [
          if (hasUnread)
            TextButton(
              style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
              onPressed: () => _markAllRead(context, ref),
              child: Text(l10n.notificationsMarkAllRead),
            ),
          const SizedBox(width: AppSpacing.sm),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(notificationsProvider.future),
        child: AsyncView<NotificationFeed>(
          value: feed,
          errorTitle: l10n.notificationsLoadFailed,
          onRetry: () => ref.invalidate(notificationsProvider),
          data: (feed) => feed.items.isEmpty
              ? Center(
                  child: MessageView(
                    icon: Icons.notifications_none,
                    title: l10n.notificationsEmpty,
                    message: l10n.notificationsEmptyBody,
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  children: [
                    Card(
                      margin: EdgeInsets.zero,
                      clipBehavior: Clip.antiAlias,
                      child: Column(
                        children: [
                          for (final (index, item) in feed.items.indexed) ...[
                            if (index > 0) const Divider(height: 1, color: AppColors.border),
                            _NotificationTile(
                              notification: item,
                              onTap: () => _open(context, ref, item),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({required this.notification, required this.onTap});

  final AppNotification notification;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final unread = !notification.isRead;

    return Material(
      // Unread rows carry a warm tint, as in the design.
      color: unread ? const Color(0xFFFAF6EE) : AppColors.surface,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.only(top: 7),
                child: Icon(
                  Icons.circle,
                  size: 8,
                  color: unread ? AppColors.gold : Colors.transparent,
                  semanticLabel: unread ? context.l10n.notificationUnread : null,
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            notification.title,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: unread ? FontWeight.w700 : FontWeight.w500,
                            ),
                          ),
                        ),
                        if (notification.createdAt != null) ...[
                          const SizedBox(width: AppSpacing.sm),
                          Text(
                            timeAgoLabel(context, notification.createdAt!),
                            style: theme.textTheme.bodySmall?.copyWith(color: muted),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(notification.body, style: theme.textTheme.bodyMedium?.copyWith(color: muted)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
