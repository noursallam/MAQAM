import 'rewards.dart';

export 'rewards.dart';

/// A customer's points and where they stand between two ranks.
class Wallet {
  const Wallet({
    required this.balance,
    required this.totalEarned,
    required this.progress,
    required this.pointsToNextRank,
    this.rankName,
    this.rankIconUrl,
    this.nextRankName,
    this.nextRankMinPoints,
    this.pendingRankUp,
  });

  /// Points that can be spent now.
  final int balance;

  /// Lifetime points; this, not the balance, decides the rank.
  final int totalEarned;

  /// Progress toward the next rank, from 0 to 1.
  final double progress;
  final int pointsToNextRank;
  final String? rankName;
  final String? rankIconUrl;

  /// Null at the highest rank.
  final String? nextRankName;
  final int? nextRankMinPoints;

  /// Non-null when the customer just earned a new rank and hasn't seen the celebration yet.
  final PendingRankUp? pendingRankUp;
}

class PendingRankUp {
  const PendingRankUp({required this.rankName, this.rankIconUrl});

  final String rankName;
  final String? rankIconUrl;
}

class PointsTransaction {
  const PointsTransaction({
    required this.id,
    required this.points,
    required this.description,
    this.date,
  });

  final int id;

  /// Signed: positive when points were added, negative when they left.
  final int points;
  final String description;
  final DateTime? date;
}

enum TransactionFilter {
  all(null),
  earned('earn'),
  spent('spend');

  const TransactionFilter(this.apiValue);

  final String? apiValue;
}

abstract interface class LoyaltyRepository {
  Future<Wallet> wallet();

  /// Clears the pending rank-up flag so the celebration screen is not shown again.
  Future<void> acknowledgeRankUp();

  /// The most recent transactions, newest first.
  Future<List<PointsTransaction>> transactions({TransactionFilter filter = TransactionFilter.all});

  /// The customer's rewards in [status], newest first.
  Future<List<Reward>> rewards(RewardStatus status);

  Future<WheelConfig> wheel();

  /// Spends a spin's points and returns where the wheel landed.
  Future<SpinOutcome> spin();

  Future<List<LeaderboardEntry>> leaderboard();
}

class LeaderboardEntry {
  const LeaderboardEntry({required this.rank, required this.name, required this.points});

  final int rank;
  final String name;
  final int points;
}
