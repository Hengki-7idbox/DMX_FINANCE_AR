<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Edit Bank Account" back-url="{{ route('bank-accounts.index') }}" />
    </x-slot>

    <x-card>
        <form action="{{ route('bank-accounts.update', $bankAccount) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid gap-6 md:grid-cols-2">
                <x-form-group label="Bank Name" for="bank_name" :error="$errors->first('bank_name')" :required="true">
                    <x-input id="bank_name" name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name) }}" />
                </x-form-group>

                <x-form-group label="Account Number" for="account_number">
                    <x-input id="account_number" value="{{ $bankAccount->account_number }}" disabled />
                </x-form-group>

                <x-form-group label="Account Name" for="account_name" :error="$errors->first('account_name')" :required="true">
                    <x-input id="account_name" name="account_name" value="{{ old('account_name', $bankAccount->account_name) }}" />
                </x-form-group>

                <x-form-group label="Currency" for="currency" :error="$errors->first('currency')" :required="true">
                    <select id="currency" name="currency" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="IDR" {{ old('currency', $bankAccount->currency) == 'IDR' ? 'selected' : '' }}>IDR</option>
                        <option value="USD" {{ old('currency', $bankAccount->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('currency', $bankAccount->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                    </select>
                </x-form-group>

                <x-form-group label="Status" for="status" :error="$errors->first('status')" :required="true">
                    <select id="status" name="status" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="ACTIVE" {{ old('status', $bankAccount->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status', $bankAccount->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </x-form-group>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-button href="{{ route('bank-accounts.index') }}" variant="outline">Batal</x-button>
                <x-button type="submit">Update</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
