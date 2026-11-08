import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/session/session_provider.dart';
import 'data/api_merchant_repository.dart';
import 'domain/merchant.dart';

/// The customer's merchant profile, or null before they have applied.
final merchantProvider =
    AsyncNotifierProvider.autoDispose<MerchantNotifier, MerchantProfile?>(MerchantNotifier.new);

class MerchantNotifier extends AsyncNotifier<MerchantProfile?> {
  @override
  Future<MerchantProfile?> build() {
    ref.watch(sessionProvider);
    return _repository.profile();
  }

  MerchantRepository get _repository => ref.read(merchantRepositoryProvider);

  /// Throws an [ApiException] if the server refuses the request.
  Future<void> apply({required String businessName, String? businessAddress, LogoImage? logo}) async {
    await _repository.apply(businessName: businessName, businessAddress: businessAddress, logo: logo);
    // The answer to applying has no statistics; read the whole profile.
    state = AsyncData(await _repository.profile());
  }

  Future<void> uploadLogo(LogoImage logo) async {
    await _repository.uploadLogo(logo);
    state = AsyncData(await _repository.profile());
  }
}

final techniciansProvider = FutureProvider.autoDispose<List<Technician>>((ref) {
  ref.watch(sessionProvider);
  return ref.read(merchantRepositoryProvider).technicians();
});

final technicianDetailProvider =
    FutureProvider.autoDispose.family<TechnicianDetail, int>((ref, id) {
  ref.watch(sessionProvider);
  return ref.read(merchantRepositoryProvider).technician(id);
});

final merchantTransactionsProvider =
    FutureProvider.autoDispose<List<MerchantTransaction>>((ref) {
  ref.watch(sessionProvider);
  return ref.read(merchantRepositoryProvider).transactions();
});

/// Every scan credited to the merchant's code, a page at a time.
final merchantScansProvider =
    AsyncNotifierProvider.autoDispose<MerchantScansNotifier, MerchantScanList>(MerchantScansNotifier.new);

class MerchantScanList {
  const MerchantScanList({this.items = const [], this.hasMore = false, this.loadingMore = false});

  final List<MerchantScan> items;
  final bool hasMore;
  final bool loadingMore;
}

class MerchantScansNotifier extends AsyncNotifier<MerchantScanList> {
  int _page = 1;

  @override
  Future<MerchantScanList> build() async {
    ref.watch(sessionProvider);
    _page = 1;
    final first = await ref.read(merchantRepositoryProvider).scans();
    return MerchantScanList(items: first.items, hasMore: first.hasMore);
  }

  /// Throws an [ApiException] if the next page cannot be loaded; what is shown stays.
  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(MerchantScanList(items: current.items, hasMore: true, loadingMore: true));
    try {
      final next = await ref.read(merchantRepositoryProvider).scans(page: _page + 1);
      _page++;
      state = AsyncData(MerchantScanList(items: [...current.items, ...next.items], hasMore: next.hasMore));
    } catch (_) {
      state = AsyncData(current);
      rethrow;
    }
  }
}
