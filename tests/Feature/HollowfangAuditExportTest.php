<?php

namespace Tests\Feature;

use App\Livewire\HollowfangV3;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HollowfangAuditExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hollowfang.owner_discord_ids', ['111111111111111111']);
        config()->set('services.manuk.token', str_repeat('x', 40));
        config()->set('services.manuk.v3_port', 3212);
        Http::fake([
            '127.0.0.1:3212/api/v3/logs' => Http::response(['logs' => [
                ['id' => 'a', 'action' => 'moderation.ban', 'actor' => '111111111111111111', 'target' => '222222222222222222', 'detail' => '=SUM(1,1)', 'at' => '2026-11-19T09:00:00Z'],
                ['id' => 'b', 'action' => 'role.add', 'actor' => '111111111111111111', 'target' => '333333333333333333', 'detail' => 'Regular member', 'at' => '2026-11-19T09:02:00Z'],
            ]]),
            '127.0.0.1:3212/api/v3/warnings' => Http::response(['warnings' => []]),
        ]);
    }

    private function user(string $discordId): User
    {
        $user = User::factory()->create();
        $user->forceFill(['discord_id' => $discordId])->save();

        return $user;
    }

    public function test_owner_can_filter_audit_records_and_export_fresh_data(): void
    {
        $this->actingAs($this->user('111111111111111111'));
        $component = Livewire::test(HollowfangV3::class, ['initialTab' => 'logs'])
            ->assertSee('Audit Explorer')
            ->assertSee('moderation.ban')
            ->assertSee('role.add')
            ->set('auditSearch', 'SUM')
            ->set('auditCategory', 'moderation')
            ->assertSee('moderation.ban')
            ->assertDontSee('role.add')
            ->call('exportAuditCsv')
            ->assertHasNoErrors();

        Http::assertSentCount(3); // mount logs + warnings, export logs again
    }

    public function test_non_owner_cannot_open_audit_explorer(): void
    {
        $this->actingAs($this->user('999999999999999999'));
        Livewire::test(HollowfangV3::class, ['initialTab' => 'logs'])->assertForbidden();
    }

    public function test_export_rejects_invalid_category(): void
    {
        $this->actingAs($this->user('111111111111111111'));
        Livewire::test(HollowfangV3::class, ['initialTab' => 'logs'])
            ->set('auditCategory', 'anything')
            ->call('exportAuditCsv')
            ->assertHasErrors(['auditCategory' => 'in']);
        Http::assertSentCount(2);
    }
}
