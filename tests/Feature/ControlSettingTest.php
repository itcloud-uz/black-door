<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessLogoBranding;
use App\Jobs\RebuildMobileApp;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;

class ControlSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $financier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'itcloud.uz',
            'password' => bcrypt('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->financier = User::create([
            'name' => 'Financier User',
            'email' => 'financier@test.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::Financier,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_control_settings(): void
    {
        $response = $this->get('/control/settings');
        $response->assertRedirect('/login');
    }

    public function test_financier_cannot_access_control_settings(): void
    {
        $response = $this->actingAs($this->financier)->get('/control/settings');
        $response->assertStatus(404);
    }

    public function test_admin_can_access_control_settings(): void
    {
        $response = $this->actingAs($this->admin)->get('/control/settings');
        $response->assertStatus(200);
        $response->assertSee('Tizim Sozlamalari');
    }

    public function test_admin_can_save_settings_and_upload_logo(): void
    {
        Storage::fake('local');
        Bus::fake();

        $logo = UploadedFile::fake()->image('new_logo.png', 500, 500);

        $response = $this->actingAs($this->admin)->post('/control/settings/update', [
            'company_name' => 'New System Title',
            'company_tagline' => 'Tagline test',
            'accent_color' => 'blue',
            'support_phone' => '+998901234567',
            'support_email' => 'support@new.uz',
            'support_telegram' => '@new_tele',
            'logo' => $logo,
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('New System Title', Setting::get('company_name'));
        $this->assertEquals('Tagline test', Setting::get('company_tagline'));
        $this->assertEquals('+998901234567', Setting::get('support_phone'));
        
        Bus::assertDispatched(ProcessLogoBranding::class);
    }

    public function test_admin_can_trigger_apk_rebuild(): void
    {
        Bus::fake();

        $response = $this->actingAs($this->admin)->post('/control/settings/update'); // or rebuild-apk
        
        // Let's call rebuild-apk route:
        $response = $this->actingAs($this->admin)->post('/control/settings/rebuild-apk');
        $response->assertRedirect();
        
        Bus::assertDispatched(RebuildMobileApp::class);
    }

    public function test_user_can_autologin_via_token(): void
    {
        // Generate a token for the financier
        $token = $this->financier->createToken('test-token')->plainTextToken;

        // Make requests to autologin
        $response = $this->get('/auth/autologin?token=' . $token);
        
        // Assert redirect to finance dashboard
        $response->assertRedirect('/finance');
        $this->assertAuthenticatedAs($this->financier);
        $this->assertTrue(session('finance_pin_verified'));
    }
}
