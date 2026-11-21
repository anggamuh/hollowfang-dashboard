<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HollowfangVisualPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_rendered_workspace_has_accessible_navigation_and_exports_ci_preview(): void
    {
        config()->set('hollowfang.owner_discord_ids', ['111111111111111111']);
        config()->set('services.manuk.base_url', 'http://127.0.0.1:3210');
        config()->set('services.manuk.token', str_repeat('x', 40));
        $user = User::factory()->create(['name' => 'Angga']);
        $user->forceFill(['discord_id' => '111111111111111111', 'discord_username' => 'Angga'])->save();
        Http::fake(['*' => Http::response([
            'bot' => ['connected' => true, 'name' => 'ManukAPP', 'ping_ms' => 24, 'uptime_seconds' => 98760],
            'discord' => ['connected' => true, 'guild_name' => 'Hollowfang', 'total_members' => 128, 'online_members' => null],
            'lavalink' => ['connected' => true, 'status' => 'Connected'],
            'music' => ['active_sessions' => 1, 'queue_length' => 8, 'now_playing' => ['title' => 'Hollowfang radio', 'artist' => 'Community listening session', 'position_ms' => 60000, 'duration_ms' => 180000]],
        ])]);
        $response = $this->actingAs($user)->get('/control');
        $response->assertOk()->assertSee('hf-search-title')->assertSee('hf-mobile-title')
            ->assertSee('Lewati navigasi')->assertSee('Presence belum tersedia');

        // Opt-in CI-only static render with synthetic data; never a public route.
        if (getenv('HF_VISUAL_PREVIEW') === '1') {
            $html = $response->getContent();
            $css = '';
            foreach (glob(public_path('build/assets/*.css')) as $path) $css .= file_get_contents($path);
            $html = preg_replace('~<script\b[^>]*>.*?</script>~si', '', $html);
            $html = preg_replace('~<link\b[^>]*>~si', '', $html);
            $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
            @mkdir(storage_path('app/visual-preview'), 0755, true);
            file_put_contents(storage_path('app/visual-preview/overview.html'), $html);
        }
    }
}
