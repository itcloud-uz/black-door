<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test API login successfully.
     */
    public function test_api_login_success(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '+998901234567',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '+998901234567',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'phone', 'role', 'is_active']
        ]);
    }

    /**
     * Test API login with wrong credentials.
     */
    public function test_api_login_wrong_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '+998901234567',
            'email' => 'admin2@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '+998901234567',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test getting user profile with token.
     */
    public function test_api_get_profile(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone' => '+998901234567',
            'email' => 'admin3@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/profile');

        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'phone' => '+998901234567',
                'role' => 'super_admin'
            ]
        ]);
    }

    /**
     * Test PIN code verification and lock out.
     */
    public function test_api_pin_verification_and_lockout(): void
    {
        $user = User::create([
            'name' => 'Financier User',
            'phone' => '+998901234568',
            'email' => 'financier@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'pin_code' => Hash::make('1234'),
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        // 1. Success verification
        $response = $this->postJson('/api/auth/verify-pin', ['pin' => '1234']);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 2. Failed verification 1
        $response = $this->postJson('/api/auth/verify-pin', ['pin' => '9999']);
        $response->assertStatus(400);
        $response->assertJson(['remaining_attempts' => 2]);

        // 3. Failed verification 2
        $response = $this->postJson('/api/auth/verify-pin', ['pin' => '9999']);
        $response->assertStatus(400);
        $response->assertJson(['remaining_attempts' => 1]);

        // 4. Failed verification 3 -> Lockout
        $response = $this->postJson('/api/auth/verify-pin', ['pin' => '9999']);
        $response->assertStatus(423);
        $response->assertJson(['locked' => true]);
    }
}
