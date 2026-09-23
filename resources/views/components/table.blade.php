@props([
    'id' => 'data-table',
    'striped' => true,
    'hoverable' => true,
])

<div class="relative w-full overflow-auto">
    <table {{ $attributes->merge(['class' => 'w-full caption-bottom text-sm', 'id' => $id]) }}>
        {{ $slot }}
    </table>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('{{ $id }}');
    if (table && typeof $.fn.DataTable !== 'undefined') {
        $(table).DataTable({
            pageLength: 25,
            order: [],
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
    }
});
</script>
@endonce
