import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// A push notification as the app needs it.
class PushMessage {
  const PushMessage({this.title, this.body, this.orderNumber, this.seminarId});

  final String? title;
  final String? body;

  /// The order it is about, when it is about one.
  final String? orderNumber;

  /// The seminar it is about: a prize won in its draw.
  final int? seminarId;
}

/// Push notifications from the server (Firebase Cloud Messaging on the phones).
abstract interface class PushMessaging {
  /// Asks for permission if needed and returns this device's push token, or
  /// null when push is unavailable or was refused.
  Future<String?> start();

  /// A new token, whenever the system replaces the old one.
  Stream<String> get tokenRefreshes;

  /// Notifications that arrive while the app is open (the system shows nothing then).
  Stream<PushMessage> get foregroundMessages;

  /// Notifications the customer tapped while the app was in the background.
  Stream<PushMessage> get openedMessages;

  /// The notification whose tap launched the app, if that is how it started.
  Future<PushMessage?> launchMessage();
}

/// Platforms without push: the web and desktop builds, and the tests.
class NoPushMessaging implements PushMessaging {
  const NoPushMessaging();

  @override
  Future<String?> start() async => null;

  @override
  Stream<String> get tokenRefreshes => const Stream.empty();

  @override
  Stream<PushMessage> get foregroundMessages => const Stream.empty();

  @override
  Stream<PushMessage> get openedMessages => const Stream.empty();

  @override
  Future<PushMessage?> launchMessage() async => null;
}

class FirebasePushMessaging implements PushMessaging {
  bool _ready = false;

  /// Firebase is configured natively (google-services.json), so there is
  /// nothing to pass here. Returns false if this build has no such config.
  Future<bool> _ensureReady() async {
    if (_ready) return true;
    try {
      await Firebase.initializeApp();
      _ready = true;
    } on Exception {
      _ready = false;
    }
    return _ready;
  }

  @override
  Future<String?> start() async {
    if (!await _ensureReady()) return null;
    try {
      final settings = await FirebaseMessaging.instance.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) return null;
      return await FirebaseMessaging.instance.getToken();
    } on Exception {
      // No Google services on the device, or no network yet: push simply stays off.
      return null;
    }
  }

  @override
  Stream<String> get tokenRefreshes => _whenReady(() => FirebaseMessaging.instance.onTokenRefresh);

  @override
  Stream<PushMessage> get foregroundMessages =>
      _whenReady(() => FirebaseMessaging.onMessage.map(_message));

  @override
  Stream<PushMessage> get openedMessages =>
      _whenReady(() => FirebaseMessaging.onMessageOpenedApp.map(_message));

  @override
  Future<PushMessage?> launchMessage() async {
    if (!await _ensureReady()) return null;
    final message = await FirebaseMessaging.instance.getInitialMessage();
    return message == null ? null : _message(message);
  }

  Stream<T> _whenReady<T>(Stream<T> Function() source) async* {
    if (await _ensureReady()) yield* source();
  }

  static PushMessage _message(RemoteMessage message) => PushMessage(
        title: message.notification?.title,
        body: message.notification?.body,
        orderNumber: message.data['order_number'] as String?,
        seminarId: int.tryParse('${message.data['seminar_id'] ?? ''}'),
      );
}

final pushMessagingProvider = Provider<PushMessaging>((ref) {
  final onPhone = !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);
  return onPhone ? FirebasePushMessaging() : const NoPushMessaging();
});

/// The push token last registered with the server, so signing out can tell the
/// server to stop pushing to this device.
final pushTokenProvider = NotifierProvider<PushTokenNotifier, String?>(PushTokenNotifier.new);

class PushTokenNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void set(String? token) => state = token;
}
