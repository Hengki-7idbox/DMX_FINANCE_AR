# Import Format

## Overview
Format file yang didukung untuk import data ke AR Finance Tools.

---

## Supported Formats

| Format | Extension | Usage |
|--------|-----------|-------|
| CSV | `.csv` | Generic data import |
| Excel | `.xlsx`, `.xls` | Spreadsheet import |
| XML | `.xml` | Accurate 5 integration |
| JSON | `.json` | API data import |

---

## CSV Format

### Invoice Exclusion Import
```csv
invoice_number,reason_code,reason_detail,exclude_related_payment,notes
INV-2026-001,DUPLICATE,Duplicate invoice from March,,Original: INV-2026-001-M
INV-2026-005,TEST_INVOICE,,,Test data from development
INV-2026-010,DATA_ENTRY_ERROR,,false,Wrong amount entered
```

#### Columns
| Column | Required | Type | Description |
|--------|----------|------|-------------|
| `invoice_number` | ✅ | String | Nomor invoice |
| `reason_code` | ✅ | Enum | Reason: DUPLICATE, GL_ERROR, TEST_INVOICE, DATA_ENTRY_ERROR, WORKFLOW_ERROR, OTHER |
| `reason_detail` | ❌ | String | Detail tambahan |
| `exclude_related_payment` | ❌ | Boolean | Default: true |
| `notes` | ❌ | String | Catatan |

### Customer Import
```csv
code,name,address,phone,email,credit_limit,payment_terms,region,status
CUST-001,PT ABC Distributor,Jl. Sudirman 100,081234567890,abc@example.com,2000000000,30,Jakarta,ACTIVE
CUST-002,CV DEF Trading,Jl. Gatot Subroto 50,081234567891,def@example.com,1000000000,60,Surabaya,ACTIVE
```

### Invoice Import
```csv
invoice_number,customer_code,invoice_date,due_date,amount,currency,notes
INV-2026-001,CUST-001,2026-07-01,2026-07-31,500000000,IDR,
INV-2026-002,CUST-002,2026-07-15,2026-08-14,300000000,IDR,Plus tax
```

### Payment Import
```csv
invoice_number,payment_date,amount,payment_method,reference_number,bank_name,notes
INV-2026-001,2026-07-28,500000000,BCA Transfer,TRF-2026-07-28-001,BCA,Full payment
INV-2026-002,2026-08-10,150000000,Cash,,Cash partial payment
```

---

## Excel Format

### Template
File template tersedia di: `storage/app/templates/`

| Template | File |
|----------|------|
| Exclusion Import | `exclusion_import_template.xlsx` |
| Customer Import | `customer_import_template.xlsx` |
| Invoice Import | `invoice_import_template.xlsx` |
| Bank Statement | `bank_statement_template.xlsx` |

### Requirements
- Header row harus sesuai template
- Tidak ada baris kosong di tengah
- Format tanggal: `YYYY-MM-DD`
- Format angka: tanpa separator (500000000, bukan 500,000,000)

---

## XML Format (Accurate 5)

### Import Structure
```xml
<?xml version="1.0" encoding="utf-8"?>
<Accurate>
    <Invoice>
        <Number>INV-2026-001</Number>
        <Customer>PT ABC Distributor</Customer>
        <Date>2026-07-01</Date>
        <DueDate>2026-07-31</DueDate>
        <Amount>500000000</Amount>
        <Currency>IDR</Currency>
    </Invoice>
</Accurate>
```

---

## Validation Rules

### Before Import
1. File size max: **5MB**
2. Max rows: **5000**
3. Required columns must exist
4. Data types must match

### During Import
1. Validate each row
2. Skip invalid rows (log errors)
3. Show import summary
4. Transaction: all-or-nothing (optional)

### Import Summary
```
Import Summary
──────────────
Total rows:     100
Success:         95
Failed:           5
Skipped:          0

Failed Rows:
Row 12: Duplicate invoice number INV-2026-005
Row 23: Invalid reason code INVALID
Row 45: Amount must be greater than 0
Row 67: Customer code CUST-999 not found
Row 89: Invalid date format
```

---

## Error Handling

| Error | Code | Action |
|-------|------|--------|
| File too large | 413 | Reject upload |
| Invalid format | 415 | Reject upload |
| Missing columns | 422 | Reject upload |
| Duplicate data | 409 | Skip row, log |
| Invalid data | 422 | Skip row, log |
| Server error | 500 | Rollback, alert |
