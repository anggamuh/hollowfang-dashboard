<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Throwable;

class HollowfangV3 extends Component
{
    public string $tab = 'overview';
    public array $overview = [];
    public array $members = [];
    public array $logs = [];
    public array $events = [];
    public array $warnings = [];
    public array $roles = [];
    public string $roleId = '';
    public string $roleOperation = 'add';
    public bool $membersPartial = true;
    public string $search = '';
    public string $targetId = '';
    public string $action = 'timeout';
    public string $reason = '';
    public int $minutes = 10;
    public string $eventTitle = '';
    public string $eventDescription = '';
    public string $eventChannelId = '';
    public string $eventAt = '';
    public array $channels = [];
    public string $errorMessage = '';
    public string $successMessage = '';

    private function guardOwner(): void
    {
        $id = (string) (auth()->user()?->discord_id ?? '');
        abort_unless(auth()->check() && $id !== '' && in_array($id, array_map('strval', config('hollowfang.owner_discord_ids', [])), true), 403);
    }

    private function requestApi(string $method, string $path, array $payload = []): ?array
    {
        $this->guardOwner();
        $token = (string) config('services.manuk.token');
        $url = 'http://127.0.0.1:'.(int) config('services.manuk.v3_port', 3212);
        if (strlen($token) < 32) { $this->errorMessage = 'Token Manuk belum dikonfigurasi.'; return null; }
        try {
            $http = Http::acceptJson()->withToken($token)->withoutRedirecting()->connectTimeout(2)->timeout(10);
            $response = strtoupper($method) === 'POST' ? $http->post($url.$path, $payload) : $http->get($url.$path, $payload);
            if (! $response->successful()) {
                $this->errorMessage = 'Manuk V3 HTTP '.$response->status().': '.(string) ($response->json('error') ?? 'Operasi gagal');
                return null;
            }
            return is_array($response->json()) ? $response->json() : null;
        } catch (Throwable $e) {
            $this->errorMessage = 'Tidak dapat menghubungi Manuk V3 (port 3212).';
            return null;
        }
    }

    public function mount(string $initialTab = 'overview'): void {
        $this->guardOwner();
        $this->tab = in_array($initialTab, ['overview', 'members', 'moderation', 'logs', 'events'], true) ? $initialTab : 'overview';
        $this->refreshData();
    }

    public function switchTab(string $tab): void
    {
        $this->guardOwner();
        if (! in_array($tab, ['overview', 'members', 'moderation', 'logs', 'events'], true)) return;
        $this->tab = $tab; $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->guardOwner(); $this->errorMessage = '';
        if ($this->tab === 'overview') $this->overview = $this->requestApi('GET', '/api/v3/overview') ?? [];
        if ($this->tab === 'members' || $this->tab === 'moderation') {
            $data = $this->requestApi('GET', '/api/v3/members', ['q' => $this->search, 'limit' => 100]) ?? [];
            $this->members = $data['members'] ?? [];
            $this->membersPartial = (bool) ($data['partial'] ?? true);
        }
        if ($this->tab === 'logs') {
            $this->logs = ($this->requestApi('GET', '/api/v3/logs') ?? [])['logs'] ?? [];
            $this->warnings = ($this->requestApi('GET', '/api/v3/warnings') ?? [])['warnings'] ?? [];
        }
        if ($this->tab === 'members' || $this->tab === 'moderation') {
            $this->roles = ($this->requestApi('GET', '/api/v3/roles') ?? [])['roles'] ?? [];
        }
        if ($this->tab === 'events') {
            $this->events = ($this->requestApi('GET', '/api/v3/events') ?? [])['events'] ?? [];
            // Existing Announcement Directory is the source of approved channels.
            $this->channels = [];
            $directoryUrl = rtrim((string) config('hollowfang_announcements.url'), '/');
            if (preg_match('~^http://127\.0\.0\.1:\d{1,5}$~', $directoryUrl)) {
                try {
                    $r = Http::acceptJson()->withToken((string) config('services.manuk.token'))->connectTimeout(2)->timeout(5)->get($directoryUrl.'/api/announcements/directory');
                    if ($r->successful()) foreach (($r->json('channels') ?? []) as $c) {
                        if (is_array($c) && ($c['allowed'] ?? false) && preg_match('/^\d{15,22}$/', (string) ($c['id'] ?? ''))) $this->channels[(string) $c['id']] = (string) ($c['name'] ?? 'channel');
                    }
                } catch (Throwable $e) { /* Event list remains usable without directory */ }
            }
        }
    }

