import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../cart_providers.dart';
import '../domain/cart.dart';

/// The shopping cart (design: "15 السلة").
class CartScreen extends ConsumerStatefulWidget {
  const CartScreen({
    super.key,
    required this.onCheckout,
    required this.onBrowse,
    required this.onSignIn,
  });

  final VoidCallback onCheckout;
  final VoidCallback onBrowse;
  final VoidCallback onSignIn;

  @override
  ConsumerState<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends ConsumerState<CartScreen> {
  /// True while a change is being saved; the controls wait for the server's cart.
  bool _saving = false;

  Future<void> _change(Future<void> Function(CartNotifier cart) action) async {
    if (_saving) return;
    setState(() => _saving = true);
    try {
      await action(ref.read(cartProvider.notifier));
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final signedIn = ref.watch(sessionProvider) != null;
    final cart = ref.watch(cartProvider);
    final loaded = cart.value;

    return Scaffold(
      appBar: AppBar(
        centerTitle: false,
        title: Text.rich(
          TextSpan(
            text: l10n.cartTitle,
            style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20),
            children: [
              if (loaded != null && !loaded.isEmpty)
                TextSpan(
                  text: ' · ${l10n.cartItemsCount(loaded.itemsCount)}',
                  style: theme.textTheme.bodyLarge
                      ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                ),
            ],
          ),
        ),
      ),
      body: !signedIn
          ? Center(
              child: MessageView(
                icon: Icons.shopping_cart_outlined,
                message: l10n.cartGuest,
                actionLabel: l10n.signIn,
                onAction: widget.onSignIn,
              ),
            )
          : AsyncView<Cart?>(
              value: cart,
              errorTitle: l10n.cartLoadFailed,
              onRetry: () => ref.invalidate(cartProvider),
              data: (cart) => cart == null || cart.isEmpty
                  ? Center(
                      child: MessageView(
                        icon: Icons.shopping_cart_outlined,
                        title: l10n.cartEmpty,
                        message: l10n.cartEmptyBody,
                        actionLabel: l10n.cartBrowse,
                        onAction: widget.onBrowse,
                      ),
                    )
                  : ListView(
                      padding: const EdgeInsets.all(AppSpacing.lg),
                      children: [
                        _ItemsCard(
                          items: cart.items,
                          enabled: !_saving,
                          onQuantity: (item, quantity) =>
                              _change((c) => c.setQuantity(item.id, quantity)),
                        ),
                        const SizedBox(height: AppSpacing.lg),
                        _CouponField(
                          appliedCode: cart.couponCode,
                          enabled: !_saving,
                          onApply: (code) => _change((c) => c.applyCoupon(code)),
                          onRemove: () => _change((c) => c.removeCoupon()),
                        ),
                      ],
                    ),
            ),
      bottomNavigationBar: signedIn && loaded != null && !loaded.isEmpty
          ? _CheckoutBar(cart: loaded, onCheckout: _saving ? null : widget.onCheckout)
          : null,
    );
  }
}

class _ItemsCard extends StatelessWidget {
  const _ItemsCard({required this.items, required this.enabled, required this.onQuantity});

  final List<CartItem> items;
  final bool enabled;
  final void Function(CartItem item, int quantity) onQuantity;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: EdgeInsets.zero,
      child: Column(
        children: [
          for (final (index, item) in items.indexed) ...[
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
                        Text(
                          item.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.bodyMedium,
                        ),
                        if (item.optionLabel?.isNotEmpty ?? false)
                          Text(
                            item.optionLabel!,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.bodySmall
                                ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                          ),
                        Text(
                          formatEgp(context, item.unitPrice),
                          style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  QuantityStepper(
                    compact: true,
                    quantity: item.quantity,
                    // Going below one removes the item.
                    min: 0,
                    onChanged: enabled ? (quantity) => onQuantity(item, quantity) : null,
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _CouponField extends StatefulWidget {
  const _CouponField({
    required this.appliedCode,
    required this.enabled,
    required this.onApply,
    required this.onRemove,
  });

  final String? appliedCode;
  final bool enabled;
  final ValueChanged<String> onApply;
  final VoidCallback onRemove;

  @override
  State<_CouponField> createState() => _CouponFieldState();
}

class _CouponFieldState extends State<_CouponField> {
  final _code = TextEditingController();

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  void _apply() {
    final code = _code.text.trim();
    if (code.isEmpty) return;
    FocusScope.of(context).unfocus();
    widget.onApply(code);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final applied = widget.appliedCode;

    OutlineInputBorder border(Color color) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radius),
          borderSide: BorderSide(color: color),
        );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l10n.cartCouponLabel, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (applied != null) ...[
          InputDecorator(
            decoration: InputDecoration(
              enabledBorder: border(AppColors.whatsapp),
              contentPadding: const EdgeInsetsDirectional.only(
                  start: AppSpacing.lg, end: AppSpacing.sm, top: 6, bottom: 6),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    applied,
                    textDirection: TextDirection.ltr,
                    textAlign: TextAlign.end,
                    style: const TextStyle(fontWeight: FontWeight.w600, letterSpacing: 1),
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                TextButton(
                  style: TextButton.styleFrom(
                    backgroundColor: theme.colorScheme.surfaceContainerHighest,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.md)),
                  ),
                  onPressed: widget.enabled ? widget.onRemove : null,
                  child: Text(l10n.cartCouponRemove),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            children: [
              const Icon(Icons.check, size: 16, color: AppColors.whatsapp),
              const SizedBox(width: AppSpacing.xs),
              Text(
                l10n.cartCouponApplied,
                style: theme.textTheme.bodySmall?.copyWith(color: AppColors.whatsapp),
              ),
            ],
          ),
        ] else
          TextField(
            controller: _code,
            enabled: widget.enabled,
            textCapitalization: TextCapitalization.characters,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _apply(),
            decoration: InputDecoration(
              hintText: l10n.cartCouponHint,
              enabledBorder: border(AppColors.border),
              suffixIcon: Padding(
                padding: const EdgeInsetsDirectional.only(end: AppSpacing.sm),
                child: TextButton(
                  style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                  onPressed: widget.enabled ? _apply : null,
                  child: Text(l10n.cartCouponApply),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _CheckoutBar extends StatelessWidget {
  const _CheckoutBar({required this.cart, required this.onCheckout});

  final Cart cart;
  final VoidCallback? onCheckout;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return DecoratedBox(
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              OrderSummary(
                subtotalLabel: l10n.summarySubtotal,
                subtotal: cart.subtotal,
                discount: cart.discount,
                shipping: cart.shipping,
                total: cart.total,
              ),
              const SizedBox(height: AppSpacing.md),
              FilledButton(onPressed: onCheckout, child: Text(l10n.cartCheckout)),
            ],
          ),
        ),
      ),
    );
  }
}
