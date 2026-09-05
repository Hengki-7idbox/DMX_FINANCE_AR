# Replace Emoji Icons with Lucide Icons in Component API Dark Mode Toggle

Written against: 68b75f9

## Evidence chain

- Surface: `doc/COMPONENT_API.md` (Dark Mode Toggle section, line 143)
- Problem: Emoji characters (`🌙` and `☀️`) used in Alpine.js dark mode toggle example code
- Design evidence: `doc/COMPONENT_API.md:143` shows `<span x-text="dark ? '🌙' : '☀️'"></span>` — but `build/resources/views/layouts/app.blade.php:238-239` already uses correct Lucide implementation: `<i x-show="!isDark" data-lucide="moon">` and `<i x-show="isDark" data-lucide="sun">`
- Owner: `doc/COMPONENT_API.md`
- Scope and affected surfaces: `doc/COMPONENT_API.md` — Dark Mode Toggle code example
- Uncertainty: None — documentation-only change, implementation already correct

## Design decision

Update the dark mode toggle code example in COMPONENT_API.md to match the actual implementation in `app.blade.php`. The current documentation example uses emoji fallback (`🌙`/`☀️`) while the real code uses Lucide icons (`moon`/`sun`). Documentation should be the source of truth and match implementation.

## Reuse

- Lucide icons already defined in `doc/COMPONENT_API.md:177`:
  - Navigation: `home`, `menu`, `settings`, `log-out`
- Implementation already correct in `build/resources/views/layouts/app.blade.php:238-239`
- Pattern: `x-show` conditional with `data-lucide` attribute

**Not applicable** — no new primitive needed, existing pattern fully covers this.

## Changes

1. `doc/COMPONENT_API.md` — Dark Mode Toggle section (line 136-145)
   - Change: Replace emoji-based toggle with Lucide icon-based toggle matching actual implementation
     - Before: `<span x-text="dark ? '🌙' : '☀️'"></span>`
     - After: `<i x-show="!isDark" data-lucide="moon" class="w-5 h-5"></i>` + `<i x-show="isDark" data-lucide="sun" class="w-5 h-5"></i>`
   - Preserve: Alpine.js reactive pattern, localStorage persistence logic
   - Verify: Code example matches `app.blade.php:237-240` implementation

## Scope

- Inherit: All consumers of `doc/COMPONENT_API.md` (component reference, future development)
- Verify: Code example matches actual implementation in `app.blade.php`
- Exclude: `doc/DESIGN_SYSTEM.md` dark mode section (separate finding), actual Blade templates (already correct)

## Validation

- Product: Component API documentation accurately reflects working implementation
- Interface: Markdown code blocks render correctly in Obsidian and GitHub
- System: Code example matches `build/resources/views/layouts/app.blade.php:237-240`
- Repository: `git diff doc/COMPONENT_API.md` → only dark mode toggle code block updated

## Stop conditions

- Stop if COMPONENT_API.md is used by code generation tooling that depends on the emoji pattern
- Stop if Alpine.js version changes affect the `x-show` pattern

## Design documentation

- After acceptance and validation: Update `doc/CHANGELOG.md` to record documentation sync with implementation
