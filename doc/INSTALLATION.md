# Installation Guide

**Version:** 1.0  
**Date:** 22 September 2026

---

## System Requirements

### Minimum

```text
- PHP 8.1
- MySQL 8.0
- RAM: 2GB
- Storage: 1GB
```

### Recommended

```text
- PHP 8.2
- MySQL 8.0
- RAM: 4GB
- Storage: 5GB
```

## Installation Steps

### 1. Install XAMPP

```text
1. Download XAMPP from https://www.apachefriends.org
2. Install with default settings
3. Start Apache and MySQL services
```

### 2. Install Composer

```text
1. Download from https://getcomposer.org
2. Run installer
3. Follow installation wizard
4. Verify: composer --version
```

### 3. Install Node.js

```text
1. Download from https://nodejs.org
2. Install LTS version
3. Verify: node --version
4. Verify: npm --version
```

### 4. Clone Project

```bash
cd C:\xampp\htdocs
git clone https://github.com/finance-ar/finance-ar.git
cd finance-ar
```

### 5. Install Dependencies

```bash
composer install
npm install
```

### 6. Environment Setup

```bash
copy .env.example .env
php artisan key:generate
```

### 7. Database Setup

```text
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create database: finance_ar
3. Import SQL file if available
```

### 8. Configure .env

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_ar
DB_USERNAME=root
DB_PASSWORD=
```

### 9. Run Migrations

```bash
php artisan migrate
php artisan db:seed
```

### 10. Build Assets

```bash
npm run dev
```

### 11. Start Development Server

```bash
php artisan serve
```

### 12. Access Application

```text
URL: http://localhost:8000
```

## Post-Installation

### Verify Installation

```text
1. Check all pages load
2. Test login
3. Create test data
4. Run import test
```

### Initial Setup

```text
1. Create admin user
2. Setup roles & permissions
3. Configure settings
4. Setup email notifications
```

## Troubleshooting

### Common Issues

```text
1. Permission denied
   - chmod -R 775 storage
   - chmod -R 775 bootstrap/cache

2. Module not found
   - composer update
   - npm install

3. Database connection
   - Check MySQL is running
   - Verify credentials in .env

4. Application error
   - php artisan config:clear
   - php artisan cache:clear
   - php artisan view:clear
```
