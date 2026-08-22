# Build Guide

## Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | ^8.1 | Backend runtime |
| Composer | ^2.0 | PHP dependency manager |
| Node.js | ^18.0 | Frontend build |
| npm | ^9.0 | JS dependency manager |
| MySQL | 8.0+ | Database |
| XAMPP | Latest | Local server (Apache + MySQL) |

## Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd ar-finance-tools
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install JS Dependencies
```bash
npm install
```

### 4. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure Database (.env)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_dmx
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run Migrations
```bash
php artisan migrate
```

### 7. Seed Database (Optional)
```bash
php artisan db:seed
```

## Development Commands

### Start Development Server
```bash
# Terminal 1: Laravel Backend
php artisan serve

# Terminal 2: Vite Frontend
npm run dev
```

### Build for Production
```bash
npm run build
```

### Laravel Sail (Docker)
```bash
./vendor/bin/sail up
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

## Code Quality

### Linting (Laravel Pint)
```bash
./vendor/bin/pint
```

### Type Checking
```bash
# If using PHPStan
./vendor/bin/phpstan analyse
```

### Testing
```bash
php artisan test
# or
./vendor/bin/phpunit
```

### Run Specific Test
```bash
php artisan test --filter=InvoiceExclusionTest
```

## Database Management

### Fresh Migration (Warning: destroys data)
```bash
php artisan migrate:fresh --seed
```

### Rollback Last Migration
```bash
php artisan migrate:rollback
```

### Create New Migration
```bash
php artisan make:migration create_table_name
```

### Tinker (REPL)
```bash
php artisan tinker
```

## Useful Shortcuts

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Generate Model + Migration + Controller
```bash
php artisan make:model ModelName -mcr
```

### Generate Form Request
```bash
php artisan make:request StoreExclusionRequest
```

## Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_DATABASE` | Database name | absensi_dmx |
| `MAIL_MAILER` | Email driver | smtp |
| `SANCTUM_STATEFUL_DOMAINS` | SPA domains | localhost:5173 |
| `SESSION_DRIVER` | Session storage | file |
