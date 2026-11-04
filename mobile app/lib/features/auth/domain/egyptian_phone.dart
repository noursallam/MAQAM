/// Normalizes user input to the local Egyptian mobile format the API expects
/// (`01XXXXXXXXX`). Returns null when the input is not a valid mobile number.
String? normalizeEgyptianPhone(String input) {
  var digits = input.replaceAll(RegExp(r'\D'), '');
  if (digits.startsWith('0020')) {
    digits = digits.substring(4);
  } else if (digits.startsWith('20') && digits.length == 12) {
    digits = digits.substring(2);
  }
  if (!digits.startsWith('0')) {
    digits = '0$digits';
  }
  return RegExp(r'^01[0125]\d{8}$').hasMatch(digits) ? digits : null;
}

/// `01012345678` -> `010 1234 5678`.
String formatEgyptianPhone(String phone) {
  if (phone.length != 11) return phone;
  return '${phone.substring(0, 3)} ${phone.substring(3, 7)} ${phone.substring(7)}';
}
