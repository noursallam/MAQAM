import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../cart/cart_providers.dart';
import '../catalog_providers.dart';
import '../domain/product.dart';
import '../domain/product_details.dart';
import 'widgets/product_card.dart';
import 'widgets/product_gallery.dart';

/// One product: gallery, variants, details, similar products and the
/// add-to-cart bar (design: "14 المنتج", extended).
class ProductScreen extends ConsumerStatefulWidget {
  const ProductScreen({
    super.key,
    required this.productId,
    required this.onOpenProduct,
    required this.onOpenCart,
    required this.onSignIn,
  });

  final int productId;
  final ValueChanged<int> onOpenProduct;
  final VoidCallback onOpenCart;
  final VoidCallback onSignIn;

  @override
  ConsumerState<ProductScreen> createState() => _ProductScreenState();
}

class _ProductScreenState extends ConsumerState<ProductScreen> {
  int _quantity = 1;
  int _colorIndex = 0;
  ProductOption? _option;
  bool _adding = false;

  /// The chosen variant, or the first one when nothing was tapped yet.
  ProductOption? _selectedOption(ProductDetails details) =>
      _option ?? (details.options.isEmpty ? null : details.options.first);

  double _unitPrice(ProductDetails details) =>
      _selectedOption(details)?.price ?? details.product.price;

  Future<void> _addToCart(ProductDetails details) async {
    final l10n = context.l10n;
    if (ref.read(sessionProvider) == null) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(
          content: Text(l10n.productSignInToBuy),
          action: SnackBarAction(label: l10n.signIn, onPressed: widget.onSignIn),
        ));
      return;
    }

    setState(() => _adding = true);
    try {
      await ref.read(cartProvider.notifier).add(
            productId: details.product.id,
            quantity: _quantity,
            optionId: _selectedOption(details)?.id,
            color: details.colors.isEmpty ? null : details.colors[_colorIndex].name,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(
          content: Text(l10n.productAdded),
          action: SnackBarAction(label: l10n.productViewCart, onPressed: widget.onOpenCart),
        ));
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final details = ref.watch(productDetailsProvider(widget.productId));

    return Scaffold(
      body: switch (details) {
        AsyncData(:final value) => _Content(
            details: value,
            colorIndex: _colorIndex,
            option: _selectedOption(value),
            unitPrice: _unitPrice(value),
            onColor: (i) => setState(() => _colorIndex = i),
            onOption: (o) => setState(() => _option = o),
            onOpenCart: widget.onOpenCart,
            onOpenProduct: widget.onOpenProduct,
          ),
        _ => SafeArea(
            child: Column(
              children: [
                Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: IconButton(
                    tooltip: l10n.back,
                    onPressed: () => Navigator.maybePop(context),
                    icon: const Icon(Icons.arrow_back),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: AsyncView(
                      value: details,
                      errorTitle: l10n.productLoadFailed,
                      onRetry: () => ref.invalidate(productDetailsProvider(widget.productId)),
                      data: (_) => const SizedBox.shrink(),
                    ),
                  ),
                ),
              ],
            ),
          ),
      },
      bottomNavigationBar: details.hasValue
          ? _AddToCartBar(
              inStock: details.requireValue.product.inStock,
              maxQuantity: details.requireValue.product.stock,
              quantity: _quantity,
              total: _unitPrice(details.requireValue) * _quantity,
              busy: _adding,
              onQuantity: (q) => setState(() => _quantity = q),
              onAdd: () => _addToCart(details.requireValue),
            )
          : null,
    );
  }
}

class _Content extends ConsumerWidget {
  const _Content({
    required this.details,
    required this.colorIndex,
    required this.option,
    required this.unitPrice,
    required this.onColor,
    required this.onOption,
    required this.onOpenCart,
    required this.onOpenProduct,
  });

  final ProductDetails details;
  final int colorIndex;
  final ProductOption? option;
  final double unitPrice;
  final ValueChanged<int> onColor;
  final ValueChanged<ProductOption> onOption;
  final VoidCallback onOpenCart;
  final ValueChanged<int> onOpenProduct;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final product = details.product;
    final muted = theme.colorScheme.onSurfaceVariant;
    final stock = product.stock;

