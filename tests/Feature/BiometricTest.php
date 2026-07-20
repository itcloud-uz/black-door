<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class BiometricTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $testVector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
            'pin_code' => Hash::make('1234'),
        ]);

        // Standard test embedding vector (128 floats)
        $this->testVector = [];
        for ($i = 0; $i < 128; $i++) {
            $this->testVector[] = (float)($i / 128);
        }
    }

    /**
     * Test user can register biometric profile.
     */
    public function test_user_can_register_biometric_profile(): void
    {
        $response = $this->actingAs($this->admin)->post('/finance/face/register', [
            'embedding' => json_encode($this->testVector),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->admin->refresh();
        $this->assertTrue($this->admin->hasFaceId());
        
        $decryptedVector = json_decode($this->admin->getFaceEmbedding(), true);
        $this->assertEquals($this->testVector, $decryptedVector);
    }

    /**
     * Test face ID verification flow requires correct similarity and liveness verification.
     */
    public function test_face_id_verification_flow(): void
    {
        // 1. Register face
        $this->admin->setFaceEmbedding(json_encode($this->testVector));

        // Set temp session PIN verification state
        session(['finance_pin_verified_temp' => true]);

        // 2. Submit matching vector with liveness check true
        $response = $this->actingAs($this->admin)->post('/finance/face/verify', [
            'embedding' => json_encode($this->testVector),
            'liveness_verified' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('finance.dashboard'),
        ]);

        // Session should be fully authorized
        $this->assertTrue(session('finance_pin_verified'));
    }

    /**
     * Test verification fails with incorrect embedding or missing liveness.
     */
    public function test_verification_fails_with_poor_similarity_or_no_liveness(): void
    {
        $this->admin->setFaceEmbedding(json_encode($this->testVector));
        session(['finance_pin_verified_temp' => true]);

        // 1. Fail due to liveness not verified
        $response1 = $this->actingAs($this->admin)->post('/finance/face/verify', [
            'embedding' => json_encode($this->testVector),
            'liveness_verified' => false,
        ]);
        $response1->assertStatus(400);

        // 2. Fail due to mismatching vectors (different cosine similarity)
        $mismatchVector = [];
        foreach ($this->testVector as $idx => $v) {
            $mismatchVector[] = $idx % 2 === 0 ? -$v : $v;
        }
        $response2 = $this->actingAs($this->admin)->post('/finance/face/verify', [
            'embedding' => json_encode($mismatchVector),
            'liveness_verified' => true,
        ]);
        $response2->assertStatus(401);
    }

    /**
     * Test biometric lockout occurs after 3 consecutive failures.
     */
    public function test_biometric_lockout_after_three_failures(): void
    {
        $this->admin->setFaceEmbedding(json_encode($this->testVector));
        session(['finance_pin_verified_temp' => true]);

        $mismatchVector = [];
        foreach ($this->testVector as $idx => $v) {
            $mismatchVector[] = $idx % 2 === 0 ? -$v : $v;
        }

        // 1st failure
        $this->actingAs($this->admin)->post('/finance/face/verify', [
            'embedding' => json_encode($mismatchVector),
            'liveness_verified' => true,
        ])->assertStatus(401);

        // 2nd failure
        $this->actingAs($this->admin)->post('/finance/face/verify', [
            'embedding' => json_encode($mismatchVector),
            'liveness_verified' => true,
        ])->assertStatus(401);

        // 3rd failure (should lock)
        $response = $this->actingAs($this->admin)->post('/finance/face/verify', [
            'embedding' => json_encode($mismatchVector),
            'liveness_verified' => true,
        ]);

        $response->assertStatus(423);
        $response->assertJson([
            'success' => false,
            'lockout' => true
        ]);

        $this->admin->refresh();
        $this->assertNotNull($this->admin->face_locked_until);
        $this->assertTrue($this->admin->face_locked_until->isFuture());
    }

    /**
     * Test toggle settings and biometric profile deletion.
     */
    public function test_toggle_and_biometric_deletion(): void
    {
        $this->admin->setFaceEmbedding(json_encode($this->testVector));

        // 1. Toggle face ID on/off
        $responseToggle = $this->actingAs($this->admin)->post('/finance/face/toggle', [
            'enabled' => false,
        ]);
        $responseToggle->assertRedirect();
        $this->admin->refresh();
        $this->assertFalse($this->admin->face_id_enabled);

        // 2. Delete biometrics
        $responseDelete = $this->actingAs($this->admin)->post('/finance/face/delete');
        $responseDelete->assertRedirect();
        
        $this->admin->refresh();
        $this->assertFalse($this->admin->hasFaceId());
        $this->assertNull($this->admin->face_embedding);
    }
}
