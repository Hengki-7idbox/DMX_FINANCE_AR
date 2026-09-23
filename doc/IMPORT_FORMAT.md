# Import Format Guide

**Version:** 1.0  
**Date:** 22 September 2026

---

## Piutang Import Format

### Column Mapping

```text
| Excel Column      | Database Field    | Required | Format        |
|-------------------|-------------------|----------|---------------|
| No. Invoice       | invoice_number    | Yes      | String        |
| Kode Customer     | customer_code     | Yes      | String        |
| Tanggal Invoice   | invoice_date      | Yes      | DD/MM/YYYY    |
| Jatuh Tempo       | due_date          | Yes      | DD/MM/YYYY    |
| Total             | grand_total       | Yes      | Number        |
| Diskon            | discount          | No       | Number        |
| Pajak             | tax               | No       | Number        |
| Sisa Outstanding  | outstanding_amount| No       | Number        |
| Keterangan        | notes             | No       | String        |
```

### Example

```text
No. Invoice    Kode Customer  Tanggal Invoice  Jatuh Tempo  Total        Outstanding
INV-001/09/26  CUST-001       01/09/2026       01/10/2026   10000000     10000000
INV-002/09/26  CUST-002       05/09/2026       05/10/2026   5000000      3000000
```

## Rekening Koran Import Format

### Column Mapping

```text
| Excel Column      | Database Field     | Required | Format        |
|-------------------|--------------------|----------|---------------|
| Tanggal           | transaction_date   | Yes      | DD/MM/YYYY    |
| Keterangan        | description        | Yes      | String        |
| Referensi         | reference_number   | Yes      | String        |
| Debit             | debit              | No       | Number        |
| Credit            | credit             | No       | Number        |
| Saldo             | balance            | No       | Number        |
```

### Example

```text
Tanggal      Keterangan                     Referensi       Debit        Credit
22/09/2026   PT ABC Indonesia               TRF-001                      10000000
22/09/2026   Biaya Admin                    FEE-001         25000
23/09/2026   PT XYZ Indonesia               TRF-002                      7500000
```

## Validation Rules

### Invoice Import

```text
1. invoice_number: required, unique
2. customer_code: must exist in system
3. invoice_date: valid date format
4. due_date: must be >= invoice_date
5. grand_total: must be > 0
6. Duplicate check: invoice_number
```

### Bank Import

```text
1. transaction_date: valid date format
2. reference_number: required
3. Either debit or credit must be > 0
4. Balance calculation must be correct
5. Duplicate check: reference_number + transaction_date
```

## Import Workflow

```text
1. Upload File
   - Validate file type
   - Validate file size
   
2. Column Mapping
   - Auto-detect columns
   - Manual mapping option
   
3. Validation
   - Check required fields
   - Check data formats
   - Check business rules
   
4. Preview
   - Show valid records
   - Show invalid records
   - Show duplicate records
   
5. Import
   - Import valid records
   - Skip duplicates
   - Log failed records
   
6. Report
   - Total records
   - Success count
   - Failed count
   - Duplicate count
```

## File Requirements

```text
- Format: .xlsx, .xls, .csv
- Maximum size: 10MB
- Encoding: UTF-8
- Date format: DD/MM/YYYY
- Number format: without thousand separator
```

## Error Handling

```text
- Row validation: Skip row, log error
- Column mapping: Prompt user to remap
- Duplicate: Skip, log, continue
- File error: Stop, show error message
```
