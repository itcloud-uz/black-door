<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Counterparty;
use App\Models\Transaction;
use App\Enums\UserRole;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Enums\CounterpartyCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DebtBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $financier;
    protected CashAccount $account;
    protected Counterparty $counterparty;

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
            'balance' => 1000000,
        ]);

        CashBalance::create([
            'cash_account_id' => $this->account->id,
            'currency' => Currency::UZS->value,
            'balance' => 10000000,
        ]);

        $this->counterparty = Counterparty::create([
            'name' => 'Client Sobir',
            'category' => CounterpartyCategory::Client,
            'phone' => '1234567',
            'created_by' => $this->financier->id,
        ]);
    }

    /**
     * Test dynamic balance calculations based on transactions.
     */
    public function test_debt_balance_calculation(): void
    {
        session(['finance_pin_verified' => true]);

        // 1. Client pays us $100.00 (Income) -> balance direction is +1
        // (So they pay us, meaning we owe them or credit increases)
        $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 100.00,
            'currency' => 'USD',
            'counterparty_id' => $this->counterparty->id,
            'note' => 'Client payment',
        ]);

        // 2. We pay client $40.00 (Expense) -> balance direction is -1
        $this->actingAs($this->financier)->post('/finance/transactions', [
            'cash_account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 40.00,
            'currency' => 'USD',
            'counterparty_id' => $this->counterparty->id,
            'note' => 'Client refund/payout',
        ]);

        // Expected balance: +10000 - 4000 = 6000 cents
        $this->assertEquals(6000, $this->counterparty->getBalanceUsd());
        $this->assertEquals(0, $this->counterparty->getBalanceUzs());
    }
}
