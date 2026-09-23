# Error Handling Strategy

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Strategi penanganan error untuk seluruh aplikasi Finance AR.

## Error Types

### 1. Validation Error

```php
// Response
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "invoice_number": ["The invoice number is required"],
            "amount": ["The amount must be greater than 0"]
        }
    }
}
```

### 2. Not Found Error

```php
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Invoice not found"
    }
}
```

### 3. Business Logic Error

```php
{
    "success": false,
    "error": {
        "code": "BUSINESS_RULE_VIOLATION",
        "message": "Cannot allocate more than payment amount"
    }
}
```

### 4. Database Error

```php
{
    "success": false,
    "error": {
        "code": "DATABASE_ERROR",
        "message": "Transaction failed"
    }
}
```

## Laravel Implementation

### Exception Handler

```php
// app/Exceptions/Handler.php
class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Validation failed',
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });
    }
}
```

### Custom Exception

```php
// app/Exceptions/BusinessRuleException.php
class BusinessRuleException extends Exception
{
    protected $errorCode;
    
    public function __construct(string $message, string $errorCode = 'BUSINESS_RULE_VIOLATION')
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }
}
```

## Transaction Rollback

```php
DB::beginTransaction();

try {
    // Create payment
    $payment = Payment::create($data);
    
    // Create allocation
    PaymentAllocation::create($allocationData);
    
    // Update invoice
    $invoice->update(['status' => 'PAID']);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw new BusinessRuleException('Payment process failed: ' . $e->getMessage());
}
```

## Logging

```text
Log levels:
- emergency: System is unusable
- alert: Action must be taken immediately
- critical: Critical conditions
- error: Error conditions
- warning: Warning conditions
- notice: Normal but significant conditions
- info: Informational messages
- debug: Debug-level messages
```

### Log Location

```text
storage/logs/laravel.log
```

### Log Rotation

```text
Daily rotation
Keep 30 days
```

## User-Friendly Messages

```text
"Terjadi kesalahan. Silakan coba lagi."
"Data tidak ditemukan."
"Anda tidak memiliki akses untuk melakukan operasi ini."
"Data sudah ada. Silakan gunakan data yang berbeda."
```

## Email Notification

```text
- Critical errors: Notify admin
- Failed imports: Notify user
- System failures: Notify admin
```
