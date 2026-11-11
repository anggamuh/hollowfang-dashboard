
<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use SocialiteProviders\Manager\ServiceProvider as SocialiteServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    SocialiteServiceProvider::class,
];
