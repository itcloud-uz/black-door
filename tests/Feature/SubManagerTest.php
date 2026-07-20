<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Obj;
use App\Models\ObjectSubManager;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\ObjectCashAccount;
use App\Models\ObjectTransactionCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(UserRole $role, string $email = 'test@example.com')
    {
        return User::create([
            'name' => 'Test ' . $role->name,
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    protected function createObject(string $name = 'Test Object')
    {
        return Obj::create([
            'name' => $name,
            'type' => \App\Enums\ObjectType::Factory,
            'address' => 'Test Address',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_assign_and_remove_sub_manager()
    {
        $admin = $this->createUser(UserRole::SuperAdmin, 'admin@test.com');
        $manager = $this->createUser(UserRole::Manager, 'manager@test.com');
        $object = $this->createObject();

        // 1. Assign sub manager
        $response = $this->actingAs($admin)->post("/admin/objects/{$object->id}/sub-managers", [
            'user_id' => $manager->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('object_sub_managers', [
            'object_id' => $object->id,
            'user_id' => $manager->id,
        ]);

        // 2. Remove sub manager
        $sub = ObjectSubManager::first();
        $response = $this->actingAs($admin)->delete("/admin/objects/{$object->id}/sub-managers/{$sub->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('object_sub_managers', [
            'id' => $sub->id,
        ]);
    }

    public function test_sub_manager_can_access_assigned_object()
    {
        $manager = $this->createUser(UserRole::Manager, 'manager@test.com');
        $object = $this->createObject();

        // Assign as sub manager
        ObjectSubManager::create([
            'object_id' => $object->id,
            'user_id' => $manager->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        // Attempt access
        $response = $this->actingAs($manager)->get("/manager");
        $response->assertStatus(200);
        $response->assertSee($object->name);
    }

    public function test_sub_manager_actions_flagged_as_sub_manager()
    {
        $manager = $this->createUser(UserRole::Manager, 'manager@test.com');
        $object = $this->createObject();
        $cashAccount = ObjectCashAccount::create([
            'object_id' => $object->id,
            'name' => 'Kassa 1',
            'type' => \App\Enums\CashAccountType::Cash,
        ]);
        $category = ObjectTransactionCategory::create([
            'object_id' => $object->id,
            'name' => 'Kirim',
            'type' => 'income',
            'is_active' => true,
        ]);

        // Assign as sub manager
        ObjectSubManager::create([
            'object_id' => $object->id,
            'user_id' => $manager->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        // Create transaction as sub-manager
        $response = $this->actingAs($manager)->post("/manager/transactions", [
            'object_id' => $object->id,
            'object_cash_account_id' => $cashAccount->id,
            'category_id' => $category->id,
            'type' => 'income',
            'currency' => 'UZS',
            'amount' => 100,
            'counterparty_name' => 'Test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('object_transactions', [
            'object_id' => $object->id,
            'as_sub_manager' => true,
        ]);
    }

    public function test_sub_manager_expiration_via_command()
    {
        $manager = $this->createUser(UserRole::Manager, 'manager@test.com');
        $object = $this->createObject();

        // Expired yesterday
        ObjectSubManager::create([
            'object_id' => $object->id,
            'user_id' => $manager->id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse(ObjectSubManager::where('processed', true)->exists());

        // Run expirer command
        Artisan::call('submanagers:expire');

        $this->assertTrue(ObjectSubManager::where('processed', true)->exists());
    }
}
