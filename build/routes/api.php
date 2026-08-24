<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExclusionController;
use App\Http\Controllers\Api\AgingController;
use App\Http\Controllers\Api\ReconController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\TrackerController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $r) => $r->user());

    // Dashboard
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
    Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
    Route::get('/dashboard/alerts', [DashboardController::class, 'alerts']);

    // Exclusions
    Route::apiResource('exclusions', ExclusionController::class);
    Route::post('exclusions/bulk-import', [ExclusionController::class, 'bulkImport']);
    Route::put('exclusions/{id}/revert', [ExclusionController::class, 'revert']);

    // Aging
    Route::get('aging-report', [AgingController::class, 'index']);
    Route::get('aging-report/export', [AgingController::class, 'export']);

    // Reconciliation
    Route::post('reconciliation/import', [ReconController::class, 'import']);
    Route::post('reconciliation/match', [ReconController::class, 'match']);
    Route::get('reconciliation/report', [ReconController::class, 'report']);

    // Credit Limit
    Route::get('credit-limit/monitor', [CreditController::class, 'monitor']);
    Route::get('credit-limit/{customer}', [CreditController::class, 'detail']);
    Route::post('credit-limit/check-so-approval', [CreditController::class, 'checkSO']);

    // Tracker
    Route::get('tracker/invoices', [TrackerController::class, 'index']);
    Route::post('tracker/action', [TrackerController::class, 'addAction']);
    Route::put('tracker/status/{invoice}', [TrackerController::class, 'updateStatus']);
});
