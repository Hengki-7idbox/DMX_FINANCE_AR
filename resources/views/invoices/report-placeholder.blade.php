<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="{{ $pageTitle }}" description="{{ $pageDescription ?? '' }}" back-url="{{ route('invoices.index') }}"></x-page-header>
    </x-slot>

    @include('invoices._tabs')

    <x-card>
        <p class="text-muted-foreground text-center py-8">{{ $pageTitle }} — Coming Soon.</p>
    </x-card>
</x-layouts.app>
