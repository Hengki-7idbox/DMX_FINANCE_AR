<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Detail Bank Account" back-url="{{ route('bank-accounts.index') }}">
            <x-button href="{{ route('bank-accounts.edit', $bankAccount) }}" variant="outline">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </x-button>
        </x-page-header>
    </x-slot>

    <x-card title="Informasi Bank Account">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-sm text-muted-foreground">Bank Name</p>
                <p class="font-medium text-foreground">{{ $bankAccount->bank_name }}</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Account Number</p>
                <p class="font-medium text-foreground">{{ $bankAccount->account_number }}</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Account Name</p>
                <p class="font-medium text-foreground">{{ $bankAccount->account_name }}</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Currency</p>
                <p class="font-medium text-foreground">{{ $bankAccount->currency }}</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Status</p>
                <x-badge :type="$bankAccount->status === 'ACTIVE' ? 'success' : 'secondary'">
                    {{ $bankAccount->status }}
                </x-badge>
            </div>
        </div>
    </x-card>
</x-layouts.app>
