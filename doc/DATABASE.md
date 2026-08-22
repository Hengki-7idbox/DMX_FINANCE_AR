# Database Documentation

## Overview
Database **MySQL** untuk AR Finance Tools, nama database: `absensi_dmx`.

---

## Tables

### 1. users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','finance_manager','ar_accountant','ar_collector','sales_manager','viewer') DEFAULT 'viewer',
    region VARCHAR(50) NULL,
    phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;
```

### 2. customers
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    sales_rep_id BIGINT UNSIGNED NULL,
    region VARCHAR(50) NULL,
    credit_limit DECIMAL(15,2) DEFAULT 0,
    payment_terms INT DEFAULT 30,
    risk_profile ENUM('safe','caution','risk','blocked') DEFAULT 'safe',
    contact_preference ENUM('email','whatsapp','both','none') DEFAULT 'both',
    status ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_sales_rep (sales_rep_id),
    FOREIGN KEY (sales_rep_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

### 3. invoices
```sql
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IDR',
    status ENUM('PENDING','SENT','PARTIAL','SETTLED') DEFAULT 'PENDING',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
```

### 4. payments
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    reference_number VARCHAR(100) NULL,
    bank_name VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_reference (reference_number),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
```

### 5. invoice_exclusions (FOUNDATION)
```sql
CREATE TABLE invoice_exclusions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    customer_name VARCHAR(100) NULL,
    original_amount DECIMAL(15,2) NULL,
    reason_code ENUM('DUPLICATE','GL_ERROR','TEST_INVOICE','DATA_ENTRY_ERROR','WORKFLOW_ERROR','OTHER') NOT NULL,
    reason_detail TEXT NULL,
    exclude_related_payment BOOLEAN DEFAULT TRUE,
    excluded_by VARCHAR(50) NOT NULL,
    excluded_date DATETIME NOT NULL,
    status ENUM('ACTIVE','REVERTED') DEFAULT 'ACTIVE',
    revert_by VARCHAR(50) NULL,
    revert_date DATETIME NULL,
    revert_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status),
    INDEX idx_reason_code (reason_code),
    INDEX idx_excluded_date (excluded_date)
) ENGINE=InnoDB;
```

### 6. collection_actions
```sql
CREATE TABLE collection_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('EMAIL','WA','CALL','NOTE','STATUS_CHANGE') NOT NULL,
    description TEXT NULL,
    action_by VARCHAR(50) NULL,
    action_date DATETIME NOT NULL,
    status VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_action_date (action_date),
    INDEX idx_action_type (action_type),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
```

### 7. credit_limits
```sql
CREATE TABLE credit_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    credit_limit DECIMAL(15,2) NOT NULL,
    effective_date DATE NOT NULL,
    expiry_date DATE NULL,
    approved_by VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
```

### 8. ar_reconciliation_log
```sql
CREATE TABLE ar_reconciliation_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recon_date DATE NOT NULL,
    gl_balance DECIMAL(15,2) NOT NULL,
    payment_balance DECIMAL(15,2) NOT NULL,
    difference DECIMAL(15,2) NOT NULL,
    accuracy_pct DECIMAL(5,2) NOT NULL,
    matched_count INT DEFAULT 0,
    unmatched_count INT DEFAULT 0,
    performed_by VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recon_date (recon_date)
) ENGINE=InnoDB;
```

### 9. audit_log
```sql
CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT UNSIGNED NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id),
    INDEX idx_table_record (table_name, record_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

### 10. email_templates
```sql
CREATE TABLE email_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### 11. wa_templates
```sql
CREATE TABLE wa_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    body TEXT NOT NULL,
    variables JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### 12. scheduled_tasks
```sql
CREATE TABLE scheduled_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(100) NOT NULL,
    task_type VARCHAR(50) NOT NULL,
    schedule VARCHAR(100) NOT NULL,
    config JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

---

## Relationships

```
users ──1:N──> customers (sales_rep_id)
customers ──1:N──> invoices
customers ──1:N──> credit_limits
invoices ──1:N──> payments
invoices ──1:N──> collection_actions
invoices ──1:1──> invoice_exclusions (by invoice_number)
```
