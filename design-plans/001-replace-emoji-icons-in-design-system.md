# Replace Emoji Icons with Lucide SVG Icons in Design System Documentation

Written against: 68b75f9

## Evidence chain

- Surface: `doc/DESIGN_SYSTEM.md` (Aging Colors table line 28-31, Credit Limit Colors table line 36-39)
- Problem: Emoji characters (🟢🟡🟠🔴⚫) used as structural/status indicators in design system documentation, violating "No Emoji as Structural Icons" policy
- Design evidence: `doc/DESIGN_SYSTEM.md:28-31` defines Aging Colors with emoji prefixes; `doc/DESIGN_SYSTEM.md:36-39` defines Credit Limit Colors with emoji prefixes
- Owner: `doc/DESIGN_SYSTEM.md`
- Scope and affected surfaces: `doc/DESIGN_SYSTEM.md` — Aging Colors and Credit Limit Colors tables
- Uncertainty: None — documentation-only change, no runtime impact

## Design decision

Replace all emoji characters in design system tables with their corresponding Lucide icon names. This ensures documentation aligns with the implementation standard already used in `build/resources/views/` (Lucide icons via `<i data-lucide="...">`). The project already uses Lucide throughout — documentation should reflect the same standard.

## Reuse

- Lucide icons already defined in `doc/COMPONENT_API.md:176-178`:
  - Status: `check-circle`, `alert-triangle`, `alert-circle`, `x-circle`
- Pattern already implemented in `build/resources/views/dashboard.blade.php:17-19,33-35,50-52`

If a new primitive is required, state why the existing system cannot express the decision, where the primitive belongs, and which consumers should share it.

**Not applicable** — existing Lucide icon set fully covers all status indicators.

## Changes

1. `doc/DESIGN_SYSTEM.md` — Aging Colors table (lines 28-31)
   - Change: Replace emoji prefixes with Lucide icon names
     - `🟢 Current (0-30)` → `check-circle Current (0-30)` (using `green-500`)
     - `🟡 31-60 days` → `alert-triangle 31-60 days` (using `yellow-500`)
     - `🟠 61-90 days` → `alert-circle 61-90 days` (using `orange-500`)
     - `🔴 90+ days` → `x-circle 90+ days` (using `red-500`)
   - Preserve: Color values, bucket ranges, table structure
   - Verify: Table renders correctly with text icon names instead of emoji

2. `doc/DESIGN_SYSTEM.md` — Credit Limit Colors table (lines 36-39)
   - Change: Replace emoji prefixes with Lucide icon names
     - `🟢 Green` → `check-circle Green` (using `green-500`)
     - `🟡 Yellow` → `alert-triangle Yellow` (using `yellow-500`)
     - `🔴 Red` → `alert-circle Red` (using `red-500`)
     - `⚫ Blocked` → `x-circle Blocked` (using `gray-800`)
   - Preserve: Color values, range percentages, table structure
   - Verify: Table renders correctly with text icon names instead of emoji

## Scope

- Inherit: All consumers of `doc/DESIGN_SYSTEM.md` (design reference, future component development)
- Verify: Visual consistency with `doc/COMPONENT_API.md` icon list and `build/resources/views/` implementation
- Exclude: `doc/COMPONENT_API.md:143` (dark mode toggle emoji — separate finding #2), actual Blade templates (no changes needed)

## Validation

- Product: Design system documentation accurately reflects implementation standard
- Interface: Markdown tables render correctly in Obsidian and GitHub
- System: Lucide icon names match existing icon list in `doc/COMPONENT_API.md:176-178`
- Repository: `git diff doc/DESIGN_SYSTEM.md` → only emoji replacements in two tables

## Stop conditions

- Stop if design system is used by automated tooling that depends on emoji characters
- Stop if another documentation file references these emoji values

## Design documentation

- After acceptance and validation: Update `doc/CHANGELOG.md` to record emoji→Lucide migration in design system
