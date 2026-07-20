<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Models\ClientLicense;
use App\Services\LicenseCryptoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseShield
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the server is running in "control" mode, bypass license verification
        if (env('BLACK_DOOR_MODE', 'client') === 'control') {
            return $next($request);
        }

        // Bypass for existing tests unless explicitly testing licensing
        if (app()->environment('testing') && !config('license.test_enforcement', false)) {
            return $next($request);
        }

        // List of routes to exclude from licensing checks
        $excluded = [
            'login',
            'logout',
            'license/activate',
            'license/activate/submit',
            'admin/license',
            'admin/license/refresh',
            'up',
            '_reverb',
        ];

        foreach ($excluded as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return $next($request);
            }
        }

        // Fetch the active license
        $license = ClientLicense::first();

        if (!$license) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Litsenziya topilmadi. Tizim faollashtirilmagan.'], 403);
            }
            return redirect()->route('license.activate');
        }

        // Verify cryptographic signature of the token payload
        $isValid = LicenseCryptoService::verifyPayload($license->token_payload, $license->token_signature);

        if (!$isValid) {
            $license->delete();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Litsenziya imzosi noto\'g\'ri yoki buzilgan.'], 403);
            }
            return redirect()->route('license.activate')->withErrors(['key' => 'Litsenziya imzosi noto\'g\'ri yoki buzilgan. Qayta faollashtiring.']);
        }

        // Check if status is suspended or inactive
        if ($license->status === 'suspended') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Litsenziya to\'xtatilgan. Administrator bilan bog\'laning.'], 403);
            }
            return redirect()->route('license.activate')->withErrors(['key' => 'Litsenziya to\'xtatilgan. Administrator bilan bog\'laning.']);
        }

        // Check expiration
        if ($license->expires_at && $license->expires_at->isPast()) {
            $graceDays = $license->expires_at->diffInDays(now()->startOfDay(), false);

            if ($graceDays > 7) {
                // Completely expired
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Litsenziya muddati tugagan.'], 403);
                }
                return redirect()->route('license.activate')->withErrors(['key' => 'Litsenziya muddati tugagan. Qayta faollashtiring.']);
            } else {
                // Read-only grace period (7 days)
                $license->update(['is_read_only_grace' => true]);

                if (!$request->isMethod('GET')) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Litsenziya muddati tugagan (Faqat o\'qish rejimi). Ma\'lumotlarni o\'zgartirib bo\'lmaydi.'], 403);
                    }
                    return back()->withErrors(['error' => 'Litsenziya muddati tugagan (Faqat o\'qish rejimi). Ma\'lumotlarni o\'zgartirib bo\'lmaydi.']);
                }

                session()->flash('license_warning', 'Litsenziya muddati tugagan. Tizim vaqtincha faqat o\'qish rejimida ishlamoqda. Iltimos, litsenziyani yangilang.');
            }
        } else {
            if ($license->is_read_only_grace) {
                $license->update(['is_read_only_grace' => false]);
            }
        }

        // Check connection grace period (7 days without successful heartbeat)
        if ($license->last_successful_heartbeat_at) {
            $daysSinceHeartbeat = $license->last_successful_heartbeat_at->diffInDays(now(), false);
            if ($daysSinceHeartbeat > 7) {
                session()->flash('license_warning', 'Litsenziya serveri bilan aloqa yo\'qolganiga 7 kundan oshdi. Iltimos, tarmoq ulanishini tekshiring.');
            }
        }

        // Check feature flags for specific modules
        // Mobile API
        if ($request->is('api/manager*') || $request->is('api/employee*')) {
            if (!$license->hasFeature('mobile_api')) {
                return response()->json(['error' => 'Mobil ilova xizmati litsenziyangizda faollashtirilmagan.'], 403);
            }
        }

        // Reports
        if ($request->is('finance/reports*') || $request->is('manager/reports*') || $request->is('api/reports*')) {
            if (!$license->hasFeature('reports')) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Hisobotlar moduli litsenziyangizda faollashtirilmagan.'], 403);
                }
                return back()->withErrors(['error' => 'Hisobotlar moduli litsenziyangizda faollashtirilmagan.']);
            }
        }

        return $next($request);
    }
}
