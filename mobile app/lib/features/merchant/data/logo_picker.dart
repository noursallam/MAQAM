import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../domain/merchant.dart';

/// Lets the customer choose a logo from their photos. Null when they cancel.
typedef LogoPicker = Future<LogoImage?> Function();

Future<LogoImage?> pickLogoFromGallery() async {
  // Scaled and recompressed so a phone photo stays under the server's 2 MB limit.
  final file = await ImagePicker().pickImage(
    source: ImageSource.gallery,
    maxWidth: 1024,
    maxHeight: 1024,
    imageQuality: 85,
  );
  if (file == null) return null;
  return LogoImage(bytes: await file.readAsBytes(), fileName: file.name);
}

/// Replaced in tests, which have no photo library.
final logoPickerProvider = Provider<LogoPicker>((ref) => pickLogoFromGallery);
