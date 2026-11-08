import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/locale/locale_provider.dart';
import '../core/security/app_lock.dart';
import '../core/theme/app_theme.dart';
import '../features/content/content_providers.dart';
import '../features/notifications/data/api_notifications_repository.dart';
import '../features/scan/scan_providers.dart';
import '../l10n/app_texts_delegate.dart';
import '../l10n/l10n.dart';
import 'push_coordinator.dart';
import 'router.dart';

/// How long the app may sit in the background before it locks again.
const relockAfter = Duration(minutes: 2);

class MaqamApp extends ConsumerStatefulWidget {
  const MaqamApp({super.key});

  @override
  ConsumerState<MaqamApp> createState() => _MaqamAppState();
}

class _MaqamAppState extends ConsumerState<MaqamApp> with WidgetsBindingObserver {
  DateTime? _backgroundedAt;

  // Push banners are shown from outside any screen, so they need the messenger itself.
  final _messengerKey = GlobalKey<ScaffoldMessengerState>();
  late final PushCoordinator _push = PushCoordinator(ref, _messengerKey);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _push.start();
    Future.microtask(() {
      ref.read(appUpdateProvider.notifier).check();
      ref.read(scanQueueProvider.notifier).sync();
      ref.read(appTextsProvider.notifier).refresh();
    });
  }

  @override
  void dispose() {
    _push.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused) {
      _backgroundedAt ??= DateTime.now();
    } else if (state == AppLifecycleState.resumed) {
      // Coming back is the likeliest moment the connection is back too.
      ref.read(scanQueueProvider.notifier).sync();
      // Orders may have moved on while away, and a push is not guaranteed to have said so
      ref.invalidate(notificationsProvider);
      ref.read(appTextsProvider.notifier).refresh();
      final since = _backgroundedAt;
      _backgroundedAt = null;
      if (since != null && DateTime.now().difference(since) >= relockAfter) {
        ref.read(appLockProvider.notifier).lock();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      onGenerateTitle: (context) => context.l10n.appName,
      debugShowCheckedModeBanner: false,
      scaffoldMessengerKey: _messengerKey,
      theme: AppTheme.light(),
      locale: ref.watch(localeProvider),
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: AppTextsDelegate.all(ref.watch(appTextsProvider)),
      routerConfig: ref.watch(routerProvider),
    );
  }
}
