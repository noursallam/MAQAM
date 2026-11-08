import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../l10n/l10n.dart';
import '../content_providers.dart';
import '../domain/content.dart';

/// The terms and the privacy policy, as the website publishes them.
class LegalScreen extends ConsumerStatefulWidget {
  const LegalScreen({super.key, this.initial = LegalPageSlug.terms});

  final LegalPageSlug initial;

  @override
  ConsumerState<LegalScreen> createState() => _LegalScreenState();
}

class _LegalScreenState extends ConsumerState<LegalScreen> {
  late LegalPageSlug _slug = widget.initial;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final page = ref.watch(legalPageProvider(_slug));

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.accountLegal),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: [
            SegmentedTabs<LegalPageSlug>(
              tabs: [(LegalPageSlug.terms, l10n.legalTerms), (LegalPageSlug.privacy, l10n.legalPrivacy)],
              selected: _slug,
              onSelected: (slug) => setState(() => _slug = slug),
            ),
            const SizedBox(height: AppSpacing.lg),
            AsyncView<LegalPage>(
              value: page,
              errorTitle: l10n.legalLoadFailed,
              onRetry: () => ref.invalidate(legalPageProvider(_slug)),
              data: (page) => _LegalBody(page: page),
            ),
          ],
        ),
      ),
    );
  }
}

class _LegalBody extends StatelessWidget {
  const _LegalBody({required this.page});

  final LegalPage page;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(page.title, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 22)),
        if (page.lead.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.xs),
          Text(page.lead, style: theme.textTheme.bodyLarge?.copyWith(color: muted)),
        ],
        const SizedBox(height: AppSpacing.md),
        for (final block in page.blocks)
          Padding(
            padding: EdgeInsets.only(
              top: block.kind == LegalBlockKind.heading ? AppSpacing.lg : AppSpacing.xs,
            ),
            child: switch (block.kind) {
              LegalBlockKind.heading => Text(
                  block.text,
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
              LegalBlockKind.paragraph => Text(block.text, style: theme.textTheme.bodyLarge),
              LegalBlockKind.bullet => Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('•  ', style: theme.textTheme.bodyLarge?.copyWith(color: muted)),
                    Expanded(child: Text(block.text, style: theme.textTheme.bodyLarge)),
                  ],
                ),
            },
          ),
      ],
    );
  }
}
