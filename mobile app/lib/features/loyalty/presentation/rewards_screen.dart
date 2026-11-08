import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../cart/cart_providers.dart';
import '../domain/wallet.dart';
import '../loyalty_providers.dart';

/// The customer's coupons and prizes (design: "11 مكافآتي").
class RewardsScreen extends ConsumerStatefulWidget {
  const RewardsScreen({super.key, required this.onOpenCart, required this.onOpenWheel});

  final VoidCallback onOpenCart;
  final VoidCallback onOpenWheel;

  @override
  ConsumerState<RewardsScreen> createState() => _RewardsScreenState();
}

class _RewardsScreenState extends ConsumerState<RewardsScreen> {
  RewardStatus _status = RewardStatus.available;

  /// The reward whose code is being applied to the cart.
  int? _applying;

  Future<void> _useInCart(Reward reward) async {
    if (_applying != null) return;
    setState(() => _applying = reward.id);
    try {
      await ref.read(cartProvider.notifier).applyCoupon(reward.code);
      if (mounted) widget.onOpenCart();
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _applying = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final rewards = ref.watch(rewardsProvider(_status));

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.actionRewards),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => ref.refresh(rewardsProvider(_status).future),
                child: ListView(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  children: [
                    SegmentedTabs<RewardStatus>(
                      tabs: [
                        (RewardStatus.available, l10n.rewardsAvailable),
                        (RewardStatus.used, l10n.rewardsUsed),
                        (RewardStatus.expired, l10n.rewardsExpired),
                      ],
                      selected: _status,
                      onSelected: (status) => setState(() => _status = status),
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    AsyncView<List<Reward>>(
                      value: rewards,
                      errorTitle: l10n.rewardsLoadFailed,
                      onRetry: () => ref.invalidate(rewardsProvider(_status)),
                      data: (list) => list.isEmpty
                          ? MessageView(
                              icon: Icons.card_giftcard,
                              title: l10n.rewardsEmpty,
                              message: l10n.rewardsEmptyBody,
                            )
                          : Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                for (final reward in list) ...[
                                  _RewardCard(
                                    reward: reward,
                                    status: _status,
                                    applying: _applying == reward.id,
                                    onUse: () => _useInCart(reward),
                                  ),
                                  const SizedBox(height: AppSpacing.md),
                                ],
                                if (_status == RewardStatus.available) InfoNote(text: l10n.rewardsNote),
                              ],
                            ),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.lg),
              child: OutlinedButton(
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(AppSpacing.controlHeight),
                  foregroundColor: Theme.of(context).colorScheme.onSurface,
                  side: const BorderSide(color: AppColors.navy),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.radius)),
                ),
                onPressed: widget.onOpenWheel,
                child: Text(l10n.rewardsTryWheel),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RewardCard extends StatelessWidget {
  const _RewardCard({
    required this.reward,
    required this.status,
    required this.applying,
    required this.onUse,
  });

  final Reward reward;
  final RewardStatus status;
  final bool applying;
  final VoidCallback onUse;

  String _title(BuildContext context) {
    final l10n = context.l10n;
    if (reward.kind == RewardKind.product) {
      return l10n.rewardProduct(reward.productName ?? l10n.rewardProductFallback);
    }
    return l10n.rewardDiscount(_amountLabel(context));
  }

  String _amountLabel(BuildContext context) => reward.isPercentage
      ? '${_number(reward.amount)}%'
      : context.l10n.priceEgp(_number(reward.amount));

  static String _number(double value) =>
      value == value.roundToDouble() ? value.toStringAsFixed(0) : value.toStringAsFixed(2);

  String _subtitle(BuildContext context) {
    final l10n = context.l10n;
    final source = reward.fromWheel ? l10n.rewardFromWheel : l10n.rewardFromMaqam;
    final String detail;
    switch (status) {
      case RewardStatus.used:
        detail = reward.usedAt == null ? l10n.rewardsUsed : l10n.rewardUsedOn(dayLabel(context, reward.usedAt!));
      case RewardStatus.expired:
        detail = reward.expiresAt == null
            ? l10n.rewardsExpired
            : l10n.rewardExpiredOn(dayLabel(context, reward.expiresAt!));
      case RewardStatus.available:
        final expiresAt = reward.expiresAt;
        if (reward.kind == RewardKind.product) {
          detail = l10n.rewardShipsFree;
        } else if (expiresAt == null) {
          detail = l10n.rewardNoExpiry;
        } else {
          final days = expiresAt.difference(DateTime.now()).inHours / 24;
          detail = l10n.rewardExpiresIn(days.ceil().clamp(1, 9999));
        }
    }
    return '$source · $detail';
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final available = status == RewardStatus.available;

    return Card(
      margin: EdgeInsets.zero,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Row(
              children: [
                reward.kind == RewardKind.product
                    ? ProductThumbnail(url: reward.productImageUrl)
                    : _AmountTile(label: _amountLabel(context)),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _title(context),
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      Text(_subtitle(context), style: theme.textTheme.bodyMedium?.copyWith(color: muted)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: AppColors.border),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
            child: Row(
              children: [
                Expanded(
                  child: SelectableText(
                    reward.code,
                    textDirection: TextDirection.ltr,
                    // The code reads left to right but sits at the card's start edge.
                    textAlign: Directionality.of(context) == TextDirection.rtl ? TextAlign.right : TextAlign.left,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      letterSpacing: 1.5,
                      color: available ? null : muted,
                    ),
                  ),
                ),
                if (available)
                  FilledButton(
                    style: FilledButton.styleFrom(
                      minimumSize: const Size(0, 48),
                      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.md)),
                    ),
                    onPressed: applying ? null : onUse,
                    child: applying
                        ? const SizedBox.square(
                            dimension: 20,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Text(l10n.rewardUseInCart),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// The navy square with the discount on it.
class _AmountTile extends StatelessWidget {
  const _AmountTile({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(color: AppColors.navy, borderRadius: BorderRadius.circular(AppSpacing.md)),
      child: SizedBox.square(
        dimension: 56,
        child: Center(
          child: FittedBox(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.xs),
              child: Text(
                label,
                textDirection: TextDirection.ltr,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      color: AppColors.goldBright,
                      fontWeight: FontWeight.w700,
                    ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
