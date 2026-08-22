# Incremental Update Workflow

## Overview
Workflow untuk update incremental (perubahan kecil secara bertahap) dalam AR Finance Tools.

---

## Workflow Types

### 1. Feature Addition
```
Branch → Code → Test → Review → Merge → Deploy
```

### 2. Bug Fix
```
Issue → Reproduce → Fix → Test → Review → Merge → Deploy
```

### 3. Data Migration
```
Script → Test (staging) → Backup → Execute → Verify → Backup
```

### 4. Config Change
```
Plan → Test (staging) → Document → Execute → Verify
```

---

## Branch Strategy

### Branch Types
| Branch | Purpose | Lifetime |
|--------|---------|----------|
| `main` | Production code | Permanent |
| `develop` | Development integration | Permanent |
| `feature/*` | New features | Temporary |
| `fix/*` | Bug fixes | Temporary |
| `hotfix/*` | Critical production fixes | Temporary |

### Naming Convention
```
feature/tool-1-exclusion
feature/tool-2-aging-report
fix/aging-bucket-calculation
hotfix/critical-auth-issue
```

---

## Development Workflow

### Step 1: Create Branch
```bash
git checkout develop
git pull origin develop
git checkout -b feature/tool-1-exclusion
```

### Step 2: Implement
```bash
# Code changes
# Add tests
# Run tests
php artisan test
```

### Step 3: Commit
```bash
git add .
git commit -m "feat(exclusion): add bulk import functionality"
```

### Step 4: Push & Create PR
```bash
git push origin feature/tool-1-exclusion
# Create PR on GitHub/GitLab
```

### Step 5: Code Review
- [ ] Code passes tests
- [ ] Laravel Pint passed
- [ ] Documentation updated
- [ ] No security issues

### Step 6: Merge
```bash
git checkout develop
git merge --no-ff feature/tool-1-exclusion
git push origin develop
```

### Step 7: Deploy
```bash
git checkout main
git merge develop
git push origin main

# On server
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

---

## Database Migration Strategy

### Safe Migration Pattern
```php
// 1. Add new column (nullable)
Schema::table('invoices', function (Blueprint $table) {
    $table->string('new_field')->nullable();
});

// 2. Populate data
DB::table('invoices')->whereNull('new_field')->update(['new_field' => 'default']);

// 3. Make NOT NULL (after data populated)
Schema::table('invoices', function (Blueprint $table) {
    $table->string('new_field')->nullable(false)->default('default')->change();
});
```

### Rollback Strategy
```php
// Always write rollback
public function down()
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropColumn('new_field');
    });
}
```

---

## Feature Flag Pattern

### Using Config
```php
// config/features.php
return [
    'tool_1_exclusion' => env('FEATURE_EXCLUSION', true),
    'tool_2_aging' => env('FEATURE_AGING', false),
];
```

### Usage in Code
```php
if (config('features.tool_1_exclusion')) {
    // Show exclusion features
}
```

### Blade
```blade
@if(config('features.tool_1_exclusion'))
    <a href="{{ route('exclusions.index') }}">Exclusions</a>
@endif
```

---

## Testing Checklist

### Per Feature
- [ ] Unit tests written
- [ ] Feature tests written
- [ ] Manual testing done
- [ ] Edge cases covered
- [ ] Error handling tested
- [ ] Performance acceptable

### Per Release
- [ ] All tests passing
- [ ] Database migrations tested
- [ ] Rollback tested
- [ ] Documentation updated
- [ ] Changelog updated

---

## Rollback Procedure

### Code Rollback
```bash
git revert <commit-hash>
git push origin main
```

### Database Rollback
```bash
php artisan migrate:rollback --step=1
```

### Full Rollback
```bash
# 1. Revert code
git revert HEAD

# 2. Rollback database
php artisan migrate:rollback

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 4. Restart queue workers
php artisan queue:restart
```
