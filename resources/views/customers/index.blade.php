<x-layouts.app>
    <x-slot name="header">
        <x-page-header title="Customer" description="Manage your customers">
            <x-button href="{{ route('customers.import.template') }}" variant="outline">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Template
            </x-button>
            <x-button onclick="document.getElementById('import_modal').showModal()">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Import Customer
            </x-button>
            <x-button href="{{ route('customers.create', ['tab' => $tab]) }}">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Customer
            </x-button>
        </x-page-header>
    </x-slot>

    {{-- Tabs External / Internal --}}
    <div class="mb-4 flex gap-1 rounded-lg border border-[#3d4557] bg-[#252d3d] p-1 w-fit">
        <a href="{{ route('customers.index', ['tab' => 'external']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'external' ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
            Customer External
        </a>
        <a href="{{ route('customers.index', ['tab' => 'internal']) }}"
           class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'internal' ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
            Customer Internal
        </a>
    </div>

    @if(session('success'))
        <x-alert type="success" class="mb-4">
            {{ session('success') }}
        </x-alert>
    @endif

    @if(session('error'))
        <x-alert type="error" class="mb-4">
            {{ session('error') }}
        </x-alert>
    @endif

    {{-- Table --}}
    <x-card>
        <div class="relative w-full overflow-auto">
            <table id="customers-table" class="w-full caption-bottom text-sm">
                <thead class="border-b border-[#3d4557]">
                    <tr>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Kode</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Nama</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Group</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Type</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Source</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Outstanding</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Status</th>
                        <th class="h-10 px-4 text-left font-medium text-[#b0b8c4]">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr class="border-b border-[#3d4557] transition-colors hover:bg-[#3a4556]/50">
                            <td class="p-4 text-white">{{ $customer->customer_code }}</td>
                            <td class="p-4">
                                <div>
                                    <div class="font-medium text-white">{{ $customer->customer_name }}</div>
                                    <div class="text-sm text-[#b0b8c4]">{{ $customer->email }}</div>
                                </div>
                            </td>
                            <td class="p-4 text-[#b0b8c4]">{{ $customer->customerGroup->group_name ?? '-' }}</td>
                            <td class="p-4 text-[#b0b8c4]">{{ $customer->customer_type }}</td>
                            <td class="p-4">
                                <x-badge :type="$customer->source_type === 'external' ? 'info' : 'warning'">
                                    {{ strtoupper($customer->source_type) }}
                                </x-badge>
                            </td>
                            <td class="p-4 font-medium text-white">Rp {{ number_format($customer->outstanding, 0, ',', '.') }}</td>
                            <td class="p-4">
                                <x-badge :type="$customer->status === 'ACTIVE' ? 'success' : 'secondary'">
                                    {{ $customer->status }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <x-button href="{{ route('customers.show', ['customer' => $customer, 'tab' => $tab]) }}" variant="ghost" size="icon">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </x-button>
                                    <x-button href="{{ route('customers.edit', ['customer' => $customer, 'tab' => $tab]) }}" variant="ghost" size="icon">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </x-button>
                                    <x-button onclick="openDeleteModal('{{ route('customers.destroy', ['customer' => $customer, 'tab' => $tab]) }}')" variant="ghost" size="icon">
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

    {{-- Import Modal --}}
    <dialog id="import_modal" class="modal">
        <div class="modal-box bg-[#252d3d] border border-[#3d4557]">
            <h3 class="font-bold text-lg text-white">Import Customer {{ $tab === 'external' ? 'External' : 'Internal' }}</h3>
            <p class="py-2 text-sm text-[#b0b8c4]">Upload file Excel dari Accurate (.xls/.xlsx) atau template (.xlsx). Jika kode customer sudah ada, data akan ditimpa (update).</p>

            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="source_type" value="{{ $tab }}">
                <div class="py-4">
                    <label for="file" class="block text-sm font-medium text-[#b0b8c4] mb-2">File Excel</label>
                    <input type="file"
                           id="file"
                           name="file"
                           accept=".xlsx,.xls"
                           required
                           class="block w-full text-sm text-[#b0b8c4] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#e8c493] file:text-[#1a1f2e] hover:file:bg-[#d4a574] cursor-pointer" />
                    @error('file')
                        <p class="mt-2 text-sm text-[#ef4444]">{{ $message }}</p>
                    @enderror
                    <p class="mt-3 text-xs text-[#7a8494]">
                        Format Accurate (Customer No., Customer Name, dst) atau template:
                        customer_code, customer_name, group_code/group_name, customer_type, payment_term.
                        <a href="{{ route('customers.import.template') }}" class="text-[#1dd7d7] underline">Download template</a>
                    </p>
                </div>
                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('import_modal').close()">Batal</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

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
            $('#customers-table').DataTable({
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
