@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <div class="w-20 h-20 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <i data-lucide="construction" class="w-10 h-10 text-primary-500"></i>
        </div>
        <h2 class="text-2xl font-bold text-surface-900 mb-2">{{ $title }}</h2>
        <p class="text-surface-500 mb-6">Halaman ini sedang dalam pengembangan</p>
        <a href="/dashboard" class="btn btn-primary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection
