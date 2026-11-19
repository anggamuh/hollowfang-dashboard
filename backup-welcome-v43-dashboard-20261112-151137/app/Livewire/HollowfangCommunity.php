<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Throwable;

class HollowfangCommunity extends Component
{
    public array $config = [];
    public array $activity = [];
    public array $tickets = [];
    public array $capabilities = [];
    public array $channels = [];
    public array $roles = [];
    public string $panelChannelId = '';
    public string $panelRoleId = '';
    public string $words = '';
    public string $errorMessage = '';
    public string $successMessage = '';

    private function guardOwner(): void
    {
        $id = (string) (auth()->user()?->discord_id ?? '');
        abort_unless(auth()->check() && $id !== '' && in_array($id, array_map('strval', config('hollowfang.owner_discord_ids', [])), true), 403);
    }

    private function api(string $method, string $path, array $data = []): ?array
    {
        $this->guardOwner();
        $token = (string) config('services.manuk.token');
        if (strlen($token) < 32) { $this->errorMessage = 'Token ManukAPP belum valid.'; return null; }
        try {
            $http = Http::acceptJson()->withToken($token)->withoutRedirecting()->connectTimeout(2)->timeout(10);
            $port = $path === '/api/announcements/directory' ? (int) parse_url((string) config('hollowfang_announcements.url'), PHP_URL_PORT) : 3213;
            $port = $port > 0 && $port <= 65535 ? $port : 3213;
            $base = 'http://127.0.0.1:'.$port;
            $response = $method === 'POST' ? $http->post($base.$path, $data) : $http->get($base.$path, $data);
            if (! $response->successful()) {
                $this->errorMessage = 'API HTTP '.$response->status().': '.(string) ($response->json('error') ?? 'Request gagal');
                return null;
            }
            $result = $response->json();
            return is_array($result) ? $result : null;
        } catch (Throwable $e) { $this->errorMessage = 'Tidak bisa menghubungi ManukAPP (API V4).'; return null; }
    }

    public function mount(): void
    {
        $this->guardOwner();
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->guardOwner();
        $this->errorMessage = '';
        $result = $this->api('GET', '/api/v4/status');
        if ($result !== null) {
            $this->config = $result['config'] ?? [];
            $this->words = implode(', ', $this->config['blockedWords'] ?? []);
            $this->activity = $result['activity'] ?? [];
            $this->tickets = $result['tickets'] ?? [];
            $this->capabilities = $result['capabilities'] ?? [];
        }
        $directory = $this->api('GET', '/api/announcements/directory');
        if ($directory !== null) {
            $this->channels = [];
            foreach (($directory['channels'] ?? []) as $c) {
                $id = (string) ($c['id'] ?? '');
                if (preg_match('/^\d{15,22}$/', $id)) $this->channels[$id] = (string) ($c['name'] ?? $id);
            }
            $this->roles = [];
            foreach (($directory['roles'] ?? []) as $r) {
                $id = (string) ($r['id'] ?? '');
                if (preg_match('/^\d{15,22}$/', $id)) $this->roles[$id] = (string) ($r['name'] ?? $id);
            }
        }
    }

    public function saveSettings(): void
    {
        $this->guardOwner();
        $this->validate([
            'config.welcomeEnabled' => 'boolean', 'config.goodbyeEnabled' => 'boolean',
            'config.autoModEnabled' => 'boolean', 'config.blockLinks' => 'boolean',
            'config.welcomeChannelId' => ['nullable', 'regex:/^\d{15,22}$/'],
            'config.goodbyeChannelId' => ['nullable', 'regex:/^\d{15,22}$/'],
            'config.welcomeText' => 'required|string|max:1000', 'config.goodbyeText' => 'required|string|max:1000',
            'config.welcomeImageUrl' => 'nullable|url|starts_with:https://|max:1800',
            'config.welcomeRulesChannelId' => ['nullable', 'regex:/^\d{15,22}$/'],
            'config.welcomeInfoChannelId' => ['nullable', 'regex:/^\d{15,22}$/'],
            'config.spamLimit' => 'required|integer|min:3|max:30',
            'config.spamWindowSeconds' => 'required|integer|min:3|max:120',
            'words' => 'nullable|string|max:2000',
            'config.ticketCategoryId' => ['nullable', 'regex:/^\d{15,22}$/'],
            'config.ticketSupportRoleId' => ['nullable', 'regex:/^\d{15,22}$/'],
        ]);
        $payload = $this->config;
        $payload['blockedWords'] = array_values(array_unique(array_filter(array_map('trim', explode(',', mb_strtolower($this->words))))));
        $payload['exemptRoleIds'] = array_values(array_filter($payload['exemptRoleIds'] ?? [], fn ($id) => preg_match('/^\d{15,22}$/', (string) $id)));
        $result = $this->api('POST', '/api/v4/settings', ['actor' => (string) auth()->user()->discord_id, 'config' => $payload]);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = 'Pengaturan berhasil disimpan di ManukAPP.';
            $this->dispatch('hf-community-toast', message: $this->successMessage);
            $this->refreshData();
        }
    }

    public function testWelcome(): void
    {
        $this->guardOwner();
        $this->errorMessage = '';
        // Simpan konfigurasi terbaru sebelum mengirim preview.
        $this->saveSettings();
        if ($this->errorMessage !== '' || $this->getErrorBag()->isNotEmpty()) return;
        $result = $this->api('POST', '/api/v4/test-welcome', [
            'actor' => (string) auth()->user()->discord_id,
        ]);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = 'Welcome preview berhasil dikirim ke Discord.';
            $this->dispatch('hf-community-toast', message: $this->successMessage);
            $this->refreshData();
        }
    }

    public function publishPanel(string $kind): void
    {
        $this->guardOwner();
        $this->validate(['panelChannelId' => ['required', 'regex:/^\d{15,22}$/']]);
        if (! isset($this->channels[$this->panelChannelId])) { $this->errorMessage = 'Channel tidak ditemukan.'; return; }
        if (! in_array($kind, ['ticket', 'role'], true)) return;
        if ($kind === 'role') $this->validate(['panelRoleId' => ['required', 'regex:/^\d{15,22}$/']]);
        $payload = ['actor' => (string) auth()->user()->discord_id, 'channelId' => $this->panelChannelId, 'roleId' => $this->panelRoleId];
        $result = $this->api('POST', $kind === 'ticket' ? '/api/v4/publish-ticket' : '/api/v4/publish-role', $payload);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = 'Panel '.($kind === 'ticket' ? 'ticket' : 'self-role').' berhasil dikirim ke Discord.';
            $this->dispatch('hf-community-toast', message: $this->successMessage);
            $this->refreshData();
        }
    }

    public function downloadTicketTranscript(string $ticketId)
    {
        $this->guardOwner();
        if (! preg_match('/^[a-f0-9-]{36}$/', $ticketId)) {
            $this->errorMessage = 'ID ticket tidak valid.';
            return null;
        }
        $result = $this->api('GET', '/api/v4/ticket-transcript?id='.rawurlencode($ticketId));
        if (! is_array($result) || ! is_string($result['content'] ?? null)) {
            return null;
        }
        $content = $result['content'];
        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, 'hollowfang-ticket-'.$ticketId.'.txt', ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function render() { return view('livewire.hollowfang-community'); }
}
