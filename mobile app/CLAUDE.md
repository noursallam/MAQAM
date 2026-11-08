# MAQAM mobile app — working rules for AI agents

Flutter client for the MAQAM store (electrical products, loyalty points, QR scanning).
The backend is the Laravel project one folder up; this app only talks to its `/api/v1`.
Read this file before changing anything here, and follow it over your own defaults.

## Ground rules

- Arabic is the primary language and the UI is RTL. English is the second locale.
- One feature at a time. Do not scaffold screens, layers, or packages that the current task does not need.
- Never add a dependency without saying why in your reply. Prefer what is already in `pubspec.yaml`.
- Before calling work done: `flutter analyze` is clean, `flutter test` passes, and you say plainly what you did not verify (for example, "not run on a real device").
- This folder is ignored by the parent repo's git. Do not commit it there.

## Architecture

Feature-first, three layers per feature. Dependencies point inward only:
`presentation → domain ← data`.

```
lib/
  main.dart                 # bootstrap only
  app/                      # MaterialApp, router, theme, localization wiring
  core/
    network/                # API client, envelope parsing, error mapping, auth header
    theme/                  # colors, typography, spacing, radii (design tokens)
    widgets/                # shared, feature-agnostic widgets
    utils/                  # formatters (money, dates), extensions
  features/
    <feature>/
      data/                 # DTOs (fromJson), remote data sources, repository implementations
      domain/               # entities (plain immutable classes), repository interfaces
      presentation/         # screens, widgets, controllers/notifiers
```

Features, matching the API: `auth`, `catalog`, `cart`, `checkout`, `orders`, `loyalty`
(wallet, ranks, wheel), `scan` (QR), `merchant`, `notifications`, `profile`, `content`.

Rules:

- **Widgets never call the API or parse JSON.** They read state from a controller and call its methods.
- **Domain has no Flutter and no `http` imports.** Entities are immutable (`final` fields, `const` constructors).
- **DTOs live in `data/` and map to entities.** JSON keys never leak past the data layer.
- **Repositories return typed results or throw a typed `AppException`** (network, unauthorized, validation, server). Screens show messages from the exception type, never raw exception text.
- A feature may import `core/` and its own folders. From another feature it may import only `domain/` and the feature's `<feature>_providers.dart`. The one exception is `features/home`, which exists to compose other features and may also use their widgets.
- Routing: **go_router** (`app/router.dart`). Screens do not navigate by route name; they take callbacks and the router wires them.
- State management: **Riverpod** (v3, `Notifier`/`Provider`; no code generation). Providers hold app-wide state (`sessionProvider`, `localeProvider`) and wire dependencies (`apiClientProvider`, `*RepositoryProvider`, declared next to the implementation they build). Server data that a screen only reads goes in a `FutureProvider` in `<feature>_providers.dart` and is rendered with `AsyncView`; it must `ref.watch(localeProvider)` when the response is localized and `ref.watch(sessionProvider)` when it belongs to the account. Multi-step flows keep their logic in a plain `ChangeNotifier` controller that takes its repository in the constructor, is created by the screen's `State`, and is unit-tested with a fake repository; see `send_code_controller.dart`. Screens receive repositories and callbacks from `app/router.dart`. No `setState` for server data, auth, or cart.
- The app lock (`core/security/app_lock.dart`) guards the screen, not the token: the router redirects every route to `/unlock` while `appLockProvider` is locked, except the lock screen and sign-in. It locks at launch and after two minutes in the background, and signing out forgets the choice. The lock is either the device's biometrics or, on a device without a sensor (or by choice), a 4-digit PIN of the app's own: stored only as a salted hash, with five wrong tries counted on disk before the PIN stops working until a fresh WhatsApp sign-in. Every device is offered a lock once after signing in. Any new route is locked by default; add it to `AppRoutes._openWhileLocked` only if a locked-out customer must reach it.
- Cross-cutting state lives in `core/` (`core/session`, `core/locale`, `core/storage`) so features never import each other.
- Automatic provider retries are off (`ProviderScope(retry: ...)` in `main.dart`): a failed load shows its error with a retry button.
- No global mutable singletons. Dependencies are injected (constructor or provider) so they can be faked in tests.

Onboarding deliberately departs from design 01: the owner asked for a five-slide feature tour drawn in code, with no photo. Keep it that way.

