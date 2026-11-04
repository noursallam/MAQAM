import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../core/locale/locale_provider.dart';
import '../core/network/api_exception.dart';
import '../core/push/push_messaging.dart';
import '../core/security/app_lock.dart';
import '../core/session/account_status.dart';
import '../core/network/api_error_message.dart';
import '../core/security/biometric_authenticator.dart';
import '../core/session/auth_session.dart';
import '../core/session/quick_sign_in.dart';
import '../core/session/session_provider.dart';
import '../features/account/data/api_account_repository.dart';
import '../features/account/presentation/delete_account_screen.dart';
import '../features/account/presentation/merchant_change_screen.dart';
import '../features/account/presentation/suspended_screen.dart';
import '../features/addresses/presentation/addresses_screen.dart';
import '../features/app_lock/presentation/biometric_setup_screen.dart';
import '../features/app_lock/presentation/pin_setup_screen.dart';
import '../features/app_lock/presentation/unlock_screen.dart';
import '../features/auth/data/api_auth_repository.dart';
import '../features/auth/data/phone_change_repository.dart';
import '../features/auth/domain/auth_repository.dart';
import '../features/auth/domain/whatsapp_challenge.dart';
import '../features/auth/presentation/account_setup_screen.dart';
import '../features/auth/presentation/bind_merchant_screen.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/otp_screen.dart';
import '../features/auth/presentation/password_screen.dart';
import '../features/auth/presentation/send_code_screen.dart';
import '../features/account/presentation/account_screen.dart';
import '../features/cart/domain/cart.dart';
import '../features/cart/presentation/cart_screen.dart';
import '../features/cart/presentation/checkout_screen.dart';
import '../features/cart/presentation/order_placed_screen.dart';
import '../features/cart/presentation/payment_screen.dart';
import '../features/catalog/presentation/product_screen.dart';
import '../features/catalog/presentation/store_screen.dart';
import '../features/content/content_providers.dart';
import '../features/content/data/api_content_repository.dart';
import '../features/content/domain/content.dart';
import '../features/content/presentation/assistant_screen.dart';
import '../features/content/presentation/legal_screen.dart';
import '../features/content/presentation/update_required_screen.dart';
import '../features/home/presentation/home_screen.dart';
import '../features/loyalty/presentation/leaderboard_screen.dart';
import '../features/loyalty/presentation/rewards_screen.dart';
import '../features/loyalty/presentation/wallet_screen.dart';
import '../features/loyalty/presentation/wheel_screen.dart';
import '../features/merchant/data/api_merchant_repository.dart';
import '../features/merchant/merchant_providers.dart';
import '../features/merchant/presentation/deduct_points_screen.dart';
import '../features/merchant/presentation/merchant_dashboard_screen.dart';
import '../features/merchant/presentation/merchant_scans_screen.dart';
import '../features/merchant/presentation/merchant_screen.dart';
import '../features/merchant/presentation/merchant_transactions_screen.dart';
import '../features/merchant/presentation/technician_detail_screen.dart';
import '../features/notifications/presentation/notifications_screen.dart';
import '../features/onboarding/presentation/onboarding_screen.dart';
import '../features/orders/presentation/orders_screen.dart';
import '../features/scan/domain/scan.dart';
import '../features/scan/presentation/pending_scans_screen.dart';
import '../features/seminars/presentation/seminar_screen.dart';
import '../features/scan/presentation/scan_screen.dart';
import '../features/scan/scan_providers.dart';
import '../l10n/l10n.dart';
import 'main_shell.dart';

abstract final class AppRoutes {
  static const onboarding = '/';
  static const login = '/login';
  static const password = '/login/password';
  static const sendCode = '/login/send-code';
  static const otp = '/login/otp';
  static const accountSetup = '/account-setup';
  static const passwordReset = '/password-reset';
  static const bindMerchant = '/bind-merchant';
  static const biometricSetup = '/biometric-setup';
  static const pinSetup = '/pin-setup';
  static const unlock = '/unlock';
  // Back on a phone that remembers its customer: a touch instead of number and password
  static const signInAgain = '/sign-in-again';
  // Typing the number even though the phone could sign in on its own
  static const loginManually = '/login?manual=1';
  static const home = '/home';
  static const store = '/store';
  static const wallet = '/wallet';
  static const account = '/account';
  static const cart = '/cart';
  static const checkout = '/checkout';
  static const payment = '/payment';
  static const orderPlaced = '/order-placed';
  static const scan = '/scan';
  static const orders = '/orders';
  static const notifications = '/notifications';
  static const rewards = '/rewards';
  static const wheel = '/wheel';
  static const leaderboard = '/leaderboard';
  static const assistant = '/assistant';
  static const legal = '/legal';
  static const addresses = '/account/addresses';
  static const changePhone = '/account/phone';
  static const deleteAccount = '/account/delete';
  static const changeMerchant = '/account/change-merchant';
  static const merchant = '/merchant';
  static const merchantDashboard = '/merchant-dashboard';
  static const merchantTransactions = '/merchant-dashboard/transactions';
  static const merchantScans = '/merchant/scans';

