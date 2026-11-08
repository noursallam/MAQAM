import 'dart:math';

import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/wallet.dart';
import '../loyalty_providers.dart';

/// Spend points for a chance at a prize (design: "12 عجلة الحظ").
class WheelScreen extends ConsumerStatefulWidget {
  const WheelScreen({super.key, required this.onOpenRewards});

  final VoidCallback onOpenRewards;

  @override
  ConsumerState<WheelScreen> createState() => _WheelScreenState();
}

class _WheelScreenState extends ConsumerState<WheelScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _spin = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 4200),
  );
  late final TapGestureRecognizer _rewardsLink = TapGestureRecognizer()..onTap = widget.onOpenRewards;

  /// The wheel's turn in radians, between spins and at the end of the last one.
  double _angle = 0;
  Animation<double>? _turn;

  /// While spinning: the balance to show, so the prize is not given away early.
  int? _balanceDuringSpin;

  @override
  void dispose() {
    _spin.dispose();
    _rewardsLink.dispose();
    super.dispose();
  }

  bool get _spinning => _balanceDuringSpin != null;

  Future<void> _start(WheelConfig config) async {
    if (_spinning || !config.canSpin || config.slices.isEmpty) return;
    HapticFeedback.selectionClick();
    setState(() => _balanceDuringSpin = config.balance - config.cost);

    final SpinOutcome outcome;
    try {
      outcome = await ref.read(wheelProvider.notifier).spin();
    } on ApiException catch (e) {
      if (mounted) {
        setState(() => _balanceDuringSpin = null);
        showApiError(context, e);
      }
      return;
    }
    if (!mounted) return;

    // Land the middle of the winning slice under the pointer, after a few full turns.
    final slice = 2 * pi / config.slices.length;
    final index = outcome.sliceIndex.clamp(0, config.slices.length - 1);
    final rest = (-index * slice) % (2 * pi);
    final from = _angle;
    final to = from - (from % (2 * pi)) + 5 * 2 * pi + rest;
    _turn = Tween<double>(begin: from, end: to)
        .animate(CurvedAnimation(parent: _spin, curve: Curves.easeOutCubic));
    await _spin.forward(from: 0);
    if (!mounted) return;
    setState(() {
      _angle = to % (2 * pi);
      _turn = null;
      _balanceDuringSpin = null;
    });
    await _showOutcome(outcome);
  }

  Future<void> _showOutcome(SpinOutcome outcome) async {
    final l10n = context.l10n;
    final openRewards = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(outcome.won ? l10n.wheelWonTitle : l10n.wheelLostTitle),
        content: Text(
          outcome.won
              ? l10n.wheelWonBody(outcome.prizeLabel ?? '')
              : l10n.wheelLostBody,
        ),
        actions: [
          if (outcome.reward != null)
            TextButton(onPressed: () => Navigator.pop(context, true), child: Text(l10n.actionRewards)),
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(l10n.wheelOk)),
        ],
      ),
    );
    if (openRewards == true) widget.onOpenRewards();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final wheel = ref.watch(wheelProvider);
    final config = wheel.value;
    final balance = _balanceDuringSpin ?? config?.balance;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: AppColors.navy,
        body: SafeArea(
          child: Column(
            children: [
              _Header(balance: balance),
              Expanded(
                child: switch (wheel) {
                  AsyncData(:final value) => _WheelBody(
                      config: value,
                      angle: _turn ?? AlwaysStoppedAnimation(_angle),
                      spinning: _spinning,
                    ),
                  AsyncError(:final error) => Center(
                      child: Padding(
                        padding: const EdgeInsets.all(AppSpacing.lg),
                        child: Card(
                          child: MessageView(
                            title: l10n.wheelLoadFailed,
                            message: error is ApiException
                                ? apiErrorMessage(context, error)
                                : l10n.errorGeneric,
                            actionLabel: l10n.retry,
                            onAction: () => ref.invalidate(wheelProvider),
                          ),
                        ),
                      ),
                    ),
                  _ => const Center(child: CircularProgressIndicator(color: AppColors.goldBright)),
                },
              ),
              if (config != null)
                _Controls(
                  config: config,
                  spinning: _spinning,
                  rewardsLink: _rewardsLink,
                  onSpin: () => _start(config),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.balance});

  final int? balance;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
      child: Row(
        children: [
          IconButton(
            tooltip: l10n.back,
            color: Colors.white,
            onPressed: () => Navigator.maybePop(context),
            icon: const Icon(Icons.arrow_back),
          ),
          Expanded(
            child: Text(
              l10n.actionWheel,
              textAlign: TextAlign.center,
              style: textTheme.titleMedium?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 20),
            ),
          ),
          ConstrainedBox(
            constraints: const BoxConstraints(minWidth: 48),
            child: balance == null
                ? const SizedBox.shrink()
                : Text.rich(
                    TextSpan(
                      text: '${formatPoints(balance!)} ',
                      style: const TextStyle(color: AppColors.goldBright, fontWeight: FontWeight.w700),
                      children: [
                        TextSpan(
                          text: l10n.pointsUnit,
                          style: const TextStyle(color: AppColors.onNavyMuted, fontWeight: FontWeight.w400),
                        ),
                      ],
                    ),
                    style: textTheme.titleMedium,
                  ),
          ),
          const SizedBox(width: AppSpacing.md),
        ],
      ),
    );
  }
}

