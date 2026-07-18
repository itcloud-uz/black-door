<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Events\PinLocked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * User Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Telefon raqami yoki parol noto\'g\'ri.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Hisobingiz faolsizlantirilgan. Admin bilan bog\'laning.'
            ], 403);
        }

        // Generate Sanctum Token
        $token = $user->createToken('mobile-app')->plainTextToken;

        AuditLogger::log('login', $user, null, ['platform' => 'mobile']);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * Get Current Profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * Verify PIN (For Finance access)
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $user = $request->user();
        $pin = $request->input('pin');

        // Check if locked
        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            $diff = $user->pin_locked_until->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => 'Kirish vaqtincha taqiqlangan.',
                'locked' => true,
                'lock_timer' => $diff
            ], 423);
        }

        if ($user->hasValidPin($pin)) {
            // Reset attempts
            $user->update([
                'failed_pin_attempts' => 0,
                'pin_locked_until' => null,
            ]);

            AuditLogger::log('pin_verify_success', $user, null, ['platform' => 'mobile']);

            return response()->json([
                'success' => true,
                'message' => 'PIN kod tasdiqlandi.'
            ]);
        }

        // Increment attempts
        $attempts = $user->failed_pin_attempts + 1;
        $updateData = ['failed_pin_attempts' => $attempts];

        if ($attempts >= 3) {
            $updateData['pin_locked_until'] = now()->addMinutes(15);
            $updateData['failed_pin_attempts'] = 0; // reset
            $user->update($updateData);

            AuditLogger::log('pin_lockout', $user, null, ['platform' => 'mobile']);

            // Broadcast PinLocked event
            try {
                broadcast(new PinLocked($user->id, 900))->toOthers();
            } catch (\Exception $e) {
                // ignore broadcast failures
            }

            return response()->json([
                'success' => false,
                'message' => 'PIN 3 marta xato kiritildi. Bo\'lim 15 daqiqaga qulflandi!',
                'locked' => true,
                'lock_timer' => 900
            ], 423);
        }

        $user->update($updateData);
        AuditLogger::log('pin_verify_failed', $user, null, ['attempts' => $attempts, 'platform' => 'mobile']);

        return response()->json([
            'success' => false,
            'message' => 'Noto\'g\'ri PIN kod kiritildi.',
            'remaining_attempts' => 3 - $attempts
        ], 400);
    }

    /**
     * User Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Tizimdan muvaffaqiyatli chiqildi.'
        ]);
    }
}
