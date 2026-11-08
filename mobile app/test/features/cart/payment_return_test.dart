import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/features/cart/presentation/payment_screen.dart';

void main() {
  final returnUrl = Uri.parse('https://maqam-eg.com/payment/kashier/callback');

  test('the return page is recognised with whatever the gateway adds to it', () {
    expect(
      isPaymentReturn(
        returnUrl,
        'https://maqam-eg.com/payment/kashier/callback/app?paymentStatus=SUCCESS&merchantOrderId=MQ-1',
      ),
      isTrue,
    );
    expect(isPaymentReturn(returnUrl, 'https://maqam-eg.com/payment/kashier/callback?x=1'), isTrue);
  });

  test('the gateway\'s own pages are not mistaken for the return', () {
    expect(isPaymentReturn(returnUrl, 'https://payments.kashier.io/session/abc'), isFalse);
    // Carrying the return address as a parameter is not being on it.
    expect(
      isPaymentReturn(
        returnUrl,
        'https://payments.kashier.io/?redirect=https://maqam-eg.com/payment/kashier/callback',
      ),
      isFalse,
    );
    expect(isPaymentReturn(null, 'https://maqam-eg.com/payment/kashier/callback'), isFalse);
  });
}
