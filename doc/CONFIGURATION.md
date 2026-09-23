# Configuration

**Version:** 1.0  
**Date:** 22 September 2026

---

## Application Config

```text
APP_NAME="Finance AR"
APP_ENV=local
APP_KEY=base64:xxxxx
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## Database Config

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_ar
DB_USERNAME=root
DB_PASSWORD=
```

## Mail Config

```text
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

## WhatsApp Config (OpenWA)

```text
WHATSAPP_API_URL=http://localhost:3000
WHATSAPP_API_KEY=your-api-key
WHATSAPP_ENABLED=true
```

## Aging Config

```text
AGING_CATEGORIES=CURRENT,1-30,31-60,61-90,91-120,>120
AGING_DEFAULT_DATE=today
```

## Import Config

```text
IMPORT_MAX_FILE_SIZE=10MB
IMPORT_ALLOWED_EXTENSIONS=xlsx,xls,csv
IMPORT_BATCH_SIZE=1000
```

## Collection Config

```text
COLLECTION_OVERDUE_DAYS=7
COLLECTION_REMINDER_DAYS=3
COLLECTION_AUTO_ASSIGN=true
```

## Notification Config

```text
NOTIFICATION_EMAIL=true
NOTIFICATION_WHATSAPP=true
NOTIFICATION_OVERDUE=true
NOTIFICATION_PROMISE_DUE=true
```

## Role & Permission

### Roles

```text
super_admin
finance_ar
collector
manager
viewer
auditor
```

### Permissions

```text
customer.view
customer.create
customer.update
customer.delete

invoice.view
invoice.create
invoice.update
invoice.delete
invoice.import

payment.view
payment.create
payment.allocate
payment.reconcile

bank.view
bank.import
bank.reconcile

collection.view
collection.create
collection.send

report.view
report.export
```
