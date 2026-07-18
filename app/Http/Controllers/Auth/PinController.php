<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PinController extends Controller
{
    /**
     * Show PIN entry screen.
     */
    public function showPinForm(Request $request)
    {
        $user = Auth::user();

        if (session()->get('finance_pin_verified')) {
            return redirect()->route('finance.dashboard');
        }

        // If locked out, check if lock expired
        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            $diff = $user->pin_locked_until->diffInSeconds(now());
            // We pass the diff so Alpine can render a real-time countdown timer
            return view('auth.pin', [
                'isLocked' => true,
                'lockTimer' => $diff,
            ]);
        }

        return view('auth.pin', [
            'isLocked' => false,
            'lockTimer' => 0,
        ]);
    }

    /**
     * Verify PIN.
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $pin = $request->input('pin');
        $user = Auth::user();

        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            return back()->withErrors(['pin' => 'Kirish vaqtincha taqiqlangan.']);
        }

        if ($user->hasValidPin($pin)) {
            // Reset attempts
            $user->update([
                'failed_pin_attempts' => 0,
                'pin_locked_until' => null,
            ]);

            session()->put('finance_pin_verified', true);

            AuditLogger::log('pin_verify_success', $user);

            return redirect()->route('finance.dashboard');
        }

        // Increment attempts
        $attempts = $user->failed_pin_attempts + 1;
        $updateData = ['failed_pin_attempts' => $attempts];

        if ($attempts >= 3) {
            $updateData['pin_locked_until'] = now()->addMinutes(15);
            $updateData['failed_pin_attempts'] = 0; // reset
            $user->update($updateData);

            AuditLogger::log('pin_lockout', $user);

            // Here we can notify super admins if there is a notification mechanism
            // For now, it's recorded in the audit log.
            
            return redirect()->route('finance.pin')->withErrors([
                'pin' => 'PIN 3 marta xato kiritildi. Moliya bo\'limi 15 daqiqaga qulflandi!'
            ]);
        }

        $user->update($updateData);
        AuditLogger::log('pin_verify_failed', $user, null, ['attempts' => $attempts]);

        // Put failed attempts into session for frontend count
        session()->flash('failed_pin_attempts', $attempts);

        return redirect()->route('finance.pin')->withErrors([
            'pin' => 'Noto\'g\'ri PIN kod kiritildi. Qolgan urinishlar: ' . (3 - $attempts)
        ]);
    }
}
