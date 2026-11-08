import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_art.dart';
import '../../../core/widgets/maqam_logo.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../catalog/catalog_providers.dart';
import '../../catalog/domain/promo_banner.dart';
import '../../catalog/presentation/widgets/product_card.dart';
import '../../loyalty/domain/wallet.dart';
import '../../loyalty/data/api_loyalty_repository.dart';
import '../../loyalty/loyalty_providers.dart';
import '../../notifications/data/api_notifications_repository.dart';
import '../../loyalty/presentation/rank_up_screen.dart';
import '../../seminars/presentation/seminar_widgets.dart';
import '../../seminars/seminars_providers.dart';

/// The first tab (design: "07 الرئيسية"). It composes other features: the
/// points summary from loyalty, banners and new arrivals from the catalog.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({
    super.key,
    required this.onSignIn,
    required this.onOpenStore,
    required this.onOpenProduct,
    required this.onScan,
    required this.onOrders,
    required this.onNotifications,
    required this.onChat,
    required this.onWheel,
    required this.onRewards,
    required this.onLeaderboard,
    required this.onOpenSeminar,
  });

  final VoidCallback onSignIn;
  final VoidCallback onOpenStore;
  final ValueChanged<int> onOpenProduct;
  final VoidCallback onScan;
  final VoidCallback onOrders;
  final VoidCallback onNotifications;
  final VoidCallback onChat;
  final VoidCallback onWheel;
  final VoidCallback onRewards;
  final VoidCallback onLeaderboard;
  final ValueChanged<int> onOpenSeminar;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final session = ref.watch(sessionProvider);
    final banners = ref.watch(bannersProvider).value ?? const <PromoBanner>[];
    final latest = ref.watch(latestProductsProvider);
    final seminar = ref.watch(currentSeminarProvider).value;

    ref.listen<AsyncValue<Wallet?>>(walletProvider, (prev, next) {
      final rankUp = next.value?.pendingRankUp;
      if (rankUp == null) return;
      if (prev?.value?.pendingRankUp != null) return;
      Navigator.of(context).push(MaterialPageRoute<void>(
        fullscreenDialog: true,
        builder: (_) => RankUpScreen(
          rankUp: rankUp,
          onDismiss: () {
            Navigator.of(context).pop();
            ref.read(loyaltyRepositoryProvider).acknowledgeRankUp();
            ref.invalidate(walletProvider);
          },
        ),
      ));
    });

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        onRefresh: () {
          ref.invalidate(bannersProvider);
          ref.invalidate(walletProvider);
          ref.invalidate(currentSeminarProvider);
          return ref.refresh(latestProductsProvider.future);
        },
        child: ListView(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xl),
          children: [
            Row(
              children: [
                const MaqamLogo(height: 16),
                const Spacer(),
                IconButton(
                  tooltip: l10n.chat,
                  onPressed: onChat,
                  icon: const Icon(Icons.chat_bubble_outline),
                ),
                IconButton(
                  tooltip: l10n.notifications,
                  onPressed: onNotifications,
                  icon: Badge(
                    isLabelVisible: ref.watch(unreadNotificationsProvider) > 0,
                    backgroundColor: theme.colorScheme.error,
                    smallSize: 8,
                    child: const Icon(Icons.notifications_none),
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            if (session == null)
              _GuestCard(onSignIn: onSignIn)
            else
              _PointsCard(
                firstName: _firstName(session.fullName),
                wallet: ref.watch(walletProvider).value,
              ),
            if (seminar != null) ...[
              const SizedBox(height: AppSpacing.lg),
              SeminarPromoCard(seminar: seminar, onOpen: () => onOpenSeminar(seminar.id)),
            ],
            const SizedBox(height: AppSpacing.xl),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _QuickAction(icon: Icons.qr_code_scanner, label: l10n.actionScan, onTap: onScan, color: AppColors.navy),
                _QuickAction(icon: Icons.casino_outlined, label: l10n.actionWheel, onTap: onWheel, color: const Color(0xFF7B61FF)),
                _QuickAction(icon: Icons.card_giftcard, label: l10n.actionRewards, onTap: onRewards, color: const Color(0xFFC5A059)),
                _QuickAction(icon: Icons.inventory_2_outlined, label: l10n.actionOrders, onTap: onOrders, color: const Color(0xFF2196F3)),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _QuickAction(icon: Icons.leaderboard_outlined, label: l10n.actionLeaderboard, onTap: onLeaderboard, color: const Color(0xFF4CAF50)),
                const SizedBox(width: 56),
                const SizedBox(width: 56),
                const SizedBox(width: 56),
              ],
            ),
            if (banners.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.lg),
              _BannerCarousel(banners: banners),
            ],
            const SizedBox(height: AppSpacing.xl),
            Row(
              children: [
                Expanded(
                  child: Text(
                    l10n.homeNewArrivals,
                    style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20),
                  ),
                ),
                TextButton(
                  style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                  onPressed: onOpenStore,
                  child: Text(l10n.viewAll),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            switch (latest) {
              AsyncData(:final value) when value.isNotEmpty => GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: productGridDelegate,
                  itemCount: value.length,
                  itemBuilder: (context, i) => ProductCard(
                    product: value[i],
                    showCategory: false,
                    onTap: () => onOpenProduct(value[i].id),
                  ),
                ),
              AsyncData() => _SectionNote(text: l10n.catalogEmpty),
              AsyncError() => _SectionNote(
                  text: l10n.catalogLoadFailed,
                  actionLabel: l10n.retry,
                  onAction: () => ref.invalidate(latestProductsProvider),
                ),
              _ => const Padding(
                  padding: EdgeInsets.all(AppSpacing.xl),
                  child: Center(child: CircularProgressIndicator()),
                ),
            },
          ],
        ),
      ),
    );
  }
}

