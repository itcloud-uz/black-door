import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:dio/dio.dart';
import 'api_client.dart';
import '../../models/models.dart';

final storageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage();
});

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient();
});

class AuthState {
  final bool isLoading;
  final String? error;
  final User? user;
  final String? token;

  AuthState({
    this.isLoading = false,
    this.error,
    this.user,
    this.token,
  });

  AuthState copyWith({
    bool? isLoading,
    String? error,
    User? user,
    String? token,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      user: user ?? this.user,
      token: token ?? this.token,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final ApiClient _client;
  final FlutterSecureStorage _storage;

  AuthNotifier(this._client, this._storage) : super(AuthState()) {
    _tryAutoLogin();
  }

  Future<void> _tryAutoLogin() async {
    state = state.copyWith(isLoading: true);
    try {
      final token = await _storage.read(key: 'auth_token');
      if (token != null) {
        final response = await _client.get('/auth/profile');
        if (response.statusCode == 200 && response.data != null) {
          final userJson = response.data['user'];
          state = AuthState(
            user: User.fromJson(userJson),
            token: token,
          );
          return;
        }
      }
    } catch (_) {
      // Failed auto login, clear token
      await _storage.delete(key: 'auth_token');
    }
    state = AuthState();
  }

  Future<bool> login(String phone, String password) async {
    state = state.copyWith(isLoading: true);
    try {
      final response = await _client.post('/auth/login', data: {
        'phone': phone,
        'password': password,
      });

      if (response.statusCode == 200 && response.data != null) {
        final token = response.data['token'] as String;
        final userJson = response.data['user'];
        
        await _storage.write(key: 'auth_token', value: token);
        
        state = AuthState(
          user: User.fromJson(userJson),
          token: token,
        );
        return true;
      }
    } on DioException catch (e) {
      final msg = e.response?.data['message'] ?? 'Telefon raqami yoki parol noto\'g\'ri.';
      state = AuthState(error: msg);
    } catch (_) {
      state = AuthState(error: 'Tarmoq ulanishida xatolik yuz berdi.');
    }
    return false;
  }

  Future<void> logout() async {
    try {
      await _client.post('/auth/logout');
    } catch (_) {}
    await _storage.delete(key: 'auth_token');
    await _storage.delete(key: 'pin_verified');
    state = AuthState();
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final client = ref.watch(apiClientProvider);
  final storage = ref.watch(storageProvider);
  return AuthNotifier(client, storage);
});

class PinState {
  final bool isVerified;
  final bool isLocked;
  final int remainingAttempts;
  final int lockTimer;
  final String? error;

  PinState({
    this.isVerified = false,
    this.isLocked = false,
    this.remainingAttempts = 3,
    this.lockTimer = 0,
    this.error,
  });

  PinState copyWith({
    bool? isVerified,
    bool? isLocked,
    int? remainingAttempts,
    int? lockTimer,
    String? error,
  }) {
    return PinState(
      isVerified: isVerified ?? this.isVerified,
      isLocked: isLocked ?? this.isLocked,
      remainingAttempts: remainingAttempts ?? this.remainingAttempts,
      lockTimer: lockTimer ?? this.lockTimer,
      error: error,
    );
  }
}

class PinNotifier extends StateNotifier<PinState> {
  final ApiClient _client;
  final FlutterSecureStorage _storage;

  PinNotifier(this._client, this._storage) : super(PinState()) {
    _checkPinStatus();
  }

  Future<void> _checkPinStatus() async {
    final verified = await _storage.read(key: 'pin_verified');
    if (verified == 'true') {
      state = PinState(isVerified: true);
    }
  }

  Future<bool> verifyPin(String pin) async {
    state = state.copyWith(error: null);
    try {
      final response = await _client.post('/auth/verify-pin', data: {'pin': pin});
      if (response.statusCode == 200) {
        await _storage.write(key: 'pin_verified', value: 'true');
        state = PinState(isVerified: true);
        return true;
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 423) {
        final lockSeconds = e.response?.data['lock_timer'] as int? ?? 900;
        state = PinState(
          isLocked: true,
          lockTimer: lockSeconds,
          error: e.response?.data['message'] ?? 'PIN bloklandi.',
        );
      } else {
        final remaining = e.response?.data['remaining_attempts'] as int? ?? 3;
        state = PinState(
          remainingAttempts: remaining,
          error: e.response?.data['message'] ?? 'PIN kod noto\'g\'ri.',
        );
      }
    } catch (_) {
      state = state.copyWith(error: 'Aloqa xatosi.');
    }
    return false;
  }

  void resetLock() {
    state = PinState();
  }
}

final pinProvider = StateNotifierProvider<PinNotifier, PinState>((ref) {
  final client = ref.watch(apiClientProvider);
  final storage = ref.watch(storageProvider);
  return PinNotifier(client, storage);
});
