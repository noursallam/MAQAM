import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_spacing.dart';
import '../l10n/l10n.dart';

/// The four tabs and the bottom bar they share (design: "شريط التنقل").
class MainShell extends StatelessWidget {
  const MainShell({super.key, required this.navigationShell, required this.onScan});

  final StatefulNavigationShell navigationShell;
  final VoidCallback onScan;

  void _goTo(int index) {
    // Tapping the current tab again returns it to its first screen.
    navigationShell.goBranch(index, initialLocation: index == navigationShell.currentIndex);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final current = navigationShell.currentIndex;

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: DecoratedBox(
        decoration: const BoxDecoration(
          color: AppColors.surface,
          border: Border(top: BorderSide(color: AppColors.border)),
        ),
        child: SafeArea(
          top: false,
          child: SizedBox(
            height: 68,
            child: Row(
              children: [
                _NavItem(
                  icon: Icons.home_outlined,
                  label: l10n.navHome,
                  selected: current == 0,
                  onTap: () => _goTo(0),
                ),
                _NavItem(
                  icon: Icons.shopping_bag_outlined,
                  label: l10n.navStore,
                  selected: current == 1,
                  onTap: () => _goTo(1),
                ),
                Expanded(
                  child: Center(
                    child: IconButton.filled(
                      tooltip: l10n.navScan,
                      style: IconButton.styleFrom(
                        fixedSize: const Size.square(52),
                        foregroundColor: AppColors.goldBright,
                      ),
                      onPressed: onScan,
                      icon: const Icon(Icons.qr_code_scanner),
                    ),
                  ),
                ),
                _NavItem(
                  icon: Icons.account_balance_wallet_outlined,
                  label: l10n.navWallet,
                  selected: current == 2,
                  onTap: () => _goTo(2),
                ),
                _NavItem(
                  icon: Icons.person_outline,
                  label: l10n.navAccount,
                  selected: current == 3,
                  onTap: () => _goTo(3),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final color = selected ? scheme.onSurface : scheme.onSurfaceVariant;

    return Expanded(
      child: Semantics(
        selected: selected,
        button: true,
        child: InkResponse(
          onTap: onTap,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: color),
              const SizedBox(height: AppSpacing.xs),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: color,
                  fontSize: 12,
                  fontWeight: selected ? FontWeight.w700 : FontWeight.w400,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
