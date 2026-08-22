# Security

## Overview
Dokumentasi keamanan untuk AR Finance Tools.

---

## Authentication

### Laravel Sanctum
- Token-based authentication
- Stateless API authentication
- SPA authentication via cookies

### Implementation
```php
// Login
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $token = Auth::user()->createToken('auth-token')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    return response()->json(['message' => 'Invalid credentials'], 401);
});
```

---

## Authorization

### Role-Based Access Control
```php
// Middleware
Route::middleware(['role:admin,finance_manager'])->group(function () {
    // Only admin and finance_manager
});

// Blade
@if(auth()->user()->role === 'admin')
    <button>Delete</button>
@endif

// Controller
public function destroy($id)
{
    if (!in_array(auth()->user()->role, ['admin'])) {
        abort(403);
    }
    // ...
}
```

---

## Data Protection

### Input Validation
```php
// Form Request
class StoreExclusionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'invoice_number' => 'required|string|max:50|unique:invoice_exclusions,invoice_number',
            'reason_code' => 'required|in:DUPLICATE,GL_ERROR,TEST_INVOICE,DATA_ENTRY_ERROR,WORKFLOW_ERROR,OTHER',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
```

### SQL Injection Prevention
```php
// Always use Eloquent or Query Builder
Invoice::where('invoice_number', $input)->first();

// NOT
DB::select("SELECT * FROM invoices WHERE invoice_number = '$input'");
```

### XSS Prevention
```blade
{{-- Escape output --}}
{{ $userInput }}

{{-- Raw HTML (use carefully) --}}
{!! $trustedHtml !!}
```

### CSRF Protection
```html
<form method="POST">
    @csrf
    ...
</form>
```

---

## Password Security

### Hashing
```php
// Always hash passwords
$user->password = bcrypt($password);
$user->password = Hash::make($password);
```

### Policy
- Minimum 8 characters
- Must include uppercase, lowercase, number
- Password expiry: 90 days
- History: remember last 5 passwords

---

## Session Security

### Configuration
```php
// config/session.php
'lifetime' => 120,  // 2 hours
'expire_on_close' => false,
'encrypt' => true,
'http_only' => true,
'secure' => true,  // HTTPS only
'same_site' => 'lax',
```

---

## API Security

### Rate Limiting
```php
// routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests per minute
});

// Bulk operations
Route::middleware('throttle:10,1')->group(function () {
    // 10 requests per minute
});
```

### CORS
```php
// config/cors.php
'allowed_origins' => ['http://localhost:5173'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

---

## Audit Trail

### Logging
```php
AuditLog::create([
    'user_id' => Auth::id(),
    'action' => 'DELETE_EXCLUSION',
    'table_name' => 'invoice_exclusions',
    'record_id' => $id,
    'old_value' => $exclusion->toArray(),
    'ip_address' => request()->ip(),
]);
```

### What to Log
- Login/logout
- Create/update/delete
- Export data
- Permission changes
- Failed authentication attempts

---

## File Upload Security

### Validation
```php
$request->validate([
    'file' => 'required|file|max:5120|mimes:csv,xlsx,xls',
]);
```

### Storage
```php
// Store outside public directory
$path = $request->file('file')->store('imports', 'private');
```

---

## Environment Security

### .env Protection
- Never commit to version control
- Use different .env for each environment
- Rotate secrets regularly

### Production Checklist
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Strong database password
- [ ] HTTPS enforced
- [ ] Session encryption enabled
- [ ] File permissions restricted

---

## Compliance

### Data Privacy
- Customer PII encrypted at rest
- Access logs retained 3 years
- Right to deletion supported

### Audit Requirements
- All financial changes logged
- User actions traceable
- Reports tamper-proof
