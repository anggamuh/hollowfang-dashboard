<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ControlCenter;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Middleware\EnsureHollowfangAccess;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Laravel Starter Kit
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');
});

require __DIR__.'/settings.php';

/*
|--------------------------------------------------------------------------
| Discord OAuth2 Authentication
|--------------------------------------------------------------------------
*/

// Login menggunakan Discord
Route::middleware('guest')->group(function () {
    Route::get('/auth/discord', [
        DiscordController::class,
        'redirect',
    ])->name('discord.login');
});

// Hubungkan akun Discord ke akun Laravel
Route::middleware('auth')->group(function () {
    Route::get('/auth/discord/connect', [
        DiscordController::class,
        'connect',
    ])->name('discord.connect');
});

// Callback dari Discord OAuth2
Route::get('/auth/discord/callback', [
    DiscordController::class,
    'callback',
])
    ->middleware('throttle:10,1')
    ->name('discord.callback');

/*
|--------------------------------------------------------------------------
| Hollowfang Control Center
|--------------------------------------------------------------------------
*/

// Hanya akun Discord yang diizinkan
Route::middleware([
    'auth',
    EnsureHollowfangAccess::class,
])->group(function () {

    Route::livewire('/control', ControlCenter::class)
        ->name('control');

});
