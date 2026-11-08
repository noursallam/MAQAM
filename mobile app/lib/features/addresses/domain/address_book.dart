import '../../cart/domain/cart.dart';

export '../../cart/domain/cart.dart' show Address;

/// The fields of an address as typed into the form.
class AddressDraft {
  const AddressDraft({
    required this.recipientName,
    required this.phone,
    required this.governorate,
    required this.city,
    required this.street,
  });

  factory AddressDraft.of(Address address) => AddressDraft(
        recipientName: address.recipientName,
        phone: address.phone,
        governorate: address.governorate,
        city: address.city,
        street: address.street,
      );

  final String recipientName;

  /// `01XXXXXXXXX`.
  final String phone;
  final String governorate;
  final String city;
  final String street;
}

abstract interface class AddressBookRepository {
  /// Saved addresses, the default first.
  Future<List<Address>> addresses();

  /// Adds an address. The first one saved becomes the default.
  Future<void> add(AddressDraft draft);

  /// Replaces the address with [id]. [makeDefault] also makes it the default.
  Future<void> update(int id, AddressDraft draft, {bool makeDefault = false});

  Future<void> delete(int id);
}
