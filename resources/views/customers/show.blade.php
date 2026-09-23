<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Detail Customer" back-url="{{ route('customers.index', ['tab' => $tab ?? $customer->source_type]) }}">
            <x-button href="{{ route('customers.edit', ['customer' => $customer, 'tab' => $tab ?? $customer->source_type]) }}" variant="outline">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </x-button>
        </x-page-header>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Customer Info --}}
        <x-card title="Informasi Customer" class="lg:col-span-2">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-muted-foreground">Kode Customer</p>
                    <p class="font-medium">{{ $customer->customer_code }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Nama Customer</p>
                    <p class="font-medium">{{ $customer->customer_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Group</p>
                    <p class="font-medium">{{ $customer->customerGroup->group_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Type</p>
                    <p class="font-medium">{{ $customer->customer_type }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Source</p>
                    <x-badge :type="$customer->source_type === 'external' ? 'info' : 'warning'">
                        {{ strtoupper($customer->source_type) }}
                    </x-badge>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Tax Number</p>
                    <p class="font-medium">{{ $customer->tax_number ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Payment Term</p>
                    <p class="font-medium">{{ $customer->paymentTerm->term_name ?? '-' }} ({{ $customer->paymentTerm->days ?? 0 }} days)</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Credit Limit</p>
                    <p class="font-medium">Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Status</p>
                    <x-badge :type="$customer->status === 'ACTIVE' ? 'success' : 'secondary'">
                        {{ $customer->status }}
                    </x-badge>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-muted-foreground">Address</p>
                    <p class="font-medium">{{ $customer->address ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">City</p>
                    <p class="font-medium">{{ $customer->city ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Phone</p>
                    <p class="font-medium">{{ $customer->phone ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Email</p>
                    <p class="font-medium">{{ $customer->email ?? '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-muted-foreground">Notes</p>
                    <p class="font-medium">{{ $customer->notes ?? '-' }}</p>
                </div>
            </div>
        </x-card>

        {{-- Outstanding --}}
        <x-card title="Outstanding">
            <div class="text-center py-4">
                <p class="text-sm text-muted-foreground">Total Outstanding</p>
                <p class="text-3xl font-bold">Rp {{ number_format($customer->outstanding, 0, ',', '.') }}</p>
            </div>
        </x-card>
    </div>

    {{-- Recent Invoices --}}
    <x-card title="Invoice Terbaru" class="mt-6">
        <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
                <thead class="border-b border-border">
                    <tr class="border-b transition-colors hover:bg-muted/50">
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Invoice Number</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Date</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Due Date</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Grand Total</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Outstanding</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customer->invoices as $invoice)
                        <tr class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-4">{{ $invoice->invoice_number }}</td>
                            <td class="p-4">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td class="p-4">{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td class="p-4">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                            <td class="p-4 font-medium">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                            <td class="p-4">
                                <x-badge :type="$invoice->status === 'PAID' ? 'success' : ($invoice->status === 'OVERDUE' ? 'danger' : 'warning')">
                                    {{ $invoice->status }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-muted-foreground">
                                Belum ada invoice
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.app>
