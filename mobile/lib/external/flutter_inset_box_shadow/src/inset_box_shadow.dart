import 'dart:ui' as ui show lerpDouble;
import 'dart:math' as math;
import 'package:flutter/foundation.dart';
import 'package:flutter/painting.dart' as painting;
import 'package:flutter/material.dart';

class InsetBoxShadow extends painting.BoxShadow {
  const InsetBoxShadow({
    Color color = const Color(0xFF000000),
    Offset offset = Offset.zero,
    double blurRadius = 0.0,
    double spreadRadius = 0.0,
    BlurStyle blurStyle = BlurStyle.normal,
    this.inset = false,
  }) : super(
          color: color,
          offset: offset,
          blurRadius: blurRadius,
          spreadRadius: spreadRadius,
          blurStyle: blurStyle,
        );

  final bool inset;

  @override
  InsetBoxShadow scale(double factor) {
    return InsetBoxShadow(
      color: color,
      offset: offset * factor,
      blurRadius: blurRadius * factor,
      spreadRadius: spreadRadius * factor,
      blurStyle: blurStyle,
      inset: inset,
    );
  }

  static InsetBoxShadow? lerp(InsetBoxShadow? a, InsetBoxShadow? b, double t) {
    if (a == null && b == null) return null;
    if (a == null) return b!.scale(t);
    if (b == null) return a.scale(1.0 - t);

    final blurStyle = a.blurStyle == BlurStyle.normal ? b.blurStyle : a.blurStyle;

    if (a.inset != b.inset) {
      return InsetBoxShadow(
        color: lerpColorWithPivot(a.color, b.color, t),
        offset: lerpOffsetWithPivot(a.offset, b.offset, t),
        blurRadius: lerpDoubleWithPivot(a.blurRadius, b.blurRadius, t),
        spreadRadius: lerpDoubleWithPivot(a.spreadRadius, b.spreadRadius, t),
        blurStyle: blurStyle,
        inset: t >= 0.5 ? b.inset : a.inset,
      );
    }

    return InsetBoxShadow(
      color: Color.lerp(a.color, b.color, t) ?? const Color(0x00000000),
      offset: Offset.lerp(a.offset, b.offset, t) ?? Offset.zero,
      blurRadius: ui.lerpDouble(a.blurRadius, b.blurRadius, t) ?? 0.0,
      spreadRadius: ui.lerpDouble(a.spreadRadius, b.spreadRadius, t) ?? 0.0,
      blurStyle: blurStyle,
      inset: b.inset,
    );
  }

  static InsetBoxShadow fromPainting(painting.BoxShadow other) {
    if (other is InsetBoxShadow) return other;
    return InsetBoxShadow(
      color: other.color,
      offset: other.offset,
      blurRadius: other.blurRadius,
      spreadRadius: other.spreadRadius,
      blurStyle: other.blurStyle,
    );
  }

  static List<InsetBoxShadow>? lerpList(List<InsetBoxShadow>? a, List<InsetBoxShadow>? b, double t) {
    if (a == null && b == null) return null;
    a ??= <InsetBoxShadow>[];
    b ??= <InsetBoxShadow>[];
    final int commonLength = math.min(a.length, b.length);
    return <InsetBoxShadow>[
      for (int i = 0; i < commonLength; i += 1) InsetBoxShadow.lerp(a[i], b[i], t)!,
      for (int i = commonLength; i < a.length; i += 1) a[i].scale(1.0 - t),
      for (int i = commonLength; i < b.length; i += 1) b[i].scale(t),
    ];
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    if (other is! InsetBoxShadow) return false;
    return other.color == color &&
        other.offset == offset &&
        other.blurRadius == blurRadius &&
        other.spreadRadius == spreadRadius &&
        other.blurStyle == blurStyle &&
        other.inset == inset;
  }

  @override
  int get hashCode => Object.hash(color, offset, blurRadius, spreadRadius, blurStyle, inset);
}

double lerpDoubleWithPivot(num? a, num? b, double t) {
  if (t < 0.5) return ui.lerpDouble(a, 0, t * 2) ?? 0.0;
  return ui.lerpDouble(0, b, (t - 0.5) * 2) ?? 0.0;
}

Offset lerpOffsetWithPivot(Offset? a, Offset? b, double t) {
  if (t < 0.5) return Offset.lerp(a, Offset.zero, t * 2) ?? Offset.zero;
  return Offset.lerp(Offset.zero, b, (t - 0.5) * 2) ?? Offset.zero;
}

Color lerpColorWithPivot(Color? a, Color? b, double t) {
  if (t < 0.5) return Color.lerp(a, a?.withOpacity(0), t * 2) ?? const Color(0x00000000);
  return Color.lerp(b?.withOpacity(0), b, (t - 0.5) * 2) ?? const Color(0x00000000);
}
