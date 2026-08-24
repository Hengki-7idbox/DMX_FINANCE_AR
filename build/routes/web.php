<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExclusionController;
use App\Http\Controllers\AgingController;
use App\Http\Controllers\ReconController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\AnalyzerController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\SettingController;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Tool 1: Invoice Exclusion
    Route::resource('exclusions', ExclusionController::class)->except(['show', 'edit', 'update', 'destroy']);
    Route::post('exclusions/bulk-import', [ExclusionController::class, 'bulkImport'])->name('exclusions.bulk-import');
    Route::put('exclusions/{exclusion}/revert', [ExclusionController::class, 'revert'])->name('exclusions.revert');
    Route::get('exclusions/statistics', [ExclusionController::class, 'statistics'])->name('exclusions.statistics');

    // Tool 2: Aging Report
    Route::get('/aging-report', [AgingController::class, 'index'])->name('aging.index');
    Route::post('/aging-report/generate', [AgingController::class, 'generate'])->name('aging.generate');
    Route::get('/aging-report/export', [AgingController::class, 'export'])->name('aging.export');
    Route::get('/aging-report/trend', [AgingController::class, 'trend'])->name('aging.trend');

    // Laporan: Aging China
    Route::get('/aging-china', function () {
        return view('laporan.aging-china');
    })->name('laporan.aging-china');

    // Laporan: Aging Normal
    Route::get('/aging-normal', function () {
        return view('laporan.aging-normal');
    })->name('laporan.aging-normal');

    // Tool 3: AR Reconciliation
    Route::get('/reconciliation', [ReconController::class, 'index'])->name('recon.index');
    Route::post('/reconciliation/import', [ReconController::class, 'importBank'])->name('recon.import');
    Route::post('/reconciliation/match', [ReconController::class, 'match'])->name('recon.match');
    Route::get('/reconciliation/report', [ReconController::class, 'report'])->name('recon.report');
    Route::get('/reconciliation/export', [ReconController::class, 'export'])->name('recon.export');

    // Tool 4: Reminders
    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('/reminders/send', [ReminderController::class, 'sendBatch'])->name('reminders.send');
    Route::get('/reminders/log', [ReminderController::class, 'log'])->name('reminders.log');
    Route::get('/reminders/config', [ReminderController::class, 'config'])->name('reminders.config');
    Route::put('/reminders/config', [ReminderController::class, 'updateConfig'])->name('reminders.update-config');

    // Tool 5: Credit Limit
    Route::get('/credit-monitor', [CreditController::class, 'index'])->name('credit.index');
    Route::get('/credit-monitor/{customer}', [CreditController::class, 'detail'])->name('credit.detail');
    Route::put('/credit-monitor/{customer}', [CreditController::class, 'update'])->name('credit.update');
    Route::get('/credit-monitor/{customer}/forecast', [CreditController::class, 'forecast'])->name('credit.forecast');

    // Tool 7: Customer Analyzer
    Route::get('/analyzer', [AnalyzerController::class, 'index'])->name('analyzer.index');
    Route::get('/analyzer/customer/{customer}', [AnalyzerController::class, 'analyze'])->name('analyzer.customer');
    Route::get('/analyzer/cohort', [AnalyzerController::class, 'cohort'])->name('analyzer.cohort');
    Route::get('/analyzer/export/{customer}', [AnalyzerController::class, 'export'])->name('analyzer.export');

    // Tool 8: Collection Tracker
    Route::get('/tracker', [TrackerController::class, 'index'])->name('tracker.index');
    Route::get('/tracker/invoice/{invoice}', [TrackerController::class, 'detail'])->name('tracker.detail');
    Route::post('/tracker/action', [TrackerController::class, 'addAction'])->name('tracker.add-action');
    Route::put('/tracker/status/{invoice}', [TrackerController::class, 'updateStatus'])->name('tracker.update-status');
    Route::post('/tracker/escalate/{invoice}', [TrackerController::class, 'escalate'])->name('tracker.escalate');

    // Customer Data (NEW)
    Route::get('/customers', function () {
        return view('customers.index');
    })->name('customers.index');

    // Import Accurate (NEW)
    Route::get('/import-accurate', function () {
        return view('accurate.index');
    })->name('accurate.index');

    Route::post('/import-accurate/import', [\App\Http\Controllers\AccurateController::class, 'import'])->name('accurate.import');
    Route::delete('/import-accurate/clear', [\App\Http\Controllers\AccurateController::class, 'clear'])->name('accurate.clear');

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'edit', 'create']);
        Route::get('/audit-log', [AuditController::class, 'index'])->name('admin.audit-log');
        Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings');
    });
});
