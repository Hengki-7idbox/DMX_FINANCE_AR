<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Payment Term" description="Manage payment terms">
            <x-button href="{{ route('payment-terms.create') }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Term
            </x-button>
        </x-page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card>
        <div class="relative w-full overflow-auto">
            <table id="terms-table" class="w-full caption-bottom text-sm">
                <thead class="border-b border-[#3d4557]">
                    <tr>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Kode</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Nama Term</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Days</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Description</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Status</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentTerms as $term)
                        <tr class="border-b border-[#3d4557] transition-colors hover:bg-[#3a4556]/50">
                            <td class="p-4 text-white">{{ $term->term_code }}</td>
                            <td class="p-4 font-medium text-white">{{ $term->term_name }}</td>
                            <td class="p-4 text-[#b0b8c4]">{{ $term->days }} hari</td>
                            <td class="p-4 text-[#b0b8c4]">{{ Str::limit($term->description, 50) }}</td>
                            <td class="p-4">
                                <x-badge :type="$term->status === 'ACTIVE' ? 'success' : 'secondary'">
                                    {{ $term->status }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <x-button href="{{ route('payment-terms.edit', $term) }}" variant="ghost" size="icon">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </x-button>
                                    <x-button onclick="openDeleteModal('{{ route('payment-terms.destroy', $term) }}')" variant="ghost" size="icon">
                                        <svg class="h-4 w-4 text-[#ef4444]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
        <div class="modal-box bg-[#252d3d] border border-[#3d4557]">
            <h3 class="font-bold text-lg text-white">Konfirmasi Hapus</h3>
            <p class="py-4 text-[#b0b8c4]">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
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
            $('#terms-table').DataTable({
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
