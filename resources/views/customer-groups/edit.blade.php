<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Edit Group Customer" back-url="{{ route('customer-groups.index') }}" />
    </x-slot>

    <x-card>
        <form action="{{ route('customer-groups.update', $group) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid gap-6 md:grid-cols-2">
                <x-form-group label="Kode Group" for="group_code">
                    <x-input id="group_code" value="{{ $group->group_code }}" disabled />
                </x-form-group>

                <x-form-group label="Nama Group" for="group_name" :error="$errors->first('group_name')" :required="true">
                    <x-input id="group_name" name="group_name" value="{{ old('group_name', $group->group_name) }}" />
                </x-form-group>

                <div class="md:col-span-2">
                    <x-form-group label="Description" for="description" :error="$errors->first('description')">
                        <x-textarea id="description" name="description">{{ old('description', $group->description) }}</x-textarea>
                    </x-form-group>
                </div>

                <x-form-group label="Status" for="status" :error="$errors->first('status')" :required="true">
                    <select id="status" name="status" class="flex h-9 w-full rounded-lg border border-border bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="ACTIVE" {{ old('status', $group->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status', $group->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </x-form-group>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-button href="{{ route('customer-groups.index') }}" variant="outline">Batal</x-button>
                <x-button type="submit">Update</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
