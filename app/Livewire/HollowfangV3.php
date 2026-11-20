<?php

namespace App\Livewire;

use App\Support\HollowfangAudit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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
    public string $auditSearch = '';
    public string $auditCategory = 'all';
    public array $roles = [];
    public string $roleId = '';
    public string $roleOperation = 'add';
    public bool $membersPartial = true;
    public string $search = '';
    public string $memberType = 'all';
    public string $memberRole = '';
    public int $memberPage = 1;
    public string $targetId = '';
    public string $action = 'timeout';
    public string $reason = '';
    public int $minutes = 10;
    public string $eventSearch = '';
    public string $eventStatus = 'all';
    public string $eventTitle = '';
    public string $eventDescription = '';
    public string $eventChannelId = '';
    public string $eventAt = '';
    public array $channels = [];
    public string $errorMessage = '';
    public string $successMessage = '';

    private function enforceActionLimit(string $action): bool
    {
        $key = 'hollowfang:v3:'.$action.':'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->errorMessage = 'Terlalu banyak aksi. Coba kembali dalam satu menit.';
            return false;
        }
        RateLimiter::hit($key, 60);
        return true;
    }

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

    public function updatedAuditSearch(): void
    {
        $this->guardOwner();
        $this->auditSearch = mb_substr($this->auditSearch, 0, 100);
    }

    public function exportAuditCsv()
    {
        $this->guardOwner();
        if ($this->tab !== 'logs') {
            abort(403);
        }

        $this->validate([
            'auditSearch' => ['nullable', 'string', 'max:100'],
            'auditCategory' => ['required', 'in:all,moderation,role,event'],
        ]);

        // Query the authoritative V3 endpoint again rather than exporting a
        // possibly stale, Livewire-hydrated public property.
        $data = $this->requestApi('GET', '/api/v3/logs');
        if ($data === null || ! is_array($data['logs'] ?? null)) {
            $this->errorMessage = 'Gagal mengambil audit terbaru untuk ekspor.';
            return null;
        }

        $rows = HollowfangAudit::filter(
            array_slice($data['logs'], 0, 100),
            $this->auditSearch,
            $this->auditCategory
        );

        return response()->streamDownload(static function () use ($rows): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }
            HollowfangAudit::writeCsv($stream, $rows);
            fclose($stream);
        }, 'hollowfang-audit-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function updatedMemberType(): void { $this->memberPage = 1; }
    public function updatedMemberRole(): void { $this->memberPage = 1; }

    public function searchMembers(): void
    {
        $this->validate(['search' => ['nullable', 'string', 'max:100']]);
        $this->memberPage = 1;
        $this->refreshData();
    }

    public function goToMemberPage(int $page): void
    {
        $this->guardOwner();
        $this->memberPage = max(1, $page);
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
        if (! $this->enforceActionLimit('moderation')) return;
        if ($data['targetId'] === (string) auth()->user()->discord_id) {
            $this->errorMessage = 'Tidak bisa memoderasi akun sendiri.';
            return;
        }
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
        if (! $this->enforceActionLimit('roles')) return;
        // Re-read roles before a mutation; public Livewire state can be stale or modified.
        $directory = $this->requestApi('GET', '/api/v3/roles');
        if ($directory === null) return;
        $this->roles = is_array($directory['roles'] ?? null) ? $directory['roles'] : [];
        if (! collect($this->roles)->contains(fn ($role) => (string) ($role['id'] ?? '') === $data['roleId'] && ! ($role['managed'] ?? false))) {
            $this->errorMessage = 'Role tidak tersedia atau dikelola integrasi Discord.';
            return;
        }
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
        if (! $this->enforceActionLimit('events')) return;
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
            'eventAt' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);
        $scheduledAt = \Carbon\CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['eventAt'], 'Asia/Jakarta');
        if (! $scheduledAt || ! $scheduledAt->isFuture()) {
            $this->addError('eventAt', 'Jadwal harus setelah waktu sekarang (WIB).');
            return;
        }
        if (! isset($this->channels[$this->eventChannelId])) { $this->errorMessage = 'Channel harus ada di allowlist Announcements.'; return; }
        if (! $this->enforceActionLimit('events')) return;
        $payload = [
            'actor' => (string) auth()->user()->discord_id,
            'title' => $data['eventTitle'],
            'description' => $data['eventDescription'] ?? '',
            'channelId' => (string) $data['eventChannelId'],
            'at' => $scheduledAt->toIso8601String(),
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

    public function render()
    {
        $this->guardOwner();

        $filteredMembers = collect($this->members)->filter(function ($member) {
            if (! is_array($member) || ! isset($member['id'])) return false;
            if ($this->memberType === 'human' && ($member['bot'] ?? false)) return false;
            if ($this->memberType === 'bot' && ! ($member['bot'] ?? false)) return false;
            return $this->memberRole === '' || in_array($this->memberRole, array_map('strval', $member['roleIds'] ?? []), true);
        })->values();
        $memberPages = max(1, (int) ceil($filteredMembers->count() / 12));
        $this->memberPage = max(1, min($this->memberPage, $memberPages));

        $eventRows = collect($this->events)->filter(fn ($event) => is_array($event));
        $query = mb_strtolower(trim(mb_substr($this->eventSearch, 0, 100)));
        $filteredEvents = $eventRows->filter(fn ($event) =>
            ($this->eventStatus === 'all' || ($event['status'] ?? 'unknown') === $this->eventStatus)
            && ($query === '' || str_contains(mb_strtolower(implode(' ', [
                $event['title'] ?? '', $event['description'] ?? '', $event['channelId'] ?? '',
            ])), $query))
        );

        return view('livewire.hollowfang-v3', [
            'filteredEvents' => $filteredEvents,
            'eventStatuses' => $eventRows->pluck('status')->filter()->unique()->sort()->values(),
            'visibleMembers' => $filteredMembers->slice(($this->memberPage - 1) * 12, 12),
            'memberMatches' => $filteredMembers->count(),
            'memberPages' => $memberPages,
            'filteredAuditLogs' => HollowfangAudit::filter($this->logs, $this->auditSearch, $this->auditCategory),
        ]);
    }
}
