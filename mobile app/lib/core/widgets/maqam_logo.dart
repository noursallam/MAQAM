import 'package:flutter/material.dart';

class MaqamLogo extends StatelessWidget {
  const MaqamLogo({super.key, this.height = 20, this.color});

  final double height;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/images/maqam_logo.png',
      height: height,
      color: color ?? Theme.of(context).colorScheme.onSurface,
      semanticLabel: 'MAQAM',
    );
  }
}