class _WheelBody extends StatelessWidget {
  const _WheelBody({required this.config, required this.angle, required this.spinning});

  final WheelConfig config;
  final Animation<double> angle;
  final bool spinning;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    // Chained and padlocked, as the designer drew it, while a spin is out of reach.
    final locked = !spinning && (!config.enabled || !config.canSpin || config.slices.isEmpty);

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Flexible(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 360, maxHeight: 360),
                child: AspectRatio(
                  aspectRatio: 1,
                  child: locked
                      ? Image.asset(
                          'assets/images/wheel_locked.png',
                          semanticLabel: l10n.wheelLocked,
                          filterQuality: FilterQuality.medium,
                        )
                      : Semantics(
                          label: l10n.wheelSemantics(
                            config.slices.map((s) => s.label).join(l10n.listSeparator),
                          ),
                          child: AnimatedBuilder(
                            animation: angle,
                            builder: (context, _) => CustomPaint(
                              painter: _WheelPainter(
                                slices: config.slices,
                                angle: angle.value,
                                textDirection: Directionality.of(context),
                                textStyle: Theme.of(context).textTheme.bodyMedium!,
                              ),
                            ),
                          ),
                        ),
                ),
              ),
            ),
            if (!config.enabled) ...[
              const SizedBox(height: AppSpacing.lg),
              Text(
                l10n.wheelDisabled,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: AppColors.onNavyMuted),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _Controls extends StatelessWidget {
  const _Controls({
    required this.config,
    required this.spinning,
    required this.rewardsLink,
    required this.onSpin,
  });

  final WheelConfig config;
  final bool spinning;
  final TapGestureRecognizer rewardsLink;
  final VoidCallback onSpin;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final textTheme = Theme.of(context).textTheme;
    final after = config.balance - config.cost;

    Widget figure(String label, String value) => Expanded(
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: AppColors.navyRaised,
              borderRadius: BorderRadius.circular(AppSpacing.radius),
            ),
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted)),
                  Text(
                    value,
                    style: textTheme.titleMedium?.copyWith(color: Colors.white, fontWeight: FontWeight.w700),
                  ),
                ],
              ),
            ),
          ),
        );

    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.lg),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              figure(l10n.wheelCost, l10n.pointsAmount(formatPoints(config.cost))),
              const SizedBox(width: AppSpacing.md),
              figure(
                l10n.wheelBalanceAfter,
                after >= 0 ? l10n.pointsAmount(formatPoints(after)) : '—',
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.goldBright,
              foregroundColor: AppColors.navy,
              disabledBackgroundColor: AppColors.navyRaised,
              disabledForegroundColor: AppColors.onNavyMuted,
              textStyle: AppTheme.buttonText(fontSize: 18, fontWeight: FontWeight.w700),
            ),
            onPressed: config.canSpin && !spinning ? onSpin : null,
            child: Text(spinning ? l10n.wheelSpinning : l10n.wheelSpin),
          ),
          const SizedBox(height: AppSpacing.md),
          if (config.enabled && !config.canSpin && !spinning)
            Text(
              l10n.wheelNotEnough(formatPoints(config.cost)),
              textAlign: TextAlign.center,
              style: textTheme.bodyMedium?.copyWith(color: AppColors.goldBright),
            )
          else
            Text.rich(
              TextSpan(
                text: l10n.wheelFooterPrefix,
                children: [
                  TextSpan(
                    text: l10n.actionRewards,
                    recognizer: rewardsLink,
                    style: const TextStyle(
                      color: AppColors.goldBright,
                      decoration: TextDecoration.underline,
                      decorationColor: AppColors.goldBright,
                    ),
                  ),
                  TextSpan(text: l10n.wheelFooterSuffix),
                ],
              ),
              textAlign: TextAlign.center,
              style: textTheme.bodyMedium?.copyWith(color: AppColors.onNavyMuted),
            ),
        ],
      ),
    );
  }
}

