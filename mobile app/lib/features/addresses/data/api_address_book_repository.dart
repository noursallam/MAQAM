import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/address_book.dart';

class ApiAddressBookRepository implements AddressBookRepository {
  const ApiAddressBookRepository(this._api);

  final ApiClient _api;

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
  Future<void> add(AddressDraft draft) => _api.post('/addresses', body: _body(draft));

  @override
  Future<void> update(int id, AddressDraft draft, {bool makeDefault = false}) =>
      _api.put('/addresses/$id', body: {..._body(draft), if (makeDefault) 'is_default': true});

  @override
  Future<void> delete(int id) => _api.delete('/addresses/$id');

  static Map<String, Object?> _body(AddressDraft draft) => {
        'recipient_name': draft.recipientName,
        'phone': draft.phone,
        'governorate': draft.governorate,
        'city': draft.city,
        'address': draft.street,
      };
}

final addressBookRepositoryProvider = Provider<AddressBookRepository>(
  (ref) => ApiAddressBookRepository(ref.watch(apiClientProvider)),
);
