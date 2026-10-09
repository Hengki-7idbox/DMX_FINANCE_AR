<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Bank Account" description="Manage bank accounts">
            <x-button href="{{ route('bank-accounts.create') }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Bank
            </x-button>
        </x-page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card>
        <div class="relative w-full overflow-auto">
            <table id="banks-table" class="w-full caption-bottom text-sm">
                <thead class="border-b border-border">
                    <tr>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Bank Name</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Account Number</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Account Name</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Currency</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Status</th>
                        <th class="h-10 px-4 text-left font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankAccounts as $bank)
                        <tr class="border-b border-border transition-colors hover:bg-accent/50">
                            <td class="p-4 font-medium text-foreground">{{ $bank->bank_name }}</td>
                            <td class="p-4 text-foreground">{{ $bank->account_number }}</td>
                            <td class="p-4 text-muted-foreground">{{ $bank->account_name }}</td>
                            <td class="p-4 text-muted-foreground">{{ $bank->currency }}</td>
                            <td class="p-4">
                                <x-badge :type="$bank->status === 'ACTIVE' ? 'success' : 'secondary'">
                                    {{ $bank->status }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <x-button href="{{ route('bank-accounts.edit', $bank) }}" variant="ghost" size="icon">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </x-button>
                                    <x-button onclick="openDeleteModal('{{ route('bank-accounts.destroy', $bank) }}')" variant="ghost" size="icon">
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
            $('#banks-table').DataTable({
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
