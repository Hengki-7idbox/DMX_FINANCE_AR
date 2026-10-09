<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Mapping Product" description="Pemetaan produk antar sistem/sumber data">
            <x-button onclick="openCreateModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Data
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
        Import <strong>item_mapping.xlsx</strong> &mdash; kolom ID, Item Code Internal, Item Code external, Code Coretax, Nama Barang Internal, Nama Barang external.
        Aksi <strong>Edit</strong> / <strong>Hapus</strong> ada di menu titik-tiga pada kolom Aksi.
    </x-alert>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="mapping-products-table" class="w-full min-w-[1000px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">ID</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kode Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Kode External</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Code Coretax</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nama Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nama External</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappingProducts as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50"
                            data-row-id="{{ $row->id }}"
                            data-source-id="{{ $row->source_id }}"
                            data-kode-internal="{{ $row->kode_item_internal }}"
                            data-kode-external="{{ $row->kode_item_external }}"
                            data-code-coretax="{{ $row->code_coretax }}"
                            data-nama-internal="{{ $row->nama_item_internal }}"
                            data-nama-external="{{ $row->nama_item_external }}">
                            <td class="px-2 py-1.5 sticky left-0 bg-card z-10">
                                <button type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30 hover:text-info"
                                        data-id="{{ $row->id }}"
                                        onclick="openActionMenu(this)"
                                        aria-label="Action">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row->source_id ?? '-' }}</td>
                            <td class="editable-cell px-2 py-1.5 font-medium text-foreground cursor-pointer" data-field="kode_item_internal" title="Dblclick untuk edit">{{ $row->kode_item_internal }}</td>
                            <td class="editable-cell px-2 py-1.5 font-medium text-foreground cursor-pointer" data-field="kode_item_external" title="Dblclick untuk edit">{{ $row->kode_item_external }}</td>
                            <td class="editable-cell px-2 py-1.5 text-muted-foreground cursor-pointer" data-field="code_coretax" title="Dblclick untuk edit">{{ $row->code_coretax ?: '-' }}</td>
                            <td class="editable-cell px-2 py-1.5 text-foreground max-w-[260px] truncate cursor-pointer" data-field="nama_item_internal" title="Dblclick untuk edit">{{ $row->nama_item_internal }}</td>
                            <td class="editable-cell px-2 py-1.5 text-foreground max-w-[260px] truncate cursor-pointer" data-field="nama_item_external" title="Dblclick untuk edit">{{ $row->nama_item_external }}</td>
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
        @if($mappingProducts->isEmpty())
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada data mapping product. Menunggu import Excel.
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

    {{-- Create / Edit Mapping Product Modal --}}
    <dialog id="mapping_form_modal" class="modal">
        <div class="modal-box">
            <h3 id="mapping_form_title" class="font-bold text-lg text-foreground">Tambah Data</h3>
            <p class="py-1 text-sm text-muted-foreground">Item Code Internal &harr; Item Code External</p>

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
                        <label for="form_kode_internal" class="block text-sm font-medium text-muted-foreground mb-1">Item Code Internal</label>
                        <input type="text" name="kode_item_internal" id="form_kode_internal" maxlength="100" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
                    </div>
                    <div>
                        <label for="form_kode_external" class="block text-sm font-medium text-muted-foreground mb-1">Item Code External</label>
                        <input type="text" name="kode_item_external" id="form_kode_external" maxlength="100" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
                    </div>
                    <div>
                        <label for="form_code_coretax" class="block text-sm font-medium text-muted-foreground mb-1">Code Coretax <span class="text-tertiary">(opsional)</span></label>
                        <input type="text" name="code_coretax" id="form_code_coretax" maxlength="100"
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
                    </div>
                    <div>
                        <label for="form_nama_internal" class="block text-sm font-medium text-muted-foreground mb-1">Nama Barang Internal</label>
                        <input type="text" name="nama_item_internal" id="form_nama_internal" maxlength="255" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
                    </div>
                    <div>
                        <label for="form_nama_external" class="block text-sm font-medium text-muted-foreground mb-1">Nama Barang External</label>
                        <input type="text" name="nama_item_external" id="form_nama_external" maxlength="255" required
                               class="block w-full rounded-lg border border-border bg-input px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                        <p class="form-error mt-1 text-sm text-destructive hidden"></p>
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

    {{-- Delete Mapping Product Modal --}}
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
            <h3 class="font-bold text-lg text-foreground">Import Mapping Product</h3>
            <p class="py-2 text-sm text-muted-foreground">Upload file <strong>item_mapping.xlsx</strong> (.xlsx/.xls). Kolom: ID, Item Code Internal, Item Code external, Code Coretax, Nama Barang Internal, Nama Barang external.</p>

            <form action="{{ route('mapping-products.import') }}" method="POST" enctype="multipart/form-data">
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
                Semua data Mapping Product ({{ number_format($mappingProducts->count()) }} baris) akan dihapus permanen.
            </p>
            <div class="modal-action">
                <form action="{{ route('mapping-products.clear') }}" method="POST">
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
            form.action = @json(route('mapping-products.store'));
            document.getElementById('mapping_form_method').innerHTML = '';
            document.getElementById('mapping_form_title').textContent = 'Tambah Data';
            clearFormErrors();
            document.getElementById('mapping_form_modal').showModal();
        }

        function openActionMenu(btn) {
            var tr = btn.closest('tr');
            actionRow = {
                id: tr.getAttribute('data-row-id'),
                source_id: tr.getAttribute('data-source-id'),
                kode_internal: tr.getAttribute('data-kode-internal'),
                kode_external: tr.getAttribute('data-kode-external'),
                code_coretax: tr.getAttribute('data-code-coretax'),
                nama_internal: tr.getAttribute('data-nama-internal'),
                nama_external: tr.getAttribute('data-nama-external')
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
            form.action = @json(route('mapping-products.update', ['mappingProduct' => '__ID__'])).replace('__ID__', row.id);
            document.getElementById('mapping_form_method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('mapping_form_title').textContent = 'Edit Data';
            clearFormErrors();

            document.getElementById('form_source_id').value = row.source_id || '';
            document.getElementById('form_kode_internal').value = row.kode_internal || '';
            document.getElementById('form_kode_external').value = row.kode_external || '';
            document.getElementById('form_code_coretax').value = row.code_coretax || '';
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
                @json(route('mapping-products.destroy', ['mappingProduct' => '__ID__'])).replace('__ID__', row.id);

            document.getElementById('delete_mapping_modal').showModal();
        }

        // ==== Inline edit (dblclick pada sel data) ====
        var CSRF_TOKEN = @json(csrf_token());
        var UPDATE_URL_TEMPLATE = @json(route('mapping-products.update', ['mappingProduct' => '__ID__']));
        var FIELD_ATTR = {
            source_id: 'data-source-id',
            kode_item_internal: 'data-kode-internal',
            kode_item_external: 'data-kode-external',
            code_coretax: 'data-code-coretax',
            nama_item_internal: 'data-nama-internal',
            nama_item_external: 'data-nama-external'
        };

        function fieldValue(tr, field) {
            return tr.getAttribute(FIELD_ATTR[field]) || '';
        }

        function renderCellText(td, field, value) {
            td.textContent = (field === 'code_coretax' && !value) ? '-' : value;
        }

        function showInlineToast(type, message) {
            var toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 z-[110] max-w-sm rounded-lg border px-4 py-3 text-sm font-medium shadow-xl ' +
                (type === 'success'
                    ? 'border-success/40 bg-success/10 text-success'
                    : 'border-destructive/40 bg-destructive/10 text-destructive');
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function () { toast.remove(); }, type === 'success' ? 2000 : 5000);
        }

        function flashCellSuccess(td) {
            td.classList.add('bg-success/10', 'ring-1', 'ring-success/50');
            setTimeout(function () {
                td.classList.remove('bg-success/10', 'ring-1', 'ring-success/50');
            }, 1200);
        }

        function startCellEdit(td) {
            if (td.getAttribute('data-editing') === '1') return;
            var tr = td.closest('tr');
            if (!tr || !tr.getAttribute('data-row-id')) return;
            var field = td.getAttribute('data-field');
            var current = fieldValue(tr, field);

            td.setAttribute('data-editing', '1');
            closeActionMenu();
            td.removeAttribute('title');
            td.classList.remove('truncate');

            var input = document.createElement('input');
            input.type = 'text';
            input.value = current;
            input.className = 'block w-full rounded-lg border border-border bg-input px-2 py-1 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring';
            if (field === 'code_coretax') input.placeholder = '(opsional)';

            td.textContent = '';
            td.appendChild(input);
            input.focus();
            input.select();

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); saveCell(td, input); }
                else if (e.key === 'Escape') { e.preventDefault(); cancelCell(td, input, field); }
            });
            input.addEventListener('blur', function () {
                if (td.getAttribute('data-editing') === '1' && td.getAttribute('data-saving') !== '1') {
                    saveCell(td, input);
                }
            });
        }

        function cancelCell(td, input, field) {
            td.removeAttribute('data-editing');
            td.removeAttribute('data-saving');
            var tr = td.closest('tr');
            var current = tr ? fieldValue(tr, field) : '';
            renderCellText(td, field, current);
            td.setAttribute('title', 'Dblclick untuk edit');
            if (field === 'nama_item_internal' || field === 'nama_item_external') td.classList.add('truncate');
        }

        function saveCell(td, input) {
            if (td.getAttribute('data-editing') !== '1' || td.getAttribute('data-saving') === '1') return;
            var tr = td.closest('tr');
            var field = td.getAttribute('data-field');
            var current = fieldValue(tr, field);
            var newValue = input.value;

            if (newValue === current) { cancelCell(td, input, field); return; }

            var errBox = td.querySelector('.inline-error');
            if (errBox) errBox.remove();

            td.setAttribute('data-saving', '1');
            input.disabled = true;

            var body = new URLSearchParams();
            body.append('_token', CSRF_TOKEN);
            body.append('_method', 'PUT');
            Object.keys(FIELD_ATTR).forEach(function (f) {
                body.append(f, f === field ? newValue : fieldValue(tr, f));
            });

            fetch(UPDATE_URL_TEMPLATE.replace('__ID__', tr.getAttribute('data-row-id')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: body.toString()
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (j) {
                    if (!res.ok) {
                        var msg = null;
                        if (j.errors) {
                            Object.keys(j.errors).some(function (k) { msg = j.errors[k][0]; return true; });
                        }
                        throw new Error(msg || ('Gagal menyimpan (HTTP ' + res.status + ')'));
                    }
                    return j;
                });
            }).then(function () {
                td.removeAttribute('data-editing');
                td.removeAttribute('data-saving');
                tr.setAttribute(FIELD_ATTR[field], newValue);
                renderCellText(td, field, newValue);
                td.setAttribute('title', 'Dblclick untuk edit');
                if (field === 'nama_item_internal' || field === 'nama_item_external') td.classList.add('truncate');
                flashCellSuccess(td);
                showInlineToast('success', 'Perubahan tersimpan');
                try { $('#mapping-products-table').DataTable().cell(td).invalidate(); } catch (e) {}
            }).catch(function (err) {
                td.removeAttribute('data-saving');
                input.disabled = false;
                var msg = (err && err.message) ? err.message : 'Gagal menyimpan perubahan';
                var box = document.createElement('div');
                box.className = 'inline-error mt-1 text-xs text-destructive';
                box.textContent = msg;
                td.appendChild(box);
                showInlineToast('error', msg);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('dblclick', function(e) {
                var td = e.target.closest('td.editable-cell');
                if (!td) return;
                e.preventDefault();
                startCellEdit(td);
            });
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

            $('#mapping-products-table').DataTable({
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

            $('#mapping-products-table').on('draw.dt', function() {
                closeActionMenu();
            });
        });
    </script>
    @endpush
</x-layouts.app>
