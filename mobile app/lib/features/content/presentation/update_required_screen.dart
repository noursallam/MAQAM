import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/blocking_notice.dart';
import '../../../l10n/l10n.dart';
import '../domain/content.dart';

/// Stops an unsupported version until it is updated (design: "05 تحديث إجباري").
class UpdateRequiredScreen extends StatelessWidget {
  const UpdateRequiredScreen({super.key, required this.status});

  final AppUpdateStatus status;

  Future<void> _openStore(BuildContext context) async {
    final url = status.storeUrl;
    var opened = false;
    if (url != null) {
      try {
        opened = await launchUrl(url, mode: LaunchMode.externalApplication);
      } on Exception {
        opened = false;
      }
    }
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(context.l10n.updateStoreFailed)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return PopScope(
      canPop: false,
      child: BlockingNotice(
        icon: Icons.download,
        iconColor: AppColors.goldBright,
        iconBackground: AppColors.navy,
        title: l10n.updateTitle,
        body: l10n.updateBody,
        detail: l10n.updateVersions(status.currentVersion, status.minimumVersion),
        actionLabel: l10n.updateNow,
        onAction: () => _openStore(context),
      ),
    );
  }
}
