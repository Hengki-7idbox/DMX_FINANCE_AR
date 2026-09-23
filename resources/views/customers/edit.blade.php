<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Edit Customer" back-url="{{ route('customers.index', ['tab' => $tab ?? $customer->source_type]) }}" />
    </x-slot>

    <x-card>
        <form action="{{ route('customers.update', ['customer' => $customer, 'tab' => $tab ?? $customer->source_type]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid gap-6 md:grid-cols-2">
                <x-form-group label="Kode Customer" for="customer_code">
                    <x-input id="customer_code" value="{{ $customer->customer_code }}" disabled />
                </x-form-group>

                <x-form-group label="Source" for="source_type">
                    <x-input id="source_type" value="{{ strtoupper($customer->source_type) }}" disabled />
                </x-form-group>

                <x-form-group label="Nama Customer" for="customer_name" :error="$errors->first('customer_name')" :required="true">
                    <x-input id="customer_name" name="customer_name" value="{{ old('customer_name', $customer->customer_name) }}" />
                </x-form-group>

                <x-form-group label="Group Customer" for="customer_group_id" :error="$errors->first('customer_group_id')" :required="true">
                    <select id="customer_group_id" name="customer_group_id" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Pilih Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('customer_group_id', $customer->customer_group_id) == $group->id ? 'selected' : '' }}>
                                {{ $group->group_name }}
                            </option>
                        @endforeach
                    </select>
                </x-form-group>

                <x-form-group label="Customer Type" for="customer_type" :error="$errors->first('customer_type')" :required="true">
                    <select id="customer_type" name="customer_type" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="CORPORATE" {{ old('customer_type', $customer->customer_type) == 'CORPORATE' ? 'selected' : '' }}>Corporate</option>
                        <option value="DISTRIBUTOR" {{ old('customer_type', $customer->customer_type) == 'DISTRIBUTOR' ? 'selected' : '' }}>Distributor</option>
                        <option value="RETAIL" {{ old('customer_type', $customer->customer_type) == 'RETAIL' ? 'selected' : '' }}>Retail</option>
                        <option value="GOVERNMENT" {{ old('customer_type', $customer->customer_type) == 'GOVERNMENT' ? 'selected' : '' }}>Government</option>
                        <option value="RESELLER" {{ old('customer_type', $customer->customer_type) == 'RESELLER' ? 'selected' : '' }}>Reseller</option>
                        <option value="INTERNAL" {{ old('customer_type', $customer->customer_type) == 'INTERNAL' ? 'selected' : '' }}>Internal</option>
                    </select>
                </x-form-group>

                <x-form-group label="Tax Number" for="tax_number" :error="$errors->first('tax_number')">
                    <x-input id="tax_number" name="tax_number" value="{{ old('tax_number', $customer->tax_number) }}" />
                </x-form-group>

                <x-form-group label="Payment Term" for="payment_term_id" :error="$errors->first('payment_term_id')" :required="true">
                    <select id="payment_term_id" name="payment_term_id" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Pilih Payment Term</option>
                        @foreach($paymentTerms as $term)
                            <option value="{{ $term->id }}" {{ old('payment_term_id', $customer->payment_term_id) == $term->id ? 'selected' : '' }}>
                                {{ $term->term_name }} ({{ $term->days }} days)
                            </option>
                        @endforeach
                    </select>
                </x-form-group>

                <x-form-group label="Credit Limit" for="credit_limit" :error="$errors->first('credit_limit')" :required="true">
                    <x-input id="credit_limit" name="credit_limit" type="number" value="{{ old('credit_limit', $customer->credit_limit) }}" />
                </x-form-group>

                <x-form-group label="Status" for="status" :error="$errors->first('status')" :required="true">
                    <select id="status" name="status" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="ACTIVE" {{ old('status', $customer->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status', $customer->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </x-form-group>

                <x-form-group label="Email" for="email" :error="$errors->first('email')">
                    <x-input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" />
                </x-form-group>

                <x-form-group label="Phone" for="phone" :error="$errors->first('phone')">
                    <x-input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" />
                </x-form-group>

                <div class="md:col-span-2">
                    <x-form-group label="Address" for="address" :error="$errors->first('address')">
                        <x-textarea id="address" name="address">{{ old('address', $customer->address) }}</x-textarea>
                    </x-form-group>
                </div>

                <x-form-group label="City" for="city" :error="$errors->first('city')">
                    <x-input id="city" name="city" value="{{ old('city', $customer->city) }}" />
                </x-form-group>

                <div class="md:col-span-2">
                    <x-form-group label="Notes" for="notes" :error="$errors->first('notes')">
                        <x-textarea id="notes" name="notes">{{ old('notes', $customer->notes) }}</x-textarea>
                    </x-form-group>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-button href="{{ route('customers.index', ['tab' => $tab ?? $customer->source_type]) }}" variant="outline">
                    Batal
                </x-button>
                <x-button type="submit">
                    Update
                </x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
