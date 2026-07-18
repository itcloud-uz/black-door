<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('login'));

        $response2 = $this->get('/finance');
        $response2->assertRedirect(route('login'));

        $response3 = $this->get('/manager');
        $response3->assertRedirect(route('login'));
    }

    /**
     * Admin can access admin dashboard.
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();
    }

    /**
     * Financier cannot access admin dashboard.
     */
    public function test_financier_cannot_access_admin_dashboard(): void
    {
        $financier = User::create([
            'name' => 'Financier User',
            'email' => 'financier@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'is_active' => true,
        ]);

        $response = $this->actingAs($financier)->get('/admin');
        $response->assertStatus(404);
    }
}