    public function selectTarget(string $id): void
    {
        $this->guardOwner();
        if (preg_match('/^\d{15,22}$/', $id)) { $this->targetId = $id; $this->successMessage = 'Anggota dipilih. Silakan kelola role atau buka tab Moderation.'; }
    }

    public function moderate(): void
    {
        $this->guardOwner();
        $data = $this->validate([
            'targetId' => ['required', 'regex:/^\d{15,22}$/'],
            'action' => ['required', 'in:warn,timeout,untimeout,kick,ban'],
            'reason' => ['required', 'string', 'min:3', 'max:300'],
            'minutes' => ['integer', 'min:1', 'max:40320'],
        ]);
        $data['targetId'] = (string) $data['targetId'];
        $data['actor'] = (string) auth()->user()->discord_id;
        $this->errorMessage = ''; $this->successMessage = '';
        $result = $this->requestApi('POST', $this->action === 'warn' ? '/api/v3/warn' : '/api/v3/moderate', $data);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = $this->action === 'warn' ? 'Warning berhasil dicatat di V3 (tidak mengirim DM).' : 'Tindakan berhasil dijalankan di Discord.';
            $this->dispatch('hf-v3-toast', message: $this->successMessage, type: 'success');
            $this->reason = '';
            $this->refreshData();
        }
    }

    public function changeRole(): void
    {
        $this->guardOwner();
        $data = $this->validate([
            'targetId' => ['required', 'regex:/^\d{15,22}$/'],
            'roleId' => ['required', 'regex:/^\d{15,22}$/'],
            'roleOperation' => ['required', 'in:add,remove'],
        ]);
        $this->errorMessage = ''; $this->successMessage = '';
        $result = $this->requestApi('POST', '/api/v3/role', [
            'actor' => (string) auth()->user()->discord_id,
            'targetId' => (string) $data['targetId'],
            'roleId' => (string) $data['roleId'],
            'operation' => $data['roleOperation'],
        ]);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = 'Role berhasil diperbarui di Discord.';
            $this->dispatch('hf-v3-toast', message: $this->successMessage, type: 'success');
            $this->refreshData();
        }
    }

    public function cancelEvent(string $id): void
    {
        $this->guardOwner();
        if (! preg_match('/^[a-f0-9-]{36}$/i', $id)) return;
        $this->errorMessage = ''; $this->successMessage = '';
        $result = $this->requestApi('POST', '/api/v3/events/cancel', [
            'actor' => (string) auth()->user()->discord_id,
            'id' => $id,
        ]);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = 'Jadwal dibatalkan.';
            $this->dispatch('hf-v3-toast', message: $this->successMessage, type: 'success');
            $this->refreshData();
        }
    }

    public function createEvent(): void
    {
        $this->guardOwner();
        $data = $this->validate([
            'eventTitle' => ['required', 'string', 'max:100'],
            'eventDescription' => ['nullable', 'string', 'max:1000'],
            'eventChannelId' => ['required', 'regex:/^\d{15,22}$/'],
            'eventAt' => ['required', 'date', 'after:now'],
        ]);
        if (! isset($this->channels[$this->eventChannelId])) { $this->errorMessage = 'Channel harus ada di allowlist Announcements.'; return; }
        $payload = [
            'actor' => (string) auth()->user()->discord_id,
            'title' => $data['eventTitle'],
            'description' => $data['eventDescription'] ?? '',
            'channelId' => (string) $data['eventChannelId'],
            'at' => \Carbon\Carbon::parse($data['eventAt'], config('app.timezone'))->toIso8601String(),
        ];
        $this->errorMessage = ''; $this->successMessage = '';
        $result = $this->requestApi('POST', '/api/v3/events', $payload);
        if (($result['ok'] ?? false) === true) {
            $this->successMessage = 'Pengumuman event terjadwal.';
            $this->dispatch('hf-v3-toast', message: $this->successMessage, type: 'success');
            $this->eventTitle = ''; $this->eventDescription = ''; $this->eventAt = '';
            $this->refreshData();
        }
    }

    public function render() { return view('livewire.hollowfang-v3'); }
}
