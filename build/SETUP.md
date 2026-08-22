# AR Finance Tools - Build Setup

## Quick Start

### 1. Create Fresh Laravel Project
```bash
composer create-project laravel/laravel ar-finance-tools "10.*"
cd ar-finance-tools
```

### 2. Copy Build Files
```bash
# Copy all files from build/ to your Laravel project
# Overwrite existing files when prompted
xcopy /E /Y build\* .
```

### 3. Install Dependencies
```bash
npm install
npm install -D tailwindcss@3.4 postcss autoprefixer
npm install alpinejs lucide
npx tailwindcss init -p
```

### 4. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_dmx
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Create Database
Open phpMyAdmin → Create database `absensi_dmx`

### 6. Run Migrations
```bash
php artisan migrate
php artisan db:seed
```

### 7. Start Development
```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

### 8. Login
- URL: http://localhost:8000/login
- Email: admin@dmx.co.id
- Password: password

## Default Users
| Name | Email | Role | Password |
|------|-------|------|----------|
| Admin | admin@dmx.co.id | admin | password |
| Manager | manager@dmx.co.id | finance_manager | password |
| Accountant | accountant@dmx.co.id | ar_accountant | password |
| Collector | collector@dmx.co.id | ar_collector | password |
| Sales | sales@dmx.co.id | sales_manager | password |
| Viewer | viewer@dmx.co.id | viewer | password |
