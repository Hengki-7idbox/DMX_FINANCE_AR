<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AR Finance Tools</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen login-bg flex items-center justify-center p-4" x-data="loginApp()">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="login-card rounded-3xl p-8 shadow-xl">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i data-lucide="receipt" class="w-8 h-8 text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-surface-900">AR Finance Tools</h1>
                <p class="text-sm text-surface-500 mt-1">PT. DMX Trading Indonesia</p>
            </div>

            <!-- Error Message -->
            <div x-show="error" x-cloak x-transition
                 class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-sm text-red-600" x-text="error"></p>
            </div>

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-surface-700 mb-1.5">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="w-4 h-4 text-surface-400"></i>
                        </div>
                        <input id="email" name="email" type="text" x-model="email"
                               class="input pl-10"
                               placeholder="admin@dmx.co.id">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-surface-700 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-4 h-4 text-surface-400"></i>
                        </div>
                        <input id="password" name="password" type="password" x-model="password"
                               class="input pl-10"
                               placeholder="Masukkan password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-surface-300 text-primary-500 focus:ring-primary-500">
                        <span class="text-sm text-surface-600">Ingat saya</span>
                    </label>
                    <a href="#" class="text-sm text-primary-500 hover:text-primary-600 font-medium">Lupa password?</a>
                </div>

                <button type="submit"
                        class="login-btn w-full py-3 rounded-xl font-semibold text-sm"
                        :disabled="loading">
                    <span x-show="!loading">Masuk</span>
                    <span x-show="loading" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <p class="text-center text-xs text-surface-400 mt-6">
                &copy; 2026 PT. DMX Trading Indonesia
            </p>
        </div>
    </div>

    <script>
        function loginApp() {
            return {
                email: '',
                password: '',
                error: '',
                loading: false,

                init() {
                    // Check for error from server
                    @if(session('error'))
                        this.error = '{{ session("error") }}';
                    @endif
                }
            };
        }
    </script>
</body>
</html>
