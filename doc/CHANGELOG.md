# Changelog

## Format
Format berdasarkan [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Added
- Initial project setup
- PRD documentation
- Tech stack: Laravel 10, MySQL, Blade, Tailwind CSS, Alpine.js
- Database schema design
- API documentation

### Changed
- DESIGN_SYSTEM.md: Replaced emoji icons (🟢🟡🟠🔴⚫) with Lucide icon names (`check-circle`, `alert-triangle`, `alert-circle`, `x-circle`) in Aging Colors and Credit Limit Colors tables
- COMPONENT_API.md: Updated Dark Mode Toggle example to use Lucide icons (`moon`/`sun`) instead of emoji (`🌙`/`☀️`), matching actual implementation
- layouts/app.blade.php: Added `defer` attribute to Lucide CDN script for non-render-blocking load
- dashboard.blade.php: Added `defer` attribute to Chart.js CDN script for non-render-blocking load
- layouts/app.blade.php: Changed default theme from dark to light mode
- layouts/app.blade.php: Updated primary color palette to softer blue tones
- DESIGN_SYSTEM.md: Updated color palette documentation to match softer primary colors
- preview/index.html: Changed default theme from dark to light mode
- preview/index.html: Updated primary color palette to softer blue tones
- preview/index.html: Updated light mode CSS overrides with softer colors (slate-based palette)

### To Do
- [ ] Tool 1: Invoice Exclusion / Blacklist Master
- [ ] Tool 2: Aging Report Generator
- [ ] Tool 3: AR Reconciliation Tool
- [ ] Tool 4: Auto Email & WA Reminders
- [ ] Tool 5: Customer Credit Limit Monitor
- [ ] Tool 6: Dashboard
- [ ] Tool 7: Customer Analyzer
- [ ] Tool 8: Collection Status Tracker

---

## [0.1.0] - 2026-08-22

### Added
- Project initialization
- Documentation structure (26 doc files)
- PRD with Laravel tech stack

### Changed
- Tech stack from React/FastAPI to Laravel/Blade
- Database from PostgreSQL to MySQL

---

## Versioning
- **Major**: Perubahan breaking (v1.0, v2.0)
- **Minor**: Fitur baru (v0.1.0, v0.2.0)
- **Patch**: Bug fix (v0.1.1, v0.1.2)

## Release Schedule
- Development: Setiap sprint (2 minggu)
- Production: Setiap quarter
