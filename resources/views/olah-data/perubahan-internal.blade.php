<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Perubahan Data {{ ucfirst($tab) }}" description="Riwayat upload dan pelacakan perubahan data ({{ $tab }})">
            <button type="button"
                    onclick="document.getElementById('clear_history_modal').showModal()"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-destructive px-3 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Clear Data
            </button>
        </x-page-header>
    </x-slot>

    {{-- Tabs Internal / External --}}
    <div class="mb-4 flex gap-1 rounded-lg border border-border bg-card p-1 w-fit">
        <a href="{{ route('olah-data.perubahan-data-internal', ['tab' => 'internal']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'internal' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
            Internal
        </a>
        <a href="{{ route('olah-data.perubahan-data-internal', ['tab' => 'external']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'external' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
            External
        </a>
    </div>

    @if (session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if ($selected === null)
        <x-alert type="info" class="mb-4">
            Belum ada riwayat upload untuk tab <strong>{{ $tab }}</strong>. Setiap <strong>Import</strong> berikutnya akan otomatis tercatat di sini — baris baru, yang nilainya berubah, dan yang tetap.
        </x-alert>
    @else
        {{-- Ringkasan sesi terpilih --}}
        <x-card class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-border p-4">
                <div>
                    <p class="text-sm font-semibold text-foreground">{{ $selected->file_name ?? 'Tanpa nama file' }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Diunggah {{ $selected->created_at->format('d/m/Y H:i') }}
                        @if ($selected->user)
                            oleh {{ $selected->user->name }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="inline-flex items-center rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-semibold text-success">{{ $selected->new_count }} Baru</span>
                    <span class="inline-flex items-center rounded-full bg-warning/10 px-2.5 py-0.5 text-xs font-semibold text-warning">{{ $selected->changed_count }} Berubah</span>
                    <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold text-foreground">{{ $selected->unchanged_count }} Tetap</span>
                    @if ($selected->skipped_count > 0)
                        <span class="inline-flex items-center rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-semibold text-destructive">{{ $selected->skipped_count }} Dilewati</span>
                    @endif
                </div>
            </div>
        </x-card>
    @endif

    {{-- Riwayat upload --}}
    @if ($sessions->isNotEmpty())
        <x-card class="mb-4">
            <div class="border-b border-border px-4 py-3">
                <h3 class="text-sm font-semibold text-foreground">Riwayat Upload</h3>
                <p class="text-xs text-muted-foreground">Klik salah satu baris untuk melihat detail sesi tersebut.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead class="border-b border-border">
                        <tr>
                            <th class="h-10 px-4 text-left text-xs font-medium uppercase text-muted-foreground">Waktu</th>
                            <th class="h-10 px-4 text-left text-xs font-medium uppercase text-muted-foreground">File</th>
                            <th class="h-10 px-4 text-right text-xs font-medium uppercase text-muted-foreground">Baru</th>
                            <th class="h-10 px-4 text-right text-xs font-medium uppercase text-muted-foreground">Berubah</th>
                            <th class="h-10 px-4 text-right text-xs font-medium uppercase text-muted-foreground">Tetap</th>
                            <th class="h-10 px-4 text-right text-xs font-medium uppercase text-muted-foreground">Dilewati</th>
                            <th class="h-10 px-4 text-center text-xs font-medium uppercase text-muted-foreground">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr class="border-b border-border transition-colors {{ $selected && $selected->id === $session->id ? 'bg-accent font-medium' : 'hover:bg-accent/50' }}">
                                <td class="px-4 py-2">
                                    <a href="{{ route('olah-data.perubahan-data-internal', ['tab' => $tab, 'session' => $session->id]) }}" class="text-primary hover:underline">
                                        {{ $session->created_at->format('d/m/Y H:i') }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-foreground">
                                    <a href="{{ route('olah-data.perubahan-data-internal', ['tab' => $tab, 'session' => $session->id]) }}" class="hover:text-primary">{{ $session->file_name ?? '-' }}</a>
                                    @if ($session->import_type === \App\Models\ImportSession::TYPE_RECAP)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-info/10 px-2 py-0.5 text-xs font-semibold text-info">Recap</span>
                                    @else
                                        <span class="ml-2 inline-flex items-center rounded-full border border-border px-2 py-0.5 text-xs font-semibold text-muted-foreground">Sales</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right font-medium text-success">{{ $session->new_count }}</td>
                                <td class="px-4 py-2 text-right font-medium text-warning">{{ $session->changed_count }}</td>
                                <td class="px-4 py-2 text-right text-muted-foreground">{{ $session->unchanged_count }}</td>
                                <td class="px-4 py-2 text-right text-destructive">{{ $session->skipped_count ?: '-' }}</td>
                                <td class="px-4 py-2 text-center">
                                    <form action="{{ route('olah-data.perubahan-data-internal.destroy', $session) }}" method="POST"
                                          onsubmit="return confirm('Hapus riwayat sesi ini? Detail sesi-nya ikut terhapus. Data tidak terpengaruh.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-destructive hover:bg-destructive/10"
                                                title="Hapus riwayat sesi" aria-label="Hapus riwayat sesi">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif

    {{-- Detail sesi terpilih --}}
    @if ($selected !== null)
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-3">
                <div>
                    <h3 class="text-sm font-semibold text-foreground">Detail Invoice</h3>
                    <p class="text-xs text-muted-foreground">{{ $details->count() }} baris tercatat pada sesi ini</p>
                </div>
                @if (count($payload) > 0)
                    <div id="filter_pills" class="flex flex-wrap gap-1.5 text-xs font-medium">
                        <button type="button" data-status="all"
                                class="filter-pill rounded-full border border-primary bg-primary px-3 py-1 text-primary-foreground">
                            Semua ({{ count($payload) }})
                        </button>
                        <button type="button" data-status="new"
                                class="filter-pill rounded-full border border-border px-3 py-1 text-muted-foreground hover:bg-accent hover:text-foreground">
                            Baru ({{ $selected->new_count }})
                        </button>
                        <button type="button" data-status="changed"
                                class="filter-pill rounded-full border border-border px-3 py-1 text-muted-foreground hover:bg-accent hover:text-foreground">
                            Berubah ({{ $selected->changed_count }})
                        </button>
                        <button type="button" data-status="unchanged"
                                class="filter-pill rounded-full border border-border px-3 py-1 text-muted-foreground hover:bg-accent hover:text-foreground">
                            Tetap ({{ $selected->unchanged_count }})
                        </button>
                    </div>
                @endif
            </div>

            @if (count($payload) === 0)
                <div class="p-8 text-center text-sm text-muted-foreground">
                    Tidak ada baris detail pada sesi ini @if (($selected->skipped_count ?? 0) > 0) (semua baris dilewati) @endif.
                </div>
            @else
                <div class="table-container w-full overflow-x-auto overflow-y-hidden">
                    <table id="upload-detail-table" class="w-full min-w-[900px] caption-bottom text-sm whitespace-nowrap">
                        <thead class="border-b border-border">
                            <tr>
                                <th class="h-10 px-3 text-left font-medium text-muted-foreground">Status</th>
                                <th class="h-10 px-3 text-left font-medium text-muted-foreground">No. / Kode</th>
                                <th class="h-10 px-3 text-left font-medium text-muted-foreground">Tanggal</th>
                                <th class="h-10 px-3 text-left font-medium text-muted-foreground">Customer</th>
                                <th class="h-10 px-3 text-left font-medium text-muted-foreground">Nilai</th>
                                <th class="h-10 px-3 text-left font-medium text-muted-foreground">Perubahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Baris dirender DataTables dari DS_DATA (deferRender) --}}
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    @endif

    {{-- Clear Riwayat Modal (password protected) --}}
    <dialog id="clear_history_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Hapus Semua Riwayat Upload?</h3>
            <p class="py-2 text-sm text-muted-foreground">
                Semua riwayat upload beserta detail invoice-nya akan dihapus permanen.
                Data Detail Sales <strong>tidak</strong> terpengaruh.
            </p>
            <form action="{{ route('olah-data.perubahan-data-internal.clear') }}" method="POST" class="pt-2">
                @csrf
                @method('DELETE')
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label for="clear_history_password" class="text-sm font-medium text-foreground">Password</label>
                <input type="password" id="clear_history_password" name="password" required
                       autocomplete="current-password" placeholder="Masukkan password"
                       class="mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                <div class="modal-action">
                    <button type="submit"
                            class="inline-flex min-h-9 items-center rounded-lg bg-destructive px-4 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        Ya, Hapus
                    </button>
                    <button type="button"
                            class="inline-flex min-h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            onclick="document.getElementById('clear_history_modal').close()">
                        Batal
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    @if (count($payload) > 0)
        @push('scripts')
            <script>
                function formatRp(value) {
                    var n = Number(value || 0);
                    return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
                }

                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                var DS_DATA = @json($payload);
                var STATUS_FILTER = 'all';

                var BADGE_BASE = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ';
                function statusBadge(type) {
                    if (type === 'new') return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">Baru</span>';
                    if (type === 'changed') return '<span class="' + BADGE_BASE + 'bg-warning/10 text-warning">Berubah</span>';
                    return '<span class="' + BADGE_BASE + 'border border-border text-foreground">Tetap</span>';
                }
                function statusLabel(type) {
                    if (type === 'new') return 'Baru';
                    if (type === 'changed') return 'Berubah';
                    return 'Tetap';
                }

                var uploadTable = $('#upload-detail-table').DataTable({
                    data: DS_DATA,
                    deferRender: true,
                    pageLength: 25,
                    order: [],
                    columns: [
                        { data: 'type', className: 'px-3 py-1.5',
                            render: function (v, type) {
                                if (type !== 'display') return statusLabel(v);
                                return statusBadge(v);
                            } },
                        { data: 'doc', className: 'px-3 py-1.5 text-foreground',
                            render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                        { data: 'date', className: 'px-3 py-1.5 text-muted-foreground',
                            render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                        { data: 'customer', className: 'px-3 py-1.5 max-w-[180px] truncate text-foreground',
                            render: function (v, type) { v = v || ''; if (type !== 'display') return v; return v === '' ? '-' : '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>'; } },
                        { data: 'invoice_amount', className: 'px-3 py-1.5 text-right text-foreground',
                            render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                        { data: 'diff', className: 'px-3 py-1.5 max-w-[320px] whitespace-normal text-muted-foreground',
                            render: function (v, type) {
                                var arr = v || [];
                                if (type !== 'display') return arr.join(' | ');
                                if (!arr.length) return '-';
                                return arr.map(function (line) { return escapeHtml(line); }).join('<br>');
                            } }
                    ],
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

                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    if (settings.nTable.id !== 'upload-detail-table') return true;
                    if (STATUS_FILTER === 'all') return true;
                    var row = uploadTable.row(dataIndex).data();
                    return !!row && row.type === STATUS_FILTER;
                });

                document.querySelectorAll('.filter-pill').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        STATUS_FILTER = btn.getAttribute('data-status');
                        document.querySelectorAll('.filter-pill').forEach(function (b) {
                            var active = b === btn;
                            b.classList.toggle('border-primary', active);
                            b.classList.toggle('bg-primary', active);
                            b.classList.toggle('text-primary-foreground', active);
                            b.classList.toggle('border-border', !active);
                            b.classList.toggle('text-muted-foreground', !active);
                        });
                        uploadTable.draw();
                    });
                });
            </script>
        @endpush
    @endif
</x-layouts.app>
