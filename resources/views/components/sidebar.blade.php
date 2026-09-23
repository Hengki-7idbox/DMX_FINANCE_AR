<aside class="fixed left-0 top-0 z-40 h-screen w-64 border-r border-[#3d4557] bg-[#252d3d]">
    <div class="flex h-full flex-col">
        <!-- Logo -->
        <div class="flex h-14 items-center border-b border-[#3d4557] px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#e8c493]">
                    <span class="text-sm font-bold text-[#1a1f2e]">FA</span>
                </div>
                <span class="text-lg font-semibold text-white">Finance AR</span>
            </a>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" 
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
            
            {{-- Master Data --}}
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">Master Data</p>
                
                <a href="{{ route('customers.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('customers.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Customer
                </a>
                
                <a href="{{ route('customer-groups.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('customer-groups.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Group Customer
                </a>
                
                <a href="{{ route('payment-terms.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('payment-terms.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                    </svg>
                    Payment Term
                </a>
                
                <a href="{{ route('bank-accounts.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('bank-accounts.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Bank Account
                </a>
            </div>
            
            {{-- Piutang --}}
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">Piutang</p>
                
                <a href="{{ route('invoices.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('invoices.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Invoice
                </a>
                
                <a href="{{ route('invoices.aging') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('invoices.aging') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Aging
                </a>
            </div>
            
            {{-- Pembayaran --}}
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">Pembayaran</p>
                
                <a href="{{ route('payments.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('payments.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Payment
                </a>
            </div>
            
            {{-- Bank --}}
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">Bank</p>
                
                <a href="{{ route('bank-transactions.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('bank-transactions.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Rekening Koran
                </a>
                
                <a href="{{ route('reconciliations.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('reconciliations.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Rekonsiliasi
                </a>
            </div>
            
            {{-- Collection --}}
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">Collection</p>
                
                <a href="{{ route('collection.dashboard') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('collection.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    Collection
                </a>
            </div>
            
            {{-- Laporan --}}
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">Laporan</p>
                
                <a href="{{ route('reports.aging') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Reports
                </a>
            </div>
            
            {{-- System --}}
            @if(auth()->user()->hasRole('super_admin'))
            <div class="pt-4">
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-[#7a8494]">System</p>
                
                <a href="{{ route('users.index') }}" 
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-[#e8c493] text-[#1a1f2e]' : 'text-[#b0b8c4] hover:bg-[#3a4556] hover:text-white' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Users
                </a>
            </div>
            @endif
        </nav>
        
        <!-- User Info -->
        <div class="border-t border-[#3d4557] p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8c493]">
                    <span class="text-sm font-medium text-[#1a1f2e]">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-[#b0b8c4] truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[#b0b8c4] hover:text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
