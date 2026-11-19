<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Discord\Provider as DiscordProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ==========================================
        // DISCORD OAUTH PROVIDER
        // ==========================================

        Event::listen(
            SocialiteWasCalled::class,
            function (SocialiteWasCalled $event): void {
                $event->extendSocialite(
                    'discord',
                    DiscordProvider::class
                );
            }
        );

        // ==========================================
        // HTTPS UNTUK NGROK
        // ==========================================

        // Berlaku hanya di environment local,
        // saat APP_URL menggunakan HTTPS.
        if (
            app()->environment('local') &&
            str_starts_with(
                (string) config('app.url'),
                'https://'
            )
        ) {
            URL::forceScheme('https');
        }
    }
}
