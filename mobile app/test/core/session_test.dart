import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:maqam_app/core/session/auth_session.dart';
import 'package:maqam_app/core/session/session_provider.dart';
import 'package:maqam_app/core/storage/key_value_store.dart';

void main() {
  test('a started session is saved and restored on the next launch', () async {
    final store = InMemoryKeyValueStore();
    final container = ProviderContainer(
      overrides: [keyValueStoreProvider.overrideWithValue(store)],
    );
    addTearDown(container.dispose);
    expect(container.read(sessionProvider), isNull);

    await container.read(sessionProvider.notifier).start(
          AuthSession(token: 't', expiresAt: DateTime.utc(2030), fullName: 'عميل'),
        );
    expect(container.read(sessionProvider)!.token, 't');

    final restored = await readStoredSession(store, now: DateTime.utc(2029));
    expect(restored!.token, 't');
    expect(restored.fullName, 'عميل');

    await container.read(sessionProvider.notifier).end();
    expect(container.read(sessionProvider), isNull);
    expect(await readStoredSession(store), isNull);
  });

  test('an expired or unreadable saved session is ignored', () async {
    final store = InMemoryKeyValueStore();
    final container = ProviderContainer(
      overrides: [keyValueStoreProvider.overrideWithValue(store)],
    );
    addTearDown(container.dispose);
    await container
        .read(sessionProvider.notifier)
        .start(AuthSession(token: 't', expiresAt: DateTime.utc(2030)));

    expect(await readStoredSession(store, now: DateTime.utc(2031)), isNull);

    store.values['session'] = 'not json';
    expect(await readStoredSession(store), isNull);
  });
}
