# API Documentation

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

REST API untuk integrasi sistem Finance AR dengan external systems.

## Base URL

```text
https://finance-ar.example.com/api/v1
```

## Authentication

```text
POST /auth/login
POST /auth/logout
POST /auth/refresh
```

## Endpoints

### Customer

```text
GET    /customers
GET    /customers/{id}
POST   /customers
PUT    /customers/{id}
DELETE /customers/{id}
GET    /customers/{id}/invoices
GET    /customers/{id}/payments
GET    /customers/{id}/aging
```

### Invoice

```text
GET    /invoices
GET    /invoices/{id}
POST   /invoices
PUT    /invoices/{id}
DELETE /invoices/{id}
POST   /invoices/import
GET    /invoices/aging
GET    /invoices/aging/snapshot
```

### Payment

```text
GET    /payments
GET    /payments/{id}
POST   /payments/internal
POST   /payments/external
POST   /payments/clone
POST   /payments/{id}/allocate
GET    /payments/{id}/allocations
```

### Bank

```text
GET    /bank-accounts
GET    /bank-accounts/{id}
POST   /bank-accounts
GET    /bank-transactions
POST   /bank-transactions/import
POST   /bank-transactions/{id}/match
```

### Reconciliation

```text
GET    /reconciliations
POST   /reconciliations/match
POST   /reconciliations/confirm
GET    /reconciliations/center
```

### Collection

```text
GET    /collection/queue
POST   /collection/follow-up
POST   /collection/whatsapp
POST   /collection/email
GET    /collection/history
```

### Report

```text
GET    /reports/aging
GET    /reports/outstanding
GET    /reports/payment
GET    /reports/reconciliation
GET    /reports/collection
```

## Response Format

```json
{
  "success": true,
  "data": {},
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 25,
    "total": 250
  }
}
```

## Error Codes

```text
400 - Bad Request
401 - Unauthorized
403 - Forbidden
404 - Not Found
422 - Validation Error
500 - Server Error
```

## Rate Limit

```text
Default: 60 requests/minute
Authenticated: 120 requests/minute
```
