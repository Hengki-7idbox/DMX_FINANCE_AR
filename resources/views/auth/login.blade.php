<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Finance AR') }}</title>

    <script>
        (function () {
            var theme = localStorage.getItem('theme');
            if (theme !== 'light' && theme !== 'dark') {
                theme = 'dark';
            }
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(theme);
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background font-sans antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="rounded-lg border border-border bg-card p-6 shadow-lg">
            <div class="text-center mb-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary mx-auto mb-4">
                    <span class="text-lg font-bold text-primary-foreground">FA</span>
                </div>
                <h1 class="text-2xl font-bold text-foreground">Finance AR</h1>
                <p class="text-sm text-muted-foreground">Masuk ke akun Anda</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-muted-foreground">Email</label>
                    <input type="text"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autofocus
                           class="flex h-9 w-full rounded-lg border border-border bg-input px-3 py-1 text-sm text-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    @error('email')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-muted-foreground">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           class="flex h-9 w-full rounded-lg border border-border bg-input px-3 py-1 text-sm text-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    @error('password')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox"
                               name="remember"
                               class="h-4 w-4 rounded border-border bg-input text-primary focus:ring-ring">
                        <span class="text-sm text-muted-foreground">Ingat saya</span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
