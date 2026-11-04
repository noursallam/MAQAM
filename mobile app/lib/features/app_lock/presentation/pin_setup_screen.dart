import 'package:flutter/material.dart';

import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import 'pin_pad.dart';

/// Choosing the app PIN: type it, then type it again to confirm.
class PinSetupScreen extends StatefulWidget {
  const PinSetupScreen({super.key, required this.onSave});

  /// Stores the PIN and moves on.
  final Future<void> Function(String pin) onSave;

  @override
  State<PinSetupScreen> createState() => _PinSetupScreenState();
}

class _PinSetupScreenState extends State<PinSetupScreen> {
  /// The first entry, while the customer is typing it a second time.
  String? _first;
  bool _mismatch = false;
  bool _saving = false;
  int _resetToken = 0;

  Future<void> _entered(String pin) async {
    final first = _first;
    if (first == null) {
      setState(() {
        _first = pin;
        _mismatch = false;
        _resetToken++;
      });
      return;
    }
    if (pin != first) {
      // Start over, so a slip on either entry cannot lock them out.
      setState(() {
        _first = null;
        _mismatch = true;
        _resetToken++;
      });
      return;
    }
    setState(() => _saving = true);
    await widget.onSave(pin);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final confirming = _first != null;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          tooltip: l10n.back,
          onPressed: _saving ? null : () => Navigator.maybePop(context),
          icon: const Icon(Icons.arrow_back),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Column(
              children: [
                Text(
                  confirming ? l10n.pinConfirmTitle : l10n.pinSetupTitle,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22),
                ),
                const SizedBox(height: AppSpacing.sm),
                Semantics(
                  liveRegion: _mismatch,
                  child: Text(
                    _mismatch
                        ? l10n.pinMismatch
                        : confirming
                            ? l10n.pinConfirmBody
                            : l10n.pinSetupBody,
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyLarge?.copyWith(
                      color: _mismatch ? theme.colorScheme.error : theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.xxl),
                PinPad(onCompleted: _entered, enabled: !_saving, resetToken: _resetToken),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
