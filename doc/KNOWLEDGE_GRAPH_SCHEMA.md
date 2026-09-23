# Knowledge Graph Schema

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Schema knowledge graph untuk Finance AR Management System menggunakan Graphify.

## Node Types

### Core Entities

```text
Customer
  - id: integer
  - customer_code: string
  - customer_name: string
  - customer_type: string
  - status: string

Invoice
  - id: integer
  - invoice_number: string
  - invoice_date: date
  - due_date: date
  - grand_total: decimal
  - outstanding_amount: decimal
  - status: string
  - source_type: string

Payment
  - id: integer
  - payment_number: string
  - payment_date: date
  - amount: decimal
  - payment_method: string
  - status: string

BankAccount
  - id: integer
  - bank_name: string
  - account_number: string
  - account_name: string

BankTransaction
  - id: integer
  - transaction_date: date
  - description: string
  - reference_number: string
  - debit: decimal
  - credit: decimal
  - balance: decimal

Reconciliation
  - id: integer
  - reconciliation_date: date
  - status: string

CollectionActivity
  - id: integer
  - channel: string
  - message: text
  - sent_at: datetime
  - status: string

PromiseToPay
  - id: integer
  - promise_date: date
  - promise_amount: decimal
  - status: string
```

## Edge Types

### Relationships

```text
CustomerGroup -[:HAS_MEMBER]-> Customer
Customer -[:HAS_CONTACT]-> CustomerContact
Customer -[:HAS_PAYMENT_TERM]-> PaymentTerm
Customer -[:HAS_BANK_ACCOUNT]-> BankAccount

Customer -[:OWES]-> Invoice
Invoice -[:HAS_ITEM]-> InvoiceItem
Invoice -[:HAS_EXCEPTION]-> InvoiceException

Invoice -[:PAID_BY]-> PaymentAllocation
Payment -[:ALLOCATED_TO]-> PaymentAllocation

BankAccount -[:HAS_TRANSACTION]-> BankTransaction
BankTransaction -[:MATCHED_WITH]-> Invoice
BankTransaction -[:LINKED_TO]-> Payment

Invoice -[:RECONCILED_WITH]-> Reconciliation
Payment -[:RECONCILED_WITH]-> Reconciliation
BankTransaction -[:RECONCILED_WITH]-> Reconciliation

Invoice -[:IN_COLLECTION]-> CollectionActivity
CollectionActivity -[:RESULTS_IN]-> PromiseToPay
PromiseToPay -[:FULFILLED_BY]-> Payment

Customer -[:HAS_ASSESSMENT]-> CustomerAssessment
Customer -[:HAS_DISPUTE]-> Dispute
```

## Community Detection

### Use Cases

```text
1. Customer clusters by payment behavior
2. Invoice groups by aging
3. Bank transaction patterns
4. Collection activity patterns
```

### Algorithms

```text
- Louvain: Community detection
- PageRank: Importance ranking
- Shortest Path: Connection analysis
```

## Query Examples

### Find all invoices for a customer

```cypher
MATCH (c:Customer {id: $customerId})-[:OWES]-> (i:Invoice)
RETURN i
```

### Find overdue invoices

```cypher
MATCH (i:Invoice {status: 'OVERDUE'})
RETURN i ORDER BY i.due_date
```

### Find unmatched bank transactions

```cypher
MATCH (bt:BankTransaction)
WHERE NOT (bt)-[:MATCHED_WITH]-> ()
RETURN bt
```

### Customer payment history

```cypher
MATCH (c:Customer {id: $customerId})-[:OWES]-> (i:Invoice)-[:PAID_BY]-> (pa:PaymentAllocation)<-[:ALLOCATED_TO]-(p:Payment)
RETURN i, p
ORDER BY p.payment_date DESC
```

## Graph Properties

```text
- Directed: Yes
- Weighted: Yes (amount)
- Temporal: Yes (dates)
- Multigraph: Yes (multiple edge types)
```
