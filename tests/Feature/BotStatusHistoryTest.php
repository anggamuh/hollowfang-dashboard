<?php

namespace Tests\Feature;

use App\Services\BotStatusHistory;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BotStatusHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::forget('hollowfang:bot-monitoring:transitions:v1');
    }

    public function test_repeated_status_checks_do_not_create_duplicate_events(): void
    {
        $history = app(BotStatusHistory::class);
        $first = $history->record(['status' => 'online']);
        $second = $history->record(['status' => 'online']);

        $this->assertCount(1, $second);
        $this->assertSame($first, $second);
        $this->assertSame('online', $second[0]['status']);
    }

    public function test_status_transitions_keep_recent_first_without_sensitive_fields(): void
    {
        $history = app(BotStatusHistory::class);
        $history->record(['status' => 'online', 'token' => 'never-save-this-secret']);
        $history->record(['status' => 'unreachable', 'exception' => 'private detail']);
        $events = $history->record(['status' => 'online']);

        $this->assertSame(['online', 'unreachable', 'online'], array_column($events, 'status'));
        $this->assertCount(3, $events);
        $this->assertArrayHasKey('at', $events[0]);
        $this->assertStringNotContainsString('never-save-this-secret', json_encode($events));
        $this->assertStringNotContainsString('private detail', json_encode($events));
    }

    public function test_history_is_bounded_and_unknown_status_is_sanitized(): void
    {
        $history = app(BotStatusHistory::class);
        for ($i = 0; $i < 40; $i++) {
            $events = $history->record(['status' => $i % 2 ? 'online' : 'unreachable']);
        }

        $this->assertCount(20, $events);
        $events = $history->record(['status' => 'unexpected', 'token' => 'private']);
        $this->assertCount(20, $events);
        $this->assertSame('invalid_response', $events[0]['status']);
    }
}
