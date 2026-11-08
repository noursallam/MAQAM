/// What claiming a pack's code gave.
class ScanResult {
  const ScanResult({
    required this.pointsAwarded,
    required this.newBalance,
    this.merchantName,
    this.merchantPoints = 0,
    this.accountFrozen = false,
  });

  final int pointsAwarded;
  final int newBalance;

  /// The merchant credited for the sale, when one was named.
  final String? merchantName;
  final int merchantPoints;

  /// The fraud check stopped the account with this scan; its token is gone.
  final bool accountFrozen;
}

/// A code scanned without a connection, kept on the phone until it is uploaded.
class QueuedScan {
  const QueuedScan({
    required this.serialCode,
    required this.scannedAt,
    this.merchantCode,
    this.merchantName,
  });

  final String serialCode;
  final DateTime scannedAt;
  final String? merchantCode;
  final String? merchantName;
}

enum SyncStatus { credited, alreadyCredited, rejected }

/// What the server did with one queued scan.
class SyncedScan {
  const SyncedScan({
    required this.scan,
    required this.status,
    this.points = 0,
    this.message,
  });

  final QueuedScan scan;
  final SyncStatus status;
  final int points;

  /// Why it was rejected, in the customer's language.
  final String? message;
}

/// The last 4 characters of a code, the part shown in lists.
String serialTail(String serial) =>
    serial.length <= 4 ? serial : serial.substring(serial.length - 4);

/// A merchant the customer has credited before, offered as a shortcut.
class RecentMerchant {
  const RecentMerchant({required this.code, required this.name});

  final String code;
  final String name;
}

/// The serial printed inside a pack, from whatever the camera read: the bare
/// code, or a link that ends with it.
String serialFromScan(String raw) {
  final text = raw.trim();
  final uri = Uri.tryParse(text);
  if (uri != null && uri.hasScheme && uri.pathSegments.isNotEmpty) {
    return uri.queryParameters['code'] ?? uri.pathSegments.last;
  }
  return text;
}

abstract interface class ScanRepository {
  /// Claims the code: awards its points and uses it up.
  Future<ScanResult> scan({required String serialCode, String? merchantCode});

  Future<List<RecentMerchant>> recentMerchants();

  /// Uploads scans made offline. Each one is credited or rejected on its own;
  /// sending the same scan twice never credits it twice.
  Future<List<SyncedScan>> sync(List<QueuedScan> scans);
}
