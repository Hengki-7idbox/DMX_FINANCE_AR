# Contributing Guide

## Overview
Panduan untuk kontribusi ke project AR Finance Tools.

---

## Development Workflow

### 1. Clone & Setup
```bash
git clone <repo-url>
cd ar-finance-tools
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### 2. Create Branch
```bash
# Feature branch
git checkout -b feature/tool-1-exclusion

# Bug fix
git checkout -b fix/aging-report-calculation

# Documentation
git checkout -b docs/update-api
```

### 3. Code Standards

#### PHP (Laravel Pint)
```bash
./vendor/bin/pint
```

#### JavaScript
- Vanilla JS untuk simple interactions
- Alpine.js untuk reactivity
- Hindari framework JS berat

#### Blade Templates
- Gunakan component untuk reusable elements
- Format: `kebab-case` untuk component name
- Selalu escape output: `{{ $variable }}`

### 4. Commit Messages
```
feat: add invoice exclusion bulk import
fix: correct aging bucket calculation
docs: update API documentation
refactor: extract audit trail logic
test: add exclusion controller tests
```

### 5. Testing

#### Run All Tests
```bash
php artisan test
```

#### Run Specific Test
```bash
php artisan test --filter=InvoiceExclusionTest
```

#### Test Coverage
```bash
./vendor/bin/phpunit --coverage-html=coverage
```

---

## Code Review Checklist

### Backend
- [ ] Form Request untuk validasi
- [ ] Eloquent query optimized (no N+1)
- [ ] Audit trail untuk operasi penting
- [ ] Exclusion filter diterapkan
- [ ] Response format konsisten
- [ ] Error handling proper

### Frontend
- [ ] Blade component reusable
- [ ] Alpine.js untuk interaktivitas
- [ ] Tailwind CSS untuk styling
- [ ] Dark mode support
- [ ] Mobile responsive
- [ ] Lucide icons konsisten

### Database
- [ ] Migration reversible
- [ ] Index untuk frequently queried columns
- [ ] Foreign key constraints
- [ ] Proper data types

---

## Pull Request Template

```markdown
## Description
[Penjelasan perubahan]

## Type
- [ ] Feature
- [ ] Bug Fix
- [ ] Documentation
- [ ] Refactor
- [ ] Test

## Related Issue
#[issue-number]

## Checklist
- [ ] Code tested locally
- [ ] Laravel Pint passed
- [ ] Documentation updated
- [ ] No sensitive data committed
```

---

## Directory Structure

```
ar-finance-tools/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   └── Traits/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── doc/
├── public/
├── resources/
│   ├── views/
│   │   ├── components/
│   │   ├── layouts/
│   │   └── pages/
│   └── css/
├── routes/
│   ├── api.php
│   └── web.php
└── tests/
```
