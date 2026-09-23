# Deployment Guide

**Version:** 1.0  
**Date:** 22 September 2026

---

## Requirements

```text
- PHP ^8.1
- MySQL ^8.0
- Nginx / Apache
- Node.js ^18
- Composer
- SSL Certificate
```

## Server Setup

### 1. Install Dependencies

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.1 mysql-server nginx nodejs npm

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Clone Repository

```bash
cd /var/www
git clone https://github.com/finance-ar/finance-ar.git
cd finance-ar
```

### 3. Setup Application

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment

```bash
nano .env
```

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://finance-ar.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_ar
DB_USERNAME=finance_user
DB_PASSWORD=secure_password
```

### 5. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Permissions

```bash
sudo chown -R www-data:www-data /var/www/finance-ar
sudo chmod -R 755 /var/www/finance-ar
sudo chmod -R 775 storage bootstrap/cache
```

### 7. Nginx Configuration

```nginx
server {
    listen 80;
    server_name finance-ar.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name finance-ar.example.com;
    root /var/www/finance-ar/public;

    ssl_certificate /etc/ssl/finance-ar.crt;
    ssl_certificate_key /etc/ssl/finance-ar.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realroot$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 8. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Cron Job

```bash
# Edit crontab
crontab -e

# Add:
* * * * * cd /var/www/finance-ar && php artisan schedule:run >> /dev/null 2>&1
```

## Queue Worker

```bash
# Create service file
sudo nano /etc/systemd/system/finance-ar-worker.service

# Start worker
sudo systemctl start finance-ar-worker
sudo systemctl enable finance-ar-worker
```

## SSL Certificate

```bash
# Using Certbot
sudo certbot --nginx -d finance-ar.example.com
```

## Backup

```bash
# Database backup
mysqldump -u root finance_ar > backup_$(date +%Y%m%d).sql

# File backup
tar -czf storage_$(date +%Y%m%d).tar.gz storage/
```

## Rollback

```bash
# Rollback last migration
php artisan migrate:rollback

# Rollback to specific version
git checkout v1.0.0
composer install
php artisan migrate
```

## Monitoring

```text
- Check logs: storage/logs/laravel.log
- Monitor queue: php artisan queue:monitor
- Check disk space: df -h
- Check memory: free -m
```
