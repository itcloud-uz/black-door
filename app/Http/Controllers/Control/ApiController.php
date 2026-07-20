<?php

declare(strict_types=1);

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\Control\License;
use App\Models\Control\Installation;
use App\Services\LicenseCryptoService;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'hardware_uuid' => 'required|string',
            'domain' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $license = License::where('license_key', $request->license_key)->first();

        if (!$license) {
            return response()->json(['error' => 'Litsenziya kaliti topilmadi.'], 404);
        }

        if ($license->status === 'suspended') {
            return response()->json(['error' => 'Ushbu litsenziya bloklangan.'], 403);
        }

        $plan = $license->tariffPlan;
        
        // Find existing installation
        $installation = Installation::where('license_id', $license->id)
            ->where('hardware_uuid', $request->hardware_uuid)
            ->first();

        if (!$installation) {
            if ($license->installations_count >= $license->activation_limit) {
                return response()->json(['error' => 'Faollashtirish limiti tugagan. Kalitni boshqa serverda ishlatib bo\'lmaydi.'], 403);
            }

            $installation = Installation::create([
                'license_id' => $license->id,
                'hardware_uuid' => $request->hardware_uuid,
                'domain' => $request->domain,
                'ip_address' => $request->ip_address,
                'last_seen_at' => now(),
                'metadata' => $request->metadata,
            ]);

            $license->increment('installations_count');
        } else {
            $installation->update([
                'domain' => $request->domain,
                'ip_address' => $request->ip_address,
                'last_seen_at' => now(),
                'metadata' => $request->metadata,
            ]);
        }

        $license->update([
            'status' => 'active',
            'last_heartbeat_at' => now(),
        ]);

        // Generate token payload
        $payload = [
            'license_key' => $license->license_key,
            'product_code' => $license->product->code,
            'tariff_plan_code' => $plan->code,
            'client_name' => $license->client->company_name,
            'starts_at' => $license->starts_at->toDateString(),
            'expires_at' => $license->expires_at ? $license->expires_at->toDateString() : null,
            'max_users' => $plan->max_users,
            'max_objects' => $plan->max_objects,
            'features' => $plan->features ?? [],
            'installation_uuid' => $installation->hardware_uuid,
            'status' => 'active',
            'generated_at' => now()->toDateTimeString(),
        ];

        $signedData = LicenseCryptoService::signPayload($payload);

        return response()->json([
            'status' => 'success',
            'token_payload' => $signedData['payload'],
            'token_signature' => $signedData['signature'],
        ]);
    }

    public function heartbeat(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'hardware_uuid' => 'required|string',
        ]);

        $license = License::where('license_key', $request->license_key)->first();

        if (!$license) {
            return response()->json(['error' => 'Litsenziya topilmadi.'], 404);
        }

        $installation = Installation::where('license_id', $license->id)
            ->where('hardware_uuid', $request->hardware_uuid)
            ->first();

        if (!$installation) {
            return response()->json(['error' => 'O\'rnatma ro\'yxatga olinmagan.'], 403);
        }

        $installation->update([
            'last_seen_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        $license->update([
            'last_heartbeat_at' => now(),
        ]);

        $plan = $license->tariffPlan;

        // Generate token payload (reflecting current status, even if suspended)
        $payload = [
            'license_key' => $license->license_key,
            'product_code' => $license->product->code,
            'tariff_plan_code' => $plan->code,
            'client_name' => $license->client->company_name,
            'starts_at' => $license->starts_at->toDateString(),
            'expires_at' => $license->expires_at ? $license->expires_at->toDateString() : null,
            'max_users' => $plan->max_users,
            'max_objects' => $plan->max_objects,
            'features' => $plan->features ?? [],
            'installation_uuid' => $installation->hardware_uuid,
            'status' => $license->status, // Send suspended/expired state if changed
            'generated_at' => now()->toDateTimeString(),
        ];

        $signedData = LicenseCryptoService::signPayload($payload);

        return response()->json([
            'status' => 'success',
            'token_payload' => $signedData['payload'],
            'token_signature' => $signedData['signature'],
        ]);
    }
}
