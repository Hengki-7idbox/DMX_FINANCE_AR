# Default Configuration

## Overview
Semua nilai default yang digunakan dalam sistem.

---

## Aging Report

| Parameter | Default | Description |
|-----------|---------|-------------|
| Bucket 1 | 0-30 hari | Current |
| Bucket 2 | 31-60 hari | Overdue 1 |
| Bucket 3 | 61-90 hari | Overdue 2 |
| Bucket 4 | 90+ hari | Overdue 3 |
| Currency | IDR | Mata uang default |
| Per Page | 15 | Items per halaman |

## Exclusion

| Parameter | Default | Description |
|-----------|---------|-------------|
| Exclude Related Payment | true | Auto-exclude payments |
| Max Bulk Import | 500 | Max rows per import |
| Allowed Extensions | csv, xlsx | File type untuk import |

## Reconciliation

| Parameter | Default | Description |
|-----------|---------|-------------|
| Amount Tolerance | 1% | ±1% auto-match |
| Date Tolerance | 2 days | ±2 hari auto-match |
| Alert Threshold | 5% | Mismatch alert |
| Auto Match Limit | 1000 | Max auto-match items |

## Collection Reminders

| Parameter | Default | Description |
|-----------|---------|-------------|
| Send Time | 09:00 WIB | Jam kirim reminder |
| Cooldown | 7 hari | Min interval antar reminder |
| Max Retry | 3x | Max retry kirim |
| Retry Backoff | 1h, 4h, 24h | Interval retry |

## Credit Limit

| Parameter | Default | Description |
|-----------|---------|-------------|
| Green | 0-75% | Safe |
| Yellow | 75-95% | Caution |
| Red | 95-100% | At Limit |
| Blocked | >100% | Over Limit |

## Dashboard

| Parameter | Default | Description |
|-----------|---------|-------------|
| Auto Refresh | 5 menit | Auto-reload interval |
| Top Customers | 10 | Top overdue display |
| Activity Feed | 10 | Recent activities |
| Date Range | This Month | Default filter |

## User Interface

| Parameter | Default | Description |
|-----------|---------|-------------|
| Theme | Light | Default theme |
| Language | ID | Bahasa Indonesia |
| Per Page Table | 15 | Rows per table |
| Date Format | DD/MM/YYYY | Format tanggal |
| Currency Format | Rp #,##0 | Format rupiah |

## Notification

| Parameter | Default | Description |
|-----------|---------|-------------|
| Email Alert | true | Sistem email aktif |
| WA Alert | false | WhatsApp aktif |
| In-App Alert | true | Notifikasi di-app |
| Quiet Hours | 22:00-06:00 | Jam tanpa notifikasi |

## Audit

| Parameter | Default | Description |
|-----------|---------|-------------|
| Log Retention | 3 tahun | Lama simpan log |
| Log Action | CREATE, UPDATE, DELETE | Aktivitas yang di-log |
| Capture IP | true | Simpan IP address |
| Capture User Agent | true | Simpan browser info |
