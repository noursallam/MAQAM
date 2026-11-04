class Technician {
  const Technician({
    required this.id,
    required this.fullName,
    this.phone,
    required this.totalPoints,
    required this.totalScans,
    this.lastScanAt,
  });

  final int id;
  final String fullName;
  final String? phone;
  final int totalPoints;
  final int totalScans;
  final DateTime? lastScanAt;
}

class TechnicianDetail {
  const TechnicianDetail({
    required this.id,
    required this.fullName,
    this.phone,
    required this.totalPoints,
    required this.totalScans,
    required this.currentBalance,
    this.lastScanAt,
  });

  final int id;
  final String fullName;
  final String? phone;
  final int totalPoints;
  final int totalScans;
  final int currentBalance;
  final DateTime? lastScanAt;
}

class MerchantTransaction {
  const MerchantTransaction({
    required this.id,
    required this.customerName,
    this.customerPhone,
    required this.type,
    required this.amount,
    this.description,
    this.createdAt,
  });

  final int id;
  final String customerName;
  final String? customerPhone;
  final String type;
  final int amount;
  final String? description;
  final DateTime? createdAt;
}
