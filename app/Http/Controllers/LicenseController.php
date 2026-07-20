<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClientLicense;
use App\Services\LicenseCryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public static function getDeviceUuid(): string
    {
        $uuidPath = storage_path('app/device_uuid');
        if (!file_exists($uuidPath)) {
            // Ensure storage/app dir exists
            if (!is_dir(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }
            $uuid = Str::uuid()->toString();
            file_put_contents($uuidPath, $uuid);
        } else {
            $uuid = file_get_contents($uuidPath);
        }
        return $uuid;
    }

    public function showActivateForm()
    {
        // If already active and valid, don't force activation unless requested
        $license = ClientLicense::first();
        if ($license && $license->status === 'active' && (!$license->expires_at || !$license->expires_at->isPast())) {
            // Verify signature
            if (LicenseCryptoService::verifyPayload($license->token_payload, $license->token_signature)) {
                return redirect()->route('admin.dashboard');
            }
        }

        $deviceUuid = self::getDeviceUuid();
        return view('license.activate', compact('deviceUuid'));
    }

    public function submitActivation(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $deviceUuid = self::getDeviceUuid();
        $controlUrl = env('CONTROL_SERVER_URL', 'http://127.0.0.1:9090');

        try {
            $response = Http::timeout(5)->post($controlUrl . '/api/control/license/activate', [
                'license_key' => $request->license_key,
                'hardware_uuid' => $deviceUuid,
                'domain' => $request->getHost(),
                'ip_address' => $request->ip(),
                'metadata' => [
                    'php_version' => PHP_VERSION,
                    'os' => PHP_OS,
                ],
            ]);

            if ($response->failed()) {
                $error = $response->json('error') ?? 'Litsenziya serveridan xato javob qaytdi.';
                return back()->withErrors(['key' => $error])->withInput();
            }

            $data = $response->json();
            $tokenPayload = $data['token_payload'];
            $tokenSignature = $data['token_signature'];

            // Cryptographic signature check
            $isValid = LicenseCryptoService::verifyPayload($tokenPayload, $tokenSignature);

            if (!$isValid) {
                return back()->withErrors(['key' => 'Litsenziya tokenining raqamli imzosi noto\'g\'ri.'])->withInput();
            }

            $payload = json_decode($tokenPayload, true);

            // Store locally
            ClientLicense::truncate();
            ClientLicense::create([
                'license_key' => $payload['license_key'],
                'tariff_plan_code' => $payload['tariff_plan_code'],
                'client_name' => $payload['client_name'],
                'starts_at' => $payload['starts_at'],
                'expires_at' => $payload['expires_at'],
                'max_users' => $payload['max_users'],
                'max_objects' => $payload['max_objects'],
                'features' => $payload['features'],
                'installation_uuid' => $payload['installation_uuid'],
                'status' => $payload['status'],
                'token_payload' => $tokenPayload,
                'token_signature' => $tokenSignature,
                'last_successful_heartbeat_at' => now(),
                'is_read_only_grace' => false,
            ]);

            return redirect()->route('admin.dashboard')->with('success', 'Tizim muvaffaqiyatli faollashtirildi!');

        } catch (\Exception $e) {
            return back()->withErrors(['key' => 'Litsenziya serveriga ulanib bo\'lmadi: ' . $e->getMessage()])->withInput();
        }
    }

    public function showLicenseInfo()
    {
        $license = ClientLicense::first();
        $deviceUuid = self::getDeviceUuid();
        return view('license.info', compact('license', 'deviceUuid'));
    }

    public function refreshLicense()
    {
        $license = ClientLicense::first();
        if (!$license) {
            return redirect()->route('license.activate');
        }

        $deviceUuid = self::getDeviceUuid();
        $controlUrl = env('CONTROL_SERVER_URL', 'http://127.0.0.1:9090');

        try {
            $response = Http::timeout(5)->post($controlUrl . '/api/control/license/heartbeat', [
                'license_key' => $license->license_key,
                'hardware_uuid' => $deviceUuid,
            ]);

            if ($response->failed()) {
                // If offline or failed, keep the current active license (do not delete or crash)
                return back()->withErrors(['error' => 'Litsenziya serveriga ulanib bo\'lmadi. Eski litsenziya ma\'lumotlari saqlab qolindi.']);
            }

            $data = $response->json();
            $tokenPayload = $data['token_payload'];
            $tokenSignature = $data['token_signature'];

            $isValid = LicenseCryptoService::verifyPayload($tokenPayload, $tokenSignature);

            if (!$isValid) {
                return back()->withErrors(['error' => 'Yangilangan litsenziya imzosi noto\'g\'ri.']);
            }

            $payload = json_decode($tokenPayload, true);

            $license->update([
                'tariff_plan_code' => $payload['tariff_plan_code'],
                'client_name' => $payload['client_name'],
                'starts_at' => $payload['starts_at'],
                'expires_at' => $payload['expires_at'],
                'max_users' => $payload['max_users'],
                'max_objects' => $payload['max_objects'],
                'features' => $payload['features'],
                'status' => $payload['status'],
                'token_payload' => $tokenPayload,
                'token_signature' => $tokenSignature,
                'last_successful_heartbeat_at' => now(),
                'is_read_only_grace' => $payload['status'] === 'suspended' ? false : $license->is_read_only_grace,
            ]);

            return back()->with('success', 'Litsenziya holati muvaffaqiyatli yangilandi!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Litsenziya serveriga ulanishda xato: ' . $e->getMessage()]);
        }
    }
}
