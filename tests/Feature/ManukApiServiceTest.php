<?php

namespace Tests\Feature;

use App\Services\ManukApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManukApiServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_unconfigured_monitoring_does_not_send_requests_or_invent_statistics(): void
    {
        config()->set('services.manuk.base_url', null);
        config()->set('services.manuk.token', null);
        Http::fake();

        $data = app(ManukApiService::class)->health();

        $this->assertFalse($data['connected']);
        $this->assertSame('unconfigured', $data['status']);
        $this->assertNull($data['discord']);
        $this->assertNull($data['music']);
        Http::assertNothingSent();
    }

    public function test_online_bot_returns_genuine_api_fields(): void
    {
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        config()->set('services.manuk.token', str_repeat('x', 40));

        Http::fake(['127.0.0.1:3210/api/health' => Http::response([
            'bot' => ['connected' => true, 'name' => 'crypt', 'ping_ms' => 32, 'uptime_seconds' => 345],
            'discord' => ['connected' => true, 'guild_name' => 'Hollowfang', 'total_members' => 100],
            'lavalink' => ['connected' => true, 'status' => 'Connected'],
            'music' => ['active_sessions' => 1, 'queue' => []],
        ])]);

        $data = app(ManukApiService::class)->health();

        $this->assertTrue($data['connected']);
        $this->assertSame('online', $data['status']);
        $this->assertSame(32, $data['bot']['ping_ms']);
        $this->assertSame(100, $data['discord']['total_members']);
        Http::assertSentCount(1);
    }

    public function test_unauthorized_monitoring_is_reported_without_exposing_credentials(): void
    {
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        config()->set('services.manuk.token', str_repeat('s', 40));
        Http::fake(['*' => Http::response(['error' => 'Unauthorized'], 401)]);

        $data = app(ManukApiService::class)->health();

        $this->assertSame('unauthorized', $data['status']);
        $this->assertFalse($data['connected']);
        $this->assertStringNotContainsString(str_repeat('s', 40), json_encode($data));
    }

    public function test_bad_api_response_and_network_failure_are_safe(): void
    {
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        config()->set('services.manuk.token', str_repeat('a', 40));

        Http::fake(['*' => Http::response(['unexpected' => true])]);
        $this->assertSame('invalid_response', app(ManukApiService::class)->health()['status']);

        Http::fake(['*' => function () { throw new \RuntimeException('private exception text'); }]);
        $data = app(ManukApiService::class)->health();
        $this->assertSame('unreachable', $data['status']);
        $this->assertStringNotContainsString('private exception text', json_encode($data));
    }

    public function test_invalid_monitoring_origin_never_receives_token(): void
    {
        config()->set('services.manuk.base_url', 'http://someone:pass@host:3210');
        config()->set('services.manuk.token', str_repeat('a', 40));
        Http::fake();

        $this->assertSame('unconfigured', app(ManukApiService::class)->health()['status']);
        Http::assertNothingSent();
    }
}
