<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Global Report" description="Outstanding invoice tahun 2024 ke bawah" back-url="{{ route('invoices.index') }}"></x-page-header>
    </x-slot>

    @include('invoices._tabs')

    <x-card>
        <div class="border-b border-border px-3 py-2 text-sm text-muted-foreground">
            *Update on {{ $updatedAt->format('d/m/Y H:i:s') }}
        </div>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table class="w-full text-sm whitespace-nowrap">
                <thead class="border-b border-border bg-accent/40">
                    <tr>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground">Company Name</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground">CR. Term</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">2024++</th>
                        <th class="h-10 px-3 text-right font-medium text-muted-foreground">Balance</th>
                        <th class="h-10 px-3 text-left font-medium text-muted-foreground">Last Pay Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr class="border-b border-border hover:bg-accent/40">
                            <td class="px-3 py-1.5 font-medium text-foreground max-w-[320px] truncate" title="{{ $r['name'] }}">{{ $r['name'] ?: '-' }}</td>
                            <td class="px-3 py-1.5 text-muted-foreground">{{ $r['term'] }}</td>
                            <td class="px-3 py-1.5 text-right text-foreground">{{ number_format($r['amount'], 0, ',', '.') }}</td>
                            <td class="px-3 py-1.5 text-right font-medium text-foreground">{{ number_format($r['balance'], 0, ',', '.') }}</td>
                            <td class="px-3 py-1.5 text-muted-foreground">-</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-muted-foreground">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                @if(count($rows))
                    <tfoot>
                        <tr class="border-t-2 border-border bg-accent/70 font-semibold">
                            <td class="px-3 py-2 text-foreground">GRAND TOTAL</td>
                            <td class="px-3 py-2"></td>
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['amount'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-foreground">{{ number_format($grand['balance'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-card>
</x-layouts.app>
