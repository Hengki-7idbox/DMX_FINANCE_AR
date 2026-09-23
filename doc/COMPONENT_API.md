# Component API

**Version:** 1.0  
**Date:** 22 September 2026

---

## Blade Components

### DataTable

```html
<x-datatable 
    id="invoice-table"
    :columns="$columns"
    :url="route('api.invoices.index')"
    :serverSide="true"
/>
```

**Props:**
- id: Table identifier
- columns: Column definitions
- url: API endpoint
- serverSide: Enable server-side processing

### Select2

```html
<x-select2
    name="customer_id"
    :url="route('api.customers.search')"
    placeholder="Pilih Customer"
/>
```

**Props:**
- name: Field name
- url: Search endpoint
- placeholder: Display text

### Card

```html
<x-card title="Invoice Details">
    <x-slot name="header">
        <button>Edit</button>
    </x-slot>
    
    {{ $slot }}
</x-card>
```

### Modal

```html
<x-modal id="create-invoice" title="Create Invoice">
    <form>
        <!-- form content -->
    </form>
</x-modal>
```

### Alert

```html
<x-alert type="success" message="Invoice created successfully" />
<x-alert type="error" message="Validation failed" />
<x-alert type="warning" message="Invoice is overdue" />
```

### Badge

```html
<x-badge type="success" label="PAID" />
<x-badge type="warning" label="PARTIAL" />
<x-badge type="danger" label="OVERDUE" />
```

## Alpine.js Components

### PaymentAllocator

```html
<div x-data="paymentAllocator({paymentId: 123})">
    <template x-for="invoice in invoices">
        <div>
            <span x-text="invoice.number"></span>
            <input type="number" x-model="invoice.allocation">
        </div>
    </template>
    <button @click="submit()">Allocate</button>
</div>
```

### AgingChart

```html
<div x-data="agingChart({customerId: 1})">
    <canvas x-ref="chart"></canvas>
</div>
```

## JavaScript API

### financeAR.invoice.search(query)

```javascript
financeAR.invoice.search('INV-001')
    .then(data => console.log(data));
```

### financeAR.payment.allocate(paymentId, allocations)

```javascript
financeAR.payment.allocate(123, [
    {invoice_id: 1, amount: 5000000},
    {invoice_id: 2, amount: 3000000}
]);
```
