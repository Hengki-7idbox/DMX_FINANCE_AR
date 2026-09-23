<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Finance AR') }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#1a1f2e] font-sans antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="rounded-lg border border-[#3d4557] bg-[#252d3d] p-6 shadow-lg">
            <div class="text-center mb-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#e8c493] mx-auto mb-4">
                    <span class="text-lg font-bold text-[#1a1f2e]">FA</span>
                </div>
                <h1 class="text-2xl font-bold text-white">Finance AR</h1>
                <p class="text-sm text-[#b0b8c4]">Masuk ke akun Anda</p>
            </div>
            
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-[#b0b8c4]">Email</label>
                    <input type="text" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}"
                           required 
                           autofocus
                           class="flex h-9 w-full rounded-lg border border-[#4a556a] bg-[#3a4556] px-3 py-1 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1dd7d7]">
                    @error('email')
                        <p class="text-sm text-[#ef4444]">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-[#b0b8c4]">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="flex h-9 w-full rounded-lg border border-[#4a556a] bg-[#3a4556] px-3 py-1 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1dd7d7]">
                    @error('password')
                        <p class="text-sm text-[#ef4444]">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" 
                               name="remember" 
                               class="h-4 w-4 rounded border-[#4a556a] bg-[#3a4556] text-[#e8c493] focus:ring-[#1dd7d7]">
                        <span class="text-sm text-[#b0b8c4]">Ingat saya</span>
                    </label>
                </div>
                
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center rounded-lg bg-[#e8c493] px-4 py-2 text-sm font-medium text-[#1a1f2e] hover:bg-[#d4a574] focus:outline-none focus:ring-2 focus:ring-[#1dd7d7]">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
