<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Mapping Unit" description="Pemetaan satuan ukur internal → kode unit Coretax (UM.xxxx)">
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
        Dipakai export XML Coretax untuk mengisi tag <strong>Unit</strong>. Kolom: Unit Internal | Unit Coretax (mis. <em>Kg</em> → <em>UM.0022</em>).
        Aksi <strong>Edit</strong> / <strong>Hapus</strong> ada di menu titik-tiga pada kolom Aksi.
    </x-alert>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="mapping-units-table" class="w-full min-w-[600px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">ID</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Unit Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Unit Coretax</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappingUnits as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50"
                            data-row-id="{{ $row->id }}"
                            data-unit-internal="{{ $row->unit_internal }}"
                            data-unit-coretax="{{ $row->unit_coretax }}">
                            <td class="px-2 py-1.5 sticky left-0 bg-card z-10">
                                <button type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30"
                                        data-id="{{ $row->id }}"
                                        onclick="openActionMenu(this)"
                                        aria-label="Action">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row->id }}</td>
                            <td class="px-2 py-1.5 font-medium text-foreground">{{ $row->unit_internal }}</td>
                            <td class="px-2 py-1.5 text-foreground">{{ $row->unit_coretax }}</td>
                        </tr>
                    @empty
                        <tr class="border-b border-border">
                            @for($i = 0; $i < 4; $i++)
                                <td class="px-2 py-1.5 text-muted-foreground">-</td>
                            @endfor
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($mappingUnits->isEmpty())
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada data mapping unit. Tambah manual atau import Excel.
            </div>
        @endif
    </x-card>

    <div id="action_menu" class="hidden fixed z-[100] w-44 rounded-lg border border-border bg-card p-1 shadow-xl">
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                onclick="openEditFromMenu()">
            Edit
        </button>
        <button type="button"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-destructive hover:bg-destructive/10"
                onclick="openDeleteFromMenu()">
            Hapus
        </button>
    </div>

    <dialog id="unit_form_modal" class="modal">
        <div class="modal-box">
            <h3 id="unit_form_title" class="font-bold text-lg text-foreground">Tambah Mapping Unit</h3>
            <form id="unit_form" method="POST">
                @csrf
                <div id="unit_form_method" class="hidden"></div>
                <div class="py-4 space-y-3">
                    <div>
                        <label for="form_unit_internal" class="block text-sm font-medium text-muted-foreground mb-1">Unit Internal</label>
                        <input type="text" name="unit_internal" id="form_unit_internal" maxlength="50" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="form_unit_coretax" class="block text-sm font-medium text-muted-foreground mb-1">Unit Coretax</label>
                        <input type="text" name="unit_coretax" id="form_unit_coretax" maxlength="50" required placeholder="UM.0022"
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('unit_form_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="delete_unit_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Mapping?</h3>
            <p class="py-3 text-sm text-muted-foreground">
                Yakin hapus mapping <strong id="delete_unit_label">-</strong>?
            </p>
            <div class="modal-action">
                <form id="delete_unit_form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error">Ya, Hapus</button>
                </form>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('delete_unit_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="import_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Import Mapping Unit</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload file Excel (.xlsx/.xls). Kolom: Unit Internal | Unit Coretax.</p>
            <form action="{{ route('mapping-units.import') }}" method="POST" enctype="multipart/form-data">
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

    <dialog id="clear_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Semua Data?</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Semua data Mapping Unit ({{ number_format($mappingUnits->count()) }} baris) akan dihapus permanen.
            </p>
            <div class="modal-action">
                <form action="{{ route('mapping-units.clear') }}" method="POST">
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
        var actionRow = null;

        function openCreateModal() {
            var form = document.getElementById('unit_form');
            form.reset();
            form.action = @json(route('mapping-units.store'));
            document.getElementById('unit_form_method').innerHTML = '';
            document.getElementById('unit_form_title').textContent = 'Tambah Mapping Unit';
            document.getElementById('unit_form_modal').showModal();
        }

        function openActionMenu(btn) {
            var tr = btn.closest('tr');
            actionRow = {
                id: tr.getAttribute('data-row-id'),
                unit_internal: tr.getAttribute('data-unit-internal'),
                unit_coretax: tr.getAttribute('data-unit-coretax')
            };

            var menu = document.getElementById('action_menu');
            menu.classList.remove('hidden');
            var rect = btn.getBoundingClientRect();
            var menuW = menu.offsetWidth, menuH = menu.offsetHeight;
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

        function openEditFromMenu() {
            if (!actionRow) return;
            var row = actionRow;
            closeActionMenu();
            var form = document.getElementById('unit_form');
            form.reset();
            form.action = @json(route('mapping-units.update', ['mappingUnit' => '__ID__'])).replace('__ID__', row.id);
            document.getElementById('unit_form_method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('unit_form_title').textContent = 'Edit Mapping Unit';
            document.getElementById('form_unit_internal').value = row.unit_internal || '';
            document.getElementById('form_unit_coretax').value = row.unit_coretax || '';
            document.getElementById('unit_form_modal').showModal();
        }

        function openDeleteFromMenu() {
            if (!actionRow) return;
            var row = actionRow;
            closeActionMenu();
            document.getElementById('delete_unit_label').textContent = row.unit_internal + ' → ' + row.unit_coretax;
            document.getElementById('delete_unit_form').action =
                @json(route('mapping-units.destroy', ['mappingUnit' => '__ID__'])).replace('__ID__', row.id);
            document.getElementById('delete_unit_modal').showModal();
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

            $('#mapping-units-table').DataTable({
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

            $('#mapping-units-table').on('draw.dt', function() { closeActionMenu(); });
        });
    </script>
    @endpush
</x-layouts.app>
