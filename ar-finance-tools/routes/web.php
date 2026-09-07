<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Main Routes
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Placeholder routes for other pages
$pages = ['customers', 'exclusions', 'import-accurate', 'aging', 'reconciliation', 'reminders', 'credit', 'analyzer', 'tracker', 'aging-china', 'aging-normal', 'query-hutang'];

foreach ($pages as $page) {
    Route::get('/' . $page, function () use ($page) {
        $title = str_replace('-', ' ', ucfirst($page));
        return view('pages.placeholder', [
            'title' => $title,
            'currentPage' => $page
        ]);
    })->name($page);
}