/// The wheel as the designer drew it ("عجلة الحظ.ai"): a navy rim between
/// gold edges with sixteen gold studs, the pointer tucked inside the rim at
/// the top, and slices that alternate white with navy, gold and a navy
/// diamond pattern. Proportions are fractions of the outer radius, measured
/// from the file.
class _WheelPainter extends CustomPainter {
  _WheelPainter({
    required this.slices,
    required this.angle,
    required this.textDirection,
    required this.textStyle,
  });

  final List<WheelSlice> slices;
  final double angle;
  final TextDirection textDirection;
  final TextStyle textStyle;

  static const _studs = 16;

  @override
  void paint(Canvas canvas, Size size) {
    final center = size.center(Offset.zero);
    final outer = size.shortestSide / 2;
    final rimInner = outer * 0.85;
    final faceRadius = outer * 0.83;

    _paintRim(canvas, center, outer, rimInner);
    _paintFace(canvas, center, faceRadius);
    _paintPointer(canvas, center, rimInner, outer);
    _paintHub(canvas, center, outer);
  }

  void _paintRim(Canvas canvas, Offset center, double outer, double rimInner) {
    final gold = SweepGradient(
      colors: const [
        AppColors.wheelEdgeLight,
        AppColors.wheelEdge,
        AppColors.wheelEdgeDark,
        AppColors.wheelEdge,
        AppColors.wheelEdgeLight,
      ],
      transform: const GradientRotation(-pi / 4),
    ).createShader(Rect.fromCircle(center: center, radius: outer));

    canvas.drawCircle(center, outer, Paint()..shader = gold);
    canvas.drawCircle(
      center,
      outer * 0.982,
      Paint()
        ..shader = const RadialGradient(
          colors: [AppColors.wheelRim, AppColors.wheelRimShade],
          stops: [0.85, 1],
        ).createShader(Rect.fromCircle(center: center, radius: outer)),
    );
    canvas.drawCircle(center, rimInner, Paint()..shader = gold);

    // The studs sit between the top two, so none hides behind the pointer.
    final stud = Paint()..color = AppColors.wheelStud;
    final shine = Paint()..color = AppColors.wheelEdgeLight;
    for (var i = 0; i < _studs; i++) {
      final a = -pi / 2 + (i + 0.5) * 2 * pi / _studs;
      final at = center + Offset(cos(a), sin(a)) * outer * 0.915;
      canvas.drawCircle(at, outer * 0.03, stud);
      canvas.drawCircle(at - Offset(outer, outer) * 0.007, outer * 0.012, shine);
    }
  }

