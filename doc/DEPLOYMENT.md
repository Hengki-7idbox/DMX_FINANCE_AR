# Deployment Guide

## Overview
Panduan deployment AR Finance Tools ke production environment.

---

## Prerequisites

| Component | Version |
|-----------|---------|
| PHP | ^8.1 |
| MySQL | 8.0+ |
| Node.js | ^18 |
| Composer | ^2.0 |
| Nginx / Apache | Latest |

---

## Deployment Steps

### 1. Server Setup
```bash
# Install PHP
sudo apt install php8.1 php8.1-fpm php8.1-mysql

# Install MySQL
sudo apt install mysql-server

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs
```

### 2. Clone & Configure
```bash
cd /var/www
git clone <repo-url> ar-finance-tools
cd ar-finance-tools

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Environment
cp .env.production .env
php artisan key:generate
```

### 3. Configure .env
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ar.yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=absensi_dmx
DB_USERNAME=ar_user
DB_PASSWORD=secure_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
```

### 4. Database Setup
```bash
mysql -u root -p
CREATE DATABASE absensi_dmx;
CREATE USER 'ar_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON absensi_dmx.* TO 'ar_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force
php artisan db:seed --force
```

### 5. Permissions
```bash
sudo chown -R www-data:www-data /var/www/ar-finance-tools
sudo chmod -R 755 /var/www/ar-finance-tools
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Nginx Configuration
```nginx
server {
    listen 80;
    server_name ar.yourdomain.com;
    root /var/www/ar-finance-tools/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 7. SSL (Let's Encrypt)
```bash
sudo certbot --nginx -d ar.yourdomain.com
```

### 8. Scheduled Tasks
```bash
# Add to crontab
* * * * * cd /var/www/ar-finance-tools && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Queue Worker (for reminders)
```bash
# Create systemd service
sudo nano /etc/systemd/system/ar-worker.service
```

```ini
[Unit]
Description=AR Finance Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/ar-finance-tools
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable ar-worker
sudo systemctl start ar-worker
```

---

## Post-Deployment Checklist
- [ ] SSL certificate active
- [ ] Database migrated
- [ ] Storage permissions correct
- [ ] Queue worker running
- [ ] Scheduled tasks active
- [ ] Email/WA API working
- [ ] Login test passed
- [ ] Audit log recording

---

## Rollback
```bash
git log --oneline -5  # Find previous commit
git checkout <commit-hash>
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate:rollback
```
