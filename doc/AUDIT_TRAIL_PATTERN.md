# Audit Trail Pattern

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Pola implementasi audit trail untuk seluruh aktivitas penting dalam sistem Finance AR.

## Data Structure

```text
audit_logs
├── id
├── user_id
├── action (CREATE/UPDATE/DELETE)
├── module
├── record_id
├── old_value (JSON)
├── new_value (JSON)
├── ip_address
├── user_agent
├── created_at
```

## Tracked Modules

```text
- Invoice
- Payment
- Payment Allocation
- Bank Transaction
- Reconciliation
- Customer
- Collection Activity
- Promise to Pay
- Exception Invoice
- User/Role changes
```

## Implementation Pattern

### Laravel Observer

```php
// app/Observers/InvoiceObserver.php
class InvoiceObserver
{
    public function updated(Invoice $invoice)
    {
        $changes = $invoice->getChanges();
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'UPDATE',
            'module' => 'Invoice',
            'record_id' => $invoice->id,
            'old_value' => $invoice->getOriginal(),
            'new_value' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

## Rules

```text
1. Audit log tidak boleh dihapus oleh user biasa
2. Only Super Admin dapat menghapus audit log
3. Audit log harus immutable
4. Setiap perubahan financial WAJIB tercatat
5. Format timestamp: YYYY-MM-DD HH:mm:ss
```

## Example

```text
User: Budi (Finance AR)
Action: UPDATE
Module: Invoice
Record: INV-001

old_value:
  grand_total: 10,000,000
  status: UNPAID

new_value:
  grand_total: 9,500,000
  status: PARTIAL

ip: 192.168.1.100
timestamp: 2026-09-22 10:32:15
```