  void _paintFace(Canvas canvas, Offset center, double radius) {
    final sweep = 2 * pi / slices.length;
    final bounds = Rect.fromCircle(center: center, radius: radius);
    final edge = Paint()
      ..color = AppColors.wheelEdge
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;

    for (var i = 0; i < slices.length; i++) {
      // Slice 0 is centred under the pointer when the wheel is at rest.
      final middle = -pi / 2 + i * sweep + angle;
      final style = _SliceStyle.of(i);
      final wedge = Path()
        ..moveTo(center.dx, center.dy)
        ..arcTo(bounds, middle - sweep / 2, sweep, false)
        ..close();

      canvas.drawPath(wedge, Paint()..shader = style.fill(bounds));
      if (style == _SliceStyle.pattern) _paintDiamonds(canvas, wedge, bounds);
      canvas.drawLine(
        center,
        center + Offset(cos(middle - sweep / 2), sin(middle - sweep / 2)) * radius,
        edge,
      );

      // Labels stay upright as the wheel turns, as in design 12.
      final label = TextPainter(
        text: TextSpan(
          text: slices[i].label,
          style: textStyle.copyWith(
            color: style.text,
            fontWeight: FontWeight.w600,
            fontSize: slices.length > 8 ? 12 : 14,
          ),
        ),
        textAlign: TextAlign.center,
        textDirection: textDirection,
        maxLines: 2,
        ellipsis: '…',
      )..layout(maxWidth: radius * 0.5);
      final at = center + Offset(cos(middle), sin(middle)) * radius * 0.62;
      label.paint(canvas, at - Offset(label.width / 2, label.height / 2));
    }
  }

  /// The small gold diamonds on the patterned slice.
  void _paintDiamonds(Canvas canvas, Path wedge, Rect bounds) {
    final line = Paint()
      ..color = AppColors.wheelEdge.withValues(alpha: 0.55)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 0.8;
    final step = bounds.width / 11;
    final half = step * 0.28;
    canvas.save();
    canvas.clipPath(wedge);
    for (var row = 0; row * step < bounds.height + step; row++) {
      for (var col = 0; col * step < bounds.width + step; col++) {
        final c = bounds.topLeft + Offset(col * step + (row.isOdd ? step / 2 : 0), row * step);
        canvas.drawPath(
          Path()
            ..moveTo(c.dx, c.dy - half)
            ..lineTo(c.dx + half, c.dy)
            ..lineTo(c.dx, c.dy + half)
            ..lineTo(c.dx - half, c.dy)
            ..close(),
          line,
        );
        canvas.drawLine(Offset(c.dx - half / 2, c.dy - half / 2), Offset(c.dx + half / 2, c.dy + half / 2), line);
      }
    }
    canvas.restore();
  }

  void _paintPointer(Canvas canvas, Offset center, double rimInner, double outer) {
    final top = center.dy - rimInner + outer * 0.005;
    final width = outer * 0.15;
    final pointer = Path()
      ..moveTo(center.dx - width / 2, top)
      ..lineTo(center.dx + width / 2, top)
      ..lineTo(center.dx, top + outer * 0.13)
      ..close();
    canvas.drawShadow(pointer, AppColors.navyDeep, 3, false);
    canvas.drawPath(pointer, Paint()..color = AppColors.wheelPointer);
  }

  void _paintHub(Canvas canvas, Offset center, double outer) {
    canvas.drawCircle(center, outer * 0.085, Paint()..color = AppColors.wheelEdge);
    canvas.drawCircle(center, outer * 0.072, Paint()..color = AppColors.navyDeep);
  }

  @override
  bool shouldRepaint(_WheelPainter old) =>
      old.angle != angle || old.slices != slices || old.textDirection != textDirection;
}

/// The light slices are white; the dark ones take turns being navy, gold,
/// navy and patterned, as in the file.
enum _SliceStyle {
  white(AppColors.navy),
  navy(AppColors.goldBright),
  gold(AppColors.navy),
  pattern(AppColors.goldBright);

  const _SliceStyle(this.text);

  final Color text;

  static _SliceStyle of(int index) => index.isEven
      ? white
      : const [navy, gold, navy, pattern][(index ~/ 2) % 4];

  Shader fill(Rect bounds) => switch (this) {
        white => const LinearGradient(colors: [AppColors.surface, AppColors.surface]).createShader(bounds),
        gold => const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.wheelGoldSliceLight, AppColors.wheelGoldSlice],
          ).createShader(bounds),
        navy || pattern => const RadialGradient(
            colors: [AppColors.wheelSliceShade, AppColors.wheelSlice],
          ).createShader(bounds),
      };
}
