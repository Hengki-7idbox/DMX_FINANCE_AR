# PRD — Finance AR Management System

**Version:** 1.0  
**Tanggal:** 22 September 2026  
**Platform:** Web Application  
**Status:** Draft

---

## 1. Product Overview

Finance AR Management System adalah aplikasi web untuk mengelola proses **Account Receivable (AR)** secara end-to-end, mulai dari pengelolaan customer, invoice/piutang, monitoring aging, collection, pembayaran, rekening koran, rekonsiliasi, sampai pelaporan.

Sistem mendukung dua sumber data:

- **Database Internal**
- **Database External**

Alur utama sistem:

```text
Customer
   ↓
Invoice / Piutang
   ↓
Aging
   ↓
Collection
   ↓
Promise to Pay
   ↓
Payment / Pelunasan
   ↓
Rekening Koran
   ↓
Rekonsiliasi
   ↓
Invoice PAID / PARTIAL / EXCEPTION
   ↓
Reporting
```

---

# 2. Tujuan Produk

## 2.1 Tujuan Utama

Sistem harus mampu:

1. Memusatkan pengelolaan data piutang.
2. Mengelola Customer dan Group Customer.
3. Mengelola invoice internal dan external.
4. Mengimpor laporan piutang dari Excel.
5. Mengelola pengecualian invoice.
6. Menghitung dan mencetak laporan aging piutang.
7. Mengelola pelunasan invoice internal.
8. Mengelola pelunasan invoice external.
9. Melakukan clone pelunasan external ke internal.
10. Mengelola rekening koran.
11. Melakukan matching pembayaran dengan invoice.
12. Melakukan rekonsiliasi invoice, payment, dan rekening koran.
13. Mengelola collection melalui WhatsApp dan email.
14. Mencatat schedule pembayaran dan Promise to Pay.
15. Menganalisis histori pembayaran customer.
16. Menyediakan laporan dan dashboard AR.
17. Menyediakan audit trail seluruh perubahan penting.
18. Mengurangi proses manual Finance AR.

---

# 3. Target User

| Role | Deskripsi |
|---|---|
| Super Admin | Mengelola seluruh sistem |
| Finance AR | Mengelola invoice, payment, aging, rekonsiliasi |
| Collector | Melakukan collection dan follow-up customer |
| Manager | Monitoring, approval, dan reporting |
| Viewer | Melihat data dan laporan |
| Auditor | Melihat data, rekonsiliasi, dan audit trail |

---

# 4. Technology Stack

## Backend

- Laravel 10
- PHP ^8.1
- MySQL
- XAMPP
- Laravel Sanctum
- Maatwebsite Excel
- Laravel Tinker

## Frontend

- Blade
- Tailwind CSS 3.4
- Alpine.js 3.15
- Vite 5
- Lucide Icons
- jQuery
- DataTables
- Select2

## Development & Testing

- PHPUnit
- Laravel Pint
- Laravel Sail
- Faker
- Ignition

## Project Knowledge / AI Memory

- Obsidian
- GitNexus
- Graphify

---

# 5. Database Architecture

Sistem menggunakan dua sumber database:

```text
DATABASE INTERNAL
DATABASE EXTERNAL
```

Setiap transaksi yang membutuhkan identifikasi sumber harus mempunyai informasi source.

Contoh:

```text
source_type
------------
internal
external
```

Sistem tidak boleh mencampur transaksi internal dan external tanpa identitas sumber.

---

# 6. Modul Sistem

```text
DASHBOARD

MASTER DATA
├── Customer
├── Group Customer
├── Customer Contact
├── Payment Term
└── Bank Account

PIUTANG
├── Invoice / Piutang
├── Recap Piutang
├── Import Piutang
├── Exception Invoice
└── Aging Piutang

PEMBAYARAN
├── Pelunasan Internal
├── Pelunasan External
├── Clone Pelunasan External → Internal
├── Payment Matching
└── Payment History

BANK
├── Rekening Koran
├── Import Rekening Koran
├── Bank Transaction
└── Rekonsiliasi

COLLECTION
├── Collection Dashboard
├── Follow Up
├── Schedule Pembayaran
├── Promise to Pay
├── WhatsApp
└── Email

CUSTOMER
├── Customer Assessment
├── Payment History
├── Collection History
└── Dispute

LAPORAN
├── Aging Piutang
├── Outstanding
├── Pembayaran
├── Rekening Koran
├── Rekonsiliasi
├── Collection
├── Customer
└── Aging Snapshot

SYSTEM
├── Users
├── Roles & Permission
├── Settings
├── Import History
└── Audit Log
```

