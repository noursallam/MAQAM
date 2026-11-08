import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_art.dart';
import '../../../l10n/l10n.dart';
import '../domain/wallet.dart';
import '../loyalty_providers.dart';

class LeaderboardScreen extends ConsumerWidget {
  const LeaderboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final leaderboard = ref.watch(leaderboardProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: leaderboard.when(
        data: (entries) => entries.isEmpty
            ? Column(
                children: [
                  const _Header(top3: []),
                  Expanded(child: Center(child: Text(l10n.leaderboardEmpty))),
                ],
              )
            : _LeaderboardBody(entries: entries),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                e is ApiException ? apiErrorMessage(context, e) : l10n.errorGeneric,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Theme.of(context).colorScheme.error,
                    ),
              ),
              const SizedBox(height: AppSpacing.md),
              TextButton(
                onPressed: () => ref.invalidate(leaderboardProvider),
                child: Text(l10n.retry),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LeaderboardBody extends StatelessWidget {
  const _LeaderboardBody({required this.entries});

  final List<LeaderboardEntry> entries;

  @override
  Widget build(BuildContext context) {
    final top3 = entries.take(3).toList();
    final rest = entries.length > 3 ? entries.sublist(3) : const <LeaderboardEntry>[];

    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(child: _Header(top3: top3)),
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.lg, AppSpacing.md, AppSpacing.xl),
          sliver: SliverList.separated(
            itemCount: rest.length,
            separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
            itemBuilder: (context, i) => _EntryTile(entry: rest[i]),
          ),
        ),
      ],
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.top3});

  final List<LeaderboardEntry> top3;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;

    return ClipRRect(
      borderRadius: const BorderRadius.vertical(bottom: Radius.circular(32)),
      child: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: RadialGradient(
            center: Alignment(0, -0.1),
            radius: 0.9,
            colors: [AppColors.navyRaised, AppColors.navy, AppColors.navyDeep],
            stops: [0, 0.55, 1],
          ),
        ),
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: RadialGradient(
                    center: const Alignment(0, 0.15),
                    radius: 0.6,
                    colors: [
                      AppColors.gold.withValues(alpha: 0.35),
                      AppColors.gold.withValues(alpha: 0),
                    ],
                  ),
                ),
              ),
            ),
            SafeArea(
              bottom: false,
              child: Column(
                children: [
                  Row(
                    children: [
                      IconButton(
                        onPressed: () => Navigator.of(context).maybePop(),
                        tooltip: MaterialLocalizations.of(context).backButtonTooltip,
                        icon: const Icon(Icons.arrow_back, color: Colors.white),
                      ),
                      Expanded(
                        child: Text(
                          context.l10n.leaderboardTitle,
                          textAlign: TextAlign.center,
                          style: textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
                        ),
                      ),
                      const SizedBox(width: 48),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  if (top3.isNotEmpty) _Podium(top3: top3),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Podium extends StatelessWidget {
  const _Podium({required this.top3});

  final List<LeaderboardEntry> top3;

  @override
  Widget build(BuildContext context) {
    // Second, first, third: the winner stands in the middle in either reading direction.
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(child: top3.length > 1 ? _PodiumPlace(entry: top3[1], place: 2) : const SizedBox()),
          Expanded(child: _PodiumPlace(entry: top3[0], place: 1)),
          Expanded(child: top3.length > 2 ? _PodiumPlace(entry: top3[2], place: 3) : const SizedBox()),
        ],
      ),
    );
  }
}

class _Medal {
  const _Medal(this.light, this.dark);

  final Color light;
  final Color dark;

  static const gold = _Medal(AppColors.medalGoldLight, AppColors.medalGoldDark);
  static const silver = _Medal(AppColors.medalSilverLight, AppColors.medalSilverDark);
  static const bronze = _Medal(AppColors.medalBronzeLight, AppColors.medalBronzeDark);

  static _Medal forPlace(int place) => switch (place) {
        1 => gold,
        2 => silver,
        _ => bronze,
      };

  LinearGradient get gradient => LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [light, dark],
      );
}

class _PodiumPlace extends StatelessWidget {
  const _PodiumPlace({required this.entry, required this.place});

  final LeaderboardEntry entry;
  final int place;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    final medal = _Medal.forPlace(place);
    final isFirst = place == 1;
    final avatarSize = isFirst ? 84.0 : 64.0;
    final pedestalHeight = switch (place) { 1 => 96.0, 2 => 72.0, _ => 56.0 };

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (isFirst)
          Icon(Icons.workspace_premium, color: medal.light, size: 32)
        else
          const SizedBox(height: 32),
        const SizedBox(height: AppSpacing.xs),
        _Avatar(name: entry.name, size: avatarSize, medal: medal),
        const SizedBox(height: AppSpacing.sm),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xs),
          child: Text(
            entry.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: textTheme.bodyMedium?.copyWith(color: Colors.white, fontWeight: FontWeight.w600),
          ),
        ),
        const SizedBox(height: 2),
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _grouped(entry.points),
              style: textTheme.bodySmall?.copyWith(color: medal.light, fontWeight: FontWeight.w700),
            ),
            const SizedBox(width: AppSpacing.xs),
            const ArtIcon(AppArt.coin, size: 14),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        Container(
          height: pedestalHeight,
          margin: const EdgeInsets.symmetric(horizontal: AppSpacing.xs),
          decoration: BoxDecoration(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(14)),
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [medal.light.withValues(alpha: 0.35), medal.dark.withValues(alpha: 0.08)],
            ),
            border: Border(top: BorderSide(color: medal.light.withValues(alpha: 0.8), width: 2)),
          ),
          alignment: const Alignment(0, -0.4),
          child: Text(
            '$place',
            style: TextStyle(
              color: medal.light,
              fontSize: isFirst ? 40 : 30,
              fontWeight: FontWeight.w700,
              height: 1,
            ),
          ),
        ),
      ],
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.name, required this.size, required this.medal});

  final String name;
  final double size;
  final _Medal medal;

  @override
  Widget build(BuildContext context) {
    final initial = name.isNotEmpty ? name.characters.first.toUpperCase() : '?';

    return Container(
      width: size,
      height: size,
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: medal.gradient,
        boxShadow: [BoxShadow(color: medal.light.withValues(alpha: 0.45), blurRadius: 18)],
      ),
      child: DecoratedBox(
        decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.navyRaised),
        child: Center(
          child: Text(
            initial,
            style: TextStyle(color: Colors.white, fontSize: size * 0.38, fontWeight: FontWeight.w700),
          ),
        ),
      ),
    );
  }
}

