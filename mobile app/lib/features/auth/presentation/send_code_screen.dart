import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/l10n.dart';
import '../domain/auth_repository.dart';
import '../domain/egyptian_phone.dart';
import '../domain/whatsapp_challenge.dart';
import 'send_code_controller.dart';
import 'widgets/auth_step_scaffold.dart';

typedef UrlOpener = Future<bool> Function(Uri url);

Future<bool> _openExternally(Uri url) => launchUrl(url, mode: LaunchMode.externalApplication);

/// Step 2 of sign-in (design: "03 إرسال الكود").
class SendCodeScreen extends StatefulWidget {
  const SendCodeScreen({
    super.key,
    required this.repository,
    required this.phone,
    this.purpose = SignInPurpose.signIn,
    required this.onOtpSent,
    this.openUrl = _openExternally,
  });

  final AuthRepository repository;

  /// Normalized phone number (`01XXXXXXXXX`).
  final String phone;
  final SignInPurpose purpose;
  final ValueChanged<OtpTicket> onOtpSent;
  final UrlOpener openUrl;

  @override
  State<SendCodeScreen> createState() => _SendCodeScreenState();
}

class _SendCodeScreenState extends State<SendCodeScreen> {
  late final SendCodeController _controller;

  @override
  void initState() {
    super.initState();
    _controller = SendCodeController(widget.repository, widget.phone, purpose: widget.purpose)
      ..addListener(_onChanged);
    _controller.start();
  }

  bool _handedOff = false;

  void _onChanged() {
    final ticket = _controller.ticket;
    if (ticket == null || _handedOff) return;
    _handedOff = true;
    widget.onOtpSent(ticket);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _copyCode(String code) async {
    await Clipboard.setData(ClipboardData(text: code));
    if (mounted) _showMessage(context.l10n.sendCodeCopied);
  }

  Future<void> _openWhatsapp(Uri url) async {
    var opened = false;
    try {
      opened = await widget.openUrl(url);
    } on Exception {
      opened = false;
    }
    if (!opened && mounted) _showMessage(context.l10n.sendCodeWhatsappMissing);
  }

  void _showMessage(String text) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(text)));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return ListenableBuilder(
      listenable: _controller,
      builder: (context, _) {
        final challenge = _controller.challenge;
        final waiting = _controller.status == SendCodeStatus.waiting && challenge != null;

        return AuthStepScaffold(
          trailing: const AuthStepLabel(step: 2),
          footer: waiting ? _ValidityFooter(validFor: challenge.validFor) : null,
          children: [
            AuthStepHeader(
              title: l10n.sendCodeTitle,
              subtitle: TextSpan(
                text: l10n.sendCodeSubtitlePrefix,
                children: [
                  TextSpan(
                    // A left-to-right isolate keeps the digit groups in order inside Arabic text.
                    text: '\u2066${formatEgyptianPhone(widget.phone)}\u2069',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  TextSpan(text: l10n.sendCodeSubtitleSuffix),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),
            switch (_controller.status) {
              SendCodeStatus.starting => const Padding(
                  padding: EdgeInsets.all(AppSpacing.xxl),
                  child: Center(child: CircularProgressIndicator()),
                ),
              SendCodeStatus.failed => _Problem(
                  message: apiErrorMessage(context, _controller.error!),
                  actionLabel: l10n.retry,
                  onAction: _controller.start,
                ),
              SendCodeStatus.expired => _Problem(
                  message: l10n.sendCodeExpired,
                  actionLabel: l10n.startOver,
                  onAction: _controller.start,
                ),
              SendCodeStatus.waiting => _Waiting(
                  challenge: challenge!,
                  onCopy: () => _copyCode(challenge.code),
                  onOpenWhatsapp: () => _openWhatsapp(challenge.whatsappUrl),
                ),
            },
          ],
        );
      },
    );
  }
}

class _Waiting extends StatelessWidget {
  const _Waiting({required this.challenge, required this.onCopy, required this.onOpenWhatsapp});

  final WhatsappChallenge challenge;
  final VoidCallback onCopy;
  final VoidCallback onOpenWhatsapp;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final steps = [l10n.sendCodeStep1, l10n.sendCodeStep2, l10n.sendCodeStep3];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _CodeCard(code: challenge.code, onCopy: onCopy),
        const SizedBox(height: AppSpacing.md),
        FilledButton.icon(
          style: FilledButton.styleFrom(backgroundColor: AppColors.whatsapp),
          onPressed: onOpenWhatsapp,
          icon: const Icon(Icons.chat_bubble_outline),
          label: Text(l10n.sendCodeOpenWhatsapp),
        ),
        const SizedBox(height: AppSpacing.xl),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const SizedBox.square(
              dimension: 16,
              child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.goldDeep),
            ),
            const SizedBox(width: AppSpacing.md),
            Flexible(
              child: Text(
                l10n.sendCodeWaiting,
                style: theme.textTheme.bodyMedium
                    ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.xxl),
        for (final (index, text) in steps.indexed)
          Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.lg),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 14,
                  backgroundColor: theme.colorScheme.primary,
                  foregroundColor: theme.colorScheme.onPrimary,
                  child: Text('${index + 1}', style: const TextStyle(fontSize: 13)),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(child: Text(text, style: theme.textTheme.bodyLarge)),
              ],
            ),
          ),
      ],
    );
  }
}

class _CodeCard extends StatelessWidget {
  const _CodeCard({required this.code, required this.onCopy});

  final String code;
  final VoidCallback onCopy;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.sendCodeLabel,
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  SelectableText(
                    code,
                    textDirection: TextDirection.ltr,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 1.5,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            IconButton.filledTonal(
              tooltip: l10n.sendCodeCopy,
              style: IconButton.styleFrom(
                minimumSize: const Size.square(48),
                backgroundColor: AppColors.background,
                foregroundColor: theme.colorScheme.onSurface,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AppSpacing.md),
                  side: const BorderSide(color: AppColors.border),
                ),
              ),
              onPressed: onCopy,
              icon: const Icon(Icons.copy_outlined, size: 20),
            ),
          ],
        ),
      ),
    );
  }
}

class _Problem extends StatelessWidget {
  const _Problem({required this.message, required this.actionLabel, required this.onAction});

  final String message;
  final String actionLabel;
  final VoidCallback onAction;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Semantics(
          liveRegion: true,
          child: Text(message, style: Theme.of(context).textTheme.bodyLarge),
        ),
        const SizedBox(height: AppSpacing.lg),
        FilledButton(onPressed: onAction, child: Text(actionLabel)),
      ],
    );
  }
}

class _ValidityFooter extends StatelessWidget {
  const _ValidityFooter({required this.validFor});

  final Duration validFor;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);

    return Wrap(
      alignment: WrapAlignment.center,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        Text(
          l10n.sendCodeValidFor(validFor.inMinutes),
          style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
        ),
        AuthTextLink(
          label: l10n.sendCodeChangeNumber,
          onPressed: () => Navigator.maybePop(context),
        ),
      ],
    );
  }
}
