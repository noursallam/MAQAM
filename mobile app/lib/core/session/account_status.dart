import 'package:flutter_riverpod/flutter_riverpod.dart';

/// True once the server has said the account is frozen for review. The router
/// then keeps the customer on the suspended-account screen until they sign out.
final accountFrozenProvider = NotifierProvider<AccountFrozenNotifier, bool>(AccountFrozenNotifier.new);

class AccountFrozenNotifier extends Notifier<bool> {
  @override
  bool build() => false;

  void freeze() => state = true;

  void clear() => state = false;
}
