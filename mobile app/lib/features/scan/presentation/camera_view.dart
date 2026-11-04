import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';

/// Builds the live camera that reports QR codes. A provider, so tests can
/// swap in a view that needs no camera.
typedef CameraViewBuilder = Widget Function({
  required ValueChanged<String> onCode,
  required bool torchOn,
  required bool paused,
});

final cameraViewBuilderProvider = Provider<CameraViewBuilder>(
  (ref) => ({required onCode, required torchOn, required paused}) =>
      _CameraView(onCode: onCode, torchOn: torchOn, paused: paused),
);

class _CameraView extends StatefulWidget {
  const _CameraView({required this.onCode, required this.torchOn, required this.paused});

  final ValueChanged<String> onCode;
  final bool torchOn;
  final bool paused;

  @override
  State<_CameraView> createState() => _CameraViewState();
}

class _CameraViewState extends State<_CameraView> {
  final _controller = MobileScannerController(formats: const [BarcodeFormat.qrCode]);

  @override
  void didUpdateWidget(_CameraView oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.torchOn != oldWidget.torchOn) _controller.toggleTorch();
    if (widget.paused != oldWidget.paused) {
      widget.paused ? _controller.pause() : _controller.start();
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MobileScanner(
      controller: _controller,
      onDetect: (capture) {
        final value = capture.barcodes.firstOrNull?.rawValue;
        if (value != null && value.trim().isNotEmpty) widget.onCode(value);
      },
      // No camera, or permission refused: typing the code still works.
      errorBuilder: (context, error) => ColoredBox(
        color: AppColors.navy,
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: Text(
              context.l10n.scanCameraUnavailable,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.onNavyMuted),
            ),
          ),
        ),
      ),
    );
  }
}
