import 'package:flutter/material.dart' hide BoxDecoration, BoxShadow;
import 'package:local_auth/local_auth.dart';
import '../../external/flutter_inset_box_shadow/flutter_inset_box_shadow.dart';

class BiometricsHelper {
  static final LocalAuthentication _auth = LocalAuthentication();

  static Future<bool> isAvailable() async {
    final bool canAuthenticateWithBiometrics = await _auth.canCheckBiometrics;
    final bool canAuthenticate = canAuthenticateWithBiometrics || await _auth.isDeviceSupported();
    return canAuthenticate;
  }

  static Future<bool> authenticate() async {
    try {
      if (!await isAvailable()) return false;
      return await _auth.authenticate(
        localizedReason: 'Moliya bo\'limini ochish uchun barmoq izi yoki yuzni tanitishdan foydalaning',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );
    } catch (e) {
      return false;
    }
  }
}

class PrivacyShield extends StatefulWidget {
  final Widget child;

  const PrivacyShield({Key? key, required this.child}) : super(key: key);

  @override
  State<PrivacyShield> createState() => _PrivacyShieldState();
}

class _PrivacyShieldState extends State<PrivacyShield> with WidgetsBindingObserver {
  bool _isBackground = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    setState(() {
      _isBackground = state == AppLifecycleState.paused || state == AppLifecycleState.inactive;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isBackground) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Scaffold(
          backgroundColor: const Color(0xFFEEF2F7),
          body: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: const InsetBoxDecoration(
                    color: Color(0xFFEEF2F7),
                    shape: BoxShape.circle,
                    boxShadow: [
                      InsetBoxShadow(
                        color: Color(0xFFC9D2DE),
                        offset: Offset(6, 6),
                        blurRadius: 12,
                      ),
                      InsetBoxShadow(
                        color: Color(0xFFFFFFFF),
                        offset: Offset(-6, -6),
                        blurRadius: 12,
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.lock_outline,
                    size: 64,
                    color: Color(0xFF7F8C8D),
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  'Black Door',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2C3E50),
                    letterSpacing: 2,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Moliyaviy ma\'lumotlar himoyalangan',
                  style: TextStyle(
                    fontSize: 14,
                    color: Color(0xFF7F8C8D),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }
    return widget.child;
  }
}
