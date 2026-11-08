import 'technician.dart';
export 'technician.dart';

/// The signed-in customer's merchant profile.
class MerchantProfile {
  const MerchantProfile({
    required this.code,
    required this.businessName,
    required this.approved,
    this.businessAddress,
    this.logoUrl,
    this.ownerName,
    this.joinedAt,
    this.stats = const MerchantStats(),
    this.recentScans = const [],
  });

  /// What customers type when they scan, to credit this merchant.
  final String code;
  final String businessName;
  final String? businessAddress;
  final String? logoUrl;

  /// False until MAQAM reviews the request; the code does not work before then.
  final bool approved;
  final String? ownerName;
  final DateTime? joinedAt;
  final MerchantStats stats;

  /// The latest scans credited to the code, newest first.
  final List<MerchantScan> recentScans;
}

class MerchantStats {
  const MerchantStats({
    this.totalScans = 0,
    this.totalPoints = 0,
    this.scansThisMonth = 0,
    this.pointsThisMonth = 0,
    this.uniqueCustomers = 0,
    this.lastScanAt,
  });

  final int totalScans;
  final int totalPoints;
  final int scansThisMonth;
  final int pointsThisMonth;
  final int uniqueCustomers;
  final DateTime? lastScanAt;
}

/// A customer's scan that credited the merchant's code. The phone arrives partly hidden.
class MerchantScan {
  const MerchantScan({
    required this.id,
    required this.points,
    this.customerName,
    this.customerPhone,
    this.prize,
    this.scannedAt,
  });

  final int id;
  final int points;
  final String? customerName;
  final String? customerPhone;
  final String? prize;
  final DateTime? scannedAt;
}

/// One page of the code's scans.
class MerchantScanPage {
  const MerchantScanPage({required this.items, required this.hasMore});

  final List<MerchantScan> items;
  final bool hasMore;
}

/// An image chosen on the device, ready to upload.
class LogoImage {
  const LogoImage({required this.bytes, required this.fileName});

  final List<int> bytes;
  final String fileName;
}

abstract interface class MerchantRepository {
  /// The profile, or null when the customer has not asked to be a merchant.
  Future<MerchantProfile?> profile();

  /// Asks to become a merchant; the profile waits for MAQAM's approval.
  Future<MerchantProfile> apply({required String businessName, String? businessAddress, LogoImage? logo});

  Future<MerchantProfile> uploadLogo(LogoImage logo);

  /// Every scan credited to the code, newest first, [page] counting from 1.
  Future<MerchantScanPage> scans({int page = 1});

  /// Technicians bound to this merchant.
  Future<List<Technician>> technicians();

  /// Detail for one technician.
  Future<TechnicianDetail> technician(int id);

  /// Scans by a specific technician under this merchant.
  Future<MerchantScanPage> technicianScans(int technicianId, {int page = 1});

  /// Deduct points from a technician (for returns).
  Future<void> deductPoints(int technicianId, {required int amount, required String reason});

  /// All point transactions for this merchant's technicians.
  Future<List<MerchantTransaction>> transactions();
}
