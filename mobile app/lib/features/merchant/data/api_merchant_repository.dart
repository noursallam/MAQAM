import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../domain/merchant.dart';

class ApiMerchantRepository implements MerchantRepository {
  const ApiMerchantRepository(this._api);

  final ApiClient _api;

  @override
  Future<MerchantProfile?> profile() async {
    try {
      return _profile(await _api.get('/merchant') as Map<String, dynamic>);
    } on ApiException catch (e) {
      if (e.code == ApiErrorCodes.notFound) return null;
      rethrow;
    }
  }

  @override
  Future<MerchantProfile> apply({
    required String businessName,
    String? businessAddress,
    LogoImage? logo,
  }) async {
    final fields = {
      'business_name': businessName,
      if (businessAddress != null && businessAddress.isNotEmpty) 'business_address': businessAddress,
    };
    final data = logo == null
        ? await _api.post('/merchant/apply', body: fields)
        : await _api.postMultipart(
            '/merchant/apply',
            fields: fields,
            fileField: 'logo',
            fileBytes: logo.bytes,
            fileName: logo.fileName,
          );
    return _profile(data as Map<String, dynamic>);
  }

  @override
  Future<MerchantProfile> uploadLogo(LogoImage logo) async {
    final data = await _api.postMultipart(
      '/merchant/logo',
      fileField: 'logo',
      fileBytes: logo.bytes,
      fileName: logo.fileName,
    );
    return _profile(data as Map<String, dynamic>);
  }

  @override
  Future<MerchantScanPage> scans({int page = 1}) async {
    final result = await _api.getPage('/merchant/scans', query: {'page': '$page', 'per_page': '20'});
    final lastPage = result.meta['last_page'] as int? ?? page;
    return MerchantScanPage(items: result.items.map(_scan).toList(), hasMore: page < lastPage);
  }

  static MerchantProfile _profile(Map<String, dynamic> json) {
    final stats = json['stats'] as Map<String, dynamic>?;
    final recent = json['recent_scans'];
    return MerchantProfile(
      code: json['merchant_code'] as String? ?? '',
      businessName: json['business_name'] as String? ?? '',
      businessAddress: json['business_address'] as String?,
      logoUrl: json['logo_url'] as String?,
      approved: json['is_approved'] == true,
      ownerName: json['owner_name'] as String?,
      joinedAt: _date(json['created_at']),
      stats: stats == null
          ? const MerchantStats()
          : MerchantStats(
              totalScans: stats['total_scans'] as int? ?? 0,
              totalPoints: stats['total_points'] as int? ?? 0,
              scansThisMonth: stats['scans_this_month'] as int? ?? 0,
              pointsThisMonth: stats['points_this_month'] as int? ?? 0,
              uniqueCustomers: stats['unique_customers'] as int? ?? 0,
              lastScanAt: _date(stats['last_scan_at']),
            ),
      recentScans: recent is List ? recent.cast<Map<String, dynamic>>().map(_scan).toList() : const [],
    );
  }

  static MerchantScan _scan(Map<String, dynamic> json) => MerchantScan(
        id: json['id'] as int,
        points: json['points'] as int? ?? 0,
        customerName: json['customer_name'] as String?,
        customerPhone: json['customer_phone'] as String?,
        prize: json['prize'] as String?,
        scannedAt: _date(json['scanned_at']),
      );

  @override
  Future<List<Technician>> technicians() async {
    final data = await _api.get('/merchant/technicians') as List;
    return data.cast<Map<String, dynamic>>().map(_technician).toList();
  }

  @override
  Future<TechnicianDetail> technician(int id) async {
    final data = await _api.get('/merchant/technicians/$id') as Map<String, dynamic>;
    return TechnicianDetail(
      id: data['id'] as int,
      fullName: data['full_name'] as String? ?? '',
      phone: data['phone_number'] as String?,
      totalPoints: data['total_points'] as int? ?? 0,
      totalScans: data['total_scans'] as int? ?? 0,
      currentBalance: data['current_balance'] as int? ?? 0,
      lastScanAt: _date(data['last_scan_at']),
    );
  }

  @override
  Future<MerchantScanPage> technicianScans(int technicianId, {int page = 1}) async {
    final result = await _api.getPage(
      '/merchant/technicians/$technicianId/scans',
      query: {'page': '$page', 'per_page': '20'},
    );
    final lastPage = result.meta['last_page'] as int? ?? page;
    return MerchantScanPage(items: result.items.map(_scan).toList(), hasMore: page < lastPage);
  }

  @override
  Future<void> deductPoints(int technicianId, {required int amount, required String reason}) =>
      _api.post('/merchant/technicians/$technicianId/deduct-points', body: {
        'amount': amount,
        'reason': reason,
      });

  @override
  Future<List<MerchantTransaction>> transactions() async {
    final data = await _api.get('/merchant/transactions') as List;
    return data.cast<Map<String, dynamic>>().map(_transaction).toList();
  }

  static Technician _technician(Map<String, dynamic> json) => Technician(
        id: json['id'] as int,
        fullName: json['full_name'] as String? ?? '',
        phone: json['phone_number'] as String?,
        totalPoints: json['total_points'] as int? ?? 0,
        totalScans: json['total_scans'] as int? ?? 0,
        lastScanAt: _date(json['last_scan_at']),
      );

  static MerchantTransaction _transaction(Map<String, dynamic> json) => MerchantTransaction(
        id: json['id'] as int,
        customerName: json['customer_name'] as String? ?? '',
        customerPhone: json['customer_phone'] as String?,
        type: json['type'] as String? ?? '',
        amount: json['amount'] as int? ?? 0,
        description: json['description'] as String?,
        createdAt: _date(json['created_at']),
      );

  static DateTime? _date(Object? value) => value is String ? DateTime.tryParse(value)?.toLocal() : null;
}

final merchantRepositoryProvider = Provider<MerchantRepository>(
  (ref) => ApiMerchantRepository(ref.watch(apiClientProvider)),
);
