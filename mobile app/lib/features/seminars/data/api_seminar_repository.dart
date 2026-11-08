import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/seminar.dart';

class ApiSeminarRepository implements SeminarRepository {
  const ApiSeminarRepository(this._api);

  final ApiClient _api;

  @override
  Future<Seminar?> current() async {
    final data = await _api.get('/seminars/current');
    return data is Map<String, dynamic> ? _seminar(data) : null;
  }

  @override
  Future<Seminar> seminar(int id) async => _seminar(await _api.get('/seminars/$id') as Map<String, dynamic>);

  @override
  Future<Seminar> register(int id) async =>
      _seminar(await _api.post('/seminars/$id/register') as Map<String, dynamic>);

  static Seminar _seminar(Map<String, dynamic> json) => Seminar(
        id: json['id'] as int,
        title: json['title'] as String? ?? '',
        description: _text(json['description']),
        location: _text(json['location']),
        imageUrl: _text(json['image_url']),
        startsAt: DateTime.parse(json['starts_at'] as String).toLocal(),
        endsAt: DateTime.parse(json['ends_at'] as String).toLocal(),
        isLive: json['status'] == 'live',
        registrations: json['registrations_count'] as int? ?? 0,
        isRegistered: json['is_registered'] == true,
        draws: [
          for (final draw in (json['draws'] as List<dynamic>? ?? const []).cast<Map<String, dynamic>>())
            SeminarDraw(
              position: draw['position'] as int? ?? 0,
              prize: draw['prize'] as String? ?? '',
              isDrawn: draw['is_drawn'] == true,
              winnerName: draw['winner_name'] as String?,
              isMine: draw['is_mine'] == true,
            ),
        ],
      );

  static String? _text(Object? value) {
    final text = (value as String?)?.trim();
    return text == null || text.isEmpty ? null : text;
  }
}

final seminarRepositoryProvider = Provider<SeminarRepository>(
  (ref) => ApiSeminarRepository(ref.watch(apiClientProvider)),
);
