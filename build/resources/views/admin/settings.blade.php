@extends('layouts.app')
@section('content')
<div x-data="settingsPage()" x-init="init()">
    <div class="mb-6">
        <h1 class="text-xl font-bold">System Settings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Konfigurasi sistem AR Finance Tools</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4 flex items-center gap-2"><i data-lucide="settings" class="w-5 h-5"></i> General</h3>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">Company Name</label><input type="text" value="PT. DMX Trading Indonesia" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Currency</label><select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"><option>IDR (Rp)</option></select></div>
                <div><label class="block text-sm font-medium mb-1">Fiscal Year Start</label><select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"><option>January</option><option>April</option><option>July</option><option>October</option></select></div>
            </div>
        </div>

        <!-- Aging Buckets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4 flex items-center gap-2"><i data-lucide="calendar-clock" class="w-5 h-5"></i> Aging Buckets</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-green-500 rounded-full"></span><input type="number" value="30" class="w-20 px-2 py-1 bg-gray-100 dark:bg-gray-700 border-0 rounded text-sm text-center"><span class="text-sm">hari (Current)</span></div>
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-yellow-500 rounded-full"></span><input type="number" value="60" class="w-20 px-2 py-1 bg-gray-100 dark:bg-gray-700 border-0 rounded text-sm text-center"><span class="text-sm">hari (31-60)</span></div>
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-orange-500 rounded-full"></span><input type="number" value="90" class="w-20 px-2 py-1 bg-gray-100 dark:bg-gray-700 border-0 rounded text-sm text-center"><span class="text-sm">hari (61-90)</span></div>
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-red-500 rounded-full"></span><span class="text-sm">90+ hari (Overdue)</span></div>
            </div>
        </div>

        <!-- Reminder Config -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4 flex items-center gap-2"><i data-lucide="bell" class="w-5 h-5"></i> Reminder Schedule</h3>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">Auto-reminder Frequency</label><select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"><option>Setiap 7 hari</option><option>Setiap 14 hari</option><option>Setiap 30 hari</option></select></div>
                <div><label class="block text-sm font-medium mb-1">Max Reminders per Invoice</label><input type="number" value="5" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div class="flex items-center gap-3"><label class="text-sm font-medium">Enable WhatsApp</label><input type="checkbox" checked class="rounded bg-gray-100 dark:bg-gray-700"></div>
                <div class="flex items-center gap-3"><label class="text-sm font-medium">Enable Email</label><input type="checkbox" checked class="rounded bg-gray-100 dark:bg-gray-700"></div>
            </div>
        </div>

        <!-- Credit Limit Config -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4 flex items-center gap-2"><i data-lucide="credit-card" class="w-5 h-5"></i> Credit Limit Thresholds</h3>
            <div class="space-y-4">
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-green-500 rounded-full"></span><span class="text-sm flex-1">Safe</span><input type="number" value="75" class="w-20 px-2 py-1 bg-gray-100 dark:bg-gray-700 border-0 rounded text-sm text-center"><span class="text-sm">%</span></div>
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-yellow-500 rounded-full"></span><span class="text-sm flex-1">Caution</span><input type="number" value="95" class="w-20 px-2 py-1 bg-gray-100 dark:bg-gray-700 border-0 rounded text-sm text-center"><span class="text-sm">%</span></div>
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-red-500 rounded-full"></span><span class="text-sm flex-1">Critical</span><span class="text-sm">100%</span></div>
                <div class="flex items-center gap-3"><span class="w-3 h-3 bg-gray-800 rounded-full"></span><span class="text-sm flex-1">Block SO</span><span class="text-sm">&gt; 100%</span></div>
            </div>
        </div>

        <!-- Email Config -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4 flex items-center gap-2"><i data-lucide="mail" class="w-5 h-5"></i> Email Settings</h3>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">SMTP Host</label><input type="text" value="smtp.gmail.com" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">SMTP Port</label><input type="text" value="587" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">From Email</label><input type="email" value="ar@dmx.co.id" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
            </div>
        </div>

        <!-- WhatsApp Config -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-4 flex items-center gap-2"><i data-lucide="message-circle" class="w-5 h-5"></i> WhatsApp API Settings</h3>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">API Provider</label><select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"><option>Wablas</option><option>Fonnte</option><option>Manual (WA Web)</option></select></div>
                <div><label class="block text-sm font-medium mb-1">API Key</label><input type="password" value="••••••••••" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Device Name</label><input type="text" value="DMX-AR-Bot" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Save Settings</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
function settingsPage() { return { init() { this.$nextTick(() => lucide.createIcons()); } } }
</script>
@endsection