class _EntryTile extends StatelessWidget {
  const _EntryTile({required this.entry});

  final LeaderboardEntry entry;

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    final initial = entry.name.isNotEmpty ? entry.name.characters.first.toUpperCase() : '?';

    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
        child: Row(
          children: [
            SizedBox(
              width: 28,
              child: Text(
                '${entry.rank}',
                textAlign: TextAlign.center,
                style: textTheme.titleSmall?.copyWith(color: AppColors.textSecondary, fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            CircleAvatar(
              radius: 20,
              backgroundColor: AppColors.goldSoft,
              child: Text(
                initial,
                style: textTheme.titleSmall?.copyWith(color: AppColors.goldDeep, fontWeight: FontWeight.w700),
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Text(
                entry.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: textTheme.bodyLarge?.copyWith(color: AppColors.navy, fontWeight: FontWeight.w500),
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            DecoratedBox(
              decoration: const ShapeDecoration(shape: StadiumBorder(), color: AppColors.goldSoft),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      _grouped(entry.points),
                      style: textTheme.titleSmall?.copyWith(color: AppColors.navy, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(width: AppSpacing.xs),
                    const ArtIcon(AppArt.coin, size: 18),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

String _grouped(int value) => value.toString().replaceAllMapped(
      RegExp(r'\B(?=(\d{3})+(?!\d))'),
      (match) => ',',
    );
