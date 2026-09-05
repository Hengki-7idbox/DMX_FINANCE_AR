# Component API

## Overview
Referensi komponen Blade + Alpine.js yang digunakan dalam AR Finance Tools.

---

## Layouts

### Admin Layout (`layouts/admin.blade.php`)
```html
@include('layouts.admin', [
    'title' => 'Dashboard',
    'breadcrumbs' => ['Dashboard'],
])
```
- Sidebar navigation
- Top header dengan user info
- Dark mode toggle

### Viewer Layout (`layouts/viewer.blade.php`)
```html
@include('layouts.viewer', [
    'title' => 'Report View',
])
```
- Read-only view
- Simplified navigation
- Print-friendly

### Mobile Layout (`layouts/mobile.blade.php`)
```html
@include('layouts.mobile', [
    'title' => 'Tracker',
])
```
- Bottom navigation
- Touch-friendly UI
- Simplified data view

### Print Layout (`layouts/print.blade.php`)
```html
@include('layouts.print', [
    'title' => 'Aging Report',
])
```
- No navigation
- Optimized for paper
- Header/footer with company info

---

## Blade Components

### KPI Card
```blade
<x-kpi-card
    title="Total AR"
    value="Rp 7.0B"
    change="-5%"
    direction="down"
    icon="wallet"
/>
```

### Data Table
```blade
<x-data-table
    id="exclusion-table"
    :columns="$columns"
    :data="$exclusions"
    :options="['responsive' => true, 'searchable' => true]"
/>
```

### Status Badge
```blade
<x-status-badge status="overdue" label="Overdue" />
<x-status-badge status="settled" label="Settled" />
```

### Filter Bar
```blade
<x-filter-bar>
    <x-filter-select name="status" :options="$statuses" />
    <x-filter-date name="from" />
    <x-filter-date name="to" />
    <x-filter-search placeholder="Search invoice..." />
</x-filter-bar>
```

### Modal Dialog
```blade
<x-modal id="add-exclusion" title="Add Exclusion">
    <form>...</form>
    <x-slot name="footer">
        <button class="btn-primary">Save</button>
    </x-slot>
</x-modal>
```

### Toast Notification
```html
<div x-data="{ show: true }" x-show="show" x-transition
     class="toast toast-success">
    Exclusion added successfully!
</div>
```

---

## Alpine.js Components

### Shift Picker
```html
<div x-data="shiftPicker()">
    <select x-model="selectedShift">
        <option value="morning">Pagi (06:00-14:00)</option>
        <option value="afternoon">Siang (14:00-22:00)</option>
        <option value="night">Malam (22:00-06:00)</option>
    </select>
</div>
```

### Date Range Picker
```html
<div x-data="dateRangePicker()">
    <input type="date" x-model="startDate" />
    <input type="date" x-model="endDate" />
    <button @click="applyFilter()">Apply</button>
</div>
```

### Dark Mode Toggle
```html
<div x-data="{ isDark: localStorage.getItem('darkMode') === 'true' }"
     x-init="if (isDark) document.documentElement.classList.add('dark')">
    <button @click="isDark = !isDark; localStorage.setItem('darkMode', isDark); document.documentElement.classList.toggle('dark', isDark)">
        <i x-show="!isDark" data-lucide="moon" class="w-5 h-5"></i>
        <i x-show="isDark" data-lucide="sun" class="w-5 h-5"></i>
        <span class="sr-only">Toggle theme</span>
    </button>
</div>
```

### Toast Manager
```html
<div x-data="toastManager()" class="fixed bottom-4 right-4">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.show" x-transition
             :class="'toast-' + toast.type"
             x-text="toast.message">
        </div>
    </template>
</div>
```

---

## Lucide Icons

### Usage
```html
<i data-lucide="wallet"></i>
<i data-lucide="alert-triangle"></i>
<i data-lucide="check-circle"></i>
<i data-lucide="search"></i>
<i data-lucide="filter"></i>
<i data-lucide="download"></i>
```

### Available Icons
- Navigation: `home`, `menu`, `settings`, `log-out`
- Actions: `search`, `filter`, `download`, `upload`, `refresh`
- Status: `check-circle`, `alert-triangle`, `alert-circle`, `x-circle`
- Finance: `wallet`, `credit-card`, `dollar-sign`, `trending-up`, `trending-down`
- Users: `users`, `user`, `user-check`, `user-x`
