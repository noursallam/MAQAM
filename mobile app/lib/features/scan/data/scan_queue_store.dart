import 'dart:convert';

import '../../../core/storage/key_value_store.dart';
import '../domain/scan.dart';

/// Keeps offline scans, and the outcome of the last upload, on the phone so
/// they survive the app being closed.
class ScanQueueStore {
  const ScanQueueStore(this._store);

  final KeyValueStore _store;

  static const _pendingKey = 'scan_queue';
  static const _lastSyncKey = 'scan_last_sync';

  Future<List<QueuedScan>> readPending() async =>
      [for (final json in await _readList(_pendingKey)) _scanFromJson(json)];

  Future<List<SyncedScan>> readLastSync() async => [
        for (final json in await _readList(_lastSyncKey))
          SyncedScan(
            scan: _scanFromJson(json['scan'] as Map<String, dynamic>),
            status: SyncStatus.values.asNameMap()[json['status']] ?? SyncStatus.rejected,
            points: json['points'] as int? ?? 0,
            message: json['message'] as String?,
          ),
      ];

  Future<void> save({required List<QueuedScan> pending, required List<SyncedScan> lastSync}) async {
    await _store.write(_pendingKey, jsonEncode([for (final scan in pending) _scanToJson(scan)]));
    await _store.write(
      _lastSyncKey,
      jsonEncode([
        for (final synced in lastSync)
          {
            'scan': _scanToJson(synced.scan),
            'status': synced.status.name,
            'points': synced.points,
            'message': synced.message,
          },
      ]),
    );
  }

  Future<void> clear() async {
    await _store.delete(_pendingKey);
    await _store.delete(_lastSyncKey);
  }

  Future<List<Map<String, dynamic>>> _readList(String key) async {
    try {
      final raw = await _store.read(key);
      if (raw == null) return const [];
      return (jsonDecode(raw) as List<dynamic>).cast<Map<String, dynamic>>();
    } on Exception {
      // Unreadable storage must not stop scanning; start the list afresh.
      return const [];
    }
  }

  static Map<String, Object?> _scanToJson(QueuedScan scan) => {
        'serial_code': scan.serialCode,
        'scanned_at': scan.scannedAt.toUtc().toIso8601String(),
        'merchant_code': scan.merchantCode,
        'merchant_name': scan.merchantName,
      };

  static QueuedScan _scanFromJson(Map<String, dynamic> json) => QueuedScan(
        serialCode: json['serial_code'] as String,
        scannedAt: DateTime.tryParse(json['scanned_at'] as String? ?? '')?.toLocal() ?? DateTime.now(),
        merchantCode: json['merchant_code'] as String?,
        merchantName: json['merchant_name'] as String?,
      );
}
