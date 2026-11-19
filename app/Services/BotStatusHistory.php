<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BotStatusHistory
{
    private const KEY = 'hollowfang:bot-monitoring:transitions:v1';

    /**
     * Save only state changes. A failed API check is not the same as a bot
     * disconnect, so preserve its distinct diagnostic status.
     *
     * @param array<string, mixed> $snapshot
     * @return array<int, array{status: string, at: string}>
     */
    public function record(array $snapshot): array
    {
        $status = (string) ($snapshot['status'] ?? 'unconfigured');
        $allowed = ['online', 'bot_offline', 'unreachable', 'unauthorized', 'http_error', 'invalid_response', 'unconfigured'];
        if (! in_array($status, $allowed, true)) {
            $status = 'invalid_response';
        }

        $history = Cache::get(self::KEY, []);
        $history = is_array($history) ? array_values(array_filter($history, static fn ($entry) => is_array($entry)
            && is_string($entry['status'] ?? null)
            && is_string($entry['at'] ?? null))) : [];

        if (($history[0]['status'] ?? null) !== $status) {
            array_unshift($history, [
                'status' => $status,
                'at' => now()->toIso8601String(),
            ]);
            $history = array_slice($history, 0, 20);
        }

        // Keep recent changes for seven days; do not store API tokens or user data.
        Cache::put(self::KEY, $history, now()->addDays(7));

        return $history;
    }
}
