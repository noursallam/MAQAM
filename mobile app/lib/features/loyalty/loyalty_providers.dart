import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/locale/locale_provider.dart';
import '../../core/session/session_provider.dart';
import 'data/api_loyalty_repository.dart';
import 'domain/wallet.dart';

/// The signed-in customer's wallet, or null for a guest. Reloads when the
/// account or the language (rank names are localized) changes.
final walletProvider = FutureProvider<Wallet?>((ref) async {
  ref.watch(localeProvider);
  if (ref.watch(sessionProvider) == null) return null;
  return ref.watch(loyaltyRepositoryProvider).wallet();
});

final transactionsProvider =
    FutureProvider.autoDispose.family<List<PointsTransaction>, TransactionFilter>((ref, filter) {
  ref.watch(localeProvider);
  ref.watch(sessionProvider);
  return ref.watch(loyaltyRepositoryProvider).transactions(filter: filter);
});

final rewardsProvider =
    FutureProvider.autoDispose.family<List<Reward>, RewardStatus>((ref, status) {
  ref.watch(localeProvider);
  ref.watch(sessionProvider);
  return ref.watch(loyaltyRepositoryProvider).rewards(status);
});

final leaderboardProvider = FutureProvider.autoDispose<List<LeaderboardEntry>>((ref) {
  ref.watch(localeProvider);
  return ref.watch(loyaltyRepositoryProvider).leaderboard();
});

/// The wheel as the signed-in customer sees it: slices, cost and balance.
final wheelProvider = AsyncNotifierProvider.autoDispose<WheelNotifier, WheelConfig>(WheelNotifier.new);

class WheelNotifier extends AsyncNotifier<WheelConfig> {
  @override
  Future<WheelConfig> build() {
    ref.watch(localeProvider);
    ref.watch(sessionProvider);
    return ref.watch(loyaltyRepositoryProvider).wheel();
  }

  /// Spends a spin. Throws an [ApiException] if the server refuses; otherwise
  /// the balance here, in the wallet and in the rewards follow the outcome.
  Future<SpinOutcome> spin() async {
    final outcome = await ref.read(loyaltyRepositoryProvider).spin();
    final config = state.value;
    if (config != null) {
      state = AsyncData(WheelConfig(
        enabled: config.enabled,
        cost: config.cost,
        balance: outcome.newBalance,
        canSpin: config.enabled && outcome.newBalance >= config.cost,
        slices: config.slices,
      ));
    }
    ref.invalidate(walletProvider);
    ref.invalidate(rewardsProvider);
    ref.invalidate(transactionsProvider);
    return outcome;
  }
}
