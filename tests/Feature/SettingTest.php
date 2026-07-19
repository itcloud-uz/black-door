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
        $response = $this->get('/finance/settings');
        $response->assertRedirect(route('login'));
    }

    /**
     * Financier can access settings, but cannot update global settings.
     */
    public function test_financier_can_access_settings_but_cannot_save_global_settings(): void
    {
        $financier = User::create([
            'name' => 'Financier User',
            'email' => 'financier@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'is_active' => true,
        ]);

        session(['finance_pin_verified' => true]);

        // 1. Can view index
        $response = $this->actingAs($financier)->get('/finance/settings');
        $response->assertOk();
        $response->assertSee('Xavfsiz sozlamalar');
        $response->assertDontSee('Brending va Tizim Sozlamalari'); // Admin only card

        // 2. Cannot save global settings (aborts 403)
        $response2 = $this->actingAs($financier)->post('/finance/settings', [
            'company_name' => 'Financier Tried',
            'company_tagline' => 'Should fail',
            'accent_color' => 'blue',
        ]);
        $response2->assertStatus(403);
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

        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($admin)->get('/finance/settings');
        $response->assertOk();
        $response->assertSee('Xavfsiz sozlamalar');
        $response->assertSee('Brending va Tizim Sozlamalari'); // Admin only card
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

        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($admin)->post('/finance/settings', [
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

        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($admin)->post('/finance/settings', [
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

        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($admin)->post('/finance/settings', [
            'current_password' => 'password123',
            'new_pin' => '9999',
        ]);

        $response->assertRedirect();
        
        // Reload admin and check pin
        $admin->refresh();
        $this->assertTrue($admin->hasValidPin('9999'));
    }

    /**
     * Admin can upload logo.
     */
    public function test_admin_can_upload_logo(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        session(['finance_pin_verified' => true]);

        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 512, 512);

        $response = $this->actingAs($admin)->post('/finance/settings', [
            'logo' => $file,
        ]);

        $response->assertRedirect();
    }
}
