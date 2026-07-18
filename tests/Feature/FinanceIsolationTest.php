<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinanceIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that manager gets 404 when accessing finance endpoints.
     */
    public function test_manager_gets_404_on_finance_endpoints(): void
    {
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get('/finance');
        $response->assertStatus(404);

        $response2 = $this->actingAs($manager)->get('/finance/transactions');
        $response2->assertStatus(404);
    }

    /**
     * Test that employee gets 404 when accessing finance endpoints.
     */
    public function test_employee_gets_404_on_finance_endpoints(): void
    {
        $employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee)->get('/finance');
        $response->assertStatus(404);
    }
}
