import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_exception.dart';
import '../../core/session/session_provider.dart';
import '../../core/storage/key_value_store.dart';
import '../loyalty/loyalty_providers.dart';
import 'data/api_scan_repository.dart';
import 'data/scan_queue_store.dart';
import 'domain/scan.dart';

class ScanQueueState {
  const ScanQueueState({this.pending = const [], this.lastSync = const [], this.syncing = false});

  /// Scans waiting for a connection, oldest first.
  final List<QueuedScan> pending;

  /// What the last upload did with each scan it sent.
  final List<SyncedScan> lastSync;
  final bool syncing;

  ScanQueueState copyWith({List<QueuedScan>? pending, List<SyncedScan>? lastSync, bool? syncing}) =>
      ScanQueueState(
        pending: pending ?? this.pending,
        lastSync: lastSync ?? this.lastSync,
        syncing: syncing ?? this.syncing,
      );
}

/// Scans made without a connection. They belong to the signed-in account, so
/// signing out on purpose forgets them (see [ScanQueueNotifier.clear]).
final scanQueueProvider = NotifierProvider<ScanQueueNotifier, ScanQueueState>(ScanQueueNotifier.new);

class ScanQueueNotifier extends Notifier<ScanQueueState> {
  late Future<void> _loaded;

  ScanQueueStore get _store => ScanQueueStore(ref.read(keyValueStoreProvider));

  @override
  ScanQueueState build() {
    // Signing in, including again after the token ran out, uploads what waits.
    ref.listen(sessionProvider, (previous, next) {
      if (previous == null && next != null) sync();
    });
    _loaded = _load();
    return const ScanQueueState();
  }

  Future<void> _load() async {
    final pending = await _store.readPending();
    final lastSync = await _store.readLastSync();
    state = state.copyWith(pending: [...pending, ...state.pending], lastSync: lastSync);
  }

  Future<void> _save() => _store.save(pending: state.pending, lastSync: state.lastSync);

  /// Keeps [scan] for later. A code already waiting is not added twice.
  Future<void> enqueue(QueuedScan scan) async {
    await _loaded;
    if (state.pending.any((s) => s.serialCode == scan.serialCode)) return;
    state = state.copyWith(pending: [...state.pending, scan]);
    await _save();
  }

  /// Uploads what is waiting. Returns false when it could not reach the
  /// server, leaving the queue as it was.
  Future<bool> sync() async {
    await _loaded;
    if (state.syncing || state.pending.isEmpty || ref.read(sessionProvider) == null) return true;
    final sent = state.pending;
    state = state.copyWith(syncing: true);
    try {
      final results = await ref.read(scanRepositoryProvider).sync(sent);
      final answered = {for (final r in results) r.scan.serialCode};
      state = state.copyWith(
        // Scans queued while this upload ran stay queued too.
        pending: [for (final s in state.pending) if (!answered.contains(s.serialCode)) s],
        lastSync: results,
        syncing: false,
      );
      await _save();
      if (results.any((r) => r.status == SyncStatus.credited)) ref.invalidate(walletProvider);
      return true;
    } on ApiException {
      state = state.copyWith(syncing: false);
      return false;
    }
  }

  /// Forgets everything, for when the customer signs out on purpose: the scans
  /// must not be credited to whoever signs in next.
  Future<void> clear() async {
    await _loaded;
    state = const ScanQueueState();
    await _store.clear();
  }

  /// Hides one line of the last upload's outcome.
  Future<void> dismiss(SyncedScan synced) async {
    state = state.copyWith(lastSync: [for (final s in state.lastSync) if (s != synced) s]);
    await _save();
  }
}

final pendingScanCountProvider = Provider<int>((ref) => ref.watch(scanQueueProvider).pending.length);
