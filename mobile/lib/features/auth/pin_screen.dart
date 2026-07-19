import 'dart:async';
import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/providers.dart';
import '../../core/widgets/neumorphic_widgets.dart';
import '../../core/theme/app_theme.dart';
import '../../core/security/security_helpers.dart';

class PinScreen extends ConsumerStatefulWidget {
  final VoidCallback onSuccess;

  const PinScreen({Key? key, required this.onSuccess}) : super(key: key);

  @override
  ConsumerState<PinScreen> createState() => _PinScreenState();
}

class _PinScreenState extends ConsumerState<PinScreen> {
  String _pin = '';
  Timer? _countdownTimer;
  int _secondsRemaining = 0;
  bool _biometricAvailable = false;

  @override
  void initState() {
    super.initState();
    _checkBiometrics();
  }

  Future<void> _checkBiometrics() async {
    final available = await BiometricsHelper.isAvailable();
    if (mounted) {
      setState(() => _biometricAvailable = available);
    }
  }

  void _authenticateWithBiometrics() async {
    final success = await BiometricsHelper.authenticate();
    if (success && mounted) {
      ref.read(pinProvider.notifier).resetLock(); // Reset any lockouts
      widget.onSuccess();
    }
  }

  void _handleKeyPress(String value) {
    if (_pin.length < 4) {
      setState(() {
        _pin += value;
      });
      if (_pin.length == 4) {
        _verifyPin();
      }
    }
  }

  void _handleDelete() {
    if (_pin.isNotEmpty) {
      setState(() {
        _pin = _pin.substring(0, _pin.length - 1);
      });
    }
  }

  void _verifyPin() async {
    final success = await ref.read(pinProvider.notifier).verifyPin(_pin);
    if (success) {
      widget.onSuccess();
    } else {
      setState(() {
        _pin = '';
      });
      final pinState = ref.read(pinProvider);
      if (pinState.isLocked) {
        _startTimer(pinState.lockTimer);
      }
    }
  }

  void _startTimer(int seconds) {
    _countdownTimer?.cancel();
    setState(() {
      _secondsRemaining = seconds;
    });
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsRemaining > 0) {
        setState(() {
          _secondsRemaining--;
        });
      } else {
        _countdownTimer?.cancel();
        ref.read(pinProvider.notifier).resetLock();
      }
    });
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final pinState = ref.watch(pinProvider);
    final textTheme = Theme.of(context).textTheme;

    // Check if locked and timer not yet started
    if (pinState.isLocked && _secondsRemaining == 0) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _startTimer(pinState.lockTimer);
      });
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Column(
          children: [
            const Spacer(),
            // Locked/Unlocked Icon
            Center(
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: const BoxDecoration(
                  color: AppColors.surface,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(color: AppColors.shadowDark, offset: Offset(6, 6), blurRadius: 12),
                    BoxShadow(color: AppColors.shadowLight, offset: Offset(-6, -6), blurRadius: 12),
                  ],
                ),
                child: Icon(
                  pinState.isLocked ? Icons.lock_outline : Icons.lock_open_outlined,
                  size: 48,
                  color: pinState.isLocked ? AppColors.danger : AppColors.textPrimary,
                ),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              pinState.isLocked ? 'MOLIYA BO\'LIMI QULFLANDI' : 'PIN KODNI KIRITING',
              style: textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold, letterSpacing: 1.5),
            ),
            const SizedBox(height: 8),
            Text(
              pinState.isLocked
                  ? 'Qayta urinish uchun $_secondsRemaining soniya kuting'
                  : 'Xavfsiz hudud. Moliyaviy ma\'lumotlar kirish ruxsati.',
              style: textTheme.bodyMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),

            // Dots indicators
            if (!pinState.isLocked) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(4, (index) {
                  final filled = index < _pin.length;
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 150),
                    margin: const EdgeInsets.symmetric(horizontal: 12),
                    width: 20,
                    height: 20,
                    decoration: filled
                        ? const BoxDecoration(
                            gradient: LinearGradient(colors: AppColors.greenGradient),
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(color: AppColors.shadowDark, offset: Offset(2, 2), blurRadius: 4),
                            ],
                          )
                        : const BoxDecoration(
                            color: AppColors.surface,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(color: AppColors.shadowDark, offset: Offset(2, 2), blurRadius: 4, inset: true),
                              BoxShadow(color: AppColors.shadowLight, offset: Offset(-2, -2), blurRadius: 4, inset: true),
                            ],
                          ),
                  );
                }),
              ),
              const SizedBox(height: 24),
              if (pinState.error != null) ...[
                Text(
                  pinState.error!,
                  style: const TextStyle(color: AppColors.danger, fontWeight: FontWeight.bold),
                ),
              ],
            ],

            const Spacer(),

            // Keyboard or lock screen
            if (pinState.isLocked) ...[
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 40),
                child: NeumorphicCard(
                  color: const Color(0xFFFDE8E8),
                  child: Center(
                    child: Text(
                      'PIN 3 marta noto\'g\'ri kiritildi.\nTizim xavfsizligi tufayli moliya moduli bloklandi. Iltimos, taymer tugashini kuting.',
                      textAlign: TextAlign.center,
                      style: textTheme.bodyMedium?.copyWith(color: AppColors.danger, height: 1.5, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ),
              const Spacer(),
            ] else ...[
              // Keyboard
              NeumorphicKeyboard(
                onKeyPressed: _handleKeyPress,
                onDeletePressed: _handleDelete,
              ),
              
              if (_biometricAvailable) ...[
                const SizedBox(height: 20),
                IconButton(
                  onPressed: _authenticateWithBiometrics,
                  icon: const Icon(Icons.fingerprint, size: 48, color: AppColors.success),
                ),
              ],
              const SizedBox(height: 24),
            ],
          ],
        ),
      ),
    );
  }
}
