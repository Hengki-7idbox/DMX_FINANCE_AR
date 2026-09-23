# Database Documentation

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Sistem Finance AR menggunakan MySQL database dengan dual source: Internal dan External.

## Database Schema

### Core Tables

```text
customer_groups
customers
customer_contacts
payment_terms
bank_accounts

invoices
invoice_items
invoice_exceptions

payments
payment_allocations

bank_transactions
reconciliations

promise_to_pay
collection_activities
disputes

customer_assessments
aging_snapshots

import_batches
audit_logs

users
roles
permissions
```

## Table Relationships

```text
customer_groups ──< customers
customers ──< invoices
customers ──< payments
customers ──< collection_activities

invoices ──< payment_allocations
payments ──< payment_allocations

bank_accounts ──< bank_transactions

invoices ──< reconciliations
payments ──< reconciliations
bank_transactions ──< reconciliations
```

## Naming Convention

```text
- Table: plural, snake_case (e.g., invoice_items)
- Column: snake_case (e.g., customer_id)
- Foreign key: {table}_id (e.g., customer_id)
- Primary key: id
- Timestamps: created_at, updated_at
```

## Index Strategy

```text
- Primary keys: auto-indexed
- Foreign keys: indexed
- Search fields: indexed
- Date fields: indexed for range queries
- Composite indexes for common queries
```

## Soft Deletes

```text
Tables with soft deletes:
- customers
- invoices
- payments
- bank_transactions
```

## Data Types

```text
- DECIMAL(15,2) for monetary values
- DATE for dates
- DATETIME for timestamps
- VARCHAR for short strings
- TEXT for long strings
- ENUM for fixed values
- JSON for flexible data
```

## Backup Schedule

```text
- Daily: 02:00 AM
- Weekly: Sunday 03:00 AM
- Monthly: 1st of month 04:00 AM
```
