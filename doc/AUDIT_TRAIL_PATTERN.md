# Audit Trail Pattern

## Overview
Semua operasi penting harus dicatat dalam audit log untuk compliance dan troubleshooting.

## Database Table

```sql
CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT UNSIGNED NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id),
    INDEX idx_table_record (table_name, record_id)
) ENGINE=InnoDB;
```

## Actions Tracked

| Category | Actions |
|----------|---------|
| **Invoice Exclusion** | ADD, REVERT, BULK_IMPORT |
| **Aging Report** | VIEW, EXPORT, CUSTOM_BUCKET_SAVE |
| **Reconciliation** | IMPORT_BANK, MATCH_RUN, MANUAL_MATCH, EXPORT |
| **Reminders** | SEND_BATCH, TEMPLATE_CREATE, CONFIG_UPDATE |
| **Credit Limit** | UPDATE, APPROVE_SO, BLOCK_SO, BULK_UPDATE |
| **Customer** | VIEW, UPDATE, ANALYZE |
| **Tracker** | STATUS_CHANGE, ACTION_ADD, ESCALATE, REASSIGN |
| **Auth** | LOGIN, LOGOUT, PASSWORD_CHANGE |
| **Admin** | USER_CREATE, USER_UPDATE, ROLE_CHANGE |

## Implementation

### Laravel Trait
```php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::log('CREATE', $model, null, $model->toArray());
        });

        static::updated(function ($model) {
            self::log('UPDATE', $model, $model->getOriginal(), $model->toArray());
        });

        static::deleted(function ($model) {
            self::log('DELETE', $model, $model->toArray(), null);
        });
    }

    protected static function log($action, $model, $old, $new)
    {
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'table_name' => $model->getTable(),
            'record_id'  => $model->getKey(),
            'old_value'  => $old,
            'new_value'  => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Usage in Model
```php
class InvoiceExclusion extends Model
{
    use Auditable;

    // ... model code
}
```

### Manual Logging
```php
use App\Traits\Auditable;

AuditLog::create([
    'user_id'    => Auth::id(),
    'action'     => 'EXPORT_REPORT',
    'table_name' => 'aging_report',
    'record_id'  => null,
    'old_value'  => null,
    'new_value'  => ['format' => 'excel', 'filters' => $filters],
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

## Viewing Audit Logs

### Controller
```php
public function index(Request $request)
{
    $query = AuditLog::with('user');

    if ($request->table_name) {
        $query->where('table_name', $request->table_name);
    }

    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }

    if ($request->from && $request->to) {
        $query->whereBetween('created_at', [$request->from, $request->to]);
    }

    return $query->orderBy('created_at', 'desc')
                 ->paginate(25);
}
```

## Retention Policy
- Audit logs disimpan minimal **3 tahun**
- Monthly cleanup script untuk logs > 3 tahun
- Export ke archive sebelum delete
