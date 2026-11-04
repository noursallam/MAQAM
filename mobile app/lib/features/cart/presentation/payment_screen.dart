import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../data/api_cart_repository.dart';
import '../domain/cart.dart';

/// Whether the gateway's page can be shown inside the app. Only the phone
/// platforms have a web view; elsewhere the page opens in the browser.
final inAppPaymentSupportedProvider = Provider<bool>(
  (ref) =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS),
);

/// Whether [url] is the page the gateway sends the customer back to. The server
/// names that page by its beginning, since the gateway adds its own query.
bool isPaymentReturn(Uri? returnUrl, String url) {
  final target = returnUrl?.toString();
  return target != null && target.isNotEmpty && url.startsWith(target);
}

/// Builds the gateway page. A provider, so tests can swap in a page that
/// needs no web view.
typedef PaymentPageBuilder = Widget Function({
  required Uri paymentUrl,
  required Uri? returnUrl,
  required VoidCallback onReturned,
});

final paymentPageBuilderProvider = Provider<PaymentPageBuilder>(
  (ref) => ({required paymentUrl, required returnUrl, required onReturned}) =>
      _GatewayWebView(paymentUrl: paymentUrl, returnUrl: returnUrl, onReturned: onReturned),
);

/// The payment gateway's page, shown inside the app. Card details are typed
/// into the gateway's own page and never pass through the app.
class PaymentScreen extends ConsumerStatefulWidget {
  const PaymentScreen({super.key, required this.order, required this.onDone});

  final PlacedOrder order;

  /// Called once with what the server says about the payment.
  final ValueChanged<PaymentState> onDone;

  @override
  ConsumerState<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends ConsumerState<PaymentScreen> {
  bool _checking = false;

  /// Asks the server, which hears from the gateway directly, how it went.
  Future<void> _finish() async {
    if (_checking) return;
    setState(() => _checking = true);
    PaymentState state;
    try {
      state = await ref.read(cartRepositoryProvider).paymentState(widget.order.orderNumber);
    } on ApiException {
      // Unknown is not the same as failed; the order screen can settle it later.
      state = PaymentState.pending;
    }
    if (mounted) widget.onDone(state);
  }

  Future<void> _confirmClose() async {
    final l10n = context.l10n;
    final leave = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.paymentLeaveTitle),
        content: Text(l10n.paymentLeaveBody),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(l10n.paymentLeaveStay),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(l10n.paymentLeaveConfirm),
          ),
        ],
      ),
    );
    // They may have paid before closing, so the server is still asked.
    if (leave == true) await _finish();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final page = ref.watch(paymentPageBuilderProvider);

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop && !_checking) _confirmClose();
      },
      child: Scaffold(
        appBar: AppBar(
          automaticallyImplyLeading: false,
          leading: IconButton(
            tooltip: l10n.close,
            onPressed: _checking ? null : _confirmClose,
            icon: const Icon(Icons.close),
          ),
          title: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.lock_outline, size: 16, color: theme.colorScheme.secondary),
              const SizedBox(width: AppSpacing.sm),
              Text(l10n.paymentTitle, style: theme.textTheme.titleMedium),
            ],
          ),
        ),
        body: _checking
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const CircularProgressIndicator(),
                    const SizedBox(height: AppSpacing.lg),
                    Text(l10n.paymentChecking, style: theme.textTheme.bodyLarge),
                  ],
                ),
              )
            : page(
                paymentUrl: widget.order.paymentUrl!,
                returnUrl: widget.order.paymentReturnUrl,
                onReturned: _finish,
              ),
      ),
    );
  }
}

class _GatewayWebView extends StatefulWidget {
  const _GatewayWebView({
    required this.paymentUrl,
    required this.returnUrl,
    required this.onReturned,
  });

  final Uri paymentUrl;
  final Uri? returnUrl;
  final VoidCallback onReturned;

  @override
  State<_GatewayWebView> createState() => _GatewayWebViewState();
}

class _GatewayWebViewState extends State<_GatewayWebView> {
  late final WebViewController _controller;
  int _progress = 0;
  bool _returned = false;
  Timer? _returnFallback;

  /// How long the return page may take to finish loading before the app stops
  /// waiting for it. By then the server has already received the request.
  static const _returnPatience = Duration(seconds: 6);

  bool _isReturn(String url) => isPaymentReturn(widget.returnUrl, url);

  void _handBack() {
    if (_returned || !mounted) return;
    _returned = true;
    _returnFallback?.cancel();
    widget.onReturned();
  }

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            if (mounted) setState(() => _progress = progress);
          },
          onNavigationRequest: (request) {
            final uri = Uri.tryParse(request.url);
            // Wallet apps and bank apps are opened by links the web view cannot follow.
            if (uri != null && uri.scheme != 'http' && uri.scheme != 'https') {
              launchUrl(uri, mode: LaunchMode.externalApplication).ignore();
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
          // The return page is allowed to load first: that request is how the
          // server learns the customer came back. A page that never finishes
          // must not keep the customer in the frame, hence the timer.
          onPageStarted: (url) {
            if (_isReturn(url) && !_returned) {
              _returnFallback ??= Timer(_returnPatience, _handBack);
            }
          },
          onPageFinished: (url) {
            if (_isReturn(url)) _handBack();
          },
          onWebResourceError: (error) {
            final url = error.url;
            if (error.isForMainFrame == true && url != null && _isReturn(url)) _handBack();
          },
        ),
      )
      ..loadRequest(widget.paymentUrl);
  }

  @override
  void dispose() {
    _returnFallback?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        WebViewWidget(controller: _controller),
        if (_progress < 100)
          LinearProgressIndicator(value: _progress / 100, minHeight: 2),
      ],
    );
  }
}
