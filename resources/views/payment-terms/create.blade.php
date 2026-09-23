<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Tambah Payment Term" back-url="{{ route('payment-terms.index') }}" />
    </x-slot>

    <x-card>
        <form action="{{ route('payment-terms.store') }}" method="POST">
            @csrf
            <div class="grid gap-6 md:grid-cols-2">
                <x-form-group label="Kode Term" for="term_code" :error="$errors->first('term_code')" :required="true">
                    <x-input id="term_code" name="term_code" value="{{ old('term_code') }}" placeholder="NET30" />
                </x-form-group>

                <x-form-group label="Nama Term" for="term_name" :error="$errors->first('term_name')" :required="true">
                    <x-input id="term_name" name="term_name" value="{{ old('term_name') }}" placeholder="Net 30 Days" />
                </x-form-group>

                <x-form-group label="Days" for="days" :error="$errors->first('days')" :required="true">
                    <x-input id="days" name="days" type="number" value="{{ old('days', 0) }}" min="0" />
                </x-form-group>

                <x-form-group label="Status" for="status" :error="$errors->first('status')" :required="true">
                    <select id="status" name="status" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </x-form-group>

                <div class="md:col-span-2">
                    <x-form-group label="Description" for="description" :error="$errors->first('description')">
                        <x-textarea id="description" name="description">{{ old('description') }}</x-textarea>
                    </x-form-group>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-button href="{{ route('payment-terms.index') }}" variant="outline">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
