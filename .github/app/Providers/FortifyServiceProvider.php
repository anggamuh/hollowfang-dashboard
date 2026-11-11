<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Emergency bootstrap provider. Restore previous Fortify configuration
 * from editor history once available (registration/reset/2FA customizations).
 */
class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void {}
}
