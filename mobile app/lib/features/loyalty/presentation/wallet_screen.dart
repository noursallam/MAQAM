import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/wallet.dart';
import '../loyalty_providers.dart';

/// Points balance and history (design: "10 المحفظة").
class WalletScreen extends ConsumerStatefulWidget {
  const WalletScreen({
    super.key,
    required this.onSignIn,
    required this.onScan,
    required this.onRewards,
    required this.onWheel,
  });

  final VoidCallback onSignIn;
  final VoidCallback onScan;
  final VoidCallback onRewards;
  final VoidCallback onWheel;

  @override
  ConsumerState<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends ConsumerState<WalletScreen> {
  TransactionFilter _filter = TransactionFilter.all;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    if (ref.watch(sessionProvider) == null) {
      return SafeArea(
        bottom: false,
        child: Column(
          children: [
            PageHeader(title: l10n.navWallet),
            Expanded(
              child: Center(
                child: MessageView(
                  icon: Icons.account_balance_wallet_outlined,
                  message: l10n.walletGuest,
                  actionLabel: l10n.signIn,
                  onAction: widget.onSignIn,
                ),
              ),
            ),
          ],
        ),
      );
    }

    final wallet = ref.watch(walletProvider);
    final transactions = ref.watch(transactionsProvider(_filter));

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        onRefresh: () {
          ref.invalidate(transactionsProvider(_filter));
          return ref.refresh(walletProvider.future);
        },
        child: ListView(
          padding: const EdgeInsets.only(bottom: AppSpacing.xl),
          children: [
            PageHeader(
              title: l10n.navWallet,
              actions: [
                TextButton(
                  style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                  onPressed: widget.onRewards,
                  child: Text(l10n.actionRewards),
                ),
              ],
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  AsyncView<Wallet?>(
                    value: wallet,
                    errorTitle: l10n.walletLoadFailed,
                    onRetry: () => ref.invalidate(walletProvider),
                    data: (wallet) =>
                        wallet == null ? const SizedBox.shrink() : _BalanceCard(wallet: wallet),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  Row(
                    children: [
                      Expanded(
                        child: _ActionButton(
                          icon: Icons.qr_code_scanner,
                          label: l10n.walletEarn,
                          onPressed: widget.onScan,
                        ),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: _ActionButton(
                          icon: Icons.casino_outlined,
                          label: l10n.actionWheel,
                          onPressed: widget.onWheel,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  Wrap(
                    alignment: WrapAlignment.spaceBetween,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    runSpacing: AppSpacing.sm,
                    children: [
                      Text(
                        l10n.walletRecent,
                        style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20),
                      ),
                      Wrap(
                        spacing: AppSpacing.sm,
                        children: [
                          for (final (filter, label) in [
                            (TransactionFilter.all, l10n.catalogAll),
                            (TransactionFilter.earned, l10n.filterEarned),
                            (TransactionFilter.spent, l10n.filterSpent),
                          ])
                            FilterPill(
                              label: label,
                              selected: _filter == filter,
                              onTap: () => setState(() => _filter = filter),
                            ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  AsyncView<List<PointsTransaction>>(
                    value: transactions,
                    onRetry: () => ref.invalidate(transactionsProvider(_filter)),
                    data: (items) => items.isEmpty
                        ? MessageView(message: l10n.walletNoTransactions)
                        : _TransactionList(items: items),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.wallet});

  final Wallet wallet;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final label = textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted);

    Widget figure(String title, int value, Color color) => Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: label),
              Text(
                '$value',
                style: TextStyle(color: color, fontSize: 28, fontWeight: FontWeight.w700, height: 1.3),
              ),
            ],
          ),
        );

    return NavyCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          IntrinsicHeight(
            child: Row(
              children: [
                figure(l10n.walletAvailable, wallet.balance, AppColors.goldBright),
                VerticalDivider(color: Colors.white.withValues(alpha: 0.15), width: AppSpacing.xl),
                figure(l10n.walletTotalEarned, wallet.totalEarned, Colors.white),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              Expanded(
                child: Text(
                  wallet.rankName ?? '',
                  style: textTheme.bodyMedium?.copyWith(color: AppColors.goldBright),
                ),
              ),
              if (wallet.nextRankName != null && wallet.nextRankMinPoints != null)
                Text(
                  l10n.walletNextRankAt(wallet.nextRankName!, wallet.nextRankMinPoints!),
                  style: label,
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          RankProgressBar(progress: wallet.progress),
          const SizedBox(height: AppSpacing.md),
          Text(l10n.walletRankNote, style: textTheme.bodySmall?.copyWith(color: Colors.white)),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({required this.icon, required this.label, required this.onPressed});

  final IconData icon;
  final String label;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return OutlinedButton.icon(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(AppSpacing.controlHeight),
        backgroundColor: AppColors.surface,
        foregroundColor: scheme.onSurface,
        iconColor: scheme.secondary,
        side: const BorderSide(color: AppColors.border),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.radius)),
        textStyle: AppTheme.buttonText(fontSize: 15, fontWeight: FontWeight.w600),
      ),
      onPressed: onPressed,
      icon: Icon(icon, size: 20),
      label: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis),
    );
  }
}

class _TransactionList extends StatelessWidget {
  const _TransactionList({required this.items});

  final List<PointsTransaction> items;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: EdgeInsets.zero,
      child: Column(
        children: [
          for (final (index, item) in items.indexed) ...[
            if (index > 0) const Divider(height: 1, color: AppColors.border),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(item.description, style: theme.textTheme.titleMedium),
                        if (item.date != null)
                          Text(
                            dayLabel(context, item.date!, withYear: true),
                            style: theme.textTheme.bodySmall
                                ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Text(
                    // The sign is part of the number, so keep it on the left in Arabic too.
                    '${item.points > 0 ? '+' : ''}${item.points}',
                    textDirection: TextDirection.ltr,
                    style: TextStyle(
                      color: item.points < 0 ? theme.colorScheme.error : AppColors.whatsapp,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
