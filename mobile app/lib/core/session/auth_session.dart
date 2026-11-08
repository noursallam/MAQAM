/// A signed-in customer's access to the API.
class AuthSession {
  const AuthSession({
    required this.token,
    this.expiresAt,
    this.fullName,
    this.phone,
    this.boundMerchantId,
    this.boundMerchantName,
    this.governorate,
    this.area,
  });

  final String token;
  final DateTime? expiresAt;
  final String? fullName;
  final String? phone;
  final int? boundMerchantId;
  final String? boundMerchantName;
  final String? governorate;
  final String? area;

  bool get isBoundToMerchant => boundMerchantId != null;

  bool isExpiredAt(DateTime now) => expiresAt != null && !expiresAt!.isAfter(now);

  AuthSession copyWith({
    String? fullName,
    int? boundMerchantId,
    String? boundMerchantName,
    String? governorate,
    String? area,
    bool clearBoundMerchant = false,
  }) =>
      AuthSession(
        token: token,
        expiresAt: expiresAt,
        fullName: fullName ?? this.fullName,
        phone: phone,
        boundMerchantId: clearBoundMerchant ? null : (boundMerchantId ?? this.boundMerchantId),
        boundMerchantName: clearBoundMerchant ? null : (boundMerchantName ?? this.boundMerchantName),
        governorate: governorate ?? this.governorate,
        area: area ?? this.area,
      );
}
