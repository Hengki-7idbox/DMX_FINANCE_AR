# API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
Semua endpoint memerlukan **Laravel Sanctum** token.

### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "secret"
}
```

### Response
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "Admin",
            "email": "user@example.com",
            "role": "admin"
        }
    }
}
```

### Usage
```http
Authorization: Bearer 1|abc123...
```

---

## Endpoints

### TOOL 1: Invoice Exclusion

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/exclusions` | Add exclusion |
| `GET` | `/exclusions` | List with filters |
| `GET` | `/exclusions/{id}` | Get exclusion detail |
| `PUT` | `/exclusions/{id}` | Update exclusion |
| `PUT` | `/exclusions/{id}/revert` | Revert exclusion |
| `POST` | `/exclusions/bulk-import` | Bulk import CSV |
| `GET` | `/exclusions/statistics` | Get stats |

### TOOL 2: Aging Report

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/aging-report` | Generate report |
| `GET` | `/aging-report/buckets` | Get bucket config |
| `POST` | `/aging-report/custom-buckets` | Save custom buckets |
| `GET` | `/aging-report/export` | Export (format: excel\|pdf\|csv) |
| `GET` | `/aging-report/trend` | Month-over-month trend |

### TOOL 3: AR Reconciliation

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/reconciliation/import-bank-statement` | Upload bank data |
| `GET` | `/reconciliation/gl-balance` | Fetch GL balance |
| `POST` | `/reconciliation/match` | Run auto-matching |
| `GET` | `/reconciliation/unmatched-items` | List unmatched |
| `POST` | `/reconciliation/manual-match` | Manual match |
| `GET` | `/reconciliation/report` | Generate report |
| `GET` | `/reconciliation/export` | Export report |

### TOOL 4: Email & WA Reminders

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/reminders/send-batch` | Send batch reminders |
| `GET` | `/reminders/scheduled-jobs` | View job status |
| `PUT` | `/reminders/config` | Update config |
| `GET` | `/reminders/send-log` | View history |
| `GET` | `/reminders/templates` | List templates |

### TOOL 5: Credit Limit Monitor

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/credit-limit/monitor` | Get all utilization |
| `GET` | `/credit-limit/{customer_id}` | Customer detail |
| `PUT` | `/credit-limit/{customer_id}` | Update limit |
| `POST` | `/credit-limit/check-so-approval` | Check SO approval |
| `GET` | `/credit-limit/forecast/{customer_id}` | Forecast |

### TOOL 6: Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/dashboard/kpis` | Get KPI data |
| `GET` | `/dashboard/charts` | Get chart data |
| `GET` | `/dashboard/alerts` | Get alerts |
| `GET` | `/dashboard/activity-feed` | Recent activities |

### TOOL 7: Customer Analyzer

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/analyzer/customer/{id}` | Customer analysis |
| `GET` | `/analyzer/customer/{id}/trend` | Payment trend |
| `GET` | `/analyzer/customer/{id}/forecast` | Forecast DSO |
| `GET` | `/analyzer/all-customers/cohort-analysis` | Segment all |

### TOOL 8: Collection Tracker

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/tracker/invoices` | List with status |
| `GET` | `/tracker/invoice/{id}` | Detail + history |
| `POST` | `/tracker/action` | Add manual action |
| `PUT` | `/tracker/status/{id}` | Update status |

---

## Response Format

### Success
```json
{
    "success": true,
    "data": {},
    "message": "Success"
}
```

### Error
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {}
}
```

## Pagination
```json
{
    "data": [...],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
    }
}
```
