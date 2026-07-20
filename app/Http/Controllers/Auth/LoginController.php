<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            return back()->withErrors(['email' => 'Ushbu email bilan foydalanuvchi topilmadi.'])->withInput();
        }

        // Check if user is temporarily locked out
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $diff = $user->locked_until->diffInMinutes(now());
            return back()->withErrors([
                'email' => "Ko'p muvaffaqiyatsiz urinishlar sababli hisobingiz bloklangan. Iltimos, {$diff} daqiqadan so'ng urinib ko'ring."
            ])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Sizning hisobingiz faolsizlantirilgan.'])->withInput();
        }

        // Verify password
        if (Hash::check($credentials['password'], $user->password)) {
            // Reset failed login attempts
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            Auth::login($user, $request->has('remember'));

            AuditLogger::log('login', $user, null, ['status' => 'success']);

            return redirect()->intended('/');
        }

        // Increment failed attempts
        $attempts = $user->failed_login_attempts + 1;
        $updateData = ['failed_login_attempts' => $attempts];

        if ($attempts >= 5) {
            $updateData['locked_until'] = now()->addMinutes(15);
            $updateData['failed_login_attempts'] = 0; // reset attempts for next cycle
            
            $user->update($updateData);
            AuditLogger::log('login_lockout', $user, null, ['attempts' => $attempts]);
            return back()->withErrors([
                'email' => 'Muvaffaqiyatsiz urinishlar soni oshib ketdi. Hisobingiz 15 daqiqaga bloklandi.'
            ])->withInput();
        }

        $user->update($updateData);
        AuditLogger::log('login_failed', $user, null, ['attempts' => $attempts]);

        return back()->withErrors([
            'email' => 'Kiritilgan parol noto\'g\'ri. Qolgan urinishlar: ' . (5 - $attempts)
        ])->withInput();
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLogger::log('logout', $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
