import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/security/app_lock.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';

/// Dots showing how much of the PIN is typed, over a number pad.
///
/// Calls [onCompleted] with the digits once [pinLength] are in, then clears
/// itself when the parent bumps [resetToken] (for example after a wrong PIN).
class PinPad extends StatefulWidget {
  const PinPad({
    super.key,
    required this.onCompleted,
    this.enabled = true,
    this.onDark = false,
    this.resetToken = 0,
  });

  final ValueChanged<String> onCompleted;
  final bool enabled;

  /// True on the navy lock screen, false on the light setup screens.
  final bool onDark;
  final int resetToken;

  @override
  State<PinPad> createState() => _PinPadState();
}

class _PinPadState extends State<PinPad> {
  String _digits = '';

  @override
  void didUpdateWidget(PinPad oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.resetToken != oldWidget.resetToken) _digits = '';
  }

  void _press(String digit) {
    if (!widget.enabled || _digits.length >= pinLength) return;
    HapticFeedback.selectionClick();
    setState(() => _digits += digit);
    if (_digits.length == pinLength) widget.onCompleted(_digits);
  }

  void _delete() {
    if (!widget.enabled || _digits.isEmpty) return;
    setState(() => _digits = _digits.substring(0, _digits.length - 1));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final foreground = widget.onDark ? Colors.white : AppColors.navy;
    final keyFill = widget.onDark ? Colors.white.withValues(alpha: 0.08) : AppColors.surface;
    final keyBorder = widget.onDark ? Colors.transparent : AppColors.border;

    Widget key(String digit) => _Key(
          onPressed: widget.enabled ? () => _press(digit) : null,
          fill: keyFill,
          border: keyBorder,
          child: Text(
            digit,
            style: TextStyle(color: foreground, fontSize: 24, fontWeight: FontWeight.w500),
          ),
        );

    // Digits and their order are the same in both languages, as on a phone dialler.
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Semantics(
            label: l10n.pinProgress(_digits.length, pinLength),
            child: ExcludeSemantics(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  for (var i = 0; i < pinLength; i++)
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 120),
                      margin: const EdgeInsets.symmetric(horizontal: 8),
                      width: 14,
                      height: 14,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: i < _digits.length ? AppColors.goldBright : Colors.transparent,
                        border: Border.all(
                          color: i < _digits.length
                              ? AppColors.goldBright
                              : foreground.withValues(alpha: 0.4),
                          width: 1.5,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          for (final row in const [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
          ])
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  for (final digit in row)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
                      child: key(digit),
                    ),
                ],
              ),
            ),
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(width: _Key.size + AppSpacing.md * 2),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
                child: key('0'),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
                child: SizedBox.square(
                  dimension: _Key.size,
                  child: IconButton(
                    tooltip: l10n.pinDelete,
                    color: foreground,
                    onPressed: widget.enabled && _digits.isNotEmpty ? _delete : null,
                    icon: const Icon(Icons.backspace_outlined),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Key extends StatelessWidget {
  const _Key({required this.onPressed, required this.fill, required this.border, required this.child});

  static const double size = 68;

  final VoidCallback? onPressed;
  final Color fill;
  final Color border;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return SizedBox.square(
      dimension: size,
      child: Material(
        color: fill,
        shape: CircleBorder(side: BorderSide(color: border)),
        clipBehavior: Clip.antiAlias,
        child: InkWell(onTap: onPressed, child: Center(child: child)),
      ),
    );
  }
}
