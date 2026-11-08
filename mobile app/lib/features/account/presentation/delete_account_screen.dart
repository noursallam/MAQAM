import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../loyalty/loyalty_providers.dart';

/// The word the customer types to confirm, the same in both languages.
const _confirmWord = 'DELETE';

/// Confirms deleting the account for good (design: "26 حذف الحساب").
class DeleteAccountScreen extends ConsumerStatefulWidget {
  const DeleteAccountScreen({super.key, required this.onDelete});

  /// Deletes the account and leaves it; throws an [ApiException] if the server refuses.
  final Future<void> Function() onDelete;

  @override
  ConsumerState<DeleteAccountScreen> createState() => _DeleteAccountScreenState();
}

class _DeleteAccountScreenState extends ConsumerState<DeleteAccountScreen> {
  final _confirm = TextEditingController();
  bool _deleting = false;

  @override
  void dispose() {
    _confirm.dispose();
    super.dispose();
  }

  bool get _confirmed => _confirm.text.trim().toUpperCase() == _confirmWord;

  Future<void> _delete() async {
    if (!_confirmed || _deleting) return;
    setState(() => _deleting = true);
    try {
      await widget.onDelete();
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _deleting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final balance = ref.watch(walletProvider).value?.balance;

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.accountDelete),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(AppSpacing.lg),
                children: [
                  Text(l10n.deleteTitle, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22)),
                  const SizedBox(height: AppSpacing.xs),
                  Text(l10n.deleteSubtitle, style: theme.textTheme.bodyLarge?.copyWith(color: muted)),
                  const SizedBox(height: AppSpacing.lg),
                  _ListCard(
                    title: l10n.deleteLostTitle,
                    titleColor: AppColors.error,
                    headerColor: AppColors.errorSoft,
                    items: [
                      l10n.deleteLostIdentity,
                      balance == null ? l10n.deleteLostPoints : l10n.deleteLostPointsCount(balance),
                      l10n.deleteLostRewards,
                      l10n.deleteLostAddresses,
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  _ListCard(
                    title: l10n.deleteKeptTitle,
                    headerColor: AppColors.surfaceMuted,
                    items: [l10n.deleteKeptOrders],
                    muted: true,
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  Text.rich(
                    TextSpan(
                      text: l10n.deleteConfirmPrefix,
                      children: [
                        TextSpan(
                          text: _confirmWord,
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                    style: theme.textTheme.titleMedium,
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  TextField(
                    controller: _confirm,
                    textDirection: TextDirection.ltr,
                    textCapitalization: TextCapitalization.characters,
                    autocorrect: false,
                    onChanged: (_) => setState(() {}),
                    onSubmitted: (_) => _delete(),
                    decoration: const InputDecoration(hintText: _confirmWord),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  FilledButton(
                    style: FilledButton.styleFrom(backgroundColor: AppColors.error),
                    onPressed: _confirmed && !_deleting ? _delete : null,
                    child: _deleting
                        ? const SizedBox.square(
                            dimension: 22,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Text(l10n.deleteSubmit),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  TextButton(
                    onPressed: _deleting ? null : () => Navigator.maybePop(context),
                    child: Text(l10n.deleteKeep),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// A card with a tinted header and a plain list beneath it.
class _ListCard extends StatelessWidget {
  const _ListCard({
    required this.title,
    required this.headerColor,
    required this.items,
    this.titleColor,
    this.muted = false,
  });

  final String title;
  final Color headerColor;
  final Color? titleColor;
  final List<String> items;
  final bool muted;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ColoredBox(
            color: headerColor,
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Text(
                title,
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700, color: titleColor),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (final item in items)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
                    child: Text(
                      item,
                      style: theme.textTheme.bodyLarge
                          ?.copyWith(color: muted ? theme.colorScheme.onSurfaceVariant : null),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
