import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/maqam_logo.dart';
import '../../../l10n/l10n.dart';

const _pageTurn = Duration(milliseconds: 350);

/// First-launch tour of what the app does, in five slides.
class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key, required this.onStart, required this.onBrowseAsGuest});

  /// Continue to sign-in (the last slide's button, and "skip").
  final VoidCallback onStart;
  final VoidCallback onBrowseAsGuest;

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _pages = PageController();
  int _index = 0;

  // Where the buttons are heading. Tracked apart from [_index] so quick taps
  // during a page turn each count, instead of re-requesting the same page.
  int _target = 0;

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  List<_Slide> _slides(AppLocalizations l10n) => [
        _Slide(
          icon: Icons.storefront_outlined,
          title: l10n.onboarding1Title,
          body: l10n.onboarding1Body,
          chipA: (Icons.shopping_bag_outlined, l10n.onboarding1ChipA),
          chipB: (Icons.local_shipping_outlined, l10n.onboarding1ChipB),
        ),
        _Slide(
          icon: Icons.qr_code_scanner,
          title: l10n.onboarding2Title,
          body: l10n.onboarding2Body,
          chipA: (Icons.qr_code_2, l10n.onboarding2ChipA),
          chipB: (Icons.bolt_outlined, l10n.onboarding2ChipB),
        ),
        _Slide(
          icon: Icons.workspace_premium_outlined,
          title: l10n.onboarding3Title,
          body: l10n.onboarding3Body,
          chipA: (Icons.trending_up, l10n.onboarding3ChipA),
          chipB: (Icons.star_outline, l10n.onboarding3ChipB),
        ),
        _Slide(
          icon: Icons.card_giftcard,
          title: l10n.onboarding4Title,
          body: l10n.onboarding4Body,
          chipA: (Icons.sell_outlined, l10n.onboarding4ChipA),
          chipB: (Icons.redeem, l10n.onboarding4ChipB),
        ),
        _Slide(
          icon: Icons.fingerprint,
          title: l10n.onboarding5Title,
          body: l10n.onboarding5Body,
          chipA: (Icons.chat_bubble_outline, l10n.onboarding5ChipA),
          chipB: (Icons.face_unlock_outlined, l10n.onboarding5ChipB),
        ),
      ];

  void _next(int count) {
    if (_index == count - 1) {
      widget.onStart();
      return;
    }
    _target = math.min(math.max(_target, _index) + 1, count - 1);
    if (MediaQuery.disableAnimationsOf(context)) {
      _pages.jumpToPage(_target);
    } else {
      _pages.animateToPage(_target, duration: _pageTurn, curve: Curves.easeOutCubic);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final slides = _slides(l10n);
    final isLast = _index == slides.length - 1;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Column(
          children: [
            // --- Navy top half with illustration ---
            Expanded(
              flex: 55,
              child: DecoratedBox(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFF1D2436), AppColors.navy],
                    stops: [0, 0.7],
                  ),
                ),
                child: SafeArea(
                  bottom: false,
                  child: Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
                      child: Column(
                        children: [
                          Padding(
                            padding: const EdgeInsetsDirectional.only(
                              start: AppSpacing.xl,
                              end: AppSpacing.md,
                              top: AppSpacing.sm,
                            ),
                            child: SizedBox(
                              height: 48,
                              child: Row(
                                children: [
                                  const MaqamLogo(height: 16, color: Colors.white),
                                  const Spacer(),
                                  Visibility(
                                    visible: !isLast,
                                    maintainSize: true,
                                    maintainAnimation: true,
                                    maintainState: true,
                                    child: TextButton(
                                      style: TextButton.styleFrom(foregroundColor: AppColors.onNavyMuted),
                                      onPressed: widget.onStart,
                                      child: Text(l10n.skip),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          Expanded(
                            child: NotificationListener<ScrollEndNotification>(
                              onNotification: (_) {
                                _target = _index;
                                return false;
                              },
                              child: PageView.builder(
                                controller: _pages,
                                itemCount: slides.length,
                                onPageChanged: (index) => setState(() => _index = index),
                                itemBuilder: (context, i) => _SlideArt(slide: slides[i], active: i == _index),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
            // --- Curved white bottom half ---
            Expanded(
              flex: 45,
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  // The curve that overlaps the navy section.
                  Positioned(
                    top: -40,
                    left: 0,
                    right: 0,
                    child: CustomPaint(
                      size: const Size(double.infinity, 40),
                      painter: _CurvePainter(color: AppColors.background),
                    ),
                  ),
                  // White content area.
                  ColoredBox(
                    color: AppColors.background,
                    child: SafeArea(
                      top: false,
                      child: Center(
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(maxWidth: AppSpacing.maxContentWidth),
                          child: CustomScrollView(
                            slivers: [
                              SliverFillRemaining(
                                hasScrollBody: false,
                                child: Padding(
                                  padding: const EdgeInsets.fromLTRB(
                                      AppSpacing.xl, AppSpacing.sm, AppSpacing.xl, AppSpacing.lg),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.stretch,
                                    children: [
                                      _PageDots(count: slides.length, index: _index, dark: true),
                                      const SizedBox(height: AppSpacing.lg),
                                      Text(
                                        slides[_index].title,
                                        style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                                          color: AppColors.navy,
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                                      const SizedBox(height: AppSpacing.sm),
                                      Expanded(
                                        child: Text(
                                          slides[_index].body,
                                          style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                            color: AppColors.textSecondary,
                                          ),
                                        ),
                                      ),
                                      FilledButton(
                                        style: FilledButton.styleFrom(
                                          backgroundColor: AppColors.navy,
                                          foregroundColor: Colors.white,
                                        ),
                                        onPressed: () => _next(slides.length),
                                        child: Text(isLast ? l10n.onboardingStart : l10n.onboardingNext),
                                      ),
                                      const SizedBox(height: AppSpacing.sm),
                                      TextButton(
                                        style: TextButton.styleFrom(foregroundColor: AppColors.goldDeep),
                                        onPressed: widget.onBrowseAsGuest,
                                        child: Text(l10n.onboardingGuest),
                                      ),
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
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Slide {
  const _Slide({
    required this.icon,
    required this.title,
    required this.body,
    required this.chipA,
    required this.chipB,
  });

  final IconData icon;
  final String title;
  final String body;
  final (IconData, String) chipA;
  final (IconData, String) chipB;
}

class _SlideArt extends StatelessWidget {
  const _SlideArt({required this.slide, required this.active});

  final _Slide slide;
  final bool active;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
      child: _FeatureArt(slide: slide, active: active),
    );
  }
}

/// The illustration for a slide: the brand's glowing arch around the feature's
/// icon, with two labels that float in.
class _FeatureArt extends StatelessWidget {
  const _FeatureArt({required this.slide, required this.active});

  final _Slide slide;
  final bool active;

  @override
  Widget build(BuildContext context) {
    final reduceMotion = MediaQuery.disableAnimationsOf(context);

    // Only positioned children, so the art has no height of its own and is the
    // first thing to give way on a short screen.
    return Stack(
      children: [
        Positioned.fill(
          child: LayoutBuilder(
            builder: (context, constraints) {
              final size = math.min(math.min(constraints.maxWidth, constraints.maxHeight), 320.0);
              if (size < 180) return const SizedBox.shrink();

              return Center(
                child: ExcludeSemantics(
                  child: SizedBox.square(
                    dimension: size,
                    child: TweenAnimationBuilder<double>(
                      key: ValueKey(active),
                      tween: Tween(begin: active && !reduceMotion ? 0 : 1, end: 1),
                      duration: const Duration(milliseconds: 500),
                      curve: Curves.easeOutCubic,
                      builder: (context, t, _) => _ArtLayers(slide: slide, size: size, t: t),
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _ArtLayers extends StatelessWidget {
  const _ArtLayers({required this.slide, required this.size, required this.t});

  final _Slide slide;
  final double size;

  /// Entrance progress, 0 to 1.
  final double t;

  @override
  Widget build(BuildContext context) {
    final tile = size * 0.34;

    return Stack(
      clipBehavior: Clip.none,
      children: [
        // Soft gold light behind everything.
        Positioned.fill(
          child: DecoratedBox(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: RadialGradient(
                colors: [AppColors.gold.withValues(alpha: 0.22), AppColors.gold.withValues(alpha: 0)],
              ),
            ),
          ),
        ),
        Positioned.fill(child: CustomPaint(painter: _ArchPainter(progress: t))),
        Center(
          child: Transform.scale(
            scale: 0.9 + 0.1 * t,
            child: Container(
              width: tile,
              height: tile,
              decoration: BoxDecoration(
                color: const Color(0xFF222A3D),
                borderRadius: BorderRadius.circular(tile * 0.28),
                border: Border.all(color: AppColors.goldBright.withValues(alpha: 0.7)),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.gold.withValues(alpha: 0.25),
                    blurRadius: 32,
                    spreadRadius: 2,
                  ),
                ],
              ),
              child: Icon(slide.icon, color: AppColors.goldBright, size: tile * 0.46),
            ),
          ),
        ),
        PositionedDirectional(
          top: size * 0.16,
          start: 0,
          child: _FloatingChip(chip: slide.chipA, t: t, from: const Offset(0, 16)),
        ),
        PositionedDirectional(
          bottom: size * 0.14,
          end: 0,
          child: _FloatingChip(chip: slide.chipB, t: t, from: const Offset(0, -16)),
        ),
      ],
    );
  }
}

class _FloatingChip extends StatelessWidget {
  const _FloatingChip({required this.chip, required this.t, required this.from});

  final (IconData, String) chip;
  final double t;
  final Offset from;

  @override
  Widget build(BuildContext context) {
    final (icon, label) = chip;

    return Opacity(
      opacity: t,
      child: Transform.translate(
        offset: from * (1 - t),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: const Color(0xFF262E42),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
            boxShadow: const [
              BoxShadow(color: Color(0x33000000), blurRadius: 16, offset: Offset(0, 8)),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 16, color: AppColors.goldBright),
                const SizedBox(width: AppSpacing.sm),
                Text(
                  label,
                  style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// The arch from MAQAM's product photography, drawn as a glowing gold line.
class _ArchPainter extends CustomPainter {
  const _ArchPainter({required this.progress});

  /// How much of the line is drawn, 0 to 1.
  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final width = size.width * 0.56;
    final left = (size.width - width) / 2;
    final top = size.height * 0.08;
    final bottom = size.height * 0.92;
    final radius = width / 2;

    final arch = Path()
      ..moveTo(left, bottom)
      ..lineTo(left, top + radius)
      ..arcToPoint(Offset(left + width, top + radius), radius: Radius.circular(radius))
      ..lineTo(left + width, bottom);

    final metric = arch.computeMetrics().first;
    final drawn = metric.extractPath(0, metric.length * progress);
    final shader = const LinearGradient(
      begin: Alignment.topCenter,
      end: Alignment.bottomCenter,
      colors: [AppColors.goldBright, Color(0x00DCB479)],
      stops: [0.35, 1],
    ).createShader(Offset.zero & size);

    canvas
      ..drawPath(
        drawn,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 6
          ..shader = shader
          ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 8),
      )
      ..drawPath(
        drawn,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 1.5
          ..strokeCap = StrokeCap.round
          ..shader = shader,
      );
  }

  @override
  bool shouldRepaint(_ArchPainter oldDelegate) => oldDelegate.progress != progress;
}

class _CurvePainter extends CustomPainter {
  const _CurvePainter({required this.color});

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final path = Path()
      ..moveTo(0, size.height)
      ..quadraticBezierTo(size.width / 2, 0, size.width, size.height)
      ..lineTo(size.width, size.height)
      ..close();
    canvas.drawPath(path, Paint()..color = color);
  }

  @override
  bool shouldRepaint(_CurvePainter old) => old.color != color;
}

class _PageDots extends StatelessWidget {
  const _PageDots({required this.count, required this.index, this.dark = false});

  final int count;
  final int index;
  final bool dark;

  @override
  Widget build(BuildContext context) {
    final activeColor = dark ? AppColors.navy : AppColors.goldBright;
    final inactiveColor = dark
        ? AppColors.navy.withValues(alpha: 0.2)
        : Colors.white.withValues(alpha: 0.25);

    return Semantics(
      label: context.l10n.onboardingPage(index + 1, count),
      child: Row(
        children: [
          for (var i = 0; i < count; i++)
            AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              curve: Curves.easeOut,
              margin: const EdgeInsetsDirectional.only(end: 6),
              width: i == index ? 28 : 8,
              height: 4,
              decoration: BoxDecoration(
                color: i == index ? activeColor : inactiveColor,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
        ],
      ),
    );
  }
}
