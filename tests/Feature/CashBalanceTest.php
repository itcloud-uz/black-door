<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Transaction;
use App\Enums\UserRole;
use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashBalanceTest extends TestCase
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
            'balance' => 100000, // $1000.00
        ]);

        CashBalance::create([
            'cash_account_id' => $this->account->id,
            'currency' => Currency::UZS->value,
            'balance' => 5000000, // 50,000 UZS
        ]);
    }

    /**
     * Test income transaction increases balance.
     */
    public function test_income_increases_balance(): void
    {
        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 50.00, // $50.00 = 5000 cents
            'currency' => 'USD',
            'note' => 'Test income',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        // Balance should be 100000 + 5000 = 105000 cents
        $this->assertEquals(105000, $this->account->getBalance(Currency::USD));
    }

    /**
     * Test expense transaction decreases balance.
     */
    public function test_expense_decreases_balance(): void
    {
        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50.00, // $50.00 = 5000 cents
            'currency' => 'USD',
            'note' => 'Test expense',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        // Balance should be 100000 - 5000 = 95000 cents
        $this->assertEquals(95000, $this->account->getBalance(Currency::USD));
    }

    /**
     * Test preventing negative balance.
     */
    public function test_prevent_negative_balance(): void
    {
        session(['finance_pin_verified' => true]);

        $response = $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 1500.00, // $1500.00 = 150000 cents, which exceeds $1000.00
            'currency' => 'USD',
            'note' => 'Exceeding expense',
        ]);

        $response->assertSessionHasErrors('amount');
        // Balance remains unchanged
        $this->assertEquals(100000, $this->account->getBalance(Currency::USD));
    }
}
