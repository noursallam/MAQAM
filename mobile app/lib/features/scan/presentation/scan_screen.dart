import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/session/account_status.dart';
import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../loyalty/loyalty_providers.dart';
import '../data/api_scan_repository.dart';
import '../domain/scan.dart';
import '../scan_providers.dart';
import 'camera_view.dart';

/// Camera scanner for the code inside a pack (design: "08 مسح الكود"). With
/// no connection the code is kept on the phone for later (design: "29 مسح بدون إنترنت").
class ScanScreen extends ConsumerStatefulWidget {
  const ScanScreen({super.key, required this.onScanned, required this.onOpenPending});

  final ValueChanged<ScanResult> onScanned;

  /// Opens the list of scans waiting for a connection.
  final VoidCallback onOpenPending;

  @override
  ConsumerState<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends ConsumerState<ScanScreen> {
  final _merchantCode = TextEditingController();

  /// The merchant to credit: a recent one, or one typed in. Null for none.
  RecentMerchant? _merchant;
  bool _torchOn = false;
  bool _claiming = false;
  ApiException? _failure;

  /// Set once a scan could not reach the server; cleared by the next one that does.
  bool _offline = false;

  /// The scan just kept on the phone, shown until the customer scans again.
  QueuedScan? _saved;

  @override
  void initState() {
    super.initState();
    // Whatever waits from an earlier offline scan goes up first, if it can.
    Future.microtask(() => ref.read(scanQueueProvider.notifier).sync());
  }

  @override
  void dispose() {
    _merchantCode.dispose();
    super.dispose();
  }

  Future<void> _claim(String raw) async {
    // The camera reports the same code many times a second.
    if (_claiming) return;
    final serial = serialFromScan(raw);
    if (serial.isEmpty) return;

    setState(() {
      _claiming = true;
      _failure = null;
    });
    try {
      final result = await ref
          .read(scanRepositoryProvider)
          .scan(serialCode: serial, merchantCode: _merchant?.code);
      if (result.accountFrozen) {
        // The router moves to the suspended-account screen.
        ref.read(accountFrozenProvider.notifier).freeze();
        return;
      }
      ref.invalidate(walletProvider);
      _offline = false;
      if (mounted) widget.onScanned(result);
    } on ApiException catch (e) {
      if (e.kind == ApiErrorKind.network) {
        await _keepForLater(serial);
      } else if (mounted) {
        setState(() => _failure = e);
      }
    } finally {
      if (mounted) setState(() => _claiming = false);
    }
  }

  Future<void> _keepForLater(String serial) async {
    final scan = QueuedScan(
      serialCode: serial,
      scannedAt: DateTime.now(),
      merchantCode: _merchant?.code,
      merchantName: _merchant?.name,
    );
    await ref.read(scanQueueProvider.notifier).enqueue(scan);
    HapticFeedback.mediumImpact();
    if (mounted) {
      setState(() {
        _offline = true;
        _saved = scan;
      });
    }
  }

  void _addMerchant() {
    final code = _merchantCode.text.trim();
    if (code.isEmpty) return;
    FocusScope.of(context).unfocus();
    // The server checks the code when a pack is scanned; until then show it as typed.
    setState(() => _merchant = RecentMerchant(code: code, name: code));
    _merchantCode.clear();
  }

  Future<void> _enterManually() async {
    final code = await showDialog<String>(
      context: context,
      builder: (context) => const _ManualCodeDialog(),
    );
    if (code != null && code.trim().isNotEmpty) await _claim(code);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final recent = ref.watch(recentMerchantsProvider).value ?? const <RecentMerchant>[];
    final camera = ref.watch(cameraViewBuilderProvider);
    // A merchant typed by code sits beside the recent ones once chosen.
    final merchants = [
      if (_merchant != null && !recent.any((m) => m.code == _merchant!.code)) _merchant!,
      ...recent,
    ];

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: AppColors.navy,
        body: Column(
          children: [
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  camera(onCode: _claim, torchOn: _torchOn, paused: _claiming || _saved != null),
                  const IgnorePointer(child: _ViewfinderOverlay()),
                  SafeArea(
                    child: Column(
                      children: [
                        Padding(
                          padding: const EdgeInsets.all(AppSpacing.sm),
                          child: Row(
                            children: [
                              IconButton(
                                tooltip: l10n.close,
                                color: Colors.white,
                                onPressed: () => Navigator.maybePop(context),
                                icon: const Icon(Icons.close),
                              ),
                              Expanded(
                                child: Text(
                                  l10n.scanTitle,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 17,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                              IconButton(
                                tooltip: _torchOn ? l10n.scanTorchOff : l10n.scanTorchOn,
                                color: _torchOn ? AppColors.goldBright : Colors.white,
                                onPressed: () => setState(() => _torchOn = !_torchOn),
                                icon: Icon(_torchOn ? Icons.flash_on : Icons.flash_off_outlined),
                              ),
                            ],
                          ),
                        ),
                        if (_offline) const _OfflineBanner(),
                        const Spacer(),
                        Padding(
                          padding: const EdgeInsets.all(AppSpacing.xl),
                          child: _claiming
                              ? const CircularProgressIndicator(color: AppColors.goldBright)
                              : Semantics(
                                  liveRegion: _failure != null,
                                  child: Text(
                                    _failure == null
                                        ? l10n.scanHint
                                        : apiErrorMessage(context, _failure!),
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      color: _failure == null
                                          ? AppColors.onNavyMuted
                                          : const Color(0xFFFFB4AB),
                                      fontSize: 15,
                                      height: 1.6,
                                    ),
                                  ),
                                ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            if (_saved != null)
              _SavedOfflinePanel(
                scan: _saved!,
                pendingCount: ref.watch(pendingScanCountProvider),
                onOpenPending: widget.onOpenPending,
                onScanAnother: () => setState(() => _saved = null),
              )
            else if (!(ref.watch(sessionProvider)?.isBoundToMerchant ?? false))
              _MerchantPanel(
                merchants: merchants,
                selected: _merchant,
                codeController: _merchantCode,
                onSelect: (merchant) => setState(() => _merchant = merchant),
                onAdd: _addMerchant,
                onManualEntry: _claiming ? null : _enterManually,
              ),
          ],
        ),
      ),
    );
  }
}

class _OfflineBanner extends StatelessWidget {
  const _OfflineBanner();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppColors.goldBright.withValues(alpha: 0.18),
          borderRadius: BorderRadius.circular(AppSpacing.radius),
          border: Border.all(color: AppColors.goldBright.withValues(alpha: 0.4)),
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          child: Row(
            children: [
              const Icon(Icons.wifi_off, size: 20, color: AppColors.goldBright),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Semantics(
                  liveRegion: true,
                  child: Text(
                    context.l10n.scanOfflineBanner,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.goldBright),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Confirms a scan was kept on the phone, in place of the merchant picker.
class _SavedOfflinePanel extends StatelessWidget {
  const _SavedOfflinePanel({
    required this.scan,
    required this.pendingCount,
    required this.onOpenPending,
    required this.onScanAnother,
  });

  final QueuedScan scan;
  final int pendingCount;
  final VoidCallback onOpenPending;
  final VoidCallback onScanAnother;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    return DecoratedBox(
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.sm),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                margin: EdgeInsets.zero,
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: Row(
                    children: [
                      const CircleAvatar(
                        radius: 24,
                        backgroundColor: AppColors.goldSoft,
                        child: Icon(Icons.check, color: AppColors.goldDeep),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              l10n.scanOfflineSaved,
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            Text(
                              '${l10n.scanCodeTail(serialTail(scan.serialCode))} · '
                              '${scan.merchantName ?? l10n.scanNoMerchant}',
                              style: theme.textTheme.bodyMedium?.copyWith(color: muted),
                            ),
                          ],
                        ),
                      ),
                      StatusPill(label: l10n.scanPendingBadge),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              Text(l10n.scanOfflineExplain, style: theme.textTheme.bodyMedium?.copyWith(color: muted)),
              const SizedBox(height: AppSpacing.md),
              Row(
                children: [
                  Expanded(
                    child: FilledButton(onPressed: onScanAnother, child: Text(l10n.scanAnother)),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(AppSpacing.controlHeight),
                        foregroundColor: theme.colorScheme.onSurface,
                        side: const BorderSide(color: AppColors.navy),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.radius)),
                      ),
                      onPressed: onOpenPending,
                      child: Text(l10n.scanPendingCount(pendingCount)),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Gold corner brackets marking where to hold the code.
class _ViewfinderOverlay extends StatelessWidget {
  const _ViewfinderOverlay();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: FractionallySizedBox(
        widthFactor: 0.56,
        child: AspectRatio(aspectRatio: 1, child: CustomPaint(painter: _CornersPainter())),
      ),
    );
  }
}

class _CornersPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = AppColors.goldBright
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    final frame = RRect.fromRectAndRadius(Offset.zero & size, const Radius.circular(20));
    const arm = 44.0;

    // Draw the rounded frame four times, each time showing only one corner of it.
    for (final corner in [
      Offset.zero,
      Offset(size.width - arm, 0),
      Offset(0, size.height - arm),
      Offset(size.width - arm, size.height - arm),
    ]) {
      canvas
        ..save()
        ..clipRect((corner & const Size.square(arm)).inflate(2))
        ..drawRRect(frame, paint)
        ..restore();
    }
  }

  @override
  bool shouldRepaint(_CornersPainter oldDelegate) => false;
}

class _MerchantPanel extends StatelessWidget {
  const _MerchantPanel({
    required this.merchants,
    required this.selected,
    required this.codeController,
    required this.onSelect,
    required this.onAdd,
    required this.onManualEntry,
  });

  final List<RecentMerchant> merchants;
  final RecentMerchant? selected;
  final TextEditingController codeController;
  final ValueChanged<RecentMerchant?> onSelect;
  final VoidCallback onAdd;
  final VoidCallback? onManualEntry;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    Widget chip(String label, RecentMerchant? merchant) {
      final isSelected = selected?.code == merchant?.code;
      return Padding(
        padding: const EdgeInsetsDirectional.only(end: AppSpacing.sm),
        child: ChoiceChip(
          label: Text(label),
          selected: isSelected,
          onSelected: (_) => onSelect(merchant),
          avatar: isSelected ? const Icon(Icons.check, size: 16, color: AppColors.whatsapp) : null,
          showCheckmark: false,
          backgroundColor: AppColors.surface,
          selectedColor: AppColors.surface,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.md)),
          side: BorderSide(
            color: isSelected ? theme.colorScheme.primary : theme.colorScheme.outlineVariant,
            width: isSelected ? 2 : 1,
          ),
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.md),
        ),
      );
    }

    return DecoratedBox(
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.sm),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      l10n.scanMerchantQuestion,
                      style: theme.textTheme.headlineMedium?.copyWith(fontSize: 18),
                    ),
                  ),
                  Text(l10n.scanOptional, style: theme.textTheme.bodySmall?.copyWith(color: muted)),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              SizedBox(
                height: 52,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    for (final merchant in merchants) chip(merchant.name, merchant),
                    chip(l10n.scanNoMerchant, null),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                controller: codeController,
                textCapitalization: TextCapitalization.characters,
                textInputAction: TextInputAction.done,
                onSubmitted: (_) => onAdd(),
                decoration: InputDecoration(
                  hintText: l10n.scanMerchantCodeHint,
                  contentPadding:
                      const EdgeInsetsDirectional.only(start: AppSpacing.lg, top: 14, bottom: 14),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppSpacing.radius),
                    borderSide: const BorderSide(color: AppColors.border),
                  ),
                  suffixIcon: Padding(
                    padding: const EdgeInsets.all(6),
                    child: FilledButton(
                      style: FilledButton.styleFrom(
                        minimumSize: const Size(72, 40),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(AppSpacing.md)),
                      ),
                      onPressed: onAdd,
                      child: Text(l10n.scanMerchantAdd),
                    ),
                  ),
                ),
              ),
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton(
                  style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                  onPressed: onManualEntry,
                  child: Text(l10n.scanManualEntry),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ManualCodeDialog extends StatefulWidget {
  const _ManualCodeDialog();

  @override
  State<_ManualCodeDialog> createState() => _ManualCodeDialogState();
}

class _ManualCodeDialogState extends State<_ManualCodeDialog> {
  final _code = TextEditingController();

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return AlertDialog(
      title: Text(l10n.scanManualEntry),
      content: TextField(
        controller: _code,
        autofocus: true,
        textDirection: TextDirection.ltr,
        textCapitalization: TextCapitalization.characters,
        inputFormatters: [LengthLimitingTextInputFormatter(32)],
        onSubmitted: (value) => Navigator.pop(context, value),
        decoration: InputDecoration(hintText: l10n.scanManualHint),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: Text(l10n.cancel)),
        TextButton(
          onPressed: () => Navigator.pop(context, _code.text),
          child: Text(l10n.scanManualSubmit),
        ),
      ],
    );
  }
}

