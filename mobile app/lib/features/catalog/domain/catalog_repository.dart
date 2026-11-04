import 'category.dart';
import 'product.dart';
import 'product_details.dart';
import 'promo_banner.dart';

abstract interface class CatalogRepository {
  Future<List<Category>> categories();

  Future<List<PromoBanner>> banners();

  Future<ProductDetails> product(int id);

  Future<ProductPage> products({
    int? categoryId,
    String search = '',
    ProductSort sort = ProductSort.newest,
    int perPage = 30,
  });
}
