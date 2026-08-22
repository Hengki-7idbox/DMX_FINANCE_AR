# Business Rules

## Overview
Aturan bisnis yang berlaku untuk seluruh sistem AR Finance Tools.

---

## RULE 1: Invoice Exclusion

### Aturan
1. Invoice yang di-exclude **TIDAK BOLEH** masuk dalam kalkulasi apapun
2. Exclusion bersifat **reversible**, bukan hard delete
3. Setiap exclusion harus punya **reason code** yang valid
4. Revert exclusion memerlukan **alasan** dan dicatat di audit log

### Reason Codes
| Code | Keterangan |
|------|------------|
| `DUPLICATE` | Invoice duplikat |
| `GL_ERROR` | Error di General Ledger |
| `TEST_INVOICE` | Invoice test/testing |
| `DATA_ENTRY_ERROR` | Kesalahan input data |
| `WORKFLOW_ERROR` | Error dalam workflow |
| `OTHER` | Lainnya (wajib isi note) |

### Cascade Rules
- Default: Exclude invoice saja
- Optional: Exclude + related payments
- Optional: Exclude + SO + payments
- Toggle `exclude_related_payment` menentukan cascade

---

## RULE 2: Aging Report

### Aging Buckets (Default)
| Bucket | Range | Indicator |
|--------|-------|-----------|
| Current | 0-30 hari | 🟢 Green |
| Overdue 1 | 31-60 hari | 🟡 Yellow |
| Overdue 2 | 61-90 hari | 🟠 Orange |
| Overdue 3 | 90+ hari | 🔴 Red |

### Kalkulasi
- Days overdue = `today - due_date`
- Jika days overdue <= 0 → Current
- Semua kalkulasi **exclude** invoice yang sudah di-exclude

---

## RULE 3: AR Reconciliation

### Matching Priority
1. **Exact Match**: Invoice number = payment reference, amount exact, date ±2 hari
2. **Fuzzy Match**: Amount ±1%, date ±2 hari, Levenshtein distance < 2
3. **Manual Review**: Tidak match setelah priority 1 & 2

### Thresholds
- Auto-match tolerance: ±1% amount
- Date tolerance: ±2 days
- Alert threshold: mismatch > 5% dari total

---

## RULE 4: Collection Reminders

### Escalation Rules
| Overdue Days | Action |
|--------------|--------|
| 1 hari | Email reminder |
| 5 hari | Email + WhatsApp |
| 15 hari | Email + WhatsApp + Escalate |
| 30 hari | Manager alert |

### Smart Scheduling Rules
**JANGAN** kirim reminder jika:
1. Invoice sudah dibayar hari ini
2. Sudah di-reminder minggu ini
3. Customer masuk blacklist reminder

### Contact Preference
Per customer bisa diatur:
- Email only
- WhatsApp only
- Both
- Do Not Contact (compliance)

---

## RULE 5: Credit Limit

### Status Indicators
| Status | Range | Action |
|--------|-------|--------|
| 🟢 GREEN | 0-75% | Safe - proceed |
| 🟡 YELLOW | 75-95% | Caution - need manager approval |
| 🔴 RED | 95-100% | At limit - block new SO |
| ⚫ BLOCKED | >100% | Over limit - stop sales |

### SO Block Logic
1. Hitung: Outstanding AR + Pending SO
2. Jika total > credit limit → block SO
3. Jika 75-95% → require approval
4. Log semua keputusan di audit trail

---

## RULE 6: Collection Status

### Status Workflow
```
PENDING → SENT → 1ST REMINDER → OVERDUE → ESCALATED → SETTLED / WRITTEN-OFF
```

### Auto-Status Rules
- Payment received → auto SETTLED
- Partial payment → PARTIAL
- Overdue 30+ hari → suggest ESCALATED

### Priority Scoring
| Factor | Weight |
|--------|--------|
| Amount > Rp 400M | +3 |
| Overdue > 30 days | +3 |
| High-risk customer | +2 |
| Previous escalation | +2 |

---

## RULE 7: Data Integrity

### Required Fields
- `invoice_number`: unique, not null
- `customer_name`: not null
- `amount`: > 0
- `due_date`: valid date, >= invoice_date
- `reason_code`: must be from valid enum

### Validation
- Semua input divalidasi via **Form Request** Laravel
- Error messages dalam Bahasa Indonesia
- Bulk import: validate baris per baris, skip invalid rows

---

## RULE 8: Access Control

### Role Permissions
| Role | View | Create | Update | Delete | Export | Approve |
|------|------|--------|--------|--------|--------|---------|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Finance Manager | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| AR Accountant | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| AR Collector | ✅ | ✅* | ✅* | ❌ | ❌ | ❌ |
| Sales Manager | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Viewer | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |

*Limited to assigned customers only
