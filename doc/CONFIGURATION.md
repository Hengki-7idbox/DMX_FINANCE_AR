# Configuration

## Overview
Semua konfigurasi AR Finance Tools.

---

## Environment Variables (.env)

### Application
```env
APP_NAME="AR Finance Tools"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_dmx
DB_USERNAME=root
DB_PASSWORD=
```

### Sanctum
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000
```

### Mail
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@dmx.co.id
MAIL_FROM_NAME="DMX Finance"
```

### WhatsApp API
```env
WA_API_URL=https://api.whatsapp.com/v1
WA_API_KEY=your-api-key
WA_PHONE_ID=your-phone-id
```

### Accurate 5 Integration
```env
ACCURATE_API_URL=https://accurate.api.url
ACCURATE_API_KEY=your-accurate-key
ACCURATE_COMPANY_ID=your-company-id
```

---

## App Config (config/ar_finance.php)

```php
return [
    // Aging Buckets
    'aging_buckets' => [
        'current'   => ['min' => 0,  'max' => 30,  'color' => 'green',  'label' => 'Current'],
        'overdue_1' => ['min' => 31, 'max' => 60,  'color' => 'yellow', 'label' => '31-60 Days'],
        'overdue_2' => ['min' => 61, 'max' => 90,  'color' => 'orange', 'label' => '61-90 Days'],
        'overdue_3' => ['min' => 91, 'max' => null, 'color' => 'red',   'label' => '90+ Days'],
    ],

    // Exclusion Reasons
    'exclusion_reasons' => [
        'DUPLICATE'       => 'Duplicate',
        'GL_ERROR'        => 'GL Error',
        'TEST_INVOICE'    => 'Test Invoice',
        'DATA_ENTRY_ERROR'=> 'Data Entry Error',
        'WORKFLOW_ERROR'  => 'Workflow Error',
        'OTHER'           => 'Other',
    ],

    // Collection Escalation
    'escalation_rules' => [
        1  => ['action' => 'email',           'label' => 'Email Reminder'],
        5  => ['action' => 'email_and_wa',     'label' => 'Email + WhatsApp'],
        15 => ['action' => 'escalate',         'label' => 'Escalate'],
        30 => ['action' => 'manager_alert',    'label' => 'Manager Alert'],
    ],

    // Credit Limit Thresholds
    'credit_thresholds' => [
        'green'  => ['min' => 0,   'max' => 75,  'label' => 'Safe'],
        'yellow' => ['min' => 75,  'max' => 95,  'label' => 'Caution'],
        'red'    => ['min' => 95,  'max' => 100, 'label' => 'At Limit'],
        'blocked'=> ['min' => 100, 'max' => null, 'label' => 'Over Limit'],
    ],

    // Reconciliation
    'reconciliation' => [
        'amount_tolerance'  => 0.01,  // 1%
        'date_tolerance'    => 2,     // days
        'auto_match_limit'  => 1000,
        'alert_threshold'   => 0.05,  // 5% mismatch
    ],

    // Pagination
    'per_page' => 15,

    // Reminder
    'reminder' => [
        'send_time'         => '09:00',  // WIB
        'cooldown_days'     => 7,
        'max_retry'         => 3,
        'retry_backoff'     => [3600, 14400, 86400],  // seconds
    ],
];
```

---

## Roles (config/roles.php)

```php
return [
    'admin'            => ['label' => 'Admin',            'level' => 100],
    'finance_manager'  => ['label' => 'Finance Manager',  'level' => 80],
    'ar_accountant'    => ['label' => 'AR Accountant',    'level' => 60],
    'ar_collector'     => ['label' => 'AR Collector',     'level' => 40],
    'sales_manager'    => ['label' => 'Sales Manager',    'level' => 50],
    'viewer'           => ['label' => 'Viewer',           'level' => 20],
];
```
