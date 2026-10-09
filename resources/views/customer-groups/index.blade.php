<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Group Customer" description="Manage customer groups">
            <x-button href="{{ route('customer-groups.create') }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Group
            </x-button>
        </x-page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" class="mb-4">
            {{ session('success') }}
        </x-alert>
    @endif

    <x-card>
        <div class="relative w-full overflow-auto">
            <table id="groups-table" class="w-full caption-bottom text-sm">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Kode</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Nama Group</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Description</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Customers</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50">
                            <td class="p-4 text-foreground">{{ $group->group_code }}</td>
                            <td class="p-4 font-medium text-foreground">{{ $group->group_name }}</td>
                            <td class="p-4 text-muted-foreground">{{ Str::limit($group->description, 50) }}</td>
                            <td class="p-4 text-muted-foreground">{{ $group->customers_count }}</td>
                            <td class="p-4">
                                <x-badge :type="$group->status === 'ACTIVE' ? 'success' : 'secondary'">
                                    {{ $group->status }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <x-button href="{{ route('customer-groups.show', $group) }}" variant="ghost" size="icon">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </x-button>
                                    <x-button href="{{ route('customer-groups.edit', $group) }}" variant="ghost" size="icon">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </x-button>
                                    <x-button onclick="openDeleteModal('{{ route('customer-groups.destroy', $group) }}')" variant="ghost" size="icon">
                                        <svg class="h-4 w-4 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Delete Modal --}}
    <dialog id="delete_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-foreground">Konfirmasi Hapus</h3>
            <p class="py-4 text-muted-foreground">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-action">
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error">Hapus</button>
                </form>
                <button class="btn btn-ghost" onclick="document.getElementById('delete_modal').close()">Batal</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#groups-table').DataTable({
                pageLength: 25,
                order: [],
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
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    }
                }
            });
        });

        function openDeleteModal(url) {
            document.getElementById('delete-form').action = url;
            document.getElementById('delete_modal').showModal();
        }
    </script>
    @endpush
</x-layouts.app>
