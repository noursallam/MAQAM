import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/locale/locale_provider.dart';
import 'data/api_catalog_repository.dart';
import 'domain/category.dart';
import 'domain/product.dart';
import 'domain/product_details.dart';
import 'domain/promo_banner.dart';

// Names come back in the app language, so every list reloads when it changes.

final categoriesProvider = FutureProvider<List<Category>>((ref) {
  ref.watch(localeProvider);
  return ref.watch(catalogRepositoryProvider).categories();
});

final bannersProvider = FutureProvider<List<PromoBanner>>((ref) {
  ref.watch(localeProvider);
  return ref.watch(catalogRepositoryProvider).banners();
});

/// The newest products, for the home screen.
final latestProductsProvider = FutureProvider<List<Product>>((ref) async {
  ref.watch(localeProvider);
  final page = await ref.watch(catalogRepositoryProvider).products(perPage: 2);
  return page.items;
});

final productsProvider = FutureProvider.autoDispose.family<ProductPage, ProductQuery>((ref, query) {
  ref.watch(localeProvider);
  return ref.watch(catalogRepositoryProvider).products(
        categoryId: query.categoryId,
        search: query.search,
        sort: query.sort,
      );
});

final productDetailsProvider = FutureProvider.autoDispose.family<ProductDetails, int>((ref, id) {
  ref.watch(localeProvider);
  return ref.watch(catalogRepositoryProvider).product(id);
});
