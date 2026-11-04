import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/features/auth/domain/egyptian_phone.dart';

void main() {
  test('accepts the common ways of writing an Egyptian mobile number', () {
    for (final input in ['1012345678', '01012345678', '10 1234 5678', '+201012345678', '00201012345678']) {
      expect(normalizeEgyptianPhone(input), '01012345678', reason: input);
    }
    expect(normalizeEgyptianPhone('1512345678'), '01512345678');
  });

  test('rejects numbers that are not Egyptian mobiles', () {
    for (final input in ['', '123', '1312345678', '0223456789', '101234567', '010123456789']) {
      expect(normalizeEgyptianPhone(input), isNull, reason: input);
    }
  });
}
