enum ApiErrorKind { network, unauthorized, validation, server }

class ApiException implements Exception {
  const ApiException(this.kind, {this.message, this.code});

  final ApiErrorKind kind;

  /// Localized message from the server, when it sent one.
  final String? message;

  /// The API's `error_code`, e.g. `INVALID_OTP`.
  final String? code;

  @override
  String toString() => 'ApiException(${kind.name}, $code)';
}

abstract final class ApiErrorCodes {
  static const challengeExpired = 'CHALLENGE_EXPIRED';
  static const invalidOtp = 'INVALID_OTP';
  static const accountFrozen = 'ACCOUNT_FROZEN_FRAUD';
  static const notFound = 'NOT_FOUND';
  static const quickLoginInvalid = 'QUICK_LOGIN_INVALID';
}
