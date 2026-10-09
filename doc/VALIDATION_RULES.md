# Validation Rules

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Aturan validasi untuk seluruh input data dalam Finance AR.

## Customer Validation

```text
source_type: required|in:internal,external
customer_code: required|string|max:20|unique:customers,customer_code + source_type (komposit, kecuali id)
customer_name: required|string|max:255
customer_group_id: required|exists:customer_groups,id
customer_type: required|in:DISTRIBUTOR,CORPORATE,RETAIL,GOVERNMENT,RESELLER,INTERNAL
tax_number: nullable|string|max:30
address: nullable|string|max:500
city: nullable|string|max:100
phone: nullable|string|max:255
email: nullable|email|max:255
payment_term_id: required|exists:payment_terms,id
credit_limit: required|numeric|min:0
status: required|in:ACTIVE,INACTIVE
notes: nullable|string|max:500
```

## Invoice Validation

```text
invoice_number: required|string|max:30|unique:invoices,invoice_number,except:id
customer_id: required|exists:customers,id
invoice_date: required|date|before_or_equal:today
due_date: required|date|after_or_equal:invoice_date
subtotal: required|numeric|min:0
discount: nullable|numeric|min:0
tax: nullable|numeric|min:0
grand_total: required|numeric|min:0
status: required|in:DRAFT,POSTED,UNPAID,PARTIAL,OVERDUE,PAID,CANCELLED,EXCEPTION
source_type: required|in:internal,external
```

## Payment Validation

```text
customer_id: required|exists:customers,id
payment_date: required|date|before_or_equal:today
amount: required|numeric|min:0.01
payment_method: required|in:BANK_TRANSFER,CASH,CHEQUE,GIRO
bank_account_id: required|exists:bank_accounts,id
reference_number: nullable|string|max:50
notes: nullable|string|max:500
```

## Payment Allocation Validation

```text
invoice_id: required|exists:invoices,id
amount: required|numeric|min:0.01
```

## Bank Account Validation

```text
bank_name: required|string|max:100
account_number: required|string|max:30|unique:bank_accounts,account_number,except:id
account_name: required|string|max:100
currency: required|in:IDR,USD,EUR
status: required|in:ACTIVE,INACTIVE
```

## Bank Transaction Validation

```text
bank_account_id: required|exists:bank_accounts,id
transaction_date: required|date
value_date: nullable|date
description: required|string|max:255
reference_number: required|string|max:50
debit: nullable|numeric|min:0
credit: nullable|numeric|min:0
```

## Collection Activity Validation

```text
customer_id: required|exists:customers,id
invoice_id: required|exists:invoices,id
channel: required|in:WHATSAPP,EMAIL,PHONE,MEETING,OTHER
message: required|string|max:1000
next_follow_up: nullable|date|after_or_equal:today
```

## Promise to Pay Validation

```text
customer_id: required|exists:customers,id
invoice_id: required|exists:invoices,id
promise_date: required|date|after_or_equal:today
promise_amount: required|numeric|min:0.01
notes: nullable|string|max:500
```

## Reconciliation Validation

```text
invoice_id: required|exists:invoices,id
payment_id: required|exists:payments,id
bank_transaction_id: required|exists:bank_transactions,id
difference: required|numeric
difference_type: required|in:BANK_FEE,DISCOUNT,ROUNDING,TAX,RETURN,OTHER
```

## User Validation

```text
name: required|string|max:255
email: required|email|max:255|unique:users,email,except:id
password: required|string|min:8|confirmed
role: required|exists:roles,name
```

## Import Validation

```text
file: required|file|max:10240|mimes:xlsx,xls,csv
source_type (customer import): required|in:internal,external

Customer import:
- Format Accurate: auto-detect header "Customer No." (header bisa di baris berikutnya, data mulai baris setelahnya)
- Format template: auto-detect header "customer_code"
- Upsert by (customer_code, source_type): kode sama dalam source yang sama → update; soft-delete di-restore
- Group default DEFAULT / term default COD dibuat otomatis bila tidak ada
- Mapping type Accurate (contoh): FRUIT/VEGETABLE/FROZEN → DISTRIBUTOR; TOKO/SUPERMARKET/ONLINE → RETAIL
- NON AKTIF → status INACTIVE; tipe tak dikenal → CORPORATE
```

## Custom Rules

### Invoice Date

```text
- Cannot be future date
- Cannot be more than 1 year ago
```

### Due Date

```text
- Must be after invoice date
- Cannot be more than 365 days after invoice date
```

### Payment Amount

```text
- Cannot exceed invoice outstanding
- Must be > 0
```

### Allocation Amount

```text
- Cannot exceed payment remaining
- Cannot exceed invoice outstanding
- Total allocation cannot exceed payment amount
```
