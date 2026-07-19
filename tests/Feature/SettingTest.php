<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guest cannot access settings.
     */
    public function test_guest_is_redirected_from_settings(): void
    {
        $response = $this->get('/admin/settings');
        $response->assertRedirect(route('login'));
    }

    /**
     * Financier cannot access settings.
     */
    public function test_financier_cannot_access_settings(): void
    {
        $financier = User::create([
            'name' => 'Financier User',
            'email' => 'financier@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'is_active' => true,
        ]);

        $response = $this->actingAs($financier)->get('/admin/settings');
        $response->assertStatus(404); // Returns 404/403 due to role middleware
    }

    /**
     * Admin can access settings.
     */
    public function test_admin_can_access_settings(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/settings');
        $response->assertOk();
        $response->assertSee('Tizim sozlamalari');
    }

    /**
     * Admin can save global settings.
     */
    public function test_admin_can_save_global_settings(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'company_name' => 'Custom Brand Name',
            'company_tagline' => 'New Subtitle Tag',
            'accent_color' => 'blue',
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('Custom Brand Name', Setting::get('company_name'));
        $this->assertEquals('New Subtitle Tag', Setting::get('company_tagline'));
        $this->assertEquals('blue', Setting::get('accent_color'));
    }

    /**
     * Admin can update their password.
     */
    public function test_admin_can_update_password(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('old_password'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'old_password' => 'old_password',
            'new_password' => 'new_password123',
            'new_password_confirmation' => 'new_password123',
        ]);

        $response->assertRedirect();
        
        // Reload admin and check password
        $admin->refresh();
        $this->assertTrue(Hash::check('new_password123', $admin->password));
    }

    /**
     * Admin can update their PIN code.
     */
    public function test_admin_can_update_pin_code(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('1111'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'current_password' => 'password123',
            'new_pin' => '9999',
        ]);

        $response->assertRedirect();
        
        // Reload admin and check pin
        $admin->refresh();
        $this->assertTrue($admin->hasValidPin('9999'));
    }
}