Current state: built so far are onboarding, sign-in by password or WhatsApp code (02 to 04, plus the password and account-setup screens, which have no design and follow the same layout), biometric unlock (27, 28), and the four tabs behind the bottom bar: home (07), store (13), wallet (10) and account (20). The purchase path is built too: product (14), cart (15), checkout (16) and the confirmation (17); online payment opens the gateway's page inside the app on Android and iOS (`payment_screen.dart`, behind `paymentPageBuilderProvider` so tests replace the web view) and asks the server how it went when the customer returns; on other platforms it opens in the browser. Checkout takes the default saved address or a typed one; the address book (21) manages them. Notifications are built (22): the list with unread marks, the dot on the bell, and tapping one to open its order. Push arrives through Firebase Cloud Messaging (`core/push/push_messaging.dart`, wired up in `app/push_coordinator.dart`): the device registers when a customer signs in and unregisters on sign-out, a push that arrives while the app is open shows a banner, and tapping one opens the order or the list. Orders are built (18, 19): the list with its filters, one order with its timeline, and cancelling before shipping. Scanning a pack's code is built as well (08, 09): the camera sits behind `cameraViewBuilderProvider` so tests replace it. Without a connection a scan goes into an on-device queue (29, 30; `scanQueueProvider`), uploaded through `qr/sync-batch` at launch, on returning to the app, after signing in, on opening the scanner, and by hand; signing out on purpose or deleting the account forgets it, a token running out does not. Also built: rewards (11) with "use in cart", the lucky wheel (12, drawn in code, landing on the server's `slice_index`), the address book (21), joining as a merchant and the merchant dashboard (23, 24; the logo comes from `image_picker` behind `logoPickerProvider`), the assistant (25), deleting the account (26), the forced-update gate (05, from `GET /app/config` at launch) and the suspended-account screen (06, on any `ACCOUNT_FROZEN_FRAUD` or a scan answered with `account_frozen`). The legal pages and editing the name and phone number have no design: the legal pages render the API's simple HTML natively, the name is edited in a dialog, and changing the number reuses the WhatsApp sign-in steps against `/user/phone/*` (`PhoneChangeRepository`). Not built because the API has no data for it: the "recent scans with your code" list on design 24. Lists load their first page only; paging is still to do.

## Backend contract

- Base URL: `https://maqam-eg.com/api/v1`. Keep it in one place, overridable with `--dart-define=API_BASE_URL=...`.
- Every response is `{ "success": bool, "message": string, "data": ..., "meta"?: {current_page, last_page, per_page, total} }`. Parse the envelope once, in `core/network`.
- Sign-in has three doors, all ending in the same bearer token (secure storage only, never plain preferences or logs):
  1. **Password.** After the phone number, `POST /auth/methods` says whether the number has one; if so `POST /auth/login`.
  2. **WhatsApp code** (`/auth/whatsapp/start|check|verify`): for a new number, a number without a password, and a forgotten password. It is the root credential and is always available.
  3. **Biometrics**: a local lock on a saved session, not a server sign-in.
- `user.has_password` is false until the customer has chosen a password. After a WhatsApp sign-in the app then shows the setup screen (`POST /user/complete-profile`, name and password); after a "forgot password" sign-in it asks for a new password (`PUT /user/password`). Both can be skipped and are asked again next time.
- Only a session opened with a WhatsApp code may set a password without the old one. The whole post-sign-in sequence lives in `onSignedIn` and `finishSignIn` in `app/router.dart`.
- The server keeps one token per `device_name`, so it must stay unique per install (`ApiAuthRepository._deviceName`).
- Errors carry `error_code` (for example `INVALID_OTP`, `CHALLENGE_EXPIRED`) and a `message` already localized through the `X-App-Locale` header; show that message with `apiErrorMessage`.
- On HTTP 401, clear the session and return to sign-in.
- Lists are paginated. Implement paging with `meta`, do not raise `per_page` to fake it.
- Show Western digits everywhere. Dates go through `dayLabel` in `core/widgets/shop_widgets.dart`, which converts the Arabic-Indic digits the date formatter produces.
- Money arrives as strings (`"75.00"`). A price of `0` means "price on request"; never display it as zero.
- The source of truth for endpoints is the Laravel code: `routes/api.php` and `app/Http/Controllers/Api/V1/`, with response shapes in `app/Support/ApiPresenter.php`. Read those instead of guessing a field name.

## Code style

- `flutter_lints` stays on; fix warnings, do not ignore them. No `// ignore:` without a reason on the same line.
- Small widgets. If a `build` method passes ~80 lines or nests more than about four levels, extract a private widget class (not a helper method returning `Widget`).
- `const` constructors wherever possible. Null safety without `!` unless the non-null is proven on the line above.
- Names say what the thing is: `ProductCard`, `CartController`, `OrderRepository`. No `Utils`, `Helper`, `Manager` grab-bags.
- No hardcoded user-facing strings in widgets. Add them to both `lib/l10n/app_ar.arb` and `app_en.arb`, run `flutter gen-l10n`, and read them with `context.l10n`. Every string can be replaced from the dashboard (Admin > App texts, served by `GET /app/texts`, laid over the built-in text by `AppTextsDelegate`), so after changing strings also run `dart run tool/gen_text_overrides.dart` (regenerates `app_localizations_override.dart`) and, in the Laravel project, `php artisan app-texts:sync` (lists the new keys in the dashboard). Plural/select strings stay app-only. Keep state as values or enums, not as already-translated strings, so it survives a language switch.
- No hardcoded colors, font sizes, or paddings in screens. Use `Theme.of(context)` and the tokens in `core/theme`.
- Comments explain why, not what. No commented-out code.
- No `print`. No secrets, tokens, or personal data in logs.

## UI and UX

The designs are the source of truth: `../docs/MAQAM Mobile App-png/` (numbered screens, Arabic names). Open the PNG for a screen before building it and match its layout, copy, and spacing. Do not invent screens or copy that the designs do not show; ask instead.

Brand: light off-white screens with navy text and buttons and a gold accent; onboarding is dark navy. Colors live in `core/theme/app_colors.dart` and were sampled from the designs. Material 3.

- **Illustrated icons.** The owner supplied four coloured illustrations in `assets/images` (points coin, rank shield, stars, money). Use them through `ArtIcon` and the `AppArt` enum in `core/widgets/app_art.dart`; the coin goes beside a points balance. Everything else stays on Material line icons.
- **Balances** are written with thousands separators (`3,821`).
- **Typeface.** IBM Plex Sans Arabic, the same as the website, bundled in `assets/fonts` (weights 400, 500, 600, 700) and set once as `AppTheme.fontFamily`. Never set a font family on an individual widget, and use only those four weights. The one trap: a button's `textStyle` replaces the theme's rather than inheriting it, so build it with `AppTheme.buttonText(...)`, never a bare `TextStyle`.

- **Tokens first.** Spacing on a 4-pt scale (4, 8, 12, 16, 24, 32). One radius scale. Text styles come from `TextTheme` only.
- **RTL-safe layout.** Use `EdgeInsetsDirectional`, `AlignmentDirectional`, `start`/`end`; never `left`/`right`. Check that directional icons (back arrows, chevrons) mirror.
- **Every screen that loads data handles four states:** loading (skeleton or spinner), content, empty (says what to do next), error (plain-language message and a retry action).
- **Feedback for every action.** Buttons show progress and are disabled while a request is in flight; success and failure are confirmed visibly. No double submits.
- **Touch targets at least 48×48.** Text contrast meets WCAG AA on the dark background. Images and icon buttons have semantic labels.
- **Responsive.** No fixed widths; layouts adapt from small phones to tablets and respect text scaling and safe areas.
- **Images** use a placeholder and an error fallback, are cached, and never cause layout jumps (fixed aspect ratio).
- **Forms** validate inline, show server validation errors on the matching field, use the right keyboard type, and keep the submit button reachable above the keyboard.
- **Navigation** is predictable: the system back button always works, and destructive actions (cancel order, delete account, sign out everywhere) ask for confirmation.
- Motion is subtle and short (150–300 ms). Nothing animates just for decoration.
- Copy is formal Arabic, short, and consistent with the website's wording.

## Testing

- Each repository: a unit test against a fake HTTP client covering success, API failure (`success: false`), and HTTP error.
- Each controller: a unit test for its state transitions.
- Each screen: a widget test for content and for the error state.
- Tests never hit the real network.
- Widget tests go through `test/support/test_app.dart` (`pumpApp`, the fakes, `openTab`), which lays the app out on a tall phone-sized surface.
- Product images do not load in the web build against a server without CORS headers on images. That is a web-only limit, not an app bug.
- For a screen that the web build cannot reach (biometrics has no web plugin), write a throwaway `lib/preview_*.dart` entry point that shows the screen with fake callbacks, build it with `flutter build web -t`, look at it, then delete it.
- A preview entry point that signs in with a made-up session must be built with `--dart-define=API_BASE_URL=http://127.0.0.1:9/none`: against a real server the fake token gets a 401, which signs the preview straight back out.
- Do not build an APK unless the owner asks for one.
- Look at every new or changed screen before calling it done: `flutter build web`, start the `mobile-web-preview` server from `../.claude/launch.json`, open it at 390×844, and compare against the design PNG. Check Arabic and English.

## Environment

- Flutter SDK is at `E:\Flutter\flutter`; JDK 17 and the Android SDK are under `E:\Flutter` too. Gradle and pub caches are on `E:` (`GRADLE_USER_HOME`, `PUB_CACHE`); keep them off `C:`.
- The machine has about 8 GB RAM. Prefer a physical Android device or `flutter run -d windows` over an emulator.
- iOS cannot be built from this machine.
- Android builds work from this machine, but Gradle fails with `Unable to establish loopback connection` unless Java's temp files are off the user profile (its path has a space). Build with `TEMP` and `TMP` set to `E:\Flutter	mp` and `JAVA_TOOL_OPTIONS="-Djdk.net.unixdomain.tmpdir=E:\Flutter	mp -Djava.io.tmpdir=E:\Flutter	mp"`. The first build takes about 15 minutes.
- The Android application id is `maqam.egypt`, because that is the app registered in the Firebase project; `android/app/google-services.json` comes from `storage/app/private/firebase/` in the Laravel project. Changing the id breaks push. The Dart package name is still `maqam_app`.
- The iOS bundle id is `maqam.egypt` too, matching the iOS app in the same Firebase project; `ios/Runner/GoogleService-Info.plist` comes from the same folder and is listed in the Xcode project's resources. Push on iOS also needs `Runner.entitlements` (`aps-environment`) and an APNs key uploaded in the Firebase console.
- The release APK is signed with the debug key (the Flutter template default). It installs for testing but cannot be published; a real keystore is still to be set up.
- Windows desktop builds need Windows Developer Mode (symlinks for plugins). Web builds do not.
- To run against the local Laravel server: `--dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1`.
