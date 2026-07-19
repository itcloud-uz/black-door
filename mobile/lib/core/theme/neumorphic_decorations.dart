import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'app_theme.dart';

class NeumorphicDecorations {
  static BoxDecoration extruded({
    double radius = 16,
    Color color = AppColors.surface,
  }) {
    return BoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(radius),
      boxShadow: const [
        BoxShadow(
          color: AppColors.shadowDark,
          offset: Offset(6, 6),
          blurRadius: 12,
        ),
        BoxShadow(
          color: AppColors.shadowLight,
          offset: Offset(-6, -6),
          blurRadius: 12,
        ),
      ],
    );
  }

  static BoxDecoration sunken({
    double radius = 16,
    Color color = AppColors.surface,
  }) {
    return BoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(radius),
      boxShadow: const [
        BoxShadow(
          color: AppColors.shadowDark,
          offset: Offset(4, 4),
          blurRadius: 8,
          inset: true,
        ),
        BoxShadow(
          color: AppColors.shadowLight,
          offset: Offset(-4, -4),
          blurRadius: 8,
          inset: true,
        ),
      ],
    );
  }

  static BoxDecoration gradient({
    required List<Color> colors,
    double radius = 16,
    bool pressed = false,
  }) {
    return BoxDecoration(
      gradient: LinearGradient(
        colors: colors,
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      ),
      borderRadius: BorderRadius.circular(radius),
      boxShadow: pressed
          ? [
              const BoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(3, 3),
                blurRadius: 6,
                inset: true,
              ),
              const BoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-3, -3),
                blurRadius: 6,
                inset: true,
              ),
            ]
          : [
              const BoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(6, 6),
                blurRadius: 12,
              ),
              const BoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-6, -6),
                blurRadius: 12,
              ),
            ],
    );
  }

  static BoxDecoration circular({
    Color color = AppColors.surface,
    bool pressed = false,
  }) {
    return BoxDecoration(
      color: color,
      shape: BoxShape.circle,
      boxShadow: pressed
          ? const [
              BoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(4, 4),
                blurRadius: 8,
                inset: true,
              ),
              BoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-4, -4),
                blurRadius: 8,
                inset: true,
              ),
            ]
          : const [
              BoxShadow(
                color: AppColors.shadowDark,
                offset: Offset(6, 6),
                blurRadius: 12,
              ),
              BoxShadow(
                color: AppColors.shadowLight,
                offset: Offset(-6, -6),
                blurRadius: 12,
              ),
            ],
    );
  }
}
