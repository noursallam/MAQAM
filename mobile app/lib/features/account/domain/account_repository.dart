abstract interface class AccountRepository {
  /// Saves the name the customer is greeted by and that goes on their orders.
  Future<void> updateName(String fullName);

  /// Erases the account for good: personal data, points, rewards and addresses.
  Future<void> deleteAccount();

  /// Binds this technician to a merchant permanently (first time only).
  /// Returns the merchant's id and business name.
  Future<({int id, String name})> bindMerchant(String merchantCode);

  /// Requests a change to a different merchant. Admin must approve.
  Future<void> requestMerchantChange(String merchantCode);

  /// Returns the latest merchant change request status, or null if none.
  Future<String?> merchantChangeStatus();
}