---

# 7. Modul Customer

## 7.1 Customer

Data minimal:

```text
id
customer_code
customer_name
customer_group_id
customer_type
tax_number
address
city
phone
email
payment_term_id
credit_limit
status
notes
created_at
updated_at
```

## 7.2 Fitur

- Tambah customer
- Edit customer
- Detail customer
- Aktif/nonaktif customer
- Search customer
- Filter berdasarkan group
- Filter berdasarkan status
- Riwayat invoice
- Riwayat pembayaran
- Riwayat collection
- Riwayat Promise to Pay
- Riwayat dispute

## 7.3 Group Customer

Customer dapat dikelompokkan berdasarkan kategori.

Contoh:

```text
Distributor
Corporate
Retail
Government
Reseller
Internal
```

Tabel:

```text
customer_groups

id
group_code
group_name
description
status
created_at
updated_at
```

Laporan dapat difilter berdasarkan Group Customer.

---

# 8. Modul Invoice / Piutang

## 8.1 Data Invoice

```text
invoice_id
invoice_number
customer_id
invoice_date
due_date
term
subtotal
discount
tax
grand_total
paid_amount
outstanding_amount
status
source_type
created_at
updated_at
```

## 8.2 Status Invoice

```text
DRAFT
POSTED
UNPAID
PARTIAL
OVERDUE
PAID
CANCELLED
EXCEPTION
```

## 8.3 Fitur

- Input invoice
- Edit invoice
- Detail invoice
- Recap piutang
- Search invoice
- Filter customer
- Filter group customer
- Filter tanggal
- Filter status
- Filter source internal/external
- Export Excel
- Print invoice/rekap sesuai kebutuhan

---

# 9. Import Laporan Piutang

Import harus menggunakan workflow:

```text
UPLOAD
   ↓
MAPPING COLUMN
   ↓
VALIDATION
   ↓
PREVIEW
   ↓
DUPLICATE CHECK
   ↓
IMPORT
   ↓
IMPORT LOG
```

## 9.1 Validation

Sistem harus mendeteksi:

- Invoice kosong
- Customer kosong
- Nominal tidak valid
- Tanggal tidak valid
- Duplicate invoice
- Customer tidak ditemukan
- Data tidak sesuai format

Contoh hasil:

```text
1.245 data valid
23 duplicate
12 customer tidak ditemukan
7 nominal tidak valid
3 invoice kosong
```

Import tidak boleh langsung menyimpan data tanpa proses validasi dan preview.

---

# 10. Exception Invoice

User dapat memasukkan invoice sebagai pengecualian.

Data:

```text
invoice_number
reason
exception_type
start_date
end_date
created_by
approved_by
status
notes
```

Jenis:

```text
DISPUTE
HOLD
CANCELLED
SPECIAL_AGREEMENT
WAITING_DOCUMENT
NOT_COLLECTED
OTHER
```

Invoice exception tetap dapat ditelusuri dan tidak boleh hilang dari audit/reporting tanpa status yang jelas.

---

# 11. Aging Piutang

## 11.1 Formula

```text
aging_days = report_date - due_date
```

Kategori default:

```text
CURRENT
1 - 30
31 - 60
61 - 90
91 - 120
>120
```

## 11.2 Report Date

Laporan aging harus mendukung tanggal laporan tertentu.

Contoh:

```text
Aging Per:
31/08/2026
```

Bukan hanya menggunakan tanggal saat laporan dibuka.

## 11.3 Output

- Total outstanding
- Current
- 1–30
- 31–60
- 61–90
- 91–120
- >120
- Customer
- Group Customer
- Invoice
- Due date
- Outstanding

---

# 12. Pelunasan Internal

Pelunasan internal digunakan untuk invoice yang berasal dari database/sumber internal.

Workflow:

```text
Invoice Internal
      ↓
Pelunasan Internal
      ↓
Pilih Bank / Rekening
      ↓
Input Payment
      ↓
Allocation
      ↓
Update Outstanding
      ↓
PAID / PARTIAL
```

Harus mendukung:

- Full payment
- Partial payment
- Satu payment untuk beberapa invoice
- Payment untuk satu invoice
- Selisih pembayaran
- Biaya bank
- Discount
- Rounding
- Catatan pembayaran
- Auto update status invoice
- Auto journal jika konfigurasi accounting tersedia

---

# 13. Pelunasan External

Pelunasan external digunakan untuk invoice dari database/sumber external.

Workflow:

```text
Invoice External
      ↓
Pelunasan External
      ↓
Payment
      ↓
Allocation
      ↓
PAID / PARTIAL
```

Pelunasan external harus menyimpan `source_type = external`.

---

# 14. Clone Pelunasan External ke Internal

User dapat melakukan:

```text
External Payment
       ↓
[ CLONE ]
       ↓
Internal Payment
```

Data clone harus menyimpan relasi sumber:

```text
source_type
source_payment_id
```

Tujuan:

- Menjaga traceability
- Menghindari input ulang
- Mengetahui asal transaksi
- Memudahkan audit

---

# 15. Payment Allocation

Satu pembayaran dapat dialokasikan ke beberapa invoice.

Contoh:

```text
Payment = Rp 20.000.000

INV-001 = Rp 10.000.000
INV-002 = Rp  7.000.000
INV-003 = Rp  3.000.000
```

Model data:

```text
payments
payment_allocations
```

Jangan menyimpan seluruh allocation hanya sebagai satu field pada payment.

---

# 16. Rekening Koran

## 16.1 Bank Account

Data:

```text
id
bank_name
account_number
account_name
currency
status
```

## 16.2 Bank Transaction

Data:

```text
id
bank_account_id
transaction_date
value_date
description
reference_number
debit
credit
balance
transaction_type
source
status
created_at
updated_at
```

Contoh:

```text
Tanggal     Keterangan              Debit       Credit
-------------------------------------------------------
22/09/26    PT ABC Indonesia                    10.000.000
22/09/26    Biaya Admin                 25.000
23/09/26    PT XYZ Indonesia                     7.500.000
```

---

# 17. Import Rekening Koran

Workflow:

```text
UPLOAD FILE
     ↓
PILIH BANK
     ↓
MAPPING COLUMN
     ↓
PREVIEW
     ↓
VALIDATION
     ↓
DUPLICATE CHECK
     ↓
IMPORT
```

Sistem harus dapat mendeteksi:

- Duplicate transaction
- Nominal invalid
- Tanggal invalid
- Reference kosong
- Format tidak sesuai

---

# 18. Payment Matching

Sistem harus dapat mencari payment berdasarkan:

- Nomor invoice
- Nama customer
- Nominal
- Reference
- Tanggal
- Kombinasi beberapa parameter

Contoh:

```text
Bank Transaction

PT ABC
Rp 10.000.000
```

Sistem mencari:

```text
INV-001    Rp 6.000.000
INV-005    Rp 4.000.000
```

User dapat memilih invoice yang sesuai.

---

# 19. Rekonsiliasi

Rekonsiliasi adalah proses mencocokkan:

```text
Invoice
   ↕
Payment
   ↕
Bank Transaction
```

Selain itu sistem harus mendukung:

```text
Internal Invoice
      ↕
External Invoice
      ↕
Payment
```

## 19.1 Rekonsiliasi Invoice

Parameter:

- Invoice Number
- Customer
- Invoice Date
- Nominal
- Tax
- Grand Total

## 19.2 Rekonsiliasi Bank

Parameter:

- Customer
- Reference
- Nominal
- Tanggal
- Invoice
- Payment

## 19.3 Status Rekonsiliasi

```text
UNMATCHED
PARTIAL_MATCH
MATCHED
RECONCILED
IGNORED
```

---

# 20. Selisih Rekonsiliasi

Contoh:

```text
Invoice          Rp 777.000
Bank Transaction Rp 774.100
Difference       Rp   2.900
```

Sistem harus meminta klasifikasi:

```text
BANK_FEE
DISCOUNT
ROUNDING
TAX
RETURN
OTHER
```

Contoh jurnal jika selisih adalah biaya bank:

```text
Dr Bank                  Rp 774.100
Dr Biaya Bank             Rp   2.900
    Cr Piutang            Rp 777.000
```

Jurnal aktual harus mengikuti konfigurasi accounting perusahaan.

---

# 21. Rekonsiliasi Center

Sediakan halaman khusus:

```text
RECONCILIATION CENTER
```

Tab:

```text
[ Invoice ]
[ Payment ]
[ Bank ]
[ Internal vs External ]
```

Summary:

