import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/features/cart/data/api_cart_repository.dart';
import 'package:maqam_app/features/cart/domain/cart.dart';

http.Response _json(Object? data, [int status = 200]) => http.Response.bytes(
      utf8.encode(jsonEncode({'success': true, 'message': '', 'data': data})),
      status,
    );

const _cartJson = {
  'id': 3,
  'items': [
    {
      'id': 9,
      'product': {'id': 1, 'name': 'مفتاح', 'image_url': 'https://example.test/a.jpg'},
      'product_option_id': 12,
      'option_label': 'أسود · 60 مللي',
      'quantity': 2,
      'unit_price': '90.00',
      'line_total': '180.00',
    },
    {'id': 10, 'product': null, 'option_label': null, 'quantity': 1, 'unit_price': '20.50'},
  ],
  'items_count': 3,
  'coupon_code': 'SAVE10',
  'subtotal': '200.50',
  'discount': '10.00',
  'shipping': '35.00',
  'total': '225.50',
  'currency': 'EGP',
};

void main() {
  late List<http.Request> requests;
  late ApiCartRepository repository;

  ApiCartRepository build(Object? Function(http.Request request) answer) {
    requests = [];
    return ApiCartRepository(
      ApiClient(
        httpClient: MockClient((request) async {
          requests.add(request);
          return _json(answer(request), request.url.path.endsWith('/checkout') ? 201 : 200);
        }),
        token: () => 't',
      ),
    );
  }

  test('maps the cart, including a line whose product was deleted', () async {
    repository = build((_) => _cartJson);

    final cart = await repository.cart();

    expect(cart.itemsCount, 3);
    expect(cart.couponCode, 'SAVE10');
    expect(cart.subtotal, 200.5);
    expect(cart.discount, 10);
    expect(cart.shipping, 35);
    expect(cart.total, 225.5);
    expect(cart.items.first.name, 'مفتاح');
    expect(cart.items.first.optionLabel, 'أسود · 60 مللي');
    expect(cart.items.first.unitPrice, 90);
    expect(cart.items.last.name, '');
    expect(cart.items.last.imageUrl, isNull);
  });

  test('sends each cart change to its endpoint', () async {
    repository = build((_) => _cartJson);

    await repository.add(productId: 1, quantity: 2, optionId: 12, color: 'أسود');
    await repository.add(productId: 4);
    await repository.setQuantity(9, 0);
    await repository.applyCoupon('SAVE10');
    await repository.removeCoupon();

    expect(requests.map((r) => '${r.method} ${r.url.path.split('/store').last}'), [
      'POST /cart/add',
      'POST /cart/add',
      'POST /cart/update',
      'POST /cart/coupon',
      'DELETE /cart/coupon',
    ]);
    expect(jsonDecode(requests[0].body), {
      'product_id': 1,
      'quantity': 2,
      'option_id': 12,
      'color': 'أسود',
    });
    // Nothing is sent for a variant or colour that was not chosen.
    expect(jsonDecode(requests[1].body), {'product_id': 4, 'quantity': 1});
    expect(jsonDecode(requests[2].body), {'item_id': 9, 'quantity': 0});
  });

  test('checks out with a saved address or a typed one', () async {
    repository = build((request) => request.url.path.endsWith('/checkout')
        ? {
            'order': {'order_number': 'MQ-1'},
            'payment_url': jsonDecode(request.body)['payment_method'] == 'kashier'
                ? 'https://pay.example.test/s/1'
                : null,
            'payment_return_url': jsonDecode(request.body)['payment_method'] == 'kashier'
                ? 'https://maqam-eg.com/payment/kashier/callback'
                : null,
          }
        : null);

    final cash = await repository.checkout(addressId: 5, payment: PaymentMethod.cashOnDelivery);
    final online = await repository.checkout(
      newAddress: const NewAddress(
        recipientName: 'أحمد',
        phone: '01012345678',
        governorate: 'القاهرة',
        city: 'مدينة نصر',
        street: 'شارع الطيران',
      ),
      payment: PaymentMethod.online,
      notes: 'اتصل أولاً',
    );

    expect(cash.orderNumber, 'MQ-1');
    expect(cash.paymentUrl, isNull);
    expect(jsonDecode(requests[0].body), {'payment_method': 'cod', 'address_id': 5});

    expect(online.paymentUrl, Uri.parse('https://pay.example.test/s/1'));
    expect(online.paymentReturnUrl, Uri.parse('https://maqam-eg.com/payment/kashier/callback'));
    expect(jsonDecode(requests[1].body), {
      'payment_method': 'kashier',
      'full_name': 'أحمد',
      'phone': '01012345678',
      'governorate': 'القاهرة',
      'city': 'مدينة نصر',
      'address': 'شارع الطيران',
      'notes': 'اتصل أولاً',
    });
  });

  test('reads how an online payment stands from the order', () async {
    final orders = {
      'MQ-PAID': {'status': 'processing', 'payment_status': 'paid'},
      'MQ-WAIT': {'status': 'pending', 'payment_status': 'pending'},
      'MQ-FAIL': {'status': 'pending', 'payment_status': 'failed'},
      'MQ-GONE': {'status': 'cancelled', 'payment_status': 'pending'},
    };
    repository = build((request) => orders[request.url.pathSegments.last]);

    expect(await repository.paymentState('MQ-PAID'), PaymentState.paid);
    expect(await repository.paymentState('MQ-WAIT'), PaymentState.pending);
    expect(await repository.paymentState('MQ-FAIL'), PaymentState.failed);
    expect(await repository.paymentState('MQ-GONE'), PaymentState.failed);
  });

  test('maps saved addresses', () async {
    repository = build((_) => [
          {
            'id': 5,
            'recipient_name': 'أحمد',
            'phone': '01012345678',
            'governorate': 'القاهرة',
            'city': 'مدينة نصر',
            'address': 'شارع الطيران',
            'notes': null,
            'is_default': true,
          },
        ]);

    final address = (await repository.addresses()).single;

    expect(address.id, 5);
    expect(address.street, 'شارع الطيران');
    expect(address.isDefault, isTrue);
  });
}
