# Validation Rules

## Overview
Aturan validasi untuk semua input di AR Finance Tools.

---

## Laravel Form Requests

### StoreExclusionRequest
```php
public function rules()
{
    return [
        'invoice_number' => [
            'required',
            'string',
            'max:50',
            'regex:/^[A-Z0-9\-]+$/i',
        ],
        'reason_code' => [
            'required',
            'in:DUPLICATE,GL_ERROR,TEST_INVOICE,DATA_ENTRY_ERROR,WORKFLOW_ERROR,OTHER',
        ],
        'reason_detail' => [
            'nullable',
            'string',
            'max:500',
        ],
        'exclude_related_payment' => [
            'boolean',
        ],
        'notes' => [
            'nullable',
            'string',
            'max:500',
        ],
    ];
}
```

### StoreCustomerRequest
```php
public function rules()
{
    return [
        'code' => [
            'required',
            'string',
            'max:20',
            'unique:customers,code',
            'regex:/^CUST-[0-9]+$/',
        ],
        'name' => [
            'required',
            'string',
            'max:100',
        ],
        'email' => [
            'nullable',
            'email',
            'max:100',
        ],
        'phone' => [
            'nullable',
            'string',
            'max:20',
        ],
        'credit_limit' => [
            'required',
            'numeric',
            'min:0',
            'max:999999999999.99',
        ],
        'payment_terms' => [
            'required',
            'integer',
            'in:0,7,14,30,45,60,90',
        ],
        'region' => [
            'nullable',
            'string',
            'max:50',
        ],
        'status' => [
            'required',
            'in:ACTIVE,INACTIVE',
        ],
    ];
}
```

### StoreInvoiceRequest
```php
public function rules()
{
    return [
        'invoice_number' => [
            'required',
            'string',
            'max:50',
            'unique:invoices,invoice_number',
            'regex:/^INV-[0-9]{4}-[0-9]+$/',
        ],
        'customer_id' => [
            'required',
            'exists:customers,id',
        ],
        'invoice_date' => [
            'required',
            'date',
            'before_or_equal:today',
        ],
        'due_date' => [
            'required',
            'date',
            'after:invoice_date',
        ],
        'amount' => [
            'required',
            'numeric',
            'min:1',
            'max:999999999999.99',
        ],
        'currency' => [
            'required',
            'in:IDR,USD',
        ],
        'notes' => [
            'nullable',
            'string',
            'max:500',
        ],
    ];
}
```

### StorePaymentRequest
```php
public function rules()
{
    return [
        'invoice_id' => [
            'required',
            'exists:invoices,id',
        ],
        'payment_date' => [
            'required',
            'date',
            'before_or_equal:today',
        ],
        'amount' => [
            'required',
            'numeric',
            'min:1',
        ],
        'payment_method' => [
            'required',
            'in:BCA,BRI,BNI,Mandiri,Cash,Other',
        ],
        'reference_number' => [
            'nullable',
            'string',
            'max:100',
        ],
        'bank_name' => [
            'nullable',
            'string',
            'max:50',
        ],
        'notes' => [
            'nullable',
            'string',
            'max:500',
        ],
    ];
}
```

### UpdateCreditLimitRequest
```php
public function rules()
{
    return [
        'credit_limit' => [
            'required',
            'numeric',
            'min:0',
            'max:999999999999.99',
        ],
        'effective_date' => [
            'required',
            'date',
            'after_or_equal:today',
        ],
        'notes' => [
            'nullable',
            'string',
            'max:500',
        ],
    ];
}
```

---

## Custom Validation Rules

### Invoice Number Format
```php
// app/Rules/InvoiceNumberFormat.php
public function passes($attribute, $value)
{
    return preg_match('/^INV-[0-9]{4}-[0-9]+$/', $value);
}

public function message()
{
    return 'Format invoice harus INV-YYYY-XXX';
}
```

### Valid Reason Code
```php
public function passes($attribute, $value)
{
    return in_array($value, [
        'DUPLICATE', 'GL_ERROR', 'TEST_INVOICE',
        'DATA_ENTRY_ERROR', 'WORKFLOW_ERROR', 'OTHER'
    ]);
}
```

### Not Excluded Invoice
```php
public function passes($attribute, $value)
{
    return !InvoiceExclusion::where('invoice_number', $value)
        ->where('status', 'ACTIVE')
        ->exists();
}

public function message()
{
    return 'Invoice sudah di-exclude';
}
```

---

## Frontend Validation (Alpine.js)

### Real-time Validation
```html
<div x-data="{ form: {}, errors: {} }">
    <input x-model="form.invoice_number"
           @blur="validateField('invoice_number')"
           :class="errors.invoice_number ? 'border-red-500' : ''">
    <span x-show="errors.invoice_number" x-text="errors.invoice_number"
          class="text-red-500 text-sm"></span>
</div>
```

### Validation Messages (ID)
```javascript
const messages = {
    required: 'Field ini wajib diisi',
    email: 'Format email tidak valid',
    min: 'Minimal {min} karakter',
    max: 'Maksimal {max} karakter',
    numeric: 'Harus berupa angka',
    unique: 'Data sudah ada',
    exists: 'Data tidak ditemukan',
    date: 'Format tanggal tidak valid',
    regex: 'Format tidak sesuai',
};
```

---

## Error Response Format

```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "invoice_number": ["Format invoice harus INV-YYYY-XXX"],
        "reason_code": ["Reason code tidak valid"],
        "amount": ["Amount harus lebih dari 0"]
    }
}
```
