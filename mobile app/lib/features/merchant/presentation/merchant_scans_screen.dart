import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../merchant_providers.dart';
import 'merchant_scan_tile.dart';

/// Every scan where a customer entered the merchant's code, newest first. No design; it follows
/// the notifications list.
class MerchantScansScreen extends ConsumerWidget {
  const MerchantScansScreen({super.key});

  Future<void> _loadMore(BuildContext context, WidgetRef ref) async {
    try {
      await ref.read(merchantScansProvider.notifier).loadMore();
    } on ApiException catch (e) {
      if (context.mounted) showApiError(context, e);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final scans = ref.watch(merchantScansProvider);

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.merchantScansTitle),
      body: SafeArea(
        top: false,
        child: RefreshIndicator(
          onRefresh: () => ref.refresh(merchantScansProvider.future),
          child: AsyncView<MerchantScanList>(
            value: scans,
            errorTitle: l10n.merchantScansLoadFailed,
            onRetry: () => ref.invalidate(merchantScansProvider),
            data: (list) => list.items.isEmpty
                ? ListView(
                    children: [
                      MessageView(icon: Icons.qr_code_scanner, message: l10n.merchantNoScans),
                    ],
                  )
                : ListView(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    children: [
                      Card(
                        margin: EdgeInsets.zero,
                        clipBehavior: Clip.antiAlias,
                        child: Column(
                          children: [
                            for (final (index, scan) in list.items.indexed) ...[
                              if (index > 0) const Divider(height: 1, color: AppColors.border),
                              MerchantScanTile(scan: scan),
                            ],
                          ],
                        ),
                      ),
                      if (list.hasMore) ...[
                        const SizedBox(height: AppSpacing.md),
                        OutlinedButton(
                          onPressed: list.loadingMore ? null : () => _loadMore(context, ref),
                          child: list.loadingMore
                              ? const SizedBox.square(
                                  dimension: 22,
                                  child: CircularProgressIndicator(strokeWidth: 2.5),
                                )
                              : Text(l10n.merchantLoadMore),
                        ),
                      ],
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}
