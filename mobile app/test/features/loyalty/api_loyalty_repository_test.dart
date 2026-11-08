import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:maqam_app/core/network/api_client.dart';
import 'package:maqam_app/core/network/api_exception.dart';
import 'package:maqam_app/features/loyalty/data/api_loyalty_repository.dart';
import 'package:maqam_app/features/loyalty/domain/wallet.dart';

http.Response _json(Object body, [int status = 200]) =>
    http.Response.bytes(utf8.encode(jsonEncode(body)), status);

ApiLoyaltyRepository _repository(MockClientHandler handler) =>
    ApiLoyaltyRepository(ApiClient(httpClient: MockClient(handler), token: () => 't'));

void main() {
  test('maps the wallet, including progress toward the next rank', () async {
    final repository = _repository((_) async => _json({
          'success': true,
          'message': '',
          'data': {
            'points_balance': 400,
            'total_points_earned': 450,
            'rank': {'id': 1, 'name': 'فضي', 'min_points': 0},
            'next_rank': {'id': 2, 'name': 'ذهبي', 'min_points': 1000},
            'points_left_for_next_rank': 550,
            'progress_percent': 45,
          },
        }));

    final wallet = await repository.wallet();

    expect(wallet.balance, 400);
    expect(wallet.totalEarned, 450);
    expect(wallet.progress, 0.45);
    expect(wallet.pointsToNextRank, 550);
    expect(wallet.rankName, 'فضي');
    expect(wallet.nextRankName, 'ذهبي');
    expect(wallet.nextRankMinPoints, 1000);
  });

  test('a customer at the top rank has no next rank', () async {
    final repository = _repository((_) async => _json({
          'success': true,
          'message': '',
          'data': {
            'points_balance': 5000,
            'total_points_earned': 9000,
            'rank': {'id': 3, 'name': 'بلاتيني', 'min_points': 5000},
            'next_rank': null,
            'points_left_for_next_rank': 0,
            'progress_percent': 100,
          },
        }));

    final wallet = await repository.wallet();

    expect(wallet.nextRankName, isNull);
    expect(wallet.progress, 1);
  });

  test('signs transactions by type and sends the filter', () async {
    late http.Request sent;
    final repository = _repository((request) async {
      sent = request;
      return _json({
        'success': true,
        'message': '',
        'data': [
          {'id': 3, 'type': 'earn', 'amount': 25, 'description': 'مسح', 'date': '2026-10-07T10:00:00+00:00'},
          {'id': 2, 'type': 'spend', 'amount': 50, 'description': 'عجلة', 'date': null},
          {'id': 1, 'type': 'expire', 'amount': 10, 'description': 'انتهاء', 'date': 'garbage'},
        ],
        'meta': {'current_page': 1, 'last_page': 1, 'per_page': 20, 'total': 3},
      });
    });

    final items = await repository.transactions(filter: TransactionFilter.spent);

    expect(sent.url.queryParameters['type'], 'spend');
    expect(sent.headers['Authorization'], 'Bearer t');
    expect(items.map((t) => t.points), [25, -50, -10]);
    expect(items.first.date, isNotNull);
    expect(items.last.date, isNull);
  });

  test('throws when the wallet cannot be loaded', () async {
    final repository = _repository((_) async => _json({'success': false, 'message': 'x'}, 500));

    await expectLater(
      repository.wallet(),
      throwsA(isA<ApiException>().having((e) => e.kind, 'kind', ApiErrorKind.server)),
    );
  });

  test('maps rewards of each kind and asks for the chosen status', () async {
    late http.Request sent;
    final repository = _repository((request) async {
      sent = request;
      return _json({
        'success': true,
        'message': '',
        'data': [
          {
            'id': 12,
            'code': 'WD-9812AXYZ',
            'type': 'discount',
            'source': 'wheel',
            'status': 'available',
            'amount_type': 'percentage',
            'amount_value': '15.00',
            'product': null,
            'expires_at': '2026-11-01T00:00:00+00:00',
            'used_at': null,
          },
          {
            'id': 13,
            'code': 'GP-4471KLMN',
            'type': 'product',
            'source': 'admin',
            'status': 'available',
            'amount_type': null,
            'amount_value': null,
            'product': {'id': 5, 'name': 'مفتاح', 'image_url': 'https://x/y.jpg'},
            'expires_at': null,
            'used_at': null,
          },
        ],
        'meta': {'current_page': 1, 'last_page': 1, 'per_page': 30, 'total': 2},
      });
    });

    final rewards = await repository.rewards(RewardStatus.available);

    expect(sent.url.queryParameters['status'], 'available');
    expect(rewards.first.kind, RewardKind.discount);
    expect(rewards.first.isPercentage, isTrue);
    expect(rewards.first.amount, 15);
    expect(rewards.first.fromWheel, isTrue);
    expect(rewards.first.expiresAt, isNotNull);
    expect(rewards.last.kind, RewardKind.product);
    expect(rewards.last.productName, 'مفتاح');
    expect(rewards.last.fromWheel, isFalse);
  });

  test('maps the wheel and where a spin landed', () async {
    final repository = _repository((request) async => _json({
          'success': true,
          'message': 'مبروك!',
          'data': request.method == 'GET'
              ? {
                  'is_enabled': true,
                  'cost_points': 50,
                  'customer_balance': 425,
                  'can_spin': true,
                  'slices': [
                    {'id': 1, 'type': 'points', 'label': '50 نقطة'},
                    {'id': null, 'type': 'none', 'label': 'حظ أوفر'},
                  ],
                }
              : {
                  'spin': {'id': 45, 'is_win': true, 'prize_type': 'points', 'prize_label': '50 نقطة', 'points_cost': 50, 'points_won': 50},
                  'reward': null,
                  'new_points_balance': 425,
                  'slice_index': 0,
                },
        }));

    final wheel = await repository.wheel();
    final spin = await repository.spin();

    expect(wheel.cost, 50);
    expect(wheel.canSpin, isTrue);
    expect(wheel.slices.map((s) => s.isPrize), [true, false]);
    expect(spin.won, isTrue);
    expect(spin.sliceIndex, 0);
    expect(spin.newBalance, 425);
    expect(spin.prizeLabel, '50 نقطة');
    expect(spin.reward, isNull);
  });

  test('a spin without enough points is refused', () async {
    final repository = _repository((_) async => _json({
          'success': false,
          'message': 'الرصيد لا يكفي.',
          'error_code': 'INSUFFICIENT_POINTS',
        }, 422));

    await expectLater(
      repository.spin(),
      throwsA(isA<ApiException>().having((e) => e.code, 'code', 'INSUFFICIENT_POINTS')),
    );
  });
}
