# Handover AI

## Overview
Dokumen handover untuk AI Agent (Gitnexus/Codebuff) yang meneruskan development AR Finance Tools.

---

## Project Summary

### What is this?
AR Finance Tools System v1.0 — platform terintegrasi untuk manajemen Accounts Receivable di PT. DMX Trading Indonesia.

### Tech Stack
- **Backend**: Laravel 10 (PHP ^8.1)
- **Database**: MySQL (absensi_dmx)
- **Frontend**: Blade + Tailwind CSS 3.4 + Alpine.js 3.15
- **Bundler**: Vite 5
- **Icons**: Lucide
- **Auth**: Laravel Sanctum

### Current Status
- Project initialized
- Documentation complete (26 doc files)
- **No code written yet** — still in planning phase

---

## Key Files to Read

### Must Read
1. `doc/PRD_Sistem.md` - Product requirements (THE source of truth)
2. `doc/DATABASE.md` - All database tables and relationships
3. `doc/BUSINESS_RULES.md` - Business rules for all 8 tools
4. `doc/ROUTE_MAP.md` - All routes (web + API)
5. `doc/API.md` - API endpoint documentation

### Should Read
6. `doc/CONFIGURATION.md` - App config and env variables
7. `doc/PERMISSION_SYSTEM.md` - Role-based access control
8. `doc/VALIDATION_RULES.md` - All validation rules
9. `doc/COMPONENT_API.md` - Blade components and Alpine.js patterns
10. `doc/DESIGN_SYSTEM.md` - UI design system

### Reference
11. `doc/ERROR_STRATEGY.md` - Error handling patterns
12. `doc/AUDIT_TRAIL_PATTERN.md` - Audit trail implementation
13. `doc/IMPORT_FORMAT.md` - File import formats
14. `doc/SECURITY.md` - Security requirements

---

## Architecture Overview

### Directory Structure
```
ar-finance-tools/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Web + API controllers
│   │   ├── Middleware/      # Role, auth middleware
│   │   └── Requests/       # Form request validation
│   ├── Models/             # Eloquent models
│   └── Traits/             # Shared traits (Auditable)
├── config/
│   └── ar_finance.php      # Custom app config
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Seeders
├── doc/                    # Documentation (THIS FOLDER)
├── resources/
│   ├── views/
│   │   ├── components/     # Blade components
│   │   ├── layouts/        # admin, viewer, mobile, print
│   │   └── pages/          # Page views
│   └── css/               # Tailwind CSS
├── routes/
│   ├── api.php            # API routes (Sanctum)
│   └── web.php            # Web routes (Blade)
└── tests/
    ├── Unit/
    └── Feature/
```

### Core Models
```php
User -> Customer -> Invoice -> Payment
                  -> CreditLimit
Invoice -> InvoiceExclusion (by invoice_number)
Invoice -> CollectionAction
```

### Key Concept: Invoice Exclusion
**ALL AR calculations MUST filter excluded invoices.**
```php
// This pattern must be used everywhere
$query->whereNotIn('invoice_number', function ($q) {
    $q->select('invoice_number')
      ->from('invoice_exclusions')
      ->where('status', 'ACTIVE');
});
```

---

## Implementation Order

### Phase 1: Foundation (Weeks 1-4)
1. Laravel project init
2. MySQL database setup
3. User model + auth
4. Layouts (admin, viewer, mobile, print)
5. **Tool 1: Invoice Exclusion** (complete)

### Phase 2: Core Reporting (Weeks 5-10)
1. **Tool 2: Aging Report**
2. **Tool 3: AR Reconciliation**

### Phase 3: Tracking & Automation (Weeks 11-16)
1. **Tool 8: Collection Tracker**
2. **Tool 4: Email & WA Reminders**

### Phase 4: Monitoring (Weeks 17-22)
1. **Tool 5: Credit Limit Monitor**
2. **Tool 7: Customer Analyzer**

### Phase 5: Visualization (Weeks 23-26)
1. **Tool 6: Dashboard**

### Phase 6: Enhancement (Weeks 27-32)
1. Mobile optimization
2. Advanced features

---

## Common Pitfalls

### DO
- ✅ Always use Eloquent ORM (no raw SQL)
- ✅ Always filter excluded invoices in AR queries
- ✅ Use Form Request for validation
- ✅ Add audit trail for important operations
- ✅ Follow RBAC for all actions
- ✅ Use Blade components for reusable UI

### DON'T
- ❌ Never hardcode credentials
- ❌ Never skip validation
- ❌ Never bypass authentication
- ❌ Never use `SELECT *`
- ❌ Never allow SQL injection
- ❌ Never forget exclusion filter in AR calculations

---

## Tools Integration

### Obsidian (Project Memory)
- All `doc/*.md` files are synced
- Update after major changes

### Gitnexus (AI Memory)
- Code patterns and solutions
- Update after completing features

### Graphify (Knowledge Graph)
- Model relationships
- Data flow visualization
- Update when schema changes

---

## Contact / Context

### Project Owner
- **Author**: Hengki Setia Putra
- **Organization**: PT. DMX Trading Indonesia

### Decision Log
- Tech stack changed from React/FastAPI to Laravel/Blade (2026-08-22)
- Database changed from PostgreSQL to MySQL
- Frontend uses Blade templates (not SPA)

---

## Next Steps for AI Agent

1. Read this document fully
2. Read `PRD_Sistem.md` for complete requirements
3. Read `DATABASE.md` for schema
4. Start with Phase 1: Laravel project initialization
5. Follow implementation order above
6. Update `PROGRESS.md` as you complete tasks
7. Update `CHANGELOG.md` with each feature
