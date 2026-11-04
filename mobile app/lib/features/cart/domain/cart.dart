class Cart {
  const Cart({
    this.items = const [],
    this.itemsCount = 0,
    this.couponCode,
    this.subtotal = 0,
    this.discount = 0,
    this.shipping = 0,
    this.total = 0,
  });

  final List<CartItem> items;

  /// Total number of units, counting quantities.
  final int itemsCount;
  final String? couponCode;
  final double subtotal;
  final double discount;
  final double shipping;
  final double total;

  bool get isEmpty => items.isEmpty;
}

class CartItem {
  const CartItem({
    required this.id,
    required this.name,
    required this.quantity,
    required this.unitPrice,
    this.imageUrl,
    this.optionLabel,
  });

  final int id;
  final String name;
  final int quantity;
  final double unitPrice;
  final String? imageUrl;

  /// The chosen variant and colour, as the server describes them.
  final String? optionLabel;
}

class Address {
  const Address({
    required this.id,
    required this.recipientName,
    required this.phone,
    required this.governorate,
    required this.city,
    required this.street,
    this.isDefault = false,
  });

  final int id;
  final String recipientName;
  final String phone;
  final String governorate;
  final String city;
  final String street;
  final bool isDefault;
}

/// A delivery address typed at checkout instead of picked from the saved ones.
class NewAddress {
  const NewAddress({
    required this.recipientName,
    required this.phone,
    required this.governorate,
    required this.city,
    required this.street,
  });

  final String recipientName;
  final String phone;
  final String governorate;
  final String city;
  final String street;
}

enum PaymentMethod {
  cashOnDelivery('cod'),
  online('kashier'),
  loyaltyPoints('wallet');

  const PaymentMethod(this.apiValue);

  final String apiValue;
}

class PlacedOrder {
  const PlacedOrder({required this.orderNumber, this.paymentUrl, this.paymentReturnUrl});

  final String orderNumber;

  /// Where the customer pays, for an online payment that is still to be made.
  final Uri? paymentUrl;

  /// The gateway sends the customer to an address starting with this when
  /// they are done, whether or not they paid.
  final Uri? paymentReturnUrl;
}

/// What the server knows about an online payment.
enum PaymentState { paid, pending, failed }

abstract interface class CartRepository {
  Future<Cart> cart();

  Future<Cart> add({required int productId, int quantity = 1, int? optionId, String? color});

  /// A quantity of zero removes the item.
  Future<Cart> setQuantity(int itemId, int quantity);

  Future<Cart> applyCoupon(String code);

  Future<Cart> removeCoupon();

  Future<List<Address>> addresses();

  /// How the online payment for an order stands, as the gateway told the server.
  Future<PaymentState> paymentState(String orderNumber);

  /// Places the order for the whole cart. Give either a saved [addressId] or a [newAddress].
  Future<PlacedOrder> checkout({
    int? addressId,
    NewAddress? newAddress,
    required PaymentMethod payment,
    String notes = '',
  });
}
