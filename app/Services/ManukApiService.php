<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ManukApiService
{
    /**
     * Read-only monitoring. Never expose API tokens or exception messages to the UI.
     * The remote bot remains authoritative; unknown metrics stay null, not zero.
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $snapshot = [
            'connected' => false,
            'bot' => null,
            'discord' => null,
            'lavalink' => null,
            'music' => null,
            'checked_at' => now()->toIso8601String(),
            'status' => 'unconfigured',
        ];

        $url = trim((string) config('services.manuk.base_url'));
        $token = trim((string) config('services.manuk.token'));

        if ($url === '' || strlen($token) < 32) {
            return $snapshot;
        }

        // Only allow explicit HTTP(S) origins; never forward auth to a redirect.
        $parts = parse_url($url);
        if (! is_array($parts)
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return $snapshot;
        }

        $snapshot['status'] = 'unreachable';

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withoutRedirecting()
                ->connectTimeout(2)
                ->timeout(5)
                ->get(rtrim($url, '/').'/api/health');

            if ($response->status() === 401 || $response->status() === 403) {
                $snapshot['status'] = 'unauthorized';

                return $snapshot;
            }

            if (! $response->successful()) {
                $snapshot['status'] = 'http_error';

                return $snapshot;
            }

            $data = $response->json();
            if (! is_array($data) || ! is_array($data['bot'] ?? null)) {
                $snapshot['status'] = 'invalid_response';

                return $snapshot;
            }

            $online = data_get($data, 'bot.connected') === true;

            return [
                'connected' => $online,
                'bot' => $data['bot'],
                'discord' => is_array($data['discord'] ?? null) ? $data['discord'] : null,
                'lavalink' => is_array($data['lavalink'] ?? null) ? $data['lavalink'] : null,
                'music' => is_array($data['music'] ?? null) ? $data['music'] : null,
                'checked_at' => $snapshot['checked_at'],
                'status' => $online ? 'online' : 'bot_offline',
            ];
        } catch (Throwable $exception) {
            // Connection failures are expected when bot and dashboard run on different hosts.
            return $snapshot;
        }
    }
}
