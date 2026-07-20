<?php

declare(strict_types=1);

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\Control\License;
use App\Models\Control\Client;
use App\Models\Control\LicensePayment;
use App\Models\Control\ClientRequest;
use App\Models\Control\Installation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total income (separately USD / UZS)
        $totalUsd = LicensePayment::where('currency', 'USD')->sum('amount');
        $totalUzs = LicensePayment::where('currency', 'UZS')->sum('amount');

        // Convert to standard format
        $totalUsdFormatted = number_format($totalUsd / 100, 2, '.', ' ');
        $totalUzsFormatted = number_format($totalUzs / 100, 2, '.', ' ');

        // 2. Counts
        $activeLicensesCount = License::where('status', 'active')->count();
        $totalClientsCount = Client::count();
        $pendingRequestsCount = ClientRequest::where('status', 'pending')->count();

        // 3. Upcoming expirations (expires in 30 days)
        $upcomingExpirations = License::with(['client', 'product', 'tariffPlan'])
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->orderBy('expires_at', 'asc')
            ->get();

        // 4. Installations (recent heartbeats & troubled ones: no heartbeat in 24 hours)
        $troubledInstallations = Installation::with(['license.client'])
            ->where('last_seen_at', '<', now()->subDay())
            ->orderBy('last_seen_at', 'desc')
            ->get();

        $recentInstallations = Installation::with(['license.client'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 5. Recent payments
        $recentPayments = LicensePayment::with(['license.client'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('control.dashboard', compact(
            'totalUsdFormatted',
            'totalUzsFormatted',
            'activeLicensesCount',
            'totalClientsCount',
            'pendingRequestsCount',
            'upcomingExpirations',
            'troubledInstallations',
            'recentInstallations',
            'recentPayments'
        ));
    }
}
