# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## AR Finance Tools System v1.0
### PT. DMX Trading Indonesia

**Document Version**: 1.0  
**Last Updated**: August 2026  
**Author**: Hengki Setia Putra  
**Status**: In Development

---

## TABLE OF CONTENTS
1. [Executive Summary](#executive-summary)
2. [Problem Statement](#problem-statement)
3. [Solution Overview](#solution-overview)
4. [Target Users](#target-users)
5. [System Architecture](#system-architecture)
6. [Detailed Feature Specifications](#detailed-feature-specifications)
7. [Technical Requirements](#technical-requirements)
8. [Implementation Roadmap](#implementation-roadmap)
9. [Success Metrics](#success-metrics)

---

## EXECUTIVE SUMMARY

**Project Name**: AR Finance Tools System  
**Organization**: PT. DMX Trading Indonesia  
**Objective**: Build integrated Accounts Receivable (AR) management platform to improve invoice tracking, automate collections, and ensure financial accuracy.

**Key Outcomes**:
- Automate invoice aging and collection reminders
- Ensure AR data accuracy through invoice exclusion mechanism
- Reduce DSO (Days Sales Outstanding) by 20% within 6 months
- Real-time visibility into customer credit utilization
- Comprehensive audit trail for compliance

**Business Impact**:
- Improved cash flow visibility
- Faster collection cycle
- Reduced bad debt risk
- Better customer relationship management
- Compliance-ready audit logs

---

## PROBLEM STATEMENT

### Current Pain Points:

1. **Data Accuracy Issues**
   - Accurate 5 contains many inaccurate/duplicate invoices
   - Manual identification of bad data is time-consuming
   - No centralized mechanism to exclude bad invoices from calculations

2. **Manual Collection Process**
   - Collection reminders sent manually (email/WA)
   - No automated tracking of overdue invoices
   - Inconsistent follow-up schedule
   - High risk of missing follow-ups

3. **Poor Visibility**
   - No real-time AR status overview
   - Difficult to identify overdue invoices quickly
   - Limited customer payment behavior insights
   - No automated alerts for credit limit breaches

4. **Reconciliation Challenges**
   - AR reconciliation is manual, error-prone
   - Difficult to match invoices with payments
   - No automated anomaly detection
   - Time-consuming month-end close process

5. **Risk Management**
   - No real-time credit limit monitoring
   - Difficult to assess customer payment risk
   - Limited historical payment analysis
   - No predictive insights for collection strategy

---

## SOLUTION OVERVIEW

### 8 Integrated AR Finance Tools:

```
┌─────────────────────────────────────────────────────────────────┐
│                  CENTRAL INVOICE EXCLUSION                      │
│           (Master list - foundation for all tools)              │
└─────────────────────────────────────────────────────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
   ┌─────────┐          ┌─────────────┐          ┌──────────┐
   │ Aging   │          │ AR Recon    │          │ Email/WA │
   │ Report  │          │ Tool        │          │ Reminder │
   └─────────┘          └─────────────┘          └──────────┘
        │                       │                       │
        └───────────────────────┼───────────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
   ┌──────────┐          ┌──────────┐          ┌─────────────┐
   │ Credit   │          │Collection│          │  Customer   │
   │ Monitor  │          │ Tracker  │          │  Analyzer   │
   └──────────┘          └──────────┘          └─────────────┘
        │                       │                       │
        └───────────────────────┼───────────────────────┘
                                │
                                ▼
                        ┌──────────────────┐
                        │    DASHBOARD     │
                        │  (Unified View)  │
                        └──────────────────┘
```

---

## TARGET USERS

| User Role | Responsibilities | Key Features Used |
|-----------|-----------------|-------------------|
| **Finance Manager** | Oversee AR, reconciliation, policy | Dashboard, Aging Report, Analyzer, Exclusion |
| **AR Accountant** | Daily AR tasks, reconciliation, exclusions | Aging, Reconciliation, Exclusion, Tracker |
| **AR Collector** | Follow up overdue, send reminders | Tracker, Email/WA, Analyzer, Dashboard |
| **Sales Manager** | Monitor customer credit, approve SO | Credit Monitor, Analyzer |
| **CFO** | Strategic AR insights, forecasting | Dashboard, Analyzer |
| **System Admin** | User management, integration, backup | All tools + admin panel |

---

## SYSTEM ARCHITECTURE

### Technology Stack:

```
BACKEND:
├─ Laravel 10 (PHP ^8.1) — framework utama
├─ MySQL — database (absensi_dmx)
├─ XAMPP — local development server
├─ Laravel Sanctum — autentikasi API
├─ maatwebsite/excel — import/export Excel
└─ Laravel Tinker — REPL CLI

FRONTEND:
├─ Blade — templating (layout: admin, viewer, mobile, print)
├─ Tailwind CSS 3.4 — styling (dark mode via class "dark")
├─ Alpine.js 3.15 — interaktivitas (picker shift, toast, dropdown, mode gelap)
├─ Vite 5 — bundler aset
├─ Lucide — ikon
└─ jQuery + DataTables — tabel di beberapa halaman (dashboard/laporan)

DEV / TESTING:
├─ PHPUnit — unit testing
├─ Laravel Pint — linter
├─ Laravel Sail — Docker-based dev environment
├─ Faker — dummy data generation
└─ Ignition — debug & error handling

TOOLS PENDUKUNG:
├─ Obsidian — Memori Project
├─ Gitnexus — Memori AI
└─ Graphify — Knowledge Graph

INTEGRATIONS:
├─ Accurate 5 (XML import/export)
├─ Twilio / WhatsApp Business API (WA)
├─ SMTP (Email)
└─ Bank API (Bank statement import)

DEPLOYMENT:
├─ Laravel Sail / Docker (Containerization)
├─ XAMPP / Apache (Reverse proxy)
└─ Linux VPS / Cloud hosting
```

### Database Schema Overview:

```sql
-- Core Tables
customers
invoices
payments
invoice_exclusions         ← FOUNDATION
collection_actions
credit_limits
ar_reconciliation_log

-- Supporting Tables
users
roles
audit_log
email_templates
wa_templates
scheduled_tasks
```

---

## DETAILED FEATURE SPECIFICATIONS

### TOOL 1: INVOICE EXCLUSION / BLACKLIST MASTER

**Purpose**: Centralized master list of invalid/inaccurate invoices to exclude from all calculations.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **Add Exclusion** | Input invoice number → auto-fetch customer & amount → select reason → save |
| **Exclusion Reasons** | Duplicate, GL Error, Test Invoice, Data Entry Error, Workflow Error, Other |
| **Bulk Import** | CSV upload: invoice_number, reason, note (batch add up to 500 at once) |
| **Revert Exclusion** | Un-exclude invoice, log reason & who reverted |
| **Exclusion List** | Table view: invoice no, customer, amount, reason, excluded by, date |
| **Search & Filter** | By invoice no, customer, reason, date range, user, status |
| **Audit Trail** | Complete history: ADD, REVERT actions with timestamp & user |
| **Cascade Options** | Exclude: invoice only / + related payments / + SO + payments |
| **Statistics** | Total excluded count, total amount, breakdown by reason & user |
| **Exclude Related Payments** | Toggle: if enabled, related payment also marked as excluded |
| **Version History** | Track all changes (revert = new version, not delete) |

**Database**:
```sql
CREATE TABLE invoice_exclusions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  invoice_number VARCHAR(50) UNIQUE NOT NULL,
  customer_name VARCHAR(100),
  original_amount DECIMAL(15,2),
  reason_code VARCHAR(50) NOT NULL,
  reason_detail TEXT,
  exclude_related_payment BOOLEAN DEFAULT TRUE,
  excluded_by VARCHAR(50) NOT NULL,
  excluded_date DATETIME NOT NULL,
  status ENUM('ACTIVE', 'REVERTED') DEFAULT 'ACTIVE',
  revert_by VARCHAR(50),
  revert_date DATETIME,
  revert_reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes TEXT,
  
  INDEX idx_invoice_number (invoice_number),
  INDEX idx_status (status),
  INDEX idx_excluded_date (excluded_date)
);
```

**API Endpoints**:
- `POST /api/exclusions` - Add exclusion
- `GET /api/exclusions` - List with filters
- `PUT /api/exclusions/{id}/revert` - Revert exclusion
- `POST /api/exclusions/bulk-import` - Bulk import CSV
- `GET /api/exclusions/statistics` - Get stats
- `DELETE /api/exclusions/{id}` - Delete (soft delete)

---

### TOOL 2: AGING REPORT GENERATOR

**Purpose**: Categorize outstanding invoices by age (days overdue from due date).

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **Standard Aging Buckets** | 0-30, 31-60, 61-90, 90+ days (auto-calculate from due date) |
| **Custom Buckets** | User can define custom ranges (e.g., 0-15, 16-45, 46-90, 90+) |
| **Auto Exclude** | Automatically filter out invoices from exclusion master list |
| **Summary Statistics** | Total AR, total overdue, avg days late, count by bucket |
| **Detail by Customer** | Drill-down: click bucket → list all invoices + customers in that bucket |
| **Filter Options** | By date range, customer, region, product category, sales rep, payment status |
| **Color Coding** | 🟢 Current, 🟡 30-60, 🟠 60-90, 🔴 90+ (visual urgency indicator) |
| **Trend Analysis** | Month-over-month aging movement (table + chart) |
| **Pivot Options** | Aging × Customer, Aging × Region, Aging × Sales Rep, Aging × Category |
| **Export Formats** | Excel (with pivot table), PDF report, CSV data |
| **Email Schedule** | Auto-send report daily/weekly/monthly to stakeholders |
| **Top Customers** | List top 10 customers by overdue amount |
| **Comparison** | This month vs last month vs same period last year |

**Output Example**:
```
┌──────────────────────────────────────────────────────────────┐
│ AGING REPORT - PT. DMX Trading Indonesia                     │
│ As of: 2026-08-22                                            │
├──────────────────────────────────────────────────────────────┤
│ Aging Bucket    │ Count │ Amount (Rp)   │ % of Total        │
├──────────────────────────────────────────────────────────────┤
│ Current (0-30)  │ 45    │ 2,500,000,000 │ 35%  🟢           │
│ 31-60 days      │ 23    │ 1,800,000,000 │ 25%  🟡           │
│ 61-90 days      │ 15    │ 1,200,000,000 │ 17%  🟠           │
│ 90+ days        │ 12    │ 1,500,000,000 │ 23%  🔴           │
├──────────────────────────────────────────────────────────────┤
│ TOTAL           │ 95    │ 7,000,000,000 │ 100%              │
│ Less: Excluded  │ (25)  │ (2,500,000,000)│ (-36%)           │
│ VALID AR        │ 70    │ 4,500,000,000 │ 64%               │
└──────────────────────────────────────────────────────────────┘
```

**API Endpoints**:
- `GET /api/aging-report` - Generate aging report (with filters)
- `GET /api/aging-report/buckets` - Get pre-configured buckets
- `POST /api/aging-report/custom-buckets` - Save custom bucket config
- `GET /api/aging-report/export?format=excel|pdf|csv` - Export report
- `GET /api/aging-report/trend` - Get month-over-month trend data

---

### TOOL 3: AR RECONCILIATION TOOL

**Purpose**: Match invoices (GL) with payments, identify discrepancies.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **GL Balance Query** | Auto-fetch AR balance from GL (customizable GL account codes) |
| **Payment Import** | Upload bank statement (CSV/Excel) or manual entry |
| **Auto Matching** | Match by: amount (±1%), date (±2 days), reference number |
| **Fuzzy Matching** | Handle slight variations in invoice number/reference format |
| **Unmatched Items** | List: invoice without payment, payment without invoice, partial payment |
| **Anomaly Detection** | Flag: overpayment, underpayment, duplicate payment, suspicion of typo |
| **Exclude Filter** | Automatically skip excluded invoices in recon |
| **Summary Report** | GL total, payments total, matched, unmatched, difference, variance % |
| **Manual Matching** | UI to manually match problematic items |
| **Journal Entry Gen** | Auto-generate adjustment JE template (for manual posting in GL) |
| **Recon History** | Track all reconciliation attempts, dates, status, who performed |
| **Accuracy Score** | % invoices matched, % amount reconciled, quality metrics |
| **Export** | Excel (GL + payments + matched + unmatched), PDF summary |
| **Email Alert** | Notify if unmatched amount exceeds threshold |
| **GL Account Validation** | Validate GL account codes exist in master |

**Matching Rules**:
```
Priority 1 (Exact Match):
  - Invoice number matches payment reference
  - Amount matches exactly
  - Date within 2 days

Priority 2 (Fuzzy Match):
  - Amount matches within ±1%
  - Date within ±2 days
  - Invoice number similar (Levenshtein distance < 2)

Priority 3 (Manual Review):
  - Unmatched after priority 1 & 2
  - Flag for user manual matching
```

**Output Example**:
```
┌──────────────────────────────────────────────────────────────┐
│ AR RECONCILIATION REPORT                                     │
│ Period: August 2026                                          │
├──────────────────────────────────────────────────────────────┤
│ GL AR Balance:                      Rp 7,000,000,000        │
│ Less: Excluded Invoices:           (Rp 2,500,000,000)       │
│ Adjusted GL Balance:                Rp 4,500,000,000        │
│                                                              │
│ Bank Statement Total Payments:      Rp 3,200,000,000        │
│ Less: Excluded Payments:            (Rp 500,000,000)        │
│ Adjusted Payment Balance:           Rp 2,700,000,000        │
│                                                              │
│ Outstanding (Reconciled):           Rp 1,800,000,000        │
│ Difference:                         Rp 0 ✓ (BALANCED)       │
│ Accuracy:                           100% ✓                  │
└──────────────────────────────────────────────────────────────┘

UNMATCHED ITEMS:
─────────────────────────────────────────────────────────────
1. Payment Rp 750,000,000 on 2026-08-01 → No matching invoice
2. Invoice INV-2026-045 (Rp 300M) → No payment found yet
3. Invoice INV-2026-050 (Rp 500M) → Partial payment Rp 200M
```

**API Endpoints**:
- `POST /api/reconciliation/import-bank-statement` - Upload bank data
- `GET /api/reconciliation/gl-balance` - Fetch GL balance
- `POST /api/reconciliation/match` - Run auto-matching algorithm
- `GET /api/reconciliation/unmatched-items` - List unmatched
- `POST /api/reconciliation/manual-match` - Manual match items
- `GET /api/reconciliation/report` - Generate recon report
- `GET /api/reconciliation/export?format=excel|pdf` - Export

---

### TOOL 4: AUTO EMAIL & WHATSAPP REMINDERS

**Purpose**: Automatically send collection reminders via email and WhatsApp.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **Scheduled Job** | Daily automated check (customizable time, e.g., 09:00 WIB) |
| **Exclude Filter** | Skip excluded invoices automatically |
| **Overdue Threshold** | Customizable reminder triggers (day 1, 5, 15, 30 overdue) |
| **Email Templates** | Personalized: customer name, invoice no, amount, due date, days overdue |
| **WA Templates** | Concise format: invoice no, amount, overdue days, payment request |
| **Escalation Rules** | Day 1→Email only, Day 5→Email+WA, Day 15→Email+WA+Escalate, Day 30→Manager alert |
| **Smart Scheduling** | Don't send if: already paid today, already reminded this week, blacklisted |
| **Contact Preference** | Per customer: email only, WA only, or both |
| **Batch Send** | UI to manually select & send batch reminders to multiple customers |
| **Send Log** | Track: invoice no, customer, template, status, timestamp, error (if any) |
| **Retry Logic** | If send fails → queue for retry (exponential backoff: 1h, 4h, 24h) |
| **Email Attachment** | Option: attach invoice copy, payment instruction sheet |
| **Whitelist/Blacklist** | Per customer: "stop reminder", "VIP (less frequent)", "escalated" |
| **Template Variables** | {{customer_name}}, {{invoice_no}}, {{amount}}, {{due_date}}, {{days_overdue}} |
| **Integration** | SMTP for email, Twilio/WhatsApp Business API for WA |
| **Compliance Log** | Full audit trail (date, recipient, content, status) for regulation |
| **Preview** | Show draft email/WA before send (for manual batches) |
| **A/B Testing** | Test different template versions to measure open/response rate |
| **Do-Not-Call List** | Manage customer preferences (GDPR/local compliance) |

**Email Template Example**:
```
Subject: Reminder - Invoice INV-2026-001 Sudah Jatuh Tempo

Dear PT ABC Distributor,

Kami ingin mengingatkan bahwa invoice berikut masih outstanding:

Invoice No: INV-2026-001
Amount: Rp 500,000,000
Due Date: 2026-07-15
Days Overdue: 38 hari

Mohon segera lakukan pembayaran. Jika sudah dilakukan, 
harap abaikan email ini.

Payment Instructions:
Bank: BCA
Account: 1234567890 (PT DMX Trading)
Amount: Rp 500,000,000

Terima kasih atas perhatiannya.

Regards,
DMX Finance Team
```

**WhatsApp Template Example**:
```
Hi PT ABC Distributor,
Tagihan INV-2026-001 (Rp 500M) sudah overdue 38 hari. 
Mohon pembayaran segera ke rek BCA 1234567890.
Terima kasih.
```

**API Endpoints**:
- `POST /api/reminders/send-batch` - Send batch reminders
- `GET /api/reminders/scheduled-jobs` - View scheduled job status
- `PUT /api/reminders/config` - Update escalation rules, thresholds
- `GET /api/reminders/send-log` - View send history
- `GET /api/reminders/templates` - List available templates
- `POST /api/reminders/custom-template` - Create custom template
- `PUT /api/reminders/customer-preference/{customer_id}` - Set contact preference

---

### TOOL 5: CUSTOMER CREDIT LIMIT MONITOR

**Purpose**: Real-time tracking of customer credit utilization and alert when approaching limits.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **Credit Limit Setup** | Per customer: configure limit amount, payment terms, risk profile |
| **Current Utilization** | Outstanding AR (from valid invoices) + pending SO not yet invoiced |
| **Real-time Dashboard** | Status color: 🟢 Green (0-75%), 🟡 Yellow (75-95%), 🔴 Red (95%+) |
| **Alert Thresholds** | Email/WA notification when 75%, 85%, 95% utilization reached |
| **SO Integration** | Query pending SO from sales system to include in utilization |
| **SO Block Logic** | API endpoint: block new SO if would exceed credit limit |
| **Require Approval** | New SO requires manager approval if would exceed limit |
| **Historical Trend** | Chart: credit utilization over time (monthly, with forecast) |
| **Customer List** | Sortable table: name, limit, current util %, status, last activity |
| **Drill-down Detail** | Click customer → see: outstanding invoices, pending SO, payment history |
| **Exclude Filter** | Excluded invoices NOT counted in utilization |
| **Custom Limits** | Ability to adjust limit per customer (with approval + audit log) |
| **Risk Profile** | Auto-tag based on payment history: safe, caution, risk, blocked |
| **Approval Workflow** | If SO exceeds → notify manager, require approval, log decision |
| **Bulk Action** | Update credit limits for multiple customers at once |
| **Export** | Excel: all customers with utilization status, risk profile |
| **Forecast** | Based on payment trend, predict utilization 30/60/90 days out |
| **Predictive Alert** | Warn if customer trending toward limit breach (preventive) |

**Status Indicators**:
```
🟢 GREEN (0-75%):    Safe - Proceed with all sales
🟡 YELLOW (75-95%):  Caution - Require manager approval for new SO
🔴 RED (95-100%):    At Limit - No new SO, focus on collection
⚫ BLOCKED (>100%):   Over Limit - Stop sales, escalate collection
```

**Output Example**:
```
┌──────────────────────────────────────────────────────────────┐
│ CUSTOMER CREDIT LIMIT MONITOR                                │
│ PT ABC Distributor                                           │
├──────────────────────────────────────────────────────────────┤
│ Credit Limit:               Rp 2,000,000,000                │
│                                                              │
│ Current Utilization:                                         │
│ • Outstanding AR:           Rp 1,500,000,000 (75%)          │
│ • Pending SO (not invoiced):Rp 300,000,000  (15%)           │
│ • Total Exposure:           Rp 1,800,000,000 (90%) 🟡      │
│ • Available Credit:         Rp 200,000,000  (10%)           │
│                                                              │
│ Status: 🟡 YELLOW - Approaching limit                      │
│                                                              │
│ Action Required:                                             │
│ ⚡ Any new SO > Rp 200M requires manager approval           │
│ ⚡ Recommend priority collection follow-up                  │
│ ⚡ Consider offering early payment discount                 │
│                                                              │
│ 30-Day Forecast: Rp 1,900,000,000 (95%) → At risk!        │
└──────────────────────────────────────────────────────────────┘
```

**API Endpoints**:
- `GET /api/credit-limit/monitor` - Get all customers with utilization
- `GET /api/credit-limit/{customer_id}` - Get specific customer detail
- `PUT /api/credit-limit/{customer_id}` - Update credit limit
- `POST /api/credit-limit/check-so-approval` - Check if SO approval needed (for sales system)
- `POST /api/credit-limit/so-block` - Block SO if would exceed limit (for sales system)
- `GET /api/credit-limit/forecast/{customer_id}?days=30|60|90` - Forecast utilization
- `GET /api/credit-limit/alerts` - Get all active alerts
- `POST /api/credit-limit/bulk-update` - Update multiple customers

---

### TOOL 6: DASHBOARD (UNIFIED AR OVERVIEW)

**Purpose**: Single pane of glass for all AR metrics and key indicators.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **KPI Cards** | Top row: Total AR, Overdue Amount, Collection Rate %, DSO (Days Sales Outstanding) |
| **Trend Indicators** | Each KPI shows: change MoM, YoY comparison, trend direction (↑ ↓) |
| **Aging Distribution Chart** | Pie/Donut chart: 0-30, 31-60, 61-90, 90+ buckets (click to drill-down) |
| **Top Overdue Customers** | Horizontal bar chart: top 10 customers by overdue amount + customer name |
| **Collection Status Breakdown** | Pie chart: Pending, Partial, Overdue, Settled (counts + amounts + %) |
| **Daily Activity Feed** | Table: recent 10 activities (payment received, reminder sent, invoice posted, etc) |
| **Credit Limit Heat Map** | Color-coded grid: all customers scored by utilization % (visual risk scan) |
| **Excluded Invoices Summary** | Count + total amount excluded from calculations (transparency) |
| **Date Range Filter** | Quick buttons: Today, This Month, This Quarter, YTD, Custom date range |
| **Customer Filter** | Dropdown: all customers, or multi-select specific customers |
| **Segment Filter** | By region, sales rep, product category, payment terms |
| **Payment Terms Filter** | By net 30, net 60, net 90, COD, etc. |
| **Export Options** | PDF report, Excel data export, email scheduled report |
| **Drill-down** | Click any chart/metric → detail view (aging report, customer list, invoice detail) |
| **Mobile Responsive** | Dashboard works on mobile/tablet (simplified view) |
| **Auto-refresh** | Refresh every 5-10 minutes (configurable), manual refresh button |
| **User Preferences** | Save custom dashboard layout, favorite filters, widget positions per user |
| **Comparison Mode** | Side-by-side: this month vs last month vs budget vs forecast |
| **Alerts & Notifications** | Banner: critical alerts (high overdue, credit limit breach, recon issue) |
| **Quick Actions** | Buttons: View Aging Report, Send Reminders, View Tracker, Reconcile |
| **Performance Metrics** | Display: collection efficiency, DSO trend, write-off rate, etc. |

**Dashboard Layout**:
```
┌─────────────────────────────────────────────────────────────────┐
│ AR FINANCE DASHBOARD - PT. DMX Trading Indonesia                │
├─────────────────────────────────────────────────────────────────┤
│ [Date Range: This Month ▼] [Customer: All ▼] [Region: All ▼]  │
│ [Refresh] [Export] [Settings]                                   │
├─────────────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐              │
│ │Total AR │ │Overdue  │ │Collect% │ │DSO      │              │
│ │Rp 7.0B  │ │Rp 2.8B  │ │87%      │ │45 days  │              │
│ │↓ 5% MoM │ │↑ 8% MoM │ │↓ 2% YoY │ │↑ 3 days │              │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘              │
├─────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────┐  ┌──────────────────────────────────┐ │
│ │ Aging Distribution   │  │ Top 10 Overdue Customers         │ │
│ │ (Pie)                │  │ (Bar Chart)                      │ │
│ │ 0-30:  35%           │  │ PT ABC:        Rp 500M (50 days) │ │
│ │ 31-60: 25%           │  │ CV DEF:        Rp 400M (45 days) │ │
│ │ 61-90: 17%           │  │ PT GHI:        Rp 350M (30 days) │ │
│ │ 90+:   23%           │  │ ...                              │ │
│ └──────────────────────┘  └──────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────┐  ┌──────────────────────────────────┐ │
│ │ Collection Status    │  │ Credit Limit Heat Map            │ │
│ │ (Pie)                │  │ (Color grid by utilization)      │ │
│ │ Pending: 35%         │  │ 🟢 Safe    🟡 Caution  🔴 Alert │ │
│ │ Partial: 25%         │  │ ...                              │ │
│ │ Overdue: 30%         │  │                                  │ │
│ │ Settled: 10%         │  │                                  │ │
│ └──────────────────────┘  └──────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────────┤
│ Recent Activity:                                                │
│ • INV-2026-095 payment received (Rp 500M)         - 1 hour ago  │
│ • 25 collection reminders sent                    - 2 hours ago │
│ • AR Reconciliation: 100% balanced                - Today 08:00 │
│ • Credit limit alert: PT XYZ at 92% utilization  - Today 09:15 │
├─────────────────────────────────────────────────────────────────┤
│ [Quick Actions: View Aging ▾] [Send Reminder ▾] [Reconcile ▾]  │
└─────────────────────────────────────────────────────────────────┘
```

**API Endpoints**:
- `GET /api/dashboard/kpis` - Get KPI data
- `GET /api/dashboard/charts` - Get all chart data (with filters)
- `GET /api/dashboard/alerts` - Get current alerts
- `GET /api/dashboard/activity-feed` - Get recent activities
- `GET /api/dashboard/preferences` - Get user's saved preferences
- `POST /api/dashboard/preferences` - Save dashboard layout/preferences
- `GET /api/dashboard/export?format=pdf|excel` - Export dashboard as report

---

### TOOL 7: CUSTOMER ANALYZER

**Purpose**: Deep-dive analysis of individual customer payment behavior and risk assessment.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **Customer Selection** | Dropdown or search to select customer |
| **Profile Summary** | Customer since, sales rep, region, credit limit, YTD sales, status |
| **Payment Behavior Stats** | On-time rate %, avg delay days, overdue frequency, settlement time |
| **Trend Analysis** | Month-by-month table: invoiced amount, paid on-time %, avg delay |
| **Trend Visualization** | Line chart: on-time rate trend, delay trend (past 12 months) |
| **Risk Assessment** | Auto-categorize: Low Risk, Medium Risk, High Risk (based on pattern) |
| **Risk Recommendations** | Suggest actions: adjust credit limit, tighten terms, escalate, monitor closely |
| **Cohort Comparison** | Compare this customer vs similar-sized/region customers |
| **Company Average** | Show metrics vs company-wide average (performance benchmark) |
| **Historical Transactions** | Table: all invoices (past 12 months), amounts, due dates, payment dates |
| **Exclude Filter** | Analysis based on valid invoices only (excluded noted separately) |
| **Predictive Insight** | Estimate next 30/60/90 days DSO based on current trend |
| **Custom Metrics** | Define custom KPI per customer segment (e.g., high-value vs SME) |
| **Cohort Segmentation** | Auto-segment all customers by: size, payment behavior, risk level |
| **Seasonal Pattern** | Detect if payment behavior varies by season (e.g., lower in Q4) |
| **Discount Analysis** | Track: discount terms offered vs actual payment behavior |
| **Export Options** | PDF detailed analysis, Excel data, email to sales team |
| **Bulk Comparison** | Select 2-5 customers → compare metrics side-by-side |
| **Action History** | Timeline of collection actions taken (email, WA, call) |

**Output Example**:
```
┌──────────────────────────────────────────────────────────────┐
│ CUSTOMER ANALYSIS REPORT - PT ABC Distributor               │
├──────────────────────────────────────────────────────────────┤
│ PROFILE:                                                     │
│ • Customer Since: 2022                                       │
│ • Sales Rep: Budi Santoso                                    │
│ • YTD Sales: Rp 8,000,000,000                               │
│ • Credit Limit: Rp 2,000,000,000                            │
│ • Current Status: Active                                     │
│                                                              │
│ PAYMENT BEHAVIOR (Past 12 Months):                           │
│ • On-time Rate: 85% (12 of 14 invoices paid on schedule)   │
│ • Avg Payment Delay: 7 days (vs 30-day terms)               │
│ • Overdue Frequency: 3x (Feb, Jun, Sep)                     │
│ • Avg Overdue Amount: Rp 350M                               │
│ • Largest Overdue: Rp 500M (38 days late)                   │
│ • Avg Settlement Time: 37 days (after due date)            │
│                                                              │
│ TREND ANALYSIS (Last 6 Months):                             │
│ Month      │ Invoiced │ On-Time│ Delay Avg │ Overdue  │ Trend│
│ ──────────────────────────────────────────────────────────  │
│ Mar 2026   │ Rp 1.5B  │ 90%   │ 3 days    │ 1x      │ ↑ Good│
│ Apr 2026   │ Rp 1.8B  │ 100%  │ 2 days    │ 0x      │ ↑ Excellent
│ May 2026   │ Rp 1.2B  │ 80%   │ 8 days    │ 1x      │ → Stable
│ Jun 2026   │ Rp 1.5B  │ 85%   │ 5 days    │ 1x      │ ↓ Slight decline
│ Jul 2026   │ Rp 1.2B  │ 80%   │ 8 days    │ 1x      │ ↓ Warning
│ Aug 2026   │ Rp 0.9B  │ 75%   │ 15 days   │ 1x      │ ↓ Alert
│                                                              │
│ RISK ASSESSMENT:                                            │
│ 🟡 MEDIUM RISK - Payment discipline declining               │
│                                                              │
│ Observations:                                                │
│ • Invoiced amount trending downward (possible cash squeeze?)│
│ • Delay increasing steadily (from 3 to 15 days)            │
│ • On-time rate declining from 90% to 75%                   │
│ • Current overdue: Rp 500M (38 days past due)              │
│                                                              │
│ RECOMMENDATIONS:                                             │
│ 🔴 IMMEDIATE: Call customer to discuss payment plan        │
│ 🟡 SHORT-TERM: Reduce credit limit Rp 2B → Rp 1.5B        │
│ 🟡 SHORT-TERM: Tighten payment terms: Net 30 → Net 15      │
│ 🟡 STRATEGY: Offer early payment discount (2/10) to improve
│ 🟢 MONITOR: Weekly payment follow-up (vs monthly)          │
│                                                              │
│ COHORT COMPARISON:                                           │
│ • Distributor (same size) avg on-time: 88% (vs PT ABC 75%) │
│ • Company average payment delay: 12 days (vs PT ABC 15 days)
│ • This customer BELOW average on reliability               │
│                                                              │
│ PREDICTIVE FORECAST (Next 30 Days):                         │
│ • Estimated DSO: 50 days (vs current 37 days)             │
│ • Risk of exceeding credit limit: 60% probability          │
│ • Recommended action: Proactive collection                 │
└──────────────────────────────────────────────────────────────┘
```

**API Endpoints**:
- `GET /api/analyzer/customer/{customer_id}` - Get customer analysis
- `GET /api/analyzer/customer/{customer_id}/trend` - Get payment trend
- `GET /api/analyzer/customer/{customer_id}/forecast` - Forecast DSO
- `GET /api/analyzer/cohort/similar-customers` - Find similar customers
- `GET /api/analyzer/customer/{customer_id}/export?format=pdf|excel` - Export analysis
- `GET /api/analyzer/all-customers/cohort-analysis` - Segment all customers
- `POST /api/analyzer/custom-metric` - Define custom KPI per segment

---

### TOOL 8: COLLECTION STATUS TRACKER

**Purpose**: End-to-end tracking of invoice collection status with action history.

**Key Features**:

| Feature | Specification |
|---------|---------------|
| **Status Workflow** | Pending → Sent → 1st Reminder → Overdue → Escalated → Settled / Written-off |
| **Status Table** | Columns: invoice no, customer, amount, due date, current status, days overdue |
| **Color Status Codes** | 🟢 Settled, 🔵 Pending, 🟡 Partial Payment, 🔴 Overdue, ⚫ Escalated |
| **Exclude Filter** | Toggle to hide/show excluded invoices (default: show valid only) |
| **Auto-Status Update** | Payment received → auto-update status to Settled |
| **Action History** | Timeline per invoice: email sent, WA sent, call log, note, status change |
| **Manual Action Log** | User can add custom action: call attempt, meeting, discussion, decision |
| **Next Action Suggest** | Rules-based: if overdue 5 days → suggest WA, if 30 days → suggest escalate |
| **Bulk Actions** | Multi-select invoices → send batch reminder, change status, escalate |
| **SLA Tracking** | Measure: time from overdue to first action, time to settlement |
| **Auto-log Integration** | Auto-log when email sent, WA sent (from email/WA system) |
| **Follow-up Reminder** | Notify user: action required for high-priority overdue |
| **User Assignment** | Assign invoice to specific collector/account manager |
| **Notes & Attachment** | Add custom notes per invoice, attach screenshots/evidence |
| **Priority Indicator** | Flag: high priority (large amount, very overdue, high-risk customer) |
| **Filter & Sort** | By status, customer, overdue days, assigned user, date, priority |
| **Search** | By invoice no, customer, reference number |
| **Export** | Excel: invoice list with action history, timeline |
| **Email Thread Link** | Link to actual email conversation history (if integrated) |
| **WA Conversation Link** | Link to actual WhatsApp conversation (if integrated) |
| **Status History** | Track all status changes over time (audit trail) |
| **Batch Reassign** | Reassign multiple invoices to different collector |

**Status Flow Diagram**:
```
Invoice Posted
      │
      ▼
   🔵 PENDING ← (Invoice issued, awaiting payment)
      │
      ├─→ Payment received → 🟢 SETTLED ✓
      │
      ├─→ No payment by due date
      │      ▼
      │   📤 SENT ← (Reminder sent: email/WA)
      │      │
      │      ├─→ Payment received → 🟢 SETTLED ✓
      │      │
      │      ├─→ No payment, overdue 5 days
      │      │      ▼
      │      │   🟡 1ST REMINDER ← (Follow-up email/WA)
      │      │      │
      │      │      ├─→ Payment received → 🟢 SETTLED ✓
      │      │      │
      │      │      ├─→ Partial payment → 🟡 PARTIAL PAYMENT
      │      │      │
      │      │      ├─→ No payment, overdue 15 days
      │      │      │      ▼
      │      │      │   🔴 OVERDUE ← (Needs action)
      │      │      │      │
      │      │      │      ├─→ Call/follow-up done
      │      │      │      │
      │      │      │      ├─→ No payment, overdue 30+ days
      │      │      │      │      ▼
      │      │      │      │   ⚫ ESCALATED ← (Manager involved)
      │      │      │      │      │
      │      │      │      │      ├─→ Payment arranged → 🟢 SETTLED ✓
      │      │      │      │      │
      │      │      │      │      ├─→ Approve write-off → ❌ WRITTEN-OFF
      │      │      │      │      │
      │      │      │      │      └─→ Payment received → 🟢 SETTLED ✓
```

**Output Example**:
```
┌──────────────────────────────────────────────────────────────┐
│ COLLECTION STATUS TRACKER                                    │
│ Filters: [Status: All ▼] [Customer: All ▼] [Date: This Month]│
├──────────────────────────────────────────────────────────────┤
│ Invoice │ Customer │ Amount  │ Due Date  │Status    │D. Overdue│
├──────────────────────────────────────────────────────────────┤
│ INV-001 │PT ABC    │500M     │2026-07-15 │🔴OVERDUE │ 38 days  │
│ INV-002 │CV DEF    │400M     │2026-08-15 │🟡PARTIAL │ 7 days   │
│ INV-003 │PT GHI    │350M     │2026-09-10 │🟢SETTLED │ -        │
│ INV-004 │PT JKL    │250M     │2026-10-15 │🔵PENDING │ 0 days   │
│ INV-005 │PT ABC    │300M     │2026-08-20 │⚫ESCALATED│ 2 days  │
├──────────────────────────────────────────────────────────────┤

DETAIL: INV-001 (PT ABC - Rp 500M)
─────────────────────────────────────────────────────────────
Status: 🔴 OVERDUE (38 days past due date)
Assigned To: Hengki Setia Putra
Priority: 🔴 HIGH (Amount > 400M + overdue >30 days)

Action History:
2026-07-16 | EMAIL SENT        | Day 1 overdue reminder sent
2026-07-23 | MANUAL NOTE       | Called customer, promised payment EOY
2026-08-01 | EMAIL SENT        | Follow-up: Day 15 overdue reminder
2026-08-05 | WA SENT           | WhatsApp reminder: "Please settle soon"
2026-08-15 | ESCALATION NEEDED | → 30 days overdue, no response
2026-08-22 | [CURRENT]         | Status: Still outstanding

Next Action Suggestion: ⚡ ESCALATE TO FINANCE MANAGER
                       📞 Schedule phone call with customer
                       📋 Send formal demand letter

[ Add Action ]  [ Change Status ]  [ Escalate ]  [ Reassign ]
```

**API Endpoints**:
- `GET /api/tracker/invoices` - List invoices with status (filters available)
- `GET /api/tracker/invoice/{invoice_id}` - Get invoice detail + action history
- `POST /api/tracker/action` - Add manual action (call, note, etc)
- `PUT /api/tracker/status/{invoice_id}` - Update invoice status
- `POST /api/tracker/bulk-status-update` - Update multiple invoices
- `GET /api/tracker/overdue-alerts` - Get high-priority overdue invoices
- `POST /api/tracker/send-batch-reminder` - Send reminders (from tracker)
- `GET /api/tracker/export?format=excel|pdf` - Export tracker data

---

## SUPPORTING FEATURES

### A. User Management & Access Control

| Feature | Specification |
|---------|---------------|
| **Role-Based Access** | Admin, Finance Manager, AR Accountant, AR Collector, Sales Manager, Viewer |
| **Permissions Matrix** | Define: who can view, edit, delete, approve per tool |
| **User Audit Trail** | Log: who did what, when, what changed (immutable) |
| **Password Policy** | Strong password requirement, expiry, reset flow |
| **Session Management** | Timeout after inactivity (configurable, e.g., 30 min) |
| **Multi-Factor Auth** | Optional 2FA for sensitive operations (e.g., exclusion, adjustment) |
| **User Groups** | Group users by region/team, assign permissions per group |

### B. Data Integration & Import/Export

| Feature | Specification |
|---------|---------------|
| **Accurate 5 XML Import** | Auto-import invoices from Accurate 5 (daily/weekly scheduled) |
| **Bank Statement Import** | CSV/Excel upload for reconciliation (with bank format templates) |
| **Sales System API** | Integration to query pending SO (for credit limit calculation) |
| **Customer Master Sync** | Sync customer data (name, address, credit limit) from master |
| **Bulk CSV Import** | Generic CSV import for data correction/initialization |
| **API Export** | Generate reports via API (for downstream systems) |
| **Email Export** | Scheduled reports emailed to stakeholders |

### C. Notifications & Alerts

| Feature | Specification |
|---------|---------------|
| **Email Alerts** | System alerts to users (new overdue, reconciliation issue, etc) |
| **In-App Notifications** | Notifications within dashboard/app UI |
| **SMS Alerts** | Optional SMS for critical alerts (e.g., credit limit breach) |
| **Dashboard Banners** | Critical alerts displayed prominently on dashboard |
| **Alert Rules** | User configurable: threshold, frequency, recipient |
| **Do-Not-Disturb** | User can set quiet hours (no alerts outside office hours) |

### D. Reporting & Analytics

| Feature | Specification |
|---------|---------------|
| **Scheduled Reports** | Auto-generate & email reports (daily/weekly/monthly) |
| **Ad-hoc Reports** | User can generate custom report on demand |
| **Report Templates** | Pre-built: aging, reconciliation, performance, customer detail |
| **Custom Report Builder** | Allow users to define custom metrics + dimensions |
| **Benchmark Reports** | Compare metrics vs company average, vs peer customers |
| **Trend Analysis** | Month-over-month, year-over-year comparisons |
| **Forecast Reports** | Cash flow forecast, DSO forecast, bad debt forecast |

### E. System Administration

| Feature | Specification |
|---------|---------------|
| **Backup & Recovery** | Daily backup, restore capability |
| **Data Validation** | Check data integrity on import (invoice no format, amounts, etc) |
| **Error Handling** | Graceful failure, retry logic, error logging |
| **Performance Monitoring** | Monitor system performance, slow query alerts |
| **Database Optimization** | Index management, query optimization |
| **Audit Log Retention** | Keep audit logs for 3+ years (compliance) |
| **Data Privacy** | Ensure PII handling complies with regulations (GDPR, local law) |

### F. Mobile Application

| Feature | Specification |
|---------|---------------|
| **Mobile Dashboard** | Simplified dashboard view (key KPIs, alerts) |
| **Tracker on Mobile** | View & update collection status (simplified UI) |
| **Notification Push** | Push notification for alerts |
| **Offline Mode** | Cache recent data for offline view |
| **Biometric Login** | Optional: fingerprint/face login on mobile |

---

## TECHNICAL REQUIREMENTS

### System Requirements:

| Component | Specification |
|-----------|---------------|
| **PHP Version** | PHP ^8.1 |
| **Framework** | Laravel 10 |
| **Database** | MySQL (database: absensi_dmx) |
| **Local Server** | XAMPP (Apache + MySQL + PHP) |
| **Frontend** | Blade + Tailwind CSS 3.4 + Alpine.js 3.15 |
| **Bundler** | Vite 5 |
| **Auth** | Laravel Sanctum |
| **Excel** | maatwebsite/excel |
| **Testing** | PHPUnit, Laravel Pint, Faker |
| **Debug** | Ignition |
| **Docker** | Laravel Sail |
| **Email** | SMTP server (Gmail, SendGrid, or self-hosted) |
| **WhatsApp API** | Twilio or WhatsApp Business API |

### Security Requirements:

- SSL/TLS encryption (HTTPS only)
- SQL injection prevention (parameterized queries)
- XSS protection (input sanitization, CSP headers)
- CSRF tokens for state-changing operations
- Rate limiting (API & login)
- Password hashing (bcrypt, Argon2)
- Regular security audits
- Compliance with PCI-DSS (if handling payment data)

### Performance Requirements:

| Metric | Target |
|--------|--------|
| **Dashboard Load Time** | < 2 seconds |
| **API Response Time** | < 500ms (p95) |
| **Report Generation** | < 10 seconds for 10K invoices |
| **Concurrent Users** | Support 50+ concurrent users |
| **Data Freshness** | < 5 minute delay from GL to system |
| **Uptime** | 99.5% (monthly) |

---

## IMPLEMENTATION ROADMAP

### Phase 1: Foundation (Weeks 1-4)
- [ ] Database schema design & setup
- [ ] Backend scaffolding (Laravel 10 + MySQL)
- [ ] Frontend setup (Blade + Tailwind CSS + Alpine.js + Vite)
- [ ] User authentication & role-based access
- [ ] **Tool 1: Invoice Exclusion Master** (complete)

**Deliverable**: Working exclusion tool + foundation architecture

### Phase 2: Core Reporting (Weeks 5-10)
- [ ] **Tool 2: Aging Report Generator** (complete)
- [ ] **Tool 3: AR Reconciliation Tool** (complete)
- [ ] Data validation & import flows
- [ ] Export functionality (Excel, PDF)

**Deliverable**: Two core reporting tools, data integration

### Phase 3: Tracking & Automation (Weeks 11-16)
- [ ] **Tool 8: Collection Status Tracker** (complete)
- [ ] **Tool 4: Auto Email & WA Reminders** (complete)
- [ ] Scheduled job setup (Laravel Task Scheduling)
- [ ] Email/WA integration (SMTP, Twilio)

**Deliverable**: Automated collection system with tracking

### Phase 4: Monitoring (Weeks 17-22)
- [ ] **Tool 5: Customer Credit Limit Monitor** (complete)
- [ ] **Tool 7: Customer Analyzer** (complete)
- [ ] Real-time utilization calculation
- [ ] Predictive analytics features

**Deliverable**: Risk monitoring & deep analysis tools

### Phase 5: Visualization (Weeks 23-26)
- [ ] **Tool 6: Dashboard** (complete)
- [ ] All charts & visualizations
- [ ] Drill-down functionality
- [ ] Real-time updates

**Deliverable**: Unified AR dashboard

### Phase 6: Enhancement (Weeks 27-32)
- [ ] Mobile layout optimization (Blade mobile layout)
- [ ] Advanced reporting & predictive features
- [ ] Integration with more systems (sales, inventory)
- [ ] Performance optimization & caching
- [ ] User training & documentation

**Deliverable**: Mobile-responsive web, advanced features, production-ready

---

## SUCCESS METRICS

### Quantitative Metrics:

| Metric | Current | Target (6 months) | Target (12 months) |
|--------|---------|-------------------|-------------------|
| **DSO (Days)** | 50 days | 40 days | 35 days |
| **Collection Rate** | 85% | 90% | 93% |
| **Overdue Amount** | Rp 2.8B | Rp 2.0B | Rp 1.5B |
| **Days to Reconcile** | 5 days | 1 day | < 2 hours |
| **Collection Cost** | TBD | ↓ 20% | ↓ 35% |
| **Bad Debt %** | 3% | 2% | 1.5% |
| **Invoice Accuracy** | 95% | 99% | 99.5% |
| **System Adoption** | 0% | 80%+ users | 95%+ daily active |

### Qualitative Metrics:

- Finance team reports improved visibility & control
- Sales team positive feedback on credit limit enforcement
- Collectors report easier priority identification
- Management confident in month-end close accuracy
- Reduced disputes with customers over overdue invoices
- Better forecasting confidence (cash flow projections)

### User Satisfaction:

- Target NPS score: 7.0+ (on 1-10 scale)
- Target system uptime: 99.5%
- Average user training time: < 2 hours
- Support ticket resolution time: < 24 hours

---

## GLOSSARY

| Term | Definition |
|------|-----------|
| **AR** | Accounts Receivable - money owed to company by customers |
| **DSO** | Days Sales Outstanding - average days to collect payment |
| **GL** | General Ledger - official accounting records |
| **Invoice Exclusion** | Master list of invoices to ignore in all calculations |
| **Aging** | Categorization of invoices by days overdue |
| **Reconciliation** | Process of matching invoices with payments |
| **Credit Limit** | Maximum amount customer can owe at any time |
| **Collection** | Process of obtaining payment from customer |
| **WA** | WhatsApp messaging |
| **Bad Debt** | Invoice that will not be paid (write-off) |
| **Settlement** | Payment received, invoice closed |

---

## APPENDIX

### A. Sample Data Model

```sql
-- customers
CREATE TABLE customers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) UNIQUE,
  address TEXT,
  phone VARCHAR(20),
  email VARCHAR(100),
  sales_rep_id INT,
  region VARCHAR(50),
  credit_limit DECIMAL(15,2),
  payment_terms INT DEFAULT 30,
  status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- invoices
CREATE TABLE invoices (
  id INT PRIMARY KEY AUTO_INCREMENT,
  invoice_number VARCHAR(50) UNIQUE NOT NULL,
  customer_id INT NOT NULL,
  invoice_date DATE NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'IDR',
  status ENUM('PENDING', 'SENT', 'PARTIAL', 'SETTLED') DEFAULT 'PENDING',
  notes TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  INDEX idx_customer_id (customer_id),
  INDEX idx_due_date (due_date)
);

-- payments
CREATE TABLE payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  invoice_id INT NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  payment_method VARCHAR(50),
  reference_number VARCHAR(100),
  notes TEXT,
  created_at TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  INDEX idx_invoice_id (invoice_id),
  INDEX idx_payment_date (payment_date)
);

-- invoice_exclusions (as defined in Tool 1)

-- collection_actions
CREATE TABLE collection_actions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  invoice_id INT NOT NULL,
  action_type ENUM('EMAIL', 'WA', 'CALL', 'NOTE', 'STATUS_CHANGE') NOT NULL,
  description TEXT,
  action_by VARCHAR(50),
  action_date DATETIME,
  status VARCHAR(50),
  notes TEXT,
  created_at TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  INDEX idx_invoice_id (invoice_id),
  INDEX idx_action_date (action_date)
);

-- audit_log
CREATE TABLE audit_log (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  action VARCHAR(100),
  table_name VARCHAR(50),
  record_id INT,
  old_value TEXT,
  new_value TEXT,
  ip_address VARCHAR(50),
  user_agent TEXT,
  created_at TIMESTAMP,
  INDEX idx_created_at (created_at)
);
```

### B. API Error Codes

```
200  - OK
201  - Created
400  - Bad Request (validation error)
401  - Unauthorized (not logged in)
403  - Forbidden (no permission)
404  - Not Found
409  - Conflict (e.g., duplicate invoice)
500  - Server Error
503  - Service Unavailable
```

### C. Deployment Checklist

- [ ] Database setup & backup configured
- [ ] Backend deployed & tested
- [ ] Frontend deployed & tested
- [ ] SSL certificate installed
- [ ] Email/WA API keys configured
- [ ] Accurate 5 integration tested
- [ ] Scheduled jobs running
- [ ] Monitoring setup (optional but recommended)
- [ ] User accounts created & roles assigned
- [ ] Training & documentation completed
- [ ] Go-live date scheduled
- [ ] Rollback plan documented

---

**Document Ends**

---

## Approval Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| **Project Owner** | Hengki Setia Putra | 2026-08-22 | ____________ |
| **Finance Manager** | [TBD] | | ____________ |
| **IT Lead** | [TBD] | | ____________ |

---

**Status**: APPROVED FOR DEVELOPMENT

**Next Step**: Begin Phase 1 (Foundation) - Database & Backend Setup
