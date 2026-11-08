import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/scan.dart';
import '../scan_providers.dart';

/// Scans kept on the phone until there is a connection, and what the last
/// upload did with them (design: "30 مسحات في انتظار الرفع").
class PendingScansScreen extends ConsumerWidget {
  const PendingScansScreen({super.key});

  Future<void> _upload(BuildContext context, WidgetRef ref) async {
    final reached = await ref.read(scanQueueProvider.notifier).sync();
    if (!reached && context.mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(context.l10n.pendingStillOffline)));
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final queue = ref.watch(scanQueueProvider);
    final heading = theme.textTheme.headlineMedium?.copyWith(fontSize: 18);

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.pendingTitle),
      body: SafeArea(
        top: false,
        child: queue.pending.isEmpty && queue.lastSync.isEmpty
            ? Center(
                child: MessageView(
                  icon: Icons.cloud_done_outlined,
                  title: l10n.pendingEmpty,
                  message: l10n.pendingEmptyBody,
                ),
              )
            : ListView(
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: [
                  if (queue.pending.isNotEmpty) ...[
                    _UploadCard(
                      count: queue.pending.length,
                      syncing: queue.syncing,
                      onUpload: () => _upload(context, ref),
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    Text(l10n.pendingWaiting, style: heading),
                    const SizedBox(height: AppSpacing.sm),
                    _RowsCard(rows: [
                      for (final scan in queue.pending)
                        _ScanRow(scan: scan, trailing: StatusPill(label: l10n.scanPendingBadge)),
                    ]),
                    const SizedBox(height: AppSpacing.lg),
                  ],
                  if (queue.lastSync.isNotEmpty) ...[
                    Text(l10n.pendingLastUpload, style: heading),
                    const SizedBox(height: AppSpacing.sm),
                    _RowsCard(rows: [
                      for (final synced in queue.lastSync)
                        _SyncedRow(
                          synced: synced,
                          onDismiss: () => ref.read(scanQueueProvider.notifier).dismiss(synced),
                        ),
                    ]),
                    const SizedBox(height: AppSpacing.lg),
                  ],
                  Text(
                    l10n.pendingFooter,
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                  ),
                ],
              ),
      ),
    );
  }
}

class _UploadCard extends StatelessWidget {
  const _UploadCard({required this.count, required this.syncing, required this.onUpload});

  final int count;
  final bool syncing;
  final VoidCallback onUpload;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return NavyCard(
      child: Row(
        children: [
          const Icon(Icons.wifi_off, color: AppColors.goldBright),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  l10n.pendingCount(count),
                  style: textTheme.titleMedium?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
                ),
                Text(l10n.pendingAuto, style: textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted)),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          FilledButton(
            style: FilledButton.styleFrom(
              minimumSize: const Size(0, 48),
              backgroundColor: AppColors.goldBright,
              foregroundColor: AppColors.navy,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.md)),
            ),
            onPressed: syncing ? null : onUpload,
            child: syncing
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2.5, color: AppColors.navy),
                  )
                : Text(l10n.pendingUploadNow),
          ),
        ],
      ),
    );
  }
}

class _RowsCard extends StatelessWidget {
  const _RowsCard({required this.rows});

  final List<Widget> rows;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Column(
        children: [
          for (final (index, row) in rows.indexed) ...[
            if (index > 0) const Divider(height: 1, color: AppColors.border),
            row,
          ],
        ],
      ),
    );
  }
}

/// "Code 4444 ••••" over when and with which merchant it was scanned.
class _ScanRow extends StatelessWidget {
  const _ScanRow({required this.scan, required this.trailing, this.detail, this.detailColor});

  final QueuedScan scan;
  final Widget trailing;

  /// Replaces the time and merchant line, for example with why it was refused.
  final String? detail;
  final Color? detailColor;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final time = scan.scannedAt;
    final clock = '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.scanCodeTail(serialTail(scan.serialCode)), style: theme.textTheme.titleMedium),
                Text(
                  detail ??
                      '${dayLabel(context, time)} $clock · ${scan.merchantName ?? l10n.scanNoMerchant}',
                  style: theme.textTheme.bodyMedium
                      ?.copyWith(color: detailColor ?? theme.colorScheme.onSurfaceVariant),
                ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          trailing,
        ],
      ),
    );
  }
}

class _SyncedRow extends StatelessWidget {
  const _SyncedRow({required this.synced, required this.onDismiss});

  final SyncedScan synced;
  final VoidCallback onDismiss;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    if (synced.status == SyncStatus.rejected) {
      return _ScanRow(
        scan: synced.scan,
        detail: l10n.pendingRejected(synced.message ?? l10n.errorGeneric),
        detailColor: theme.colorScheme.error,
        trailing: TextButton(onPressed: onDismiss, child: Text(l10n.pendingHide)),
      );
    }
    return _ScanRow(
      scan: synced.scan,
      trailing: Text(
        synced.status == SyncStatus.credited ? '+${synced.points}' : l10n.pendingAlreadyCredited,
        textDirection: TextDirection.ltr,
        style: theme.textTheme.titleMedium?.copyWith(color: AppColors.whatsapp, fontWeight: FontWeight.w700),
      ),
    );
  }
}
