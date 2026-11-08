import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../domain/wallet.dart';

class RankUpScreen extends StatelessWidget {
  const RankUpScreen({super.key, required this.rankUp, required this.onDismiss});

  final PendingRankUp rankUp;
  final VoidCallback onDismiss;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (rankUp.rankIconUrl != null)
                  Image.network(
                    rankUp.rankIconUrl!,
                    width: 200,
                    height: 200,
                    fit: BoxFit.contain,
                    loadingBuilder: (_, child, progress) =>
                        progress == null ? child : const SizedBox(width: 200, height: 200),
                    errorBuilder: (_, Object e, StackTrace? s) =>
                        const SizedBox(width: 200, height: 200),
                  )
                else
                  const SizedBox(height: 200),
                const SizedBox(height: AppSpacing.xl),
                Text(
                  l10n.rankUpCongrats,
                  style: textTheme.headlineLarge?.copyWith(
                    color: AppColors.navy,
                    fontWeight: FontWeight.w700,
                    fontSize: 32,
                  ),
                ),
                const SizedBox(height: AppSpacing.md),
                Text(
                  l10n.rankUpMessage(rankUp.rankName),
                  textAlign: TextAlign.center,
                  style: textTheme.bodyLarge?.copyWith(
                    color: AppColors.navy.withValues(alpha: 0.7),
                    fontSize: 18,
                  ),
                ),
                const SizedBox(height: AppSpacing.xl * 2),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.navy,
                      side: const BorderSide(color: AppColors.navy),
                      minimumSize: const Size.fromHeight(52),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radius),
                      ),
                    ),
                    onPressed: onDismiss,
                    child: Text(l10n.rankUpDismiss),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
