# Business Rules

**Version:** 1.0  
**Date:** 22 September 2026

---

## Invoice Rules

### Outstanding Calculation

```text
outstanding = grand_total - total_allocated_payment
```

### Status Rules

```text
IF outstanding <= 0 THEN status = PAID
IF 0 < outstanding < grand_total THEN status = PARTIAL
IF today > due_date AND outstanding > 0 THEN status = OVERDUE
```

### Validation

```text
- invoice_number: required, unique
- customer_id: required, exists
- invoice_date: required, <= today
- due_date: required, >= invoice_date
- grand_total: required, > 0
```

## Payment Rules

### Full Payment

```text
IF payment_amount >= outstanding THEN
  status = PAID
  outstanding = 0
```

### Partial Payment

```text
IF 0 < payment_amount < outstanding THEN
  status = PARTIAL
  outstanding = grand_total - payment_amount
```

### Payment Allocation

```text
- One payment can be allocated to multiple invoices
- Total allocation cannot exceed payment amount
- Allocation order: FIFO (oldest invoice first)
```

### Bank Fee Handling

```text
IF payment < invoice_amount THEN
  difference = invoice_amount - payment
  classify as: BANK_FEE / DISCOUNT / ROUNDING / OTHER
```

## Reconciliation Rules

### Match Criteria

```text
Invoice ←→ Payment ←→ Bank Transaction

Match by:
- Customer name
- Amount
- Reference number
- Date range (±3 days)
```

### Status Rules

```text
UNMATCHED: No match found
PARTIAL_MATCH: Partial match
MATCHED: All criteria match
RECONCILED: Confirmed by user
IGNORED: User decided to ignore
```

### Difference Handling

```text
IF difference > 0 THEN
  Request classification
  Create journal entry
  
Classifications:
- BANK_FEE
- DISCOUNT
- ROUNDING
- TAX
- RETURN
- OTHER
```

## Collection Rules

### Trigger

```text
IF invoice.status = OVERDUE THEN
  Add to collection queue
```

### Promise to Pay

```text
IF promise_date < today AND no payment THEN
  status = BROKEN
  Add back to collection queue
```

## Aging Rules

### Categories

```text
CURRENT:  due_date >= today
1-30:     1 <= days_overdue <= 30
31-60:    31 <= days_overdue <= 60
61-90:    61 <= days_overdue <= 90
91-120:   91 <= days_overdue <= 120
>120:     days_overdue > 120
```

### Calculation

```text
aging_days = report_date - due_date
```

## Import Rules

### Validation

```text
- Required fields: invoice_number, customer_id, amount
- Amount must be numeric > 0
- Date must be valid format
- Customer must exist in system
- Duplicate check: invoice_number
```

### Workflow

```text
Upload → Mapping → Validation → Preview → Import
```