/// Outcome of a claimed code (design: "09 نتيجة المسح").
class ScanResultScreen extends ConsumerWidget {
  const ScanResultScreen({
    super.key,
    required this.result,
    required this.onScanAnother,
    required this.onOpenWallet,
  });

  final ScanResult result;
  final VoidCallback onScanAnother;
  final VoidCallback onOpenWallet;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final wallet = ref.watch(walletProvider).value;

    Widget row(String label, Widget value) => Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              Expanded(child: Text(label, style: theme.textTheme.bodyLarge?.copyWith(color: muted))),
              value,
            ],
          ),
        );
    Widget points(int value) => Text(
          '$value ${l10n.pointsUnit}',
          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
        );

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) onOpenWallet();
      },
      child: Scaffold(
        body: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
              child: CustomScrollView(
                slivers: [
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: Padding(
                      padding: const EdgeInsets.all(AppSpacing.xl),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Spacer(),
                          const Center(
                            child: CircleAvatar(
                              radius: 32,
                              backgroundColor: AppColors.navy,
                              child: Icon(Icons.check, color: AppColors.goldBright, size: 28),
                            ),
                          ),
                          const SizedBox(height: AppSpacing.lg),
                          Text(
                            l10n.scanResultAdded,
                            textAlign: TextAlign.center,
                            style: theme.textTheme.bodyLarge?.copyWith(color: muted),
                          ),
                          Text.rich(
                            TextSpan(
                              // The sign belongs with the number in both languages.
                              text: '\u2066+${result.pointsAwarded}\u2069',
                              style: const TextStyle(
                                  fontSize: 40, fontWeight: FontWeight.w700, height: 1.3),
                              children: [
                                TextSpan(
                                  text: '  ${l10n.pointsUnit}',
                                  style: theme.textTheme.titleMedium,
                                ),
                              ],
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const Spacer(),
                          const SizedBox(height: AppSpacing.xl),
                          Card(
                            margin: EdgeInsets.zero,
                            child: Column(
                              children: [
                                row(l10n.scanResultBalance, points(result.newBalance)),
                                if (result.merchantName != null) ...[
                                  const Divider(height: 1, color: AppColors.border),
                                  row(
                                    l10n.scanResultMerchant,
                                    Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(result.merchantName!,
                                            style: theme.textTheme.titleMedium),
                                        if (result.merchantPoints > 0) ...[
                                          const SizedBox(width: AppSpacing.sm),
                                          _PointsBadge(points: result.merchantPoints),
                                        ],
                                      ],
                                    ),
                                  ),
                                ],
                                if (wallet?.nextRankName != null) ...[
                                  const Divider(height: 1, color: AppColors.border),
                                  row(
                                    l10n.scanResultToRank(wallet!.nextRankName!),
                                    points(wallet.pointsToNextRank),
                                  ),
                                ],
                              ],
                            ),
                          ),
                          const SizedBox(height: AppSpacing.lg),
                          FilledButton(onPressed: onScanAnother, child: Text(l10n.scanAnother)),
                          const SizedBox(height: AppSpacing.sm),
                          TextButton(onPressed: onOpenWallet, child: Text(l10n.scanOpenWallet)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _PointsBadge extends StatelessWidget {
  const _PointsBadge({required this.points});

  final int points;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.whatsapp.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(AppSpacing.sm),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
        child: Text(
          '+$points',
          textDirection: TextDirection.ltr,
          style: const TextStyle(color: AppColors.whatsapp, fontSize: 12, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}
