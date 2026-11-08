import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/locale/locale_provider.dart';
import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/security/app_lock.dart';
import '../../../core/session/auth_session.dart';
import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/app_art.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../loyalty/domain/wallet.dart';
import '../../loyalty/loyalty_providers.dart';
import '../../merchant/domain/merchant.dart';
import '../../merchant/merchant_providers.dart';
import '../../notifications/data/api_notifications_repository.dart';
import '../../scan/scan_providers.dart';

/// Profile and settings (design: "20 حسابي").
class AccountScreen extends ConsumerWidget {
  const AccountScreen({
    super.key,
    required this.onSignIn,
    required this.onSignOut,
    required this.onCreatePin,
    required this.onOrders,
    required this.onNotifications,
    required this.onEditName,
    required this.onMerchant,
    required this.onChangeMerchant,
    required this.onMerchantDashboard,
    required this.onAddresses,
    required this.onPendingScans,
    required this.onChangePhone,
    required this.onHelp,
    required this.onLegal,
    required this.onDeleteAccount,
  });

  final VoidCallback onSignIn;
  final VoidCallback onOrders;
  final VoidCallback onNotifications;

  /// Opens the screen where the app PIN is chosen.
  final VoidCallback onCreatePin;
  final Future<void> Function() onSignOut;

  /// Saves a new name; throws an [ApiException] if the server refuses it.
  final Future<void> Function(String name) onEditName;
  final VoidCallback onMerchant;
  final VoidCallback onChangeMerchant;
  final VoidCallback onMerchantDashboard;
  final VoidCallback onAddresses;
  final VoidCallback onPendingScans;
  final VoidCallback onChangePhone;
  final VoidCallback onHelp;
  final VoidCallback onLegal;
  final VoidCallback onDeleteAccount;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final session = ref.watch(sessionProvider);

    return SafeArea(
      bottom: false,
      child: ListView(
        padding: const EdgeInsets.only(bottom: AppSpacing.xl),
        children: [
          PageHeader(title: l10n.navAccount),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            child: session == null
                ? _GuestAccount(onSignIn: onSignIn, onHelp: onHelp, onLegal: onLegal)
                : _SignedInAccount(session: session, screen: this),
          ),
        ],
      ),
    );
  }
}

class _GuestAccount extends ConsumerWidget {
  const _GuestAccount({required this.onSignIn, required this.onHelp, required this.onLegal});

  final VoidCallback onSignIn;
  final VoidCallback onHelp;
  final VoidCallback onLegal;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        MessageView(
          icon: Icons.person_outline,
          message: l10n.accountGuest,
          actionLabel: l10n.signIn,
          onAction: onSignIn,
        ),
        const SizedBox(height: AppSpacing.lg),
        _SettingsCard(rows: [
          _LanguageRow(),
          _Row(label: l10n.accountHelp, onTap: onHelp),
          _Row(label: l10n.accountLegal, onTap: onLegal),
        ]),
        const SizedBox(height: AppSpacing.xl),
        const _VersionLabel(),
      ],
    );
  }
}

class _SignedInAccount extends ConsumerWidget {
  const _SignedInAccount({required this.session, required this.screen});

  final AuthSession session;

  /// The callbacks the router gave the account screen.
  final AccountScreen screen;

