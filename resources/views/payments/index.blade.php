<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Pembayaran" description="Pelunasan invoice internal — upload XML, bandingkan, dan bayar via web">
            <x-button onclick="document.getElementById('upload_xml_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload XML Pelunasan
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
        Upload <strong>XML pelunasan internal/external</strong> (format Accurate NMEXML) — upload ulang menggantikan seluruh data tipe itu.
        Status <strong>Cocok</strong> = total bayar <strong>XML</strong> = <strong>Payment (Accurate)</strong> — pembayaran tercatat Accurate dari detail-sales (bukan nilai invoice). Tombol <strong>Pay</strong> hanya untuk invoice belum lunas (owing &gt; 0).
    </x-alert>

    {{-- Info upload terakhir --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        @foreach (['internal' => $uploads['internal'], 'external' => $uploads['external']] as $tipe => $up)
        <div class="rounded-lg border border-border bg-card p-3">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-foreground">XML Pelunasan {{ ucfirst($tipe) }}</span>
                @if($up)
                <span class="inline-flex items-center rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-semibold text-success">Aktif</span>
                @else
                <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">Belum ada</span>
                @endif
            </div>
            @if($up)
            <div class="mt-1 text-xs text-muted-foreground">
                {{ $up->file_name }} · {{ $up->receipt_count }} receipt · {{ $up->line_count }} baris · {{ $up->created_at->format('d/m/Y H:i') }}
            </div>
            @else
            <div class="mt-1 text-xs text-muted-foreground">Belum ada file XML {{ $tipe }} yang diupload.</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Ringkasan compare internal vs external --}}
    <div class="mb-4 rounded-lg border border-border bg-card p-3">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <span class="text-sm font-semibold text-foreground">Compare Invoice Internal ↔ External</span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="inline-flex items-center rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-semibold text-success">Match</span>
                <span class="text-muted-foreground">{{ number_format($compare['match']) }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="inline-flex items-center rounded-full bg-warning/10 px-2.5 py-0.5 text-xs font-semibold text-warning">Beda Nilai</span>
                <span class="text-muted-foreground">{{ number_format($compare['beda_nilai']) }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">Hanya Internal</span>
                <span class="text-muted-foreground">{{ number_format($compare['hanya_internal']) }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 text-sm">
                <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">Hanya External</span>
                <span class="text-muted-foreground">{{ number_format($compare['hanya_external']) }}</span>
            </span>
            <span class="text-xs text-muted-foreground">Match = (nilai invoice − retur) internal = nilai invoice external, atau external termasuk PPN 11% (ext ÷ 1.11), toleransi Rp 1.</span>
        </div>
    </div>

    {{-- Filter tanggal + bulk export --}}
    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-card p-3">
        <form method="GET" action="{{ route('payments.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-40">
                <label for="f_dari" class="mb-1 block text-xs font-medium text-muted-foreground">Tanggal Dari</label>
                <input type="date" id="f_dari" name="dari" value="{{ $dari }}"
                       class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="w-40">
                <label for="f_sampai" class="mb-1 block text-xs font-medium text-muted-foreground">Tanggal Sampai</label>
                <input type="date" id="f_sampai" name="sampai" value="{{ $sampai }}"
                       class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div>
                <span class="mb-1 block text-xs invisible select-none" aria-hidden="true">Terapkan</span>
                <button type="submit" class="min-h-9 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90">Terapkan</button>
            </div>
        </form>
        <div class="ml-auto flex items-end gap-3">
            <span id="export_count" class="hidden min-h-9 items-center text-sm font-medium text-foreground">0 data dipilih</span>
            <button type="button" id="bulk_export_internal" disabled
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-50">
                Export XML Internal
            </button>
            <button type="button" id="bulk_export_external" disabled
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-50">
                Export XML External
            </button>
        </div>
    </div>

    <x-card>
        <div class="table-container w-full overflow-x-auto overflow-y-hidden">
            <table id="payments-table" class="w-full min-w-[1100px] caption-bottom text-sm whitespace-nowrap">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Aksi</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">
                            <input type="checkbox" id="select_all_rows"
                                   class="h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                   aria-label="Pilih semua baris" />
                        </th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice No.</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Tanggal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Customer</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Nilai Invoice</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Invoice External</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">PPN External</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Payment (Accurate)</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Retur</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Owing</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status Pelunasan Internal</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Status Pelunasan External</th>
                        <th class="h-10 px-2 text-left font-medium text-muted-foreground">Match Int↔Ext</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

    {{-- Action menu --}}
    <div id="action_menu" class="hidden fixed z-[100] w-56 rounded-lg border border-border bg-card p-1 shadow-xl">
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" onclick="detailFromMenu()">
            <svg class="h-4 w-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Lihat Detail Pelunasan
        </button>
        <button type="button" id="menu_pay_btn" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" onclick="payFromMenu()">
            <svg class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Pay (bayar via web)
        </button>
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" onclick="exportFromMenu('internal')">
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Export XML Internal
        </button>
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground" onclick="exportFromMenu('external')">
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Export XML External
        </button>
    </div>

    {{-- Upload XML Modal --}}
    <dialog id="upload_xml_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Upload XML Pelunasan</h3>
            <p class="py-2 text-sm text-muted-foreground">
                File XML Accurate (NMEXML / CUSTOMERRECEIPT). Upload ulang <strong>menggantikan</strong> seluruh data pelunasan tipe yang dipilih.
            </p>
            <form action="{{ route('payments.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="py-2">
                    <label class="block text-sm font-medium text-muted-foreground mb-2">Tipe</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-foreground">
                            <input type="radio" name="source_type" value="internal" checked class="text-primary focus-visible:ring-ring" /> Internal
                        </label>
                        <label class="flex items-center gap-2 text-sm text-foreground">
                            <input type="radio" name="source_type" value="external" class="text-primary focus-visible:ring-ring" /> External
                        </label>
                    </div>
                </div>
                <div class="py-4">
                    <label for="file_xml" class="block text-sm font-medium text-muted-foreground mb-2">File XML</label>
                    <input type="file" id="file_xml" name="file" accept=".xml" required
                           class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer" />
                    @error('file')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('upload_xml_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- Detail Pelunasan Modal --}}
    <dialog id="detail_modal" class="modal">
        <div class="modal-box max-w-3xl">
            <h3 class="font-bold text-lg text-foreground">Detail Pelunasan — <span id="detail_doc"></span></h3>
            <p id="detail_meta" class="py-1 text-sm text-muted-foreground"></p>
            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Sumber</th>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">No. Bukti</th>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Tanggal</th>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Bank</th>
                            <th class="px-3 py-2 text-right font-medium text-muted-foreground">Jumlah</th>
                            <th class="px-3 py-2 text-left font-medium text-muted-foreground">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="detail_body">
                        <tr><td colspan="6" class="px-3 py-6 text-center text-muted-foreground">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-end shrink-0 pt-3">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('detail_modal').close()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- Pay Modal --}}
    <dialog id="pay_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Pay — <span id="pay_doc"></span></h3>
            <p id="pay_meta" class="py-1 text-sm text-muted-foreground"></p>
            <form id="pay_form" class="py-2">
                <div class="grid gap-3">
                    <div>
                        <label for="pay_amount" class="block text-sm font-medium text-muted-foreground mb-1">Nominal Bayar</label>
                        <input type="number" id="pay_amount" name="payment_amount" min="0.01" step="0.01" required
                               class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="pay_date" class="block text-sm font-medium text-muted-foreground mb-1">Tanggal</label>
                        <input type="date" id="pay_date" name="payment_date" required
                               class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="pay_seq" class="block text-sm font-medium text-muted-foreground mb-1">No. Bukti (SEQUENCENO)</label>
                        <input type="text" id="pay_seq" name="sequence_no" maxlength="50" required placeholder="mis. DBM-2512-1038"
                               class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="pay_bank" class="block text-sm font-medium text-muted-foreground mb-1">Bank (opsional)</label>
                        <input type="text" id="pay_bank" name="bank_account" maxlength="50" placeholder="mis. 110201"
                               class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                    </div>
                    <div>
                        <label for="pay_desc" class="block text-sm font-medium text-muted-foreground mb-1">Keterangan (opsional)</label>
                        <input type="text" id="pay_desc" name="description" maxlength="500"
                               class="w-full min-h-9 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">Pembayaran disimpan dan file XML pelunasan otomatis terdownload untuk diupload ke Accurate.</p>
                <div class="modal-action">
                    <button type="submit" id="pay_submit" class="btn btn-primary">Simpan &amp; Download XML</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('pay_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    @push('scripts')
    <script>
        var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var PAY_DATA = @json($payload);

        function formatRp(value) {
            var n = Number(value || 0);
            return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        var BADGE_BASE = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ';
        function statusBadge(status, total, expected) {
            if (status === 'cocok') {
                return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">Cocok</span> <span class="text-xs text-muted-foreground">XML ' + formatRp(total) + '</span>';
            }
            if (status === 'tidak_cocok') {
                var meta = '';
                if (typeof expected === 'number') {
                    var diff = expected - total;
                    if (Math.abs(diff) >= 0.01) {
                        meta = (diff > 0 ? 'kurang ' : 'lebih ') + formatRp(Math.abs(diff)) + ' · ';
                    }
                }
                return '<span class="' + BADGE_BASE + 'bg-error/10 text-error">Tidak Cocok</span> <span class="text-xs text-muted-foreground">' + meta + 'XML ' + formatRp(total) + '</span>';
            }
            return '<span class="' + BADGE_BASE + 'border border-border text-muted-foreground">Belum</span>';
        }

        function matchBadge(status, extDiff) {
            if (status === 'match') {
                return '<span class="' + BADGE_BASE + 'bg-success/10 text-success">Match</span>';
            }
            if (status === 'beda_nilai') {
                var diffHtml = '';
                if (typeof extDiff === 'number' && Math.abs(extDiff) >= 0.01) {
                    diffHtml = ' · ext ' + (extDiff > 0 ? '+' : '-') + formatRp(Math.abs(extDiff));
                }
                return '<span class="' + BADGE_BASE + 'bg-warning/10 text-warning">Beda Nilai</span> <span class="text-xs text-muted-foreground">' + diffHtml + '</span>';
            }
            return '<span class="' + BADGE_BASE + 'border border-border text-muted-foreground">Hanya Internal</span>';
        }

        var selectedIds = new Set();
        var actionMenuDoc = null;

        function openActionMenu(btn) {
            var menu = document.getElementById('action_menu');
            actionMenuDoc = btn.getAttribute('data-doc');
            var owing = Number(btn.getAttribute('data-owing') || 0);
            document.getElementById('menu_pay_btn').style.display = owing > 0 ? '' : 'none';
            menu.classList.remove('hidden');
            var rect = btn.getBoundingClientRect();
            var top = rect.bottom + 4;
            var left = rect.left;
            if (left + 224 > window.innerWidth) left = window.innerWidth - 232;
            if (top + menu.offsetHeight > window.innerHeight) top = rect.top - menu.offsetHeight - 4;
            menu.style.top = Math.max(4, top) + 'px';
            menu.style.left = Math.max(4, left) + 'px';
        }

        function closeActionMenu() {
            document.getElementById('action_menu').classList.add('hidden');
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#action_menu') && !e.target.closest('[data-doc][data-owing]')) closeActionMenu();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeActionMenu(); });
        window.addEventListener('scroll', closeActionMenu, true);
        window.addEventListener('resize', closeActionMenu);

        function downloadBlob(blob, filename) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
        }

        // ==== Detail pelunasan ====
        function detailFromMenu() {
            closeActionMenu();
            if (!actionMenuDoc) return;
            var doc = actionMenuDoc;
            var modal = document.getElementById('detail_modal');
            document.getElementById('detail_doc').textContent = doc;
            document.getElementById('detail_meta').textContent = 'Memuat...';
            document.getElementById('detail_body').innerHTML = '<tr><td colspan="6" class="px-3 py-6 text-center text-muted-foreground">Memuat data...</td></tr>';
            modal.showModal();

            var url = @json(route('payments.detail', ['invoice' => '__DOC__'])).replace('__DOC__', encodeURIComponent(doc));
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(function (data) {
                    var inv = data.invoice;
                    document.getElementById('detail_meta').textContent =
                        (inv.customer_name || '-') + ' · Invoice ' + formatRp(inv.invoice_amount)
                        + ' · Payment (Accurate) ' + formatRp(inv.payment_amount)
                        + ' · Owing ' + formatRp(inv.owing)
                        + ' · Internal: ' + inv.status_internal
                        + ' · External: ' + inv.status_external;

                    var body = document.getElementById('detail_body');
                    if (!data.lines.length) {
                        body.innerHTML = '<tr><td colspan="6" class="px-3 py-6 text-center text-muted-foreground">Belum ada data pelunasan untuk invoice ini.</td></tr>';
                        return;
                    }
                    var SRC = { internal: 'bg-info/10 text-info', external: 'bg-blue-900 text-blue-300', web: 'bg-success/10 text-success' };
                    var html = '';
                    data.lines.forEach(function (l) {
                        html += '<tr class="border-b border-border">'
                            + '<td class="px-3 py-2"><span class="' + BADGE_BASE + (SRC[l.source_type] || 'border border-border') + '">' + escapeHtml(l.source_type) + '</span></td>'
                            + '<td class="px-3 py-2 text-foreground">' + escapeHtml(l.sequence_no || '-') + '</td>'
                            + '<td class="px-3 py-2 text-foreground">' + escapeHtml(l.payment_date || '-') + '</td>'
                            + '<td class="px-3 py-2 text-foreground">' + escapeHtml(l.bank_account || '-') + '</td>'
                            + '<td class="px-3 py-2 text-right text-foreground">' + formatRp(l.payment_amount) + '</td>'
                            + '<td class="px-3 py-2 text-muted-foreground">' + escapeHtml(l.description || '-') + '</td>'
                            + '</tr>';
                    });
                    body.innerHTML = html;
                })
                .catch(function (err) {
                    document.getElementById('detail_body').innerHTML = '<tr><td colspan="6" class="px-3 py-6 text-center text-destructive">Gagal memuat: ' + escapeHtml(err.message) + '</td></tr>';
                });
        }

        // ==== Pay ====
        function payFromMenu() {
            closeActionMenu();
            if (!actionMenuDoc) return;
            var row = PAY_DATA.find(function (r) { return r.doc_number === actionMenuDoc; });
            if (!row) return;

            document.getElementById('pay_doc').textContent = row.doc_number;
            document.getElementById('pay_meta').textContent =
                (row.customer_name || '') + ' · Sisa owing ' + formatRp(row.owing);
            var defaultAmount = Math.max(Number(row.owing) || 0, 0.01);
            document.getElementById('pay_amount').value = defaultAmount.toFixed(2);
            document.getElementById('pay_date').value = new Date().toISOString().slice(0, 10);
            document.getElementById('pay_seq').value = '';
            document.getElementById('pay_bank').value = '';
            document.getElementById('pay_desc').value = '';
            document.getElementById('pay_modal').showModal();
        }

        document.getElementById('pay_form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!actionMenuDoc) return;
            var btn = document.getElementById('pay_submit');
            btn.disabled = true;

            var body = new URLSearchParams();
            body.set('payment_amount', document.getElementById('pay_amount').value);
            body.set('payment_date', document.getElementById('pay_date').value);
            body.set('sequence_no', document.getElementById('pay_seq').value);
            var bank = document.getElementById('pay_bank').value;
            if (bank) body.set('bank_account', bank);
            var desc = document.getElementById('pay_desc').value;
            if (desc) body.set('description', desc);

            var url = @json(route('payments.pay', ['invoice' => '__DOC__'])).replace('__DOC__', encodeURIComponent(actionMenuDoc));

            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/xml' },
                body: body
            })
                .then(function (res) {
                    if (!res.ok) {
                        return res.json().then(function (j) { throw new Error(j.message || ('HTTP ' + res.status)); }).catch(function () {
                            throw new Error('HTTP ' + res.status);
                        });
                    }
                    var dispo = res.headers.get('Content-Disposition') || '';
                    var m = dispo.match(/filename="([^"]+)"/);
                    var filename = m ? m[1] : 'pelunasan.xml';
                    return res.blob().then(function (blob) { return { blob: blob, filename: filename }; });
                })
                .then(function (out) {
                    downloadBlob(out.blob, out.filename);
                    document.getElementById('pay_modal').close();
                    window.location.reload();
                })
                .catch(function (err) {
                    alert('Gagal menyimpan pembayaran: ' + err.message);
                })
                .finally(function () { btn.disabled = false; });
        });

        // ==== Export XML (row menu & bulk) ====
        function exportXmlRequest(sourceType, invoiceNos) {
            var body = new URLSearchParams();
            body.set('source_type', sourceType);
            invoiceNos.forEach(function (n) { body.append('invoice_nos[]', n); });

            fetch(@json(route('payments.export')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/xml' },
                body: body
            })
                .then(function (res) {
                    if (!res.ok) {
                        return res.json().then(function (j) { throw new Error(j.message || ('HTTP ' + res.status)); }).catch(function () {
                            throw new Error('HTTP ' + res.status);
                        });
                    }
                    var dispo = res.headers.get('Content-Disposition') || '';
                    var m = dispo.match(/filename="([^"]+)"/);
                    var filename = m ? m[1] : 'pelunasan.xml';
                    return res.blob().then(function (blob) { return { blob: blob, filename: filename }; });
                })
                .then(function (out) { downloadBlob(out.blob, out.filename); })
                .catch(function (err) { alert('Export gagal: ' + err.message); });
        }

        function exportFromMenu(sourceType) {
            closeActionMenu();
            if (!actionMenuDoc) return;
            if (sourceType === 'external') {
                exportExternalRequest([actionMenuDoc]);
            } else {
                exportXmlRequest(sourceType, [actionMenuDoc]);
            }
        }

        function selectedDocNumbers() {
            return PAY_DATA.filter(function (r) { return selectedIds.has(r.id); })
                .map(function (r) { return r.doc_number; });
        }

        function updateBulkState() {
            var count = selectedIds.size;
            var countEl = document.getElementById('export_count');
            countEl.classList.toggle('hidden', count === 0);
            countEl.classList.toggle('flex', count > 0);
            countEl.textContent = count + ' data dipilih';
            document.getElementById('bulk_export_internal').disabled = count === 0;
            document.getElementById('bulk_export_external').disabled = count === 0;
        }

        document.getElementById('bulk_export_internal').addEventListener('click', function () {
            var docs = selectedDocNumbers();
            if (docs.length) exportXmlRequest('internal', docs);
        });
        document.getElementById('bulk_export_external').addEventListener('click', function () {
            var docs = selectedDocNumbers();
            if (docs.length) exportExternalRequest(docs);
        });

        // ==== Export XML External (konversi internal -> external) ====
        function exportExternalRequest(invoiceNos) {
            var body = new URLSearchParams();
            invoiceNos.forEach(function (n) { body.append('invoice_nos[]', n); });

            fetch(@json(route('payments.exportExternal')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/xml' },
                body: body
            })
                .then(function (res) {
                    if (!res.ok) {
                        return res.json().then(function (j) { throw new Error(j.message || ('HTTP ' + res.status)); }).catch(function () {
                            throw new Error('HTTP ' + res.status);
                        });
                    }
                    var dispo = res.headers.get('Content-Disposition') || '';
                    var m = dispo.match(/filename="([^"]+)"/);
                    var filename = m ? m[1] : 'EXT.xml';
                    var skipped = 0, codes = [], warnings = [];
                    try {
                        var rawSkipped = res.headers.get('X-Skipped-Receipts');
                        if (rawSkipped) skipped = parseInt(rawSkipped, 10) || 0;
                        var rawCodes = res.headers.get('X-Skipped-Codes');
                        if (rawCodes) codes = JSON.parse(decodeURIComponent(rawCodes));
                        var rawWarn = res.headers.get('X-Convert-Warnings');
                        if (rawWarn) warnings = JSON.parse(decodeURIComponent(rawWarn));
                    } catch (e) { /* header tidak wajib valid */ }
                    return res.blob().then(function (blob) {
                        return { blob: blob, filename: filename, skipped: skipped, codes: codes, warnings: warnings };
                    });
                })
                .then(function (out) {
                    downloadBlob(out.blob, out.filename);
                    if (out.skipped > 0) {
                        alert('Peringatan: ' + out.skipped + ' receipt dilewati (kode customer tidak ada di mapping /mapping-customers):\n'
                            + out.codes.join(', '));
                    }
                    if (out.warnings.length) {
                        alert('Catatan konversi:\n- ' + out.warnings.join('\n- '));
                    }
                })
                .catch(function (err) { alert('Export XML External gagal: ' + err.message); });
        }

        // ==== DataTables ====
        $(function () {
            $('#payments-table').DataTable({
                data: PAY_DATA,
                deferRender: true,
                pageLength: 25,
                order: [],
                columns: [
                    { data: null, orderable: false, searchable: false, className: 'px-2 py-1.5 sticky left-0 bg-card z-10',
                        render: function (d, type) {
                            if (type !== 'display') return '';
                            return '<button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-info/15 text-info ring-1 ring-info/40 hover:bg-info/30 hover:text-info" data-doc="' + escapeHtml(d.doc_number || '') + '" data-owing="' + Number(d.owing || 0) + '" onclick="openActionMenu(this)" aria-label="Action">'
                                + '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg></button>';
                        } },
                    { data: null, orderable: false, searchable: false, className: 'px-2 py-1.5',
                        render: function (d, type) {
                            if (type !== 'display') return '';
                            return '<input type="checkbox" class="row-check h-4 w-4 rounded border-border text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" value="' + d.id + '"'
                                + (selectedIds.has(d.id) ? ' checked' : '')
                                + ' aria-label="Pilih ' + escapeHtml(d.doc_number || d.id) + '" />';
                        } },
                    { data: 'doc_number', className: 'px-2 py-1.5 text-foreground font-medium',
                        render: function (v, type) { if (type !== 'display') return v || ''; return escapeHtml(v || '-'); } },
                    { data: 'transaction_date', className: 'px-2 py-1.5 text-muted-foreground',
                        render: function (v, type) {
                            if (!v) return type === 'display' ? '-' : '';
                            if (type !== 'display') return v;
                            var p = v.split('-');
                            return p[2] + '/' + p[1] + '/' + p[0];
                        } },
                    { data: null, className: 'px-2 py-1.5 text-foreground max-w-[180px] truncate',
                        render: function (d, type) {
                            if (type !== 'display') return (d.customer_name || '') + ' ' + (d.customer_code || '');
                            var v = (d.customer_name || '-') + (d.customer_code ? ' (' + d.customer_code + ')' : '');
                            return '<span title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</span>';
                        } },
                    { data: 'payment_amount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'payment_amount_external', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) {
                            if (v === null || v === undefined) return type === 'display' ? '-' : '';
                            return type === 'display' ? formatRp(v) : v;
                        } },
                    { data: 'ext_tax_base', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) {
                            if (v === null || v === undefined || Number(v) === 0) return type === 'display' ? '-' : '';
                            return type === 'display' ? formatRp(v) : v;
                        } },
                    { data: 'invoice_amount', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: 'return_amount', className: 'px-2 py-1.5 text-foreground',
                        render: function (v, type) {
                            if (v === null || v === undefined || Number(v) === 0) return type === 'display' ? '-' : '';
                            return type === 'display' ? formatRp(v) : v;
                        } },
                    { data: 'owing', className: 'px-2 py-1.5 text-foreground', render: function (v, type) { return type === 'display' ? formatRp(v) : v; } },
                    { data: null, className: 'px-2 py-1.5',
                        render: function (d, type) { return type === 'display' ? statusBadge(d.status_internal, d.paid_internal, d.payment_amount) : d.status_internal; } },
                    { data: null, className: 'px-2 py-1.5',
                        render: function (d, type) { return type === 'display' ? statusBadge(d.status_external, d.paid_external, d.payment_amount_external) : d.status_external; } },
                    { data: null, className: 'px-2 py-1.5',
                        render: function (d, type) { return type === 'display' ? matchBadge(d.match_status, d.ext_diff) : d.match_status; } },
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
                    paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
                }
            });

            var table = $('#payments-table').DataTable();

            $('#payments-table').on('change', '.row-check', function () {
                var id = Number($(this).val());
                if (this.checked) selectedIds.add(id); else selectedIds.delete(id);
                updateBulkState();
            });

            $('#select_all_rows').on('change', function () {
                var checked = this.checked;
                selectedIds.clear();
                if (checked) {
                    table.rows({ page: 'current' }).every(function () {
                        selectedIds.add(Number(this.data().id));
                    });
                }
                $('#payments-table .row-check').prop('checked', checked);
                updateBulkState();
            });
        });
    </script>
    @endpush
</x-layouts.app>
