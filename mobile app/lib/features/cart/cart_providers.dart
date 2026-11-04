import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/locale/locale_provider.dart';
import '../../core/session/session_provider.dart';
import 'data/api_cart_repository.dart';
import 'domain/cart.dart';

/// The signed-in customer's cart, or null for a guest (the cart needs an account).
///
/// Every cart request answers with the whole cart, so each change replaces the
/// state with what the server now holds. A failed change throws to the caller
/// and leaves the cart as it was.
final cartProvider = AsyncNotifierProvider<CartNotifier, Cart?>(CartNotifier.new);

class CartNotifier extends AsyncNotifier<Cart?> {
  @override
  Future<Cart?> build() async {
    ref.watch(localeProvider);
    if (ref.watch(sessionProvider) == null) return null;
    return _repository.cart();
  }

  CartRepository get _repository => ref.read(cartRepositoryProvider);

  Future<void> add({required int productId, int quantity = 1, int? optionId, String? color}) async {
    state = AsyncData(await _repository.add(
      productId: productId,
      quantity: quantity,
      optionId: optionId,
      color: color,
    ));
  }

  Future<void> setQuantity(int itemId, int quantity) async {
    state = AsyncData(await _repository.setQuantity(itemId, quantity));
  }

  Future<void> applyCoupon(String code) async {
    state = AsyncData(await _repository.applyCoupon(code));
  }

  Future<void> removeCoupon() async {
    state = AsyncData(await _repository.removeCoupon());
  }

  Future<PlacedOrder> checkout({
    int? addressId,
    NewAddress? newAddress,
    required PaymentMethod payment,
    String notes = '',
  }) async {
    final order = await _repository.checkout(
      addressId: addressId,
      newAddress: newAddress,
      payment: payment,
      notes: notes,
    );
    // Placing the order empties the cart on the server.
    state = const AsyncData(Cart());
    return order;
  }
}

/// How many units are in the cart, for the badge on the cart icon.
final cartCountProvider = Provider<int>((ref) => ref.watch(cartProvider).value?.itemsCount ?? 0);

final addressesProvider = FutureProvider.autoDispose<List<Address>>((ref) {
  ref.watch(sessionProvider);
  return ref.watch(cartRepositoryProvider).addresses();
});
