/// A live event with a prize draw among the customers who sign up while it runs.
class Seminar {
  const Seminar({
    required this.id,
    required this.title,
    required this.startsAt,
    required this.endsAt,
    required this.isLive,
    this.description,
    this.location,
    this.imageUrl,
    this.registrations = 0,
    this.isRegistered = false,
    this.draws = const [],
  });

  final int id;
  final String title;
  final String? description;
  final String? location;
  final String? imageUrl;
  final DateTime startsAt;
  final DateTime endsAt;

  /// Signing up is open only while it runs.
  final bool isLive;
  final int registrations;
  final bool isRegistered;

  /// One per prize, in the order they are drawn.
  final List<SeminarDraw> draws;

  /// The prize this customer won, if any.
  SeminarDraw? get myWin => draws.where((d) => d.isMine).firstOrNull;
}

class SeminarDraw {
  const SeminarDraw({
    required this.position,
    required this.prize,
    this.isDrawn = false,
    this.winnerName,
    this.isMine = false,
  });

  final int position;
  final String prize;
  final bool isDrawn;

  /// First name and the end of the phone number, once drawn.
  final String? winnerName;

  /// Won by the customer looking at it.
  final bool isMine;
}

abstract interface class SeminarRepository {
  /// The seminar running now, or null.
  Future<Seminar?> current();

  Future<Seminar> seminar(int id);

  /// Enters the signed-in customer into the draw.
  Future<Seminar> register(int id);
}
