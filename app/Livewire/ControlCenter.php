<?php
namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\ManukApiService;

#[Layout('layouts.hollowfang')]
class ControlCenter extends Component
{
    public string $activeMenu = 'overview';

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

    public function render()
    {
        return view('livewire.control-center', [
            'botHealth' => app(ManukApiService::class)->health(),
        ]);
    }
}
