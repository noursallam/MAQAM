import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/account_repository.dart';

class ApiAccountRepository implements AccountRepository {
  const ApiAccountRepository(this._api);

  final ApiClient _api;

  @override
  Future<void> updateName(String fullName) => _api.put('/user/profile', body: {'full_name': fullName});

  @override
  Future<void> deleteAccount() => _api.delete('/user/account', body: {'confirm': 'DELETE'});

  @override
  Future<({int id, String name})> bindMerchant(String merchantCode) async {
    final data = await _api.post('/user/bind-merchant', body: {'merchant_code': merchantCode})
        as Map<String, dynamic>;
    final merchant = data['merchant'] as Map<String, dynamic>;
    return (id: merchant['id'] as int, name: merchant['business_name'] as String);
  }

  @override
  Future<void> requestMerchantChange(String merchantCode) =>
      _api.post('/user/request-merchant-change', body: {'merchant_code': merchantCode});

  @override
  Future<String?> merchantChangeStatus() async {
    final data = await _api.get('/user/merchant-change-status') as Map<String, dynamic>?;
    return data?['status'] as String?;
  }
}

final accountRepositoryProvider = Provider<AccountRepository>(
  (ref) => ApiAccountRepository(ref.watch(apiClientProvider)),
);
