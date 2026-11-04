import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/session/session_provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../loyalty/loyalty_providers.dart';
import '../cart_providers.dart';
import '../domain/cart.dart';

/// Address, payment and confirmation (design: "16 إتمام الطلب").
class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key, required this.onPlaced});

  final ValueChanged<PlacedOrder> onPlaced;

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _governorate = TextEditingController();
  final _city = TextEditingController();
  final _street = TextEditingController();
  final _notes = TextEditingController();

  PaymentMethod _payment = PaymentMethod.cashOnDelivery;

  /// True once the customer chose to type an address instead of the saved one.
  bool _typeNewAddress = false;
  bool _prefilled = false;
  bool _placing = false;
  ApiException? _failure;

  @override
  void dispose() {
    for (final c in [_name, _phone, _governorate, _city, _street, _notes]) {
      c.dispose();
    }
    super.dispose();
  }

  /// Starts the new-address form from what the account already knows.
  void _prefill() {
    if (_prefilled) return;
    _prefilled = true;
    final session = ref.read(sessionProvider);
    _name.text = session?.fullName ?? '';
    _phone.text = session?.phone ?? '';
  }

  Future<void> _place(Address? saved) async {
    if (_placing) return;
    final useSaved = saved != null && !_typeNewAddress;
    if (!useSaved && !(_form.currentState?.validate() ?? false)) return;

    FocusScope.of(context).unfocus();
    setState(() {
      _placing = true;
      _failure = null;
    });
    try {
      final order = await ref.read(cartProvider.notifier).checkout(
            addressId: useSaved ? saved.id : null,
            newAddress: useSaved
                ? null
                : NewAddress(
                    recipientName: _name.text.trim(),
                    phone: _phone.text.trim(),
                    governorate: _governorate.text.trim(),
                    city: _city.text.trim(),
                    street: _street.text.trim(),
                  ),
            payment: _payment,
            notes: _notes.text.trim(),
          );
      // Points were spent or will be earned, and a typed address is now saved.
      ref.invalidate(walletProvider);
      if (mounted) widget.onPlaced(order);
    } on ApiException catch (e) {
      if (mounted) setState(() => _failure = e);
    } finally {
      if (mounted) setState(() => _placing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final cart = ref.watch(cartProvider).value;
    final addresses = ref.watch(addressesProvider);
    final points = ref.watch(walletProvider).value?.balance;

    return Scaffold(
      appBar: AppBar(
        centerTitle: false,
        title: Text(l10n.checkoutTitle, style: theme.textTheme.headlineMedium?.copyWith(fontSize: 20)),
      ),
      body: AsyncView<List<Address>>(
        value: addresses,
        onRetry: () => ref.invalidate(addressesProvider),
        data: (list) {
          final saved = list.isEmpty
              ? null
              : list.firstWhere((a) => a.isDefault, orElse: () => list.first);
          final showForm = saved == null || _typeNewAddress;
          if (showForm) _prefill();

          return ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              Row(
                children: [
                  Expanded(child: _SectionTitle(l10n.checkoutAddress)),
                  if (saved != null)
                    TextButton(
                      style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                      onPressed: _placing
                          ? null
                          : () => setState(() => _typeNewAddress = !_typeNewAddress),
                      child: Text(_typeNewAddress ? l10n.checkoutUseSaved : l10n.checkoutChange),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              if (showForm)
                _AddressForm(
                  formKey: _form,
                  name: _name,
                  phone: _phone,
                  governorate: _governorate,
                  city: _city,
                  street: _street,
                )
              else
                _AddressCard(address: saved),
              const SizedBox(height: AppSpacing.xl),
              _SectionTitle(l10n.checkoutPayment),
              const SizedBox(height: AppSpacing.sm),
              Card(
                margin: EdgeInsets.zero,
                clipBehavior: Clip.antiAlias,
                child: RadioGroup<PaymentMethod>(
                  groupValue: _payment,
                  onChanged: (value) {
                    if (value != null && !_placing) setState(() => _payment = value);
                  },
                  child: Column(
                    children: [
                      _PaymentTile(value: PaymentMethod.cashOnDelivery, title: l10n.paymentCod),
                      const Divider(height: 1, color: AppColors.border),
                      _PaymentTile(
                        value: PaymentMethod.online,
                        title: l10n.paymentOnline,
                        subtitle: l10n.paymentOnlineBody,
                      ),
                      const Divider(height: 1, color: AppColors.border),
                      _PaymentTile(
                        value: PaymentMethod.loyaltyPoints,
                        title: l10n.paymentPoints,
                        subtitle: points == null ? null : l10n.paymentPointsBalance(points),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.xl),
              Text.rich(
                TextSpan(
                  text: l10n.checkoutNotes,
                  style: theme.textTheme.titleMedium,
                  children: [
                    TextSpan(
                      text: l10n.checkoutOptional,
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                controller: _notes,
                maxLines: 2,
                maxLength: 1000,
                decoration: InputDecoration(
                  hintText: l10n.checkoutNotesHint,
                  counterText: '',
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(AppSpacing.radius),
                    borderSide: const BorderSide(color: AppColors.border),
                  ),
                ),
              ),
              if (cart != null) ...[
                const SizedBox(height: AppSpacing.xl),
                DecoratedBox(
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(AppSpacing.radius),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: OrderSummary(
                      subtotalLabel: l10n.cartItemsCount(cart.itemsCount),
                      subtotal: cart.subtotal,
                      discount: cart.discount,
                      shipping: cart.shipping,
                      total: cart.total,
                    ),
                  ),
                ),
              ],
              if (_failure != null) ...[
                const SizedBox(height: AppSpacing.lg),
                Semantics(
                  liveRegion: true,
                  child: Text(
                    apiErrorMessage(context, _failure!),
                    style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.error),
                  ),
                ),
              ],
            ],
          );
        },
      ),
      bottomNavigationBar: addresses.hasValue && cart != null && !cart.isEmpty
          ? DecoratedBox(
              decoration: const BoxDecoration(
                color: AppColors.background,
                border: Border(top: BorderSide(color: AppColors.border)),
              ),
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: FilledButton(
                    onPressed: _placing
                        ? null
                        : () {
                            final list = addresses.requireValue;
                            _place(list.isEmpty
                                ? null
                                : list.firstWhere((a) => a.isDefault, orElse: () => list.first));
                          },
                    child: _placing
                        ? const SizedBox.square(
                            dimension: 22,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Text(l10n.checkoutConfirm),
                  ),
                ),
              ),
            )
          : null,
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(text, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontSize: 18));
  }
}

class _AddressCard extends StatelessWidget {
  const _AddressCard({required this.address});

  final Address address;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant);

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.location_on_outlined, size: 20, color: theme.colorScheme.secondary),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text.rich(
                    TextSpan(
                      text: address.recipientName,
                      style: theme.textTheme.titleMedium,
                      // A left-to-right isolate keeps the phone digits in order.
                      children: [TextSpan(text: ' · \u2066${address.phone}\u2069', style: muted)],
                    ),
                  ),
                  Text(
                    [address.governorate, address.city, address.street]
                        .where((part) => part.isNotEmpty)
                        .join('، '),
                    style: muted,
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

class _AddressForm extends StatelessWidget {
  const _AddressForm({
    required this.formKey,
    required this.name,
    required this.phone,
    required this.governorate,
    required this.city,
    required this.street,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController name;
  final TextEditingController phone;
  final TextEditingController governorate;
  final TextEditingController city;
  final TextEditingController street;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    String? required(String? value) =>
        value == null || value.trim().isEmpty ? l10n.fieldRequired : null;
    String? validPhone(String? value) {
      final digits = (value ?? '').replaceAll(RegExp(r'\D'), '');
      if (digits.isEmpty) return l10n.fieldRequired;
      return RegExp(r'^(20)?01[0125]\d{8}$').hasMatch(digits) ? null : l10n.loginPhoneInvalid;
    }

    return Form(
      key: formKey,
      child: AutofillGroup(
        child: Column(
          children: [
            _Field(
              label: l10n.addressName,
              controller: name,
              validator: required,
              autofillHints: const [AutofillHints.name],
            ),
            _Field(
              label: l10n.addressPhone,
              controller: phone,
              validator: validPhone,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d+ ]'))],
              autofillHints: const [AutofillHints.telephoneNumber],
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: _Field(
                    label: l10n.addressGovernorate,
                    controller: governorate,
                    validator: required,
                    autofillHints: const [AutofillHints.addressState],
                  ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: _Field(
                    label: l10n.addressCity,
                    controller: city,
                    validator: required,
                    autofillHints: const [AutofillHints.addressCity],
                  ),
                ),
              ],
            ),
            _Field(
              label: l10n.addressStreet,
              hint: l10n.addressStreetHint,
              controller: street,
              validator: required,
              textInputAction: TextInputAction.done,
              autofillHints: const [AutofillHints.streetAddressLine1],
            ),
          ],
        ),
      ),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.label,
    required this.controller,
    required this.validator,
    this.hint,
    this.keyboardType,
    this.textDirection,
    this.inputFormatters,
    this.autofillHints,
    this.textInputAction = TextInputAction.next,
  });

  final String label;
  final String? hint;
  final TextEditingController controller;
  final FormFieldValidator<String> validator;
  final TextInputType? keyboardType;
  final TextDirection? textDirection;
  final List<TextInputFormatter>? inputFormatters;
  final List<String>? autofillHints;
  final TextInputAction textInputAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: theme.textTheme.titleMedium),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: controller,
            validator: validator,
            keyboardType: keyboardType,
            textDirection: textDirection,
            inputFormatters: inputFormatters,
            autofillHints: autofillHints,
            textInputAction: textInputAction,
            autovalidateMode: AutovalidateMode.onUserInteraction,
            decoration: InputDecoration(
              hintText: hint,
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppSpacing.radius),
                borderSide: const BorderSide(color: AppColors.border),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentTile extends StatelessWidget {
  const _PaymentTile({required this.value, required this.title, this.subtitle});

  final PaymentMethod value;
  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return RadioListTile<PaymentMethod>(
      value: value,
      title: Text(title, style: theme.textTheme.titleMedium),
      subtitle: subtitle == null
          ? null
          : Text(
              subtitle!,
              style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
      controlAffinity: ListTileControlAffinity.leading,
      contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm),
    );
  }
}
