import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/features/auth/domain/auth_repository.dart';
import 'package:maqam_app/features/auth/domain/whatsapp_challenge.dart';
import 'package:maqam_app/features/auth/presentation/send_code_controller.dart';

const _tick = Duration(milliseconds: 5);

class _Repository implements AuthRepository {
  _Repository({this.startError, required this.checks, this.validFor = const Duration(minutes: 10)});

  final ApiException? startError;

  /// One entry per poll: a Duration (code sent), an ApiException, or null (still waiting).
  final List<Object?> checks;
  final Duration validFor;
  int starts = 0;

  @override
  Future<WhatsappChallenge> startChallenge(String phone) async {
    starts++;
    if (startError != null) throw startError!;
    return WhatsappChallenge(
      id: 'c$starts',
      code: 'MAQAM-1',
      whatsappUrl: Uri.parse('https://wa.me/1'),
      validFor: validFor,
      pollInterval: _tick,
    );
  }

  @override
  Future<Duration?> checkChallenge(String challengeId) async {
    final next = checks.isEmpty ? null : checks.removeAt(0);
    if (next is ApiException) throw next;
    return next as Duration?;
  }

  // Only the two challenge calls are used by this controller.
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<void> _wait([int ticks = 20]) => Future<void>.delayed(_tick * ticks);

void main() {
  test('keeps polling through a dropped connection until the code is sent', () async {
    final controller = SendCodeController(
      _Repository(checks: [
        null,
        const ApiException(ApiErrorKind.network),
        const Duration(minutes: 5),
      ]),
      '01012345678',
    );
    addTearDown(controller.dispose);

    await controller.start();
    expect(controller.status, SendCodeStatus.waiting);
    expect(controller.challenge!.code, 'MAQAM-1');
    expect(controller.ticket, isNull);

    await _wait();
    expect(controller.ticket!.challengeId, 'c1');
    expect(controller.ticket!.validFor, const Duration(minutes: 5));
  });

  test('reports why the challenge could not be started, and can retry', () async {
    final repository = _Repository(
      startError: const ApiException(ApiErrorKind.server, message: 'unavailable'),
      checks: [],
    );
    final controller = SendCodeController(repository, '01012345678');
    addTearDown(controller.dispose);

    await controller.start();
    expect(controller.status, SendCodeStatus.failed);
    expect(controller.error!.message, 'unavailable');

    await controller.start();
    expect(repository.starts, 2);
  });

  test('expires when the server says the challenge is gone', () async {
    final controller = SendCodeController(
      _Repository(checks: [
        const ApiException(ApiErrorKind.server, code: ApiErrorCodes.challengeExpired),
      ]),
      '01012345678',
    );
    addTearDown(controller.dispose);

    await controller.start();
    await _wait();

    expect(controller.status, SendCodeStatus.expired);
    expect(controller.ticket, isNull);
  });

  test('expires on its own when the challenge lifetime runs out', () async {
    final controller = SendCodeController(
      _Repository(checks: [], validFor: _tick * 4),
      '01012345678',
    );
    addTearDown(controller.dispose);

    await controller.start();
    await _wait();

    expect(controller.status, SendCodeStatus.expired);
  });
}
