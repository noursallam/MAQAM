import 'package:flutter/material.dart';

/// The illustrated icons in `assets/images`, as opposed to the line icons.
enum AppArt {
  /// The loyalty point: shown beside every points balance.
  coin('assets/images/bound-iconn.png'),

  /// A rank.
  rankShield('assets/images/gold-shield.png'),

  /// Progress toward the next rank.
  stars('assets/images/3stars.png'),

  /// Money and payments.
  money('assets/images/money.png');

  const AppArt(this.asset);

  final String asset;
}

/// One of the illustrated icons at a given size. Decorative: the text beside
/// it says what it stands for.
class ArtIcon extends StatelessWidget {
  const ArtIcon(this.art, {super.key, required this.size});

  final AppArt art;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      art.asset,
      width: size,
      height: size,
      // The files are 500 px; decode only what a small icon needs, even on dense screens.
      cacheWidth: (size * MediaQuery.devicePixelRatioOf(context)).ceil(),
      filterQuality: FilterQuality.medium,
      excludeFromSemantics: true,
    );
  }
}
