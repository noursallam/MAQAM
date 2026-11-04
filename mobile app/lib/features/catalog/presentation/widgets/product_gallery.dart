import 'package:flutter/material.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../l10n/l10n.dart';

const _placeholder = ColoredBox(
  color: AppColors.surfaceMuted,
  child: Center(child: Icon(Icons.image_outlined, size: 40, color: AppColors.textSecondary)),
);

Widget _image(String url, {BoxFit fit = BoxFit.cover}) => Image.network(
      url,
      fit: fit,
      excludeFromSemantics: true,
      loadingBuilder: (context, child, progress) => progress == null ? child : _placeholder,
      errorBuilder: (_, _, _) => _placeholder,
    );

/// The product's photos: swipe between them, pick one from the thumbnails, or
/// tap to open it full screen and zoom.
class ProductGallery extends StatefulWidget {
  const ProductGallery({
    super.key,
    required this.images,
    required this.cartCount,
    required this.onOpenCart,
  });

  final List<String> images;
  final int cartCount;
  final VoidCallback onOpenCart;

  @override
  State<ProductGallery> createState() => _ProductGalleryState();
}

class _ProductGalleryState extends State<ProductGallery> {
  final _pages = PageController();
  int _index = 0;

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  void _show(int index) {
    if (MediaQuery.disableAnimationsOf(context)) {
      _pages.jumpToPage(index);
    } else {
      _pages.animateToPage(index,
          duration: const Duration(milliseconds: 250), curve: Curves.easeOut);
    }
  }

  Future<void> _openFullScreen() async {
    final index = await Navigator.of(context).push<int>(
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (context) => _FullScreenGallery(images: widget.images, initialIndex: _index),
      ),
    );
    // Come back to whichever photo was being looked at.
    if (index != null && mounted) _pages.jumpToPage(index);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final images = widget.images;

    return Column(
      children: [
        AspectRatio(
          aspectRatio: 1.2,
          child: Stack(
            fit: StackFit.expand,
            children: [
              if (images.isEmpty)
                _placeholder
              else
                PageView.builder(
                  controller: _pages,
                  itemCount: images.length,
                  onPageChanged: (i) => setState(() => _index = i),
                  itemBuilder: (context, i) => Semantics(
                    button: true,
                    label: l10n.productZoom,
                    child: GestureDetector(onTap: _openFullScreen, child: _image(images[i])),
                  ),
                ),
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _RoundButton(
                        tooltip: l10n.back,
                        icon: Icons.arrow_back,
                        onPressed: () => Navigator.maybePop(context),
                      ),
                      Badge.count(
                        count: widget.cartCount,
                        isLabelVisible: widget.cartCount > 0,
                        backgroundColor: AppColors.navy,
                        child: _RoundButton(
                          tooltip: l10n.cart,
                          icon: Icons.shopping_cart_outlined,
                          onPressed: widget.onOpenCart,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (images.length > 1)
                PositionedDirectional(
                  bottom: AppSpacing.md,
                  end: AppSpacing.md,
                  child: Semantics(
                    label: l10n.productGallery(_index + 1, images.length),
                    child: ExcludeSemantics(
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          color: AppColors.navy.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(AppSpacing.md),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
                          child: Text(
                            '${_index + 1} / ${images.length}',
                            textDirection: TextDirection.ltr,
                            style: const TextStyle(color: Colors.white, fontSize: 12),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
        if (images.length > 1)
          SizedBox(
            height: 76,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
              itemCount: images.length,
              separatorBuilder: (_, _) => const SizedBox(width: AppSpacing.sm),
              itemBuilder: (context, i) => Semantics(
                button: true,
                selected: i == _index,
                label: l10n.productGallery(i + 1, images.length),
                child: GestureDetector(
                  onTap: () => _show(i),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 150),
                    width: 52,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(AppSpacing.md),
                      border: Border.all(
                        color: i == _index ? AppColors.navy : AppColors.border,
                        width: i == _index ? 2 : 1,
                      ),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: _image(images[i]),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _RoundButton extends StatelessWidget {
  const _RoundButton({required this.tooltip, required this.icon, required this.onPressed});

  final String tooltip;
  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      tooltip: tooltip,
      onPressed: onPressed,
      style: IconButton.styleFrom(
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.navy,
        fixedSize: const Size.square(48),
      ),
      icon: Icon(icon, size: 20),
    );
  }
}

/// The photos on a black background, where each can be pinched to zoom.
class _FullScreenGallery extends StatefulWidget {
  const _FullScreenGallery({required this.images, required this.initialIndex});

  final List<String> images;
  final int initialIndex;

  @override
  State<_FullScreenGallery> createState() => _FullScreenGalleryState();
}

class _FullScreenGalleryState extends State<_FullScreenGallery> {
  late final _pages = PageController(initialPage: widget.initialIndex);
  late int _index = widget.initialIndex;

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        leading: IconButton(
          tooltip: l10n.close,
          onPressed: () => Navigator.pop(context, _index),
          icon: const Icon(Icons.close),
        ),
        title: widget.images.length > 1
            ? Text(
                '${_index + 1} / ${widget.images.length}',
                textDirection: TextDirection.ltr,
                style: const TextStyle(color: Colors.white, fontSize: 15),
              )
            : null,
      ),
      body: PageView.builder(
        controller: _pages,
        itemCount: widget.images.length,
        onPageChanged: (i) => setState(() => _index = i),
        itemBuilder: (context, i) => InteractiveViewer(
          maxScale: 4,
          child: Center(child: _image(widget.images[i], fit: BoxFit.contain)),
        ),
      ),
    );
  }
}
