<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Enums\UserRole;
use App\Enums\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CurrencySeparationTest extends TestCase
{
    use RefreshDatabase;

    protected User $financier;
    protected CashAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->financier = User::create([
            'name' => 'Financier User',
            'email' => 'financier@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'is_active' => true,
        ]);

        $this->account = CashAccount::create([
            'name' => 'Main Cash',
            'type' => 'cash',
            'is_active' => true,
        ]);

        CashBalance::create([
            'cash_account_id' => $this->account->id,
            'currency' => Currency::USD->value,
            'balance' => 50000, // $500.00
        ]);

        CashBalance::create([
            'cash_account_id' => $this->account->id,
            'currency' => Currency::UZS->value,
            'balance' => 2000000, // 20,000 UZS
        ]);
    }

    /**
     * USD expense does not modify UZS balance.
     */
    public function test_usd_expense_does_not_modify_uzs_balance(): void
    {
        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 100.00, // $100.00
            'currency' => 'USD',
            'note' => 'USD expense',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        // USD balance decreases to 40000 cents
        $this->assertEquals(40000, $this->account->getBalance(Currency::USD));
        // UZS balance remains 2000000
        $this->assertEquals(2000000, $this->account->getBalance(Currency::UZS));
    }

    /**
     * UZS income does not modify USD balance.
     */
    public function test_uzs_income_does_not_modify_usd_balance(): void
    {
        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 500.00, // 500.00 UZS
            'currency' => 'UZS',
            'note' => 'UZS income',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        // UZS balance increases to 2050000
        $this->assertEquals(2050000, $this->account->getBalance(Currency::UZS));
        // USD balance remains 50000
        $this->assertEquals(50000, $this->account->getBalance(Currency::USD));
    }
}
