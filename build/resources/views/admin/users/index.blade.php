@extends('layouts.app')
@section('content')
<div x-data="usersPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">User Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola pengguna dan akses sistem</p>
        </div>
        <button @click="showModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Tambah User
        </button>
    </div>

    <!-- User Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Name</th>
                        <th class="text-left px-4 py-3 font-medium">Email</th>
                        <th class="text-left px-4 py-3 font-medium">Role</th>
                        <th class="text-center px-4 py-3 font-medium">Status</th>
                        <th class="text-left px-4 py-3 font-medium">Last Login</th>
                        <th class="text-right px-4 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center"><span class="text-xs font-semibold text-blue-600">H</span></div>
                                <span class="font-medium">Hengki</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">hengki@dmx.co.id</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 rounded-full text-xs font-medium">Admin</span></td>
                        <td class="px-4 py-3 text-center"><span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span></td>
                        <td class="px-4 py-3 text-gray-500">Hari ini, 09:15</td>
                        <td class="px-4 py-3 text-right"><button class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center"><span class="text-xs font-semibold text-green-600">A</span></div>
                                <span class="font-medium">Ahmad Fauzi</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">ahmad@dmx.co.id</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 rounded-full text-xs font-medium">AR Accountant</span></td>
                        <td class="px-4 py-3 text-center"><span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span></td>
                        <td class="px-4 py-3 text-gray-500">Hari ini, 08:30</td>
                        <td class="px-4 py-3 text-right"><button class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center"><span class="text-xs font-semibold text-yellow-600">S</span></div>
                                <span class="font-medium">Siti Rahayu</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">siti@dmx.co.id</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 rounded-full text-xs font-medium">AR Collector</span></td>
                        <td class="px-4 py-3 text-center"><span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span></td>
                        <td class="px-4 py-3 text-gray-500">Kemarin, 17:45</td>
                        <td class="px-4 py-3 text-right"><button class="text-blue-600 hover:text-blue-700 text-xs font-medium">Edit</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div x-show="showModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.away="showModal = false">
            <h3 class="text-lg font-semibold mb-4">Tambah User Baru</h3>
            <form class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">Nama Lengkap</label><input type="text" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Role</label>
                    <select class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm">
                        <option>AR Accountant</option><option>AR Collector</option><option>Finance Manager</option><option>Sales Manager</option><option>Viewer</option>
                    </select>
                </div>
                <div><label class="block text-sm font-medium mb-1">Password</label><input type="password" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm"></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function usersPage() { return { showModal: false, init() { this.$nextTick(() => lucide.createIcons()); } } }
</script>
@endsection
