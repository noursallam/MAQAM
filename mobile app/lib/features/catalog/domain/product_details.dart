import 'product.dart';

/// Everything the product screen shows.
class ProductDetails {
  const ProductDetails({
    required this.product,
    this.sku,
    this.description = '',
    this.weight,
    this.dimensions,
    this.images = const [],
    this.colors = const [],
    this.options = const [],
  });

  final Product product;
  final String? sku;
  final String description;

  /// As the admin panel wrote them, for example "0.25" (kg) and "86 x 86 mm".
  final String? weight;
  final String? dimensions;

  /// Gallery image URLs; falls back to the product's thumbnail when empty.
  final List<String> images;
  final List<ProductColor> colors;
  final List<ProductOption> options;

  List<String> get gallery =>
      images.isNotEmpty ? images : [if (product.imageUrl != null) product.imageUrl!];
}

class ProductColor {
  const ProductColor({required this.name, this.hex});

  final String name;

  /// `#RRGGBB`, or null when the admin panel gave no swatch.
  final String? hex;
}

/// One purchasable variant, for example "Size: 48 mm", with its own price.
class ProductOption {
  const ProductOption({
    required this.id,
    required this.name,
    required this.value,
    required this.price,
  });

  final int id;

  /// What kind of choice this is, shared by the options that are alternatives.
  final String name;
  final String value;
  final double price;
}
