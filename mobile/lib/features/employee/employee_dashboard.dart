import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/neumorphic_decorations.dart';
import '../auth/profile_screen.dart';

class EmployeeDashboard extends ConsumerStatefulWidget {
  const EmployeeDashboard({Key? key}) : super(key: key);

  @override
  ConsumerState<EmployeeDashboard> createState() => _EmployeeDashboardState();
}

class _EmployeeDashboardState extends ConsumerState<EmployeeDashboard> {
  bool _isLoading = false;

  void _logout() {
    ref.read(authProvider.notifier).logout();
  }

  void _openProfile() {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const ProfileScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).user;
    final textTheme = Theme.of(context).textTheme;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        title: const Text('ISHCHI VA OMOBR PANELI', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_outline),
            onPressed: _openProfile,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Welcome Card
              NeumorphicCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Xush kelibsiz,', style: textTheme.bodyMedium),
                    const SizedBox(height: 4),
                    Text(user?.name ?? 'Xodim', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: NeumorphicDecorations.sunken(radius: 6),
                      child: const Text(
                        'XODIM ROLLI KIRISH',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.success),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 32),

              // Action Buttons
              const Text('KUNLIK AMALLAR', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
              const SizedBox(height: 16),

              NeumorphicButton(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Ishga kelish qayd etildi.')),
                  );
                },
                child: const Row(
                  children: [
                    Icon(Icons.play_arrow_outlined, color: AppColors.success),
                    SizedBox(width: 16),
                    Text('ISH KUNINI BOSHLASH', style: TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              NeumorphicButton(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Ish tugaganligi qayd etildi.')),
                  );
                },
                child: const Row(
                  children: [
                    Icon(Icons.stop_circle_outlined, color: AppColors.danger),
                    SizedBox(width: 16),
                    Text('ISH KUNINI YAKUNLASH', style: TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              NeumorphicButton(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Avans so\'rovi yuborildi.')),
                  );
                },
                child: const Row(
                  children: [
                    Icon(Icons.monetization_on_outlined, color: AppColors.blueEnd),
                    SizedBox(width: 16),
                    Text('AVANS SO\'ROVI', style: TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              const SizedBox(height: 32),

              const Text('OMBOR AMALLARI', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
              const SizedBox(height: 16),

              NeumorphicButton(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Ombor sarfi qayd qilish ekrani (tez orada...)')),
                  );
                },
                child: const Row(
                  children: [
                    Icon(Icons.outbox_outlined, color: AppColors.textPrimary),
                    SizedBox(width: 16),
                    Text('MAHSULOT SARFINI KIRITISH', style: TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
