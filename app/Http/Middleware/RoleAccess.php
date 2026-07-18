<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Check if user is active
        if (! $user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Sizning hisobingiz bloklangan.']);
        }

        $userRole = $user->role->value;

        // If the user's role is in the allowed roles, proceed
        if (in_array($userRole, $roles, true)) {
            return $next($request);
        }

        // Spec requirement: If manager/employee accesses finance endpoints, return 404 (not 403)
        // Let's check if the path starts with 'finance' or if 'financier' is one of the required roles
        if (in_array('financier', $roles, true) || $request->is('finance*')) {
            abort(404);
        }

        // For other unauthorized accesses, return 404 as well to keep it secret, or 403
        abort(404);
    }
}
