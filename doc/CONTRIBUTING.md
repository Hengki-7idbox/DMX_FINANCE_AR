# Contributing Guide

**Version:** 1.0  
**Date:** 22 September 2026

---

## Getting Started

### 1. Fork & Clone

```bash
git clone https://github.com/your-username/finance-ar.git
cd finance-ar
```

### 2. Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### 3. Create Branch

```bash
git checkout -b feature/your-feature-name
```

## Branch Naming

```text
feature/   - Fitur baru
fix/       - Bug fix
hotfix/    - Urgent fix
docs/      - Dokumentasi
refactor/  - Refactoring
test/      - Penambahan test
```

## Code Style

### PHP

-遵循 PSR-12
- Gunakan Laravel Pint
- Max line length: 120 chars

```bash
./vendor/bin/pint
```

### JavaScript

-遵循 ESLint
- Gunakan Prettier

### Blade

- Indent: 4 spaces
- Gunakan component jika ada

## Commit Message

```text
type(scope): description

feat(invoice): add import validation
fix(payment): correct allocation calculation
docs(api): update endpoint documentation
```

## Pull Request

### Checklist

```text
- [ ] Code follows project style
- [ ] Tests have been added
- [ ] All tests pass
- [ ] Documentation updated
- [ ] No breaking changes
```

### Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
Describe tests run

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] Tests added
- [ ] All tests pass
```

## Review Process

```text
1. Submit PR
2. Automated tests run
3. Code review by maintainer
4. Address feedback
5. Merge when approved
```

## Reporting Issues

```text
- Use GitHub Issues
- Provide clear title
- Describe steps to reproduce
- Expected vs actual behavior
- Screenshots if applicable
```
