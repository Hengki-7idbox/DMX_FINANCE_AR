# PRD Sistem Finance AR

**Version:** 1.0  
**Date:** 22 September 2026

---

## Product Overview

Finance AR Management System adalah aplikasi web untuk mengelola proses **Account Receivable (AR)** secara end-to-end.

## Alur Utama

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

## Target User

| Role | Deskripsi |
|---|---|
| Super Admin | Mengelola seluruh sistem |
| Finance AR | Mengelola invoice, payment, aging, rekonsiliasi |
| Collector | Melakukan collection dan follow-up customer |
| Manager | Monitoring, approval, dan reporting |
| Viewer | Melihat data dan laporan |
| Auditor | Melihat data, rekonsiliasi, dan audit trail |

## Technology Stack

### Backend
- Laravel 10
- PHP ^8.1
- MySQL
- Laravel Sanctum
- Maatwebsite Excel

### Frontend
- Blade
- Tailwind CSS 3.4
- Alpine.js 3.15
- Vite 5
- Lucide Icons
- DataTables
- Select2

### AI/ Knowledge
- Obsidian
- GitNexus
- Graphify

## Module List

```text
DASHBOARD
MASTER DATA (Customer, Group, Contact, Payment Term, Bank Account)
PIUTANG (Invoice, Recap, Import, Exception, Aging)
PEMBAYARAN (Internal, External, Clone, Matching, Allocation)
BANK (Rekening Koran, Import, Transaction, Rekonsiliasi)
COLLECTION (Dashboard, Follow Up, Schedule, Promise, WhatsApp, Email)
CUSTOMER (Assessment, Payment History, Collection History, Dispute)
LAPORAN (Aging, Outstanding, Payment, Rekonsiliasi, Collection, Customer)
SYSTEM (Users, Roles, Settings, Import History, Audit Log)
```

## MVP Scope

### Phase 1
```text
Customer, Group Customer, Invoice, Import Piutang, Aging, Pelunasan Internal, Pelunasan External, Payment Allocation
```

### Phase 2
```text
Rekening Koran, Import Rekening Koran, Payment Matching, Rekonsiliasi
```

### Phase 3
```text
Collection, WhatsApp, Email, Promise to Pay, Schedule Pembayaran
```

### Phase 4
```text
Customer Assessment, Dispute, AI Assistance, Aging Snapshot, Advanced Dashboard
```

## Definition of Done

```text
- Business rule terdokumentasi
- Migration dibuat
- Model dan relationship dibuat
- Validation dibuat
- Authorization dibuat
- Service/business logic dibuat
- UI dibuat untuk light dan dark mode
- DataTables/Select2 diterapkan
- Error handling dibuat
- Audit trail diterapkan
- PHPUnit test dibuat
- Tidak ada error Laravel Pint
- Tidak ada regression
- Acceptance criteria terpenuhi
```
