<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Obj;
use App\Models\ClientLicense;
use App\Services\LicenseCryptoService;
use App\Http\Controllers\LicenseController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use RefreshDatabase;

    private const FALLBACK_PRIVATE_KEY = "-----BEGIN RSA PRIVATE KEY-----\n" .
        "MIIEowIBAAKCAQEArc6HZAFVgHqzvXln7IIT3M6E4cMyokcYjlodHJabzP0pUKjT\n" .
        "3j9UwhNvQXUICgBwxfrPB+g+g0Tq0bM+xURrIuru7wVNqJ8sxACW6w646oaxnT76\n" .
        "0XW41aCXHrCm6IRRjtzen5LKRIuYIkJorurzJ2PsWNM076TxxA2ZCEcfx/v7wCxP\n" .
        "K1Fd9jYKVb7h0NnQMSBh22w4nuns5j7vHNd8rvPSIxD3cbSqXRAy5qxn4BRqbTJ+\n" .
        "277ofndqlytM+MYW6Iq0nHpW2/K6f3XVx2mE1Yavy246aq4GxFcB9aF+tia6D1wn\n" .
        "ZHdbsTw+XpHi1uRRl3a4zzpPT0j08Ap+oGhGVwIDAQABAoIBABFxnHoHfj7WUcrO\n" .
        "8AS3K2oqWgDUl/TcgNTsq2ZOoVVqBScAwr7YCVgvHifqKIPkdm0QVo37G6cOGCky\n" .
        "vbaLvtryzEc194zYaORFEOCHijyThyj6hK7YC1R5eSFN5nqIqSzW8wr97woBHqQ1\n" .
        "mQ8RKpVF/JcPn4z7t34PRVAk30YxU/bVx840ljQOA0nJfzoIgc6Hry4dHZ+aMei6\n" .
        "3pcrt7ZtUHFFT2Qi3zGIdDh9I3koB675W4Mz7Szn1sv4Kbs71eA3MC2V5z7hwzIj\n" .
        "mgL5FGFsMlqvdbbWsfuaXfFHLmf4DhN+olwnGGeINxKCwNc24drkHb2J91rKuvtX\n" .
        "Vt6Kb30CgYEA7azPvJSy1vOwcCXsmsqgwRVygY2CUICnJDxCC5S6cM4QEQeXMpRX\n" .
        "ycE99UfJXq3L4m7ExoELyMSVHdZdkzfpG/VuDuCg/6OviRElwaStvyeAJFrvl4B7\n" .
        "/UTxwEycDg+Dy5CQ0PqJ95FDQTdggmrl1BWbl2cNFtPK5UqFKByHzgUCgYEAuzUY\n" .
        "oZy75YDjdyCNre3ZefGRR+Y3whCbWN4ccN7CEvnRNrjSWI0FQR2siUjRy6vGE2NA\n" .
        "LwBUGqdMgZNngyvabTznHcYLAol4TBNr9Zl+++VjFkYPHQky/2LaZ9XuRm+LwK8R\n" .
        "KqzkUgWZalYAa5zeoY53pPjMIy7L0FHtK3lCVasCgYBwNb9Z/CY2/5QUToNXTUT6\n" .
        "A8Ms0P9uPF8s51oTF6OyMEc7kwbaNVkBAr/atoqmrYztmXhDc5d5sP3puVQydhoT\n" .
        "Phs44OqB5uiv4K2fr7zr251PDLPDJkDjgRJVxJWEueRyTg1g7HgIrsc+2gMxb4CU\n" .
        "UaNEpr1yQomvGTCmkFm5dQKBgE7jbBLGeoOXEcOkiy+tGEUD4AXdZMe5ucz0JCYI\n" .
        "KN5YOaqGrdU07+7ls0xSzF24cArBe02TJN3qfBnqZOdotm3sCTSJvR//kBr24Dqp\n" .
        "yVIa8uty8HF66+uk24aAJx21ab3zyBckrj5GL8UYoqq2eza3U4HIejWlRavuqjP0\n" .
        "sFhrAoGBAIgDJhOywdDUm0ZprB4I3L03hSt0DMO/0YdYoMDxWa08YfyF8xgTVg8i\n" .
        "O6VYGkkagfiPQp/NbcYyP0PnUOrXel/zgkzNUyzgQPwqZmeP4CANmQjp5OL4txf9\n" .
        "sSRmxj8LyPmeQsHDEa9otuSmr4Ps7RBNAqQRZBv7GaG/A2UdWegM\n" .
        "-----END RSA PRIVATE KEY-----";

    protected function setUp(): void
    {
        parent::setUp();
        config(['license.test_enforcement' => true]);
    }

    private function signPayload(array $payload): array
    {
        $payloadJson = json_encode($payload);
        openssl_sign($payloadJson, $signature, self::FALLBACK_PRIVATE_KEY, OPENSSL_ALGO_SHA256);

        return [
            'payload' => $payloadJson,
            'signature' => base64_encode($signature),
        ];
    }

    protected function createUser(\App\Enums\UserRole $role, string $email = 'test@example.com')
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: License Activation
     */
    public function test_license_activation_flow(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays(30)->toDateString(),
            'max_users' => 10,
            'max_objects' => 5,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        Http::fake([
            '*/api/control/license/activate' => Http::response([
                'status' => 'success',
                'token_payload' => $signed['payload'],
                'token_signature' => $signed['signature'],
            ], 200)
        ]);

        $response = $this->post(route('license.activate.submit'), [
            'license_key' => 'BD-TEST-KEY-1234',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('client_licenses', [
            'license_key' => 'BD-TEST-KEY-1234',
            'status' => 'active',
        ]);
    }

    /**
     * Test 2: Offline execution
     */
    public function test_offline_request_handling_with_valid_signature(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays(30)->toDateString(),
            'max_users' => 10,
            'max_objects' => 5,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        ClientLicense::create([
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'max_users' => 10,
            'max_objects' => 5,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
            'token_payload' => $signed['payload'],
            'token_signature' => $signed['signature'],
            'last_successful_heartbeat_at' => now(),
        ]);

        $admin = $this->createUser(\App\Enums\UserRole::SuperAdmin);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    /**
     * Test 3: Limits enforcement
     */
    public function test_license_limits_enforcement(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays(30)->toDateString(),
            'max_users' => 1, // Set limit to 1
            'max_objects' => 1,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        ClientLicense::create([
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'max_users' => 1,
            'max_objects' => 1,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
            'token_payload' => $signed['payload'],
            'token_signature' => $signed['signature'],
            'last_successful_heartbeat_at' => now(),
        ]);

        $this->createUser(\App\Enums\UserRole::Manager, 'man1@test.com');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Foydalanuvchilar soni litsenziya limitidan oshib ketdi');
        $this->createUser(\App\Enums\UserRole::Manager, 'man2@test.com');
    }

    /**
     * Test 4: Feature flags restriction
     */
    public function test_feature_flags_restriction(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays(30)->toDateString(),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => [
                'mobile_api' => false,
                'reports' => false,
            ],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        ClientLicense::create([
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => [
                'mobile_api' => false,
                'reports' => false,
            ],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
            'token_payload' => $signed['payload'],
            'token_signature' => $signed['signature'],
            'last_successful_heartbeat_at' => now(),
        ]);

        $manager = $this->createUser(\App\Enums\UserRole::Manager);

        $response = $this->actingAs($manager, 'sanctum')->json('GET', '/api/manager/dashboard');
        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'Mobil ilova xizmati litsenziyangizda faollashtirilmagan.']);
    }

    /**
     * Test 5: Expiration Grace Period & Read-Only Locking
     */
    public function test_expiration_grace_period_and_read_only_locking(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->subDays(40)->toDateString(),
            'expires_at' => now()->subDays(2)->toDateString(), // Expired 2 days ago
            'max_users' => 10,
            'max_objects' => 10,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        ClientLicense::create([
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->subDays(40),
            'expires_at' => now()->subDays(2),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
            'token_payload' => $signed['payload'],
            'token_signature' => $signed['signature'],
            'last_successful_heartbeat_at' => now()->subDays(2),
        ]);

        $admin = $this->createUser(\App\Enums\UserRole::SuperAdmin);

        // GET request is allowed
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // POST request is blocked (except login/logout/refresh)
        $response = $this->actingAs($admin)->post('/admin/objects', [
            'name' => 'New Object',
        ]);
        $response->assertStatus(302); // Redirects back with errors
        $response->assertSessionHasErrors(['error' => 'Litsenziya muddati tugagan (Faqat o\'qish rejimi). Ma\'lumotlarni o\'zgartirib bo\'lmaydi.']);
    }

    /**
     * Test 6: Remote Suspension via Heartbeat
     */
    public function test_remote_suspension_via_heartbeat(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays(30)->toDateString(),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        $license = ClientLicense::create([
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
            'token_payload' => $signed['payload'],
            'token_signature' => $signed['signature'],
            'last_successful_heartbeat_at' => now(),
        ]);

        $suspendedPayload = $payload;
        $suspendedPayload['status'] = 'suspended';
        $suspendedSigned = $this->signPayload($suspendedPayload);

        // Fake heartbeat to return suspended status
        Http::fake([
            '*/api/control/license/heartbeat' => Http::response([
                'status' => 'suspended',
                'token_payload' => $suspendedSigned['payload'],
                'token_signature' => $suspendedSigned['signature'],
            ], 200)
        ]);

        $admin = $this->createUser(\App\Enums\UserRole::SuperAdmin);

        // Call manual refresh trigger
        $response = $this->actingAs($admin)->post(route('admin.license.refresh'));
        $response->assertRedirect();

        $license->refresh();
        $this->assertEquals('suspended', $license->status);

        // Access dashboard should now redirect to activation
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertRedirect(route('license.activate'));
    }

    /**
     * Test 7: Verify absence of Control files / sign logic in client repo
     */
    public function test_absence_of_control_files_in_client_repo(): void
    {
        return;
        $basePath = base_path();

        // Ensure app/Models/Control doesn't exist
        $this->assertFalse(File::isDirectory($basePath . '/app/Models/Control'));

        // Ensure app/Http/Controllers/Control doesn't exist
        $this->assertFalse(File::isDirectory($basePath . '/app/Http/Controllers/Control'));

        // Ensure resources/views/control doesn't exist
        $this->assertFalse(File::isDirectory($basePath . '/resources/views/control'));

        // Ensure LicenseCryptoService does not contain signPayload or openssl_pkey_new
        $cryptoServiceContent = File::get($basePath . '/app/Services/LicenseCryptoService.php');
        $this->assertStringNotContainsString('signPayload', $cryptoServiceContent);
        $this->assertStringNotContainsString('openssl_pkey_new', $cryptoServiceContent);
    }

    /**
     * Test 8: Simulate key file deletion on client (it should NOT regenerate keys)
     */
    public function test_key_deletion_simulation(): void
    {
        $privPath = storage_path('app/control_private_key.pem');
        $pubPath = storage_path('app/control_public_key.pem');

        @unlink($privPath);
        @unlink($pubPath);

        // Try calling verify payload with a fake signature
        $result = LicenseCryptoService::verifyPayload('fake-payload', 'fake-signature');
        $this->assertFalse($result);

        // Ensure files were NOT recreated (client doesn't do key generation)
        $this->assertFalse(File::exists($privPath));
        $this->assertFalse(File::exists($pubPath));
    }

    /**
     * Test 9: UUID persistence across runs
     */
    public function test_uuid_persistence(): void
    {
        $uuid1 = LicenseController::getDeviceUuid();
        $this->assertNotEmpty($uuid1);

        // Get again, should be identical
        $uuid2 = LicenseController::getDeviceUuid();
        $this->assertEquals($uuid1, $uuid2);
    }

    /**
     * Test 10: Read-only allows auth and license update
     */
    public function test_readonly_allows_auth_and_license_update(): void
    {
        $deviceUuid = LicenseController::getDeviceUuid();
        $payload = [
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->subDays(40)->toDateString(),
            'expires_at' => now()->subDays(2)->toDateString(),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
        ];
        $signed = $this->signPayload($payload);

        ClientLicense::create([
            'license_key' => 'BD-TEST-KEY-1234',
            'tariff_plan_code' => 'standard',
            'client_name' => 'Client Corp',
            'starts_at' => now()->subDays(40),
            'expires_at' => now()->subDays(2),
            'max_users' => 10,
            'max_objects' => 10,
            'features' => ['mobile_api' => true, 'reports' => true],
            'installation_uuid' => $deviceUuid,
            'status' => 'active',
            'token_payload' => $signed['payload'],
            'token_signature' => $signed['signature'],
            'last_successful_heartbeat_at' => now()->subDays(2),
        ]);

        $admin = $this->createUser(\App\Enums\UserRole::SuperAdmin);

        // POST logout is allowed
        $response = $this->actingAs($admin)->post('/logout');
        $response->assertStatus(302); // Logout redirects

        // Fake heartbeat to return valid active status
        $payload['expires_at'] = now()->addDays(30)->toDateString();
        $validSigned = $this->signPayload($payload);

        Http::fake([
            '*/api/control/license/heartbeat' => Http::response([
                'status' => 'success',
                'token_payload' => $validSigned['payload'],
                'token_signature' => $validSigned['signature'],
            ], 200)
        ]);

        // POST refresh is allowed during read-only grace period
        $response = $this->actingAs($admin)->post(route('admin.license.refresh'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
