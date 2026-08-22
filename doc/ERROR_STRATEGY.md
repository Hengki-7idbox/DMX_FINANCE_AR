# Error Strategy

## Overview
Strategi error handling untuk AR Finance Tools.

---

## Error Levels

| Level | Code | Description | Action |
|-------|------|-------------|--------|
| INFO | 200-299 | Success responses | Log optional |
| WARNING | 400-499 | Client errors | Log + user message |
| ERROR | 500-599 | Server errors | Log + alert admin |
| CRITICAL | - | System failure | Alert + auto-notify |

---

## HTTP Error Codes

| Code | Usage | Example |
|------|-------|---------|
| 200 | Success | Data retrieved |
| 201 | Created | Exclusion added |
| 400 | Bad Request | Invalid input |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | No permission |
| 404 | Not Found | Data not found |
| 409 | Conflict | Duplicate invoice |
| 422 | Validation Error | Form validation failed |
| 429 | Rate Limited | Too many requests |
| 500 | Server Error | Internal error |
| 503 | Service Unavailable | Maintenance |

---

## Laravel Exception Handler

```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->renderable(function (NotFoundHttpException $e, $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }
        return response()->view('errors.404', [], 404);
    });

    $this->renderable(function (ValidationException $e, $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }
    });

    $this->renderable(function (AuthorizationException $e, $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses',
            ], 403);
        }
        abort(403);
    });
}
```

---

## Controller Error Pattern

```php
public function store(StoreExclusionRequest $request)
{
    try {
        $exclusion = InvoiceExclusion::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $exclusion,
            'message' => 'Exclusion berhasil ditambahkan',
        ], 201);

    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->errorInfo[1] == 1062) { // Duplicate entry
            return response()->json([
                'success' => false,
                'message' => 'Invoice sudah di-exclude sebelumnya',
            ], 409);
        }

        Log::error('Exclusion store error', [
            'error' => $e->getMessage(),
            'user' => Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan. Silakan coba lagi.',
        ], 500);
    }
}
```

---

## Frontend Error Handling

### Toast Notifications
```html
<div x-data="toastManager()">
    <!-- Success -->
    <div x-show="toast.type === 'success'"
         class="bg-green-500 text-white p-4 rounded-lg">
        <span x-text="toast.message"></span>
    </div>

    <!-- Error -->
    <div x-show="toast.type === 'error'"
         class="bg-red-500 text-white p-4 rounded-lg">
        <span x-text="toast.message"></span>
    </div>

    <!-- Warning -->
    <div x-show="toast.type === 'warning'"
         class="bg-yellow-500 text-white p-4 rounded-lg">
        <span x-text="toast.message"></span>
    </div>
</div>
```

### Fetch Error Handler
```javascript
async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                ...options.headers,
            },
        });

        const data = await response.json();

        if (!response.ok) {
            throw { status: response.status, data };
        }

        return data;
    } catch (error) {
        if (error.status === 422) {
            // Validation errors
            showValidationErrors(error.data.errors);
        } else if (error.status === 401) {
            window.location.href = '/login';
        } else {
            showToast('error', error.data?.message || 'Terjadi kesalahan');
        }
        throw error;
    }
}
```

---

## Logging Strategy

| Level | When | Where |
|-------|------|-------|
| DEBUG | Development info | Local only |
| INFO | Normal operations | Daily file |
| WARNING | Potential issues | Daily file + email |
| ERROR | Failures | Daily file + email + Slack |
| CRITICAL | System down | Email + SMS + immediate |

### Log Channels (config/logging.php)
```php
'channels' => [
    'stack' => ['driver' => 'stack', 'channels' => ['daily', 'slack']],
    'daily' => ['driver' => 'daily', 'path' => storage_path('logs/laravel.log'), 'days' => 90],
    'slack' => ['driver' => 'slack', 'url' => env('LOG_SLACK_WEBHOOK_URL'), 'level' => 'error'],
    'audit' => ['driver' => 'daily', 'path' => storage_path('logs/audit.log'), 'days' => 1095],
],
```