```text
Unmatched
Partial
Matched
Reconciled
```

Filter:

- Bank
- Tanggal
- Customer
- Group Customer
- Nominal
- Status
- Reference
- Source

---

# 22. Collection

Collection harus terintegrasi dengan aging.

Workflow:

```text
Invoice Due / Overdue
        ↓
Collection Queue
        ↓
Follow Up
        ↓
Customer Response
        ↓
Promise to Pay
        ↓
Payment
        ↓
Reconciliation
        ↓
Closed
```

---

# 23. WhatsApp & Email Automation

Channel:

```text
WhatsApp
Email
```

Library/integrasi WhatsApp menggunakan **OpenWA** sesuai kebutuhan implementasi.

AI dapat digunakan untuk membantu:

- Membuat draft pesan
- Menyesuaikan bahasa
- Membuat reminder
- Merangkum respons customer
- Menentukan follow-up berikutnya berdasarkan data

Namun pengiriman otomatis harus memiliki kontrol dan audit.

Workflow:

```text
Invoice Overdue
      ↓
Generate Message
      ↓
AI Personalization
      ↓
Preview / Approval
      ↓
Send
      ↓
Save Collection Activity
```

---

# 24. Collection Activity

Setiap aktivitas collection harus disimpan.

Data minimal:

```text
customer_id
invoice_id
channel
message
sent_at
status
response
next_follow_up
created_by
```

Channel:

```text
WHATSAPP
EMAIL
PHONE
MEETING
OTHER
```

---

# 25. Schedule Pembayaran

Sistem harus dapat mencatat:

```text
customer
invoice
schedule_date
schedule_amount
notes
status
```

Status:

```text
PENDING
COMPLETED
PARTIAL
CANCELLED
```

---

# 26. Promise to Pay

Data:

```text
customer_id
invoice_id
promise_date
promise_amount
actual_payment_date
actual_payment_amount
status
notes
```

Status:

```text
PENDING
FULFILLED
PARTIAL
BROKEN
CANCELLED
```

Jika tanggal janji terlewati tanpa pembayaran sesuai janji:

```text
PROMISE BROKEN
```

dan masuk kembali ke collection queue.

---

# 27. Customer Assessment

Sistem menyimpan histori pembayaran customer.

Parameter:

```text
Payment On Time %
Average Days Late
Overdue Invoice Count
Promise Fulfillment %
Average Payment Delay
Total Outstanding
Oldest Outstanding
```

Kategori sistem dapat berupa:

```text
NORMAL
PERLU MONITORING
RISIKO TINGGI
```

Kategori harus berasal dari parameter yang terdokumentasi dan dapat dikonfigurasi.

---

# 28. Dispute Management

Customer dapat memiliki dispute terhadap invoice.

Data:

```text
invoice_id
customer_id
reason
amount_disputed
pic
status
resolution
resolved_date
notes
```

Status:

```text
OPEN
IN_PROGRESS
WAITING_CUSTOMER
RESOLVED
CANCELLED
```

---

# 29. Customer History

Halaman customer harus menampilkan timeline:

```text
Customer
   │
   ├── Invoice
   ├── Payment
   ├── Collection
   ├── Promise to Pay
   ├── Dispute
   └── Reconciliation
```

Tujuan agar Finance dapat melihat seluruh histori customer dari satu halaman.

---

# 30. Dashboard AR

Dashboard menampilkan:

### KPI

```text
Total Outstanding
Overdue
Due Soon
Paid
Partial
Unmatched Payment
Unreconciled Bank
Promise Due Today
```

### Aging

```text
Current
1-30
31-60
61-90
91-120
>120
```

### Monitoring

- Top outstanding customer
- Overdue invoice
- Payment belum match
- Rekening koran belum reconcile
- Promise to Pay hari ini
- Promise yang broken
- Invoice exception

Dashboard dapat difilter:

```text
Tanggal
Customer
Group Customer
Source
Bank
Status
```

---

# 31. Laporan

## 31.1 Aging Piutang

- Per customer
- Per group customer
- Per invoice
- Per periode

## 31.2 Outstanding

- Total outstanding
- Current
- Overdue
- Partial

## 31.3 Payment

- Payment per periode
- Payment per customer
- Payment per bank
- Payment internal/external

## 31.4 Rekening Koran

- Transaction
- Debit
- Credit
- Unmatched
- Reconciled

## 31.5 Rekonsiliasi