  Future<void> _confirmSignOut(BuildContext context) async {
    final l10n = context.l10n;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.signOutConfirmTitle),
        content: Text(l10n.signOutConfirmBody),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(l10n.cancel)),
          TextButton(onPressed: () => Navigator.pop(context, true), child: Text(l10n.signOut)),
        ],
      ),
    );
    if (confirmed == true) await screen.onSignOut();
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final unread = ref.watch(unreadNotificationsProvider);
    final pendingScans = ref.watch(pendingScanCountProvider);
    final merchant = ref.watch(merchantProvider).value;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _ProfileCard(
          session: session,
          merchant: merchant,
          wallet: ref.watch(walletProvider).value,
          onEditName: screen.onEditName,
        ),
        const SizedBox(height: AppSpacing.md),
        if (merchant == null)
          _MerchantBanner(onTap: screen.onMerchant)
        else
          _MerchantCard(merchant: merchant, onOpen: screen.onMerchant),
        if (merchant?.approved ?? false) ...[
          const SizedBox(height: AppSpacing.sm),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            child: FilledButton.icon(
              onPressed: screen.onMerchantDashboard,
              icon: const Icon(Icons.dashboard, size: 20),
              label: Text(l10n.merchantDashboard),
            ),
          ),
        ],
        if (session.isBoundToMerchant) ...[
          const SizedBox(height: AppSpacing.sm),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            child: OutlinedButton.icon(
              onPressed: screen.onChangeMerchant,
              icon: const Icon(Icons.swap_horiz, size: 20),
              label: Text(l10n.merchantChangeTitle),
            ),
          ),
        ],
        const SizedBox(height: AppSpacing.md),
        _SettingsCard(rows: [
          _Row(label: l10n.actionOrders, onTap: screen.onOrders),
          _Row(label: l10n.accountAddresses, onTap: screen.onAddresses),
          _Row(
            label: l10n.notifications,
            value: unread > 0 ? l10n.accountNewCount(unread) : null,
            onTap: screen.onNotifications,
          ),
          _Row(
            label: l10n.accountPendingScans,
            value: pendingScans > 0 ? '$pendingScans' : null,
            onTap: screen.onPendingScans,
          ),
          _AppLockRow(onCreatePin: screen.onCreatePin),
          _Row(label: l10n.accountChangePhone, onTap: screen.onChangePhone),
          _LanguageRow(),
          _Row(label: l10n.accountHelp, onTap: screen.onHelp),
          _Row(label: l10n.accountLegal, onTap: screen.onLegal),
        ]),
        const SizedBox(height: AppSpacing.md),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            TextButton(
              onPressed: () => _confirmSignOut(context),
              child: Text(l10n.signOut),
            ),
            TextButton(
              style: TextButton.styleFrom(foregroundColor: theme.colorScheme.error),
              onPressed: screen.onDeleteAccount,
              child: Text(l10n.accountDelete),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        const _VersionLabel(),
      ],
    );
  }
}

/// Who is signed in, on navy: name and number, merchant status, and where they stand in points.
class _ProfileCard extends StatelessWidget {
  const _ProfileCard({
    required this.session,
    required this.merchant,
    required this.wallet,
    required this.onEditName,
  });

  final AuthSession session;
  final MerchantProfile? merchant;

  /// Null while it loads; the points part waits for it.
  final Wallet? wallet;
  final Future<void> Function(String name) onEditName;

  Future<void> _edit(BuildContext context) async {
    final saved = await showDialog<bool>(
      context: context,
      builder: (context) => _EditNameDialog(initial: session.fullName ?? '', onSave: onEditName),
    );
    if (saved == true && context.mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(context.l10n.accountNameSaved)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final name = session.fullName?.trim() ?? '';
    final phone = session.phone;
    final isMerchant = merchant?.approved ?? false;

    return NavyCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              DecoratedBox(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: isMerchant ? AppColors.goldBright : AppColors.navyRaised, width: 2),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(2),
                  child: CircleAvatar(
                    radius: 28,
                    backgroundColor: AppColors.navyRaised,
                    foregroundColor: AppColors.goldBright,
                    child: name.isEmpty
                        ? const Icon(Icons.person_outline)
                        : Text(
                            name.characters.first,
                            style: textTheme.titleLarge?.copyWith(color: AppColors.goldBright),
                          ),
                  ),
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name.isEmpty ? l10n.accountNoName : name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: textTheme.titleMedium
                          ?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 18),
                    ),
                    if (phone != null)
                      Text(
                        phone.length == 11
                            ? '${phone.substring(0, 3)} ${phone.substring(3, 7)} ${phone.substring(7)}'
                            : phone,
                        textDirection: TextDirection.ltr,
                        style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted),
                      ),
                    if (isMerchant) ...[
                      const SizedBox(height: AppSpacing.xs),
                      _MerchantChip(label: l10n.merchantApproved),
                    ],
                  ],
                ),
              ),
              IconButton(
                tooltip: l10n.accountEdit,
                onPressed: () => _edit(context),
                icon: const Icon(Icons.edit_outlined, color: AppColors.goldBright),
              ),
            ],
          ),
          if (wallet != null) ...[
            const SizedBox(height: AppSpacing.lg),
            _RankProgress(wallet: wallet!),
          ],
        ],
      ),
    );
  }
}

