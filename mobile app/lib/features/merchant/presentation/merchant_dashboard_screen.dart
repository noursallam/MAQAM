import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/l10n.dart';
import '../domain/technician.dart';
import '../merchant_providers.dart';

class MerchantDashboardScreen extends ConsumerWidget {
  const MerchantDashboardScreen({
    super.key,
    required this.onTechnician,
    required this.onTransactions,
    required this.onSettings,
  });

  final ValueChanged<int> onTechnician;
  final VoidCallback onTransactions;
  final VoidCallback onSettings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final merchant = ref.watch(merchantProvider).value;
    final technicians = ref.watch(techniciansProvider);

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(merchantProvider);
          ref.invalidate(techniciansProvider);
        },
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            // --- Navy header ---
            _DashboardHeader(
              businessName: merchant?.businessName,
              onTransactions: onTransactions,
              onSettings: onSettings,
            ),
            // --- Stat cards grid ---
            if (merchant != null)
              Padding(
                padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, 0),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: _DashStatCard(
                            label: l10n.merchantStatMonth,
                            value: _grouped(merchant.stats.scansThisMonth),
                            icon: Icons.qr_code_scanner,
                            iconBg: const Color(0xFFFFF3E0),
                            iconColor: const Color(0xFFE65100),
                          ),
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: _DashStatCard(
                            label: l10n.merchantStatMonthPoints,
                            value: _grouped(merchant.stats.pointsThisMonth),
                            icon: Icons.stars,
                            iconBg: const Color(0xFFE8F5E9),
                            iconColor: const Color(0xFF2E7D32),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.md),
                    Row(
                      children: [
                        Expanded(
                          child: _DashStatCard(
                            label: l10n.merchantStatTotalScans,
                            value: _grouped(merchant.stats.totalScans),
                            icon: Icons.bar_chart,
                            iconBg: const Color(0xFFE3F2FD),
                            iconColor: const Color(0xFF1565C0),
                          ),
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: _DashStatCard(
                            label: l10n.merchantStatCustomers,
                            value: _grouped(merchant.stats.uniqueCustomers),
                            icon: Icons.people,
                            iconBg: const Color(0xFFF3E5F5),
                            iconColor: const Color(0xFF7B1FA2),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            // --- Technicians section ---
            Padding(
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg, AppSpacing.xxl, AppSpacing.lg, AppSpacing.md),
              child: Text(l10n.merchantTechnicians, style: theme.textTheme.titleLarge),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: technicians.when(
                data: (list) => list.isEmpty
                    ? Padding(
                        padding: const EdgeInsets.symmetric(vertical: AppSpacing.xxl),
                        child: Center(
                          child: Text(
                            l10n.merchantNoTechnicians,
                            style: theme.textTheme.bodyLarge?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ),
                      )
                    : Column(
                        children: list
                            .map((t) => _TechnicianTile(
                                  technician: t,
                                  onTap: () => onTechnician(t.id),
                                ))
                            .toList(),
                      ),
                loading: () => const Center(
                  child: Padding(
                    padding: EdgeInsets.all(AppSpacing.xxl),
                    child: CircularProgressIndicator(),
                  ),
                ),
                error: (e, _) => Padding(
                  padding: const EdgeInsets.all(AppSpacing.xxl),
                  child: Column(
                    children: [
                      Text(
                        e is ApiException ? apiErrorMessage(context, e) : l10n.errorGeneric,
                        style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
                      ),
                      const SizedBox(height: AppSpacing.md),
                      TextButton(
                        onPressed: () => ref.invalidate(techniciansProvider),
                        child: Text(l10n.retry),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.xl),
          ],
        ),
      ),
    );
  }
}

class _DashboardHeader extends StatelessWidget {
  const _DashboardHeader({
    required this.businessName,
    required this.onTransactions,
    required this.onSettings,
  });

  final String? businessName;
  final VoidCallback onTransactions;
  final VoidCallback onSettings;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFF1E2538), AppColors.navy],
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(24)),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(
              AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).maybePop(),
                    icon: const Icon(Icons.arrow_back, color: Colors.white),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.receipt_long, color: Colors.white),
                    tooltip: l10n.merchantTransactions,
                    onPressed: onTransactions,
                  ),
                  IconButton(
                    icon: const Icon(Icons.settings_outlined, color: Colors.white),
                    onPressed: onSettings,
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                l10n.merchantDashboard,
                style: textTheme.headlineSmall?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
              if (businessName != null) ...[
                const SizedBox(height: AppSpacing.xs),
                Text(
                  businessName!,
                  style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _DashStatCard extends StatelessWidget {
  const _DashStatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.iconBg,
    required this.iconColor,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color iconBg;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  label,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: iconBg,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: iconColor, size: 20),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            value,
            style: theme.textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w700,
              color: AppColors.navy,
            ),
          ),
        ],
      ),
    );
  }
}

class _TechnicianTile extends StatelessWidget {
  const _TechnicianTile({required this.technician, required this.onTap});

  final Technician technician;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: ListTile(
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        leading: Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: AppColors.navy.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.person, color: AppColors.navy, size: 22),
        ),
        title: Text(
          technician.fullName,
          style: theme.textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w600),
        ),
        subtitle: Text(
          '${l10n.merchantTechnicianPoints}: ${_grouped(technician.totalPoints)}  ·  ${l10n.merchantTechnicianScans}: ${_grouped(technician.totalScans)}',
          style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
        ),
        trailing: const Icon(Icons.chevron_right, color: AppColors.textSecondary),
      ),
    );
  }
}

String _grouped(int value) => value.toString().replaceAllMapped(
      RegExp(r'\B(?=(\d{3})+(?!\d))'),
      (match) => ',',
    );
