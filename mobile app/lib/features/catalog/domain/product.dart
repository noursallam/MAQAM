class Product {
  const Product({
    required this.id,
    required this.name,
    required this.price,
    this.inStock = true,
    this.stock,
    this.imageUrl,
    this.categoryId,
    this.categoryName,
  });

  final int id;
  final String name;

  /// Lowest price in EGP. Zero means the price is available on request.
  final double price;
  final bool inStock;

  /// Units left, when the server says.
  final int? stock;
  final String? imageUrl;
  final int? categoryId;
  final String? categoryName;

  bool get hasPrice => price > 0;
}

/// One page of products with the number of products matching the query.
class ProductPage {
  const ProductPage({required this.items, required this.total});

  final List<Product> items;
  final int total;
}

enum ProductSort {
  newest('newest'),
  priceLowToHigh('price_asc'),
  priceHighToLow('price_desc');

  const ProductSort(this.apiValue);

  final String apiValue;
}

/// What the store is currently showing. A record, so equal queries share a cache entry.
typedef ProductQuery = ({int? categoryId, String search, ProductSort sort});
