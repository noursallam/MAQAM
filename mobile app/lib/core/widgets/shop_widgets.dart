import 'package:flutter/material.dart';
import 'package:intl/intl.dart' show DateFormat;

import '../../l10n/l10n.dart';
import '../network/api_error_message.dart';
import '../network/api_exception.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';

/// A short date ("15 سبتمبر") in the app language, or "Today" / "Yesterday".
///
/// Arabic dates come out of the formatter in Arabic-Indic digits; the app
/// shows Western digits everywhere (prices, points, phone numbers), so they
/// are converted to match.
String dayLabel(BuildContext context, DateTime date, {bool withYear = false}) {
  final l10n = context.l10n;
  final now = DateTime.now();
  final days = DateTime(now.year, now.month, now.day)
      .difference(DateTime(date.year, date.month, date.day))
      .inDays;
  if (days == 0) return l10n.today;
  if (days == 1) return l10n.yesterday;

  final locale = Localizations.localeOf(context).languageCode;
  final text = (withYear ? DateFormat.yMMMd(locale) : DateFormat.MMMd(locale)).format(date);
  return text.replaceAllMapped(
    RegExp('[\u0660-\u0669]'),
    (match) => String.fromCharCode(match[0]!.codeUnitAt(0) - 0x0660 + 0x30),
  );
}

/// How long ago something happened: "Now", minutes, hours, then the day.
String timeAgoLabel(BuildContext context, DateTime time) {
  final l10n = context.l10n;
  final elapsed = DateTime.now().difference(time);
  if (elapsed.inMinutes < 1) return l10n.timeNow;
  if (elapsed.inMinutes < 60) return l10n.timeMinutesAgo(elapsed.inMinutes);
  if (elapsed.inHours < 24 && DateTime.now().day == time.day) return l10n.timeHoursAgo(elapsed.inHours);
  return dayLabel(context, time);
}

/// `3821` -> "3,821".
String formatPoints(int value) =>
    value.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => ',');

/// `75` -> "75.00 ج.م".
String formatEgp(BuildContext context, double amount) =>
    context.l10n.priceEgp(amount.toStringAsFixed(2));

/// Shows why a request failed, for actions that have no inline place for it.
void showApiError(BuildContext context, ApiException error) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(apiErrorMessage(context, error))));
}

/// A minus / count / plus control.
class QuantityStepper extends StatelessWidget {
  const QuantityStepper({
    super.key,
    required this.quantity,
    required this.onChanged,
    this.min = 1,
    this.max = 999,
    this.compact = false,
  });

  final int quantity;

  /// Null disables both buttons, for example while a change is being saved.
  final ValueChanged<int>? onChanged;
  final int min;
  final int max;

  /// The smaller size used inside cart rows.
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final height = compact ? 40.0 : AppSpacing.controlHeight;

    Widget button(IconData icon, String tooltip, int next, bool allowed) => IconButton(
          tooltip: tooltip,
          visualDensity: VisualDensity.compact,
          constraints: BoxConstraints(minWidth: compact ? 40 : 48, minHeight: height),
          iconSize: 18,
          onPressed: onChanged != null && allowed ? () => onChanged!(next) : null,
          icon: Icon(icon),
        );

    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(compact ? AppSpacing.md : AppSpacing.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        // Plus on the right and minus on the left in both languages, as on a number line.
        textDirection: TextDirection.rtl,
        children: [
          button(Icons.add, l10n.quantityIncrease, quantity + 1, quantity < max),
          ConstrainedBox(
            constraints: const BoxConstraints(minWidth: 24),
            child: Text(
              '$quantity',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
          ),
          button(Icons.remove, l10n.quantityDecrease, quantity - 1, quantity > min),
        ],
      ),
    );
  }
}

/// Subtotal, discount, shipping and total, as rows.
class OrderSummary extends StatelessWidget {
  const OrderSummary({
    super.key,
    required this.subtotalLabel,
    required this.subtotal,
    required this.discount,
    required this.shipping,
    required this.total,
  });

  final String subtotalLabel;
  final double subtotal;
  final double discount;
  final double shipping;
  final double total;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant);

    Widget row(String label, String value, {TextStyle? labelStyle, TextStyle? valueStyle}) =>
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 2),
          child: Row(
            children: [
              Expanded(child: Text(label, style: labelStyle ?? muted)),
              Text(value, style: valueStyle ?? theme.textTheme.bodyMedium),
            ],
          ),
        );

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        row(subtotalLabel, formatEgp(context, subtotal)),
        if (discount > 0)
          row(
            l10n.summaryDiscount,
            '- ${formatEgp(context, discount)}',
            valueStyle: theme.textTheme.bodyMedium?.copyWith(color: AppColors.whatsapp),
          ),
        row(l10n.summaryShipping, formatEgp(context, shipping)),
        const Divider(height: AppSpacing.lg, color: AppColors.border),
        row(
          l10n.summaryTotal,
          formatEgp(context, total),
          labelStyle: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
          valueStyle: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
        ),
      ],
    );
  }
}

/// A square product thumbnail with a neutral placeholder.
class ProductThumbnail extends StatelessWidget {
  const ProductThumbnail({super.key, required this.url, this.size = 56});

  final String? url;
  final double size;

  @override
  Widget build(BuildContext context) {
    const placeholder = ColoredBox(
      color: AppColors.surfaceMuted,
      child: Center(child: Icon(Icons.image_outlined, size: 20, color: AppColors.textSecondary)),
    );

    return ClipRRect(
      borderRadius: BorderRadius.circular(AppSpacing.md),
      child: SizedBox.square(
        dimension: size,
        child: url == null
            ? placeholder
            : Image.network(
                url!,
                fit: BoxFit.cover,
                excludeFromSemantics: true,
                errorBuilder: (_, _, _) => placeholder,
              ),
      ),
    );
  }
}
