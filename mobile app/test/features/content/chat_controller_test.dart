import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/features/content/presentation/chat_controller.dart';

import '../../support/test_app.dart';

void main() {
  test('a question and its answer join the conversation', () async {
    final content = FakeContent();
    final controller = ChatController(content);

    final pending = controller.send('  كم الشحن؟ ');
    expect(controller.sending, isTrue);
    expect(controller.messages.single.text, 'كم الشحن؟');

    expect(await pending, isNull);
    expect(controller.sending, isFalse);
    expect(controller.messages.map((m) => m.fromCustomer), [true, false]);
    expect(controller.messagesLeft, 59);
    expect(content.questions.single.history, isEmpty);

    await controller.send('وإزاي أكسب نقاط؟');
    // The earlier exchange goes with the next question.
    expect(content.questions.last.history, hasLength(2));
  });

  test('a refused question is taken back out and reported', () async {
    final controller = ChatController(FakeContent());

    final failure = await controller.send('FAIL');

    expect(failure, isA<ApiException>());
    expect(controller.messages, isEmpty);
    expect(controller.sending, isFalse);
  });

  test('an empty question is not sent', () async {
    final content = FakeContent();
    final controller = ChatController(content);

    await controller.send('   ');

    expect(content.questions, isEmpty);
    expect(controller.messages, isEmpty);
  });
}
