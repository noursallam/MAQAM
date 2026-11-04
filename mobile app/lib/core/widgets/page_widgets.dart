import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';

/// App version shown in the account screen. Keep in step with `pubspec.yaml`.
const appVersion = '1.0.0';

/// The top bar of a screen opened from a tab: back arrow and a start-aligned title.
class SubPageAppBar extends StatelessWidget implements PreferredSizeWidget {
  const SubPageAppBar({super.key, required this.title, this.subtitle, this.actions = const []});

  final String title;
  final String? subtitle;
  final List<Widget> actions;

  @override
  Size get preferredSize => Size.fromHeight(subtitle == null ? kToolbarHeight : 72);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final heading = Text(title, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20));

    return AppBar(
      centerTitle: false,
      toolbarHeight: preferredSize.height,
      title: subtitle == null
          ? heading
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                heading,
                Text(
                  subtitle!,
                  style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                ),
              ],
            ),
      actions: [...actions, const SizedBox(width: AppSpacing.sm)],
    );
  }
}

/// A row of tabs inside a muted track, the selected one on a white pill.
class SegmentedTabs<T> extends StatelessWidget {
  const SegmentedTabs({
    super.key,
    required this.tabs,
    required this.selected,
    required this.onSelected,
  });

  final List<(T, String)> tabs;
  final T selected;
  final ValueChanged<T> onSelected;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(AppSpacing.radius),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xs),
        child: Row(
          children: [
            for (final (value, label) in tabs)
              Expanded(
                child: Semantics(
                  selected: value == selected,
                  button: true,
                  child: Material(
                    color: value == selected ? AppColors.surface : Colors.transparent,
                    borderRadius: BorderRadius.circular(AppSpacing.md),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(AppSpacing.md),
                      onTap: () => onSelected(value),
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(minHeight: 48),
                        child: Center(
                          child: Text(
                            label,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: value == selected ? FontWeight.w700 : FontWeight.w500,
                              color: value == selected
                                  ? theme.colorScheme.onSurface
                                  : theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// A muted box with an info icon, for a short rule the customer should know.
class InfoNote extends StatelessWidget {
  const InfoNote({super.key, required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(AppSpacing.radius),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Icon(Icons.info_outline, size: 20, color: AppColors.goldDeep),
            const SizedBox(width: AppSpacing.sm),
            Expanded(child: Text(text, style: theme.textTheme.bodyMedium)),
          ],
        ),
      ),
    );
  }
}

/// A small rounded label, such as "Pending" or "Default".
class StatusPill extends StatelessWidget {
  const StatusPill({
    super.key,
    required this.label,
    this.background = AppColors.goldSoft,
    this.foreground = AppColors.goldDeep,
  });

  final String label;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(AppSpacing.md)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 2),
        child: Text(
          label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: foreground,
                fontWeight: FontWeight.w600,
              ),
        ),
      ),
    );
  }
}

/// The large title row at the top of a tab, with optional actions at the end.
class PageHeader extends StatelessWidget {
  const PageHeader({super.key, required this.title, this.actions = const []});

  final String title;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(
        start: AppSpacing.lg,
        end: AppSpacing.sm,
        top: AppSpacing.md,
        bottom: AppSpacing.sm,
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontSize: 24),
            ),
          ),
          ...actions,
          if (actions.isEmpty) const SizedBox(height: 48),
        ],
      ),
    );
  }
}

/// Pill-shaped filter chip: navy when selected, outlined otherwise.
class FilterPill extends StatelessWidget {
  const FilterPill({super.key, required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
      shape: const StadiumBorder(),
      side: BorderSide(color: selected ? scheme.primary : scheme.outlineVariant),
      backgroundColor: AppColors.surface,
      selectedColor: scheme.primary,
      labelStyle: TextStyle(
        color: selected ? scheme.onPrimary : scheme.onSurface,
        fontWeight: FontWeight.w500,
      ),
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.sm),
    );
  }
}

/// The navy card used for points summaries.
class NavyCard extends StatelessWidget {
  const NavyCard({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.navy,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Padding(padding: const EdgeInsets.all(AppSpacing.lg), child: child),
    );
  }
}

/// Gold progress bar toward the next rank.
class RankProgressBar extends StatelessWidget {
  const RankProgressBar({super.key, required this.progress, this.onLight = false});

  /// From 0 to 1.
  final double progress;

  /// True when drawn on a light surface instead of the default navy card.
  final bool onLight;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(4),
      child: LinearProgressIndicator(
        value: progress,
        minHeight: 4,
        color: onLight ? AppColors.gold : AppColors.goldBright,
        backgroundColor: onLight
            ? AppColors.navy.withValues(alpha: 0.08)
            : Colors.white.withValues(alpha: 0.15),
      ),
    );
  }
}
