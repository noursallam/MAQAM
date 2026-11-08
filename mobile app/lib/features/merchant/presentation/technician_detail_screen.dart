import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/l10n.dart';
import '../data/api_merchant_repository.dart';
import '../domain/merchant.dart';
import '../merchant_providers.dart';
import 'merchant_scan_tile.dart';

class TechnicianDetailScreen extends ConsumerWidget {
  const TechnicianDetailScreen({
    super.key,
    required this.technicianId,
    required this.onDeductPoints,
  });

  final int technicianId;
  final VoidCallback onDeductPoints;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final detail = ref.watch(technicianDetailProvider(technicianId));

    return Scaffold(
      appBar: AppBar(),
      body: detail.when(
        data: (tech) => ListView(
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: [
            Center(
              child: CircleAvatar(
                radius: 36,
                backgroundColor: AppColors.navy.withValues(alpha: 0.1),
                child: const Icon(Icons.person, size: 40, color: AppColors.navy),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Center(child: Text(tech.fullName, style: theme.textTheme.titleLarge)),
            if (tech.phone != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Center(
                child: Text(
                  tech.phone!,
                  style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                ),
              ),
            ],
            const SizedBox(height: AppSpacing.lg),
            Row(
              children: [
                Expanded(child: _Stat(label: l10n.merchantTechnicianPoints, value: '${tech.totalPoints}')),
                Expanded(child: _Stat(label: l10n.merchantTechnicianScans, value: '${tech.totalScans}')),
                Expanded(child: _Stat(label: l10n.merchantTechnicianBalance, value: '${tech.currentBalance}')),
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            FilledButton.icon(
              onPressed: onDeductPoints,
              icon: const Icon(Icons.remove_circle_outline),
              label: Text(l10n.merchantDeductPoints),
            ),
            const SizedBox(height: AppSpacing.xxl),
            Text(l10n.merchantTechnicianScans, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.md),
            _TechnicianScans(technicianId: technicianId),
          ],
        ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                e is ApiException ? apiErrorMessage(context, e) : l10n.errorGeneric,
                style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
              ),
              const SizedBox(height: AppSpacing.md),
              TextButton(
                onPressed: () => ref.invalidate(technicianDetailProvider(technicianId)),
                child: Text(l10n.retry),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      children: [
        Text(value, style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600)),
        const SizedBox(height: AppSpacing.xs),
        Text(label, style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
      ],
    );
  }
}

class _TechnicianScans extends ConsumerStatefulWidget {
  const _TechnicianScans({required this.technicianId});

  final int technicianId;

  @override
  ConsumerState<_TechnicianScans> createState() => _TechnicianScansState();
}

class _TechnicianScansState extends ConsumerState<_TechnicianScans> {
  final _scans = <MerchantScan>[];
  bool _hasMore = false;
  bool _loading = true;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final result = await ref.read(merchantRepositoryProvider).technicianScans(widget.technicianId, page: _page);
      setState(() {
        _scans.addAll(result.items);
        _hasMore = result.hasMore;
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    if (_scans.isEmpty && _loading) {
      return const Center(child: Padding(
        padding: EdgeInsets.all(AppSpacing.xxl),
        child: CircularProgressIndicator(),
      ));
    }
    return Column(
      children: [
        ...(_scans).map((scan) => MerchantScanTile(scan: scan)),
        if (_hasMore)
          TextButton(
            onPressed: _loading
                ? null
                : () {
                    _page++;
                    _load();
                  },
            child: Text(l10n.merchantLoadMore),
          ),
      ],
    );
  }
}
