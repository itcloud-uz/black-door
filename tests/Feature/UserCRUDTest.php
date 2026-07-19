<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Obj;
use App\Models\ObjectManager;
use App\Enums\UserRole;
use App\Enums\ObjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCRUDTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->targetUser = User::create([
            'name' => 'Financier Jamila',
            'email' => 'jamila@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'is_active' => true,
        ]);
    }

    /**
     * Admin can view users index.
     */
    public function test_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertOk();
        $response->assertSee('Financier Jamila');
    }

    /**
     * Admin can view edit page.
     */
    public function test_admin_can_view_edit_user_page(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->targetUser->id}/edit");
        $response->assertOk();
        $response->assertSee('Foydalanuvchini tahrirlash');
    }

    /**
     * Admin can update user.
     */
    public function test_admin_can_update_user(): void
    {
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->targetUser->id}", [
            'name' => 'Updated Name Jamila',
            'email' => 'jamila_updated@test.com',
            'phone' => '+998909999999',
            'role' => 'financier',
            'pin_code' => '9999',
        ]);

        $response->assertRedirect();
        
        $this->targetUser->refresh();
        $this->assertEquals('Updated Name Jamila', $this->targetUser->name);
        $this->assertEquals('jamila_updated@test.com', $this->targetUser->email);
        $this->assertEquals('+998909999999', $this->targetUser->phone);
        $this->assertTrue($this->targetUser->hasValidPin('9999'));
    }

    /**
     * Admin can toggle active status of user.
     */
    public function test_admin_can_toggle_user_active_status(): void
    {
        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->targetUser->id}/toggle-active");
        $response->assertRedirect();

        $this->targetUser->refresh();
        $this->assertFalse($this->targetUser->is_active);
    }

    /**
     * Admin can delete another user.
     */
    public function test_admin_can_delete_other_user(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->targetUser->id}");
        $response->assertRedirect();

        $this->assertSoftDeleted('users', [
            'id' => $this->targetUser->id
        ]);
    }

    /**
     * Admin cannot delete themselves.
     */
    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}");
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'deleted_at' => null
        ]);
    }
}
