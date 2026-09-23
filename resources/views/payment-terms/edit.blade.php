<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Edit Payment Term" back-url="{{ route('payment-terms.index') }}" />
    </x-slot>

    <x-card>
        <form action="{{ route('payment-terms.update', $paymentTerm) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid gap-6 md:grid-cols-2">
                <x-form-group label="Kode Term" for="term_code">
                    <x-input id="term_code" value="{{ $paymentTerm->term_code }}" disabled />
                </x-form-group>

                <x-form-group label="Nama Term" for="term_name" :error="$errors->first('term_name')" :required="true">
                    <x-input id="term_name" name="term_name" value="{{ old('term_name', $paymentTerm->term_name) }}" />
                </x-form-group>

                <x-form-group label="Days" for="days" :error="$errors->first('days')" :required="true">
                    <x-input id="days" name="days" type="number" value="{{ old('days', $paymentTerm->days) }}" min="0" />
                </x-form-group>

                <x-form-group label="Status" for="status" :error="$errors->first('status')" :required="true">
                    <select id="status" name="status" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="ACTIVE" {{ old('status', $paymentTerm->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status', $paymentTerm->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </x-form-group>

                <div class="md:col-span-2">
                    <x-form-group label="Description" for="description" :error="$errors->first('description')">
                        <x-textarea id="description" name="description">{{ old('description', $paymentTerm->description) }}</x-textarea>
                    </x-form-group>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-button href="{{ route('payment-terms.index') }}" variant="outline">Batal</x-button>
                <x-button type="submit">Update</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
