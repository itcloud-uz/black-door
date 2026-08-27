<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\Auth\BiometricController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BiometricSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function createFinancierUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test Financier',
            'phone' => '+998901234567',
            'email' => 'financier_test_' . uniqid() . '@blackdoor.uz',
            'role' => 'financier',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('1234'),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Test registering face embedding requires a valid 128D float array.
     */
    public function test_register_face_requires_valid_128d_array()
    {
        $user = $this->createFinancierUser();
        $this->actingAs($user);

        // Invalid vector (short)
        $response = $this->postJson(route('finance.face.register'), [
            'embedding' => json_encode([0.1, 0.2, 0.3]),
        ]);
        $response->assertStatus(400);

        // Valid 128D vector
        $validVector = array_fill(0, 128, 0.5);
        $response = $this->postJson(route('finance.face.register'), [
            'embedding' => json_encode($validVector),
        ]);
        $response->assertStatus(200);
        $this->assertTrue($user->fresh()->hasFaceId());
    }

    /**
     * Test threshold enforcement: identical vector matches, different vector is strictly rejected.
     */
    public function test_verify_face_strictly_enforces_threshold()
    {
        $user = $this->createFinancierUser(['face_id_enabled' => true]);
        $this->actingAs($user);

        // Register master vector
        $masterVector = [];
        for ($i = 0; $i < 128; $i++) {
            $masterVector[] = sin($i * 0.1) * 0.5 + 0.5;
        }
        $user->setFaceEmbedding(json_encode($masterVector));

        session(['finance_pin_verified_temp' => true]);

        // 1. Exact match (similarity = 1.0 >= 0.92) -> SUCCESS
        $responseMatch = $this->postJson(route('finance.face.verify'), [
            'embedding' => json_encode($masterVector),
            'liveness_verified' => true,
        ]);
        $responseMatch->assertStatus(200)->assertJson(['success' => true]);

        // 2. Completely different face vector (different person) -> REJECTED (401)
        $differentPersonVector = [];
        for ($i = 0; $i < 128; $i++) {
            $differentPersonVector[] = cos($i * 0.5 + 1.2) * 0.5 + 0.5;
        }
        $responseDifferent = $this->postJson(route('finance.face.verify'), [
            'embedding' => json_encode($differentPersonVector),
            'liveness_verified' => true,
        ]);
        $responseDifferent->assertStatus(401)->assertJson(['success' => false]);
    }

    /**
     * Test 3 consecutive failed face attempts trigger 15 minute lockout.
     */
    public function test_three_failed_attempts_lockout_user()
    {
        $user = $this->createFinancierUser(['face_id_enabled' => true]);
        $this->actingAs($user);

        $masterVector = [];
        $invalidVector = [];
        for ($i = 0; $i < 128; $i++) {
            $masterVector[] = ($i % 2 === 0) ? 1.0 : 0.0;
            $invalidVector[] = ($i % 2 === 0) ? 0.0 : 1.0; // Orthogonal vector
        }

        $user->setFaceEmbedding(json_encode($masterVector));
        session(['finance_pin_verified_temp' => true]);

        // Attempt 1 -> 401
        $this->postJson(route('finance.face.verify'), [
            'embedding' => json_encode($invalidVector),
            'liveness_verified' => true,
        ])->assertStatus(401);

        // Attempt 2 -> 401
        $this->postJson(route('finance.face.verify'), [
            'embedding' => json_encode($invalidVector),
            'liveness_verified' => true,
        ])->assertStatus(401);

        // Attempt 3 -> Lockout (423)
        $responseLock = $this->postJson(route('finance.face.verify'), [
            'embedding' => json_encode($invalidVector),
            'liveness_verified' => true,
        ]);
        $responseLock->assertStatus(423)->assertJson(['lockout' => true]);

        $this->assertNotNull($user->fresh()->face_locked_until);
    }
}
