{{-- Tab navigasi halaman Piutang (di bawah menu "Outstanding") --}}
@php $preserve = array_filter(['all' => request('all')], fn ($v) => $v !== null); @endphp
<div class="mb-4 flex gap-1 rounded-lg border border-border bg-card p-1 w-fit">
    <a href="{{ route('invoices.outstanding', $preserve) }}"
       class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ request()->routeIs('invoices.outstanding') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
        DB
    </a>
    <a href="{{ route('invoices.standard-report', $preserve) }}"
       class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ request()->routeIs('invoices.standard-report') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
        Standard Report
    </a>
    <a href="{{ route('invoices.china-report', $preserve) }}"
       class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ request()->routeIs('invoices.china-report') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
        China Report
    </a>
    <a href="{{ route('invoices.china-report.global') }}"
       class="rounded-md px-4 py-2 text-sm font-medium transition-colors {{ request()->routeIs('invoices.china-report.global') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
        Global
    </a>
</div>
