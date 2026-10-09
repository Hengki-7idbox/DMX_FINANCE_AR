<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Mapping Customer" description="Pemetaan customer antar sistem/sumber data">
            <x-button onclick="openCreateModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Mapping
            </x-button>
            <x-button onclick="document.getElementById('import_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import
            </x-button>
            <x-button variant="destructive" onclick="document.getElementById('clear_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Clear Data
            </x-button>
        </x-page-header>
    </x-slot>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-alert type="info" class="mb-4">
        Import <strong>Mapping Customer.xlsx</strong> — kolom ID, Kode Internal, Kode External, Nama Internal, Nama External.
        Aksi <strong>Edit</strong> / <strong>Hapus</strong> ada di menu titik-tiga pada kolom Aksi.
        @if(isset($matchCount))
            <span class="ml-2 inline-flex flex-wrap gap-1 align-middle">
                <x-badge type="success" label="Match: {{ $matchCount }}" />
                <x-badge type="danger" label="Not Match: {{ $notMatchCount }}" />
            </span>
        @endif
    </x-alert>

    <x-card>
        {{-- Horizontally scrollable data table: card tetap di batas, scroll hanya di wrapper --}}
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="mapping-customers-table" class="w-full min-w-[900px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">ID</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kode Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kode External</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nama Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nama External</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappingCustomers as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50">
                            <td class="px-2 py-1.5 sticky left-0 bg-card z-10">
                                <button type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30 hover:text-info"
                                        aria-label="Action"
                                        data-id="{{ $row->id }}"
                                        data-source-id="{{ $row->source_id }}"
                                        data-kode-internal="{{ $row->kode_internal }}"
                                        data-kode-external="{{ $row->kode_external }}"
                                        data-nama-internal="{{ $row->nama_internal }}"
                                        data-nama-external="{{ $row->nama_external }}"
                                        onclick="openActionMenu(this)">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-2 py-1.5">
                                @if($row->is_match ?? false)
                                    <x-badge type="success" label="Match" />
                                @else
                                    <x-badge type="danger" label="Not Match" />
                                @endif
                            </td>
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row->source_id ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-foreground font-medium">{{ $row->kode_internal }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->kode_external }}</td>
                            <td class="px-2 py-1.5 text-foreground max-w-[220px] truncate" title="{{ $row->nama_internal }}">{{ $row->nama_internal }}</td>
                            <td class="px-2 py-1.5 text-foreground max-w-[220px] truncate" title="{{ $row->nama_external }}">{{ $row->nama_external }}</td>
                        </tr>
                    @empty
                        {{-- Baris empty tanpa colspan: hindari DataTables warning tn/4 --}}
                        <tr class="border-b border-border">
                            @for($i = 0; $i < 7; $i++)
                                <td class="px-2 py-1.5 text-muted-foreground">-</td>
                            @endfor
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($mappingCustomers->isEmpty())
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada data mapping customer. Menunggu import Excel.
            </div>
        @endif
    </x-card>

    {{-- Action menu fixed di body: tidak terpotong overflow-x-auto table --}}
    <div id="action_menu"
         class="hidden fixed z-[100] w-44 rounded-lg border border-border bg-card p-1 shadow-xl">
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                onclick="openEditFromMenu()">
            <svg class="h-4 w-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
        </button>
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-destructive hover:bg-destructive/10"
                onclick="openDeleteFromMenu()">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus
        </button>
    </div>

    {{-- Create / Edit Mapping Modal (pola sama seperti Detail Sales) --}}
    <dialog id="mapping_form_modal" class="modal">
        <div class="modal-box">
            <h3 id="mapping_form_title" class="font-bold text-lg text-foreground">Tambah Mapping</h3>
            <p class="py-1 text-sm text-muted-foreground">Kode Internal ↔ Kode External</p>

            <form id="mapping_form" method="POST">
                @csrf
                <div id="mapping_form_method" class="hidden"></div>
                <div class="py-4 space-y-3">
                    <div>
                        <label for="form_source_id" class="block text-sm font-medium text-muted-foreground mb-1">ID <span class="text-tertiary">(opsional)</span></label>
                        <input type="number" name="source_id" id="form_source_id" min="0"
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="form_kode_internal" class="block text-sm font-medium text-muted-foreground mb-1">Kode Internal</label>
                        <input type="text" name="kode_internal" id="form_kode_internal" maxlength="100" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
                    </div>
                    <div>
                        <label for="form_kode_external" class="block text-sm font-medium text-muted-foreground mb-1">Kode External</label>
                        <input type="text" name="kode_external" id="form_kode_external" maxlength="100" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
                    </div>
                    <div>
                        <label for="form_nama_internal" class="block text-sm font-medium text-muted-foreground mb-1">Nama Internal</label>
                        <input type="text" name="nama_internal" id="form_nama_internal" maxlength="255" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="form_nama_external" class="block text-sm font-medium text-muted-foreground mb-1">Nama External</label>
                        <input type="text" name="nama_external" id="form_nama_external" maxlength="255" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('mapping_form_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Delete Mapping Modal --}}
    <dialog id="delete_mapping_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Mapping?</h3>
            <p class="py-3 text-sm text-muted-foreground">
                Yakin hapus mapping <strong id="delete_mapping_label">-</strong>?
                Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="modal-action">
                <form id="delete_mapping_form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error">Ya, Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('delete_mapping_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Import Modal --}}
    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Mapping Customer</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload file <strong>Mapping Customer.xlsx</strong> (.xlsx/.xls). Kolom: ID, Kode Internal, Kode External, Nama Internal, Nama External.</p>

            <form action="{{ route('mapping-customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-4">
                    <label for="file" class="block text-sm font-medium text-muted-foreground mb-2">File Excel</label>
                    <input type="file"
                           id="file"
                           name="file"
                           accept=".xlsx,.xls"
                           required
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
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Clear Data Modal --}}
    <dialog id="clear_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Semua Data?</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Semua data Mapping Customer ({{ number_format($mappingCustomers->count()) }} baris) akan dihapus permanen.
            </p>
            <div class="modal-action">
                <form action="{{ route('mapping-customers.clear') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error">Ya, Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('clear_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    @push('scripts')
    <script>
        var actionRow = null;

        function openCreateModal() {
            var form = document.getElementById('mapping_form');
            form.reset();
            form.action = @json(route('mapping-customers.store'));
            document.getElementById('mapping_form_method').innerHTML = '';
            document.getElementById('mapping_form_title').textContent = 'Tambah Mapping';
            clearFormErrors();
            document.getElementById('mapping_form_modal').showModal();
        }

        function openActionMenu(btn) {
            actionRow = {
                id: btn.getAttribute('data-id'),
                source_id: btn.getAttribute('data-source-id'),
                kode_internal: btn.getAttribute('data-kode-internal'),
                kode_external: btn.getAttribute('data-kode-external'),
                nama_internal: btn.getAttribute('data-nama-internal'),
                nama_external: btn.getAttribute('data-nama-external')
            };

            var menu = document.getElementById('action_menu');
            menu.classList.remove('hidden');

            var rect = btn.getBoundingClientRect();
            var menuW = menu.offsetWidth;
            var menuH = menu.offsetHeight;
            var left = rect.right + 6;
            if (left + menuW > window.innerWidth - 8) left = rect.left - menuW - 6;
            if (left < 8) left = 8;
            var top = rect.top;
            if (top + menuH > window.innerHeight - 8) top = window.innerHeight - menuH - 8;
            if (top < 8) top = 8;
            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
        }

        function closeActionMenu() {
            document.getElementById('action_menu').classList.add('hidden');
            actionRow = null;
        }

        function clearFormErrors() {
            document.querySelectorAll('#mapping_form .form-error').forEach(function (el) {
                el.classList.add('hidden');
                el.textContent = '';
            });
        }

        function openEditFromMenu() {
            if (!actionRow) return;
            var row = actionRow;
            closeActionMenu();

            var form = document.getElementById('mapping_form');
            form.reset();
            form.action = @json(route('mapping-customers.update', ['mappingCustomer' => '__ID__'])).replace('__ID__', row.id);
            document.getElementById('mapping_form_method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('mapping_form_title').textContent = 'Edit Mapping';
            clearFormErrors();

            document.getElementById('form_source_id').value = row.source_id || '';
            document.getElementById('form_kode_internal').value = row.kode_internal || '';
            document.getElementById('form_kode_external').value = row.kode_external || '';
            document.getElementById('form_nama_internal').value = row.nama_internal || '';
            document.getElementById('form_nama_external').value = row.nama_external || '';

            document.getElementById('mapping_form_modal').showModal();
        }

        function openDeleteFromMenu() {
            if (!actionRow) return;
            var row = actionRow;
            closeActionMenu();

            document.getElementById('delete_mapping_label').textContent =
                row.kode_internal + ' → ' + row.kode_external;
            document.getElementById('delete_mapping_form').action =
                @json(route('mapping-customers.destroy', ['mappingCustomer' => '__ID__'])).replace('__ID__', row.id);

            document.getElementById('delete_mapping_modal').showModal();
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-id][onclick*="openActionMenu"]');
                if (btn) {
                    e.stopPropagation();
                    openActionMenu(btn);
                    return;
                }
                if (!e.target.closest('#action_menu')) closeActionMenu();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeActionMenu();
            });
            window.addEventListener('scroll', closeActionMenu, true);
            window.addEventListener('resize', closeActionMenu);

            $('#mapping-customers-table').DataTable({
                pageLength: 25,
                order: [],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    }
                }
            });

            $('#mapping-customers-table').on('draw.dt', function() {
                closeActionMenu();
            });
        });
    </script>
    @endpush
</x-layouts.app>
