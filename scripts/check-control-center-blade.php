<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Compile the actual control center template, not unrelated legacy starter views.
$paths = [
    __DIR__.'/../resources/views/livewire/control-center.blade.php',
    __DIR__.'/../resources/views/layouts/hollowfang.blade.php',
    __DIR__.'/../resources/views/livewire/hollowfang-v3.blade.php',
];

foreach ($paths as $path) {
    \Illuminate\Support\Facades\Blade::compileString(file_get_contents($path));
    fwrite(STDOUT, 'Compiled '.basename($path).PHP_EOL);
}
