<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PinController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ObjectController as AdminObjectController;
use App\Http\Controllers\Admin\CurrencyController as AdminCurrencyController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\CashAccountController as FinanceCashAccountController;
use App\Http\Controllers\Finance\TransactionController as FinanceTransactionController;
use App\Http\Controllers\Finance\CounterpartyController as FinanceCounterpartyController;
use App\Http\Controllers\Finance\CategoryController as FinanceCategoryController;
use App\Http\Controllers\Finance\ReportController as FinanceReportController;
use App\Http\Controllers\Object\ManagerDashboardController;
use App\Http\Controllers\Object\EmployeeController as ManagerEmployeeController;
use App\Http\Controllers\Object\ObjectTransactionController as ManagerTransactionController;
use App\Http\Controllers\Object\WarehouseController as ManagerWarehouseController;

// --- Guest Routes -------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Redirect root to dashboard based on role
Route::get('/', function () {
    $user = auth()->user();
    if (! $user) {
        return redirect()->route('login');
    }
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->isFinancier()) {
        return redirect()->route('finance.dashboard');
    }
    return redirect()->route('manager.dashboard');
})->middleware('auth');

// --- PIN entry route (Requires auth and finance roles but bypasses PIN middleware)
Route::middleware(['auth', 'role:super_admin,financier'])->group(function () {
    Route::get('/finance/pin', [PinController::class, 'showPinForm'])->name('finance.pin');
    Route::post('/finance/pin/verify', [PinController::class, 'verifyPin'])->name('finance.pin.verify');
});

// --- Super Admin Routes --------------------------------------------------------
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Users Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    
    // Objects Management
    Route::get('/objects', [AdminObjectController::class, 'index'])->name('objects.index');
    Route::get('/objects/create', [AdminObjectController::class, 'create'])->name('objects.create');
    Route::post('/objects', [AdminObjectController::class, 'store'])->name('objects.store');
    Route::get('/objects/{object}/edit', [AdminObjectController::class, 'edit'])->name('objects.edit');
    Route::put('/objects/{object}', [AdminObjectController::class, 'update'])->name('objects.update');
    Route::delete('/objects/{object}', [AdminObjectController::class, 'destroy'])->name('objects.destroy');
    
    // Currency Rates Management
    Route::get('/currency-rates', [AdminCurrencyController::class, 'index'])->name('currency-rates');
    Route::post('/currency-rates', [AdminCurrencyController::class, 'store'])->name('currency-rates.store');
    
    // Audit Log
    Route::get('/audit-log', [AdminAuditController::class, 'index'])->name('audit-log');
});

// --- Finance (Qora Daftar) Routes ----------------------------------------------
Route::middleware(['auth', 'role:super_admin,financier', 'finance.pin'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/', [FinanceDashboardController::class, 'index'])->name('dashboard');
    
    // Cash accounts
    Route::get('/cash-accounts', [FinanceCashAccountController::class, 'index'])->name('cash-accounts.index');
    Route::get('/cash-accounts/create', [FinanceCashAccountController::class, 'create'])->name('cash-accounts.create');
    Route::post('/cash-accounts', [FinanceCashAccountController::class, 'store'])->name('cash-accounts.store');
    
    // Transactions
    Route::get('/transactions', [FinanceTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [FinanceTransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transactions/{transaction}/storno', [FinanceTransactionController::class, 'storno'])->name('transactions.storno');
    
    // Counterparties
    Route::get('/counterparties', [FinanceCounterpartyController::class, 'index'])->name('counterparties.index');
    Route::post('/counterparties', [FinanceCounterpartyController::class, 'store'])->name('counterparties.store');
    Route::get('/counterparties/{counterparty}', [FinanceCounterpartyController::class, 'show'])->name('counterparties.show');
    
    // Categories
    Route::get('/categories', [FinanceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [FinanceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [FinanceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [FinanceCategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Reports
    Route::get('/reports', [FinanceReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/export/{format}', [FinanceReportController::class, 'export'])->name('reports.export');
});

// --- Operational (Manager / Employee) Routes ----------------------------------
Route::middleware(['auth', 'role:manager,employee'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/', [ManagerDashboardController::class, 'index'])->name('dashboard');
    
    // Employees Management (within assigned object)
    Route::get('/employees', [ManagerEmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [ManagerEmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{employee}', [ManagerEmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/{employee}/toggle-active', [ManagerEmployeeController::class, 'toggleActive'])->name('employees.toggle-active');
    Route::post('/employees/{employee}/pay', [ManagerEmployeeController::class, 'paySalary'])->name('employees.pay');
    
    // Object Mini-Cash Transactions
    Route::get('/transactions', [ManagerTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [ManagerTransactionController::class, 'store'])->name('transactions.store');
    
    // Warehouse management
    Route::get('/warehouse', [ManagerWarehouseController::class, 'index'])->name('warehouse.index');
    Route::post('/warehouse/movement', [ManagerWarehouseController::class, 'movement'])->name('warehouse.movement');
    Route::post('/warehouse/check', [ManagerWarehouseController::class, 'check'])->name('warehouse.check');
});
