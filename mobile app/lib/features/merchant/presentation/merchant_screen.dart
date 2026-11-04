import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../data/logo_picker.dart';
import '../domain/merchant.dart';
import '../merchant_providers.dart';
import 'merchant_scan_tile.dart';

/// Becoming a merchant (design: "23 الانضمام كتاجر"), and once applied, the
/// merchant's code and what it has earned (design: "24 لوحة التاجر").
class MerchantScreen extends ConsumerWidget {
  const MerchantScreen({super.key, required this.onViewAllScans});

  /// Opens every scan credited to the code.
  final VoidCallback onViewAllScans;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final merchant = ref.watch(merchantProvider);
    final applied = merchant.value != null;

    return Scaffold(
      appBar: SubPageAppBar(title: applied ? l10n.merchantDashboardTitle : l10n.accountMerchantTitle),
      body: SafeArea(
        top: false,
        child: AsyncView<MerchantProfile?>(
          value: merchant,
          errorTitle: l10n.merchantLoadFailed,
          onRetry: () => ref.invalidate(merchantProvider),
          data: (profile) => profile == null
              ? const _ApplyForm()
              : _Dashboard(profile: profile, onViewAllScans: onViewAllScans),
        ),
      ),
    );
  }
}

class _ApplyForm extends ConsumerStatefulWidget {
  const _ApplyForm();

  @override
  ConsumerState<_ApplyForm> createState() => _ApplyFormState();
}

