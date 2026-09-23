# Agent Query Protocol

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Agent Query Protocol mendefinisikan standar komunikasi antara AI agents dengan sistem Finance AR.

## Protocol Types

### 1. Read Query

```text
GET /api/agent/invoice/{id}
GET /api/agent/customer/{id}/outstanding
GET /api/agent/aging/{customer_id}
```

### 2. Write Query

```text
POST /api/agent/payment/create
POST /api/agent/collection/activity
POST /api/agent/reconciliation/match
```

### 3. Search Query

```text
GET /api/agent/search/invoice?q={query}
GET /api/agent/search/customer?q={query}
GET /api/agent/search/bank-transaction?q={query}
```

## Response Format

```json
{
  "status": "success",
  "data": {},
  "metadata": {
    "query_time": "0.023s",
    "cached": false
  }
}
```

## Error Format

```json
{
  "status": "error",
  "error": {
    "code": "NOT_FOUND",
    "message": "Invoice not found"
  }
}
```

## Authentication

Semua query harus menggunakan:

```text
Authorization: Bearer {api_token}
X-Agent-ID: {agent_identifier}
```

## Rate Limiting

```text
Max requests per minute: 60
Burst limit: 10
```

## Caching Strategy

```text
Invoice data: 5 minutes
Customer data: 10 minutes
Aging data: 15 minutes
```
