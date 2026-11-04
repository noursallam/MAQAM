enum RewardStatus {
  available('available'),
  used('used'),
  expired('expired');

  const RewardStatus(this.apiValue);

  final String apiValue;
}

enum RewardKind { coupon, discount, product }

/// A prize or coupon the customer holds, redeemed by typing its code in the cart.
class Reward {
  const Reward({
    required this.id,
    required this.code,
    required this.kind,
    this.fromWheel = false,
    this.isPercentage = false,
    this.amount = 0,
    this.productName,
    this.productImageUrl,
    this.expiresAt,
    this.usedAt,
  });

  final int id;
  final String code;
  final RewardKind kind;
  final bool fromWheel;

  /// For a coupon or discount: a percentage off when true, an amount in EGP otherwise.
  final bool isPercentage;
  final double amount;

  /// For a free product.
  final String? productName;
  final String? productImageUrl;
  final DateTime? expiresAt;
  final DateTime? usedAt;
}

/// One slice of the lucky wheel, in the order it is drawn.
class WheelSlice {
  const WheelSlice({required this.label, required this.isPrize});

  final String label;

  /// False for the "better luck" slice.
  final bool isPrize;
}

class WheelConfig {
  const WheelConfig({
    required this.enabled,
    required this.cost,
    required this.balance,
    required this.canSpin,
    required this.slices,
  });

  final bool enabled;

  /// Points one spin takes; set by the customer's rank.
  final int cost;
  final int balance;
  final bool canSpin;
  final List<WheelSlice> slices;
}

/// What a spin landed on. The server decides; the wheel only shows it.
class SpinOutcome {
  const SpinOutcome({
    required this.won,
    required this.sliceIndex,
    required this.newBalance,
    this.prizeLabel,
    this.reward,
  });

  final bool won;

  /// Index into [WheelConfig.slices] where the wheel must stop.
  final int sliceIndex;
  final int newBalance;
  final String? prizeLabel;

  /// The coupon or product won, which then waits in the rewards list.
  final Reward? reward;
}
