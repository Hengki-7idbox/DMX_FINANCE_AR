<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Settings" description="Konfigurasi transformasi & ekspor data" />
    </x-slot>

    @if (session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <x-card class="mb-4">
            <div class="border-b border-border px-4 py-3">
                <h3 class="text-sm font-semibold text-foreground">Branch &amp; Term</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div>
                    <label for="branch_code_internal" class="mb-1 block text-xs font-medium text-muted-foreground">Branch Code Internal</label>
                    <input type="text" id="branch_code_internal" name="branch_code_internal" value="{{ old('branch_code_internal', $settings['branch_code_internal']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="branch_code_external" class="mb-1 block text-xs font-medium text-muted-foreground">Branch Code External</label>
                    <input type="text" id="branch_code_external" name="branch_code_external" value="{{ old('branch_code_external', $settings['branch_code_external']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="terms_id" class="mb-1 block text-xs font-medium text-muted-foreground">Terms ID</label>
                    <input type="text" id="terms_id" name="terms_id" value="{{ old('terms_id', $settings['terms_id']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="exim_id" class="mb-1 block text-xs font-medium text-muted-foreground">Exim ID</label>
                    <input type="text" id="exim_id" name="exim_id" value="{{ old('exim_id', $settings['exim_id']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
            </div>
        </x-card>

        <x-card class="mb-4">
            <div class="border-b border-border px-4 py-3">
                <h3 class="text-sm font-semibold text-foreground">Pajak</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div>
                    <label for="tax_rate" class="mb-1 block text-xs font-medium text-muted-foreground">Tax Rate (%)</label>
                    <input type="text" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="tax1_id" class="mb-1 block text-xs font-medium text-muted-foreground">Tax1 ID / Code</label>
                    <input type="text" id="tax1_id" name="tax1_id" value="{{ old('tax1_id', $settings['tax1_id']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="tax2_id" class="mb-1 block text-xs font-medium text-muted-foreground">Tax2 ID / Code</label>
                    <input type="text" id="tax2_id" name="tax2_id" value="{{ old('tax2_id', $settings['tax2_id']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="tax2_rate" class="mb-1 block text-xs font-medium text-muted-foreground">Tax2 Rate (%)</label>
                    <input type="text" id="tax2_rate" name="tax2_rate" value="{{ old('tax2_rate', $settings['tax2_rate']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
            </div>
        </x-card>

        <x-card class="mb-4">
            <div class="border-b border-border px-4 py-3">
                <h3 class="text-sm font-semibold text-foreground">Akun</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div>
                    <label for="warehouse_id" class="mb-1 block text-xs font-medium text-muted-foreground">Warehouse ID</label>
                    <input type="text" id="warehouse_id" name="warehouse_id" value="{{ old('warehouse_id', $settings['warehouse_id']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="ar_account" class="mb-1 block text-xs font-medium text-muted-foreground">AR Account</label>
                    <input type="text" id="ar_account" name="ar_account" value="{{ old('ar_account', $settings['ar_account']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="dp_account_id" class="mb-1 block text-xs font-medium text-muted-foreground">DP Account ID</label>
                    <input type="text" id="dp_account_id" name="dp_account_id" value="{{ old('dp_account_id', $settings['dp_account_id']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
            </div>
        </x-card>

        <x-card class="mb-4">
            <div class="border-b border-border px-4 py-3">
                <h3 class="text-sm font-semibold text-foreground">Coretax</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div>
                    <label for="seller_tin" class="mb-1 block text-xs font-medium text-muted-foreground">NPWP / TIN Penjual (16 digit)</label>
                    <input type="text" id="seller_tin" name="seller_tin" value="{{ old('seller_tin', $settings['seller_tin']) }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    <p class="mt-1 text-xs text-tertiary">ID TKU Penjual = TIN + "000000".</p>
                </div>
            </div>
        </x-card>

        <x-card class="mb-4">
            <div class="border-b border-border px-4 py-3">
                <h3 class="text-sm font-semibold text-foreground">Accurate Online API (DMX EXT)</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div>
                    <label for="accurate_host" class="mb-1 block text-xs font-medium text-muted-foreground">Host</label>
                    <input type="text" id="accurate_host" name="accurate_host" value="{{ old('accurate_host', $settings['accurate_host'] ?? 'https://zeus.accurate.id') }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                </div>
                <div>
                    <label for="accurate_warehouse_name" class="mb-1 block text-xs font-medium text-muted-foreground">Gudang (Nama)</label>
                    <input type="text" id="accurate_warehouse_name" name="accurate_warehouse_name" value="{{ old('accurate_warehouse_name', $settings['accurate_warehouse_name'] ?? 'Utama') }}"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    <p class="mt-1 text-xs text-tertiary">Nama gudang Accurate untuk DO &amp; Invoice (mis. Utama).</p>
                </div>
                <div>
                    <label for="accurate_api_token" class="mb-1 block text-xs font-medium text-muted-foreground">API Token</label>
                    <input type="password" id="accurate_api_token" name="accurate_api_token" value="{{ old('accurate_api_token', $settings['accurate_api_token'] ?? '') }}" autocomplete="off"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    <p class="mt-1 text-xs text-tertiary">Kosongkan bila tidak ingin mengubah token tersimpan.</p>
                </div>
                <div>
                    <label for="accurate_signature_secret" class="mb-1 block text-xs font-medium text-muted-foreground">Signature Secret</label>
                    <input type="password" id="accurate_signature_secret" name="accurate_signature_secret" value="{{ old('accurate_signature_secret', $settings['accurate_signature_secret'] ?? '') }}" autocomplete="off"
                           class="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
                    <p class="mt-1 text-xs text-tertiary">Dipakai untuk X-Api-Signature = base64(HMAC_SHA256(secret, timestamp)).</p>
                </div>
            </div>
        </x-card>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex min-h-9 items-center rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                Simpan Settings
            </button>
        </div>
    </form>
</x-layouts.app>
