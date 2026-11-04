import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/seminar.dart';

/// `18:05`, with Western digits in both languages.
String clockLabel(DateTime time) =>
    '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';

/// "Today · 18:00 – 21:00", or both days when it runs past midnight.
String seminarTimeLabel(BuildContext context, Seminar seminar) {
  final start = seminar.startsAt;
  final end = seminar.endsAt;
  final sameDay = start.year == end.year && start.month == end.month && start.day == end.day;
  return sameDay
      ? '${dayLabel(context, start)} · ${clockLabel(start)} – ${clockLabel(end)}'
      : '${dayLabel(context, start)} ${clockLabel(start)} – ${dayLabel(context, end)} ${clockLabel(end)}';
}

/// The seminar's promotion on the home page, shown only while it runs.
class SeminarPromoCard extends StatelessWidget {
  const SeminarPromoCard({super.key, required this.seminar, required this.onOpen});

  final Seminar seminar;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final left = seminar.endsAt.difference(DateTime.now());

    return Semantics(
      button: true,
      child: Material(
        color: AppColors.navy,
        borderRadius: BorderRadius.circular(20),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onOpen,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (seminar.imageUrl != null) SeminarImage(url: seminar.imageUrl!),
              Padding(
                padding: const EdgeInsets.all(AppSpacing.lg),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const LivePill(),
                        const SizedBox(width: AppSpacing.sm),
                        Expanded(
                          child: Text(
                            l10n.seminarEndsIn(_duration(context, left)),
                            style: textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      seminar.title,
                      style: textTheme.titleMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      l10n.seminarPromoBody(seminar.draws.length),
                      style: textTheme.bodyMedium?.copyWith(color: AppColors.goldBright),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            l10n.seminarRegisteredCount(seminar.registrations),
                            style: textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted),
                          ),
                        ),
                        if (seminar.isRegistered)
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.check_circle, size: 18, color: AppColors.success),
                              const SizedBox(width: AppSpacing.xs),
                              Text(
                                l10n.seminarYouAreIn,
                                style: textTheme.bodyMedium?.copyWith(color: AppColors.success),
                              ),
                            ],
                          )
                        else
                          DecoratedBox(
                            decoration: BoxDecoration(
                              color: AppColors.goldBright,
                              borderRadius: BorderRadius.circular(AppSpacing.md),
                            ),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
                              child: Text(
                                l10n.seminarJoin,
                                style: textTheme.bodyMedium?.copyWith(
                                  color: AppColors.navy,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  static String _duration(BuildContext context, Duration left) {
    final l10n = context.l10n;
    if (left.inHours >= 24) return l10n.seminarDays(left.inDays);
    if (left.inMinutes >= 60) return l10n.seminarHours(left.inHours);
    return l10n.seminarMinutes(left.inMinutes.clamp(1, 59));
  }
}

/// The seminar's picture at 16:9, with a quiet placeholder while it loads.
class SeminarImage extends StatelessWidget {
  const SeminarImage({super.key, required this.url});

  final String url;

  @override
  Widget build(BuildContext context) {
    const placeholder = ColoredBox(color: AppColors.navyRaised);

    return AspectRatio(
      aspectRatio: 16 / 9,
      child: Image.network(
        url,
        fit: BoxFit.cover,
        excludeFromSemantics: true,
        loadingBuilder: (context, child, progress) => progress == null ? child : placeholder,
        errorBuilder: (_, _, _) => placeholder,
      ),
    );
  }
}

/// A small green "Live now" label.
class LivePill extends StatelessWidget {
  const LivePill({super.key});

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.success.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(AppSpacing.md),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.circle, size: 8, color: AppColors.success),
            const SizedBox(width: AppSpacing.xs),
            Text(
              context.l10n.seminarLive,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: AppColors.success,
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
