<?php
namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\ManukApiService;
use App\Services\BotStatusHistory;

#[Layout('layouts.hollowfang')]
class ControlCenter extends Component
{
    public string $activeMenu = 'overview';
    public string $webhookTab = 'announcements';

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
