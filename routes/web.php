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
        if ($user->email === 'itcloud.uz') {
            return redirect()->route('control.dashboard');
        }
        return redirect()->route('admin.dashboard');
    }
    if ($user->isFinancier()) {
        return redirect()->route('finance.dashboard');
    }
    return redirect()->route('manager.dashboard');
})->middleware('auth');

// --- License Client Activation Routes ---
Route::get('/license/activate', [\App\Http\Controllers\LicenseController::class, 'showActivateForm'])->name('license.activate');
Route::post('/license/activate/submit', [\App\Http\Controllers\LicenseController::class, 'submitActivation'])->name('license.activate.submit');

// --- PIN entry route (Requires auth and finance roles but bypasses PIN middleware)
Route::middleware(['auth', 'role:super_admin,financier'])->group(function () {
    Route::get('/finance/pin', [PinController::class, 'showPinForm'])->name('finance.pin');
    Route::post('/finance/pin/verify', [PinController::class, 'verifyPin'])->name('finance.pin.verify');
});

// --- Super Admin Routes --------------------------------------------------------
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // License Management
    Route::get('/license', [\App\Http\Controllers\LicenseController::class, 'showLicenseInfo'])->name('license.info');
    Route::post('/license/refresh', [\App\Http\Controllers\LicenseController::class, 'refreshLicense'])->name('license.refresh');
    
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
    Route::get('/objects/{object}', [AdminObjectController::class, 'show'])->name('objects.show');
    Route::get('/objects/{object}/edit', [AdminObjectController::class, 'edit'])->name('objects.edit');
    Route::put('/objects/{object}', [AdminObjectController::class, 'update'])->name('objects.update');
    Route::delete('/objects/{object}', [AdminObjectController::class, 'destroy'])->name('objects.destroy');
    
    // Sub-elements under Objects
    Route::post('/objects/{object}/cash-accounts', [AdminObjectController::class, 'storeCashAccount'])->name('objects.cash-accounts.store');
    Route::delete('/objects/{object}/cash-accounts/{cashAccount}', [AdminObjectController::class, 'destroyCashAccount'])->name('objects.cash-accounts.destroy');
    Route::post('/objects/{object}/warehouse-stocks', [AdminObjectController::class, 'storeWarehouseStock'])->name('objects.warehouse-stocks.store');
    Route::delete('/objects/{object}/warehouse-stocks/{warehouseStock}', [AdminObjectController::class, 'destroyWarehouseStock'])->name('objects.warehouse-stocks.destroy');
    Route::post('/objects/{object}/employees', [AdminObjectController::class, 'storeEmployee'])->name('objects.employees.store');
    Route::delete('/objects/{object}/employees/{employee}', [AdminObjectController::class, 'destroyEmployee'])->name('objects.employees.destroy');
    
    // Sub-managers under Objects
    Route::post('/objects/{object}/sub-managers', [\App\Http\Controllers\Admin\ObjectSubManagerController::class, 'store'])->name('objects.sub-managers.store');
    Route::delete('/objects/{object}/sub-managers/{subManager}', [\App\Http\Controllers\Admin\ObjectSubManagerController::class, 'destroy'])->name('objects.sub-managers.destroy');
    
    // Currency Rates Management
    Route::get('/currency-rates', [AdminCurrencyController::class, 'index'])->name('currency-rates');
    Route::post('/currency-rates', [AdminCurrencyController::class, 'store'])->name('currency-rates.store');
    Route::get('/currency-rates/fetch-cbu', [AdminCurrencyController::class, 'fetchCbuRate'])->name('currency-rates.fetch-cbu');
    
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

    // System & Personal Settings (PIN-locked)
    Route::get('/settings', [\App\Http\Controllers\Finance\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Finance\SettingController::class, 'update'])->name('settings.update');
    
    // Currency Rate Fetching
    Route::get('/currency-rates/fetch-cbu', [\App\Http\Controllers\Admin\CurrencyController::class, 'fetchCbuRate'])->name('currency-rates.fetch-cbu');
});

// --- Operational (Manager / Employee) Routes ----------------------------------
Route::middleware(['auth', 'role:manager,employee'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/', [ManagerDashboardController::class, 'index'])->name('dashboard');
    Route::post('/switch-object', [ManagerDashboardController::class, 'switchObject'])->name('switch-object');
    
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
    
    // Currency Rate Fetching
    Route::get('/currency-rates/fetch-cbu', [\App\Http\Controllers\Admin\CurrencyController::class, 'fetchCbuRate'])->name('currency-rates.fetch-cbu');
});

// --- Black Door Control Platform Routes ---
Route::prefix('control')->name('control.')->group(function () {
    
    // Public requests portal (Ariza qoldirish)
    Route::get('/portal/request', [\App\Http\Controllers\Control\PortalController::class, 'showRequestForm'])->name('portal.request');
    Route::post('/portal/request/submit', [\App\Http\Controllers\Control\PortalController::class, 'submitRequest'])->name('portal.request.submit');

    // Authenticated Admin routes (restricted to SuperAdmin)
    Route::middleware(['auth', 'role:super_admin'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Control\DashboardController::class, 'index'])->name('dashboard');
        
        // Products catalog
        Route::get('/products', [\App\Http\Controllers\Control\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [\App\Http\Controllers\Control\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [\App\Http\Controllers\Control\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [\App\Http\Controllers\Control\ProductController::class, 'show'])->name('products.show');
        Route::post('/products/{product}/version', [\App\Http\Controllers\Control\ProductController::class, 'storeVersion'])->name('products.version.store');
        Route::post('/products/{product}/plan', [\App\Http\Controllers\Control\ProductController::class, 'storePlan'])->name('products.plan.store');

        // Clients and licenses
        Route::get('/clients', [\App\Http\Controllers\Control\ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [\App\Http\Controllers\Control\ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [\App\Http\Controllers\Control\ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}', [\App\Http\Controllers\Control\ClientController::class, 'show'])->name('clients.show');
        Route::post('/clients/{client}/license', [\App\Http\Controllers\Control\ClientController::class, 'storeLicense'])->name('clients.license.store');
        Route::post('/licenses/{license}/toggle', [\App\Http\Controllers\Control\ClientController::class, 'toggleLicenseStatus'])->name('licenses.toggle');
        Route::post('/licenses/{license}/payment', [\App\Http\Controllers\Control\ClientController::class, 'storePayment'])->name('licenses.payment.store');

        // Requests management
        Route::get('/requests', [\App\Http\Controllers\Control\PortalController::class, 'listRequests'])->name('requests.index');
        Route::post('/requests/{clientRequest}/status', [\App\Http\Controllers\Control\PortalController::class, 'updateRequestStatus'])->name('requests.status.update');
    });
});