/// `3821` -> `3,821`, as balances are usually written.
String _grouped(int value) => value.toString().replaceAllMapped(
      RegExp(r'\B(?=(\d{3})+(?!\d))'),
      (match) => ',',
    );

String? _firstName(String? fullName) {
  final name = fullName?.trim() ?? '';
  return name.isEmpty ? null : name.split(RegExp(r'\s+')).first;
}

class _PointsCard extends StatelessWidget {
  const _PointsCard({required this.firstName, required this.wallet});

  final String? firstName;

  /// Null while loading, or if the wallet could not be loaded.
  final Wallet? wallet;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final wallet = this.wallet;
    final hasBadge = wallet?.rankIconUrl != null;

    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        color: AppColors.navy,
        boxShadow: [
          BoxShadow(
            color: AppColors.navy.withValues(alpha: 0.25),
            blurRadius: 24,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final half = constraints.maxWidth / 2;
            return Stack(
              children: [
                if (hasBadge) ...[
                  PositionedDirectional(
                    top: -60,
                    bottom: -60,
                    end: -half * 0.3,
                    width: half * 1.5,
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: RadialGradient(
                          colors: [
                            AppColors.goldBright.withValues(alpha: 0.85),
                            AppColors.gold.withValues(alpha: 0.35),
                            AppColors.gold.withValues(alpha: 0),
                          ],
                          stops: const [0, 0.5, 0.95],
                        ),
                      ),
                    ),
                  ),
                  // Oversized and pushed past the card's edge so it reads as a cover, not an icon.
                  PositionedDirectional(
                    top: -12,
                    bottom: -12,
                    end: -half * 0.3,
                    width: half * 1.1,
                    child: ShaderMask(
                      blendMode: BlendMode.dstIn,
                      shaderCallback: (rect) => LinearGradient(
                        begin: AlignmentDirectional.centerEnd,
                        end: AlignmentDirectional.centerStart,
                        stops: const [0.55, 0.95],
                        colors: const [Colors.white, Colors.transparent],
                      ).createShader(rect, textDirection: Directionality.of(context)),
                      child: _RankBadge(url: wallet!.rankIconUrl!, label: wallet.rankName),
                    ),
                  ),
                ],
                Padding(
                  padding: EdgeInsetsDirectional.only(
                    start: AppSpacing.lg,
                    top: AppSpacing.lg,
                    bottom: AppSpacing.lg,
                    end: hasBadge ? half * 0.7 : AppSpacing.lg,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        firstName == null ? l10n.homeGreetingNoName : l10n.homeGreeting(firstName!),
                        style: textTheme.bodyMedium?.copyWith(color: Colors.white.withValues(alpha: 0.7)),
                      ),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        l10n.pointsUnit,
                        style: textTheme.bodySmall?.copyWith(color: AppColors.goldBright),
                      ),
                      Text(
                        wallet == null ? '—' : _grouped(wallet.balance),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 40,
                          fontWeight: FontWeight.w700,
                          height: 1.1,
                        ),
                      ),
                      const SizedBox(height: AppSpacing.md),
                      RankProgressBar(progress: wallet?.progress ?? 0),
                      const SizedBox(height: AppSpacing.sm),
                      if (wallet != null)
                        Text(
                          wallet.nextRankName == null
                              ? l10n.homeTopRank
                              : l10n.homeToNextRank(wallet.pointsToNextRank, wallet.nextRankName!),
                          style: textTheme.bodySmall?.copyWith(color: Colors.white.withValues(alpha: 0.7)),
                        ),
                    ],
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _RankBadge extends StatelessWidget {
  const _RankBadge({required this.url, this.label});

  final String url;
  final String? label;

  static const _fallback = Center(child: ArtIcon(AppArt.rankShield, size: 72));

  @override
  Widget build(BuildContext context) {
    return Image.network(
      url,
      fit: BoxFit.contain,
      semanticLabel: label,
      loadingBuilder: (_, child, progress) => progress == null ? child : _fallback,
      errorBuilder: (_, Object e, StackTrace? s) => _fallback,
    );
  }
}

class _GuestCard extends StatelessWidget {
  const _GuestCard({required this.onSignIn});

