import 'package:flutter/painting.dart' as painting;
import 'package:flutter/material.dart';
import './inset_box_shadow.dart';

class InsetBoxDecoration extends painting.BoxDecoration {
  const InsetBoxDecoration({
    Color? color,
    DecorationImage? image,
    BoxBorder? border,
    BorderRadiusGeometry? borderRadius,
    List<InsetBoxShadow>? boxShadow,
    Gradient? gradient,
    BlendMode? backgroundBlendMode,
    BoxShape shape = BoxShape.rectangle,
  }) : super(
          color: color,
          border: border,
          borderRadius: borderRadius,
          boxShadow: boxShadow,
          gradient: gradient,
          backgroundBlendMode: backgroundBlendMode,
          shape: shape,
        );

  @override
  InsetBoxDecoration copyWith({
    Color? color,
    DecorationImage? image,
    BoxBorder? border,
    BorderRadiusGeometry? borderRadius,
    List<painting.BoxShadow>? boxShadow,
    Gradient? gradient,
    BlendMode? backgroundBlendMode,
    BoxShape? shape,
  }) {
    return InsetBoxDecoration(
      color: color ?? this.color,
      image: image ?? this.image,
      border: border ?? this.border,
      borderRadius: borderRadius ?? this.borderRadius,
      boxShadow: (boxShadow?.cast<InsetBoxShadow>() ?? this.boxShadow?.cast<InsetBoxShadow>()),
      gradient: gradient ?? this.gradient,
      backgroundBlendMode: backgroundBlendMode ?? this.backgroundBlendMode,
      shape: shape ?? this.shape,
    );
  }

  @override
  InsetBoxDecoration scale(double factor) {
    return InsetBoxDecoration(
      color: Color.lerp(null, color, factor),
      image: image,
      border: BoxBorder.lerp(null, border, factor),
      borderRadius: BorderRadiusGeometry.lerp(null, borderRadius, factor),
      boxShadow: InsetBoxShadow.lerpList(null, boxShadow?.cast<InsetBoxShadow>(), factor),
      gradient: gradient?.scale(factor),
      shape: shape,
    );
  }

  @override
  InsetBoxDecoration? lerpFrom(Decoration? a, double t) {
    if (a == null) return scale(t);
    if (a is InsetBoxDecoration) return InsetBoxDecoration.lerp(a, this, t);
    if (a is painting.BoxDecoration) {
      return InsetBoxDecoration.lerp(InsetBoxDecoration.fromPainting(a), this, t);
    }
    return super.lerpFrom(a, t) as InsetBoxDecoration?;
  }

  @override
  InsetBoxDecoration? lerpTo(Decoration? b, double t) {
    if (b == null) return scale(1.0 - t);
    if (b is InsetBoxDecoration) return InsetBoxDecoration.lerp(this, b, t);
    if (b is painting.BoxDecoration) {
      return InsetBoxDecoration.lerp(this, InsetBoxDecoration.fromPainting(b), t);
    }
    return super.lerpTo(b, t) as InsetBoxDecoration?;
  }

  static InsetBoxDecoration fromPainting(painting.BoxDecoration other) {
    return InsetBoxDecoration(
      color: other.color,
      image: other.image,
      border: other.border,
      borderRadius: other.borderRadius,
      boxShadow: other.boxShadow?.map((e) => InsetBoxShadow.fromPainting(e)).toList(),
      gradient: other.gradient,
      backgroundBlendMode: other.backgroundBlendMode,
      shape: other.shape,
    );
  }

