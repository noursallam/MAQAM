import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../domain/content.dart';
import 'chat_controller.dart';

/// Questions and answers with the support assistant (design: "25 المساعد").
class AssistantScreen extends StatefulWidget {
  const AssistantScreen({super.key, required this.repository});

  final ContentRepository repository;

  @override
  State<AssistantScreen> createState() => _AssistantScreenState();
}

class _AssistantScreenState extends State<AssistantScreen> {
  late final ChatController _controller = ChatController(widget.repository)
    ..addListener(_onChanged);
  final _input = TextEditingController();
  final _scroll = ScrollController();

  @override
  void dispose() {
    _controller.dispose();
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _onChanged() {
    setState(() {});
    // Keep the newest message in view.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent,
          duration: const Duration(milliseconds: 200),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _send(String text) async {
    if (text.trim().isEmpty || _controller.sending) return;
    _input.clear();
    final failure = await _controller.send(text);
    if (failure != null && mounted) {
      // Give the question back so it can be sent again.
      if (_input.text.isEmpty) _input.text = text.trim();
      showApiError(context, failure);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final messages = [
      ChatTurn(fromCustomer: false, text: l10n.assistantGreeting),
      ..._controller.messages,
    ];

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.assistantTitle, subtitle: l10n.assistantSubtitle),
      body: Column(
        children: [
          const Divider(height: 1, color: AppColors.border),
          Expanded(
            child: ListView.builder(
              controller: _scroll,
              padding: const EdgeInsets.all(AppSpacing.lg),
              itemCount: messages.length + (_controller.sending ? 1 : 0),
              itemBuilder: (context, i) => i < messages.length
                  ? _Bubble(turn: messages[i])
                  : const _TypingBubble(),
            ),
          ),
          _Composer(
            controller: _input,
            sending: _controller.sending,
            messagesLeft: _controller.messagesLeft,
            onSend: _send,
          ),
        ],
      ),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.turn});

  final ChatTurn turn;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final mine = turn.fromCustomer;

    return Align(
      // The assistant speaks from the start side, as in the design.
      alignment: mine ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.8),
        child: Container(
          margin: const EdgeInsets.only(bottom: AppSpacing.md),
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          decoration: BoxDecoration(
            color: mine ? AppColors.navy : AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: mine ? null : Border.all(color: AppColors.border),
          ),
          child: SelectableText(
            turn.text,
            style: theme.textTheme.bodyLarge?.copyWith(color: mine ? Colors.white : null),
          ),
        ),
      ),
    );
  }
}

class _TypingBubble extends StatelessWidget {
  const _TypingBubble();

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: AlignmentDirectional.centerStart,
      child: Semantics(
        label: context.l10n.assistantTyping,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              for (final alpha in [0.35, 0.6, 0.9])
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 2),
                  child: Icon(
                    Icons.circle,
                    size: 8,
                    color: AppColors.textSecondary.withValues(alpha: alpha),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Composer extends StatelessWidget {
  const _Composer({
    required this.controller,
    required this.sending,
    required this.messagesLeft,
    required this.onSend,
  });

  final TextEditingController controller;
  final bool sending;
  final int? messagesLeft;
  final ValueChanged<String> onSend;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final suggestions = [l10n.assistantSuggestPoints, l10n.assistantSuggestCancel, l10n.assistantSuggestReturns];

    return DecoratedBox(
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.sm),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    for (final suggestion in suggestions)
                      Padding(
                        padding: const EdgeInsetsDirectional.only(end: AppSpacing.sm),
                        child: ActionChip(
                          label: Text(suggestion),
                          onPressed: sending ? null : () => onSend(suggestion),
                          backgroundColor: AppColors.background,
                          shape: const StadiumBorder(),
                          side: const BorderSide(color: AppColors.border),
                        ),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: controller,
                      minLines: 1,
                      maxLines: 4,
                      maxLength: 1000,
                      textInputAction: TextInputAction.send,
                      onSubmitted: onSend,
                      decoration: InputDecoration(
                        hintText: l10n.assistantHint,
                        counterText: '',
                        fillColor: AppColors.background,
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(AppSpacing.radius),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  IconButton.filled(
                    tooltip: l10n.assistantSend,
                    style: IconButton.styleFrom(
                      minimumSize: const Size.square(AppSpacing.controlHeight),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppSpacing.radius)),
                    ),
                    onPressed: sending ? null : () => onSend(controller.text),
                    icon: const Icon(Icons.send_outlined),
                  ),
                ],
              ),
              if (messagesLeft != null) ...[
                const SizedBox(height: AppSpacing.xs),
                Text(
                  l10n.assistantMessagesLeft(messagesLeft!),
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
