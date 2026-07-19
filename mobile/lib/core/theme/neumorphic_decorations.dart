import 'package:flutter/material.dart';
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'app_theme.dart';

class NeumorphicDecorations {
  static InsetBoxDecoration extruded({
    double radius = 16,
    Color color = AppColors.surface,
  }) {
    return InsetBoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(radius),
      boxShadow: const [
        InsetBoxShadow(
          color: AppColors.shadowDark,
          offset: Offset(6, 6),
          blurRadius: 12,
        ),
        InsetBoxShadow(
          color: AppColors.shadowLight,
          offset: Offset(-6, -6),
          blurRadius: 12,
        ),
      ],
    );
  }

  static InsetBoxDecoration sunken({
    double radius = 16,
    Color color = AppColors.surface,
  }) {
    return InsetBoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(radius),
      boxShadow: const [
        InsetBoxShadow(
          color: AppColors.shadowDark,
          offset: Offset(4, 4),
          blurRadius: 8,
          inset: true,
        ),
        InsetBoxShadow(
          color: AppColors.shadowLight,
          offset: Offset(-4, -4),
          blurRadius: 8,
          inset: true,
        ),
      ],
    );
  }

  static InsetBoxDecoration gradient({
    required List<Color> colors,
    double radius = 16,
    bool pressed = false,
  }) {
    return InsetBoxDecoration(
      gradient: LinearGradient(
        colors: colors,
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      ),
      borderRadius: BorderRadius.circular(radius),
      boxShadow: pressed
          ? [
              const InsetBoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(3, 3),
                blurRadius: 6,
                inset: true,
              ),
              const InsetBoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-3, -3),
                blurRadius: 6,
                inset: true,
              ),
            ]
          : [
              const InsetBoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(6, 6),
                blurRadius: 12,
              ),
              const InsetBoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-6, -6),
                blurRadius: 12,
              ),
            ],
    );
  }

  static InsetBoxDecoration circular({
    Color color = AppColors.surface,
    bool pressed = false,
  }) {
    return InsetBoxDecoration(
      color: color,
      shape: BoxShape.circle,
      boxShadow: pressed
          ? const [
              InsetBoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(4, 4),
                blurRadius: 8,
                inset: true,
              ),
              InsetBoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-4, -4),
                blurRadius: 8,
                inset: true,
              ),
            ]
          : const [
              InsetBoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(6, 6),
                blurRadius: 12,
              ),
              InsetBoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-6, -6),
                blurRadius: 12,
              ),
            ],
    );
  }
}
