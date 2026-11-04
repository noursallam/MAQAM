import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/session/session_provider.dart';
import '../cart/cart_providers.dart';
import 'data/api_address_book_repository.dart';
import 'domain/address_book.dart';

/// The signed-in customer's saved addresses. Each change asks the server for
/// the list again, since saving one can move the default to another.
final addressBookProvider =
    AsyncNotifierProvider.autoDispose<AddressBookNotifier, List<Address>>(AddressBookNotifier.new);

class AddressBookNotifier extends AsyncNotifier<List<Address>> {
  @override
  Future<List<Address>> build() {
    ref.watch(sessionProvider);
    return _repository.addresses();
  }

  AddressBookRepository get _repository => ref.read(addressBookRepositoryProvider);

  Future<void> add(AddressDraft draft) => _change(() => _repository.add(draft));

  Future<void> save(int id, AddressDraft draft) => _change(() => _repository.update(id, draft));

  Future<void> makeDefault(Address address) => _change(
        () => _repository.update(address.id, AddressDraft.of(address), makeDefault: true),
      );

  Future<void> delete(int id) => _change(() => _repository.delete(id));

  /// Throws to the caller if the change fails, leaving the list as it was.
  Future<void> _change(Future<void> Function() change) async {
    await change();
    // Checkout offers the default address, so it must see the change too.
    ref.invalidate(addressesProvider);
    state = AsyncData(await _repository.addresses());
  }
}