class _MerchantChip extends StatelessWidget {
  const _MerchantChip({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.goldBright,
        borderRadius: BorderRadius.circular(AppSpacing.radius),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.verified, size: 14, color: AppColors.navy),
            const SizedBox(width: AppSpacing.xs),
            Text(
              label,
              style: Theme.of(context)
                  .textTheme
                  .bodySmall
                  ?.copyWith(color: AppColors.navy, fontWeight: FontWeight.w700),
            ),
          ],
        ),
      ),
    );
  }
}

/// The points balance, the rank and how far the next one is.
class _RankProgress extends StatelessWidget {
  const _RankProgress({required this.wallet});

  final Wallet wallet;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final muted = textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted);
    final next = wallet.nextRankName;

    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.navyRaised,
        borderRadius: BorderRadius.circular(AppSpacing.md),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                const ArtIcon(AppArt.coin, size: 32),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.accountPointsBalance, style: muted),
                      Text(
                        formatPoints(wallet.balance),
                        style: textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ),
                if (wallet.rankName != null) ...[
                  const ArtIcon(AppArt.rankShield, size: 28),
                  const SizedBox(width: AppSpacing.xs),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l10n.accountRank, style: muted),
                      Text(
                        wallet.rankName!,
                        style: textTheme.titleMedium
                            ?.copyWith(color: AppColors.goldBright, fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                ],
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            ClipRRect(
              borderRadius: BorderRadius.circular(AppSpacing.xs),
              child: LinearProgressIndicator(
                value: wallet.progress.clamp(0, 1).toDouble(),
                minHeight: 6,
                backgroundColor: AppColors.navy,
                color: AppColors.goldBright,
              ),
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              next == null
                  ? l10n.accountTopRank
                  : l10n.accountToNextRank(formatPoints(wallet.pointsToNextRank), next),
              style: muted,
            ),
          ],
        ),
      ),
    );
  }
}

/// A merchant's code and this month's numbers, one tap from the merchant dashboard.
class _MerchantCard extends StatelessWidget {
  const _MerchantCard({required this.merchant, required this.onOpen});

  final MerchantProfile merchant;
  final VoidCallback onOpen;

  Future<void> _copy(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: merchant.code));
    if (context.mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(context.l10n.merchantCodeCopied)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant);

    return Card(
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radius),
        side: const BorderSide(color: AppColors.gold, width: 1.5),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onOpen,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  _MerchantLogo(url: merchant.logoUrl),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          merchant.businessName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                        ),
                        Text(
                          merchant.approved ? l10n.accountMerchantOpen : l10n.accountMerchantPendingTitle,
                          style: muted,
                        ),
                      ],
                    ),
                  ),
                  Icon(Icons.navigate_next, color: theme.colorScheme.onSurfaceVariant),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              DecoratedBox(
                decoration: BoxDecoration(
                  color: AppColors.goldSoft,
                  borderRadius: BorderRadius.circular(AppSpacing.md),
                ),
                child: Padding(
                  padding: const EdgeInsetsDirectional.only(start: AppSpacing.md),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          merchant.code,
                          textDirection: TextDirection.ltr,
                          textAlign: TextAlign.start,
                          style: theme.textTheme.titleMedium?.copyWith(
                            color: AppColors.goldDeep,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 1.2,
                          ),
                        ),
                      ),
                      IconButton(
                        tooltip: l10n.accountCopyCode,
                        onPressed: () => _copy(context),
                        icon: const Icon(Icons.copy_rounded, size: 20, color: AppColors.goldDeep),
                      ),
                    ],
                  ),
                ),
              ),
              if (merchant.approved) ...[
                const SizedBox(height: AppSpacing.md),
                Row(
                  children: [
                    _MiniStat(label: l10n.merchantStatMonth, value: merchant.stats.scansThisMonth),
                    _MiniStat(label: l10n.merchantStatMonthPoints, value: merchant.stats.pointsThisMonth),
                    _MiniStat(label: l10n.merchantStatCustomers, value: merchant.stats.uniqueCustomers),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _MerchantLogo extends StatelessWidget {
  const _MerchantLogo({required this.url});

  final String? url;

  @override
  Widget build(BuildContext context) {
    const placeholder = ColoredBox(
      color: AppColors.goldSoft,
      child: Icon(Icons.storefront_outlined, color: AppColors.goldDeep),
    );

    return ClipRRect(
      borderRadius: BorderRadius.circular(AppSpacing.md),
      child: SizedBox.square(
        dimension: 48,
        child: url == null
            ? placeholder
            : Image.network(url!, fit: BoxFit.cover, errorBuilder: (_, _, _) => placeholder),
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Expanded(
      child: Column(
        children: [
          Text(formatPoints(value), style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
          Text(
            label,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
        ],
      ),
    );
  }
}

class _MerchantBanner extends StatelessWidget {
  const _MerchantBanner({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Material(
      color: AppColors.navy,
      borderRadius: BorderRadius.circular(AppSpacing.radius),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              const Icon(Icons.storefront_outlined, color: AppColors.goldBright),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.accountMerchantTitle,
                      style: textTheme.titleMedium
                          ?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
                    ),
                    Text(
                      l10n.accountMerchantBody,
                      style: textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.navigate_next, color: AppColors.goldBright),
            ],
          ),
        ),
      ),
    );
  }
}

class _SettingsCard extends StatelessWidget {
  const _SettingsCard({required this.rows});

  final List<Widget> rows;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
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

class _Row extends StatelessWidget {
  const _Row({required this.label, this.value, required this.onTap});

  final String label;
  final String? value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final mutedText = theme.colorScheme.onSurfaceVariant;

    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.lg),
        child: Row(
          children: [
            Expanded(child: Text(label, style: theme.textTheme.titleMedium)),
            if (value != null) ...[
              Text(value!, style: theme.textTheme.bodyMedium?.copyWith(color: mutedText)),
              const SizedBox(width: AppSpacing.sm),
            ],
            Icon(Icons.navigate_next, size: 20, color: mutedText),
          ],
        ),
      ),
    );
  }
}

