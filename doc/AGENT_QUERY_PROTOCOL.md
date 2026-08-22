# Agent Query Protocol

## Overview
Protokol standar untuk AI Agent dalam mengakses dan memanipulasi data AR Finance Tools.

## Query Standards

### Database Queries
- Selalu gunakan **Eloquent ORM** atau **Query Builder** Laravel, bukan raw SQL
- Gunakan **parameterized queries** untuk mencegah SQL injection
- Prioritaskan `select()` spesifik, hindari `SELECT *`

### API Queries
- Semua endpoint harus melalui **Laravel Sanctum** authentication
- Gunakan **Form Request** untuk validasi input
- Response format: `{ success: bool, data: any, message: string, errors?: object }`

### Data Access Patterns

| Pattern | Method | Example |
|---------|--------|---------|
| Read single | `Route::get` | `GET /api/invoices/{id}` |
| Read list | `Route::get` | `GET /api/invoices?status=overdue` |
| Create | `Route::post` | `POST /api/exclusions` |
| Update | `Route::put` | `PUT /api/exclusions/{id}` |
| Delete | `Route::delete` | `DELETE /api/exclusions/{id}` |
| Bulk import | `Route::post` | `POST /api/exclusions/bulk-import` |

### Search Protocol
1. Gunakan `LIKE` dengan wildcard untuk pencarian teks
2. Gunakan `WHERE DATE()` untuk filter tanggal
3. Gunakan `whereHas()` untuk relasi
4. Pagination default: **15 items/page**

### Exclusion Filter
Semua query AR **WAJIB** memfilter invoice yang masuk exclusion list:
```php
$query->whereNotIn('invoice_number', function ($q) {
    $q->select('invoice_number')
      ->from('invoice_exclusions')
      ->where('status', 'ACTIVE');
});
```

## Response Protocol

### Success Response
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation completed successfully"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": ["Error message"]
    }
}
```

## Rate Limiting
- Default: 60 requests/minute per user
- Bulk operations: 10 requests/minute
- Export operations: 5 requests/minute