    // Variants that are alternatives share a name ("Size"); show one group per name.
    final groups = <String, List<ProductOption>>{};
    for (final o in details.options) {
      groups.putIfAbsent(o.name, () => []).add(o);
    }

    return ListView(
      padding: EdgeInsets.zero,
      children: [
        ProductGallery(
          images: details.gallery,
          cartCount: ref.watch(cartCountProvider),
          onOpenCart: onOpenCart,
        ),
        Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      product.categoryName ?? '',
                      style: theme.textTheme.bodySmall?.copyWith(color: muted),
                    ),
                  ),
                  _Availability(inStock: product.inStock),
                ],
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(product.name, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22)),
              const SizedBox(height: AppSpacing.sm),
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // The price swaps smoothly when another variant is chosen.
                  AnimatedSwitcher(
                    duration: const Duration(milliseconds: 200),
                    child: Text(
                      unitPrice > 0 ? formatEgp(context, unitPrice) : l10n.priceOnRequest,
                      key: ValueKey(unitPrice),
                      style: theme.textTheme.headlineMedium?.copyWith(
                        fontSize: 22,
                        color: theme.colorScheme.secondary,
                      ),
                    ),
                  ),
                  if (stock != null && stock > 0 && stock <= 5) ...[
                    const SizedBox(width: AppSpacing.md),
                    _Pill(
                      label: l10n.productLowStock(stock),
                      color: theme.colorScheme.error,
                    ),
                  ],
                ],
              ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                children: [
                  _Perk(icon: Icons.local_shipping_outlined, label: l10n.productPerkDelivery),
                  const SizedBox(width: AppSpacing.sm),
                  _Perk(icon: Icons.payments_outlined, label: l10n.productPerkCod),
                  const SizedBox(width: AppSpacing.sm),
                  _Perk(icon: Icons.qr_code_2, label: l10n.productPerkPoints),
                ],
              ),
              if (details.colors.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.xl),
                Text.rich(
                  TextSpan(
                    text: l10n.productColor,
                    style: theme.textTheme.titleMedium,
                    children: [
                      TextSpan(
                        text: details.colors[colorIndex].name,
                        style: theme.textTheme.bodyLarge?.copyWith(color: muted),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
                Wrap(
                  spacing: AppSpacing.md,
                  runSpacing: AppSpacing.sm,
                  children: [
                    for (final (i, color) in details.colors.indexed)
                      _ColorSwatch(
                        color: color,
                        selected: i == colorIndex,
                        onTap: () => onColor(i),
                      ),
                  ],
                ),
              ],
              for (final MapEntry(key: name, value: options) in groups.entries) ...[
                const SizedBox(height: AppSpacing.xl),
                Text(name, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                Wrap(
                  spacing: AppSpacing.sm,
                  runSpacing: AppSpacing.sm,
                  children: [
                    for (final o in options)
                      _OptionChip(
                        label: o.value,
                        selected: o.id == option?.id,
                        onTap: () => onOption(o),
                      ),
                  ],
                ),
              ],
              if (details.description.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.xl),
                Text(l10n.productDescription, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.xs),
                _ExpandableText(text: details.description),
              ],
              const SizedBox(height: AppSpacing.xl),
              _Specs(details: details),
              const SizedBox(height: AppSpacing.lg),
              DecoratedBox(
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(AppSpacing.radius),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: Row(
                    children: [
                      Icon(Icons.qr_code_scanner, size: 20, color: theme.colorScheme.secondary),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Text(l10n.productQrNote, style: theme.textTheme.bodyMedium),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
        if (product.categoryId != null)
          _SimilarProducts(
            categoryId: product.categoryId!,
            excludeId: product.id,
            onOpenProduct: onOpenProduct,
          ),
        const SizedBox(height: AppSpacing.lg),
      ],
    );
  }
}

class _Availability extends StatelessWidget {
  const _Availability({required this.inStock});

  final bool inStock;

  @override
  Widget build(BuildContext context) {
    final color = inStock ? AppColors.whatsapp : Theme.of(context).colorScheme.error;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(Icons.circle, size: 8, color: color),
        const SizedBox(width: AppSpacing.xs),
        Text(
          inStock ? context.l10n.productInStock : context.l10n.outOfStock,
          style: TextStyle(color: color, fontSize: 13),
        ),
      ],
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(AppSpacing.sm),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
        child: Text(label, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
      ),
    );
  }
}

