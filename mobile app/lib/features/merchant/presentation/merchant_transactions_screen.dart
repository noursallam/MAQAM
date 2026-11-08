import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/technician.dart';
import '../merchant_providers.dart';

class MerchantTransactionsScreen extends ConsumerWidget {
  const MerchantTransactionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final transactions = ref.watch(merchantTransactionsProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.merchantTransactions)),
      body: transactions.when(
        data: (list) => list.isEmpty
            ? Center(
                child: Text(
                  l10n.merchantNoTransactions,
                  style: theme.textTheme.bodyLarge?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              )
            : RefreshIndicator(
                onRefresh: () async => ref.invalidate(merchantTransactionsProvider),
                child: ListView.builder(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  itemCount: list.length,
                  itemBuilder: (context, index) => _TransactionTile(transaction: list[index]),
                ),
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
                onPressed: () => ref.invalidate(merchantTransactionsProvider),
                child: Text(l10n.retry),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.transaction});

  final MerchantTransaction transaction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final l10n = context.l10n;
    final isDeduction = transaction.type == 'deduction';
    final sign = isDeduction ? '-' : '+';
    final color = isDeduction ? theme.colorScheme.error : AppColors.navy;
    final label = isDeduction ? l10n.merchantTransactionDeducted : l10n.merchantTransactionEarned;

    return Card(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withValues(alpha: 0.1),
          child: Icon(isDeduction ? Icons.remove : Icons.add, color: color),
        ),
        title: Text(transaction.customerName),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label),
            if (transaction.description != null)
              Text(
                transaction.description!,
                style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
              ),
            if (transaction.createdAt != null)
              Text(
                dayLabel(context, transaction.createdAt!),
                style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
              ),
          ],
        ),
        trailing: Text(
          '$sign${transaction.amount}',
          style: theme.textTheme.titleMedium?.copyWith(color: color, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}
