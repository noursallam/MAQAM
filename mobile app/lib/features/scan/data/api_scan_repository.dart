import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/scan.dart';

class ApiScanRepository implements ScanRepository {
  const ApiScanRepository(this._api);

  final ApiClient _api;

  @override
  Future<ScanResult> scan({required String serialCode, String? merchantCode}) async {
    final data = await _api.post('/qr/scan', body: {
      'serial_code': serialCode,
      'merchant_code': ?merchantCode,
    }) as Map<String, dynamic>;
    return ScanResult(
      pointsAwarded: data['points_awarded'] as int? ?? 0,
      newBalance: data['new_balance'] as int? ?? 0,
      merchantName: data['merchant_credited'] as String?,
      merchantPoints: data['merchant_points'] as int? ?? 0,
      accountFrozen: data['account_frozen'] == true,
    );
  }

  @override
  Future<List<SyncedScan>> sync(List<QueuedScan> scans) async {
    final data = await _api.post('/qr/sync-batch', body: {
      'scans': [
        for (final scan in scans)
          {
            'serial_code': scan.serialCode,
            'merchant_code': scan.merchantCode,
            'scanned_at': scan.scannedAt.toUtc().toIso8601String(),
          },
      ],
    }) as Map<String, dynamic>;
    final results = (data['results'] as List<dynamic>? ?? const []).cast<Map<String, dynamic>>();
    // A scan the server did not answer for is left out, so it stays queued.
    return [
      for (final scan in scans)
        for (final result in results.where((r) => r['serial_code'] == scan.serialCode).take(1))
          _synced(scan, result),
    ];
  }

  static SyncedScan _synced(QueuedScan scan, Map<String, dynamic> result) => SyncedScan(
        scan: scan,
        status: switch (result['status']) {
          'success' => SyncStatus.credited,
          'already_synced' => SyncStatus.alreadyCredited,
          _ => SyncStatus.rejected,
        },
        points: result['points'] as int? ?? 0,
        message: result['message'] as String?,
      );

  @override
  Future<List<RecentMerchant>> recentMerchants() async {
    final data = await _api.get('/merchants/my-recent') as List<dynamic>;
    return [
      for (final json in data.cast<Map<String, dynamic>>())
        RecentMerchant(
          code: json['merchant_code'] as String,
          name: json['business_name'] as String? ?? '',
        ),
    ];
  }
}

final scanRepositoryProvider = Provider<ScanRepository>(
  (ref) => ApiScanRepository(ref.watch(apiClientProvider)),
);

final recentMerchantsProvider = FutureProvider.autoDispose<List<RecentMerchant>>(
  (ref) => ref.watch(scanRepositoryProvider).recentMerchants(),
);
