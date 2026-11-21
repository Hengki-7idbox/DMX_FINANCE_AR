<x-layouts.app>
    <x-slot name="header">
        <x-page-header
            title="Download Xml External"
            description="Tarik XML Coretax data external per periode dan tipe Customer No." />
    </x-slot>

    <x-alert type="info" class="mb-4">
        XML yang dihasilkan sama dengan bulk export <strong>XML Coretax</strong> di Detail Sales tab External
        (builder, urutan dokumen, dan filter retur yang sama). Dokumen bermasalah dilewati satu per satu
        dan daftarnya ditampilkan setelah proses selesai.
        <br>
        <strong>Hanya filter "FP belum ada" yang bisa ditarik.</strong>
        Status <strong>FP ada</strong>, <strong>FP draft</strong>, dan <strong>Gung Gung</strong> hanya untuk melihat
        jumlah — dokumennya sudah punya FP (atau sengaja tidak dibuat), jadi upload ulang berisiko dobel.
    </x-alert>

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    @php
        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    @endphp

    <x-card title="Filter Data" description="Pilih periode dan tipe Customer No., lalu klik Tarik Data XML Coretax.">
        <form id="xml_form" class="space-y-5" novalidate>
            <div>
                <span class="mb-2 block text-sm font-medium text-muted-foreground">Mode Periode</span>
                <div class="flex flex-wrap items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-foreground">
                        <input type="radio" name="mode" value="bulan" checked class="h-4 w-4 accent-primary">
                        Per Bulan
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-foreground">
                        <input type="radio" name="mode" value="tanggal" class="h-4 w-4 accent-primary">
                        Per Tanggal
                    </label>
                </div>
            </div>

            <div id="mode_bulan" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="f_bulan" class="mb-1 block text-sm font-medium text-muted-foreground">Bulan</label>
                    <x-select id="f_bulan" placeholder="">
                        @foreach($availableBulan as $b)
                            <option value="{{ $b }}" @selected($b === $defaultBulan)>{{ $bulanNames[$b] ?? $b }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label for="f_tahun" class="mb-1 block text-sm font-medium text-muted-foreground">Tahun</label>
                    <x-select id="f_tahun" placeholder="">
                        @foreach($availableTahun as $t)
                            <option value="{{ $t }}" @selected($t === $defaultTahun)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <div id="mode_tanggal" class="hidden">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f_dari" class="mb-1 block text-sm font-medium text-muted-foreground">Dari Tanggal</label>
                        <x-input type="date" id="f_dari" value="{{ $defaultDari }}" />
                    </div>
                    <div>
                        <label for="f_sampai" class="mb-1 block text-sm font-medium text-muted-foreground">Sampai Tanggal</label>
                        <x-input type="date" id="f_sampai" value="{{ $defaultSampai }}" />
                    </div>
                </div>
            </div>

            <div class="grid max-w-2xl gap-4 sm:grid-cols-2">
                <div>
                    <label for="f_cust_type" class="mb-1 block text-sm font-medium text-muted-foreground">Customer No.</label>
                    <x-select id="f_cust_type" placeholder="">
                        <option value="">Semua</option>
                        <option value="ktp">KTP</option>
                        <option value="npwp">NPWP</option>
                        <option value="tnpwp">TNPWP</option>
                        <option value="etc">ETC</option>
                    </x-select>
                </div>
                <div>
                    <label for="f_fp" class="mb-1 block text-sm font-medium text-muted-foreground">Faktur Pajak</label>
                    <x-select id="f_fp" placeholder="">
                        <option value="belum" selected>FP belum ada</option>
                        <option value="ada">FP ada (tidak bisa ditarik)</option>
                        <option value="draft">FP draft (tidak bisa ditarik)</option>
                        <option value="gung_gung">Gung Gung (tidak bisa ditarik)</option>
                    </x-select>
                    <p id="fp_warning" class="mt-2 hidden text-xs text-warning">
                        Filter ini hanya untuk melihat jumlah. Tarik XML hanya tersedia untuk
                        <strong>FP belum ada</strong> — status lain berarti FP-nya sudah ada atau sengaja tidak dibuat,
                        sehingga upload ulang berisiko dobel.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-border pt-4">
                <x-button id="btn_download" type="submit">Tarik Data XML Coretax</x-button>
                <span id="summary_text" class="text-sm text-muted-foreground">Menghitung…</span>
            </div>
        </form>
    </x-card>

    <div id="result_panel" class="mt-4 hidden">
        <x-card title="Hasil Tarik" description="Ringkasan dokumen yang diproses pada tarikan terakhir.">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-border bg-background px-4 py-3">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Invoice diexport</p>
                    <p id="res_count" class="text-2xl font-semibold text-foreground">0</p>
                </div>
                <div class="rounded-lg border border-border bg-background px-4 py-3">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Dokumen dilewati</p>
                    <p id="res_skipped" class="text-2xl font-semibold text-foreground">0</p>
                </div>
                <div class="rounded-lg border border-border bg-background px-4 py-3">
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Unit belum dipetakan</p>
                    <p id="res_unmapped" class="text-2xl font-semibold text-foreground">0</p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-button variant="outline" id="btn_view_skipped" class="hidden" onclick="openResultModal()">
                    Lihat dokumen yang dilewati
                </x-button>
                <x-button variant="outline" id="btn_export_skipped" class="hidden" onclick="exportSkipped()">
                    Export Excel
                </x-button>
            </div>
        </x-card>
    </div>

    <dialog id="result_modal" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 id="result_title" class="text-lg font-bold text-foreground">Hasil</h3>
            <div id="result_body" class="mt-3 max-h-80 overflow-y-auto text-sm text-muted-foreground"></div>
            <div class="modal-action">
                <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('result_modal').close()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <div id="xml_toast"
         class="fixed right-4 top-4 z-[200] hidden rounded-lg px-4 py-3 text-sm font-medium text-white shadow-xl"></div>

    @push('scripts')
    <script>
        var SUMMARY_URL = @json(route('tax.download-xml-external.summary'));
        var DOWNLOAD_URL = @json(route('tax.download-xml-external.download'));
        var SKIPPED_EXPORT_URL = @json(route('tax.download-xml-external.skipped-export'));
        var CSRF_TOKEN = @json(csrf_token());

        var xmlForm = document.getElementById('xml_form');
        var modeInputs = xmlForm.querySelectorAll('input[name="mode"]');
        var bulanBox = document.getElementById('mode_bulan');
        var tanggalBox = document.getElementById('mode_tanggal');
        var downloadBtn = document.getElementById('btn_download');
        var summaryEl = document.getElementById('summary_text');

        var nf = new Intl.NumberFormat('id-ID');
        var summarySeq = 0;
        var summaryTimer = null;
        var lastNotices = [];
        var lastFilters = null;

        function xmlToast(type, msg) {
            var t = document.getElementById('xml_toast');
            if (!t) return;
            t.className = 'fixed right-4 top-4 z-[200] rounded-lg px-4 py-3 text-sm font-medium text-white shadow-xl '
                + (type === 'success' ? 'bg-success' : 'bg-destructive');
            t.textContent = msg;
            t.classList.remove('hidden');
            clearTimeout(xmlToast._timer);
            xmlToast._timer = setTimeout(function () { t.classList.add('hidden'); }, 8000);
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function currentFilters() {
            var mode = xmlForm.querySelector('input[name="mode"]:checked').value;
            return {
                mode: mode,
                bulan: document.getElementById('f_bulan').value,
                tahun: document.getElementById('f_tahun').value,
                dari: document.getElementById('f_dari').value,
                sampai: document.getElementById('f_sampai').value,
                cust_type: document.getElementById('f_cust_type').value,
                fp: document.getElementById('f_fp').value
            };
        }

        function isIncomplete(f) {
            if (f.mode === 'bulan') return !f.bulan || !f.tahun;
            return !f.dari || !f.sampai;
        }

        function toggleMode() {
            var mode = xmlForm.querySelector('input[name="mode"]:checked').value;
            bulanBox.classList.toggle('hidden', mode !== 'bulan');
            tanggalBox.classList.toggle('hidden', mode !== 'tanggal');
            refreshSummary();
        }

        function refreshSummary() {
            var f = currentFilters();
            if (isIncomplete(f)) {
                summaryEl.textContent = 'Lengkapi periode terlebih dahulu.';
                return;
            }

            var current = ++summarySeq;
            summaryEl.textContent = 'Menghitung…';

            fetch(SUMMARY_URL + '?' + new URLSearchParams(f).toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) {
                    return res.json().then(function (j) { return { ok: res.ok, body: j }; });
                })
                .then(function (r) {
                    if (current !== summarySeq) return;
                    if (!r.ok || !r.body.ok) {
                        summaryEl.textContent = (r.body && r.body.message) || 'Gagal menghitung ringkasan.';
                        return;
                    }
                    var b = r.body.by_type || {};
                    summaryEl.innerHTML = '<strong class="text-foreground">' + nf.format(r.body.total) + '</strong> invoice · '
                        + 'KTP ' + nf.format(b.ktp || 0)
                        + ' · NPWP ' + nf.format(b.npwp || 0)
                        + ' · TNPWP ' + nf.format(b.tnpwp || 0)
                        + ' · ETC ' + nf.format(b.etc || 0);
                })
                .catch(function () {
                    if (current !== summarySeq) return;
                    summaryEl.textContent = 'Gagal menghitung ringkasan.';
                });
        }

        function scheduleSummary() {
            clearTimeout(summaryTimer);
            summaryTimer = setTimeout(refreshSummary, 300);
        }

        function toggleFpWarning() {
            var value = document.getElementById('f_fp').value;
            var blocked = value !== 'belum';

            document.getElementById('fp_warning').classList.toggle('hidden', !blocked);

            var btn = document.getElementById('btn_download');
            if (btn) {
                btn.disabled = blocked;
                btn.classList.toggle('opacity-50', blocked);
                btn.classList.toggle('cursor-not-allowed', blocked);
                btn.title = blocked ? 'Hanya "FP belum ada" yang bisa ditarik.' : '';
            }
        }

        function showResultModal(title, items, type) {
            var dlg = document.getElementById('result_modal');
            var head = document.getElementById('result_title');
            head.textContent = title;
            head.className = 'text-lg font-bold ' + (type === 'error' ? 'text-destructive' : 'text-warning');
            document.getElementById('result_body').innerHTML = '<ul class="list-disc space-y-1 pl-5">'
                + items.map(function (t) { return '<li>' + escapeHtml(t) + '</li>'; }).join('')
                + '</ul>';
            if (dlg && !dlg.open) dlg.showModal();
        }

        function openResultModal() {
            if (!lastNotices.length) return;
            showResultModal('Dokumen dilewati / unit belum dipetakan', lastNotices, 'warning');
        }

        // Unduh Excel dokumen dilewati memakai filter tarikan terakhir.
        function exportSkipped() {
            var f = lastFilters || currentFilters();
            xmlToast('success', 'Menyiapkan file Excel dokumen dilewati…');
            window.location.href = SKIPPED_EXPORT_URL + '?' + new URLSearchParams(f).toString();
        }

        // Semua dokumen dilewati (tidak ada yang bisa diexport): tetap tampilkan
        // panel + tombol export Excel agar data yang kurang bisa diperbaiki.
        function renderSkippedOnly(body) {
            var skipped = Array.isArray(body.errors) ? body.errors : [];
            document.getElementById('result_panel').classList.remove('hidden');
            document.getElementById('res_count').textContent = '0';
            document.getElementById('res_skipped').textContent = nf.format(skipped.length);
            document.getElementById('res_unmapped').textContent = nf.format((body.unmapped || []).length);
            lastNotices = skipped.slice();
            document.getElementById('btn_view_skipped').classList.toggle('hidden', lastNotices.length === 0);
            document.getElementById('btn_export_skipped').classList.toggle('hidden', skipped.length === 0);
        }

        function renderResult(body) {
            document.getElementById('result_panel').classList.remove('hidden');
            document.getElementById('res_count').textContent = nf.format(body.count || 0);
            document.getElementById('res_skipped').textContent = nf.format((body.skipped || []).length);
            document.getElementById('res_unmapped').textContent = nf.format((body.unmapped || []).length);

            lastNotices = (body.skipped || []).slice();
            (body.unmapped || []).forEach(function (u) {
                lastNotices.push('Unit "' + u + '" belum dipetakan → dipakai default ' + (body.unmapped_default || '-') + '.');
            });
            if ((body.unmapped || []).length) {
                lastNotices.push('Lengkapi pemetaannya di menu Mapping Unit agar tidak memakai unit default.');
            }

            document.getElementById('btn_view_skipped').classList.toggle('hidden', lastNotices.length === 0);
            document.getElementById('btn_export_skipped').classList.toggle('hidden', (body.skipped || []).length === 0);

            if (lastNotices.length) {
                var title = nf.format(body.count || 0) + ' invoice diexport';
                if ((body.skipped || []).length) title += ', ' + (body.skipped || []).length + ' dokumen dilewati';
                if ((body.unmapped || []).length) title += ', ' + (body.unmapped || []).length + ' unit belum dipetakan';
                showResultModal(title, lastNotices, 'warning');
            }
        }

        modeInputs.forEach(function (el) {
            el.addEventListener('change', toggleMode);
        });

        ['f_bulan', 'f_tahun', 'f_dari', 'f_sampai', 'f_cust_type', 'f_fp'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', scheduleSummary);
        });

        document.getElementById('f_fp').addEventListener('change', toggleFpWarning);

        xmlForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var f = currentFilters();
            if (isIncomplete(f)) {
                xmlToast('error', 'Lengkapi periode terlebih dahulu.');
                return;
            }
            if (f.mode === 'tanggal' && f.dari > f.sampai) {
                xmlToast('error', 'Tanggal awal tidak boleh melebihi tanggal akhir.');
                return;
            }

            downloadBtn.disabled = true;
            var label = downloadBtn.textContent;
            downloadBtn.textContent = 'Memproses…';

            fetch(DOWNLOAD_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(f)
            })
                .then(function (res) {
                    return res.json().then(function (j) { return { ok: res.ok, body: j }; });
                })
                .then(function (r) {
                    if (!r.ok || !r.body.ok) {
                        if (r.body && Array.isArray(r.body.errors) && r.body.errors.length) {
                            lastFilters = f;
                            renderSkippedOnly(r.body);
                            showResultModal('Tidak bisa export XML Coretax', r.body.errors, 'error');
                        } else {
                            xmlToast('error', (r.body && r.body.message) || 'Gagal membuat XML Coretax.');
                        }
                        return;
                    }

                    r.body.files.forEach(function (file, i) {
                        setTimeout(function () {
                            var blob = new Blob([file.content], { type: 'text/xml' });
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = file.name;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                        }, i * 350);
                    });

                    lastFilters = f;
                    renderResult(r.body);
                    xmlToast('success', nf.format(r.body.count) + ' invoice → XML Coretax diunduh.');
                })
                .catch(function () {
                    xmlToast('error', 'Gagal membuat XML Coretax.');
                })
                .finally(function () {
                    downloadBtn.disabled = false;
                    downloadBtn.textContent = label;
                });
        });

        toggleFpWarning();
        refreshSummary();
    </script>
    @endpush
</x-layouts.app>
