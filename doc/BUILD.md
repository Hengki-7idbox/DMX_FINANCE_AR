# Build Guide

**Version:** 1.0  
**Date:** 22 September 2026

---

## Prerequisites

```text
- PHP ^8.1
- Composer
- Node.js ^18
- MySQL ^8.0
- XAMPP
```

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/finance-ar/finance-ar.git
cd finance-ar
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 6. Build Assets

```bash
npm run build
```

### 7. Run Development Server

```bash
php artisan serve
```

## Development Commands

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=InvoiceTest

# Code formatting
./vendor/bin/pint

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Build for Production

```bash
# Optimize
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm run build
```

## Database Backup

```bash
# Export
mysqldump -u root finance_ar > backup.sql

# Import
mysql -u root finance_ar < backup.sql
```

## Troubleshooting

```text
Permission denied: chmod -R 775 storage bootstrap/cache
Module not found: composer update
Clear all caches: php artisan optimize:clear
```
