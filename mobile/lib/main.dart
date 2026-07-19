import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'core/theme/app_theme.dart';
import 'core/localization/app_localizations.dart';
import 'core/security/security_helpers.dart';
import 'core/network/providers.dart';
import 'features/auth/login_screen.dart';
import 'features/auth/pin_screen.dart';
import 'features/admin/admin_dashboard.dart';
import 'features/finance/finance_dashboard.dart';
import 'features/manager/manager_dashboard.dart';
import 'features/employee/employee_dashboard.dart';
import 'models/models.dart';

void main() {
  runApp(
    const ProviderScope(
      child: PrivacyShield(
        child: BlackDoorApp(),
      ),
    ),
  );
}

class BlackDoorApp extends ConsumerWidget {
  const BlackDoorApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);

    return MaterialApp(
      title: 'Black Door Mobile',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      localizationsDelegates: const [
        AppLocalizationsDelegate(),
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('uz', ''), // Uzbekistan language (latin)
      ],
      home: authState.isLoading
          ? const Scaffold(
              body: Center(
                child: CircularProgressIndicator(color: AppColors.success),
              ),
            )
          : authState.user == null
              ? const LoginScreen()
              : _buildRoleMainScreen(authState.user!, ref),
    );
  }

  Widget _buildRoleMainScreen(User user, WidgetRef ref) {
    switch (user.role) {
      case UserRole.superAdmin:
        // Admin starts with dashboard but has access to pin-verify to enter finance
        return const AdminDashboard();
      case UserRole.financier:
        // Financier must verify PIN first
        final pinState = ref.watch(pinProvider);
        if (pinState.isVerified) {
          return const FinanceDashboard();
        } else {
          return PinScreen(
            onSuccess: () {},
          );
        }
      case UserRole.manager:
        return const ManagerDashboard();
      case UserRole.employee:
        return const EmployeeDashboard();
    }
  }
}
