<?php

namespace Tests\Feature;

use App\Livewire\ControlCenter;
use App\Livewire\HollowfangV3;
use App\Models\User;
use App\Services\ManukApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HollowfangUsabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hollowfang.owner_discord_ids', ['111111111111111111']);
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        config()->set('services.manuk.token', str_repeat('x', 40));
        Cache::flush();
        Http::preventStrayRequests();
        $owner = User::factory()->create();
        $owner->forceFill(['discord_id' => '111111111111111111'])->save();
        $this->actingAs($owner);
    }

    public function test_snapshot_reuses_checks_expires_and_isolates_credentials(): void
    {
        Http::fake(['*' => Http::response(['bot' => ['connected' => true]])]);
        $service = app(ManukApiService::class);
        $first = $service->snapshot();
        $this->travel(5)->seconds();
        $this->assertSame($first, $service->snapshot());
        Http::assertSentCount(1);
        $this->travel(6)->seconds();
        $service->snapshot();
        Http::assertSentCount(2);
        config()->set('services.manuk.token', str_repeat('y', 40));
        $service->snapshot();
        Http::assertSentCount(3);
    }

    public function test_menu_navigation_reuses_snapshot_but_explicit_refresh_rechecks(): void
    {
        Http::fake(['*' => Http::response(['bot' => ['connected' => true]])]);
        $component = Livewire::test(ControlCenter::class)
            ->call('changeMenu', 'music')->call('changeMenu', 'gateway');
        Http::assertSentCount(1);
        $component->call('refreshNow');
        Http::assertSentCount(2);
    }

    public function test_menu_can_be_bookmarked_and_invalid_values_fall_back(): void
    {
        Http::fake(['*' => Http::response(['bot' => ['connected' => true]])]);
        Livewire::withQueryParams(['menu' => 'music'])->test(ControlCenter::class)
            ->assertSet('activeMenu', 'music');
        Livewire::withQueryParams(['menu' => 'missing'])->test(ControlCenter::class)
            ->assertSet('activeMenu', 'overview');
    }

    public function test_filters_and_pagination_do_not_fetch_members_again(): void
    {
        $members = [];
        for ($i = 0; $i < 15; $i++) {
            $members[] = ['id' => (string) (222222222222222220 + $i), 'displayName' => 'Member '.$i,
                'bot' => $i === 14, 'roleIds' => $i === 14 ? ['333333333333333333'] : []];
        }
        Http::fake([
            '*/api/v3/members*' => Http::response(['members' => $members, 'partial' => true]),
            '*/api/v3/roles' => Http::response(['roles' => [['id' => '333333333333333333', 'name' => 'Music']]]),
        ]);
        Livewire::test(HollowfangV3::class, ['initialTab' => 'members'])
            ->assertSee('Member 0')->assertDontSee('Member 14')
            ->call('goToMemberPage', 2)->assertSee('Member 14')->assertDontSee('Member 0')
            ->set('memberType', 'bot')->assertSet('memberPage', 1)->assertSee('Member 14')
            ->set('memberRole', '444444444444444444')->assertSee('Belum ada anggota')
            ->call('goToMemberPage', 999)->assertSet('memberPage', 1);
        Http::assertSentCount(2);
    }

    public function test_moderation_results_allow_selecting_a_target(): void
    {
        Http::fake([
            '*/api/v3/members*' => Http::response(['members' => [['id' => '222222222222222222', 'displayName' => 'Target User']]]),
            '*/api/v3/roles' => Http::response(['roles' => []]),
        ]);
        Livewire::test(HollowfangV3::class, ['initialTab' => 'moderation'])
            ->assertSee('Target User')->call('selectTarget', '222222222222222222')
            ->assertSet('targetId', '222222222222222222');
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_forged_or_stale_role_directory_cannot_authorize_a_change(): void
    {
        Http::fake([
            '*/api/v3/members*' => Http::response(['members' => []]),
            '*/api/v3/roles' => Http::response(['roles' => []]),
        ]);
        Livewire::test(HollowfangV3::class, ['initialTab' => 'members'])
            ->set('roles', [['id' => '333333333333333333', 'name' => 'Forged', 'managed' => false]])
            ->set('roleId', '333333333333333333')->set('targetId', '222222222222222222')
            ->call('changeRole')->assertSee('Role tidak tersedia');
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }
}
