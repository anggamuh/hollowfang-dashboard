<?php
namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\ManukApiService;
use App\Services\BotStatusHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

#[Layout('layouts.hollowfang')]
class ControlCenter extends Component
{
    public string $activeMenu = 'overview';
    public string $webhookTab = 'announcements';
    public string $musicFeedback = '';
    public string $musicError = '';

    public array $menus = [
        'overview' => 'Overview',
        'monitoring' => 'Bot Monitoring',
        'webhooks' => 'Announcements & Embeds',
        'gateway' => 'Discord Gateway',
        'moderation' => 'Moderation',
        'members' => 'Members & Roles',
        'music' => 'Music Monitoring',
        'events' => 'Events',
        'community' => 'Community Control',
        'logs' => 'Audit Logs',
        'settings' => 'Settings',
    ];

    public function changeMenu(string $menu): void
    {
        if (array_key_exists($menu, $this->menus)) {
            $this->activeMenu = $menu;
        }
    }

    public function changeWebhookTab(string $tab): void
    {
        if (in_array($tab, ['announcements', 'webhooks'], true)) {
            $this->webhookTab = $tab;
        }
    }

    public function controlMusic(string $action): void
    {
        $user = auth()->user();
        abort_unless($user && $user->discord_id && in_array(
            (string) $user->discord_id,
            array_map('strval', config('hollowfang.owner_discord_ids', [])),
            true
        ), 403);

        $this->musicFeedback = '';
        $this->musicError = '';

        $allowed = ['pause', 'resume', 'skip', 'shuffle', 'autoplay_on', 'autoplay_off', 'loop_off', 'loop_song', 'loop_queue'];
        if (! in_array($action, $allowed, true)) {
            $this->musicError = 'Aksi musik tidak dikenali.';
            return;
        }

        $base = trim((string) config('services.manuk.base_url'));
        $token = trim((string) config('services.manuk.token'));
        $parts = parse_url($base);
        if (strlen($token) < 32 || ! is_array($parts)
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            $this->musicError = 'Konfigurasi API ManukAPP belum valid.';
            return;
        }

        $key = 'hollowfang:music-control:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->musicError = 'Terlalu banyak perintah musik. Coba lagi sebentar.';
            return;
        }
        RateLimiter::hit($key, 60);

        try {
            $response = Http::acceptJson()->withToken($token)->withoutRedirecting()
                ->connectTimeout(2)->timeout(8)->post(rtrim($base, '/').'/api/music/control', [
                    'actor' => (string) $user->discord_id,
                    'action' => $action,
                ]);

            if ($response->successful() && $response->json('ok') === true) {
                $this->musicFeedback = 'Perintah '.str_replace('_', ' ', $action).' berhasil dijalankan.';
                return;
            }
            $this->musicError = match ($response->status()) {
                401 => 'Token API ManukAPP tidak cocok.',
                403 => 'Akun Discord ini bukan administrator server.',
                404 => 'Endpoint kontrol musik belum tersedia. Update bot ManukAPP dulu.',
                409 => 'Sesi musik tidak aktif atau perintah tidak dapat dijalankan.',
                429 => 'Bot membatasi perintah musik. Coba lagi sebentar.',
                default => 'Bot belum bisa menjalankan perintah (HTTP '.$response->status().').',
            };
        } catch (Throwable) {
            $this->musicError = 'Tidak bisa menghubungi kontrol musik ManukAPP.';
        }
    }

    public function refreshNow(): void
    {
        // A Livewire round-trip re-reads the monitoring snapshot through render().
        // This operation is read-only and does not issue any Discord commands.
    }

    public function render()
    {
        $health = app(ManukApiService::class)->health();

        return view('livewire.control-center', [
            'botHealth' => $health,
            'statusHistory' => app(BotStatusHistory::class)->record($health),
        ]);
    }
}
