import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();

  void _submit() async {
    if (_formKey.currentState!.validate()) {
      final success = await ref.read(authProvider.notifier).login(
        _phoneController.text,
        _passwordController.text,
      );
      if (success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Xush kelibsiz!')),
        );
      }
    }
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Logo
                  Center(
                    child: Container(
                      width: 100,
                      height: 100,
                      decoration: const BoxDecoration(
                        color: AppColors.background,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.shadowDark,
                            offset: Offset(8, 8),
                            blurRadius: 16,
                          ),
                          BoxShadow(
                            color: AppColors.shadowLight,
                            offset: Offset(-8, -8),
                            blurRadius: 16,
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.door_back_door_outlined,
                        size: 48,
                        color: AppColors.success,
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                  
                  // Brand Header
                  const Center(
                    child: Text(
                      'BLACK DOOR',
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w900,
                        color: AppColors.textPrimary,
                        letterSpacing: 3,
                      ),
                    ),
                  ),
                  const Center(
                    child: Text(
                      'TIZIMGA KIRISH',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textMuted,
                        letterSpacing: 2,
                      ),
                    ),
                  ),
                  const SizedBox(height: 48),

                  if (authState.error != null) ...[
                    NeumorphicCard(
                      color: const Color(0xFFFDE8E8),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: AppColors.danger),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              authState.error!,
                              style: const TextStyle(color: AppColors.danger, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Phone Field
                  NeumorphicTextField(
                    controller: _phoneController,
                    labelText: 'Telefon raqami',
                    hintText: '+998901234567',
                    keyboardType: TextInputType.phone,
                    prefixIcon: Icons.phone_outlined,
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Telefon raqamingizni kiriting';
                      return null;
                    },
                  ),
                  const SizedBox(height: 24),

                  // Password Field
                  NeumorphicTextField(
                    controller: _passwordController,
                    labelText: 'Parol',
                    hintText: 'Kamida 8 belgi',
                    obscureText: true,
                    prefixIcon: Icons.lock_outline,
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Parolingizni kiriting';
                      return null;
                    },
                  ),
                  const SizedBox(height: 40),

                  // Submit Button
                  NeumorphicButton(
                    onTap: authState.isLoading ? null : _submit,
                    gradientColors: AppColors.greenGradient,
                    child: Center(
                      child: authState.isLoading
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Text(
                              'KIRISH',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 2,
                              ),
                            ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
