<?php

namespace Tests\Feature;

use App\Livewire\HollowfangCommunity;
use App\Livewire\HollowfangV3;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HollowfangPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hollowfang.owner_discord_ids', ['111111111111111111']);
        config()->set('services.manuk.token', str_repeat('x', 40));
        config()->set('hollowfang_announcements.url', 'http://127.0.0.1:3211');
        config()->set('app.timezone', 'UTC');
        $user = User::factory()->create();
        $user->forceFill(['discord_id' => '111111111111111111'])->save();
        $this->actingAs($user);
        Http::preventStrayRequests();
    }

    private function settings(): array
    {
        return ['welcomeEnabled' => false, 'goodbyeEnabled' => false, 'autoModEnabled' => false,
            'blockLinks' => false, 'welcomeText' => 'Welcome', 'goodbyeText' => 'Goodbye',
            'welcomeHeader' => 'Hello', 'welcomeTitle' => 'Hollowfang', 'spamLimit' => 5,
            'spamWindowSeconds' => 10, 'blockedWords' => ['spam']];
    }

    private function fakeEvents(): void
    {
        Http::fake([
            '*/api/announcements/directory' => Http::response(['channels' => [['id' => '222222222222222222', 'name' => 'events', 'allowed' => true]]]),
            '*/api/v3/events' => Http::response(['ok' => true, 'events' => [
                ['id' => 'one', 'title' => 'Game Night', 'status' => 'scheduled', 'description' => 'Valorant'],
                ['id' => 'two', 'title' => 'Movie Night', 'status' => 'sent', 'description' => 'Film'],
            ]]),
        ]);
    }

    public function test_event_datetime_is_wib_even_when_server_uses_utc(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20T02:00:00Z'));
        $this->fakeEvents();
        Livewire::test(HollowfangV3::class, ['initialTab' => 'events'])
            ->set('eventTitle', 'Game Night')->set('eventChannelId', '222222222222222222')
            ->set('eventAt', '2026-11-20T10:00')->call('createEvent')->assertHasNoErrors();
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['at'] === '2026-11-20T10:00:00+07:00'
            && $request['actor'] === '111111111111111111');
    }

    public function test_past_wib_time_is_rejected_even_if_it_looks_future_in_utc(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20T02:00:00Z'));
        $this->fakeEvents();
        Livewire::test(HollowfangV3::class, ['initialTab' => 'events'])
            ->set('eventTitle', 'Old schedule')->set('eventChannelId', '222222222222222222')
            ->set('eventAt', '2026-11-20T08:00')->call('createEvent')->assertHasErrors('eventAt')
            ->set('eventAt', '2026-02-30T12:00')->call('createEvent')->assertHasErrors('eventAt');
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_event_filters_combine_without_new_api_calls(): void
    {
        $this->fakeEvents();
        Livewire::test(HollowfangV3::class, ['initialTab' => 'events'])
            ->set('eventSearch', 'NIGHT')->set('eventStatus', 'scheduled')
            ->assertSee('Game Night')->assertDontSee('Movie Night')
            ->set('eventSearch', 'missing')->assertSee('Tidak ada jadwal yang cocok.');
        Http::assertSentCount(2);
    }

    public function test_sync_preserves_form_and_explicit_reload_replaces_it(): void
    {
        Http::fake([
            '*/api/v4/status' => Http::response(['config' => $this->settings()]),
            '*/api/announcements/directory' => Http::response(['channels' => [], 'roles' => []]),
        ]);
        Livewire::test(HollowfangCommunity::class)->set('config.welcomeText', 'Unsaved draft')
            ->set('words', 'draft, words')->call('refreshData')
            ->assertSet('config.welcomeText', 'Unsaved draft')->assertSet('words', 'draft, words')
            ->call('reloadSettings')->assertSet('config.welcomeText', 'Welcome')->assertSet('words', 'spam');
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_failed_reload_keeps_the_form(): void
    {
        Http::fake([
            '*/api/v4/status' => Http::sequence()->push(['config' => $this->settings()])->push([], 503),
            '*/api/announcements/directory' => Http::response([]),
        ]);
        Livewire::test(HollowfangCommunity::class)->set('config.welcomeText', 'Keep me')
            ->call('reloadSettings')->assertSet('config.welcomeText', 'Keep me')
            ->assertSee('Perubahan di form tetap disimpan.');
    }

    public function test_unacknowledged_save_never_sends_welcome_preview(): void
    {
        Http::fake([
            '*/api/v4/status' => Http::response(['config' => $this->settings()]),
            '*/api/announcements/directory' => Http::response([]),
            '*/api/v4/settings' => Http::response(['ok' => false]),
        ]);
        Livewire::test(HollowfangCommunity::class)->set('config.welcomeText', 'Keep draft')
            ->call('testWelcome')->assertSet('config.welcomeText', 'Keep draft')
            ->assertSee('Bot belum mengonfirmasi penyimpanan.');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'test-welcome'));
    }

    public function test_successful_save_does_not_replace_form_with_stale_status(): void
    {
        Http::fake([
            '*/api/v4/status' => Http::response(['config' => $this->settings()]),
            '*/api/announcements/directory' => Http::response([]),
            '*/api/v4/settings' => Http::response(['ok' => true]),
        ]);
        Livewire::test(HollowfangCommunity::class)->set('config.welcomeText', 'New welcome')
            ->call('saveSettings')->assertSet('config.welcomeText', 'New welcome')->assertHasNoErrors();
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['config']['welcomeText'] === 'New welcome');
    }

    public function test_ticket_filters_match_member_and_staff_without_fetching_again(): void
    {
        Http::fake([
            '*/api/v4/status' => Http::response(['config' => $this->settings(), 'tickets' => [
                ['id' => 'ticket-first', 'userId' => '222222222222222222', 'claimedBy' => '333333333333333333', 'status' => 'claimed'],
                ['id' => 'ticket-other', 'userId' => '444444444444444444', 'status' => 'closed'],
            ]]),
            '*/api/announcements/directory' => Http::response([]),
        ]);
        Livewire::test(HollowfangCommunity::class)->set('ticketSearch', '333333333333333333')
            ->set('ticketStatus', 'claimed')->assertSee('222222222222222222')->assertDontSee('444444444444444444')
            ->set('ticketStatus', 'closed')->assertSee('Tidak ada ticket yang cocok.');
        Http::assertSentCount(2);
    }

    public function test_non_owner_cannot_reload_community_settings(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(HollowfangCommunity::class)->assertForbidden();
        Http::assertNothingSent();
    }
}
