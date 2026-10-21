<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Kata Kunci Coretax" description="Kata kunci item untuk menentukan TrxCode (04 bila cocok, selain itu 08)">
            <x-button onclick="document.getElementById('keyword_modal').showModal()">Tambah Kata Kunci</x-button>
            <x-button variant="destructive" onclick="document.getElementById('clear_modal').showModal()">Clear Data</x-button>
        </x-page-header>
    </x-slot>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-alert type="info" class="mb-4">
        Export XML Coretax memberi <strong>TrxCode 04</strong> bila ada item description invoice yang mengandung salah satu kata kunci
        (pencocokan substring, tidak peka huruf besar/kecil); selain itu <strong>08</strong>.
    </x-alert>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="keywords-table" class="w-full min-w-[400px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">ID</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kata Kunci</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keywords as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50">
                            <td class="px-2 py-1.5">
                                <form action="{{ route('coretax-keywords.destroy', $row) }}" method="POST"
                                      onsubmit="return confirm('Hapus kata kunci ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-destructive hover:underline text-xs font-medium">Hapus</button>
                                </form>
                            </td>
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row->id }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->kata }}</td>
                        </tr>
                    @empty
                        <tr class="border-b border-border">
                            <td class="px-2 py-1.5 text-muted-foreground">-</td>
                            <td class="px-2 py-1.5 text-muted-foreground">-</td>
                            <td class="px-2 py-1.5 text-muted-foreground">-</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($keywords->isEmpty())
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada kata kunci. Tambah manual.
            </div>
        @endif
    </x-card>

    <dialog id="keyword_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Tambah Kata Kunci</h3>
            <form action="{{ route('coretax-keywords.store') }}" method="POST">
                @csrf
                <div class="py-4">
                    <label for="form_kata" class="block text-sm font-medium text-muted-foreground mb-1">Kata Kunci</label>
                    <input type="text" name="kata" id="form_kata" maxlength="255" required
                           class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    @error('kata')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('keyword_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="clear_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Semua Kata Kunci?</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Semua kata kunci ({{ number_format($keywords->count()) }} baris) akan dihapus. Tanpa kata kunci, semua invoice akan memakai TrxCode 08.
            </p>
            <div class="modal-action">
                <form action="{{ route('coretax-keywords.clear') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error">Ya, Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('clear_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#keywords-table').DataTable({
                pageLength: 25,
                order: [],
                columnDefs: [{ orderable: false, targets: 0 }],
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
