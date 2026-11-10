<x-layouts.app>
    <x-slot name="header">
        <x-page-header
            title="Detail Rekening Koran"
            description="{{ $bankTransaction->description }}"
            backUrl="{{ route('bank-transactions.index') }}"
        >
            <x-badge :type="$bankTransaction->position === 'CR' ? 'success' : 'danger'" class="text-sm">
                {{ $bankTransaction->position === 'CR' ? 'Kredit (CR)' : 'Debet (DB)' }}
            </x-badge>
        </x-page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if(session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Data mutasi --}}
        <x-card class="lg:col-span-2" title="Data Mutasi" description="Nilai sesuai file rekening koran">
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-muted-foreground">Tanggal</dt>
                    <dd class="font-medium">{{ $bankTransaction->transaction_date?->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Cabang</dt>
                    <dd class="font-medium">{{ $bankTransaction->branch_code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Nilai</dt>
                    <dd class="font-medium font-mono">
                        {{ $bankTransaction->amount_text ?: number_format((float) $bankTransaction->amount, 2, ',', '.') }}
                        ({{ number_format((float) $bankTransaction->amount, 2, ',', '.') }})
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Saldo</dt>
                    <dd class="font-medium font-mono">
                        <span @class([
                            'inline-block rounded px-1',
                            'bg-amber-500/25' => $bankTransaction->source === 'BASEROW',
                        ]) @if($bankTransaction->source === 'BASEROW') title="Saldo dari Baserow, bisa berbeda dari CSV" @endif>
                            {{ number_format((float) $bankTransaction->balance, 2, ',', '.') }}
                        </span>
                        @if($bankTransaction->source === 'BASEROW')
                            <span class="block text-xs text-muted-foreground">Saldo dari Baserow (best-effort), akan diganti setelah rekening koran CSV periode ini diimport.</span>
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-muted-foreground">Keterangan</dt>
                    <dd class="font-medium">{{ $bankTransaction->description }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Rekening</dt>
                    <dd class="font-medium">
                        {{ $bankTransaction->bankAccount?->bank_name }} — {{ $bankTransaction->bankAccount?->account_number }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Periode</dt>
                    <dd class="font-medium">
                        {{ $bankTransaction->period_month ? \Carbon\Carbon::create()->month($bankTransaction->period_month)->translatedFormat('F') : '—' }}
                        {{ $bankTransaction->period_year }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Pelunasan</dt>
                    <dd class="font-medium">{{ $bankTransaction->is_paid ? 'Ya' : 'Tidak' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Ada Masalah</dt>
                    <dd class="font-medium">{{ $bankTransaction->has_issue ? 'Ya' : 'Tidak' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-muted-foreground">Status Pencocokan Baserow</dt>
                    <dd class="mt-1">
                        @php
                            $statusType = match ($bankTransaction->status) {
                                'MATCHED' => 'success',
                                'AMBIGUOUS' => 'warning',
                                default => 'danger',
                            };
                        @endphp
                        <x-badge :type="$statusType">{{ $bankTransaction->status }}</x-badge>
                    </dd>
                </div>
            </dl>
        </x-card>

        {{-- Nomor dokumen & catatan --}}
        <x-card title="Nomor Dokumen & Catatan" description="Bisa diisi / diperbaiki manual">
            <form method="POST" action="{{ route('bank-transactions.fields', $bankTransaction) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <x-form-group label="No Dokumen" for="document_number">
                    <x-input id="document_number" name="document_number" value="{{ old('document_number', $bankTransaction->document_number) }}" placeholder="033767/DMX/RK/X/2026" />
                </x-form-group>

                <x-form-group label="No Dok Internal" for="document_internal">
                    <x-input id="document_internal" name="document_internal" value="{{ old('document_internal', $bankTransaction->document_internal) }}" placeholder="DBM-2610-1455" />
                </x-form-group>

                <x-form-group label="No Dok External" for="document_external">
                    <x-input id="document_external" name="document_external" value="{{ old('document_external', $bankTransaction->document_external) }}" placeholder="Nomor dokumen Accurate Online" />
                </x-form-group>

                <x-form-group label="Catatan" for="note">
                    <x-textarea id="note" name="note" rows="4" placeholder="Catatan Finance AR">{{ old('note', $bankTransaction->note) }}</x-textarea>
                </x-form-group>

                <x-button type="submit">Simpan Perubahan</x-button>
            </form>
        </x-card>
    </div>

    @if($bankTransaction->importBatch)
        <x-card class="mt-4" title="Riwayat Import" description="Batch yang terakhir menulis baris ini">
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div>
                    <dt class="text-muted-foreground">File</dt>
                    <dd class="font-medium break-all">{{ $bankTransaction->importBatch->file_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Periode</dt>
                    <dd class="font-medium">
                        {{ $bankTransaction->importBatch->period_start?->format('d/m/Y') ?? '—' }}
                        s.d.
                        {{ $bankTransaction->importBatch->period_end?->format('d/m/Y') ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Strategi</dt>
                    <dd class="font-medium">{{ $bankTransaction->importBatch->strategy ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Waktu Import</dt>
                    <dd class="font-medium">{{ $bankTransaction->importBatch->created_at?->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </x-card>
    @endif
</x-layouts.app>
