import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/features/content/data/api_content_repository.dart';

import 'support/test_app.dart';

const _saved = AuthSession(token: 'saved-token', fullName: 'أحمد', phone: '01012345678');

void main() {
  testWidgets('wording changed from the dashboard replaces the built-in text', (tester) async {
    final app = await pumpApp(
      tester,
      session: _saved,
      appTexts: const {
        'ar': {'accountHelp': 'كلّمنا', 'accountVersion': 'نسخة التطبيق {version}'},
      },
    );
    await openTab(tester, 'حسابي');

    expect(find.text('كلّمنا'), findsOneWidget);
    expect(find.textContaining('نسخة التطبيق '), findsOneWidget);
    // Untouched texts keep the app's own wording
    expect(find.text('عناويني'), findsOneWidget);
    // Kept on the device for the next launch, also offline
    expect(app.store.values['app_texts'], contains('كلّمنا'));
  });

  test('the server answer is read per language and ignores anything else', () {
    expect(
      parseAppTexts({
        'version': 3,
        'texts': {
          'ar': {'navAccount': 'ملفي', 'bad': 7},
          'en': <Object>[],
        },
      }),
      {
        'ar': {'navAccount': 'ملفي'},
      },
    );
    expect(parseAppTexts(null), isEmpty);
  });
}
