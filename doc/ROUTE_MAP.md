# Route Map

## Overview
Peta semua route dalam AR Finance Tools.

---

## Web Routes (routes/web.php)

### Auth
```php
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
```

### Dashboard
```php
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
```

### Tool 1: Invoice Exclusion
```php
Route::resource('exclusions', ExclusionController::class);
Route::post('exclusions/bulk-import', [ExclusionController::class, 'bulkImport'])->name('exclusions.bulk-import');
Route::put('exclusions/{id}/revert', [ExclusionController::class, 'revert'])->name('exclusions.revert');
Route::get('exclusions/statistics', [ExclusionController::class, 'statistics'])->name('exclusions.statistics');
```

### Tool 2: Aging Report
```php
Route::get('/aging-report', [AgingController::class, 'index'])->name('aging.index');
Route::post('/aging-report/generate', [AgingController::class, 'generate'])->name('aging.generate');
Route::get('/aging-report/export', [AgingController::class, 'export'])->name('aging.export');
Route::get('/aging-report/trend', [AgingController::class, 'trend'])->name('aging.trend');
```

### Tool 3: AR Reconciliation
```php
Route::get('/reconciliation', [ReconController::class, 'index'])->name('recon.index');
Route::post('/reconciliation/import', [ReconController::class, 'importBank'])->name('recon.import');
Route::post('/reconciliation/match', [ReconController::class, 'match'])->name('recon.match');
Route::get('/reconciliation/report', [ReconController::class, 'report'])->name('recon.report');
Route::get('/reconciliation/export', [ReconController::class, 'export'])->name('recon.export');
```

### Tool 4: Reminders
```php
Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
Route::post('/reminders/send', [ReminderController::class, 'sendBatch'])->name('reminders.send');
Route::get('/reminders/log', [ReminderController::class, 'log'])->name('reminders.log');
Route::get('/reminders/config', [ReminderController::class, 'config'])->name('reminders.config');
Route::put('/reminders/config', [ReminderController::class, 'updateConfig'])->name('reminders.update-config');
```

### Tool 5: Credit Limit
```php
Route::get('/credit-monitor', [CreditController::class, 'index'])->name('credit.index');
Route::get('/credit-monitor/{customer}', [CreditController::class, 'detail'])->name('credit.detail');
Route::put('/credit-monitor/{customer}', [CreditController::class, 'update'])->name('credit.update');
Route::get('/credit-monitor/{customer}/forecast', [CreditController::class, 'forecast'])->name('credit.forecast');
```

### Tool 7: Customer Analyzer
```php
Route::get('/analyzer', [AnalyzerController::class, 'index'])->name('analyzer.index');
Route::get('/analyzer/customer/{customer}', [AnalyzerController::class, 'analyze'])->name('analyzer.customer');
Route::get('/analyzer/cohort', [AnalyzerController::class, 'cohort'])->name('analyzer.cohort');
Route::get('/analyzer/export/{customer}', [AnalyzerController::class, 'export'])->name('analyzer.export');
```

### Tool 8: Collection Tracker
```php
Route::get('/tracker', [TrackerController::class, 'index'])->name('tracker.index');
Route::get('/tracker/invoice/{invoice}', [TrackerController::class, 'detail'])->name('tracker.detail');
Route::post('/tracker/action', [TrackerController::class, 'addAction'])->name('tracker.add-action');
Route::put('/tracker/status/{invoice}', [TrackerController::class, 'updateStatus'])->name('tracker.update-status');
Route::post('/tracker/escalate/{invoice}', [TrackerController::class, 'escalate'])->name('tracker.escalate');
```

### Admin
```php
Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class);
    Route::get('/audit-log', [AuditController::class, 'index'])->name('admin.audit-log');
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings');
});
```

---

## API Routes (routes/api.php)

### Public
```php
Route::post('/login', [ApiAuthController::class, 'login']);
```

### Protected (Sanctum)
```php
Route::middleware('auth:sanctum')->group(function () {
    // Dashboard
    Route::get('/dashboard/kpis', [ApiDashboardController::class, 'kpis']);
    Route::get('/dashboard/charts', [ApiDashboardController::class, 'charts']);
    Route::get('/dashboard/alerts', [ApiDashboardController::class, 'alerts']);

    // Exclusions
    Route::apiResource('exclusions', ApiExclusionController::class);
    Route::post('exclusions/bulk-import', [ApiExclusionController::class, 'bulkImport']);
    Route::put('exclusions/{id}/revert', [ApiExclusionController::class, 'revert']);

    // Aging
    Route::get('aging-report', [ApiAgingController::class, 'index']);
    Route::get('aging-report/export', [ApiAgingController::class, 'export']);

    // Reconciliation
    Route::post('reconciliation/import', [ApiReconController::class, 'import']);
    Route::post('reconciliation/match', [ApiReconController::class, 'match']);
    Route::get('reconciliation/report', [ApiReconController::class, 'report']);

    // Credit Limit
    Route::get('credit-limit/monitor', [ApiCreditController::class, 'monitor']);
    Route::get('credit-limit/{customer}', [ApiCreditController::class, 'detail']);
    Route::post('credit-limit/check-so-approval', [ApiCreditController::class, 'checkSO']);

    // Tracker
    Route::get('tracker/invoices', [ApiTrackerController::class, 'index']);
    Route::post('tracker/action', [ApiTrackerController::class, 'addAction']);
    Route::put('tracker/status/{invoice}', [ApiTrackerController::class, 'updateStatus']);
});
```

---

## Route Summary

| Section | Web Routes | API Routes |
|---------|-----------|------------|
| Auth | 3 | 1 |
| Dashboard | 1 | 3 |
| Exclusion | 5 | 5 |
| Aging | 4 | 2 |
| Reconciliation | 5 | 3 |
| Reminders | 4 | - |
| Credit Limit | 4 | 3 |
| Analyzer | 4 | - |
| Tracker | 5 | 3 |
| Admin | 3 | - |
| **Total** | **38** | **20** |
