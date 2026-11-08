/// A promotional banner managed from the admin panel.
class PromoBanner {
  const PromoBanner({required this.id, required this.title, this.imageUrl});

  final int id;
  final String title;
  final String? imageUrl;
}
