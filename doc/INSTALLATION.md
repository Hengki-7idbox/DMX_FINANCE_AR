# Installation Guide

## Overview
Panduan instalasi lengkap AR Finance Tools.

---

## System Requirements

### Minimum
| Component | Version |
|-----------|---------|
| PHP | ^8.1 |
| MySQL | 5.7+ |
| Composer | ^2.0 |
| Node.js | ^16 |
| npm | ^8 |

### Recommended
| Component | Version |
|-----------|---------|
| PHP | 8.2 |
| MySQL | 8.0 |
| Composer | 2.6 |
| Node.js | 18 LTS |
| npm | 9 |

### PHP Extensions
- php-mysql
- php-mbstring
- php-xml
- php-curl
- php-zip
- php-bcmath
- php-gd
- php-intl

---

## Installation Steps

### 1. Prerequisites (XAMPP)

#### Install XAMPP
1. Download XAMPP dari https://www.apachefriends.org
2. Install dengan Apache + MySQL
3. Start Apache dan MySQL

#### Configure PHP
Edit `php.ini`:
```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
extension=pdo_mysql
extension=mbstring
extension=xml
extension=curl
extension=zip
extension=bcmath
```

### 2. Clone Repository
```bash
cd C:\xampp\htdocs
git clone <repo-url> ar-finance-tools
cd ar-finance-tools
```

### 3. Install PHP Dependencies
```bash
composer install
```

### 4. Install JS Dependencies
```bash
npm install
```

### 5. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 6. Configure Database

#### Create Database
Open phpMyAdmin (http://localhost/phpmyadmin):
```sql
CREATE DATABASE absensi_dmx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Update .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_dmx
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Run Migrations
```bash
php artisan migrate
```

### 8. Seed Data (Optional)
```bash
php artisan db:seed
```

### 9. Storage Link
```bash
php artisan storage:link
```

### 10. Start Development Server

#### Terminal 1: Laravel
```bash
php artisan serve
```
Access: http://localhost:8000

#### Terminal 2: Vite
```bash
npm run dev
```
Access: http://localhost:5173

---

## Docker Installation (Laravel Sail)

### Prerequisites
- Docker Desktop installed

### Steps
```bash
# Clone repository
git clone <repo-url> ar-finance-tools
cd ar-finance-tools

# Install via Sail
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Generate key
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate

# Start Vite
./vendor/bin/sail npm run dev
```

Access: http://localhost

---

## Post-Installation

### Create Admin User
```bash
php artisan tinker
```
```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@dmx.co.id',
    'password' => bcrypt('password'),
    'role' => 'admin',
    'is_active' => true,
]);
```

### Verify Installation
1. Login: http://localhost:8000/login
2. Email: admin@dmx.co.id
3. Password: password

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| PHP version error | Install PHP 8.1+ via XAMPP |
| Missing extension | Enable in php.ini, restart Apache |
| Database connection | Check .env, ensure MySQL running |
| Permission denied | chmod -R 775 storage bootstrap/cache |
| Vite not working | Run `npm install && npm run dev` |
| CSRF token mismatch | Clear cache: `php artisan config:clear` |
