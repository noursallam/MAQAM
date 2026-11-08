import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/seminar.dart';
import '../seminars_providers.dart';
import 'seminar_widgets.dart';

/// A seminar's details, signing up for its draw, and the winners as they are drawn.
class SeminarScreen extends ConsumerStatefulWidget {
  const SeminarScreen({super.key, required this.seminarId, required this.onSignIn});

  final int seminarId;
  final VoidCallback onSignIn;

  @override
  ConsumerState<SeminarScreen> createState() => _SeminarScreenState();
}

class _SeminarScreenState extends ConsumerState<SeminarScreen> {
  /// How often the page asks for new winners while the seminar runs.
  static const _refreshEvery = Duration(seconds: 20);

  Timer? _refresh;
  bool _registering = false;

  @override
  void initState() {
    super.initState();
    // Winners are drawn live at the event; keep the list current without pulling.
    _refresh = Timer.periodic(_refreshEvery, (_) {
      final seminar = ref.read(seminarProvider(widget.seminarId)).value;
      if (seminar != null && seminar.isLive) {
        ref.read(seminarProvider(widget.seminarId).notifier).refresh();
      }
    });
  }

  @override
  void dispose() {
    _refresh?.cancel();
    super.dispose();
  }

  Future<void> _register() async {
    if (ref.read(sessionProvider) == null) {
      widget.onSignIn();
      return;
    }
    setState(() => _registering = true);
    try {
      await ref.read(seminarProvider(widget.seminarId).notifier).register();
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(context.l10n.seminarRegisteredDone)));
      }
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _registering = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final seminar = ref.watch(seminarProvider(widget.seminarId));

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.seminarTitle),
      body: SafeArea(
        top: false,
        child: AsyncView<Seminar>(
          value: seminar,
          errorTitle: l10n.seminarLoadFailed,
          onRetry: () => ref.invalidate(seminarProvider(widget.seminarId)),
          data: (seminar) => RefreshIndicator(
            onRefresh: () => ref.read(seminarProvider(widget.seminarId).notifier).refresh(),
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                _Header(seminar: seminar),
                const SizedBox(height: AppSpacing.lg),
                if (seminar.myWin != null) ...[
                  _WinCard(draw: seminar.myWin!),
                  const SizedBox(height: AppSpacing.lg),
                ],
                _SignUpCard(seminar: seminar, busy: _registering, onRegister: _register),
                const SizedBox(height: AppSpacing.xl),
                Text(
                  l10n.seminarPrizes,
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontSize: 18),
                ),
                const SizedBox(height: AppSpacing.sm),
                _DrawsCard(draws: seminar.draws),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.seminar});

  final Seminar seminar;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    Widget fact(IconData icon, String text) => Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xs),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, size: 18, color: theme.colorScheme.secondary),
              const SizedBox(width: AppSpacing.sm),
              Expanded(child: Text(text, style: theme.textTheme.bodyMedium?.copyWith(color: muted))),
            ],
          ),
        );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (seminar.imageUrl != null) ...[
          ClipRRect(
            borderRadius: BorderRadius.circular(AppSpacing.radius),
            child: SeminarImage(url: seminar.imageUrl!),
          ),
          const SizedBox(height: AppSpacing.lg),
        ],
        Align(
          alignment: AlignmentDirectional.centerStart,
          child: seminar.isLive
              ? const LivePill()
              : StatusPill(
                  label: l10n.seminarEnded,
                  background: AppColors.surfaceMuted,
                  foreground: AppColors.textSecondary,
                ),
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(seminar.title, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22)),
        fact(Icons.schedule, seminarTimeLabel(context, seminar)),
        if (seminar.location != null) fact(Icons.place_outlined, seminar.location!),
        if (seminar.description != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(seminar.description!, style: theme.textTheme.bodyLarge),
        ],
      ],
    );
  }
}

class _SignUpCard extends StatelessWidget {
  const _SignUpCard({required this.seminar, required this.busy, required this.onRegister});

  final Seminar seminar;
  final bool busy;
  final VoidCallback onRegister;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    final Widget action;
    if (seminar.isRegistered) {
      action = Row(
        children: [
          const Icon(Icons.check_circle, color: AppColors.success),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(
              seminar.isLive ? l10n.seminarRegisteredLive : l10n.seminarRegisteredEnded,
              style: textTheme.bodyLarge?.copyWith(color: Colors.white),
            ),
          ),
        ],
      );
    } else if (seminar.isLive) {
      action = FilledButton(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.goldBright,
          foregroundColor: AppColors.navy,
        ),
        onPressed: busy ? null : onRegister,
        child: busy
            ? const SizedBox.square(
                dimension: 22,
                child: CircularProgressIndicator(strokeWidth: 2.5, color: AppColors.navy),
              )
            : Text(l10n.seminarJoin),
      );
    } else {
      action = Text(l10n.seminarClosed, style: textTheme.bodyLarge?.copyWith(color: AppColors.onNavyMuted));
    }

    return NavyCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.seminarRegisteredCount(seminar.registrations),
            style: textTheme.titleMedium?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(l10n.seminarHowItWorks, style: textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted)),
          const SizedBox(height: AppSpacing.lg),
          action,
        ],
      ),
    );
  }
}

class _WinCard extends StatelessWidget {
  const _WinCard({required this.draw});

  final SeminarDraw draw;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Semantics(
      liveRegion: true,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppColors.goldSoft,
          borderRadius: BorderRadius.circular(AppSpacing.radius),
          border: Border.all(color: AppColors.gold),
        ),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              const Icon(Icons.emoji_events, size: 36, color: AppColors.goldDeep),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.seminarYouWon(draw.prize),
                      style: textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    Text(l10n.seminarCollect, style: textTheme.bodyMedium),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DrawsCard extends StatelessWidget {
  const _DrawsCard({required this.draws});

  final List<SeminarDraw> draws;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          for (final (index, draw) in draws.indexed) ...[
            if (index > 0) const Divider(height: 1, color: AppColors.border),
            ColoredBox(
              color: draw.isMine ? AppColors.goldSoft : AppColors.surface,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 16,
                      backgroundColor: draw.isDrawn ? AppColors.navy : AppColors.surfaceMuted,
                      child: Text(
                        '${draw.position}',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: draw.isDrawn ? AppColors.goldBright : muted,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.md),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(draw.prize, style: theme.textTheme.titleMedium),
                          Text(
                            draw.isMine
                                ? l10n.seminarWinnerYou
                                : draw.isDrawn
                                    ? l10n.seminarWinner(draw.winnerName ?? '—')
                                    : l10n.seminarNotDrawn,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: draw.isMine ? AppColors.goldDeep : muted,
                              fontWeight: draw.isMine ? FontWeight.w700 : null,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (draw.isDrawn)
                      Icon(
                        Icons.emoji_events_outlined,
                        size: 20,
                        color: draw.isMine ? AppColors.goldDeep : AppColors.gold,
                      ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
