import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/content.dart';

/// A conversation with the support assistant. The server keeps nothing, so
/// the conversation lives here and recent turns go with every question.
class ChatController extends ChangeNotifier {
  ChatController(this._repository);

  final ContentRepository _repository;

  final List<ChatTurn> messages = [];
  bool sending = false;

  /// How many questions the customer may still ask today, once the server has said.
  int? messagesLeft;

  bool _disposed = false;

  /// Sends [text]. Returns null on success, or why it failed; a failed
  /// question is taken back out of the conversation so it can be sent again.
  Future<ApiException?> send(String text) async {
    final question = text.trim();
    if (question.isEmpty || sending) return null;

    final history = List<ChatTurn>.of(messages);
    final turn = ChatTurn(fromCustomer: true, text: question);
    messages.add(turn);
    sending = true;
    notifyListeners();

    ApiException? failure;
    try {
      final reply = await _repository.ask(question, history: history);
      messages.add(ChatTurn(fromCustomer: false, text: reply.text));
      messagesLeft = reply.messagesLeftToday ?? messagesLeft;
    } on ApiException catch (e) {
      messages.remove(turn);
      failure = e;
    }
    if (_disposed) return failure;
    sending = false;
    notifyListeners();
    return failure;
  }

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }
}
