class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.isRead,
    this.orderNumber,
    this.seminarId,
    this.createdAt,
  });

  final int id;
  final String title;
  final String body;
  final bool isRead;

  /// The order this is about, when it is about one; tapping then opens it.
  final String? orderNumber;

  /// The seminar whose draw it is about; tapping then opens it.
  final int? seminarId;
  final DateTime? createdAt;

  AppNotification asRead() => AppNotification(
        id: id,
        title: title,
        body: body,
        isRead: true,
        orderNumber: orderNumber,
        seminarId: seminarId,
        createdAt: createdAt,
      );
}

class NotificationFeed {
  const NotificationFeed({this.items = const [], this.unreadCount = 0});

  final List<AppNotification> items;

  /// Unread across all of the customer's notifications, not only the loaded ones.
  final int unreadCount;
}

abstract interface class NotificationsRepository {
  /// The most recent notifications, newest first.
  Future<NotificationFeed> feed();

  Future<void> markRead(int id);

  Future<void> markAllRead();

  /// Tells the server where to push to on this device.
  Future<void> registerDevice({required String token, String? platform});
}