- Matched
- Unmatched
- Partial
- Difference
- Reconciled

## 31.6 Collection

- Jumlah follow-up
- Follow-up berhasil
- Promise to Pay
- Promise broken
- Payment hasil collection

## 31.7 Customer

- Outstanding per customer
- Aging per customer
- Payment history
- Collection history

## 31.8 Aging Snapshot

Sistem dapat menyimpan snapshot aging pada tanggal tertentu.

Contoh:

```text
31/07/2026
31/08/2026
30/09/2026
```

Tujuannya untuk membandingkan posisi AR antar periode.

---

# 32. DataTables Standard

Semua tabel menggunakan DataTables dengan konfigurasi default:

```text
25 records / page
Search
Pagination
Sorting
Column visibility
Export
```

Untuk data besar, gunakan:

```text
Server Side Processing
```

terutama:

- Invoice
- Payment
- Customer
- Bank Transaction
- Collection
- Audit Log

---

# 33. Select2 Standard

Select2 digunakan untuk field dengan pilihan data besar:

- Customer
- Invoice
- Group Customer
- Bank
- Payment
- PIC
- Sales

Customer dan data besar harus menggunakan AJAX search.

---

# 34. UI / UX

Sistem memiliki dua tema:

```text
LIGHT
DARK
```

Layout utama:

```text
┌─────────────────────────────────────────────┐
│ Logo     Search       Notification   User   │
├───────────┬─────────────────────────────────┤
│ Dashboard │                                 │
│ Customer  │            CONTENT              │
│ Piutang   │                                 │
│ Payment   │                                 │
│ Bank      │                                 │
│ Collection│                                 │
│ Report    │                                 │
│ Settings  │                                 │
└───────────┴─────────────────────────────────┘
```

Layout Blade:

```text
admin
viewer
mobile
print
```

---

# 35. Authentication & Authorization

Menggunakan:

```text
Laravel Sanctum
```

Permission harus diterapkan pada:

- Menu
- Route
- Controller
- Action
- Data tertentu bila diperlukan

Contoh:

```text
invoice.view
invoice.create
invoice.update
invoice.delete

payment.view
payment.create
payment.reconcile

bank.view
bank.import
bank.reconcile

collection.view
collection.create
collection.send

report.view
report.export
```

---

# 36. Audit Trail

Semua aktivitas penting harus tercatat.

Data:

```text
user_id
action
module
record_id
old_value
new_value
ip_address
user_agent
created_at
```

Contoh:

```text
User: Budi
Action: UPDATE
Module: Invoice
Invoice: INV-001

grand_total:
10.000.000
→
9.500.000

22/09/2026 10:32
```

Audit log tidak boleh dapat dihapus oleh user biasa.

---

# 37. Import History

Setiap proses import menyimpan:

```text
batch_id
file_name
module
uploaded_by
uploaded_at
total_rows
success_rows
failed_rows
duplicate_rows
status
```

Status:

```text
UPLOADED
PROCESSING
COMPLETED
PARTIAL
FAILED
```

---

# 38. Business Rules

## Invoice

```text
outstanding = grand_total - total_allocated_payment
```

## Full Payment

Jika:

```text
outstanding <= 0
```

maka:

```text
PAID
```

## Partial Payment

Jika:

```text
0 < payment < grand_total
```

maka:

```text
PARTIAL
```

## Overdue

Jika:

```text
today > due_date
AND outstanding > 0
```

maka invoice dianggap overdue.

## Rekonsiliasi

Invoice hanya dapat dianggap fully reconciled jika:

```text
invoice
+
payment
+
bank transaction
```

sudah memiliki hubungan/validasi yang sesuai.

---

# 39. Non-Functional Requirements

## Performance

Target:

- Page load normal < 3 detik untuk halaman standar.
- Search tabel menggunakan server-side untuk data besar.
- Import besar dilakukan melalui proses terkontrol.
- Query harus menggunakan indexing yang sesuai.

## Security

- Authentication
- Authorization
- CSRF protection
- Validation
- SQL injection protection
- XSS protection
- File upload validation
- Audit trail
- Secure credential storage

## Reliability

- Transaction database untuk proses financial critical.
- Tidak boleh terjadi payment tersimpan tetapi allocation gagal tanpa rollback.
- Import harus dapat dilacak berdasarkan batch.

---

# 40. Database Entity Relationship

Relasi utama:

```text
CustomerGroup
      │
      └──< Customer
                │
       ┌────────┼──────────┐
       │        │          │
       ▼        ▼          ▼
    Invoice  Collection  Promise
       │
       │
       ▼
   Payment
       │
       ▼
Payment Allocation
       │
       ▼
    Invoice


Bank Account
      │
      ▼
Bank Transaction
      │
      ▼
Reconciliation
      │
      ├── Payment
      └── Invoice
```

---

# 41. Core Database Tables

Minimal tabel:

```text
customer_groups
customers
customer_contacts
payment_terms

invoices
invoice_items
invoice_exceptions

payments
payment_allocations

bank_accounts
bank_transactions
reconciliations

promise_to_pay
collection_activities
disputes

customer_assessments
aging_snapshots

import_batches
audit_logs

users
roles
permissions
```

---

# 42. Financial Transaction Safety

Untuk transaksi financial:

```text
BEGIN TRANSACTION
      ↓
Validate
      ↓
Create / Update Payment
      ↓
Create Allocation
      ↓
Update Invoice
      ↓
Create Journal (jika aktif)
      ↓
Create Audit Log
      ↓
COMMIT
```

Jika salah satu proses gagal:

```text
ROLLBACK
```

---

# 43. Notification

Sistem dapat memberikan notification untuk:

- Invoice jatuh tempo
- Invoice overdue
- Promise to Pay hari ini
- Promise broken
- Payment unmatched
- Bank transaction belum reconcile
- Import gagal
- Rekonsiliasi memiliki selisih
- Approval diperlukan

---

# 44. Search Global

Global search dapat mencari:

```text
Invoice Number
Customer
Customer Code
Payment Reference
Bank Transaction
Phone
Email
```

Contoh:

```text
Search:
INV-002616

Result:
Invoice
Customer
Payment
Bank Transaction
Collection
Reconciliation
```

---

# 45. Approval

Approval dapat diterapkan untuk:

- Invoice exception
- Discount
- Write-off
- Selisih pembayaran
- Manual reconciliation
- Cancel payment
- Adjustment tertentu

Contoh:

```text
Finance
   ↓
Submit Adjustment
   ↓
Manager
   ↓
Approve / Reject
```

---

# 46. MVP Scope

Tahap pertama sebaiknya fokus pada core AR.

## Phase 1

```text
Customer
Group Customer
Invoice
Import Piutang
Aging
Pelunasan Internal
Pelunasan External
Payment Allocation
```

## Phase 2

```text
Rekening Koran
Import Rekening Koran
Payment Matching
Rekonsiliasi
```

## Phase 3

```text
Collection
WhatsApp
Email
Promise to Pay
Schedule Pembayaran
```

## Phase 4

```text
Customer Assessment
Dispute
AI Assistance
Aging Snapshot
Advanced Dashboard
```

---

# 47. Acceptance Criteria Utama

## Customer

- User dapat membuat customer.
- User dapat memasukkan customer ke group.
- User dapat melihat seluruh histori customer.

## Invoice

- Invoice dapat dibuat.
- Invoice internal dan external dapat dibedakan.
- Outstanding otomatis dihitung.

## Payment

- Full payment dapat dilakukan.
- Partial payment dapat dilakukan.
- Satu payment dapat dialokasikan ke beberapa invoice.
- Status invoice otomatis diperbarui.

## Rekening Koran

- File dapat di-upload.
- Kolom dapat dimapping.
- Duplicate dapat terdeteksi.
- Transaction dapat ditampilkan.

## Rekonsiliasi

- Bank transaction dapat dicari berdasarkan customer.
- Bank transaction dapat dicari berdasarkan nominal.
- Invoice kandidat dapat ditemukan.
- Payment dapat di-match.
- Selisih dapat dicatat.
- Status rekonsiliasi dapat dilacak.

## Collection

- Invoice overdue masuk collection queue.
- Follow-up dapat dicatat.
- WhatsApp/email dapat digunakan.
- Promise to Pay dapat dicatat.
- Promise broken dapat dideteksi.

---

# 48. Success Metrics

Sistem dianggap berhasil apabila:

1. Finance dapat mengetahui outstanding AR dari satu dashboard.
2. Finance dapat mengetahui invoice overdue.
3. Payment dapat dicocokkan dengan invoice.
4. Rekening koran dapat diimport.
5. Transaksi bank dapat direkonsiliasi.
6. Partial payment dapat ditangani.
7. Selisih pembayaran dapat ditelusuri.
8. Histori collection dapat dilihat.
9. Histori customer dapat dilihat.
10. Seluruh transaksi financial penting memiliki audit trail.