/// One of the three reassurances under the price.
class _Perk extends StatelessWidget {
  const _Perk({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Expanded(
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.md),
          border: Border.all(color: AppColors.border),
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.md, horizontal: AppSpacing.xs),
          child: Column(
            children: [
              Icon(icon, size: 20, color: theme.colorScheme.secondary),
              const SizedBox(height: AppSpacing.xs),
              Text(
                label,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: theme.textTheme.bodySmall?.copyWith(fontSize: 12, height: 1.3),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ColorSwatch extends StatelessWidget {
  const _ColorSwatch({required this.color, required this.selected, required this.onTap});

  final ProductColor color;
  final bool selected;
  final VoidCallback onTap;

  /// `#1A1A1A` -> a colour; grey when the admin panel gave none or a bad value.
  static Color _parse(String? hex) {
    final digits = hex?.replaceFirst('#', '') ?? '';
    final value = digits.length == 6 ? int.tryParse(digits, radix: 16) : null;
    return value == null ? AppColors.surfaceMuted : Color(0xFF000000 | value);
  }

  @override
  Widget build(BuildContext context) {
    final fill = _parse(color.hex);

    return Semantics(
      label: color.name,
      selected: selected,
      button: true,
      child: InkResponse(
        onTap: onTap,
        radius: 28,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          width: 44,
          height: 44,
          padding: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(
              color: selected ? AppColors.navy : Colors.transparent,
              width: 2,
            ),
          ),
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: fill,
              shape: BoxShape.circle,
              border: Border.all(color: AppColors.border),
            ),
            child: selected
                ? Icon(
                    Icons.check,
                    size: 16,
                    // A tick that stays visible on both light and dark swatches.
                    color: fill.computeLuminance() > 0.6 ? AppColors.navy : Colors.white,
                  )
                : null,
          ),
        ),
      ),
    );
  }
}

class _OptionChip extends StatelessWidget {
  const _OptionChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
      backgroundColor: AppColors.surface,
      selectedColor: AppColors.surface,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.md)),
      side: BorderSide(
        color: selected ? scheme.primary : scheme.outlineVariant,
        width: selected ? 2 : 1,
      ),
      labelStyle: TextStyle(
        color: scheme.onSurface,
        fontWeight: selected ? FontWeight.w700 : FontWeight.w400,
      ),
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.md),
    );
  }
}

/// Long descriptions start folded, with a "read more" toggle.
class _ExpandableText extends StatefulWidget {
  const _ExpandableText({required this.text});

  final String text;

  @override
  State<_ExpandableText> createState() => _ExpandableTextState();
}

class _ExpandableTextState extends State<_ExpandableText> {
  static const _foldedLines = 4;
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final style = theme.textTheme.bodyLarge?.copyWith(color: theme.colorScheme.onSurfaceVariant);

    return LayoutBuilder(
      builder: (context, constraints) {
        final painter = TextPainter(
          text: TextSpan(text: widget.text, style: style),
          maxLines: _foldedLines,
          textDirection: Directionality.of(context),
          textScaler: MediaQuery.textScalerOf(context),
        )..layout(maxWidth: constraints.maxWidth);
        final overflows = painter.didExceedMaxLines;
        painter.dispose();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AnimatedSize(
              duration: const Duration(milliseconds: 200),
              alignment: AlignmentDirectional.topStart,
              child: Text(
                widget.text,
                style: style,
                maxLines: _expanded ? null : _foldedLines,
                overflow: _expanded ? null : TextOverflow.ellipsis,
              ),
            ),
            if (overflows)
              TextButton(
                style: TextButton.styleFrom(
                  foregroundColor: theme.colorScheme.secondary,
                  padding: EdgeInsets.zero,
                ),
                onPressed: () => setState(() => _expanded = !_expanded),
                child: Text(_expanded ? l10n.productReadLess : l10n.productReadMore),
              ),
          ],
        );
      },
    );
  }
}

