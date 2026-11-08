import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/page_widgets.dart';
import '../../../core/widgets/shop_widgets.dart';
import '../../../l10n/l10n.dart';
import '../../auth/domain/egyptian_phone.dart';
import '../addresses_providers.dart';
import '../domain/address_book.dart';

/// The customer's saved delivery addresses (design: "21 عناويني").
class AddressesScreen extends ConsumerStatefulWidget {
  const AddressesScreen({super.key});

  @override
  ConsumerState<AddressesScreen> createState() => _AddressesScreenState();
}

class _AddressesScreenState extends ConsumerState<AddressesScreen> {
  /// The address being edited in the form; null while adding a new one.
  Address? _editing;

  /// The address whose change is being saved, to show progress on its card.
  int? _busyId;

  void _snack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _run(int id, Future<void> Function() change, String done) async {
    setState(() => _busyId = id);
    try {
      await change();
      if (mounted) _snack(done);
    } on ApiException catch (e) {
      if (mounted) showApiError(context, e);
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _delete(Address address) async {
    final l10n = context.l10n;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.addressDeleteTitle),
        content: Text(l10n.addressDeleteBody),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(l10n.cancel)),
          TextButton(
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            onPressed: () => Navigator.pop(context, true),
            child: Text(l10n.addressDelete),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    if (_editing?.id == address.id) setState(() => _editing = null);
    await _run(
      address.id,
      () => ref.read(addressBookProvider.notifier).delete(address.id),
      l10n.addressDeleted,
    );
  }

  Future<void> _submit(AddressDraft draft) async {
    final notifier = ref.read(addressBookProvider.notifier);
    final editing = _editing;
    if (editing == null) {
      await notifier.add(draft);
    } else {
      await notifier.save(editing.id, draft);
    }
    if (!mounted) return;
    setState(() => _editing = null);
    _snack(context.l10n.addressSaved);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final addresses = ref.watch(addressBookProvider);

    return Scaffold(
      appBar: SubPageAppBar(title: l10n.accountAddresses),
      body: SafeArea(
        top: false,
        child: AsyncView<List<Address>>(
          value: addresses,
          errorTitle: l10n.addressesLoadFailed,
          onRetry: () => ref.invalidate(addressBookProvider),
          data: (list) => ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              for (final address in list) ...[
                _AddressCard(
                  address: address,
                  busy: _busyId == address.id,
                  onEdit: () => setState(() => _editing = address),
                  onMakeDefault: () => _run(
                    address.id,
                    () => ref.read(addressBookProvider.notifier).makeDefault(address),
                    l10n.addressDefaultSet,
                  ),
                  onDelete: () => _delete(address),
                ),
                const SizedBox(height: AppSpacing.md),
              ],
              if (list.isEmpty) ...[
                Text(
                  l10n.addressesEmpty,
                  textAlign: TextAlign.center,
                  style: Theme.of(context)
                      .textTheme
                      .bodyMedium
                      ?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
                ),
                const SizedBox(height: AppSpacing.md),
              ],
              _AddressForm(
                // A fresh form for each address, so its fields start from that address.
                key: ValueKey(_editing?.id),
                editing: _editing,
                onSubmit: _submit,
                onCancel: () => setState(() => _editing = null),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AddressCard extends StatelessWidget {
  const _AddressCard({
    required this.address,
    required this.busy,
    required this.onEdit,
    required this.onMakeDefault,
    required this.onDelete,
  });

  final Address address;
  final bool busy;
  final VoidCallback onEdit;
  final VoidCallback onMakeDefault;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;
    final location = [address.governorate, address.city, address.street]
        .where((part) => part.trim().isNotEmpty)
        .join(l10n.listSeparator);

    return Card(
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radius),
        side: BorderSide(
          color: address.isDefault ? AppColors.navy : AppColors.border,
          width: address.isDefault ? 2 : 1,
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.xs),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    address.recipientName,
                    style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ),
                if (address.isDefault)
                  StatusPill(
                    label: l10n.addressDefault,
                    background: AppColors.navy,
                    foreground: Colors.white,
                  ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(location, style: theme.textTheme.bodyLarge?.copyWith(color: muted)),
            Text(
              formatEgyptianPhone(address.phone),
              textDirection: TextDirection.ltr,
              // The number reads left to right but sits at the card's start edge.
              textAlign: Directionality.of(context) == TextDirection.rtl ? TextAlign.right : TextAlign.left,
              style: theme.textTheme.bodyLarge?.copyWith(color: muted),
            ),
            const Divider(height: AppSpacing.xl, color: AppColors.border),
            if (busy)
              const Padding(
                padding: EdgeInsets.all(AppSpacing.md),
                child: Center(
                  child: SizedBox.square(dimension: 24, child: CircularProgressIndicator(strokeWidth: 2.5)),
                ),
              )
            else
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  TextButton(onPressed: onEdit, child: Text(l10n.addressEdit)),
                  if (!address.isDefault)
                    TextButton(
                      style: TextButton.styleFrom(foregroundColor: theme.colorScheme.secondary),
                      onPressed: onMakeDefault,
                      child: Text(l10n.addressMakeDefault),
                    ),
                  TextButton(
                    style: TextButton.styleFrom(foregroundColor: AppColors.error),
                    onPressed: onDelete,
                    child: Text(l10n.addressDelete),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }
}

/// Adds a new address, or edits [editing] when it is set.
class _AddressForm extends StatefulWidget {
  const _AddressForm({super.key, required this.editing, required this.onSubmit, required this.onCancel});

  final Address? editing;

  /// Saves the address; throws an [ApiException] if the server refuses it.
  final Future<void> Function(AddressDraft draft) onSubmit;
  final VoidCallback onCancel;

  @override
  State<_AddressForm> createState() => _AddressFormState();
}

class _AddressFormState extends State<_AddressForm> {
  final _form = GlobalKey<FormState>();
  late final _name = TextEditingController(text: widget.editing?.recipientName);
  late final _phone = TextEditingController(text: widget.editing?.phone);
  late final _governorate = TextEditingController(text: widget.editing?.governorate);
  late final _city = TextEditingController(text: widget.editing?.city);
  late final _street = TextEditingController(text: widget.editing?.street);
  bool _saving = false;
  ApiException? _failure;

  @override
  void dispose() {
    for (final controller in [_name, _phone, _governorate, _city, _street]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (_saving || !_form.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    setState(() {
      _saving = true;
      _failure = null;
    });
    try {
      await widget.onSubmit(AddressDraft(
        recipientName: _name.text.trim(),
        phone: normalizeEgyptianPhone(_phone.text)!,
        governorate: _governorate.text.trim(),
        city: _city.text.trim(),
        street: _street.text.trim(),
      ));
      if (mounted && widget.editing == null) {
        _form.currentState!.reset();
        for (final controller in [_name, _phone, _governorate, _city, _street]) {
          controller.clear();
        }
      }
    } on ApiException catch (e) {
      if (mounted) setState(() => _failure = e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final theme = Theme.of(context);
    final editing = widget.editing != null;

    String? required(String? value) => (value ?? '').trim().isEmpty ? l10n.fieldRequired : null;

    Widget field(TextEditingController controller, String hint,
            {TextInputType? keyboard, String? Function(String?)? validator, TextDirection? direction}) =>
        TextFormField(
          controller: controller,
          enabled: !_saving,
          keyboardType: keyboard,
          textDirection: direction,
          textInputAction: TextInputAction.next,
          validator: validator ?? required,
          inputFormatters: keyboard == TextInputType.phone
              ? [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(11)]
              : null,
          decoration: InputDecoration(hintText: hint, labelText: hint),
        );

    return Card(
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radius),
        side: BorderSide(color: editing ? AppColors.gold : AppColors.border),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Form(
          key: _form,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                editing ? l10n.addressEditTitle : l10n.addressNewTitle,
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: AppSpacing.md),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(child: field(_name, l10n.addressRecipient)),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: field(
                      _phone,
                      l10n.addressPhone,
                      keyboard: TextInputType.phone,
                      direction: TextDirection.ltr,
                      validator: (value) => (value ?? '').trim().isEmpty
                          ? l10n.fieldRequired
                          : normalizeEgyptianPhone(value!) == null
                              ? l10n.addressPhoneInvalid
                              : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(child: field(_governorate, l10n.addressGovernorate)),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(child: field(_city, l10n.addressCity)),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              field(_street, l10n.addressStreetDetail, keyboard: TextInputType.streetAddress),
              if (_failure != null) ...[
                const SizedBox(height: AppSpacing.sm),
                Text(
                  apiErrorMessage(context, _failure!),
                  style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                ),
              ],
              const SizedBox(height: AppSpacing.lg),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox.square(
                        dimension: 22,
                        child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                      )
                    : Text(editing ? l10n.addressSaveChanges : l10n.addressSave),
              ),
              if (editing)
                TextButton(onPressed: _saving ? null : widget.onCancel, child: Text(l10n.cancel)),
            ],
          ),
        ),
      ),
    );
  }
}
