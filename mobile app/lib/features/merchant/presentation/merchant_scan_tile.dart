import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/merchant.dart';

/// One customer's scan with the merchant's code: who, which pack, the points and when.
class MerchantScanTile extends StatelessWidget {
  const MerchantScanTile({super.key, required this.scan});

  final MerchantScan scan;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final name = scan.customerName?.trim() ?? '';
    final details = [
      ?scan.prize,
      if (scan.scannedAt != null) timeAgoLabel(context, scan.scannedAt!),
    ].join(' · ');

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
      child: Row(
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: AppColors.surfaceMuted,
            foregroundColor: theme.colorScheme.primary,
            child: name.isEmpty ? const Icon(Icons.person_outline, size: 20) : Text(name.characters.first),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name.isEmpty ? l10n.merchantUnknownCustomer : name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.titleMedium,
                ),
                if (scan.customerPhone != null)
                  Text(
                    scan.customerPhone!,
                    textDirection: TextDirection.ltr,
                    style: theme.textTheme.bodySmall?.copyWith(color: muted),
                  ),
                if (details.isNotEmpty)
                  Text(details, style: theme.textTheme.bodySmall?.copyWith(color: muted)),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Text(
            l10n.merchantScanPoints(scan.points),
            style: theme.textTheme.titleMedium?.copyWith(color: AppColors.goldDeep, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}