---

# 49. Prinsip Arsitektur

Prinsip utama development:

```text
Database First
      ↓
Domain Model
      ↓
Business Rules
      ↓
Service Layer
      ↓
API / Controller
      ↓
Blade / UI
```

Untuk business logic financial, hindari menempatkan seluruh logic di Controller.

Gunakan service khusus, misalnya:

```text
InvoiceService
PaymentService
PaymentAllocationService
BankTransactionService
ReconciliationService
AgingService
CollectionService
CustomerAssessmentService
```

---

# 50. Core Workflow

Workflow utama aplikasi:

```text
                    CUSTOMER
                       │
                       ▼
                    INVOICE
                       │
                       ▼
                     AGING
                       │
              ┌────────┴────────┐
              │                 │
              ▼                 ▼
          NOT DUE            OVERDUE
                                │
                                ▼
                           COLLECTION
                                │
                                ▼
                         PROMISE TO PAY
                                │
                                ▼
                            PAYMENT
                                │
              ┌─────────────────┴─────────────────┐
              │                                   │
              ▼                                   ▼
       PELUNASAN INTERNAL                 PELUNASAN EXTERNAL
              │                                   │
              └─────────────────┬─────────────────┘
                                ▼
                           BANK / PAYMENT
                                │
                                ▼
                        REKENING KORAN
                                │
                                ▼
                         REKONSILIASI
                                │
                    ┌───────────┴───────────┐
                    ▼                       ▼
                 MATCHED                DIFFERENCE
                    │                       │
                    ▼                       ▼
                  PAID              BANK FEE / OTHER
```

---

# 51. Future Enhancement

Fitur yang dapat ditambahkan setelah core system stabil:

- Auto bank reconciliation
- AI payment matching
- AI collection assistant
- Predictive cash collection berdasarkan histori
- Collection priority queue
- Customer portal
- Customer self-service payment confirmation
- Payment link
- Automatic reminder schedule
- WhatsApp chatbot
- OCR rekening koran
- OCR invoice
- Multi-company
- Multi-currency
- Multi-bank
- Accounting journal integration
- ERP integration
- API integration
- Mobile PWA
- Advanced analytics

---

# 52. Final Product Structure

```text
FINANCE AR
│
├── DASHBOARD
│
├── MASTER DATA
│   ├── Customer
│   ├── Group Customer
│   ├── Contact
│   ├── Payment Term
│   └── Bank Account
│
├── PIUTANG
│   ├── Invoice
│   ├── Recap
│   ├── Import
│   ├── Exception
│   └── Aging
│
├── PEMBAYARAN
│   ├── Pelunasan Internal
│   ├── Pelunasan External
│   ├── Clone External → Internal
│   ├── Payment Matching
│   └── Payment Allocation
│
├── BANK
│   ├── Rekening Koran
│   ├── Import
│   ├── Bank Transaction
│   └── Rekonsiliasi
│
├── COLLECTION
│   ├── Dashboard
│   ├── Follow Up
│   ├── Schedule
│   ├── Promise to Pay
│   ├── WhatsApp
│   └── Email
│
├── CUSTOMER
│   ├── Assessment
│   ├── Payment History
│   ├── Collection History
│   └── Dispute
│
├── REPORT
│   ├── Aging
│   ├── Outstanding
│   ├── Payment
│   ├── Bank
│   ├── Reconciliation
│   ├── Collection
│   └── Customer
│
└── SYSTEM
    ├── User
    ├── Role & Permission
    ├── Settings
    ├── Import History
    └── Audit Log
```

---

## 53. Definition of Done

Sebuah fitur dianggap selesai apabila:

- Business rule telah terdokumentasi.
- Migration telah dibuat.
- Model dan relationship telah dibuat.
- Validation telah dibuat.
- Authorization telah dibuat.
- Service/business logic telah dibuat.
- UI telah dibuat untuk light dan dark mode.
- DataTables/Select2 telah diterapkan sesuai kebutuhan.
- Error handling telah dibuat.
- Audit trail telah diterapkan untuk transaksi penting.
- PHPUnit test telah dibuat untuk business logic.
- Tidak terdapat error Laravel Pint.
- Tidak terdapat regression pada fitur terkait.
- Acceptance criteria telah terpenuhi.
