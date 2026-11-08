/// The code a customer must send to MAQAM's WhatsApp number to prove they own
/// the phone number they entered.
class WhatsappChallenge {
  const WhatsappChallenge({
    required this.id,
    required this.code,
    required this.whatsappUrl,
    required this.validFor,
    required this.pollInterval,
  });

  final String id;
  final String code;

  /// Opens WhatsApp with the code already typed.
  final Uri whatsappUrl;
  final Duration validFor;
  final Duration pollInterval;
}

/// What the flow is for: a normal sign-in, replacing a forgotten password
/// (which signs in the same way, then asks for a new password), or moving a
/// signed-in account to a new number (proved from that number).
enum SignInPurpose { signIn, resetPassword, changePhone }

/// Handed to the code-entry step once the server has sent the sign-in code.
class OtpTicket {
  const OtpTicket({
    required this.challengeId,
    required this.validFor,
    this.purpose = SignInPurpose.signIn,
  });

  final String challengeId;
  final Duration validFor;
  final SignInPurpose purpose;
}
