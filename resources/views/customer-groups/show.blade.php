<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Detail Group Customer" back-url="{{ route('customer-groups.index') }}">
            <x-button href="{{ route('customer-groups.edit', $group) }}" variant="outline">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </x-button>
        </x-page-header>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Informasi Group" class="lg:col-span-2">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-muted-foreground">Kode Group</p>
                    <p class="font-medium text-foreground">{{ $group->group_code }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Nama Group</p>
                    <p class="font-medium text-foreground">{{ $group->group_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Status</p>
                    <x-badge :type="$group->status === 'ACTIVE' ? 'success' : 'secondary'">
                        {{ $group->status }}
                    </x-badge>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-muted-foreground">Description</p>
                    <p class="font-medium text-foreground">{{ $group->description ?? '-' }}</p>
                </div>
            </div>
        </x-card>

        <x-card title="Jumlah Customer">
            <div class="text-center py-4">
                <p class="text-sm text-muted-foreground">Customers</p>
                <p class="text-3xl font-bold text-foreground">{{ $group->customers_count ?? $group->customers->count() }}</p>
            </div>
        </x-card>
    </div>

    <x-card title="Customer pada Group Ini" class="mt-6">
        <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Kode</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Nama</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Type</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Source</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($group->customers as $customer)
                        <tr class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-4 text-foreground">{{ $customer->customer_code }}</td>
                            <td class="p-4 text-foreground">{{ $customer->customer_name }}</td>
                            <td class="p-4 text-muted-foreground">{{ $customer->customer_type }}</td>
                            <td class="p-4">
                                <x-badge :type="$customer->source_type === 'external' ? 'info' : 'warning'">
                                    {{ strtoupper($customer->source_type) }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <x-badge :type="$customer->status === 'ACTIVE' ? 'success' : 'secondary'">
                                    {{ $customer->status }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-muted-foreground">
                                Belum ada customer pada group ini
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.app>
