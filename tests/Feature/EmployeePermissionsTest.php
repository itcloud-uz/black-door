<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Obj;
use App\Models\ObjectEmployee;
use App\Enums\UserRole;
use App\Enums\ObjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private Obj $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->object = Obj::create([
            'name' => 'Test Zavod',
            'type' => ObjectType::Factory,
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);
    }

    /**
     * Test employee without warehouse permission is blocked.
     */
    public function test_employee_without_warehouse_permission_gets_404(): void
    {
        ObjectEmployee::create([
            'object_id' => $this->object->id,
            'user_id' => $this->employee->id,
            'position' => 'Worker',
            'hired_at' => now()->toDateString(),
            'permissions' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employee)->get('/manager/warehouse');
        $response->assertStatus(404);
    }

    /**
     * Test employee with warehouse permission can access warehouse.
     */
    public function test_employee_with_warehouse_permission_can_access(): void
    {
        ObjectEmployee::create([
            'object_id' => $this->object->id,
            'user_id' => $this->employee->id,
            'position' => 'Worker',
            'hired_at' => now()->toDateString(),
            'permissions' => ['warehouse'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employee)->get('/manager/warehouse');
        $response->assertOk();
    }

    /**
     * Test employee without transactions permission is blocked.
     */
    public function test_employee_without_transactions_permission_gets_404(): void
    {
        ObjectEmployee::create([
            'object_id' => $this->object->id,
            'user_id' => $this->employee->id,
            'position' => 'Worker',
            'hired_at' => now()->toDateString(),
            'permissions' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employee)->get('/manager/transactions');
        $response->assertStatus(404);
    }

    /**
     * Test employee with transactions permission can access transactions.
     */
    public function test_employee_with_transactions_permission_can_access(): void
    {
        ObjectEmployee::create([
            'object_id' => $this->object->id,
            'user_id' => $this->employee->id,
            'position' => 'Worker',
            'hired_at' => now()->toDateString(),
            'permissions' => ['transactions'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employee)->get('/manager/transactions');
        $response->assertOk();
    }

    /**
     * Test employee without employees permission is blocked.
     */
    public function test_employee_without_employees_permission_gets_404(): void
    {
        ObjectEmployee::create([
            'object_id' => $this->object->id,
            'user_id' => $this->employee->id,
            'position' => 'Worker',
            'hired_at' => now()->toDateString(),
            'permissions' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employee)->get('/manager/employees');
        $response->assertStatus(404);
    }

    /**
     * Test employee with employees permission can access.
     */
    public function test_employee_with_employees_permission_can_access(): void
    {
        ObjectEmployee::create([
            'object_id' => $this->object->id,
            'user_id' => $this->employee->id,
            'position' => 'Worker',
            'hired_at' => now()->toDateString(),
            'permissions' => ['employees'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->employee)->get('/manager/employees');
        $response->assertOk();
    }
}
