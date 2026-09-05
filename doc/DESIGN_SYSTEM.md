# Design System

## Overview
Design system untuk AR Finance Tools menggunakan Tailwind CSS 3.4 + Lucide icons.

---

## Color Palette

### Primary
| Name | Light | Dark | Usage |
|------|-------|------|-------|
| Primary | `primary-500` (#2b8eff) | `primary-400` (#4ba9ff) | Buttons, links |
| Primary Light | `primary-100` (#e0efff) | `primary-900` (#083366) | Backgrounds |
| Primary Dark | `primary-800` (#0a448f) | `primary-200` (#b8dfff) | Text |

### Status Colors (Soft)
| Status | Light | Dark | Usage |
|--------|-------|------|-------|
| Success | `green-500` | `green-400` | Settled, Current |
| Warning | `yellow-500` | `yellow-400` | Caution, 31-60 days |
| Danger | `red-500` | `red-400` | Overdue, At limit |
| Info | `blue-500` | `blue-400` | Pending, Info |

### Aging Colors
| Bucket | Color | Icon |
|--------|-------|------|
| Current (0-30) | `green-500` | `check-circle` |
| 31-60 days | `yellow-500` | `alert-triangle` |
| 61-90 days | `orange-500` | `alert-circle` |
| 90+ days | `red-500` | `x-circle` |

### Credit Limit Colors
| Status | Color | Range |
|--------|-------|-------|
| `check-circle` Green | `green-500` | 0-75% |
| `alert-triangle` Yellow | `yellow-500` | 75-95% |
| `alert-circle` Red | `red-500` | 95-100% |
| `x-circle` Blocked | `gray-800` | >100% |

---

## Typography

### Font Family
```css
font-sans: Inter, system-ui, sans-serif;
```

### Font Sizes
| Class | Size | Usage |
|-------|------|-------|
| `text-xs` | 12px | Captions, labels |
| `text-sm` | 14px | Body small |
| `text-base` | 16px | Body default |
| `text-lg` | 18px | Subheadings |
| `text-xl` | 20px | Section titles |
| `text-2xl` | 24px | Page titles |
| `text-3xl` | 30px | KPI values |

---

## Components

### Buttons
```html
<!-- Primary -->
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
    Save
</button>

<!-- Secondary -->
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
    Cancel
</button>

<!-- Danger -->
<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
    Delete
</button>

<!-- Ghost -->
<button class="text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg">
    View Details
</button>
```

### Cards
```html
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold mb-4">Card Title</h3>
    <p class="text-gray-600 dark:text-gray-400">Content here</p>
</div>
```

### KPI Cards
```html
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Total AR</p>
            <p class="text-3xl font-bold">Rp 7.0B</p>
            <p class="text-sm text-green-600">↓ 5% MoM</p>
        </div>
        <i data-lucide="wallet" class="w-10 h-10 text-blue-500"></i>
    </div>
</div>
```

### Status Badges
```html
<!-- Settled -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
    Settled
</span>

<!-- Overdue -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
    Overdue
</span>

<!-- Pending -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
    Pending
</span>
```

### Data Table
```html
<table class="w-full text-sm text-left">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            <th class="px-4 py-3">Invoice</th>
            <th class="px-4 py-3">Customer</th>
            <th class="px-4 py-3">Amount</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">INV-001</td>
            <td class="px-4 py-3">PT ABC</td>
            <td class="px-4 py-3">Rp 500M</td>
        </tr>
    </tbody>
</table>
```

---

## Dark Mode
```html
<!-- Toggle -->
<html class="dark">
    <body class="bg-gray-900 text-white">
        <!-- Components automatically adapt -->
    </body>
</html>

<!-- Toggle Button -->
<button @click="$dispatch('toggle-dark')"
        class="p-2 rounded-lg hover:bg-gray-700">
    <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
    <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
</button>
```

---

## Responsive Breakpoints

| Class | Width | Usage |
|-------|-------|-------|
| `sm` | 640px | Mobile landscape |
| `md` | 768px | Tablet |
| `lg` | 1024px | Desktop |
| `xl` | 1280px | Large desktop |

### Layout Patterns
- **Mobile**: Single column, bottom nav
- **Tablet**: 2 columns, collapsible sidebar
- **Desktop**: Sidebar + main content
