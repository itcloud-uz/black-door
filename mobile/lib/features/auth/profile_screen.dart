import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/neumorphic_widgets.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;
    final textTheme = Theme.of(context).textTheme;

    if (user == null) {
      return const Scaffold(body: Center(child: Text('Foydalanuvchi topilmadi')));
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        title: const Text('PROFIL SOZLAMALARI', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.5, fontSize: 16)),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: AppColors.textPrimary),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            // Profile Avatar/Icon
            Center(
              child: Container(
                width: 120,
                height: 120,
                decoration: const BoxDecoration(
                  color: AppColors.surface,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(color: AppColors.shadowDark, offset: Offset(8, 8), blurRadius: 16),
                    BoxShadow(color: AppColors.shadowLight, offset: Offset(-8, -8), blurRadius: 16),
                  ],
                ),
                child: const Icon(Icons.person_outline, size: 60, color: AppColors.success),
              ),
            ),
            const SizedBox(height: 32),

            // User Info Cards
            _buildInfoItem('F.I.Sh', user.name, Icons.person, textTheme),
            const SizedBox(height: 20),
            _buildInfoItem('Telefon', user.phone, Icons.phone, textTheme),
            const SizedBox(height: 20),
            _buildInfoItem('Email', user.email ?? 'Kiritilmagan', Icons.email, textTheme),
            const SizedBox(height: 20),
            _buildInfoItem('Rol', user.role.name.toUpperCase(), Icons.badge, textTheme),

            const SizedBox(height: 48),

            // Logout Button
            NeumorphicButton(
              onTap: () {
                ref.read(authProvider.notifier).logout();
                Navigator.pop(context); // Go back after logout (authState change will trigger login screen)
              },
              color: const Color(0xFFFDE8E8),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.logout, color: AppColors.danger),
                  SizedBox(width: 12),
                  Text(
                    'TIZIMDAN CHIQISH',
                    style: TextStyle(color: AppColors.danger, fontWeight: FontWeight.bold, letterSpacing: 1),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(String label, String value, IconData icon, TextTheme textTheme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 8, bottom: 8),
          child: Text(label, style: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.bold, color: AppColors.textMuted)),
        ),
        NeumorphicCard(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          child: Row(
            children: [
              Icon(icon, color: AppColors.success, size: 20),
              const SizedBox(width: 16),
              Expanded(
                child: Text(value, style: textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w600)),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