class _ApplyFormState extends ConsumerState<_ApplyForm> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _address = TextEditingController();
  LogoImage? _logo;
  bool _sending = false;
  ApiException? _failure;

  @override
  void dispose() {
    _name.dispose();
    _address.dispose();
    super.dispose();
  }

  Future<void> _pickLogo() async {
    final logo = await ref.read(logoPickerProvider)();
    if (logo != null && mounted) setState(() => _logo = logo);
  }

  Future<void> _submit() async {
    if (_sending || !_form.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    setState(() {
      _sending = true;
      _failure = null;
    });
    try {
      await ref.read(merchantProvider.notifier).apply(
            businessName: _name.text.trim(),
            businessAddress: _address.text.trim(),
            logo: _logo,
          );
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(context.l10n.merchantApplied)));
      }
    } on ApiException catch (e) {
      if (mounted) setState(() => _failure = e);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    Widget label(String text, {bool optional = false}) => Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.sm),
          child: Text.rich(
            TextSpan(
              text: text,
              children: [
                if (optional) TextSpan(text: l10n.checkoutOptional, style: TextStyle(color: muted)),
              ],
            ),
            style: theme.textTheme.titleMedium,
          ),
        );

    return Form(
      key: _form,
      child: Column(
        children: [
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                NavyCard(
                  child: Column(
                    children: [
                      for (final benefit in [l10n.merchantBenefitCode, l10n.merchantBenefitPoints, l10n.merchantBenefitReview])
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
                          child: Row(
                            children: [
                              const Icon(Icons.check, size: 18, color: AppColors.goldBright),
                              const SizedBox(width: AppSpacing.sm),
                              Expanded(
                                child: Text(
                                  benefit,
                                  style: theme.textTheme.bodyLarge?.copyWith(color: Colors.white),
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.lg),
                label(l10n.merchantNameLabel),
                TextFormField(
                  controller: _name,
                  enabled: !_sending,
                  maxLength: 255,
                  textInputAction: TextInputAction.next,
                  validator: (value) => (value ?? '').trim().isEmpty ? l10n.fieldRequired : null,
                  decoration: InputDecoration(hintText: l10n.merchantNameHint, counterText: ''),
                ),
                const SizedBox(height: AppSpacing.lg),
                label(l10n.merchantAddressLabel, optional: true),
                TextFormField(
                  controller: _address,
                  enabled: !_sending,
                  minLines: 2,
                  maxLines: 4,
                  maxLength: 1000,
                  keyboardType: TextInputType.streetAddress,
                  decoration: InputDecoration(hintText: l10n.merchantAddressHint, counterText: ''),
                ),
                const SizedBox(height: AppSpacing.lg),
                label(l10n.merchantLogoLabel, optional: true),
                _LogoField(logo: _logo, onPick: _sending ? null : _pickLogo),
                if (_failure != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    apiErrorMessage(context, _failure!),
                    style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
                  ),
                ],
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                FilledButton(
                  onPressed: _sending ? null : _submit,
                  child: _sending
                      ? const SizedBox.square(
                          dimension: 22,
                          child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                        )
                      : Text(l10n.merchantSubmit),
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  l10n.merchantSubmitNote,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodySmall?.copyWith(color: muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _LogoField extends StatelessWidget {
  const _LogoField({required this.logo, required this.onPick});

  final LogoImage? logo;
  final VoidCallback? onPick;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return Material(
      color: AppColors.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radius),
        side: const BorderSide(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onPick,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              DecoratedBox(
                decoration: BoxDecoration(
                  color: AppColors.surfaceMuted,
                  borderRadius: BorderRadius.circular(AppSpacing.md),
                ),
                child: SizedBox.square(
                  dimension: 56,
                  child: Icon(
                    logo == null ? Icons.file_upload_outlined : Icons.image_outlined,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      logo == null ? l10n.merchantLogoPick : l10n.merchantLogoChosen,
                      style: theme.textTheme.titleMedium,
                    ),
                    Text(
                      logo?.fileName ?? l10n.merchantLogoHint,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Dashboard extends ConsumerStatefulWidget {
  const _Dashboard({required this.profile, required this.onViewAllScans});

  final MerchantProfile profile;
  final VoidCallback onViewAllScans;

  @override
  ConsumerState<_Dashboard> createState() => _DashboardState();
}

class _DashboardState extends ConsumerState<_Dashboard> {
  bool _uploading = false;

  Future<void> _changeLogo() async {
    final logo = await ref.read(logoPickerProvider)();
    if (logo == null || !mounted) return;
    setState(() => _uploading = true);
    try {
      await ref.read(merchantProvider.notifier).uploadLogo(logo);
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _share() async {
    final l10n = context.l10n;
    final code = widget.profile.code;
    final text = l10n.merchantShareText(widget.profile.businessName, code);
    var opened = false;
    try {
      opened = await launchUrl(
        Uri.https('wa.me', '/', {'text': text}),
        mode: LaunchMode.externalApplication,
      );
    } on Exception {
      opened = false;
    }
    if (opened || !mounted) return;
    // No WhatsApp: the code goes to the clipboard to paste anywhere.
    await Clipboard.setData(ClipboardData(text: code));
    if (mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.merchantCodeCopied)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final profile = widget.profile;
    final stats = profile.stats;

    return RefreshIndicator(
      onRefresh: () => ref.refresh(merchantProvider.future),
      child: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          _CodeCard(
            profile: profile,
            uploading: _uploading,
            onChangeLogo: _changeLogo,
            onShare: _share,
          ),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              _Stat(label: l10n.merchantStatPoints, value: stats.totalPoints),
              const SizedBox(width: AppSpacing.md),
              _Stat(label: l10n.merchantStatScans, value: stats.totalScans),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              _Stat(label: l10n.merchantStatMonth, value: stats.scansThisMonth),
              const SizedBox(width: AppSpacing.md),
              _Stat(label: l10n.merchantStatMonthPoints, value: stats.pointsThisMonth),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              _Stat(label: l10n.merchantStatCustomers, value: stats.uniqueCustomers),
            ],
          ),
          const SizedBox(height: AppSpacing.xl),
          _RecentScans(profile: profile, onViewAll: widget.onViewAllScans),
          const SizedBox(height: AppSpacing.md),
          InfoNote(text: l10n.merchantPointsNote),
        ],
      ),
    );
  }
}

class _CodeCard extends StatelessWidget {
  const _CodeCard({
    required this.profile,
    required this.uploading,
    required this.onChangeLogo,
    required this.onShare,
  });

  final MerchantProfile profile;
  final bool uploading;
  final VoidCallback onChangeLogo;
  final VoidCallback onShare;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final statusColor = profile.approved ? AppColors.success : AppColors.goldBright;

    return NavyCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              _LogoTile(url: profile.logoUrl, uploading: uploading, onTap: onChangeLogo),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      profile.businessName,
                      style: textTheme.titleMedium?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 18),
                    ),
                    Row(
                      children: [
                        Icon(Icons.circle, size: 8, color: statusColor),
                        const SizedBox(width: AppSpacing.xs),
                        Text(
                          profile.approved ? l10n.merchantApproved : l10n.merchantPending,
                          style: textTheme.bodyMedium?.copyWith(color: statusColor),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          DecoratedBox(
            decoration: BoxDecoration(
              color: AppColors.navyRaised,
              borderRadius: BorderRadius.circular(AppSpacing.md),
              border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
            ),
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(l10n.merchantCodeLabel, style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted)),
                        SelectableText(
                          profile.code,
                          textDirection: TextDirection.ltr,
                          style: textTheme.titleMedium?.copyWith(
                            color: AppColors.goldBright,
                            fontWeight: FontWeight.w700,
                            fontSize: 22,
                            letterSpacing: 1.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (profile.approved)
                    FilledButton(
                      style: FilledButton.styleFrom(
                        minimumSize: const Size(0, 48),
                        backgroundColor: AppColors.goldBright,
                        foregroundColor: AppColors.navy,
                        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.md)),
                      ),
                      onPressed: onShare,
                      child: Text(l10n.merchantShare),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            profile.approved ? l10n.merchantCodeHint : l10n.merchantPendingHint,
            style: textTheme.bodyMedium?.copyWith(color: Colors.white),
          ),
          if (profile.joinedAt != null || profile.stats.lastScanAt != null) ...[
            const SizedBox(height: AppSpacing.sm),
            Text(
              [
                if (profile.joinedAt != null) l10n.merchantSince(dayLabel(context, profile.joinedAt!, withYear: true)),
                if (profile.stats.lastScanAt != null) l10n.merchantLastScan(timeAgoLabel(context, profile.stats.lastScanAt!)),
              ].join(' · '),
              style: textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted),
            ),
          ],
        ],
      ),
    );
  }
}

class _LogoTile extends StatelessWidget {
  const _LogoTile({required this.url, required this.uploading, required this.onTap});

  final String? url;
  final bool uploading;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final placeholder = Center(
      child: Text(
        l10n.merchantLogoPlaceholder,
        style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.onNavyMuted),
      ),
    );

    return Semantics(
      button: true,
      label: l10n.merchantChangeLogo,
      child: Material(
        color: AppColors.navyRaised,
        borderRadius: BorderRadius.circular(AppSpacing.md),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: uploading ? null : onTap,
          child: SizedBox.square(
            dimension: 56,
            child: uploading
                ? const Center(
                    child: SizedBox.square(
                      dimension: 22,
                      child: CircularProgressIndicator(strokeWidth: 2.5, color: AppColors.goldBright),
                    ),
                  )
                : url == null
                    ? placeholder
                    : Image.network(url!, fit: BoxFit.cover, errorBuilder: (_, _, _) => placeholder),
          ),
        ),
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Expanded(
      child: Card(
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
              const SizedBox(height: AppSpacing.xs),
              Text(
                formatPoints(value),
                style: theme.textTheme.headlineMedium?.copyWith(fontSize: 28),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// The latest customers who used the code, with a way to see them all.
class _RecentScans extends StatelessWidget {
  const _RecentScans({required this.profile, required this.onViewAll});

  final MerchantProfile profile;
  final VoidCallback onViewAll;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final scans = profile.recentScans;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(child: Text(l10n.merchantRecentScans, style: theme.textTheme.titleLarge)),
            if (profile.stats.totalScans > scans.length)
              TextButton(onPressed: onViewAll, child: Text(l10n.merchantViewAll)),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        Card(
          margin: EdgeInsets.zero,
          clipBehavior: Clip.antiAlias,
          child: scans.isEmpty
              ? Padding(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: Text(
                    l10n.merchantNoScans,
                    style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                  ),
                )
              : Column(
                  children: [
                    for (final (index, scan) in scans.indexed) ...[
                      if (index > 0) const Divider(height: 1, color: AppColors.border),
                      MerchantScanTile(scan: scan),
                    ],
                  ],
                ),
        ),
      ],
    );
  }
}
