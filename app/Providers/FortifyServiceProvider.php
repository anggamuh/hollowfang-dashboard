<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::loginView(function () {
            // Gunakan halaman login Laravel jika tersedia.
            if (view()->exists('auth.login')) {
                return view('auth.login');
            }

            // Jika halaman login hilang saat recovery,
            // arahkan ke login Discord Hollowfang.
            return redirect()->route('discord.login');
        });
    }
}
