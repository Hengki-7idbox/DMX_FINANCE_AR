<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Bank Transactions" description="Manage bank transactions">
            <x-button href="{{ route('bank-transactions.import') }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import
            </x-button>
        </x-page-header>
    </x-slot>
    <x-card>
        <p class="text-muted-foreground text-center py-8">Module Bank Transaction - Coming Soon</p>
    </x-card>
</x-layouts.app>
