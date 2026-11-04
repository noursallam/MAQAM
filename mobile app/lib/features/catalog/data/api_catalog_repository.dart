import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/catalog_repository.dart';
import '../domain/category.dart';
import '../domain/product.dart';
import '../domain/product_details.dart';
import '../domain/promo_banner.dart';

class ApiCatalogRepository implements CatalogRepository {
  const ApiCatalogRepository(this._api);

  final ApiClient _api;

  @override
  Future<List<Category>> categories() async {
    final data = await _api.get('/store/categories') as List<dynamic>;
    return [for (final json in data.cast<Map<String, dynamic>>()) _category(json)];
  }

  @override
  Future<List<PromoBanner>> banners() async {
    final data = await _api.get('/store/banners') as List<dynamic>;
    return [
      for (final json in data.cast<Map<String, dynamic>>())
        PromoBanner(
          id: json['id'] as int,
          title: json['title'] as String? ?? '',
          imageUrl: json['image_url'] as String?,
        ),
    ];
  }

  @override
  Future<ProductDetails> product(int id) async {
    final json = await _api.get('/store/products/$id') as Map<String, dynamic>;
    List<Map<String, dynamic>> list(String key) =>
        (json[key] as List<dynamic>? ?? const []).cast<Map<String, dynamic>>();

    return ProductDetails(
      product: _product(json),
      sku: json['sku'] as String?,
      description: json['description'] as String? ?? '',
      weight: _text(json['weight']),
      dimensions: _text(json['dimensions']),
      images: [for (final image in list('images')) image['url'] as String],
      colors: [
        for (final color in list('colors'))
          ProductColor(name: color['name'] as String? ?? '', hex: color['hex'] as String?),
      ],
      options: [
        for (final option in list('options'))
          ProductOption(
            id: option['id'] as int,
            name: option['name'] as String? ?? '',
            value: option['value'] as String? ?? '',
            price: double.tryParse('${option['price']}') ?? 0,
          ),
      ],
    );
  }

  @override
  Future<ProductPage> products({
    int? categoryId,
    String search = '',
    ProductSort sort = ProductSort.newest,
    int perPage = 30,
  }) async {
    final page = await _api.getPage('/store/products', query: {
      'per_page': '$perPage',
      'sort': sort.apiValue,
      if (categoryId != null) 'category_id': '$categoryId',
      if (search.isNotEmpty) 'search': search,
    });
    return ProductPage(items: [for (final json in page.items) _product(json)], total: page.total);
  }

  static Category _category(Map<String, dynamic> json) =>
      Category(id: json['id'] as int, name: json['name'] as String? ?? '');

  static Product _product(Map<String, dynamic> json) => Product(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        price: double.tryParse('${json['min_price'] ?? json['price']}') ?? 0,
        inStock: (json['stock_quantity'] as int? ?? 0) > 0,
        stock: json['stock_quantity'] as int?,
        imageUrl: json['image_url'] as String?,
        categoryId: (json['category'] as Map<String, dynamic>?)?['id'] as int?,
        categoryName: (json['category'] as Map<String, dynamic>?)?['name'] as String?,
      );

  /// A field that may arrive as a number, a string, empty, or not at all.
  static String? _text(Object? value) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty || text == '0' || text == '0.00' ? null : text;
  }
}

final catalogRepositoryProvider = Provider<CatalogRepository>(
  (ref) => ApiCatalogRepository(ref.watch(apiClientProvider)),
);
