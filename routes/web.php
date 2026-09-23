<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;

// Public Routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
require __DIR__.'/auth.php';

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Master Data
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('customers/import/template', [CustomerController::class, 'importTemplate'])->name('customers.import.template');
    Route::resource('customers', CustomerController::class);
    Route::resource('customer-groups', CustomerGroupController::class);
    Route::resource('payment-terms', PaymentTermController::class);
    Route::resource('bank-accounts', BankAccountController::class);
    
    // Piutang
    Route::get('invoices/aging', [InvoiceController::class, 'aging'])->name('invoices.aging');
    Route::post('invoices/import', [InvoiceController::class, 'import'])->name('invoices.import');
    Route::resource('invoices', InvoiceController::class)->except(['show']);
    
    // Pembayaran
    Route::get('payments/internal/create', [PaymentController::class, 'createInternal'])->name('payments.internal.create');
    Route::post('payments/internal', [PaymentController::class, 'storeInternal'])->name('payments.internal.store');
    Route::get('payments/external/create', [PaymentController::class, 'createExternal'])->name('payments.external.create');
    Route::post('payments/external', [PaymentController::class, 'storeExternal'])->name('payments.external.store');
    Route::post('payments/{payment}/allocate', [PaymentController::class, 'allocate'])->name('payments.allocate');
    Route::post('payments/clone/{payment}', [PaymentController::class, 'clone'])->name('payments.clone');
    Route::resource('payments', PaymentController::class)->only(['index', 'show']);
    
    // Bank
    Route::post('bank-transactions/import', [BankTransactionController::class, 'import'])->name('bank-transactions.import');
    Route::resource('bank-transactions', BankTransactionController::class)->only(['index', 'show']);
    
    // Rekonsiliasi
    Route::get('reconciliations/center', [ReconciliationController::class, 'center'])->name('reconciliations.center');
    Route::post('reconciliations/match', [ReconciliationController::class, 'match'])->name('reconciliations.match');
    Route::resource('reconciliations', ReconciliationController::class)->only(['index', 'show']);
    
    // Collection
    Route::prefix('collection')->name('collection.')->group(function () {
        Route::get('/', [CollectionController::class, 'dashboard'])->name('dashboard');
        Route::get('/queue', [CollectionController::class, 'queue'])->name('queue');
        Route::post('/follow-up', [CollectionController::class, 'followUp'])->name('follow-up');
        Route::post('/whatsapp', [CollectionController::class, 'whatsapp'])->name('whatsapp');
        Route::post('/email', [CollectionController::class, 'email'])->name('email');
        Route::get('/schedule', [CollectionController::class, 'schedule'])->name('schedule');
        Route::post('/schedule', [CollectionController::class, 'storeSchedule'])->name('store-schedule');
        Route::get('/promise', [CollectionController::class, 'promise'])->name('promise');
        Route::post('/promise', [CollectionController::class, 'storePromise'])->name('store-promise');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/aging', [ReportController::class, 'aging'])->name('aging');
        Route::get('/outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
        Route::get('/payment', [ReportController::class, 'payment'])->name('payment');
        Route::get('/reconciliation', [ReportController::class, 'reconciliation'])->name('reconciliation');
        Route::get('/collection', [ReportController::class, 'collection'])->name('collection');
    });
    
    // System
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class);
    });
});
