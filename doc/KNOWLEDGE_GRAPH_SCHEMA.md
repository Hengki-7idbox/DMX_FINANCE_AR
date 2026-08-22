# Knowledge Graph Schema

## Overview
Schema knowledge graph untuk AR Finance Tools menggunakan Graphify.

---

## Node Types

### Core Nodes

```yaml
Customer:
  properties:
    - id: INTEGER
    - code: STRING
    - name: STRING
    - credit_limit: FLOAT
    - risk_profile: ENUM
    - status: ENUM
  relationships:
    - HAS_INVOICE: Invoice
    - HAS_CREDIT_LIMIT: CreditLimit
    - ASSIGNED_TO: User

Invoice:
  properties:
    - id: INTEGER
    - invoice_number: STRING
    - amount: FLOAT
    - due_date: DATE
    - status: ENUM
    - is_excluded: BOOLEAN
  relationships:
    - BELONGS_TO: Customer
    - HAS_PAYMENT: Payment
    - HAS_EXCLUSION: Exclusion
    - HAS_ACTION: CollectionAction

Payment:
  properties:
    - id: INTEGER
    - amount: FLOAT
    - payment_date: DATE
    - reference_number: STRING
  relationships:
    - PAYS: Invoice

Exclusion:
  properties:
    - id: INTEGER
    - reason_code: ENUM
    - status: ENUM
    - excluded_date: DATETIME
  relationships:
    - EXCLUDES: Invoice
    - PERFORMED_BY: User

CollectionAction:
  properties:
    - id: INTEGER
    - action_type: ENUM
    - action_date: DATETIME
    - description: STRING
  relationships:
    - TARGETS: Invoice
    - PERFORMED_BY: User

CreditLimit:
  properties:
    - id: INTEGER
    - limit_amount: FLOAT
    - effective_date: DATE
  relationships:
    - BELONGS_TO: Customer
    - APPROVED_BY: User

User:
  properties:
    - id: INTEGER
    - name: STRING
    - role: ENUM
    - email: STRING
  relationships:
    - MANAGES: Customer
    - PERFORMS: CollectionAction
    - EXCLUDES: Exclusion
```

---

## Relationship Types

### Direct Relationships
| Relationship | Source | Target | Cardinality |
|--------------|--------|--------|-------------|
| `HAS_INVOICE` | Customer | Invoice | 1:N |
| `PAYS` | Payment | Invoice | N:1 |
| `EXCLUDES` | Exclusion | Invoice | 1:1 |
| `HAS_ACTION` | Invoice | CollectionAction | 1:N |
| `HAS_CREDIT_LIMIT` | Customer | CreditLimit | 1:N |
| `ASSIGNED_TO` | Customer | User | N:1 |
| `PERFORMED_BY` | Action/Exclusion | User | N:1 |
| `APPROVED_BY` | CreditLimit | User | N:1 |

### Computed Relationships
| Relationship | Logic |
|--------------|-------|
| `OVERDUE` | Invoice where due_date < today AND status != SETTLED |
| `AT_RISK` | Customer where credit_utilization > 75% |
| `NEEDS_REMINDER` | Invoice where days_overdue > 0 AND last_reminder > 7 days |
| `HIGH_PRIORITY` | Invoice where amount > 400M AND overdue > 30 days |

---

## Graph Queries

### Customer AR Overview
```cypher
MATCH (c:Customer)-[:HAS_INVOICE]->(i:Invoice)
WHERE i.is_excluded = false
RETURN c.name, 
       COUNT(i) as total_invoices,
       SUM(i.amount) as total_ar,
       COUNT(CASE WHEN i.status = 'OVERDUE' THEN 1 END) as overdue_count
```

### Exclusion Impact
```cypher
MATCH (e:Exclusion {status: 'ACTIVE'})-[:EXCLUDES]->(i:Invoice)
MATCH (i)<-[:HAS_INVOICE]-(c:Customer)
RETURN c.name, i.invoice_number, i.amount, e.reason_code
ORDER BY i.amount DESC
```

### Collection Priority
```cypher
MATCH (i:Invoice)-[:BELONGS_TO]->(c:Customer)
WHERE i.status = 'OVERDUE'
WITH i, c,
     (i.amount / c.credit_limit * 100) as util_pct,
     duration.between(i.due_date, date()).days as days_overdue
RETURN i.invoice_number, c.name, i.amount, days_overdue, util_pct
ORDER BY days_overdue DESC, i.amount DESC
LIMIT 10
```

---

## Sync Protocol

### Laravel → Graphify
```php
// Event-based sync
Invoice::created(function ($invoice) {
    Graphify::node('Invoice', $invoice->id, [
        'invoice_number' => $invoice->invoice_number,
        'amount' => $invoice->amount,
        'status' => $invoice->status,
    ]);
});
```

### Frequency
| Event | Sync Time |
|-------|-----------|
| Create | Real-time |
| Update | Real-time |
| Delete | Real-time |
| Batch import | After completion |
| Full refresh | Weekly |