  static String technicianDetail(int id) => '/merchant-dashboard/technician/$id';
  static String technicianDeduct(int id) => '/merchant-dashboard/technician/$id/deduct';
  static const pendingScans = '/scan/pending';
  static const update = '/update';
  static const suspended = '/suspended';

  static String order(String number) => '/orders/$number';
  static const scanResult = '/scan/result';

  static String product(int id) => '/product/$id';

  static String seminar(int id) => '/seminar/$id';

  /// Reachable while the app is locked: the lock itself and signing in again.
  static const _openWhileLocked = {unlock, signInAgain, login, password, sendCode, otp, legal, bindMerchant};
}

typedef _SendCodeArgs = ({String phone, SignInPurpose purpose});

/// Marks the sign-in-again screen opened by signing out, which waits for a tap.
const _afterSignOut = 'after-sign-out';
typedef _PlacedArgs = ({PlacedOrder order, PaymentState? payment});

final routerProvider = Provider<GoRouter>((ref) {
  Future<void> signOut() async {
    try {
      await ref.read(authRepositoryProvider).signOut(pushToken: ref.read(pushTokenProvider));
    } on ApiException {
      // The token may already be gone, or we are offline; sign out locally anyway.
    }
    await ref.read(scanQueueProvider.notifier).clear();
    await ref.read(sessionProvider.notifier).end();
  }

  // Keeps the name and number the app shows in step with the account.
  Future<void> updateSession({String? fullName, String? phone}) async {
    final session = ref.read(sessionProvider);
    if (session == null) return;
    await ref.read(sessionProvider.notifier).start(AuthSession(
          token: session.token,
          expiresAt: session.expiresAt,
          fullName: fullName ?? session.fullName,
          phone: phone ?? session.phone,
          boundMerchantId: session.boundMerchantId,
          boundMerchantName: session.boundMerchantName,
          governorate: session.governorate,
          area: session.area,
        ));
    final credential = ref.read(quickSignInProvider);
    if (credential != null) {
      await ref.read(quickSignInProvider.notifier).save(QuickSignInCredential(
            secret: credential.secret,
            name: fullName ?? credential.name,
            phone: phone ?? credential.phone,
          ));
    }
  }

  // Once the app is locked with fingerprint, Face ID or a PIN, the phone may sign back in
  // with that instead of number and password.
  Future<void> rememberDevice() async {
    final session = ref.read(sessionProvider);
    if (session == null || !ref.read(appLockProvider).enabled) return;
    try {
      final secret = await ref.read(authRepositoryProvider).enableQuickSignIn();
      await ref.read(quickSignInProvider.notifier).save(
            QuickSignInCredential(secret: secret, phone: session.phone, name: session.fullName),
          );
    } on ApiException {
      // Offline: tried again at the next launch.
    }
  }

  Future<void> forgetDevice() async {
    if (ref.read(sessionProvider) != null && ref.read(quickSignInProvider) != null) {
      try {
        await ref.read(authRepositoryProvider).disableQuickSignIn();
      } on ApiException {
        // The secret is erased here anyway, so it can no longer be used.
      }
    }
    await ref.read(quickSignInProvider.notifier).clear();
  }

  // Someone else is taking the phone: forget its customer completely.
  Future<void> forgetCustomer() async {
    await forgetDevice();
    await ref.read(appLockProvider.notifier).forget();
  }

  late final GoRouter router;

  // After the account has what it needs: offer biometrics once, then the app.
  // If the user has no bound merchant, offer the binding screen before the app.
  Future<void> finishSignIn() async {
    final appLock = ref.read(appLockProvider.notifier);
    if (appLock.shouldOfferSetup) {
      router.go(AppRoutes.biometricSetup, extra: await appLock.isAvailable());
      return;
    }
    final session = ref.read(sessionProvider);
    if (session != null && !session.isBoundToMerchant) {
      router.go(AppRoutes.bindMerchant);
      return;
    }
    router.go(AppRoutes.home);
  }

  Future<void> afterLockSetup() async {
    final session = ref.read(sessionProvider);
    if (session != null && !session.isBoundToMerchant) {
      router.go(AppRoutes.bindMerchant);
      return;
    }
    router.go(AppRoutes.home);
  }

  // Signs in with the phone's own secret, after the person proved themselves on it.
  Future<bool> signInAgain(BuildContext context) async {
    final credential = ref.read(quickSignInProvider);
    if (credential == null) {
      router.go(AppRoutes.loginManually);
      return false;
    }
    try {
      final signIn = await ref.read(authRepositoryProvider).quickSignIn(credential.secret);
      await ref.read(sessionProvider.notifier).start(signIn.session);
      await ref.read(appLockProvider.notifier).unlockAfterSignIn();
      router.go(AppRoutes.home);
      return true;
    } on ApiException catch (e) {
      if (e.code == ApiErrorCodes.quickLoginInvalid) {
        // Signed out everywhere, password changed elsewhere, or the account is gone
        await ref.read(quickSignInProvider.notifier).clear();
        await ref.read(appLockProvider.notifier).forget();
        router.go(AppRoutes.loginManually);
      }
      if (context.mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(apiErrorMessage(context, e))));
      }
      return false;
    }
  }

  Future<void> onSignedIn(SignIn signIn, SignInPurpose purpose) async {
    await ref.read(sessionProvider.notifier).start(signIn.session);
    await ref.read(appLockProvider.notifier).unlockAfterSignIn();
    if (!signIn.hasPassword) {
      router.go(AppRoutes.accountSetup);
    } else if (purpose == SignInPurpose.resetPassword) {
      router.go(AppRoutes.passwordReset);
    } else {
      await finishSignIn();
    }
  }

  // Scanning earns points, so it needs an account.
  void openScanner() {
    router.push(ref.read(sessionProvider) == null ? AppRoutes.login : AppRoutes.scan);
  }

  void openNotifications() {
    router.push(ref.read(sessionProvider) == null ? AppRoutes.login : AppRoutes.notifications);
  }

  void openOrders() {
    router.push(ref.read(sessionProvider) == null ? AppRoutes.login : AppRoutes.orders);
  }

  // Everything below belongs to an account; a guest is asked to sign in first.
  void openForCustomer(String path) {
    router.push(ref.read(sessionProvider) == null ? AppRoutes.login : path);
  }

  String? needsSession(BuildContext context, GoRouterState state) =>
      ref.read(sessionProvider) == null ? AppRoutes.login : null;

  router = GoRouter(
    initialLocation: ref.read(sessionProvider) != null
        ? AppRoutes.home
        : ref.read(quickSignInProvider) != null
            ? AppRoutes.signInAgain
            : AppRoutes.onboarding,
    redirect: (context, state) {
      final path = state.uri.path;
      // A frozen account and an unsupported version each stop the whole app.
      if (ref.read(accountFrozenProvider)) {
        return path == AppRoutes.suspended ? null : AppRoutes.suspended;
      }
      if (ref.read(appUpdateProvider)?.required == true) {
        return path == AppRoutes.update ? null : AppRoutes.update;
      }
      if (path == AppRoutes.suspended || path == AppRoutes.update) return AppRoutes.home;
      final locked = ref.read(appLockProvider).locked;
      if (locked && !AppRoutes._openWhileLocked.contains(path)) return AppRoutes.unlock;
      if (!locked && path == AppRoutes.unlock) return AppRoutes.home;
      return null;
    },
    routes: [
      GoRoute(
        path: AppRoutes.onboarding,
        builder: (context, state) => OnboardingScreen(
          onStart: () => context.push(AppRoutes.login),
          onBrowseAsGuest: () => context.go(AppRoutes.home),
        ),
      ),
      GoRoute(
        path: AppRoutes.login,
        // A phone that remembers its customer offers a touch first; typing stays one tap away.
        redirect: (context, state) => ref.read(quickSignInProvider) != null &&
                state.uri.queryParameters['manual'] != '1'
            ? AppRoutes.signInAgain
            : null,
        builder: (context, state) => LoginScreen(
          onContinue: (phone) async {
            final hasPassword = await ref.read(authRepositoryProvider).hasPassword(phone);
            if (!context.mounted) return;
            if (hasPassword) {
              context.push(AppRoutes.password, extra: phone);
            } else {
              // No password yet, or a new number: WhatsApp proves it and registers it.
              context.push(AppRoutes.sendCode, extra: (phone: phone, purpose: SignInPurpose.signIn));
            }
          },
          onToggleLanguage: ref.read(localeProvider.notifier).toggle,
          onOpenTerms: () => context.push(AppRoutes.legal, extra: LegalPageSlug.terms),
          onOpenPrivacy: () => context.push(AppRoutes.legal, extra: LegalPageSlug.privacy),
        ),
      ),
      GoRoute(
        path: AppRoutes.password,
        // The phone number is passed in memory; a cold start here has none.
        redirect: (context, state) => state.extra is String ? null : AppRoutes.login,
        builder: (context, state) {
          final phone = state.extra! as String;
          return PasswordScreen(
            phone: phone,
            onSubmit: (password) async {
              final signIn = await ref
                  .read(authRepositoryProvider)
                  .signInWithPassword(phone: phone, password: password);
              await onSignedIn(signIn, SignInPurpose.signIn);
            },
            onForgot: () => context.push(
              AppRoutes.sendCode,
              extra: (phone: phone, purpose: SignInPurpose.resetPassword),
            ),
          );
        },
      ),
      GoRoute(
        path: AppRoutes.sendCode,
        redirect: (context, state) => state.extra is _SendCodeArgs ? null : AppRoutes.login,
        builder: (context, state) {
          final args = state.extra! as _SendCodeArgs;
          return SendCodeScreen(
            repository: _repositoryFor(ref, args.purpose),
            phone: args.phone,
            purpose: args.purpose,
            onOtpSent: (ticket) => context.pushReplacement(AppRoutes.otp, extra: ticket),
          );
        },
      ),
      GoRoute(
        path: AppRoutes.otp,
        redirect: (context, state) => state.extra is OtpTicket ? null : AppRoutes.login,
        builder: (context, state) {
          final ticket = state.extra! as OtpTicket;
          return OtpScreen(
            repository: _repositoryFor(ref, ticket.purpose),
            ticket: ticket,
            onSignedIn: (signIn) async {
              if (ticket.purpose != SignInPurpose.changePhone) {
                return onSignedIn(signIn, ticket.purpose);
              }
              await updateSession(phone: signIn.session.phone);
              if (!context.mounted) return;
              ScaffoldMessenger.of(context)
                ..hideCurrentSnackBar()
                ..showSnackBar(SnackBar(content: Text(context.l10n.changePhoneDone)));
              router.go(AppRoutes.account);
            },
            // Send-code was replaced by this screen, so back lands where it was opened from.
            onStartOver: () => context.pop(),
          );
        },
      ),
      GoRoute(
        path: AppRoutes.accountSetup,
        builder: (context, state) => AccountSetupScreen(
          askForName: true,
          onSubmit: ({fullName, required password, governorate, area}) async {
            await ref.read(authRepositoryProvider).completeProfile(
                  fullName: fullName!,
                  password: password,
                  governorate: governorate,
                  area: area,
                );
            final session = ref.read(sessionProvider);
            if (session != null) {
              await ref.read(sessionProvider.notifier).start(session.copyWith(
                    fullName: fullName,
                    governorate: governorate,
                    area: area,
                  ));
            }
            await finishSignIn();
          },
          onSkip: finishSignIn,
        ),
      ),
      GoRoute(
        path: AppRoutes.passwordReset,
        builder: (context, state) => AccountSetupScreen(
          askForName: false,
          onSubmit: ({fullName, required password, governorate, area}) async {
            await ref.read(authRepositoryProvider).setPassword(password);
            await finishSignIn();
          },
          onSkip: finishSignIn,
        ),
      ),
      GoRoute(
        path: AppRoutes.bindMerchant,
        builder: (context, state) => BindMerchantScreen(
          onSubmit: (merchantCode) async {
            try {
              final result = await ref.read(accountRepositoryProvider).bindMerchant(merchantCode);
              final session = ref.read(sessionProvider);
              if (session != null) {
                await ref.read(sessionProvider.notifier).start(session.copyWith(
                      boundMerchantId: result.id,
                      boundMerchantName: result.name,
                    ));
              }
            } on ApiException catch (e) {
              if (e.code != 'ALREADY_BOUND') rethrow;
            }
            router.go(AppRoutes.home);
          },
          onSkip: () => router.go(AppRoutes.home),
        ),
      ),
      GoRoute(
        path: AppRoutes.biometricSetup,
        builder: (context, state) => Consumer(
          builder: (context, screenRef, _) => BiometricSetupScreen(
            biometricsAvailable: state.extra == true,
            // Watched by this screen only: watching it in the router would rebuild the router
            kind: screenRef.watch(biometricKindProvider).value ?? BiometricKind.other,
            // `true`: once the PIN is saved, carry on into the app.
            onCreatePin: () => context.push(AppRoutes.pinSetup, extra: true),
            onEnable: () async {
              final enabled = await ref
                  .read(appLockProvider.notifier)
                  .enable(context.l10n.biometricEnableReason);
              if (enabled && context.mounted) await afterLockSetup();
              return enabled;
            },
            onSkip: () async {
              await ref.read(appLockProvider.notifier).decline();
              if (context.mounted) await afterLockSetup();
            },
          ),
        ),
      ),
      GoRoute(
        path: AppRoutes.pinSetup,
        builder: (context, state) => PinSetupScreen(
          onSave: (pin) async {
            await ref.read(appLockProvider.notifier).setPin(pin);
            if (state.extra == true) {
              await afterLockSetup();
            } else if (context.mounted) {
              // Opened from the account screen: return to it.
              context.pop();
            }
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.unlock,
        builder: (context, state) => Consumer(
          builder: (context, ref, _) {
            final session = ref.watch(sessionProvider);
            final appLock = ref.read(appLockProvider.notifier);
            return UnlockScreen(
              name: session?.fullName,
              phone: session?.phone,
              kind: ref.watch(biometricKindProvider).value ?? BiometricKind.other,
              usesPin: ref.watch(appLockProvider).usesPin,
              pinAttemptsLeft: appLock.pinAttemptsLeft,
              onPin: (pin) async {
                final attempt = await appLock.unlockWithPin(pin);
                if (attempt.unlocked && context.mounted) context.go(AppRoutes.home);
                return attempt;
              },
              onUnlock: () async {
                final unlocked =
                    await ref.read(appLockProvider.notifier).unlock(context.l10n.unlockReason);
                if (unlocked && context.mounted) context.go(AppRoutes.home);
                return unlocked;
              },
              onUseWhatsapp: () => session?.phone == null
                  ? context.push(AppRoutes.login)
                  : context.push(
                      AppRoutes.sendCode,
                      extra: (phone: session!.phone!, purpose: SignInPurpose.signIn),
                    ),
              onDifferentAccount: () async {
                await forgetCustomer();
                // Signing out returns to the start; go one step further to phone entry.
                await signOut();
                router.push(AppRoutes.loginManually);
              },
            );
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.signInAgain,
        redirect: (context, state) => ref.read(sessionProvider) != null
            ? AppRoutes.home
            : ref.read(quickSignInProvider) == null
                ? AppRoutes.loginManually
                : null,
        builder: (context, state) => Consumer(
          builder: (context, ref, _) {
            final credential = ref.watch(quickSignInProvider);
            final appLock = ref.read(appLockProvider.notifier);
            return UnlockScreen(
              name: credential?.name,
              phone: credential?.phone,
              promptOnOpen: state.extra != _afterSignOut,
              kind: ref.watch(biometricKindProvider).value ?? BiometricKind.other,
              usesPin: ref.watch(appLockProvider).usesPin,
              pinAttemptsLeft: appLock.pinAttemptsLeft,
              onPin: (pin) async {
                final attempt = await appLock.unlockWithPin(pin);
                if (attempt.unlocked && context.mounted) await signInAgain(context);
                return attempt;
              },
              onUnlock: () async {
                if (!await appLock.unlock(context.l10n.unlockReason)) return false;
                return context.mounted && await signInAgain(context);
              },
              onUseWhatsapp: () => credential?.phone == null
                  ? context.push(AppRoutes.loginManually)
                  : context.push(
                      AppRoutes.sendCode,
                      extra: (phone: credential!.phone!, purpose: SignInPurpose.signIn),
                    ),
              onDifferentAccount: () async {
                await forgetCustomer();
                router.go(AppRoutes.loginManually);
              },
              onBrowseAsGuest: () => context.go(AppRoutes.home),
            );
          },
        ),
      ),
      GoRoute(
        path: '/product/:id',
        redirect: (context, state) =>
            int.tryParse(state.pathParameters['id'] ?? '') == null ? AppRoutes.store : null,
        builder: (context, state) => ProductScreen(
          // A key per product, so opening a similar one starts from a clean screen.
          key: ValueKey(state.pathParameters['id']),
          productId: int.parse(state.pathParameters['id']!),
          onOpenProduct: (id) => context.push(AppRoutes.product(id)),
          onOpenCart: () => context.push(AppRoutes.cart),
          onSignIn: () => context.push(AppRoutes.login),
        ),
      ),
      GoRoute(
        path: AppRoutes.cart,
        builder: (context, state) => CartScreen(
          onCheckout: () => context.push(AppRoutes.checkout),
          onBrowse: () => context.go(AppRoutes.store),
          onSignIn: () => context.push(AppRoutes.login),
        ),
      ),
      GoRoute(
        path: AppRoutes.checkout,
        builder: (context, state) => CheckoutScreen(
          onPlaced: (order) async {
            final paymentUrl = order.paymentUrl;
            if (paymentUrl == null) {
              router.go(AppRoutes.orderPlaced, extra: (order: order, payment: null));
              return;
            }
            if (ref.read(inAppPaymentSupportedProvider)) {
              router.go(AppRoutes.payment, extra: order);
              return;
            }
            // No web view on this platform: the gateway's page opens in the browser,
            // and the app cannot tell when the customer has finished there.
            var opened = false;
            try {
              opened = await launchUrl(paymentUrl, mode: LaunchMode.externalApplication);
            } on Exception {
              opened = false;
            }
            if (!opened && context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(context.l10n.checkoutPaymentOpenFailed)),
              );
            }
            router.go(AppRoutes.orderPlaced, extra: (order: order, payment: PaymentState.pending));
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.payment,
        redirect: (context, state) =>
            state.extra is PlacedOrder && (state.extra! as PlacedOrder).paymentUrl != null
                ? null
                : AppRoutes.home,
        builder: (context, state) {
          final order = state.extra! as PlacedOrder;
          return PaymentScreen(
            order: order,
            onDone: (payment) =>
                router.go(AppRoutes.orderPlaced, extra: (order: order, payment: payment)),
          );
        },
      ),
      GoRoute(
        path: AppRoutes.orderPlaced,
        redirect: (context, state) => state.extra is _PlacedArgs ? null : AppRoutes.home,
        builder: (context, state) {
          final args = state.extra! as _PlacedArgs;
          final order = args.order;
          return OrderPlacedScreen(
            orderNumber: order.orderNumber,
            payment: args.payment,
            // Home goes underneath, so "back" from the order does not return here.
            onTrack: () {
              router.go(AppRoutes.home);
              router.push(AppRoutes.order(order.orderNumber));
            },
            onHome: () => context.go(AppRoutes.home),
          );
        },
      ),
      GoRoute(
        path: AppRoutes.scan,
        redirect: (context, state) => ref.read(sessionProvider) == null ? AppRoutes.login : null,
        builder: (context, state) => ScanScreen(
          onScanned: (result) => context.pushReplacement(AppRoutes.scanResult, extra: result),
          onOpenPending: () => context.push(AppRoutes.pendingScans),
        ),
      ),
      GoRoute(
        path: '/seminar/:id',
        redirect: (context, state) =>
            int.tryParse(state.pathParameters['id'] ?? '') == null ? AppRoutes.home : null,
        builder: (context, state) => SeminarScreen(
          key: ValueKey(state.pathParameters['id']),
          seminarId: int.parse(state.pathParameters['id']!),
          onSignIn: () => context.push(AppRoutes.login),
        ),
      ),
      GoRoute(
        path: AppRoutes.pendingScans,
        redirect: needsSession,
        builder: (context, state) => const PendingScansScreen(),
      ),
      GoRoute(
        path: AppRoutes.rewards,
        redirect: needsSession,
        builder: (context, state) => RewardsScreen(
          onOpenCart: () => context.push(AppRoutes.cart),
          onOpenWheel: () => context.pushReplacement(AppRoutes.wheel),
        ),
      ),
      GoRoute(
        path: AppRoutes.wheel,
        redirect: needsSession,
        builder: (context, state) => WheelScreen(
          onOpenRewards: () => context.pushReplacement(AppRoutes.rewards),
        ),
      ),
      GoRoute(
        path: AppRoutes.leaderboard,
        builder: (context, state) => const LeaderboardScreen(),
      ),
      GoRoute(
        path: AppRoutes.assistant,
        redirect: needsSession,
        builder: (context, state) => AssistantScreen(repository: ref.read(contentRepositoryProvider)),
      ),
      GoRoute(
        path: AppRoutes.legal,
        builder: (context, state) => LegalScreen(
          initial: state.extra is LegalPageSlug ? state.extra! as LegalPageSlug : LegalPageSlug.terms,
        ),
      ),
      GoRoute(
        path: AppRoutes.addresses,
        redirect: needsSession,
        builder: (context, state) => const AddressesScreen(),
      ),
      GoRoute(
        path: AppRoutes.changeMerchant,
        redirect: needsSession,
        builder: (context, state) => MerchantChangeScreen(
          onSubmit: (merchantCode) async {
            await ref.read(accountRepositoryProvider).requestMerchantChange(merchantCode);
            if (context.mounted) {
              ScaffoldMessenger.of(context)
                ..hideCurrentSnackBar()
                ..showSnackBar(SnackBar(content: Text(context.l10n.merchantChangeRequestSent)));
              context.pop();
            }
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.merchant,
        redirect: needsSession,
        builder: (context, state) => MerchantScreen(
          onViewAllScans: () => context.push(AppRoutes.merchantScans),
        ),
      ),
      GoRoute(
        path: AppRoutes.merchantScans,
        redirect: needsSession,
        builder: (context, state) => const MerchantScansScreen(),
      ),
      GoRoute(
        path: AppRoutes.merchantDashboard,
        redirect: needsSession,
        builder: (context, state) => MerchantDashboardScreen(
          onTechnician: (id) => context.push(AppRoutes.technicianDetail(id)),
          onTransactions: () => context.push(AppRoutes.merchantTransactions),
          onSettings: () => context.push(AppRoutes.merchant),
        ),
      ),
      GoRoute(
        path: '/merchant-dashboard/technician/:id',
        redirect: (context, state) =>
            int.tryParse(state.pathParameters['id'] ?? '') == null ? AppRoutes.merchantDashboard : needsSession(context, state),
        builder: (context, state) {
          final id = int.parse(state.pathParameters['id']!);
          return TechnicianDetailScreen(
            technicianId: id,
            onDeductPoints: () => context.push(AppRoutes.technicianDeduct(id)),
          );
        },
      ),
      GoRoute(
        path: '/merchant-dashboard/technician/:id/deduct',
        redirect: (context, state) =>
            int.tryParse(state.pathParameters['id'] ?? '') == null ? AppRoutes.merchantDashboard : needsSession(context, state),
        builder: (context, state) {
          final id = int.parse(state.pathParameters['id']!);
          return DeductPointsScreen(
            technicianName: '',
            onSubmit: ({required amount, required reason}) async {
              await ref.read(merchantRepositoryProvider).deductPoints(id, amount: amount, reason: reason);
              ref.invalidate(technicianDetailProvider(id));
              ref.invalidate(merchantTransactionsProvider);
              if (context.mounted) {
                ScaffoldMessenger.of(context)
                  ..hideCurrentSnackBar()
                  ..showSnackBar(SnackBar(content: Text(context.l10n.merchantDeductSuccess)));
                context.pop();
              }
            },
          );
        },
      ),
      GoRoute(
        path: AppRoutes.merchantTransactions,
        redirect: needsSession,
        builder: (context, state) => const MerchantTransactionsScreen(),
      ),
      GoRoute(
        path: AppRoutes.changePhone,
        redirect: needsSession,
        builder: (context, state) => LoginScreen(
          changingPhone: true,
          onContinue: (phone) async {
            context.push(
              AppRoutes.sendCode,
              extra: (phone: phone, purpose: SignInPurpose.changePhone),
            );
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.deleteAccount,
        redirect: needsSession,
        builder: (context, state) => DeleteAccountScreen(
          onDelete: () async {
            await ref.read(accountRepositoryProvider).deleteAccount();
            await ref.read(scanQueueProvider.notifier).clear();
            // The server has dropped this phone's quick sign-in with the account
            await ref.read(quickSignInProvider.notifier).clear();
            await ref.read(appLockProvider.notifier).forget();
            if (context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(context.l10n.deleteDone)),
              );
            }
            // Ending the session returns to the start.
            await ref.read(sessionProvider.notifier).end();
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.update,
        builder: (context, state) {
          final status = ref.read(appUpdateProvider);
          return status == null ? const SizedBox.shrink() : UpdateRequiredScreen(status: status);
        },
      ),
      GoRoute(
        path: AppRoutes.suspended,
        builder: (context, state) => SuspendedScreen(
          onSignOut: () async {
            ref.read(accountFrozenProvider.notifier).clear();
            await signOut();
            router.go(AppRoutes.onboarding);
          },
        ),
      ),
      GoRoute(
        path: AppRoutes.scanResult,
        redirect: (context, state) => state.extra is ScanResult ? null : AppRoutes.home,
        builder: (context, state) => ScanResultScreen(
          result: state.extra! as ScanResult,
          onScanAnother: () => context.pushReplacement(AppRoutes.scan),
          onOpenWallet: () => context.go(AppRoutes.wallet),
        ),
      ),
      GoRoute(
        path: AppRoutes.orders,
        redirect: (context, state) => ref.read(sessionProvider) == null ? AppRoutes.login : null,
        builder: (context, state) => OrdersScreen(
          onOpenOrder: (number) => context.push(AppRoutes.order(number)),
          onBrowse: () => context.go(AppRoutes.store),
        ),
        routes: [
          GoRoute(
            path: ':number',
            builder: (context, state) =>
                OrderDetailsScreen(orderNumber: state.pathParameters['number']!),
          ),
        ],
      ),
      GoRoute(
        path: AppRoutes.notifications,
        redirect: (context, state) => ref.read(sessionProvider) == null ? AppRoutes.login : null,
        builder: (context, state) => NotificationsScreen(
          onOpenOrder: (number) => context.push(AppRoutes.order(number)),
          onOpenSeminar: (id) => context.push(AppRoutes.seminar(id)),
        ),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            MainShell(navigationShell: navigationShell, onScan: openScanner),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutes.home,
              builder: (context, state) => HomeScreen(
                onSignIn: () => context.push(AppRoutes.login),
                onOpenStore: () => context.go(AppRoutes.store),
                onOpenProduct: (id) => context.push(AppRoutes.product(id)),
                onScan: openScanner,
                onOrders: openOrders,
                onNotifications: openNotifications,
                onChat: () => openForCustomer(AppRoutes.assistant),
                onWheel: () => openForCustomer(AppRoutes.wheel),
                onRewards: () => openForCustomer(AppRoutes.rewards),
                onLeaderboard: () => context.push(AppRoutes.leaderboard),
                onOpenSeminar: (id) => context.push(AppRoutes.seminar(id)),
              ),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutes.store,
              builder: (context, state) => StoreScreen(
                onOpenProduct: (id) => context.push(AppRoutes.product(id)),
                onOpenCart: () => context.push(AppRoutes.cart),
              ),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutes.wallet,
              builder: (context, state) => WalletScreen(
                onSignIn: () => context.push(AppRoutes.login),
                onScan: openScanner,
                onRewards: () => openForCustomer(AppRoutes.rewards),
                onWheel: () => openForCustomer(AppRoutes.wheel),
              ),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutes.account,
              builder: (context, state) => AccountScreen(
                onSignIn: () => context.push(AppRoutes.login),
                onSignOut: signOut,
                onCreatePin: () => context.push(AppRoutes.pinSetup),
                onOrders: openOrders,
                onNotifications: openNotifications,
                onEditName: (name) async {
                  await ref.read(accountRepositoryProvider).updateName(name);
                  await updateSession(fullName: name);
                },
                onMerchant: () => openForCustomer(AppRoutes.merchant),
                onChangeMerchant: () => openForCustomer(AppRoutes.changeMerchant),
                onMerchantDashboard: () => openForCustomer(AppRoutes.merchantDashboard),
                onAddresses: () => openForCustomer(AppRoutes.addresses),
                onPendingScans: () => openForCustomer(AppRoutes.pendingScans),
                onChangePhone: () => openForCustomer(AppRoutes.changePhone),
                onHelp: () => openForCustomer(AppRoutes.assistant),
                onLegal: () => context.push(AppRoutes.legal),
                onDeleteAccount: () => openForCustomer(AppRoutes.deleteAccount),
              ),
            ),
          ]),
        ],
      ),
    ],
  );

  // Signing out, or the server rejecting the token, returns to the start, or straight
  // to signing back in with a touch on a phone that remembers its customer.
  ref.listen(sessionProvider, (previous, next) {
    if (previous != null && next == null) {
      if (ref.read(quickSignInProvider) != null) {
        router.go(AppRoutes.signInAgain, extra: _afterSignOut);
      } else {
        router.go(AppRoutes.onboarding);
      }
    }
  });
  ref.listen(accountFrozenProvider, (previous, next) {
    if (next) router.go(AppRoutes.suspended);
  });
  ref.listen(appUpdateProvider, (previous, next) {
    if (next?.required == true) router.go(AppRoutes.update);
  });
  // Re-locking (after time in the background) covers whatever was on screen.
  ref.listen(appLockProvider, (previous, next) {
    if (next.locked && previous?.locked != true) router.go(AppRoutes.unlock);
    // Turning the lock on lets this phone sign back in with it; turning it off stops that.
    if (next.enabled && previous?.enabled != true) rememberDevice();
    if (!next.enabled && previous?.enabled == true) forgetDevice();
  });
  // Phones locked before quick sign-in existed are registered at the next launch.
  Future.microtask(() {
    if (ref.read(quickSignInProvider) == null) rememberDevice();
  });
  ref.onDispose(router.dispose);

  return router;
});

/// The sign-in steps drive changing the number too, against its own endpoints.
AuthRepository _repositoryFor(Ref ref, SignInPurpose purpose) => purpose == SignInPurpose.changePhone
    ? ref.read(phoneChangeRepositoryProvider)
    : ref.read(authRepositoryProvider);
