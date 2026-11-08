import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../core/locale/locale_provider.dart';
import '../core/network/api_exception.dart';
import '../core/push/push_messaging.dart';
import '../core/session/session_provider.dart';
import '../features/loyalty/loyalty_providers.dart';
import '../features/notifications/data/api_notifications_repository.dart';
import '../features/orders/data/api_orders_repository.dart';
import '../l10n/l10n.dart';
import 'router.dart';

/// Connects push notifications to the rest of the app: registers this device
/// for the signed-in customer, refreshes what a notification is about when it
/// arrives, and opens the right screen when one is tapped.
class PushCoordinator {
  PushCoordinator(this._ref, this._messenger);

  final WidgetRef _ref;
  final GlobalKey<ScaffoldMessengerState> _messenger;
  final _subscriptions = <StreamSubscription<Object?>>[];

  GoRouter get _router => _ref.read(routerProvider);

  PushMessaging get _push => _ref.read(pushMessagingProvider);

  void start() {
    _subscriptions
      ..add(_push.tokenRefreshes.listen(_register))
      ..add(_push.foregroundMessages.listen(_arrived))
      ..add(_push.openedMessages.listen(_open));

    // Register on launch when already signed in, and again on every sign-in.
    _ref.listenManual(sessionProvider, (previous, next) {
      if (next != null && previous?.token != next.token) _startForSession();
    }, fireImmediately: true);

    _push.launchMessage().then((message) {
      if (message != null) _open(message);
    });
  }

  void dispose() {
    for (final subscription in _subscriptions) {
      subscription.cancel();
    }
  }

  Future<void> _startForSession() async {
    final token = await _push.start();
    if (token != null) await _register(token);
  }

  Future<void> _register(String token) async {
    if (_ref.read(sessionProvider) == null) return;
    try {
      await _ref.read(notificationsRepositoryProvider).registerDevice(
            token: token,
            platform: defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android',
          );
      _ref.read(pushTokenProvider.notifier).set(token);
    } on ApiException {
      // Tried again on the next launch or token refresh.
    }
  }

  /// While the app is open the system shows nothing, so the app does.
  void _arrived(PushMessage message) {
    _ref
      ..invalidate(notificationsProvider)
      ..invalidate(walletProvider);
    if (message.orderNumber != null) {
      _ref
        ..invalidate(ordersProvider)
        ..invalidate(orderDetailsProvider(message.orderNumber!));
    }

    final text = [message.title, message.body].whereType<String>().join('\n');
    if (text.isEmpty) return;
    _messenger.currentState
      ?..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(text),
        duration: const Duration(seconds: 6),
        action: SnackBarAction(
          label: lookupAppLocalizations(_ref.read(localeProvider)).view,
          onPressed: () => _open(message),
        ),
      ));
  }

  void _open(PushMessage message) {
    if (_ref.read(sessionProvider) == null) return;
    final orderNumber = message.orderNumber;
    final seminarId = message.seminarId;
    _router.push(
      orderNumber != null
          ? AppRoutes.order(orderNumber)
          : seminarId != null
              ? AppRoutes.seminar(seminarId)
              : AppRoutes.notifications,
    );
  }
}
