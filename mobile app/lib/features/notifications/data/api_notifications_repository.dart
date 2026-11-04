import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/session/session_provider.dart';
import '../domain/app_notification.dart';

class ApiNotificationsRepository implements NotificationsRepository {
  const ApiNotificationsRepository(this._api);

  final ApiClient _api;

  @override
  Future<NotificationFeed> feed() async {
    final page = await _api.getPage('/notifications', query: {'per_page': '30'});
    return NotificationFeed(
      unreadCount: page.meta['unread_count'] as int? ?? 0,
      items: [
        for (final json in page.items)
          AppNotification(
            id: json['id'] as int,
            title: json['title'] as String? ?? '',
            body: json['body'] as String? ?? '',
            isRead: json['is_read'] == true,
            orderNumber: _orderNumber(json['data']),
            seminarId: _seminarId(json['data']),
            createdAt: DateTime.tryParse(json['created_at'] as String? ?? '')?.toLocal(),
          ),
      ],
    );
  }

  @override
  Future<void> markRead(int id) => _api.post('/notifications/$id/read');

  @override
  Future<void> markAllRead() => _api.post('/notifications/read-all');

  @override
  Future<void> registerDevice({required String token, String? platform}) =>
      _api.post('/notifications/device-token', body: {
        'device_token': token,
        'platform': ?platform,
      });

  /// `data` is an object, but an empty one may arrive as a list.
  static String? _orderNumber(Object? data) =>
      data is Map<String, dynamic> ? data['order_number'] as String? : null;

  static int? _seminarId(Object? data) =>
      data is Map<String, dynamic> ? int.tryParse('${data['seminar_id'] ?? ''}') : null;
}

final notificationsRepositoryProvider = Provider<NotificationsRepository>(
  (ref) => ApiNotificationsRepository(ref.watch(apiClientProvider)),
);

/// The signed-in customer's notifications; empty for a guest.
///
/// Marking as read updates this state at once and tells the server after, so
/// the list and the badges react immediately.
final notificationsProvider =
    AsyncNotifierProvider<NotificationsNotifier, NotificationFeed>(NotificationsNotifier.new);

class NotificationsNotifier extends AsyncNotifier<NotificationFeed> {
  @override
  Future<NotificationFeed> build() async {
    if (ref.watch(sessionProvider) == null) return const NotificationFeed();
    return ref.watch(notificationsRepositoryProvider).feed();
  }

  Future<void> markRead(AppNotification notification) async {
    final feed = state.value;
    if (feed == null || notification.isRead) return;
    state = AsyncData(NotificationFeed(
      unreadCount: feed.unreadCount > 0 ? feed.unreadCount - 1 : 0,
      items: [for (final n in feed.items) n.id == notification.id ? n.asRead() : n],
    ));
    await ref.read(notificationsRepositoryProvider).markRead(notification.id);
  }

  Future<void> markAllRead() async {
    final feed = state.value;
    if (feed == null || feed.unreadCount == 0) return;
    state = AsyncData(NotificationFeed(items: [for (final n in feed.items) n.asRead()]));
    await ref.read(notificationsRepositoryProvider).markAllRead();
  }
}

/// For the dot on the bell and the count in the account screen.
final unreadNotificationsProvider =
    Provider<int>((ref) => ref.watch(notificationsProvider).value?.unreadCount ?? 0);