  static InsetBoxDecoration? lerp(InsetBoxDecoration? a, InsetBoxDecoration? b, double t) {
    if (a == null && b == null) return null;
    if (a == null) return b!.scale(t);
    if (b == null) return a.scale(1.0 - t);
    if (t == 0.0) return a;
    if (t == 1.0) return b;

    return InsetBoxDecoration(
      color: Color.lerp(a.color, b.color, t),
      image: t < 0.5 ? a.image : b.image,
      border: BoxBorder.lerp(a.border, b.border, t),
      borderRadius: BorderRadiusGeometry.lerp(a.borderRadius, b.borderRadius, t),
      boxShadow: InsetBoxShadow.lerpList(a.boxShadow?.cast<InsetBoxShadow>(), b.boxShadow?.cast<InsetBoxShadow>(), t),
      gradient: Gradient.lerp(a.gradient, b.gradient, t),
      shape: t < 0.5 ? a.shape : b.shape,
    );
  }

  @override
  BoxPainter createBoxPainter([VoidCallback? onChanged]) {
    return _InsetBoxDecorationPainter(this, onChanged);
  }
}

class _InsetBoxDecorationPainter extends BoxPainter {
  _InsetBoxDecorationPainter(this._decoration, VoidCallback? onChanged) : super(onChanged);
  final InsetBoxDecoration _decoration;

  @override
  void paint(Canvas canvas, Offset offset, ImageConfiguration configuration) {
    assert(configuration.size != null);
    final Rect rect = offset & configuration.size!;
    final TextDirection? textDirection = configuration.textDirection;

    // Outer Shadows
    if (_decoration.boxShadow != null) {
      for (final shadow in _decoration.boxShadow!) {
        if (shadow is InsetBoxShadow && shadow.inset) continue;
        final Paint paint = shadow.toPaint();
        final Rect bounds = rect.shift(shadow.offset).inflate(shadow.spreadRadius);
        _paintBox(canvas, bounds, paint, textDirection);
      }
    }

    // Background
    if (_decoration.color != null || _decoration.gradient != null) {
      final Paint paint = Paint();
      if (_decoration.backgroundBlendMode != null) paint.blendMode = _decoration.backgroundBlendMode!;
      if (_decoration.color != null) paint.color = _decoration.color!;
      if (_decoration.gradient != null) {
        paint.shader = _decoration.gradient!.createShader(rect, textDirection: textDirection);
      }
      _paintBox(canvas, rect, paint, textDirection);
    }

    // Inner Shadows
    if (_decoration.boxShadow != null) {
      for (final shadow in _decoration.boxShadow!) {
        if (shadow is! InsetBoxShadow || !shadow.inset) continue;
        final color = shadow.color;
        final borderRadius = (_decoration.borderRadius ?? (_decoration.shape == BoxShape.circle ? BorderRadius.circular(rect.longestSide) : BorderRadius.zero)).resolve(textDirection);
        final clipRRect = borderRadius.toRRect(rect);
        final innerRect = rect.deflate(shadow.spreadRadius);
        final innerRRect = borderRadius.toRRect(innerRect);

        canvas.save();
        canvas.clipRRect(clipRRect);
        final outerRect = rect.inflate(shadow.blurRadius).shift(shadow.offset);
        canvas.drawDRRect(
          RRect.fromRectAndRadius(rect.inflate(shadow.blurRadius * 2), Radius.zero),
          innerRRect.shift(shadow.offset),
          Paint()
            ..color = color
            ..maskFilter = MaskFilter.blur(BlurStyle.normal, shadow.blurSigma),
        );
        canvas.restore();
      }
    }

    // Border
    _decoration.border?.paint(canvas, rect, shape: _decoration.shape, borderRadius: _decoration.borderRadius?.resolve(textDirection), textDirection: textDirection);
  }

  void _paintBox(Canvas canvas, Rect rect, Paint paint, TextDirection? textDirection) {
    if (_decoration.shape == BoxShape.circle) {
      canvas.drawCircle(rect.center, rect.shortestSide / 2.0, paint);
    } else {
      if (_decoration.borderRadius == null) {
        canvas.drawRect(rect, paint);
      } else {
        canvas.drawRRect(_decoration.borderRadius!.resolve(textDirection).toRRect(rect), paint);
      }
    }
  }
}
