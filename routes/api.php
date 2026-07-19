<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\ManagerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Public Routes ---
Route::post('/auth/login', [AuthController::class, 'login']);

// --- Authenticated Routes ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth profile & PIN verification
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/verify-pin', [AuthController::class, 'verifyPin']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // --- Super Admin Routes ---
    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        
        // User management
        Route::get('/users', [AdminController::class, 'listUsers']);
        Route::post('/users', [AdminController::class, 'storeUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);
        Route::post('/users/{user}/toggle', [AdminController::class, 'toggleActiveUser']);

        // Object management
        Route::get('/objects', [AdminController::class, 'listObjects']);
        Route::post('/objects', [AdminController::class, 'storeObject']);
        Route::put('/objects/{object}', [AdminController::class, 'updateObject']);
        Route::delete('/objects/{object}', [AdminController::class, 'destroyObject']);

        // Product management
        Route::get('/products', [AdminController::class, 'listProducts']);
        Route::post('/products', [AdminController::class, 'storeProduct']);
        Route::put('/products/{product}', [AdminController::class, 'updateProduct']);
        Route::delete('/products/{product}', [AdminController::class, 'destroyProduct']);

        // Currency management
        Route::get('/currency-rates', [AdminController::class, 'getCurrencyRates']);
        Route::post('/currency-rates', [AdminController::class, 'storeCurrencyRate']);
        Route::get('/currency-rates/fetch-cbu', [AdminController::class, 'fetchCbuRate']);

        // Audit log
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);

        // Hard Delete Transaction
        Route::delete('/transactions/{transaction}', [AdminController::class, 'destroyTransaction']);
    });

    // --- Financier (and Admin) Routes ---
    Route::middleware('role:super_admin,financier')->prefix('finance')->group(function () {
        Route::get('/currency-rate', [FinanceController::class, 'getCurrentRate']);
        Route::get('/cash-accounts', [FinanceController::class, 'listCashAccounts']);
        
        Route::get('/counterparties', [FinanceController::class, 'listCounterparties']);
        Route::post('/counterparties', [FinanceController::class, 'storeCounterparty']);
        Route::put('/counterparties/{counterparty}', [FinanceController::class, 'updateCounterparty']);
        Route::delete('/counterparties/{counterparty}', [FinanceController::class, 'destroyCounterparty']);
        Route::get('/counterparties/{counterparty}', [FinanceController::class, 'showCounterparty']);

        Route::get('/categories', [FinanceController::class, 'listCategories']);
        Route::post('/categories', [FinanceController::class, 'storeCategory']);
        Route::put('/categories/{category}', [FinanceController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [FinanceController::class, 'destroyCategory']);

        Route::get('/transactions', [FinanceController::class, 'listTransactions']);
        Route::post('/transactions', [FinanceController::class, 'storeTransaction']);
        Route::post('/transactions/{transaction}/storno', [FinanceController::class, 'stornoTransaction']);

        Route::get('/reports', [FinanceController::class, 'getReport']);
    });

    // --- Object Manager Routes ---
    Route::middleware('role:manager')->prefix('manager')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard']);
        
        // Employee management
        Route::get('/employees', [ManagerController::class, 'listEmployees']);
        Route::post('/employees', [ManagerController::class, 'storeEmployee']);
        Route::put('/employees/{employee}', [ManagerController::class, 'updateEmployee']);
        Route::delete('/employees/{employee}', [ManagerController::class, 'destroyEmployee']);
        Route::post('/employees/{employee}/toggle', [ManagerController::class, 'toggleActiveEmployee']);
        Route::post('/employees/{employee}/pay', [ManagerController::class, 'paySalary']);

        // Object transaction management
        Route::get('/transactions', [ManagerController::class, 'listTransactions']);
        Route::post('/transactions', [ManagerController::class, 'storeTransaction']);

        // Warehouse stock & movements
        Route::get('/stocks', [ManagerController::class, 'listStocks']);
        Route::post('/movements', [ManagerController::class, 'storeMovement']);
        Route::delete('/movements/{movement}', [ManagerController::class, 'destroyMovement']);
        Route::post('/inventory-check', [ManagerController::class, 'storeInventoryCheck']);

        // Daily summary
        Route::get('/daily-summary', [ManagerController::class, 'dailySummary']);
    });
});
