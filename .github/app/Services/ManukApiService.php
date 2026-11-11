<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ManukApiService
{
    public function health(): array
    {
        $offline = ['connected' => false, 'bot' => null, 'discord' => null, 'lavalink' => null, 'music' => null];
        $url = config('services.manuk.base_url');
        $token = config('services.manuk.token');
        if (! $url || ! $token) return $offline;
        try {
            $response = Http::acceptJson()->withToken($token)
                ->connectTimeout(2)->timeout(5)
                ->get(rtrim($url, '/') . '/api/health');
            if (! $response->successful()) return $offline;
            $data = $response->json();
            if (! is_array($data)) return $offline;
            return [
                'connected' => data_get($data, 'bot.connected') === true,
                'bot' => $data['bot'] ?? null,
                'discord' => $data['discord'] ?? null,
                'lavalink' => $data['lavalink'] ?? null,
                'music' => $data['music'] ?? null,
            ];
        } catch (Throwable $e) {
            report($e);
            return $offline;
        }
    }
}
