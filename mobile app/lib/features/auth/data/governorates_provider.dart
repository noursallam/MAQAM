import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';

final governoratesProvider = FutureProvider<List<String>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final data = await api.get('/governorates') as List;
  return data.cast<String>();
});