class _LanguageRow extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    // Reading the locale rebuilds the row with the new language's own name.
    ref.watch(localeProvider);

    return _Row(
      label: l10n.accountLanguage,
      value: l10n.languageName,
      onTap: ref.read(localeProvider.notifier).toggle,
    );
  }
}

/// Turns the app lock on or off. A device with a sensor gets biometrics;
/// one without gets the app's own PIN.
class _AppLockRow extends ConsumerWidget {
  const _AppLockRow({required this.onCreatePin});

  final VoidCallback onCreatePin;

  Future<void> _toggle(BuildContext context, WidgetRef ref, bool enabled) async {
    final l10n = context.l10n;
    final appLock = ref.read(appLockProvider.notifier);
    if (enabled) {
      await appLock.decline();
      return;
    }
    if (await appLock.isAvailable()) {
      await appLock.enable(l10n.biometricEnableReason);
    } else {
      onCreatePin();
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final lock = ref.watch(appLockProvider);

    return _Row(
      label: l10n.accountAppLock,
      value: lock.usesBiometrics
          ? l10n.accountLockBiometrics
          : lock.usesPin
              ? l10n.accountLockPin
              : l10n.accountBiometricsOff,
      onTap: () => _toggle(context, ref, lock.enabled),
    );
  }
}

class _VersionLabel extends StatelessWidget {
  const _VersionLabel();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Text(
      context.l10n.accountVersion(appVersion),
      textAlign: TextAlign.center,
      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
    );
  }
}

class _EditNameDialog extends StatefulWidget {
  const _EditNameDialog({required this.initial, required this.onSave});

  final String initial;
  final Future<void> Function(String name) onSave;

  @override
  State<_EditNameDialog> createState() => _EditNameDialogState();
}

class _EditNameDialogState extends State<_EditNameDialog> {
  late final _name = TextEditingController(text: widget.initial);
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _name.text.trim();
    if (name.length < 2) {
      setState(() => _error = context.l10n.accountNameRequired);
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await widget.onSave(name);
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = apiErrorMessage(context, e));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return AlertDialog(
      title: Text(l10n.accountEditName),
      content: TextField(
        controller: _name,
        autofocus: true,
        enabled: !_saving,
        textCapitalization: TextCapitalization.words,
        textInputAction: TextInputAction.done,
        maxLength: 255,
        onSubmitted: (_) => _save(),
        decoration: InputDecoration(
          labelText: l10n.accountNameLabel,
          errorText: _error,
          counterText: '',
        ),
      ),
      actions: [
        TextButton(
          onPressed: _saving ? null : () => Navigator.pop(context, false),
          child: Text(l10n.cancel),
        ),
        TextButton(onPressed: _saving ? null : _save, child: Text(l10n.save)),
      ],
    );
  }
}
