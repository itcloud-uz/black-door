import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const Color background = Color(0xFFEEF2F7);
  static const Color surface = Color(0xFFEEF2F7);
  
  static const Color shadowDark = Color(0xFFC9D2DE);
  static const Color shadowLight = Color(0xFFFFFFFF);
  
  static const Color textPrimary = Color(0xFF2C3E50);
  static const Color textMuted = Color(0xFF7F8C8D);
  
  // Gradients
  static const Color greenStart = Color(0xFF58D68D);
  static const Color greenEnd = Color(0xFF2EC4B6);
  static const List<Color> greenGradient = [greenStart, greenEnd];
  
  static const Color redStart = Color(0xFFFF8A7A);
  static const Color redEnd = Color(0xFFE74C3C);
  static const List<Color> redGradient = [redStart, redEnd];
  
  static const Color blueStart = Color(0xFF5DADE2);
  static const Color blueEnd = Color(0xFF2E86C1);
  static const List<Color> blueGradient = [blueStart, blueEnd];

  // Status colors
  static const Color success = Color(0xFF27AE60);
  static const Color warning = Color(0xFFF1C40F);
  static const Color danger = Color(0xFFE74C3C);
  static const Color info = Color(0xFF2980B9);
}

class AppTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: AppColors.background,
      colorScheme: const ColorScheme.light(
        primary: AppColors.success,
        secondary: AppColors.blueEnd,
        surface: AppColors.surface,
        error: AppColors.danger,
      ),
      textTheme: TextTheme(
        displayLarge: GoogleFonts.nunito(fontSize: 32, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
        displayMedium: GoogleFonts.nunito(fontSize: 28, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
        titleLarge: GoogleFonts.nunito(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
        titleMedium: GoogleFonts.nunito(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
        bodyLarge: GoogleFonts.nunito(fontSize: 16, color: AppColors.textPrimary),
        bodyMedium: GoogleFonts.nunito(fontSize: 14, color: AppColors.textMuted),
        labelLarge: GoogleFonts.nunito(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
      ),
    );
  }
}
