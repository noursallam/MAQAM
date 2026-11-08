import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/session/session_provider.dart';
import 'data/api_seminar_repository.dart';
import 'domain/seminar.dart';

/// The seminar running now, for the home page; null when there is none. The
/// home page shows nothing while it loads or if it fails: it is a promotion,
/// not something the customer is waiting for.
final currentSeminarProvider = FutureProvider<Seminar?>((ref) async {
  // Whether the customer has signed up depends on who is asking.
  ref.watch(sessionProvider);
  return ref.watch(seminarRepositoryProvider).current();
});

/// One seminar's page. Signing up replaces it with what the server now holds.
final seminarProvider =
    AsyncNotifierProvider.autoDispose.family<SeminarNotifier, Seminar, int>(SeminarNotifier.new);

class SeminarNotifier extends AsyncNotifier<Seminar> {
  SeminarNotifier(this.id);

  final int id;

  @override
  Future<Seminar> build() {
    ref.watch(sessionProvider);
    return ref.watch(seminarRepositoryProvider).seminar(id);
  }

  /// Throws an [ApiException] if the server refuses, leaving the page as it was.
  Future<void> register() async {
    state = AsyncData(await ref.read(seminarRepositoryProvider).register(id));
    ref.invalidate(currentSeminarProvider);
  }

  /// Reloads quietly, keeping what is on screen until the answer arrives.
  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(seminarRepositoryProvider).seminar(id));
    } on Exception {
      // The next refresh tries again; the page keeps what it had.
    }
  }
}
