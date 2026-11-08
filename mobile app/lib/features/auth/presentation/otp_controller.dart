import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/auth_repository.dart';
import '../domain/whatsapp_challenge.dart';

/// Step 3 of sign-in: counts down the code's lifetime and exchanges it for a session.
class OtpController extends ChangeNotifier {
  OtpController(this._repository, this._ticket) : remaining = _ticket.validFor {
    _ticker = Timer.periodic(const Duration(seconds: 1), (_) => _tick());
  }

  final AuthRepository _repository;
  final OtpTicket _ticket;

  Duration remaining;
  bool submitting = false;
  ApiException? error;

  late final Timer _ticker;
  bool _disposed = false;

  bool get expired => remaining <= Duration.zero;

  void _tick() {
    remaining -= const Duration(seconds: 1);
    if (expired) {
      remaining = Duration.zero;
      _ticker.cancel();
    }
    notifyListeners();
  }

  void clearError() {
    if (error == null) return;
    error = null;
    notifyListeners();
  }

  /// Returns the sign-in on success, or null after setting [error].
  Future<SignIn?> verify(String otp) async {
    if (submitting || expired) return null;
    submitting = true;
    error = null;
    notifyListeners();

    SignIn? session;
    try {
      session = await _repository.verifyOtp(challengeId: _ticket.challengeId, otp: otp);
    } on ApiException catch (e) {
      error = e;
      if (e.code == ApiErrorCodes.challengeExpired) {
        remaining = Duration.zero;
        _ticker.cancel();
      }
    }
    if (_disposed) return session;
    submitting = false;
    notifyListeners();
    return session;
  }

  @override
  void dispose() {
    _disposed = true;
    _ticker.cancel();
    super.dispose();
  }
}
