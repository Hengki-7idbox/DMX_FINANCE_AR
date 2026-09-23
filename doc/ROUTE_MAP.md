# Route Map

**Version:** 1.0  
**Date:** 22 September 2026

---

## Web Routes

### Authentication

```text
GET  /login
POST /login
POST /logout
GET  /register
```

### Dashboard

```text
GET  /
GET  /dashboard
```

### Master Data

```text
GET  /customers
GET  /customers/create
POST /customers
GET  /customers/{id}
GET  /customers/{id}/edit
PUT  /customers/{id}
DELETE /customers/{id}

GET  /customer-groups
POST /customer-groups
PUT  /customer-groups/{id}
DELETE /customer-groups/{id}

GET  /payment-terms
POST /payment-terms
PUT  /payment-terms/{id}
DELETE /payment-terms/{id}

GET  /bank-accounts
POST /bank-accounts
PUT  /bank-accounts/{id}
DELETE /bank-accounts/{id}
```

### Invoice

```text
GET  /invoices
GET  /invoices/create
POST /invoices
GET  /invoices/{id}
GET  /invoices/{id}/edit
PUT  /invoices/{id}
DELETE /invoices/{id}
POST /invoices/import
GET  /invoices/aging
GET  /invoices/aging/snapshot
```

### Payment

```text
GET  /payments
GET  /payments/internal/create
POST /payments/internal
GET  /payments/external/create
POST /payments/external
POST /payments/clone/{id}
GET  /payments/{id}/allocate
POST /payments/{id}/allocate
```

### Bank

```text
GET  /bank-transactions
POST /bank-transactions/import
GET  /bank-transactions/{id}/match
POST /bank-transactions/{id}/match
```

### Reconciliation

```text
GET  /reconciliations
GET  /reconciliations/center
POST /reconciliations/match
POST /reconciliations/confirm
```

### Collection

```text
GET  /collection/dashboard
GET  /collection/queue
POST /collection/follow-up
POST /collection/whatsapp
POST /collection/email
GET  /collection/schedule
POST /collection/schedule
GET  /collection/promise
POST /collection/promise
```

### Reports

```text
GET  /reports/aging
GET  /reports/outstanding
GET  /reports/payment
GET  /reports/bank
GET  /reports/reconciliation
GET  /reports/collection
GET  /reports/customer
```

### System

```text
GET  /settings/users
POST /settings/users
PUT  /settings/users/{id}
DELETE /settings/users/{id}

GET  /settings/roles
POST /settings/roles
PUT  /settings/roles/{id}

GET  /settings/import-history
GET  /settings/audit-log
```

## API Routes

```text
/api/v1/customers
/api/v1/invoices
/api/v1/payments
/api/v1/bank-accounts
/api/v1/bank-transactions
/api/v1/reconciliations
/api/v1/collection
/api/v1/reports
/api/v1/search
```

## Named Routes

```text
dashboard.index
customers.index
customers.create
customers.store
customers.show
customers.edit
customers.update
customers.destroy

invoices.index
invoices.create
invoices.store
invoices.show
invoices.aging

payments.internal.create
payments.internal.store
payments.external.create
payments.external.store

bank-transactions.import
reconciliations.center
collection.dashboard
reports.aging
```
