/// Where an order is on its way to the customer.
enum OrderStatus {
  received,
  preparing,
  shipped,
  delivered,
  cancelled;

  /// Still on its way: neither delivered nor cancelled.
  bool get isActive => this == received || this == preparing || this == shipped;
}

class Order {
  const Order({
    required this.number,
    required this.status,
    required this.total,
    this.paymentMethod,
    this.placedAt,
    this.canCancel = false,
    this.awaitingPayment = false,
  });

  final String number;
  final OrderStatus status;
  final double total;

  /// The API's own value: `cod`, `kashier` or `wallet`.
  final String? paymentMethod;
  final DateTime? placedAt;
  final bool canCancel;

  /// An online payment that was started and has not gone through: the order
  /// exists, but nothing is prepared until it is paid.
  final bool awaitingPayment;
}

class OrderDetails {
  const OrderDetails({
    required this.order,
    this.items = const [],
    this.subtotal = 0,
    this.discount = 0,
    this.shipping = 0,
    this.recipientName,
    this.recipientPhone,
    this.addressLine,
  });

  final Order order;
  final List<OrderItem> items;
  final double subtotal;
  final double discount;
  final double shipping;
  final String? recipientName;
  final String? recipientPhone;

  /// Governorate, city and street, already joined for display.
  final String? addressLine;
}

class OrderItem {
  const OrderItem({
    required this.name,
    required this.quantity,
    required this.lineTotal,
    this.imageUrl,
    this.optionLabel,
  });

  final String name;
  final int quantity;

  /// Zero for a gift, such as a prize won on the wheel.
  final double lineTotal;
  final String? imageUrl;
  final String? optionLabel;
}

abstract interface class OrdersRepository {
  /// The customer's most recent orders, newest first.
  Future<List<Order>> orders();

  Future<OrderDetails> order(String number);

  /// Cancels an order that has not shipped; points and rewards are given back.
  Future<OrderDetails> cancel(String number);
}
