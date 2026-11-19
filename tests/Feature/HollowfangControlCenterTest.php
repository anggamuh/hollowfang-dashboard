<?php

namespace Tests\Feature;

use App\Livewire\ControlCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HollowfangControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('hollowfang.owner_discord_ids', ['111111111111111111']);
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        config()->set('services.manuk.token', str_repeat('x', 40));

    }

    private function fakeOnline(): void
    {
        Http::fake(['127.0.0.1:3210/api/health' => Http::response([
            'bot' => ['connected' => true, 'name' => 'crypt', 'ping_ms' => 24, 'uptime_seconds' => 300],
            'discord' => ['connected' => true, 'guild_id' => '222222222222222222', 'guild_name' => 'Hollowfang', 'total_members' => 65, 'online_members' => null],
            'lavalink' => ['connected' => true, 'status' => 'Connected'],
            'music' => ['active_sessions' => 1, 'queue_length' => 1, 'queue' => [['title' => 'Next Track', 'artist' => 'Another artist']], 'now_playing' => ['title' => 'Music Test', 'artist' => 'Artist Test', 'position_ms' => 2000, 'duration_ms' => 10000]],
        ])]);
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['discord_id' => '111111111111111111', 'discord_username' => 'Owner'])->save();

        return $user;
    }

    public function test_control_center_rejects_unlinked_or_non_owner_discord_accounts(): void
    {
        $this->actingAs(User::factory()->create())->get('/control')->assertForbidden();

        $other = User::factory()->create();
        $other->forceFill(['discord_id' => '333333333333333333'])->save();
        $this->actingAs($other)->get('/control')->assertForbidden();
    }

    public function test_owner_can_open_live_overview_without_fake_statistics(): void
    {
        $this->fakeOnline();
        $this->actingAs($this->owner())->get('/control')
            ->assertOk()
            ->assertSee('Music Test')
            ->assertSee('Hollowfang')
            ->assertSee('Music Sessions');
    }

    public function test_owner_can_open_music_gateway_and_settings_tabs(): void
    {
        $this->fakeOnline();
        $this->actingAs($this->owner());

        Livewire::test(ControlCenter::class)
            ->call('changeMenu', 'music')
            ->assertSet('activeMenu', 'music')
            ->assertSee('Music Test')
            ->assertSee('Next Track')
            ->call('changeMenu', 'gateway')
            ->assertSet('activeMenu', 'gateway')
            ->assertSee('Connection Diagnostics')
            ->call('changeMenu', 'settings')
            ->assertSet('activeMenu', 'settings')
            ->assertSee('MANUK_API_BASE_URL')
            ->assertDontSee(str_repeat('x', 40))
            ->call('changeMenu', 'not-real')
            ->assertSet('activeMenu', 'settings');
    }

    public function test_offline_snapshot_shows_diagnostics_without_inventing_member_counts(): void
    {
        $this->actingAs($this->owner());
        Http::fake(['*' => Http::response(['error' => 'Unauthorized'], 401)]);

        Livewire::test(ControlCenter::class)
            ->assertSee('Monitoring offline')
            ->assertSee('Token API ditolak')
            ->assertDontSee('Music Test');
    }
}
