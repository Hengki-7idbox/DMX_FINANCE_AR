# Permission System

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Sistem permission menggunakan Laravel Spatie Permission untuk mengelola akses user.

## Roles

```text
super_admin   - Akses penuh ke seluruh sistem
finance_ar    - Mengelola invoice, payment, aging, rekonsiliasi
collector     - Melakukan collection dan follow-up
manager       - Monitoring, approval, dan reporting
viewer        - Melihat data dan laporan
auditor       - Melihat data, rekonsiliasi, dan audit trail
```

## Permissions

### Customer

```text
customer.view
customer.create
customer.update
customer.delete
```

### Invoice

```text
invoice.view
invoice.create
invoice.update
invoice.delete
invoice.import
invoice.export
```

### Payment

```text
payment.view
payment.create
payment.allocate
payment.reconcile
payment.export
```

### Bank

```text
bank.view
bank.import
bank.reconcile
bank.export
```

### Collection

```text
collection.view
collection.create
collection.send
collection.schedule
```

### Report

```text
report.view
report.export
report.aging
report.outstanding
report.reconciliation
```

### System

```text
system.user.view
system.user.create
system.user.update
system.user.delete
system.role.view
system.role.manage
system.settings.view
system.settings.update
system.audit.view
```

## Implementation

### Migration

```php
// database/seeders/RolesAndPermissionsSeeder.php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$roles = [
    'super_admin' => '*',
    'finance_ar' => [
        'customer.view', 'customer.create', 'customer.update',
        'invoice.view', 'invoice.create', 'invoice.update', 'invoice.import',
        'payment.view', 'payment.create', 'payment.allocate',
        'bank.view', 'bank.import',
        'collection.view', 'collection.create',
        'report.view',
    ],
    'collector' => [
        'customer.view',
        'invoice.view',
        'collection.view', 'collection.create', 'collection.send',
        'collection.schedule',
    ],
    'manager' => [
        'customer.view',
        'invoice.view',
        'payment.view',
        'bank.view',
        'collection.view',
        'report.view', 'report.export',
    ],
    'viewer' => [
        'customer.view',
        'invoice.view',
        'payment.view',
        'bank.view',
        'report.view',
    ],
    'auditor' => [
        'customer.view',
        'invoice.view',
        'payment.view',
        'bank.view',
        'report.view',
        'system.audit.view',
    ],
];
```

### Middleware

```php
// routes/web.php
Route::middleware(['permission:invoice.create'])->group(function () {
    Route::get('/invoices/create', [InvoiceController::class, 'create']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
});
```

### Blade

```blade
@can('invoice.create')
    <button>Create Invoice</button>
@endcan
```

### Controller

```php
public function store(Request $request)
{
    $this->authorize('invoice.create');
    
    // Create invoice logic
}
```

## Data Permission

### Invoice Access

```text
- Finance AR: All invoices
- Collector: Assigned customers' invoices
- Manager: All invoices
- Viewer: All invoices
- Auditor: All invoices
```

### Payment Access

```text
- Finance AR: All payments
- Manager: All payments
- Viewer: All payments
- Auditor: All payments
```

## Approval Workflow

```text
Finance → Submit → Manager → Approve/Reject
```

### Approval Types

```text
- Invoice exception
- Discount
- Write-off
- Payment difference
- Manual reconciliation
- Cancel payment
```
