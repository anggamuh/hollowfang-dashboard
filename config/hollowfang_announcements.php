<?php

$channels = [];
foreach (explode(',', (string) env('HOLLOWFANG_ANNOUNCE_CHANNELS', '')) as $entry) {
    [$name, $id] = array_pad(explode(':', trim($entry), 2), 2, '');
    if ($name !== '' && preg_match('/^\d{15,22}$/', $id)) {
        $channels[$id] = trim($name);
    }
}

return [
    'url' => env('MANUK_ANNOUNCE_API_URL', 'http://127.0.0.1:3211'),
    'channels' => $channels,
];
