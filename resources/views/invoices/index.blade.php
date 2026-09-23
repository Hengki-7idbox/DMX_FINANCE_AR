<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Invoice" description="Manage invoices">
            <x-button href="{{ route('invoices.create') }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Invoice
            </x-button>
        </x-page-header>
    </x-slot>
    <x-card>
        <p class="text-muted-foreground text-center py-8">Module Invoice - Coming Soon</p>
    </x-card>
</x-layouts.app>
