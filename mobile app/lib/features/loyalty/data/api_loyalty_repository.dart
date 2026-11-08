import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/wallet.dart';

class ApiLoyaltyRepository implements LoyaltyRepository {
  const ApiLoyaltyRepository(this._api);

  final ApiClient _api;

  /// Types that take points away; the API sends every amount as a positive number.
  static const _outgoing = {'spend', 'expire'};

  @override
  Future<Wallet> wallet() async {
    final data = await _api.get('/wallet') as Map<String, dynamic>;
    final rank = data['rank'] as Map<String, dynamic>?;
    final nextRank = data['next_rank'] as Map<String, dynamic>?;
    final iconPath = rank?['icon_url'] as String?;
    final origin = Uri.parse(_api.baseUrl).origin;
    final pendingRank = data['pending_rank_up'] as Map<String, dynamic>?;
    final pendingIconPath = pendingRank?['icon_url'] as String?;
    return Wallet(
      balance: data['points_balance'] as int? ?? 0,
      totalEarned: data['total_points_earned'] as int? ?? 0,
      progress: ((data['progress_percent'] as int? ?? 0) / 100).clamp(0, 1).toDouble(),
      pointsToNextRank: data['points_left_for_next_rank'] as int? ?? 0,
      rankName: rank?['name'] as String?,
      rankIconUrl: iconPath != null ? '$origin$iconPath' : null,
      nextRankName: nextRank?['name'] as String?,
      nextRankMinPoints: nextRank?['min_points'] as int?,
      pendingRankUp: pendingRank != null
          ? PendingRankUp(
              rankName: pendingRank['name'] as String? ?? '',
              rankIconUrl: pendingIconPath != null ? '$origin$pendingIconPath' : null,
            )
          : null,
    );
  }

  @override
  Future<void> acknowledgeRankUp() async {
    await _api.post('/wallet/acknowledge-rank-up');
  }

  @override
  Future<List<PointsTransaction>> transactions({
    TransactionFilter filter = TransactionFilter.all,
  }) async {
    final page = await _api.getPage('/wallet/transactions', query: {
      'per_page': '20',
      if (filter.apiValue != null) 'type': filter.apiValue!,
    });
    return [
      for (final json in page.items)
        PointsTransaction(
          id: json['id'] as int,
          points: _signed(json['amount'] as int? ?? 0, json['type'] as String?),
          description: json['description'] as String? ?? '',
          date: DateTime.tryParse(json['date'] as String? ?? '')?.toLocal(),
        ),
    ];
  }

  static int _signed(int amount, String? type) =>
      _outgoing.contains(type) ? -amount.abs() : amount;

  @override
  Future<List<Reward>> rewards(RewardStatus status) async {
    final page = await _api.getPage('/customer/rewards', query: {
      'status': status.apiValue,
      'per_page': '30',
    });
    return [for (final json in page.items) _reward(json)];
  }

  @override
  Future<WheelConfig> wheel() async {
    final data = await _api.get('/wheel/config') as Map<String, dynamic>;
    return WheelConfig(
      enabled: data['is_enabled'] == true,
      cost: data['cost_points'] as int? ?? 0,
      balance: data['customer_balance'] as int? ?? 0,
      canSpin: data['can_spin'] == true,
      slices: [
        for (final slice in (data['slices'] as List<dynamic>? ?? const []).cast<Map<String, dynamic>>())
          WheelSlice(
            label: slice['label'] as String? ?? '',
            isPrize: slice['type'] != 'none',
          ),
      ],
    );
  }

  @override
  Future<SpinOutcome> spin() async {
    final data = await _api.post('/wheel/spin') as Map<String, dynamic>;
    final spin = data['spin'] as Map<String, dynamic>? ?? const {};
    final reward = data['reward'] as Map<String, dynamic>?;
    return SpinOutcome(
      won: spin['is_win'] == true,
      sliceIndex: data['slice_index'] as int? ?? 0,
      newBalance: data['new_points_balance'] as int? ?? 0,
      prizeLabel: spin['prize_label'] as String?,
      reward: reward == null ? null : _reward(reward),
    );
  }

  @override
  Future<List<LeaderboardEntry>> leaderboard() async {
    final data = await _api.get('/leaderboard') as List<dynamic>;
    return [
      for (final json in data.cast<Map<String, dynamic>>())
        LeaderboardEntry(
          rank: json['rank'] as int,
          name: json['name'] as String? ?? '',
          points: json['points'] as int? ?? 0,
        ),
    ];
  }

  static Reward _reward(Map<String, dynamic> json) {
    final product = json['product'] as Map<String, dynamic>?;
    return Reward(
      id: json['id'] as int,
      code: json['code'] as String? ?? '',
      kind: switch (json['type']) {
        'product' => RewardKind.product,
        'discount' => RewardKind.discount,
        _ => RewardKind.coupon,
      },
      fromWheel: json['source'] == 'wheel',
      isPercentage: json['amount_type'] == 'percentage',
      amount: double.tryParse('${json['amount_value']}') ?? 0,
      productName: product?['name'] as String?,
      productImageUrl: product?['image_url'] as String?,
      expiresAt: DateTime.tryParse(json['expires_at'] as String? ?? '')?.toLocal(),
      usedAt: DateTime.tryParse(json['used_at'] as String? ?? '')?.toLocal(),
    );
  }
}

final loyaltyRepositoryProvider = Provider<LoyaltyRepository>(
  (ref) => ApiLoyaltyRepository(ref.watch(apiClientProvider)),
);
