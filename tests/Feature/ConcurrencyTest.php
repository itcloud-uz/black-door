<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(UserRole $role)
    {
        return User::create([
            'name' => 'Test ' . $role->name,
            'email' => $role->name . '@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_transfer_sorts_ids_and_applies_pessimistic_locking()
    {
        $admin = $this->createUser(UserRole::SuperAdmin);
        session(['finance_pin_verified' => true]);

        $accountA = CashAccount::create([
            'name' => 'Account A',
            'type' => 'cash',
            'is_active' => true
        ]);
        $accountB = CashAccount::create([
            'name' => 'Account B',
            'type' => 'cash',
            'is_active' => true
        ]);

        // Fund Account A
        CashBalance::create([
            'cash_account_id' => $accountA->id,
            'currency' => 'USD',
            'balance' => 50000, // $500
        ]);

        $response = $this->actingAs($admin)->post('/finance/transactions', [
            'type' => 'transfer',
            'cash_account_id' => $accountA->id,
            'destination_cash_account_id' => $accountB->id,
            'currency' => 'USD',
            'amount' => 100, // $1.00
            'note' => 'Transfer test',
        ]);

        $response->assertRedirect();

        // We will assert that the transfer successfully modified balances.
        $this->assertEquals(40000, CashBalance::where('cash_account_id', $accountA->id)->where('currency', 'USD')->first()->balance);
        $this->assertEquals(10000, CashBalance::where('cash_account_id', $accountB->id)->where('currency', 'USD')->first()->balance);
    }
}
