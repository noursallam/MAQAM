import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../cart/cart_providers.dart';
import '../catalog_providers.dart';
import '../domain/product.dart';
import 'widgets/product_card.dart';

/// The product catalog (design: "13 المتجر").
class StoreScreen extends ConsumerStatefulWidget {
  const StoreScreen({super.key, required this.onOpenProduct, required this.onOpenCart});

  final ValueChanged<int> onOpenProduct;
  final VoidCallback onOpenCart;

  @override
  ConsumerState<StoreScreen> createState() => _StoreScreenState();
}

class _StoreScreenState extends ConsumerState<StoreScreen> {
  final _search = TextEditingController();
  Timer? _debounce;
  ProductQuery _query = (categoryId: null, search: '', sort: ProductSort.newest);

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  void _onSearchChanged(String text) {
    // Wait for a pause in typing before asking the server.
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      final search = text.trim();
      if (search != _query.search) {
        setState(() => _query = (categoryId: _query.categoryId, search: search, sort: _query.sort));
      }
    });
  }

  void _selectCategory(int? id) {
    setState(() => _query = (categoryId: id, search: _query.search, sort: _query.sort));
  }

  void _selectSort(ProductSort sort) {
    setState(() => _query = (categoryId: _query.categoryId, search: _query.search, sort: sort));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final categories = ref.watch(categoriesProvider).value ?? const [];
    final products = ref.watch(productsProvider(_query));
    final cartCount = ref.watch(cartCountProvider);

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        onRefresh: () {
          ref.invalidate(categoriesProvider);
          return ref.refresh(productsProvider(_query).future);
        },
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(
              child: PageHeader(
                title: l10n.navStore,
                actions: [
                  IconButton(
                    tooltip: l10n.cart,
                    onPressed: widget.onOpenCart,
                    icon: Badge.count(
                      count: cartCount,
                      isLabelVisible: cartCount > 0,
                      backgroundColor: theme.colorScheme.primary,
                      child: const Icon(Icons.shopping_cart_outlined),
                    ),
                  ),
                ],
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                child: TextField(
                  controller: _search,
                  onChanged: _onSearchChanged,
                  textInputAction: TextInputAction.search,
                  decoration: InputDecoration(
                    hintText: l10n.storeSearchHint,
                    prefixIcon: const Icon(Icons.search),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppSpacing.radius),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                  ),
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: SizedBox(
                height: 64,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.lg, vertical: AppSpacing.md),
                  children: [
                    FilterPill(
                      label: l10n.catalogAll,
                      selected: _query.categoryId == null,
                      onTap: () => _selectCategory(null),
                    ),
                    for (final category in categories)
                      Padding(
                        padding: const EdgeInsetsDirectional.only(start: AppSpacing.sm),
                        child: FilterPill(
                          label: category.name,
                          selected: _query.categoryId == category.id,
                          onTap: () => _selectCategory(category.id),
                        ),
                      ),
                  ],
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsetsDirectional.only(start: AppSpacing.lg, end: AppSpacing.sm),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        products.hasValue ? l10n.storeCount(products.requireValue.total) : '',
                        style: theme.textTheme.bodyMedium
                            ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                      ),
                    ),
                    _SortMenu(selected: _query.sort, onSelected: _selectSort),
                  ],
                ),
              ),
            ),
            ...switch (products) {
              AsyncData(:final value) when value.items.isNotEmpty => [
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(
                        AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xl),
                    sliver: SliverGrid.builder(
                      gridDelegate: productGridDelegate,
                      itemCount: value.items.length,
                      itemBuilder: (context, i) => ProductCard(
                        product: value.items[i],
                        onTap: () => widget.onOpenProduct(value.items[i].id),
                      ),
                    ),
                  ),
                ],
              AsyncData() => [
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Center(child: MessageView(message: l10n.catalogEmpty)),
                  ),
                ],
              _ => [
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Center(
                      child: AsyncView(
                        value: products,
                        errorTitle: l10n.catalogLoadFailed,
                        onRetry: () => ref.invalidate(productsProvider(_query)),
                        data: (_) => const SizedBox.shrink(),
                      ),
                    ),
                  ),
                ],
            },
          ],
        ),
      ),
    );
  }
}

class _SortMenu extends StatelessWidget {
  const _SortMenu({required this.selected, required this.onSelected});

  final ProductSort selected;
  final ValueChanged<ProductSort> onSelected;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    String label(ProductSort sort) => switch (sort) {
          ProductSort.newest => l10n.sortNewest,
          ProductSort.priceLowToHigh => l10n.sortPriceLow,
          ProductSort.priceHighToLow => l10n.sortPriceHigh,
        };

    return PopupMenuButton<ProductSort>(
      tooltip: l10n.sortBy,
      initialValue: selected,
      onSelected: onSelected,
      itemBuilder: (context) => [
        for (final sort in ProductSort.values) PopupMenuItem(value: sort, child: Text(label(sort))),
      ],
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.md),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(label(selected), style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(width: AppSpacing.xs),
            const Icon(Icons.keyboard_arrow_down, size: 20),
          ],
        ),
      ),
    );
  }
}
