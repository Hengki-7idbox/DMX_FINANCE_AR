<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Kode Barang/Jasa Coretax" description="Daftar Kode Barang/Jasa 6 digit (sheet Efaktur Code) yang dipakai export XML Coretax">
            <x-button onclick="openCreateModal()">Tambah Data</x-button>
            <x-button onclick="document.getElementById('import_modal').showModal()">Import</x-button>
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
        Import dari file converter, sheet <strong>Efaktur Code</strong> (kolom <em>Kode Barang Jasa</em> dan <em>Nama Barang/Jasa</em>).
        Nama item dicocokkan otomatis (tanpa membedakan huruf besar/kecil, spasi, dan kata negara) ke
        <strong>Mapping Product</strong>, lalu dipakai sebagai tag <strong>Code</strong> pada XML Coretax.
    </x-alert>

    <x-card>
        <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-border p-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama..."
                   class="w-full max-w-sm rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
            @if(request('q'))
                <a href="{{ route('coretax-item-codes.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
            <span class="ml-auto text-xs text-muted-foreground">{{ $codes->count() }} kode</span>
        </form>

        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table class="w-full min-w-[600px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kode</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nama Barang/Jasa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50"
                            data-row-id="{{ $row->id }}"
                            data-code="{{ $row->code }}"
                            data-name="{{ $row->name }}">
                            <td class="px-2 py-1.5">
                                <div class="flex items-center gap-1">
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="openEditModal(this)">Edit</button>
                                    <button type="button" class="btn btn-ghost btn-xs text-destructive" onclick="openDeleteModal(this)">Hapus</button>
                                </div>
                            </td>
                            <td class="px-2 py-1.5 font-mono font-medium text-foreground">{{ $row->code }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->name }}</td>
                        </tr>
                    @empty
                        <tr class="border-b border-border">
                            <td colspan="3" class="px-2 py-4 text-center text-muted-foreground">
                                Belum ada kode. Import file converter (sheet Efaktur Code) atau tambah manual.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <dialog id="code_form_modal" class="modal">
        <div class="modal-box">
            <h3 id="code_form_title" class="font-bold text-lg text-foreground">Tambah Kode Barang/Jasa</h3>
            <form id="code_form" method="POST" action="{{ route('coretax-item-codes.store') }}">
                @csrf
                <div id="code_form_method" class="hidden"></div>
                <div class="py-4 space-y-3">
                    <div>
                        <label for="form_code" class="block text-sm font-medium text-muted-foreground mb-1">Kode Barang Jasa</label>
                        <input type="text" name="code" id="form_code" maxlength="6" required placeholder="080600"
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="form_name" class="block text-sm font-medium text-muted-foreground mb-1">Nama Barang/Jasa</label>
                        <input type="text" name="name" id="form_name" maxlength="255" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('code_form_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="delete_code_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Kode?</h3>
            <p class="py-3 text-sm text-muted-foreground">
                Yakin hapus kode <strong id="delete_code_label">-</strong>?
            </p>
            <div class="modal-action">
                <form id="delete_code_form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-destructive">Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('delete_code_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Kode Barang/Jasa</h3>
            <form action="{{ route('coretax-item-codes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-4 space-y-3">
                    <p class="text-sm text-muted-foreground">
                        Pilih file converter (<em>Hky - Faktur Pajak - Converter ...xlsx</em>). Sheet yang dibaca:
                        <strong>Efaktur Code</strong>.
                    </p>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                           class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-primary-foreground" />
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="clear_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Clear Semua Kode?</h3>
            <p class="py-3 text-sm text-muted-foreground">
                Semua data kode barang/jasa akan dihapus. Lanjutkan?
            </p>
            <div class="modal-action">
                <form method="POST" action="{{ route('coretax-item-codes.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-destructive">Hapus Semua</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('clear_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    @push('scripts')
    <script>
        function openCreateModal() {
            var form = document.getElementById('code_form');
            form.action = @json(route('coretax-item-codes.store'));
            document.getElementById('code_form_method').innerHTML = '';
            document.getElementById('code_form_title').textContent = 'Tambah Kode Barang/Jasa';
            document.getElementById('form_code').value = '';
            document.getElementById('form_name').value = '';
            document.getElementById('code_form_modal').showModal();
        }

        function openEditModal(btn) {
            var tr = btn.closest('tr');
            var id = tr.dataset.rowId;
            var form = document.getElementById('code_form');
            form.action = @json(url('coretax-item-codes')) + '/' + id;
            document.getElementById('code_form_method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('code_form_title').textContent = 'Edit Kode Barang/Jasa';
            document.getElementById('form_code').value = tr.dataset.code || '';
            document.getElementById('form_name').value = tr.dataset.name || '';
            document.getElementById('code_form_modal').showModal();
        }

        function openDeleteModal(btn) {
            var tr = btn.closest('tr');
            var id = tr.dataset.rowId;
            document.getElementById('delete_code_label').textContent = (tr.dataset.code || '') + ' - ' + (tr.dataset.name || '');
            document.getElementById('delete_code_form').action = @json(url('coretax-item-codes')) + '/' + id;
            document.getElementById('delete_code_modal').showModal();
        }
    </script>
    @endpush
</x-layouts.app>