class _Specs extends StatelessWidget {
  const _Specs({required this.details});

  final ProductDetails details;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final rows = <(String, String, bool)>[
      if (details.sku != null) (l10n.productSpecSku, details.sku!, true),
      if (details.product.categoryName != null)
        (l10n.productSpecCategory, details.product.categoryName!, false),
      if (details.weight != null) (l10n.productSpecWeight, l10n.productWeightKg(details.weight!), false),
      if (details.dimensions != null) (l10n.productSpecDimensions, details.dimensions!, true),
    ];
    if (rows.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l10n.productSpecs, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        Card(
          margin: EdgeInsets.zero,
          child: Column(
            children: [
              for (final (index, (label, value, ltr)) in rows.indexed) ...[
                if (index > 0) const Divider(height: 1, color: AppColors.border),
                Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.lg, vertical: AppSpacing.md),
                  child: Row(
                    children: [
                      Text(
                        label,
                        style: theme.textTheme.bodyMedium
                            ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                      ),
                      const SizedBox(width: AppSpacing.lg),
                      Expanded(
                        child: Text(
                          value,
                          textAlign: TextAlign.end,
                          // Codes and measurements read left to right in both languages.
                          textDirection: ltr ? TextDirection.ltr : null,
                          style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

/// Other products from the same category, in a row that scrolls sideways.
class _SimilarProducts extends ConsumerWidget {
  const _SimilarProducts({
    required this.categoryId,
    required this.excludeId,
    required this.onOpenProduct,
  });

  final int categoryId;
  final int excludeId;
  final ValueChanged<int> onOpenProduct;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final query = (categoryId: categoryId, search: '', sort: ProductSort.newest);
    // Optional extra: while loading, or if it fails, the section simply is not there.
    final items = (ref.watch(productsProvider(query)).value?.items ?? const <Product>[])
        .where((p) => p.id != excludeId)
        .take(8)
        .toList();
    if (items.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.md),
          child: Text(
            context.l10n.productSimilar,
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontSize: 18),
          ),
        ),
        SizedBox(
          height: 230,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            itemCount: items.length,
            separatorBuilder: (_, _) => const SizedBox(width: AppSpacing.md),
            itemBuilder: (context, i) => SizedBox(
              width: 160,
              child: ProductCard(
                product: items[i],
                showCategory: false,
                onTap: () => onOpenProduct(items[i].id),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _AddToCartBar extends StatelessWidget {
  const _AddToCartBar({
    required this.inStock,
    required this.maxQuantity,
    required this.quantity,
    required this.total,
    required this.busy,
    required this.onQuantity,
    required this.onAdd,
  });

  final bool inStock;

  /// Units in stock, when known: the stepper stops there.
  final int? maxQuantity;
  final int quantity;
  final double total;
  final bool busy;
  final ValueChanged<int> onQuantity;
  final VoidCallback onAdd;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final max = maxQuantity;

    return DecoratedBox(
      decoration: const BoxDecoration(
        color: AppColors.background,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              QuantityStepper(
                quantity: quantity,
                max: max != null && max > 0 ? max : 999,
                onChanged: inStock && !busy ? onQuantity : null,
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: FilledButton(
                  onPressed: inStock && !busy ? onAdd : null,
                  child: busy
                      ? const SizedBox.square(
                          dimension: 22,
                          child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                        )
                      : FittedBox(
                          fit: BoxFit.scaleDown,
                          child: Text(
                            !inStock
                                ? l10n.outOfStock
                                : total > 0
                                    ? l10n.productAddWithTotal(formatEgp(context, total))
                                    : l10n.productAddToCart,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
