# Add defer/async to Non-Critical CDN Script Tags

Written against: 68b75f9

## Evidence chain

- Surface: `build/resources/views/layouts/app.blade.php:10`, `build/resources/views/dashboard.blade.php:3`
- Problem: CDN scripts loaded synchronously without `defer` or `async`, potentially blocking page rendering
- Design evidence: 
  - `app.blade.php:10`: `<script src="https://unpkg.com/lucide@latest"></script>` — no defer/async
  - `dashboard.blade.php:3`: `<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>` — no defer/async
  - `app.blade.php:8-9`: Tailwind and Alpine already use `defer` correctly (good pattern to follow)
- Owner: `build/resources/views/layouts/app.blade.php`, `build/resources/views/dashboard.blade.php`
- Scope and affected surfaces: Layout script loading, dashboard chart rendering
- Uncertainty: Lucide `createIcons()` call timing may need verification after adding defer

## Design decision

Add `defer` attribute to non-critical CDN scripts to prevent render-blocking while maintaining execution order. This follows the existing pattern already established for Tailwind (`defer`) and Alpine (`defer`). Lucide icons are rendered after DOM ready via `lucide.createIcons()` in `appLayout.init()`, so defer is safe. Chart.js is initialized in `dashboard.init()` via `$nextTick`, so defer is safe.

## Reuse

- Existing pattern in `app.blade.php:8-9`:
  - `<script src="https://cdn.tailwindcss.com"></script>` (no defer — Tailwind requires early load)
  - `<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>` (deferred)
- Existing initialization pattern: `lucide.createIcons()` called in `init()` after `$nextTick`

**Not applicable** — no new primitive needed, existing defer pattern fully covers this.

## Changes

1. `build/resources/views/layouts/app.blade.php` — Lucide script (line 10)
   - Change: Add `defer` attribute to prevent render-blocking
     - Before: `<script src="https://unpkg.com/lucide@latest"></script>`
     - After: `<script defer src="https://unpkg.com/lucide@latest"></script>`
   - Preserve: Script source URL, initialization via `lucide.createIcons()` in `appLayout.init()`
   - Verify: Icons render correctly after page load (deferred execution)

2. `build/resources/views/dashboard.blade.php` — Chart.js script (line 3)
   - Change: Add `defer` attribute to prevent render-blocking
     - Before: `<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>`
     - After: `<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>`
   - Preserve: Script source URL, initialization via `new Chart()` in `dashboard.init()`
   - Verify: Charts render correctly after page load (deferred execution)

## Scope

- Inherit: All pages using `layouts/app.blade.php`, dashboard page
- Verify: Lucide icons and Chart.js charts render correctly across all pages
- Exclude: Tailwind CSS script (must remain synchronous for styling), Alpine.js (already deferred)

## Validation

- Product: Page loads faster with non-critical scripts deferred
- Interface: All pages with Lucide icons render correctly; dashboard charts render correctly
- System: No JavaScript errors in console; `lucide.createIcons()` still executes after DOM ready
- Repository: `git diff build/resources/views/` → only `defer` attributes added to two script tags

## Stop conditions

- Stop if Lucide or Chart.js CDN scripts require synchronous loading (check documentation)
- Stop if deferred loading causes visible flash of unstyled content (FOUC) for icons

## Design documentation

- After acceptance and validation: Update `doc/BUILD.md` or `doc/INSTALLATION.md` to document script loading best practices
