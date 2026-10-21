<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Retur Sales" description="Return penjualan per item dari export Accurate Sales Return by Item">
            <x-button onclick="document.getElementById('import_modal').showModal()">Import Retur</x-button>
        </x-page-header>
    </x-slot>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-alert type="info" class="mb-4">
        Quantity dan amount retur bernilai negatif sesuai export Accurate. Import ulang file yang sama akan
        menimpa data untuk nomor retur yang sama (tidak menambah duplikat).
    </x-alert>

    <x-card>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 border-b border-border px-4 py-3 text-sm text-muted-foreground">
            <span>{{ number_format($returns->count()) }} baris</span>
            <span>{{ number_format($returns->pluck('return_no')->unique()->count()) }} nomor retur</span>
            <span>Total qty: <span class="font-semibold text-foreground">{{ number_format($returns->sum('quantity'), 2, ',', '.') }}</span></span>
            <span>Total amount: <span class="font-semibold text-error">Rp {{ number_format($returns->sum('amount'), 2, ',', '.') }}</span></span>
        </div>

        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="retur-sales-table" class="w-full min-w-[1200px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tanggal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">No Retur</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer Name</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Item No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Item Description</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Unit Price</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Qty</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Unit</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50">
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row->return_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-2 py-1.5 font-medium text-foreground">{{ $row->return_no }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->invoice_no ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->customer_code ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-foreground max-w-[220px] truncate" title="{{ $row->customer_name }}">{{ $row->customer_name ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->item_no ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-foreground max-w-[280px] truncate" title="{{ $row->item_description }}">{{ $row->item_description ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-right text-foreground">{{ number_format((float) $row->unit_price, 2, ',', '.') }}</td>
                            <td class="px-2 py-1.5 text-right {{ (float) $row->quantity < 0 ? 'text-error' : 'text-foreground' }}">{{ number_format((float) $row->quantity, 2, ',', '.') }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->unit ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-right {{ (float) $row->amount < 0 ? 'text-error' : 'text-foreground' }}">{{ number_format((float) $row->amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr class="border-b border-border">
                            <td colspan="11" class="px-2 py-6 text-center text-muted-foreground">Belum ada data retur. Klik Import Retur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Retur Sales</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Upload export Accurate <strong>Sales Return by Item</strong> (.xls/.xlsx).
            </p>
            <form action="{{ route('retur-sales.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-4">
                    <input type="file" id="file" name="file" accept=".xlsx,.xls" required
                           class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer" />
                    @error('file')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    @push('scripts')
    <script>
        $(function () {
            $('#retur-sales-table').DataTable({
                pageLength: 25,
                order: [],
                layout: { topStart: 'search', topEnd: 'pageLength', bottomStart: 'info', bottomEnd: 'paging' },
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
                }
            });
        });
    </script>
    @endpush
</x-layouts.app>
