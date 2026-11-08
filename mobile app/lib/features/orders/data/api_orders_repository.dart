import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/locale/locale_provider.dart';
import '../../../core/network/api_client.dart';
import '../../../core/session/session_provider.dart';
import '../domain/order.dart';

class ApiOrdersRepository implements OrdersRepository {
  const ApiOrdersRepository(this._api);

  final ApiClient _api;

  @override
  Future<List<Order>> orders() async {
    final page = await _api.getPage('/orders', query: {'per_page': '30'});
    return [for (final json in page.items) _order(json)];
  }

  @override
  Future<OrderDetails> order(String number) async =>
      _details(await _api.get('/orders/$number') as Map<String, dynamic>);

  @override
  Future<OrderDetails> cancel(String number) async =>
      _details(await _api.post('/orders/$number/cancel') as Map<String, dynamic>);

  static Order _order(Map<String, dynamic> json) => Order(
        number: json['order_number'] as String,
        status: _status(json['status'] as String?),
        total: _money(json['total_amount']),
        paymentMethod: json['payment_method'] as String?,
        placedAt: DateTime.tryParse(json['created_at'] as String? ?? '')?.toLocal(),
        canCancel: json['can_cancel'] == true,
        awaitingPayment: json['payment_method'] == 'kashier' &&
            json['payment_status'] != 'paid' &&
            _status(json['status'] as String?) != OrderStatus.cancelled,
      );

  static OrderDetails _details(Map<String, dynamic> json) {
    final address = json['shipping_address'] as Map<String, dynamic>?;
    return OrderDetails(
      order: _order(json),
      subtotal: _money(json['subtotal']),
      discount: _money(json['discount']),
      shipping: _money(json['shipping_cost']),
      recipientName: address?['recipient_name'] as String?,
      recipientPhone: address?['phone'] as String?,
      addressLine: address == null
          ? null
          : [address['governorate'], address['city'], address['address']]
              .whereType<String>()
              .where((part) => part.isNotEmpty)
              .join('، '),
      items: [
        for (final item in (json['items'] as List<dynamic>? ?? const []).cast<Map<String, dynamic>>())
          OrderItem(
            name: (item['product'] as Map<String, dynamic>?)?['name'] as String? ?? '',
            imageUrl: (item['product'] as Map<String, dynamic>?)?['image_url'] as String?,
            optionLabel: item['option_label'] as String?,
            quantity: item['quantity'] as int? ?? 1,
            lineTotal: _money(item['subtotal']),
          ),
      ],
    );
  }

  /// The server's statuses, folded into the four steps the app shows.
  static OrderStatus _status(String? status) => switch (status) {
        'processing' => OrderStatus.preparing,
        'shipped' => OrderStatus.shipped,
        'delivered' => OrderStatus.delivered,
        'cancelled' || 'refunded' => OrderStatus.cancelled,
        _ => OrderStatus.received,
      };

  /// Money arrives as a string or a number.
  static double _money(Object? value) => double.tryParse('$value') ?? 0;
}

final ordersRepositoryProvider = Provider<OrdersRepository>(
  (ref) => ApiOrdersRepository(ref.watch(apiClientProvider)),
);

final ordersProvider = FutureProvider.autoDispose<List<Order>>((ref) {
  ref.watch(sessionProvider);
  return ref.watch(ordersRepositoryProvider).orders();
});

final orderDetailsProvider = FutureProvider.autoDispose.family<OrderDetails, String>((ref, number) {
  // Product names come back in the app language.
  ref.watch(localeProvider);
  return ref.watch(ordersRepositoryProvider).order(number);
});
