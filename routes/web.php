<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\RecapSaleController;
use App\Http\Controllers\DetailSaleController;
use App\Http\Controllers\FakturPajakController;
use App\Http\Controllers\MappingCustomerController;
use App\Http\Controllers\MappingProductController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\OlahDataController;

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
    Route::delete('customers/clear', [CustomerController::class, 'clear'])->name('customers.clear');
    Route::resource('customers', CustomerController::class);
    Route::resource('customer-groups', CustomerGroupController::class);
    Route::resource('payment-terms', PaymentTermController::class);
    Route::resource('bank-accounts', BankAccountController::class);
    Route::get('recap-sales', [RecapSaleController::class, 'index'])->name('recap-sales.index');
    Route::post('recap-sales/import', [RecapSaleController::class, 'import'])->name('recap-sales.import');
    Route::delete('recap-sales/clear', [RecapSaleController::class, 'clear'])->name('recap-sales.clear');
    Route::get('detail-sales', [DetailSaleController::class, 'index'])->name('detail-sales.index');
    Route::post('detail-sales/import', [DetailSaleController::class, 'import'])->name('detail-sales.import');
    Route::get('detail-sales/items/{docNumber}', [DetailSaleController::class, 'items'])->name('detail-sales.items');
    Route::delete('detail-sales/clear', [DetailSaleController::class, 'clear'])->name('detail-sales.clear');
    Route::post('detail-sales/export', [DetailSaleController::class, 'export'])->name('detail-sales.export');
    Route::post('detail-sales/export-xml-external', [DetailSaleController::class, 'exportXmlExternal'])->name('detail-sales.export-xml-external');
    Route::put('detail-sales/faktur-pajak-status', [DetailSaleController::class, 'updateFakturPajakStatus'])->name('detail-sales.fp-status');
    Route::get('faktur-pajak', [FakturPajakController::class, 'index'])->name('faktur-pajak.index');
    Route::post('faktur-pajak/import', [FakturPajakController::class, 'import'])->name('faktur-pajak.import');
    Route::delete('faktur-pajak/clear', [FakturPajakController::class, 'clear'])->name('faktur-pajak.clear');
    Route::put('faktur-pajak/reference', [FakturPajakController::class, 'updateReference'])->name('faktur-pajak.reference');
    Route::get('mapping-customers', [MappingCustomerController::class, 'index'])->name('mapping-customers.index');
    Route::post('mapping-customers', [MappingCustomerController::class, 'store'])->name('mapping-customers.store');
    Route::post('mapping-customers/import', [MappingCustomerController::class, 'import'])->name('mapping-customers.import');
    Route::delete('mapping-customers/clear', [MappingCustomerController::class, 'clear'])->name('mapping-customers.clear');
    Route::put('mapping-customers/{mappingCustomer}', [MappingCustomerController::class, 'update'])->name('mapping-customers.update');
    Route::delete('mapping-customers/{mappingCustomer}', [MappingCustomerController::class, 'destroy'])->name('mapping-customers.destroy');
    Route::get('mapping-products', [MappingProductController::class, 'index'])->name('mapping-products.index');
    Route::post('mapping-products', [MappingProductController::class, 'store'])->name('mapping-products.store');
    Route::post('mapping-products/import', [MappingProductController::class, 'import'])->name('mapping-products.import');
    Route::delete('mapping-products/clear', [MappingProductController::class, 'clear'])->name('mapping-products.clear');
    Route::put('mapping-products/{mappingProduct}', [MappingProductController::class, 'update'])->name('mapping-products.update');
    Route::delete('mapping-products/{mappingProduct}', [MappingProductController::class, 'destroy'])->name('mapping-products.destroy');

    // Olah Data
    Route::prefix('olah-data')->name('olah-data.')->group(function () {
    Route::get('perubahan-data-internal', [OlahDataController::class, 'perubahanDataInternal'])->name('perubahan-data-internal');
    Route::delete('perubahan-data-internal/clear', [OlahDataController::class, 'clearPerubahanInternal'])->name('perubahan-data-internal.clear');
    Route::delete('perubahan-data-internal/{session}', [OlahDataController::class, 'destroySession'])->name('perubahan-data-internal.destroy');
        Route::get('data-external-tidak-same', [OlahDataController::class, 'dataExternalTidakSama'])->name('data-external-tidak-sama');
        Route::get('rekonsiliasi-harian-internal', [OlahDataController::class, 'rekonsiliasiHarianInternal'])->name('rekonsiliasi-harian-internal');
        Route::put('rekonsiliasi-harian-internal/receipt', [OlahDataController::class, 'updateReceipt'])->name('rekonsiliasi-harian-internal.receipt');
        Route::put('rekonsiliasi-harian-internal/bulk-receipt', [OlahDataController::class, 'bulkUpdateReceipt'])->name('rekonsiliasi-harian-internal.bulk-receipt');
        Route::put('rekonsiliasi-harian-internal/bulk-exchange', [OlahDataController::class, 'bulkUpdateExchange'])->name('rekonsiliasi-harian-internal.bulk-exchange');
    });

    // Piutang
    Route::get('invoices/outstanding', [InvoiceController::class, 'outstanding'])->name('invoices.outstanding');
    Route::post('invoices/outstanding/import', [InvoiceController::class, 'importOutstanding'])->name('invoices.outstanding.import');
    Route::post('invoices/outstanding/exception', [InvoiceController::class, 'setException'])->name('invoices.outstanding.exception');
    Route::get('invoices/anomali', [InvoiceController::class, 'anomali'])->name('invoices.anomali');
    Route::get('invoices/standard-report', [InvoiceController::class, 'standardReport'])->name('invoices.standard-report');
    Route::get('invoices/standard-report/export', [InvoiceController::class, 'standardReportExport'])->name('invoices.standard-report.export');
    Route::get('invoices/standard-report/print', [InvoiceController::class, 'standardReportPrint'])->name('invoices.standard-report.print');
    Route::get('invoices/china-report', [InvoiceController::class, 'chinaReport'])->name('invoices.china-report');
    Route::get('invoices/china-report/export', [InvoiceController::class, 'chinaReportExport'])->name('invoices.china-report.export');
    Route::get('invoices/china-report/print', [InvoiceController::class, 'chinaReportPrint'])->name('invoices.china-report.print');
    Route::get('invoices/china-report/global', [InvoiceController::class, 'chinaReportGlobal'])->name('invoices.china-report.global');
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
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::resource('users', UserController::class);
    });
});
