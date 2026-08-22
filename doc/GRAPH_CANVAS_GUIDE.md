# Graph Canvas Guide

## Overview
Panduan untuk visualisasi knowledge graph menggunakan Graphify.

---

## What is Graphify?

Graphify adalah tool untuk membuat knowledge graph dari data AR Finance Tools, membantu memahami relasi antar entitas dan alur data.

---

## Entity Types

### Core Entities

| Entity | Color | Description |
|--------|-------|-------------|
| Customer | 🔵 Blue | Data customer |
| Invoice | 🟢 Green | Invoice AR |
| Payment | 🟡 Yellow | Pembayaran |
| Exclusion | 🔴 Red | Invoice di-exclude |
| User | 🟣 Purple | Sistem user |
| Action | 🟠 Orange | Collection action |

### Relationship Types

| Relationship | From → To | Description |
|--------------|-----------|-------------|
| `HAS_INVOICE` | Customer → Invoice | Customer punya invoice |
| `PAYS` | Payment → Invoice | Payment untuk invoice |
| `EXCLUDES` | Exclusion → Invoice | Invoice di-exclude |
| `OWNS` | User → Action | User melakukan action |
| `REMINDS` | Action → Invoice | Reminder untuk invoice |
| `LIMITS` | Customer → CreditLimit | Credit limit customer |

---

## Canvas Layout

### Main View
```
                    ┌─────────────┐
                    │  Dashboard  │
                    └──────┬──────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
   ┌────▼────┐       ┌────▼────┐       ┌────▼────┐
   │ Exclusion│       │  Aging  │       │Recon    │
   │  Master  │       │ Report  │       │  Tool   │
   └────┬────┘       └────┬────┘       └────┬────┘
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                    ┌──────▼──────┐
                    │   Invoice   │
                    │  (Central)  │
                    └──────┬──────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
   ┌────▼────┐       ┌────▼────┐       ┌────▼────┐
   │Customer │       │ Payment │       │Collection│
   │         │       │         │       │  Tracker │
   └─────────┘       └─────────┘       └─────────┘
```

### Filter Options
- By entity type
- By date range
- By status
- By customer

### Interaction
- **Click node**: View detail
- **Hover**: Show tooltip
- **Double-click**: Open full detail
- **Drag**: Reposition node
- **Zoom**: Scroll wheel

---

## Query Patterns

### Customer Invoice Graph
```cypher
MATCH (c:Customer)-[:HAS_INVOICE]->(i:Invoice)
WHERE c.id = {customer_id}
RETURN c, i
```

### Exclusion Impact Graph
```cypher
MATCH (e:Exclusion)-[:EXCLUDES]->(i:Invoice)<-[:HAS_INVOICE]-(c:Customer)
WHERE e.status = 'ACTIVE'
RETURN e, i, c
```

### Payment Flow Graph
```cypher
MATCH (p:Payment)-[:PAYS]->(i:Invoice)<-[:HAS_INVOICE]-(c:Customer)
WHERE i.due_date >= '2026-01-01'
RETURN p, i, c
```

---

## Export Formats

| Format | Usage |
|--------|-------|
| PNG | Screenshot, documentation |
| SVG | Scalable graphics |
| JSON | Data interchange |
| Markdown | Documentation |

---

## Integration with Obsidian

Knowledge graph dari Graphify bisa di-embed di Obsidian:
```markdown
```graphviz
[[graphify:customer-ar-flow]]
```
```
