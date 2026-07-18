<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinancePinVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If the user doesn't have a role that can access finance, they should get 404
        if (! $user || ! $user->canAccessFinance()) {
            abort(404);
        }

        // Check if user is locked out due to PIN attempts
        if ($user->pin_locked_until && $user->pin_locked_until->isFuture()) {
            return redirect()->route('finance.pin')->withErrors([
                'pin' => 'PIN-kod noto\'g\'ri kiritilganligi sababli kirish vaqtincha bloklangan.'
            ]);
        }

        // Check if session has verified PIN
        if (! session()->get('finance_pin_verified')) {
            return redirect()->route('finance.pin');
        }

        return $next($request);
    }
}
