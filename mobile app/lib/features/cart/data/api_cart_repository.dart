import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/cart.dart';

class ApiCartRepository implements CartRepository {
  const ApiCartRepository(this._api);

  final ApiClient _api;

  @override
  Future<Cart> cart() async => _cart(await _api.get('/store/cart'));

  @override
  Future<Cart> add({
    required int productId,
    int quantity = 1,
    int? optionId,
    String? color,
  }) async {
    return _cart(await _api.post('/store/cart/add', body: {
      'product_id': productId,
      'quantity': quantity,
      'option_id': ?optionId,
      'color': ?color,
    }));
  }

  @override
  Future<Cart> setQuantity(int itemId, int quantity) async {
    return _cart(await _api.post('/store/cart/update', body: {
      'item_id': itemId,
      'quantity': quantity,
    }));
  }

  @override
  Future<Cart> applyCoupon(String code) async =>
      _cart(await _api.post('/store/cart/coupon', body: {'code': code}));

  @override
  Future<Cart> removeCoupon() async => _cart(await _api.delete('/store/cart/coupon'));

  @override
  Future<List<Address>> addresses() async {
    final data = await _api.get('/addresses') as List<dynamic>;
    return [
      for (final json in data.cast<Map<String, dynamic>>())
        Address(
          id: json['id'] as int,
          recipientName: json['recipient_name'] as String? ?? '',
          phone: json['phone'] as String? ?? '',
          governorate: json['governorate'] as String? ?? '',
          city: json['city'] as String? ?? '',
          street: json['address'] as String? ?? '',
          isDefault: json['is_default'] == true,
        ),
    ];
  }

  @override
  Future<PlacedOrder> checkout({
    int? addressId,
    NewAddress? newAddress,
    required PaymentMethod payment,
    String notes = '',
  }) async {
    final data = await _api.post('/store/checkout', body: {
      'payment_method': payment.apiValue,
      'address_id': ?addressId,
      if (newAddress != null) ...{
        'full_name': newAddress.recipientName,
        'phone': newAddress.phone,
        'governorate': newAddress.governorate,
        'city': newAddress.city,
        'address': newAddress.street,
      },
      if (notes.isNotEmpty) 'notes': notes,
    }) as Map<String, dynamic>;
    final paymentUrl = data['payment_url'] as String?;
    final returnUrl = data['payment_return_url'] as String?;
    return PlacedOrder(
      orderNumber: (data['order'] as Map<String, dynamic>)['order_number'] as String,
      paymentUrl: paymentUrl == null ? null : Uri.parse(paymentUrl),
      paymentReturnUrl: returnUrl == null ? null : Uri.parse(returnUrl),
    );
  }

  @override
  Future<PaymentState> paymentState(String orderNumber) async {
    final order = await _api.get('/orders/$orderNumber') as Map<String, dynamic>;
    return switch (order['payment_status']) {
      'paid' => PaymentState.paid,
      'failed' => PaymentState.failed,
      _ => order['status'] == 'cancelled' ? PaymentState.failed : PaymentState.pending,
    };
  }

  static Cart _cart(dynamic data) {
    final json = data as Map<String, dynamic>;
    return Cart(
      items: [
        for (final item in (json['items'] as List<dynamic>).cast<Map<String, dynamic>>())
          CartItem(
            id: item['id'] as int,
            name: (item['product'] as Map<String, dynamic>?)?['name'] as String? ?? '',
            imageUrl: (item['product'] as Map<String, dynamic>?)?['image_url'] as String?,
            optionLabel: item['option_label'] as String?,
            quantity: item['quantity'] as int? ?? 1,
            unitPrice: _money(item['unit_price']),
          ),
      ],
      itemsCount: json['items_count'] as int? ?? 0,
      couponCode: json['coupon_code'] as String?,
      subtotal: _money(json['subtotal']),
      discount: _money(json['discount']),
      shipping: _money(json['shipping']),
      total: _money(json['total']),
    );
  }

  /// Money arrives as a string such as `"75.00"`.
  static double _money(Object? value) => double.tryParse('$value') ?? 0;
}

final cartRepositoryProvider = Provider<CartRepository>(
  (ref) => ApiCartRepository(ref.watch(apiClientProvider)),
);
