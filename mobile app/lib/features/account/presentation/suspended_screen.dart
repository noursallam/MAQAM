import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/blocking_notice.dart';
import '../../../l10n/l10n.dart';
import '../../content/content_providers.dart';

/// Shown while the account is frozen for review (design: "06 حساب موقوف").
class SuspendedScreen extends ConsumerWidget {
  const SuspendedScreen({super.key, required this.onSignOut});

  final VoidCallback onSignOut;

  Future<void> _contactSupport(BuildContext context, WidgetRef ref) async {
    final l10n = context.l10n;
    String? number;
    try {
      number = await ref.read(supportWhatsappProvider.future);
    } on Exception {
      number = null;
    }
    var opened = false;
    if (number != null) {
      try {
        opened = await launchUrl(
          Uri.https('wa.me', '/$number', {'text': l10n.suspendedSupportMessage}),
          mode: LaunchMode.externalApplication,
        );
      } on Exception {
        opened = false;
      }
    }
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.suspendedSupportFailed)));
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;

    return PopScope(
      canPop: false,
      child: BlockingNotice(
        icon: Icons.lock_outline,
        iconColor: AppColors.error,
        iconBackground: AppColors.errorSoft,
        title: l10n.suspendedTitle,
        body: l10n.suspendedBody,
        actionLabel: l10n.suspendedContact,
        onAction: () => _contactSupport(context, ref),
        secondaryLabel: l10n.signOut,
        onSecondary: onSignOut,
      ),
    );
  }
}
