import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../domain/auth_repository.dart';
import '../domain/whatsapp_challenge.dart';

enum SendCodeStatus { starting, waiting, failed, expired }

/// Step 2 of sign-in: gets the code to send over WhatsApp, then polls until
/// the server has seen the customer's message and sent them a sign-in code.
class SendCodeController extends ChangeNotifier {
  SendCodeController(this._repository, this.phone, {this.purpose = SignInPurpose.signIn});

  final AuthRepository _repository;
  final String phone;
  final SignInPurpose purpose;

  SendCodeStatus status = SendCodeStatus.starting;
  WhatsappChallenge? challenge;

  /// Why the challenge could not be started, when [status] is `failed`.
  ApiException? error;

  /// Set once the sign-in code has been sent; the flow then moves on.
  OtpTicket? ticket;

  Timer? _pollTimer;
  Timer? _expiryTimer;
  bool _disposed = false;

  Future<void> start() async {
    _cancelTimers();
    status = SendCodeStatus.starting;
    challenge = null;
    error = null;
    notifyListeners();

    try {
      final started = await _repository.startChallenge(phone);
      if (_disposed) return;
      challenge = started;
      status = SendCodeStatus.waiting;
      _expiryTimer = Timer(started.validFor, _expire);
      _schedulePoll();
    } on ApiException catch (e) {
      if (_disposed) return;
      error = e;
      status = SendCodeStatus.failed;
    }
    notifyListeners();
  }

  void _schedulePoll() {
    _pollTimer = Timer(challenge!.pollInterval, _poll);
  }

  Future<void> _poll() async {
    final current = challenge!;
    try {
      final otpValidFor = await _repository.checkChallenge(current.id);
      if (_disposed || challenge != current) return;
      if (otpValidFor != null) {
        _cancelTimers();
        ticket = OtpTicket(challengeId: current.id, validFor: otpValidFor, purpose: purpose);
        notifyListeners();
        return;
      }
    } on ApiException catch (e) {
      if (_disposed || challenge != current) return;
      if (e.code == ApiErrorCodes.challengeExpired) {
        _expire();
        return;
      }
      // A dropped connection or a busy server: keep waiting and try again.
    }
    _schedulePoll();
  }

  void _expire() {
    _cancelTimers();
    status = SendCodeStatus.expired;
    notifyListeners();
  }

  void _cancelTimers() {
    _pollTimer?.cancel();
    _expiryTimer?.cancel();
  }

  @override
  void dispose() {
    _disposed = true;
    _cancelTimers();
    super.dispose();
  }
}