  final VoidCallback onSignIn;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return NavyCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(l10n.homeGuestTitle, style: textTheme.titleMedium?.copyWith(color: Colors.white)),
          const SizedBox(height: AppSpacing.xs),
          Text(
            l10n.homeGuestBody,
            style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted),
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: AppColors.navy,
              minimumSize: const Size.fromHeight(48),
            ),
            onPressed: onSignIn,
            child: Text(l10n.signIn),
          ),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.color,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: color, size: 26),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w500, fontSize: 11),
          ),
        ],
      ),
    );
  }
}

class _BannerCarousel extends StatelessWidget {
  const _BannerCarousel({required this.banners});

  final List<PromoBanner> banners;

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 2.7,
      child: PageView.builder(
        itemCount: banners.length,
        itemBuilder: (context, i) => _BannerCard(banner: banners[i]),
      ),
    );
  }
}

class _BannerCard extends StatelessWidget {
  const _BannerCard({required this.banner});

  final PromoBanner banner;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: Stack(
        fit: StackFit.expand,
        children: [
          const ColoredBox(color: AppColors.navy),
          if (banner.imageUrl != null)
            Image.network(
              banner.imageUrl!,
              fit: BoxFit.cover,
              excludeFromSemantics: true,
              errorBuilder: (_, _, _) => const SizedBox.shrink(),
            ),
          if (banner.title.isNotEmpty) ...[
            // Keeps the title readable over any image.
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Color(0x00161B29), Color(0xB3161B29)],
                ),
              ),
            ),
            PositionedDirectional(
              start: AppSpacing.lg,
              end: AppSpacing.lg,
              bottom: AppSpacing.lg,
              child: Text(
                banner.title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 18),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _SectionNote extends StatelessWidget {
  const _SectionNote({required this.text, this.actionLabel, this.onAction});

  final String text;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.xl),
      child: Column(
        children: [
          Text(
            text,
            style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
          if (actionLabel != null) TextButton(onPressed: onAction, child: Text(actionLabel!)),
        ],
      ),
    );
  }
}
