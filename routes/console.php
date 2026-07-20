<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Obj;
use App\Services\AnalyticsClient;
use Illuminate\Support\Facades\Log;

Schedule::call(function () {
    Log::info('Kunlik obyekt hisobotlarini shakllantirish boshlandi...');
    $objects = Obj::where('is_active', true)->get();
    $analytics = new AnalyticsClient();
    $today = now()->toDateString();
    
    foreach ($objects as $object) {
        try {
            $report = $analytics->getObjectDailyReport((int)$object->id, $today);
            Log::info("Kunlik hisobot shakllantirildi: {$object->name}");
        } catch (\Exception $e) {
            Log::error("Hisobot shakllantirishda xatolik ({$object->name}): " . $e->getMessage());
        }
    }
})->dailyAt('23:00');

Schedule::command('currency:sync-cbu')->dailyAt('09:00');
Schedule::command('db:backup')->dailyAt('02:00');
Schedule::command('submanagers:expire')->dailyAt('00:05');

Schedule::call(function () {
    if (env('BLACK_DOOR_MODE', 'client') !== 'control') {
        $license = \App\Models\ClientLicense::first();
        if ($license) {
            $deviceUuid = \App\Http\Controllers\LicenseController::getDeviceUuid();
            $controlUrl = env('CONTROL_SERVER_URL', 'http://127.0.0.1:9090');
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->post($controlUrl . '/api/control/license/heartbeat', [
                    'license_key' => $license->license_key,
                    'hardware_uuid' => $deviceUuid,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    $tokenPayload = $data['token_payload'];
                    $tokenSignature = $data['token_signature'];

                    if (\App\Services\LicenseCryptoService::verifyPayload($tokenPayload, $tokenSignature)) {
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
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Litsenziya heartbeat xatosi: ' . $e->getMessage());
            }
        }
    }
})->everySixHours();

