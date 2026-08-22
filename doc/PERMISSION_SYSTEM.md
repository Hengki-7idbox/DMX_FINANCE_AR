# Permission System

## Overview
Role-Based Access Control (RBAC) untuk AR Finance Tools menggunakan Laravel Sanctum.

---

## Roles

| Role | Level | Description |
|------|-------|-------------|
| `admin` | 100 | Full system access |
| `finance_manager` | 80 | Manage AR operations |
| `ar_accountant` | 60 | Daily AR tasks |
| `ar_collector` | 40 | Collection follow-up |
| `sales_manager` | 50 | Monitor customer credit |
| `viewer` | 20 | Read-only access |

---

## Permission Matrix

### Tool 1: Invoice Exclusion
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| View exclusion list | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Add exclusion | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Bulk import | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Revert exclusion | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View statistics | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |

### Tool 2: Aging Report
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| View report | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Export report | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Custom buckets | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Schedule report | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

### Tool 3: AR Reconciliation
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| View recon | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| Import bank statement | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Run matching | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Manual match | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Export recon | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |

### Tool 4: Email & WA Reminders
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| Send reminders | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| View send log | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Configure rules | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage templates | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

### Tool 5: Credit Limit
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| View credit status | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Update credit limit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Approve SO | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| View forecast | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |

### Tool 6: Dashboard
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| View dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Export dashboard | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Customize layout | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

### Tool 7: Customer Analyzer
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| Analyze customer | ✅ | ✅ | ✅ | ✅* | ✅ | ✅ |
| View cohort | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| Export analysis | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |

*Limited to assigned customers

### Tool 8: Collection Tracker
| Action | Admin | Finance Mgr | AR Accountant | AR Collector | Sales Mgr | Viewer |
|--------|-------|-------------|---------------|--------------|-----------|--------|
| View tracker | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Add action | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Change status | ✅ | ✅ | ✅ | ✅* | ❌ | ❌ |
| Escalate | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reassign | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Implementation

### Middleware
```php
// app/Http/Middleware/CheckRole.php
public function handle($request, Closure $next, ...$roles)
{
    if (!in_array($request->user()->role, $roles)) {
        abort(403, 'Anda tidak memiliki akses');
    }
    return $next($request);
}
```

### Route Protection
```php
// routes/web.php
Route::middleware(['auth', 'role:admin,finance_manager'])->group(function () {
    Route::resource('exclusions', ExclusionController::class);
});

Route::middleware(['auth', 'role:admin,finance_manager,ar_accountant'])->group(function () {
    Route::get('/reconciliation', [ReconController::class, 'index']);
});
```

### Blade Check
```blade
@if(in_array(auth()->user()->role, ['admin', 'finance_manager']))
    <button>Add Exclusion</button>
@endif
```

### API Check
```php
public function store(StoreExclusionRequest $request)
{
    if (!in_array(auth()->user()->role, ['admin', 'finance_manager'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    // ...
}
```

---

## User Assignment Rules

### AR Collector Scope
- Can only see assigned customers
- Can only add actions to assigned invoices
- Cannot view unassigned customers

### Sales Manager Scope
- Can view all customers
- Cannot modify AR data
- Can view credit status for their region
