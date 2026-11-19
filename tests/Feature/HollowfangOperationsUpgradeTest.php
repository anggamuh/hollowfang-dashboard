<?php

namespace Tests\Feature;

use App\Livewire\ControlCenter;
use App\Livewire\HollowfangV3;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class HollowfangOperationsUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hollowfang.owner_discord_ids', ['111111111111111111']);
        config()->set('services.manuk.token', str_repeat('x', 40));
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        Http::fake(['*' => Http::response(['bot' => ['connected' => false]])]);
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['discord_id' => '111111111111111111'])->save();

        return $user;
    }

    public function test_owner_can_access_both_announcement_and_encrypted_webhook_interfaces(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(ControlCenter::class)
            ->call('changeMenu', 'webhooks')
            ->assertSee('Bot Announcements')
            ->assertSee('Discord Webhook Manager')
            ->assertSet('webhookTab', 'announcements')
            ->call('changeWebhookTab', 'webhooks')
            ->assertSet('webhookTab', 'webhooks')
            ->assertSee('Embed Composer')
            ->call('changeWebhookTab', 'invalid')
            ->assertSet('webhookTab', 'webhooks');
    }

    public function test_moderation_rejects_self_target_without_posting_to_bot(): void
    {
        $user = $this->owner();
        $this->actingAs($user);

        Livewire::test(HollowfangV3::class, ['initialTab' => 'moderation'])
            ->set('targetId', '111111111111111111')
            ->set('action', 'timeout')
            ->set('reason', 'Testing safety guard')
            ->call('moderate')
            ->assertSee('Tidak bisa memoderasi akun sendiri.');

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_role_changes_reject_unlisted_role_ids(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(HollowfangV3::class, ['initialTab' => 'members'])
            ->set('targetId', '222222222222222222')
            ->set('roleId', '333333333333333333')
            ->set('roleOperation', 'add')
            ->call('changeRole')
            ->assertSee('Role tidak tersedia');

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }
}
