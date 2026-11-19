<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Mapping Unit" description="Pemetaan satuan ukur Accurate Online → kode unit Coretax (UM.xxxx)">
            <x-button variant="outline" onclick="refreshUnits()">Refresh dari Accurate</x-button>
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

    <div id="save_error_banner"
         class="mb-4 hidden rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
        <div class="flex items-start gap-3">
            <span id="save_error_text" class="flex-1"></span>
            <button type="button" onclick="window.location.reload()"
                    class="shrink-0 font-semibold underline underline-offset-2">Muat ulang</button>
        </div>
    </div>

    @if($accurateError)
        <x-alert type="warning" class="mb-4">
            Gagal memuat daftar unit dari Accurate Online: {{ $accurateError }}
            <br>Daftar di bawah diambil dari cache/data lokal. Coba <strong>Refresh dari Accurate</strong> lagi.
        </x-alert>
    @endif

    @if($unmappedCount > 0)
        <x-alert type="warning" class="mb-4">
            <strong>{{ number_format($unmappedCount) }} unit belum dipetakan.</strong>
            Unit tersebut akan memakai kode default <em>{{ \App\Models\Setting::get('coretax_unit_default', 'UM.0022') }}</em> saat export XML Coretax.
        </x-alert>
    @endif

    <x-alert type="info" class="mb-4">
        Daftar unit diambil langsung dari master <strong>Accurate Online</strong>. Pilih kode unit <strong>Coretax</strong> pada dropdown di kanan —
        perubahan langsung tersimpan. Dipakai export XML Coretax untuk mengisi tag <strong>Unit</strong>;
        bila belum dipilih, dipakai default dari Settings (<em>UM.0022</em>).
    </x-alert>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="mapping-units-table" class="w-full min-w-[760px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Unit Accurate</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">ID</th>
                        <th class="h-10 px-2 text-right font-medium text-muted-foreground">Dipakai</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Unit Coretax</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50">
                            <td class="px-2 py-1.5 font-medium text-foreground">
                                {{ $row['name'] }}
                                @unless($row['in_accurate'])
                                    <span class="ml-1 inline-flex items-center rounded-full bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning"
                                          title="Unit ini muncul di data transaksi tetapi tidak ada di master Accurate Online">
                                        tidak ada di Accurate
                                    </span>
                                @endunless
                                <span data-unmapped-badge="{{ $row['name'] }}"
                                      class="ml-1 inline-flex items-center rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive {{ empty($row['mapped']) ? '' : 'hidden' }}"
                                      title="Unit ini belum dipetakan; export XML Coretax akan memakai kode default">
                                    belum dipetakan
                                </span>
                            </td>
                            <td class="px-2 py-1.5 text-muted-foreground">{{ $row['id'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums text-foreground">{{ number_format($row['usage'], 0, ',', '.') }}</td>
                            <td class="px-2 py-1.5">
                                <div class="flex items-center gap-2">
                                    <select class="coretax-unit-select h-8 min-w-[220px] rounded-md border border-border bg-background px-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                            data-unit="{{ $row['name'] }}"
                                            data-saved="{{ $row['mapped'] ?? '' }}"
                                            aria-label="Unit Coretax untuk {{ $row['name'] }}">
                                        <option value="">— belum dipilih —</option>
                                        @foreach($coretaxUnits as $code => $label)
                                            <option value="{{ $code }}" @selected(($row['mapped'] ?? '') === $code)>
                                                {{ $code }} — {{ $label }}
                                            </option>
                                        @endforeach
                                        @if(!empty($row['mapped']) && ! array_key_exists($row['mapped'], $coretaxUnits))
                                            <option value="{{ $row['mapped'] }}" selected>{{ $row['mapped'] }} — kode tidak dikenal</option>
                                        @endif
                                    </select>
                                    <span class="save-state text-xs text-muted-foreground"></span>
                                </div>
                            </td>
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
        @if(empty($rows))
            <div class="p-4 text-center text-sm text-muted-foreground border-t border-border">
                Belum ada unit yang bisa ditampilkan. Coba <strong>Refresh dari Accurate</strong>.
            </div>
        @endif
    </x-card>

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
                Semua data Mapping Unit ({{ number_format($mappedCount) }} baris terisi) akan dihapus permanen.
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
        function refreshUnits() {
            window.location.href = @json(route('mapping-units.index')) + '?refresh=1';
        }

        // Halaman bisa dipulihkan dari cache browser (tombol Back / bfcache).
        // Muat ulang agar nilai dropdown selalu sesuai data terbaru di server.
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            var SAVE_URL = @json(route('mapping-units.quick-save'));
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

            document.querySelectorAll('.coretax-unit-select').forEach(function(sel) {
                sel.addEventListener('change', function() {
                    saveUnitMapping(sel);
                });
            });

            function saveUnitMapping(sel) {
                var unit = sel.getAttribute('data-unit') || '';
                var value = sel.value || '';
                var state = sel.parentElement.querySelector('.save-state');
                var previous = sel.getAttribute('data-saved') || '';

                sel.disabled = true;
                setState(state, 'Menyimpan...', 'text-muted-foreground');

                fetch(SAVE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({ unit_internal: unit, unit_coretax: value })
                }).then(function(res) {
                    return res.text().then(function(text) {
                        var body = {};
                        try { body = JSON.parse(text); } catch (e) { body = {}; }
                        return { status: res.status, ok: res.ok, body: body };
                    });
                }).then(function(res) {
                    if (!res.ok || !res.body.ok) {
                        var errors = res.body.errors || {};
                        var detail = res.body.message
                            || (errors.unit_coretax && errors.unit_coretax[0])
                            || (res.status === 419
                                ? 'Sesi Anda sudah berakhir.'
                                : (res.status === 200
                                    ? 'Sesi mungkin sudah berakhir atau server menolak permintaan.'
                                    : 'Gagal menyimpan.'));
                        var err = new Error(detail);
                        err.status = res.status;
                        throw err;
                    }

                    // Server mengembalikan nama kanonik (mis. "CRT" tersimpan sebagai "Crt").
                    var canonical = res.body.unit_internal || unit;
                    sel.setAttribute('data-unit', canonical);
                    sel.setAttribute('data-saved', value);
                    setState(state, value === '' ? 'Dikosongkan' : 'Tersimpan', 'text-success');
                    syncSiblings(canonical, value);
                    hideSaveError();
                }).catch(function(err) {
                    sel.value = previous;
                    setState(state, 'Gagal menyimpan', 'text-destructive');
                    showSaveError(
                        'Unit "' + unit + '" gagal disimpan'
                        + (err.status ? ' (' + err.status + ')' : '')
                        + ': ' + (err.message || 'tidak diketahui')
                        + ' Muat ulang halaman lalu coba lagi.'
                    );
                }).finally(function() {
                    sel.disabled = false;
                });
            }

            // "Crt" dan "CRT" (juga "Kg"/"KG", "pac"/"Pac") adalah satu baris yang
            // sama di database. Samakan tampilannya tanpa perlu reload.
            function syncSiblings(canonical, value) {
                var key = canonical.toLowerCase();

                document.querySelectorAll('.coretax-unit-select').forEach(function(other) {
                    var name = (other.getAttribute('data-unit') || '').toLowerCase();
                    if (name !== key) return;

                    other.value = value;
                    other.setAttribute('data-saved', value);

                    var state = other.parentElement.querySelector('.save-state');
                    if (state) setState(state, value === '' ? 'Dikosongkan' : 'Tersimpan', 'text-success');

                    var row = other.closest('tr');
                    var badge = row ? row.querySelector('[data-unmapped-badge]') : null;
                    if (badge) badge.classList.toggle('hidden', value !== '');
                });
            }

            function showSaveError(message) {
                var banner = document.getElementById('save_error_banner');
                var text = document.getElementById('save_error_text');
                if (!banner || !text) return;
                text.textContent = message;
                banner.classList.remove('hidden');
            }

            function hideSaveError() {
                var banner = document.getElementById('save_error_banner');
                if (banner) banner.classList.add('hidden');
            }

            function setState(el, text, cls) {
                if (!el) return;
                el.textContent = text;
                el.className = 'save-state text-xs ' + cls;
            }

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
        });
    </script>
    @endpush
</x-layouts.app>
