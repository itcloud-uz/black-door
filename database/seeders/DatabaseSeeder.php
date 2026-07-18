<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Obj;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\ObjectCashAccount;
use App\Models\ObjectCashBalance;
use App\Models\ObjectTransactionCategory;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\CurrencyRate;
use App\Models\CashAccount;
use App\Models\CashBalance;
use App\Models\Counterparty;
use App\Models\TransactionCategory;
use App\Models\Transaction;
use App\Enums\UserRole;
use App\Enums\ObjectType;
use App\Enums\Currency;
use App\Enums\ProductUnit;
use App\Enums\CashAccountType;
use App\Enums\CounterpartyCategory;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key constraints during seeding
        Schema::disableForeignKeyConstraints();
        
        // Truncate tables
        DB::table('transactions')->truncate();
        DB::table('transaction_categories')->truncate();
        DB::table('cash_balances')->truncate();
        DB::table('cash_accounts')->truncate();
        DB::table('counterparty_tag')->truncate();
        DB::table('counterparty_tags')->truncate();
        DB::table('counterparties')->truncate();
        DB::table('object_transactions')->truncate();
        DB::table('object_transaction_categories')->truncate();
        DB::table('object_cash_balances')->truncate();
        DB::table('object_cash_accounts')->truncate();
        DB::table('object_employees')->truncate();
        DB::table('object_manager_history')->truncate();
        DB::table('object_managers')->truncate();
        DB::table('salary_payments')->truncate();
        DB::table('warehouse_movements')->truncate();
        DB::table('warehouse_stocks')->truncate();
        DB::table('inventory_check_items')->truncate();
        DB::table('inventory_checks')->truncate();
        DB::table('products')->truncate();
        DB::table('objects')->truncate();
        DB::table('currency_rates')->truncate();
        DB::table('audit_logs')->truncate();
        DB::table('users')->truncate();
        
        Schema::enableForeignKeyConstraints();

        // 1. Users
        $admin = User::create([
            'name' => 'Abdullayev Sardor',
            'email' => 'admin@blackdoor.uz',
            'phone' => '+998901111111',
            'password' => Hash::make('password123'),
            'role' => UserRole::SuperAdmin,
            'pin_code' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $financier = User::create([
            'name' => 'Karimova Nilufar',
            'email' => 'moliyachi@blackdoor.uz',
            'phone' => '+998902222222',
            'password' => Hash::make('password123'),
            'role' => UserRole::Financier,
            'pin_code' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $factoryMgrUser = User::create([
            'name' => 'Toshmatov Jamshid',
            'email' => 'zavod.menejer@blackdoor.uz',
            'phone' => '+998903333333',
            'password' => Hash::make('password123'),
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $warehouseMgrUser = User::create([
            'name' => 'Rahimov Bekzod',
            'email' => 'ombor.menejer@blackdoor.uz',
            'phone' => '+998904444444',
            'password' => Hash::make('password123'),
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        // Factory employees
        $emp1User = User::create([
            'name' => 'Aliyev Oybek',
            'email' => 'oybek@blackdoor.uz',
            'phone' => '+998905555555',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $emp2User = User::create([
            'name' => 'Javohir Eshmatov',
            'email' => 'javohir@blackdoor.uz',
            'phone' => '+998906666666',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        // Warehouse employees
        $emp3User = User::create([
            'name' => 'Saidova Mohira',
            'email' => 'mohira@blackdoor.uz',
            'phone' => '+998907777777',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $emp4User = User::create([
            'name' => 'Shaxzod Rahmonov',
            'email' => 'shaxzod@blackdoor.uz',
            'phone' => '+998908888888',
            'password' => Hash::make('password123'),
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        // 2. Objects
        $factory = Obj::create([
            'name' => 'Toshkent Tekstil Zavodi',
            'type' => ObjectType::Factory,
            'address' => 'Toshkent sh., Chilonzor tumani',
            'is_active' => true,
        ]);

        $warehouse = Obj::create([
            'name' => 'Markaziy Omborxona',
            'type' => ObjectType::Warehouse,
            'address' => 'Toshkent viloyati, Zangiota tumani',
            'is_active' => true,
        ]);

        // 3. Object Managers
        ObjectManager::create([
            'object_id' => $factory->id,
            'user_id' => $factoryMgrUser->id,
            'assigned_at' => now(),
        ]);

        ObjectManager::create([
            'object_id' => $warehouse->id,
            'user_id' => $warehouseMgrUser->id,
            'assigned_at' => now(),
        ]);

        // 4. Object Employees
        ObjectEmployee::create([
            'object_id' => $factory->id,
            'user_id' => $emp1User->id,
            'position' => 'Ishlab chiqarish operatori',
            'daily_rate_currency' => Currency::UZS,
            'daily_rate' => 10000000, // 100 000.00 UZS in tiyin
            'monthly_rate_currency' => Currency::UZS,
            'monthly_rate' => 0,
            'hired_at' => now()->subMonths(6)->toDateString(),
            'permissions' => ['warehouse'],
            'is_active' => true,
        ]);

        ObjectEmployee::create([
            'object_id' => $factory->id,
            'user_id' => $emp2User->id,
            'position' => 'Katta usta',
            'daily_rate_currency' => Currency::UZS,
            'daily_rate' => 15000000, // 150 000.00 UZS
            'monthly_rate_currency' => Currency::UZS,
            'monthly_rate' => 0,
            'hired_at' => now()->subMonths(3)->toDateString(),
            'permissions' => ['warehouse', 'transactions'],
            'is_active' => true,
        ]);

        ObjectEmployee::create([
            'object_id' => $warehouse->id,
            'user_id' => $emp3User->id,
            'position' => 'Omborchi',
            'daily_rate_currency' => Currency::UZS,
            'daily_rate' => 0,
            'monthly_rate_currency' => Currency::UZS,
            'monthly_rate' => 300000000, // 3 000 000.00 UZS
            'hired_at' => now()->subMonths(12)->toDateString(),
            'permissions' => ['warehouse'],
            'is_active' => true,
        ]);

        ObjectEmployee::create([
            'object_id' => $warehouse->id,
            'user_id' => $emp4User->id,
            'position' => 'Yordamchi omborchi',
            'daily_rate_currency' => Currency::UZS,
            'daily_rate' => 0,
            'monthly_rate_currency' => Currency::UZS,
            'monthly_rate' => 250000000, // 2 500 000.00 UZS
            'hired_at' => now()->subMonths(2)->toDateString(),
            'permissions' => ['warehouse', 'transactions'],
            'is_active' => true,
        ]);

        // 5. Object Cash Accounts
        $factoryCashAcc = ObjectCashAccount::create([
            'object_id' => $factory->id,
            'name' => 'TTZ G\'aznasi',
            'type' => CashAccountType::Cash->value,
            'is_active' => true,
        ]);

        $warehouseCashAcc = ObjectCashAccount::create([
            'object_id' => $warehouse->id,
            'name' => 'MO G\'aznasi',
            'type' => CashAccountType::Cash->value,
            'is_active' => true,
        ]);

        // 6. Object Cash Balances
        ObjectCashBalance::create([
            'object_cash_account_id' => $factoryCashAcc->id,
            'currency' => Currency::UZS,
            'balance' => 500000000, // 5 000 000.00 UZS
        ]);
        ObjectCashBalance::create([
            'object_cash_account_id' => $factoryCashAcc->id,
            'currency' => Currency::USD,
            'balance' => 100000, // 1 000.00 USD
        ]);

        ObjectCashBalance::create([
            'object_cash_account_id' => $warehouseCashAcc->id,
            'currency' => Currency::UZS,
            'balance' => 300000000, // 3 000 000.00 UZS
        ]);
        ObjectCashBalance::create([
            'object_cash_account_id' => $warehouseCashAcc->id,
            'currency' => Currency::USD,
            'balance' => 50000, // 500.00 USD
        ]);

        // 7. Currency Rate
        CurrencyRate::create([
            'rate_uzs_per_usd' => 1250000, // 1 USD = 12 500.00 UZS
            'set_by' => $admin->id,
            'effective_date' => now()->toDateString(),
        ]);

        // 8. Global/Object Transaction Categories (Operational)
        $salCat = ObjectTransactionCategory::create([
            'name' => 'Ish haqi',
            'type' => 'expense',
            'is_active' => true,
        ]);
        $advCat = ObjectTransactionCategory::create([
            'name' => 'Avans',
            'type' => 'expense',
            'is_active' => true,
        ]);
        $incCat = ObjectTransactionCategory::create([
            'name' => 'Keltirildi',
            'type' => 'income',
            'is_active' => true,
        ]);
        $othCat = ObjectTransactionCategory::create([
            'name' => 'Boshqa xarajatlar',
            'type' => 'expense',
            'is_active' => true,
        ]);

        // 9. Products
        $cement = Product::create([
            'name' => 'Sement (M500)',
            'unit' => ProductUnit::Kg,
            'min_stock_level' => 50,
            'is_active' => true,
        ]);
        $sand = Product::create([
            'name' => 'Elangan qum',
            'unit' => ProductUnit::CubicMeter,
            'min_stock_level' => 10,
            'is_active' => true,
        ]);
        $brick = Product::create([
            'name' => 'G\'isht (Pishgan)',
            'unit' => ProductUnit::Piece,
            'min_stock_level' => 1000,
            'is_active' => true,
        ]);

        // 10. Warehouse Stocks
        WarehouseStock::create([
            'object_id' => $factory->id,
            'product_id' => $cement->id,
            'quantity' => 100,
        ]);
        WarehouseStock::create([
            'object_id' => $factory->id,
            'product_id' => $sand->id,
            'quantity' => 20,
        ]);

        WarehouseStock::create([
            'object_id' => $warehouse->id,
            'product_id' => $brick->id,
            'quantity' => 5000,
        ]);
        WarehouseStock::create([
            'object_id' => $warehouse->id,
            'product_id' => $cement->id,
            'quantity' => 200,
        ]);

        // 11. Moliya (Qora Daftar) Cash Accounts & Balances
        $financeCash1 = CashAccount::create([
            'name' => 'Asosiy naqd kassa',
            'type' => CashAccountType::Cash,
            'is_active' => true,
        ]);
        CashBalance::create([
            'cash_account_id' => $financeCash1->id,
            'currency' => Currency::USD,
            'balance' => 100000000, // 1 000 000.00 USD in cents (so'rovlar case condition checks cents)
        ]);

        $financeCash2 = CashAccount::create([
            'name' => 'Bank hisobi',
            'type' => CashAccountType::Bank,
            'is_active' => true,
        ]);
        CashBalance::create([
            'cash_account_id' => $financeCash2->id,
            'currency' => Currency::UZS,
            'balance' => 50000000000, // 500 000 000.00 UZS in cents/tiyin
        ]);

        // 12. Counterparties
        $cp1 = Counterparty::create([
            'name' => 'Akmal Zokirov',
            'phone' => '+998901234567',
            'category' => CounterpartyCategory::Supplier,
            'created_by' => $admin->id,
        ]);

        $cp2 = Counterparty::create([
            'name' => 'Barno Savdo LLC',
            'phone' => '+998907654321',
            'category' => CounterpartyCategory::Client,
            'created_by' => $admin->id,
        ]);

        // 13. Finance Transaction Categories
        $fc1 = TransactionCategory::create([
            'name' => 'Sotish',
            'type' => 'income',
            'is_active' => true,
        ]);
        $fc2 = TransactionCategory::create([
            'name' => 'Boshqa',
            'type' => 'expense',
            'is_active' => true,
        ]);

        // 14. Finance Transactions
        Transaction::create([
            'cash_account_id' => $financeCash1->id,
            'counterparty_id' => $cp1->id,
            'category_id' => $fc2->id,
            'type' => TransactionType::Expense,
            'currency' => Currency::USD,
            'amount' => 450000, // 4 500.00 USD
            'balance_after' => 550000,
            'note' => 'Xomashyo xaridi',
            'transaction_date' => now()->toDateString(),
            'created_by' => $financier->id,
        ]);
    }
}
